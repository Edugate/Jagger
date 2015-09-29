<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Jauth
{
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;
    protected $ci;
    protected $status;
    protected $messages;
    protected $errors = array();
    public static $timeOffset = 0;
    protected static $isAdmin;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('cookie');
        self::$timeOffset = (int)$this->ci->session->userdata('timeoffset') * 60;
        log_message('debug', 'TimeOffset  :' . self::$timeOffset);
    }

    public function finalizepartiallogin() {

        $usersession = $this->ci->session->userdata();
        /**
         * @var models\User $user
         */
        if (!empty($usersession['partiallogged']) && !empty($usersession['username'])) {
            try {
                $user = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $usersession['username'] . ''));
            } catch (PDOException $e) {
                log_message('error', $e);
                show_error("Server error", 500);
                exit;
            }
            if (empty($user)) {

                return false;
            }
            $ipAddr = $this->ci->input->ip_address();
            $userprefs = $user->getUserpref();
            if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
                $this->ci->session->set_userdata(array('board' => $userprefs['board']));
            }

            $user->setIP($ipAddr);
            $user->updated();
            $this->em->persist($user);
            $authntype = '';
            if (isset($usersession['authntype'])) {
                $authntype = $usersession['authntype'];
            }
            $trackDetails = 'Authn from ' . $ipAddr . ' ::  ' . $authntype . ' Authn and 2F';
            $this->ci->tracker->save_track('user', 'authn', $user->getUsername(), $trackDetails, false);

            $this->em->flush();
            $this->ci->session->set_userdata('logged', 1);
            $this->ci->session->unset_userdata('partiallogged');

            return true;
        }
        return false;
    }

    public function login($identity, $password) {
        /**
         * @var models\User $user
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $identity, 'local' => true));
        } catch (PDOException $e) {
            log_message('error', $e);
            show_error("Server error", 500);
            exit;
        }
        if ($user !== null) {
            $isPassMatch = $user->isPasswordMatch($password);
            if ($isPassMatch === true) {
                $twofactorauthn = $this->ci->config->item('twofactorauthn');
                $secondfactor = $user->getSecondFactor();
                if (!empty($twofactorauthn) && $twofactorauthn === true && !empty($secondfactor) && $secondfactor === 'duo') {
                    $sig_request = Duo::signRequest($this->ci->config->item('duo-ikey'), $this->ci->config->item('duo-skey'), $this->ci->config->item('duo-akey'), $user->getUsername());
                    $this->ci->session->set_userdata(
                        array('partiallogged' => 1,
                            'logged' => 0,
                            'username' => '' . $user->getUsername() . '',
                            'user_id' => '' . $user->getId() . '',
                            'secondfactor' => $secondfactor,
                            'authntype' => 'local')
                    );
                    return true;
                } else {
                    $ip = $this->ci->input->ip_address();
                    $userprefs = $user->getUserpref();
                    if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
                        $this->ci->session->set_userdata(array('board' => $userprefs['board']));
                    }

                    $user->setIP($ip);
                    $user->updated();
                    $this->em->persist($user);
                    $trackDetails = 'Authn from ' . $ip . ' ::  Local Authn';
                    $this->ci->tracker->save_track('user', 'authn', $user->getUsername(), $trackDetails, false);

                    $userSessionData = $user->getBasic();
                    $this->em->flush();
                    $this->ci->session->set_userdata(array(
                        'logged' => 1,
                        'username' => '' . $userSessionData['username'] . '',
                        'user_id' => '' . $userSessionData['user_id'] . '',
                        'showhelp' => '' . $userSessionData['showhelp'] . '',
                        'authntype' => 'local'
                    ));
                    $this->ci->session->sess_regenerate();
                    $this->set_message('login_successful');
                    return true;
                }
            } else {
                $this->setError('login_unsuccessful');
                return false;
            }
        } else {
            $this->setError('login_unsuccessful');
            return false;
        }
    }

    public function logout() {
        $this->ci->session->sess_destroy();
        $this->ci->session->sess_regenerate(true);
        $this->set_message('logout_successful');
        return true;
    }

    /**
     * @return bool
     */
    public function isLoggedIn() {
        $loggedin = trim($this->ci->session->userdata('logged'));
        $username = trim($this->ci->session->userdata('username'));
        if (!empty($loggedin) && !empty($username)) {
            log_message('debug', 'Session is active for: ' . $username . '');
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function loggedInAndAjax() {
        return ($this->isLoggedIn() && $this->ci->input->is_ajax_request());
    }

    /**
     * @return null|string
     */
    public function getLoggedinUsername() {
        $username = null;
        if ($this->isLoggedIn()) {
            $username = trim($this->ci->session->userdata('username'));
        }

        if (strlen($username) == 0) {
            $username = null;
        }
        return $username;
    }

    public function setError($error) {
        $this->errors[] = $error;

        return $error;
    }

    public function errors() {
        $_output = '';
        foreach ($this->errors as $error) {
            $_output .= "<p>" . $this->ci->lang->line($error) . "</p>";
        }

        return $_output;
    }

    public function set_message($message) {
        $this->messages[] = $message;

        return $message;
    }

    public function messages() {
        $_output = '';
        foreach ($this->messages as $message) {
            $_output .= "<p>" . $this->ci->lang->line($message) . "</p>";
        }

        return $_output;
    }

    public function isAdministrator() {
        if (self::$isAdmin === true) {
            return true;
        } elseif (self::$isAdmin === false) {
            return false;
        }

        $username = $this->getLoggedinUsername();
        if (empty($username)) {
            self::$isAdmin = false;
            return false;
        }
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $username . ''));
        if ($user === null) {
            log_message('error', 'isAdministrator: Browser client session from IP:' . $this->ci->input->ip_address() . ' references to nonexist user: ' . $username);
            $this->ci->session->sess_destroy();
            return false;
        }
        $adminRole = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator', 'type' => 'system'));
        if (empty($adminRole)) {
            log_message('error', 'isAdministrator: Administrator Role is missing in DB AclRoles tbl');

            return false;
        } else {
            $userRoles = $user->getRoles();
            if ($userRoles->contains($adminRole)) {
                log_message('debug', 'isAdministrator: user ' . $user->getUsername() . ' found in Administrator group');
                self::$isAdmin = true;
                return false;
            } else {
                log_message('debug', 'isAdministrator: user ' . $user->getUsername() . ' not found in Administrator group');
                self::$isAdmin = false;
                return false;
            }
        }
    }

}
