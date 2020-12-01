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
class Auth extends MY_Controller
{
    private $data;

    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function index() {
        if (!$this->jauth->isLoggedIn()) {
            return $this->login();
        } else {
            redirect($this->config->item('base_url'), 'location');
        }
    }

    public function logout() {
        if ($this->jauth->isLoggedIn()) {
            $this->jauth->logout();
        }
        if ($this->input->is_ajax_request()) {
            return $this->output->set_status_header(200)->set_output('OK');
        }
        $this->load->view('auth/logout');
    }

    public function fedregister() {
        $canApplyForAccount = (bool)$this->config->item("feduserapplyform") || false;
        $method = $this->input->method(true);
        if (!$this->input->is_ajax_request() || $method !== 'POST') {
            return $this->output->set_status_header(403)->set_output('Permission Denied');
        }
        if ($this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Already authenticated');
        }
        if($canApplyForAccount !== true){
            return $this->output->set_status_header(403)->set_output('Registration is disabled. Please contact support.');
        }

        $fedidentity = $this->session->userdata('fedidentity');
        $newUserData = array(
            'username' => null,
            'email' => null,
            'fname' => null,
            'sname' => null,
            'type' => 'federated',
            'ip' => $this->input->ip_address()
        );
        log_message('debug', __METHOD__ . ' fedregistration in post received' . serialize($this->session->userdata()));
        if (is_array($fedidentity)) {
            if (array_key_exists('fedusername', $fedidentity)) {
                $newUserData['username'] = trim($fedidentity['fedusername']);
            }
            if (array_key_exists('fedemail', $fedidentity)) {
                $newUserData['email'] = trim($fedidentity['fedemail']);
            }
            if (empty($newUserData['username']) || empty($newUserData['email'])) {
                $this->session->sess_regenerate(true);
                return $this->output->set_status_header(403)->set_output('missing some attrs like username or/and email');
            }
            if (array_key_exists('fedfname', $fedidentity)) {
                $newUserData['fname'] = trim($fedidentity['fedfname']);
            }
            if (array_key_exists('fedsname', $fedidentity)) {
                $newUserData['sname'] = trim($fedidentity['fedsname']);
            }
        }
        /**
         * @var models\User $checkuser
         */
        $checkuser = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $newUserData['username'] . ''));
        if ($checkuser !== null) {
            $this->session->sess_regenerate(true);
            return $this->output->set_status_header(403)->set_output('' . lang('err_userexist') . '');
        }
        /**
         * @var models\Queue $inqueue
         */
        $inqueue = $this->em->getRepository('models\Queue')->findOneBy(array('name' => '' . $newUserData['username'] . '', 'action' => 'Create'));
        if ($inqueue !== null) {
            return $this->output->set_status_header(403)->set_output('' . lang('err_userinqueue') . '');
        }
        $queue = new models\Queue;
        $queue->setAction('Create');
        $queue->setName($newUserData['username']);
        $queue->setEmail($newUserData['email']);
        $queue->setToken();
        $queue->addUser($newUserData);
        $this->em->persist($queue);
        /**
         * BEGIN send notification
         */
        if (!empty($newUserData['fname']) || !empty($newUserData['sname'])) {
            $reqfullname = $newUserData['fname'] . ' ' . $newUserData['sname'];
        } else {
            $reqfullname = 'unknown fullname';
        }
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        $templateArgs = array(
            'token' => $queue->getToken(),
            'srcip' => $this->input->ip_address(),
            'reqemail' => $newUserData['email'],
            'requsername' => $newUserData['username'],
            'reqfullname' => $reqfullname,
            'qurl' => '' . base_url('reports/awaiting/detail/' . $queue->getToken() . ''),
            'datetimeutc' => '' . $nowUtc->format('Y-m-d h:i:s') . ' UTC',
        );
        $mailTemplate = $this->emailsender->feduserRegistrationRequest($templateArgs);
        $this->emailsender->addToMailQueue(array(), null, $mailTemplate['subject'], $mailTemplate['body'], array(), false);

        /**
         * END send notification
         */
        try {
            $this->em->flush();
            if(PHP_SESSION_ACTIVE) {
                $this->session->sess_regenerate(true);
            }
        } catch (Exception $e) {
            return $this->output->set_status_header(500)->set_output('Unknown error occurred');
        }

        return $this->output->set_status_header(200)->set_output('' . lang('userregreceived') . '');
    }

    public function ssphpauth() {
        if ($this->jauth->isLoggedIn()) {
            redirect($this->config->item('base_url'), 'location');
        }
        $spsp = $this->config->item('simplesamlphp');
        if (empty($spsp['enabled'])) {
            show_error('Federated access is not enabled', 403);
        }
        if (empty($spsp['location']) || !file_exists($spsp['location'])) {
            log_message('error', 'location of simplesamlphp is not set or not exist. check config file and check $[simplesamlphp][location]');
            show_error('Server error', 500);
        }

        if (!isset($spsp['attributes'])) {
            log_message('error', 'missing defined $[simplesamlphp][attributes]');
            show_error('Server error', 500);
        }
        $mapped = $spsp['attributes'];
        if (empty($mapped['username']) || empty($mapped['mail'])) {
            log_message('error', 'missing defined $[simplesamlphp][attributes][username] or/and $[simplesamlphp][attributes][mail] in config ');
            show_error('Server error', 500);
        }
        require_once($spsp['location']);
        $auth = new \SimpleSAML_Auth_Simple('' . $spsp['authsourceid'] . '');
        $auth->requireAuth();

        $attributes = $auth->getAttributes();


        if (!empty($attributes['' . $mapped['username'] . ''])) {
            if (is_array($attributes['' . $mapped['username'] . '']) && count($attributes['' . $mapped['username'] . '']) == 1) {
                $username = reset($attributes['' . $mapped['username'] . '']);
                if (empty($username)) {
                    show_error('Missing attribute from IdP', 403);
                }
            } else {
                log_message('warning', 'Missing or multiple values found for attr: ' . $mapped['username'] . ' ');
                show_error('Missing or multiple values found for attr', 403);
            }
        } else {
            log_message('warning', 'Couldnt find ' . $mapped['username'] . ' provided by simplesaml');
            show_error('Missing attribute from IdP to map as username', 403);
        }
        $mail = null;
        if (!empty($attributes['' . $mapped['mail'] . ''])) {
            if (is_array($attributes['' . $mapped['mail'] . '']) && count($attributes['' . $mapped['mail'] . '']) > 0) {

                $mail = reset($attributes['' . $mapped['mail'] . '']);

                if (empty($mail)) {
                    log_message('warning', 'IdP didnt provide mail');
                    show_error('Missing mail attribute', 403);
                }
            } else {
                log_message('warning', 'IdP didnt provide mail');
                show_error('Missing mail attribute', 403);
            }
        } else {
            log_message('warning', 'IdP didnt provide mail');
            show_error('Missing mail attribute', 403);
        }

        /**
         * @var models\User $user
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));

        if ($user !== null) {

            $can_access = (bool)($user->isEnabled() && $user->getFederated());
            if (!$can_access) {
                show_error(lang('rerror_youraccountdisorfeddis'), 403);
            }
            $session_data = $user->getBasic();
            $userprefs = $user->getUserpref();

            $this->session->set_userdata($session_data);
            if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
                $this->session->set_userdata(array('board' => $userprefs['board']));
            }
            $this->session->set_userdata('username', '' . $session_data['username'] . '');
            $this->session->set_userdata('user_id', '' . $session_data['user_id'] . '');
            if (!empty($timeoffset) && is_numeric($timeoffset)) {
                $this->session->set_userdata('timeoffset', (int)$timeoffset);
            }
            $this->session->set_userdata('logged', 1);
            $ip = $this->input->ip_address();
            $user->setIP($ip);
            $user->updated();
            $this->em->persist($user);
            $this->load->library('tracker');
            $track_details = 'Authn from ' . $ip . '  with federated access';
            $this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
            $this->em->flush();
        } else {

            $can_autoregister = $this->config->item('autoregister_federated');
            if (!$can_autoregister) {

                log_message('error', 'User authorization failed: ' . $username . ' doesnt exist in RR');
                show_error(' ' . htmlentities($username) . ' - ' . lang('error_usernotexist') . ' ' . lang('applyforaccount') . ' <a href="mailto:' . $this->config->item('support_mailto') . '?subject=Access%20request%20from%20' . $mail . '">' . lang('rrhere') . '</a>', 403);
            } else {
                $attrs = array('username' => $username, 'mail' => $mail);
                try {
                    $nuser = $this->jauth->registerUser($attrs, 'federated', null);
                    $this->em->persist($nuser);
                } catch (Exception $e) {
                    show_error(html_escape($e->getMessage()), 403);
                }
                try {
                    $this->em->flush();
                } catch (Exception $e) {
                    show_error('Internal Server Error', 500);
                }
            }
        }
        redirect(base_url(), 'location');
    }

    public function login() {

        if ($this->jauth->isLoggedIn()) {
            redirect($this->config->item('base_url'), 'location');
        }
        $this->data['dontshowsigning'] = true;
        $this->data['title'] = lang('authn_form');
        $this->data['showloginform'] = true;
        $this->data['content_view'] = 'auth/empty_view';
        $this->load->view(MY_Controller::$page, $this->data);

    }

    /**
     * @return string
     */
    private function getShibUsername() {
        $usernameVarName = $this->config->item('Shib_username');
        $usernameValue = $this->input->server($usernameVarName);
        if ($usernameValue === null) {
            $usernameValue = $this->input->server('REDIRECT_' . $usernameVarName);
        }
        return trim($usernameValue);
    }

    /**
     * @return mixed|null
     */
    private function getShibFname() {
        $fnameVarName = $this->config->item('Shib_fname');
        if ($fnameVarName !== null) {
            $fname = $this->input->server($fnameVarName);
            if ($fname !== null) {
                return $fname;
            }
            $fname = $this->input->server('REDIRECT_' . $fnameVarName);
            if ($fname !== null) {
                return $fname;
            }
        }
        return null;
    }

    private function getShibSname() {
        $snameVarName = $this->config->item('Shib_sname');
        if ($snameVarName !== null) {
            $sname = $this->input->server($snameVarName);
            if ($sname !== null) {
                return $sname;
            }
            $sname = $this->input->server('REDIRECT_' . $snameVarName);
            if ($sname !== null) {
                return $sname;
            }
        }

        return null;
    }

    private function getShibAffiliation(){
        $affiliationVarName = $this->config->item('Shib_affiliation');
        $affiliation = $this->input->server($affiliationVarName);
        if($affiliation !== null) {
            return $affiliation;
        }
        $affiliation = $this->input->server('REDIRECT_' . $affiliationVarName);
        if($affiliation !== null) {
            return $affiliation;
        }
        return '';
    }

    private function getShibMail() {
        $emailVarName = $this->config->item('Shib_mail');
        $email = $this->input->server($emailVarName);
        if ($email !== null) {
            return $email;
        }
        $email = $this->input->server('REDIRECT_' . $emailVarName);
        if ($email !== null) {
            return $email;
        }
        return '';
    }

    private function getShibIdp() {
        $IdpEnvVars = array(
            'Shib-Identity-Provider',
            'REDIRECT_Shib-Identity-Provider',
            'Shib_Identity_Provider',
            'REDIRECT_Shib_Identity_Provider'
        );
        $idpVal = null;
        foreach ($IdpEnvVars as $val) {
            $idpVal = $this->input->server($val);
            if (!empty($idpVal)) {
                break;
            }
        }
        return $idpVal;
    }

    private function getShibGroups($groupsVarName) {
        $varToReturn = $this->input->server($groupsVarName);
        if ($varToReturn === null) {
            $varToReturn = $this->input->server('REDIRECT_' . $groupsVarName);
        }

        return $varToReturn;
    }

    private function getRolesFromAA() {
        $shibGroupsCnf = $this->config->item('Shib_groups');
        if (empty($shibGroupsCnf)) {
            return null;
        }
        $groups = $this->getShibGroups($shibGroupsCnf);
        $group_administrator = $this->config->item('register_group_administrator');
        $group_member = $this->config->item('register_group_member');
        $group_guest = $this->config->item('register_group_guest');

        // Check configuration parameters for group names in the AA
        if (empty($group_administrator)) {
            $group_administrator = 'Administrator';
        }
        if (empty($group_member)) {
            $group_member = 'Member';
        }
        if (empty($group_guest)) {
            $group_guest = 'Guest';
        }

        // Obtain user group by looking at AA attribute
        if (strstr($groups, $group_administrator) !== false) {
            $userGroup = 'Administrator';
        } else if (strstr($groups, $group_member) !== false) {
            $userGroup = 'Member';
        } else if (strstr($groups, $group_guest) !== false) {
            $userGroup = 'Guest';
        } else {
            log_message('warning', 'The group attribute is not present in federated authentication');

            return null;
        }

        return $userGroup;

    }

    /**
     * @param \models\User $user
     */
    private function assignRolesFromAA(models\User $user) {

        $roleFromAA = $this->getRolesFromAA();
        if ($roleFromAA !== null) {
            /**
             * @var models\AclRole $member
             */
            $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $roleFromAA, 'type' => 'system'));
            if ($member !== null) {
                /**
                 * @var models\AclRole[] $userRoles
                 */
                $userRoles = $user->getRoles();
                foreach ($userRoles as $role) {
                    $roleType = $role->getType();
                    if ($roleType === 'system') {
                        $user->unsetRole($role);
                    }
                }
                $user->setRole($member);

            }
        }
    }

    public function fedauth($timeoffset = null) {
        log_message('debug', __METHOD__ . ' fired');
        $isShibValid = $this->getShibIdp();
        if ($isShibValid === null) {
            log_message('error', __METHOD__ . ':: ' . current_url() . ':: This location should be protected by shibboleth in apache');
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
        if ($this->jauth->isLoggedIn()) {
            redirect('' . base_url() . '', 'location');
        }
        $userValue = $this->getShibUsername();
        if ($userValue === '') {
            log_message('error', __METHOD__ . ': IdP: ' . $this->getShibIdp() . ' didnt provide username');
            return $this->output->set_status_header(500)->set_output('Internal server error: missing required attribute in SAML response from Identity Provider');
        }

        /**
         * @var models\User $user
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $userValue));
        if ($user !== null) {
            $canAccess = (bool)($user->isEnabled() && $user->getFederated());
            if (!$canAccess) {
                show_error(lang('rerror_youraccountdisorfeddis'), 403);
            }
            $session_data = $user->getBasic();
            $userprefs = $user->getUserpref();
            $this->session->set_userdata('username', '' . $session_data['username'] . '');
            $this->session->set_userdata('user_id', '' . $session_data['user_id'] . '');
            $this->session->set_userdata('authntype', 'federated');

            $systemTwoFactor = $this->config->item('twofactorauthn');
            if ($systemTwoFactor === true) {
                $userSecondFactor = $user->getSecondFactor();
                $systemAllowed2Factors = (array)$this->config->item('2fengines');
                if (!empty($userSecondFactor) && in_array($userSecondFactor, $systemAllowed2Factors)) {
                    $this->session->set_userdata('partiallogged', 1);
                    $this->session->set_userdata('secondfactor', $userSecondFactor);
                    $this->session->set_userdata('twofactor', 1);
                    $this->session->set_userdata('logged', 0);
                } else {

                    $this->session->set_userdata('logged', 1);
                }
            } else {

                $this->session->set_userdata('logged', 1);
            }
            $this->session->set_userdata($session_data);
            if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
                $this->session->set_userdata('board', $userprefs['board']);
            }
            if (!empty($timeoffset) && is_numeric($timeoffset)) {

                $this->session->set_userdata('timeoffset', (int)$timeoffset);
            }
            $updatefullname = $this->config->item('shibb_updatefullname');
            if ($updatefullname === true) {
                $fname = trim($this->getShibFname());
                $sname = trim($this->getShibSname());
                if ($fname !== '') {
                    $user->setGivenname('' . $fname . '');
                }
                if ($sname !== '') {
                    $user->setSurname('' . $sname . '');
                }
            }
            $updateemail = $this->config->item('shibb_updateemail');
            $emailFromIdP = trim($this->getShibMail());
            if (filter_var($emailFromIdP, FILTER_VALIDATE_EMAIL)) {
            }
            if ($updateemail === true) {
                if (filter_var($emailFromIdP, FILTER_VALIDATE_EMAIL)) {
                    $user->setEmail($emailFromIdP);
                } else {
                    log_message('warning', __METHOD__ . ':: it looks like system received invalid email address from idp: ' . $emailFromIdP);
                }

            }
            $islogged = $this->session->userdata('logged');
            if (!empty($islogged)) {

                $ip = $this->input->ip_address();
                $user->setIP($ip);
                $this->assignRolesFromAA($user);
                $user->updated();
                $this->em->persist($user);
                $this->load->library('tracker');
                $track_details = 'Authn from ' . $ip . '  with federated access';
                $this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
                $this->em->flush();


            }
        } else {
            $fnameVarName = $this->getShibFname();
            $snameVarName = $this->getShibSname();
            $emailVarName = $this->getShibMail();
            $canAutoRegister = $this->config->item('autoregister_federated');
            if (empty($emailVarName)) {
                log_message('warning', __METHOD__ . ' User hasnt provided email attr during federated access');
                show_error(lang('error_noemail'), 403);
            }

            if (!$canAutoRegister) {
                log_message('error', 'User authorization failed: ' . $userValue . ' doesnt exist in RR');
                $canApplyForAccount = (bool) $this->config->item("feduserapplyform") || false;

                if($canApplyForAccount === true) {
                    $fedidentity = array('fedusername' => $userValue, 'fedfname' => $this->getShibFname(), 'fedsname' => $this->getShibSname(), 'fedemail' => $this->getShibMail());
                    $this->session->set_userdata(array('fedidentity' => $fedidentity));
                    $data['content_view'] = 'feduserregister_view';
                }
                else {
                    $data['content_view'] = 'feduserregisterdisabled_view';
                }

                return $this->load->view(MY_Controller::$page, $data);
            } else {

                $attrs = array('username' => $userValue, 'mail' => $emailVarName, 'fname' => $fnameVarName, 'sname' => $snameVarName);
                try {
                    $nuser = $this->jauth->registerUser($attrs, 'federated', $this->getRolesFromAA());
                    $this->em->persist($nuser);

                } catch (Exception $e) {
                    show_error(html_escape($e->getMessage()), 403);
                }

                try {
                    $this->em->flush();
                } catch (Exception $e) {
                    show_error(html_escape($e->getMessage()), 403);
                }


                redirect(current_url(), 'location');
            }
        }
        redirect(base_url(), 'location');
    }

}
