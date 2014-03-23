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
    public static $timeOffset = 0;

    public function __construct() {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('cookie');
        if(isset($_SESSION['timeoffset']))
        {
            self::$timeOffset = (int) $_SESSION['timeoffset'] * 60;
        }
        log_message('debug','TimeOffset  :'.self::$timeOffset);
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
                 * @todo set groups
                 */
                $ip = $this->ci->input->ip_address();
                $userprefs = $u->getUserpref();
                if(!empty($userprefs) && array_key_exists('board',$userprefs))
                {
                    $this->ci->session->set_userdata(array('board'=> $userprefs['board']));
                }
             
                $u->setIP($ip);
                $u->updated();
                $this->em->persist($u);
                $track_details = 'Authn from ' . $ip . ' ::  Local Authn';
                $this->ci->tracker->save_track('user', 'authn', $u->getUsername(), $track_details, false);

                $session_data = $u->getBasic();
                $this->em->flush();
                $this->ci->session->set_userdata(array(
                   'logged'=>1,
                   'username'=>''.$session_data['username'].'',
                   'user_id'=> ''.$session_data['user_id'].'',
                   'showhelp'=>$session_data['showhelp']
                ));
                $this->ci->session->sess_regenerate();
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
        //session_start();
        $this->ci->session->sess_regenerate(TRUE);
        $this->set_message('logout_successful');
        return TRUE;
    }
    public function logged_in() {
        if(!empty($_SESSION['logged']) && !empty($_SESSION['username']))
        {
            log_message('debug' , 'J_auth::$timeOffset : '.J_auth::$timeOffset);
            if(!empty($_SESSION['timeoffset']))
            {
               $timeoffset = $_SESSION['timeoffset'];
            }
            else
            {
               $timeoffset = 0;
            }
            log_message('debug','session is active for: ' . $_SESSION['username'] .' with set timeoffsett '.$timeoffset);
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
    public function isAdministrator()
    {
        $username = $this->current_user();
        if(empty($username))
        {
           return FALSE;
        }
        $u = $this->em->getRepository("models\User")->findOneBy(array('username'=>''.$username.''));
        if(empty($u))
        {
            log_message('error', 'isAdministrator: Browser client session from IP:'.$_SERVER['REMOTE_ADDR'] .' references to nonexist user: '.$username);
            $this->ci->session->sess_destroy();
            show_error('Access denied', 403);
        }
        $adminRole = $this->em->getRepository("models\AclRole")->findOneBy(array('name'=>'Administrator','type'=>'system'));
        if(empty($adminRole))
        {
            log_message('error', 'isAdministrator: Administrator Role is missing in DB AclRoles tbl');
        }
        else
        {
             $userRoles = $u->getRoles();
             if($userRoles->contains($adminRole))
             {
                log_message('debug','isAdministrator: user '.$u->getUsername().' found in Administrator group'); 
                return TRUE;
             }   
             else
             {
                log_message('debug','isAdministrator: user '.$u->getUsername().' not found in Administrator group'); 

             }
        }
        return FALSE;
      
        

    }



}

?>
