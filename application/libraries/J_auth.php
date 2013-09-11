<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * J_auth Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class J_auth {

    protected $em;
    protected $ci;
    protected $status;
    protected $messages;
    protected $errors = array();

    public function __construct() {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->library('email');
        $this->ci->load->helper('cookie');
    }

    public function login($identity, $password) {
        /**
         * @todo change to use static from model, add more condition like user is local,valid etc
         */
        try {
            $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $identity));
        } catch (PDOException $e) {
            log_message('error', $e);
            show_error("Server error", 500);
            log_message('error', "LLL" . $e);
            exit;
        }
        if ($u) {
            log_message('debug', '::::::::::::::::::Authn: user found: ' . $identity);
            $salt = $u->getSalt();
            log_message('debug', '::::::::::::::::::Authn: salt: ' . $salt);
            $encrypted_password = sha1($password . $salt);
            log_message('debug', '::::::::::::::::::Authn: enc_fill_pass: ' . $encrypted_password);
            $pass = $u->getPassword();
            log_message('debug', '::::::::::::::::::Authn: enc_db_pass: ' . $pass);
            if ($pass === $encrypted_password)
            {
                /**
                 * @todo test last login ip, last login time
                 */
                /**
                 * @todo set groups
                 */
                $ip = $_SERVER['REMOTE_ADDR'];
                $userprefs = $u->getUserpref();
                if(!empty($userprefs) && array_key_exists('board',$userprefs))
                {
                    $this->ci->session->set_userdata(array('board'=> $userprefs['board']));
                }
             
                $u->setIP($ip);
                $u->updated();
                $this->em->persist($u);
                $track_details = 'authenticated from ' . $ip . ' using local authentication';
                $this->ci->tracker->save_track('user', 'authn', $u->getUsername(), $track_details, false);
                $this->em->flush();

                $session_data = $u->getBasic();
                $_SESSION['logged'] = 1;
                $_SESSION['username'] = $session_data['username'];
                $_SESSION['user_id'] =  $session_data['user_id'];
                $this->set_message('login_successful');
                return TRUE;
            }
            else
            {
                $this->set_error('login_unsuccessful');
                return FALSE;
            }
        } 
        else 
        {
            $this->set_error('login_unsuccessful');
            return FALSE;
        }
    }

    public function logout() {
        $identity = $this->ci->config->item('identity', 'j_auth');
        $this->ci->session->sess_destroy();
        //session_unset('username');
        //session_unset('user_id');
        //session_unset('logged');
        //session_destroy();
        session_start();
        $this->set_message('logout_successful');
        return TRUE;
    }
    public function logged_in() {
        if(!empty($_SESSION['logged']) && !empty($_SESSION['username']))
        {
            log_message('debug','session is active for: ' . $_SESSION['username']);
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    public function current_user() {
        if(isset($_SESSION['username']))
        {
            return $_SESSION['username'];
        }
        return FALSE;
    }

    public function set_error($error) {
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

}

?>
