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
 * Auth Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Auth extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    function index()
    {
        if (!$this->j_auth->logged_in())
        {
            return $this->login();
        } else
        {
            redirect($this->config->item('base_url'), 'location');
        }
    }

    function logout()
    {
        if ($this->j_auth->logged_in())
        {
            $this->j_auth->logout();
        }
        $this->load->view('auth/logout');
    }

    function ssphpauth()
    {
        if ($this->j_auth->logged_in())
        {
            redirect($this->config->item('base_url'), 'location');
        }
        $spsp = $this->config->item('simplesamlphp');
        if (empty($spsp['enabled']))
        {
            show_error('Federated access is not enabled', 403);
        }
        if (empty($spsp['location']) || !file_exists($spsp['location']))
        {
            log_message('error', 'location of simeplsamlphp is not set or not exist. check config file and check $[simplesamlphp][location]');
            show_error('Server error', 500);
        }

        if(!isset($spsp['attributes']))
        {
            log_message('error', 'missing defined $[simplesamlphp][attributes]');
            show_error('Server error', 500);
        }
        $mapped = $spsp['attributes'];
        if (empty($mapped['username']) or empty($mapped['mail']))
        {
            log_message('error', 'missing defined $[simplesamlphp][attributes][username] or/and $[simplesamlphp][attributes][mail] in config ');
            show_error('Server error', 500);
        }
        require_once($spsp['location']);
        $auth = new \SimpleSAML_Auth_Simple('' . $spsp['authsourceid'] . '');
        $auth->requireAuth();

        $attributes = $auth->getAttributes();
      
      
        
        if (!empty($attributes['' . $mapped['username'] . '']))
        {
             
            if (is_array($attributes['' . $mapped['username'] . '']) && count($attributes['' . $mapped['username'] . '']) == 1)
            {
               
                $username = reset($attributes['' . $mapped['username'] . '']);
          
             
                if (empty($username))
                {
                      
                    show_error('Missing atribute from IdP', 403);
                }
            } else
            {
                log_message('warning', 'Missing or multiple values found for attr: ' . $mapped['username'] . ' ');
                show_error('Missing or multiple values found for attr', 403);
            }
        } else
        {
            log_message('warning', 'Couldn find ' . $mapped['username'] . ' provider by simplesaml');
            show_error('Missing atribute from IdP to map as username', 403);
        }
      
        $mail = null;
        if (!empty($attributes['' . $mapped['mail'] . '']))
        {
 
            if (is_array($attributes['' . $mapped['mail'] . '']) && count($attributes['' . $mapped['mail'] . '']) > 0)
            {

                $mail = reset($attributes['' . $mapped['mail'] . '']);
    
                if (empty($mail))
                {
                    log_message('warning', 'IdP didnt provide mail');
                    show_error('Missing mail attribute', 403);
                }
            } else
            {
                log_message('warning', 'IdP didnt provide mail');
                show_error('Missing mail attribute', 403);
            }
        } else
        {
            log_message('warning', 'IdP didnt provide mail');
            show_error('Missing mail attribute', 403);
        }
     
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
 
        if (!empty($user))
        {
    
            $can_access = (bool) ($user->isEnabled() && $user->getFederated());
            if (!$can_access)
            {
                show_error(lang('rerror_youraccountdisorfeddis'), 403);
            }
            $session_data = $user->getBasic();
            $userprefs = $user->getUserpref();

            $this->session->set_userdata($session_data);
            if (!empty($userprefs) && array_key_exists('board', $userprefs))
            {
                $this->session->set_userdata(array('board' => $userprefs['board']));
            }
            $_SESSION['username'] = $session_data['username'];
            $_SESSION['user_id'] = $session_data['user_id'];
            $_SESSION['logged'] = 1;
            if (!empty($timeoffset) && is_numeric($timeoffset))
            {
                $_SESSION['timeoffset'] = (int) $timeoffset;
            }
            $ip = $_SERVER['REMOTE_ADDR'];
            $user->setIP($ip);
            $user->updated();
            $this->em->persist($user);
            $this->load->library('tracker');
            $track_details = 'Authn from ' . $ip . '  with federated access';
            $this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
            $this->em->flush();
        } else
        {
      
            $can_autoregister = $this->config->item('autoregister_federated');
       
            if (!$can_autoregister)
            {
        
                log_message('error', 'User authorization failed: ' . $username . ' doesnt exist in RR');
                show_error(' ' . htmlentities($username) . ' - ' . lang('error_usernotexist') . ' ' . lang('applyforaccount') . ' <a href="mailto:' . $this->config->item('support_mailto') . '?subject=Access%20request%20from%20' . $mail . '">' . lang('rrhere') . '</a>', 403);
            } else
            {   
                $attrs = array('username' => $username, 'mail' => $mail);
               
                $reg = $this->registerUser($attrs);
              
                if ($reg !== TRUE)
                {
                    show_error('User couldnt be registered.', 403);
                }
            }
            redirect(current_url(), 'location');
        }
        redirect(base_url(), 'location');
    }

    function login()
    {
        $this->title = lang('title_login');

        $shib = $this->config->item('Shibboleth');
        if ($shib['enabled'] === TRUE)
        {
            $this->data['shib_url'] = base_url() . $shib['loginapp_uri'];
        } else
        {
            $this->data['shib_url'] = null;
        }
        $this->data['title'] = 'Login';
        /**
         * @todo check if no looping
         */
        $cu = $this->session->flashdata('target');
        $this->session->set_flashdata('target', $cu);

        if ($this->j_auth->logged_in())
        {
//already logged in so no need to access this page
            redirect($this->config->item('base_url'), 'location');
        }

//validate form input
        $this->form_validation->set_rules('username', lang('rr_username'), 'required|xss_clean');
        $this->form_validation->set_rules('password', lang('rr_password'), 'required');
        $validated = $this->form_validation->run();
        if ($validated === TRUE)
        {
            if ($this->j_auth->login($this->input->post('username'), $this->input->post('password')))
            {
                $cu = $this->session->flashdata('target');
                $this->session->set_flashdata('message', $this->j_auth->messages());
                if (!empty($cu))
                {
                    redirect($cu, 'location');
                } else
                {
                    redirect($this->config->item('base_url'), 'location');
                }
            } else
            {
                $this->session->set_flashdata('message', $this->j_auth->errors());

                redirect('auth/login', 'location'); //use redirects instead of loading views for compatibility with MY_Controller libraries
            }
        } else
        {  //the user is not logging in so display the login page
//set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['username'] = array(
                'name' => 'username',
                'id' => 'username',
                'type' => 'text',
                'value' => $this->form_validation->set_value('username'),
            );
            $this->data['password'] = array('name' => 'password',
                'id' => 'password',
                'type' => 'password',
            );


            $this->data['dontshowsigning'] = true;
            $this->data['title'] = lang('authn_form');
            $this->data['showloginform'] = TRUE;
            $this->data['content_view'] = 'auth/empty_view';
            $this->load->view('page', $this->data);
        }
    }

    private function get_shib_username()
    {
        $username_var = $this->config->item('Shib_username');
        if (isset($_SERVER[$username_var]))
        {
            return $_SERVER[$username_var];
        } elseif (isset($_SERVER['REDIRECT_' . $username_var]))
        {
            return $_SERVER['REDIRECT_' . $username_var];
        } else
        {
            return FALSE;
        }
    }
    private function get_shib_fname()
    {
        $fname_var = $this->config->item('Shib_fname');
        if(empty($fname_var))
        {
           return false;
        }
        if (isset($_SERVER[$fname_var]))
        {
            return $_SERVER[$fname_var];
        }
        return false;
    }
    private function get_shib_sname()
    {
        $fname_var = $this->config->item('Shib_sname');
        if(empty($sname_var))
        {
           return false;
        }
        if (isset($_SERVER[$sname_var]))
        {
            return $_SERVER[$sname_var];
        }
        return false;
    }

    private function get_shib_mail()
    {
        $email_var = $this->config->item('Shib_mail');
        if (isset($_SERVER[$email_var]))
        {
            return $_SERVER[$email_var];
        } elseif (isset($_SERVER['REDIRECT_' . $email_var]))
        {
            return $_SERVER['REDIRECT_' . $email_var];
        } else
        {
            return '';
        }
    }

    private function get_shib_idp()
    {
        if (isset($_SERVER['Shib-Identity-Provider']))
        {
            return $_SERVER['Shib-Identity-Provider'];
        } elseif (isset($_SERVER['REDIRECT_Shib-Identity-Provider']))
        {
            return $_SERVER['REDIRECT_Shib-Identity-Provider'];
        } elseif (isset($_SERVER['Shib_Identity_Provider']))
        {
            return $_SERVER['Shib_Identity_Provider'];
        } elseif (isset($_SERVER['REDIRECT_Shib_Identity_Provider']))
        {
            return $_SERVER['REDIRECT_Shib_Identity_Provider'];
        } else
        {
            return '';
        }
    }

    private function registerUser($attrs)
    {
        $username = $attrs['username'];
        $mail = $attrs['mail'];
     
        $checkIfExist = $this->em->getRepository("models\User")->findOneBy(array('email' => $mail));
        if (!empty($checkIfExist))
        {
            log_message('warning', 'Cannot register new user, provided mail already exists in db');
            return false;
        }

        $user = new models\User;
        $this->load->helper('random_generator');
        $randompass = str_generator();
        $user->setUsername($username);
        $user->setEmail($mail);
        $user->setSalt();
        $user->setPassword($randompass);
        $user->setLocalDisabled();
        $user->setFederatedEnabled();
        $user->setAccepted();
        $user->setEnabled();
        $user->setValid();
        $user->setUserpref(array());
        $defaultRole = $this->config->item('register_defaultrole');
        $allowedroles = array('Guest', 'Member');
        if (empty($defaultRole) || !in_array($defaultRole, $allowedroles))
        {
            $defaultRole = 'Guest';
        }
        $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $defaultRole));
        if (!empty($member))
        {
            $user->setRole($member);
        }
        $p_role = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $username));
        if (empty($p_role))
        {
            $p_role = new models\AclRole;
            $p_role->setName($username);
            $p_role->setType('user');
            $p_role->setDescription('personal role for user ' . $username);
            $user->setRole($p_role);
            $this->em->persist($p_role);
        }
        $this->em->persist($user);
        $this->tracker->save_track('user', 'create', $username, 'user autocreated in the system', false);
        $this->em->flush();
        
        
        return true;
    }

    public function fedauth($timeoffset = null)
    {
        $shibb_valid = (bool) $this->get_shib_idp();
        if (!$shibb_valid)
        {
            log_message('error', 'This location should be protected by shibboleth in apache');
            show_error('Internal server error', 500);
        }
        if ($this->j_auth->logged_in())
        {
            
        }
        $user_var = $this->get_shib_username();
        if (empty($user_var))
        {
            log_message('error', 'IdP didnt provide username');
            show_error('Internal server error: missing attribute from IdP', 500);
        }

        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user_var));
        if (!empty($user))
        {
            $can_access = (bool) ($user->isEnabled() && $user->getFederated());
            if (!$can_access)
            {
                show_error(lang('rerror_youraccountdisorfeddis'), 403);
            }
            $session_data = $user->getBasic();
            $userprefs = $user->getUserpref();

            $this->session->set_userdata($session_data);
            if (!empty($userprefs) && array_key_exists('board', $userprefs))
            {
                $this->session->set_userdata(array('board' => $userprefs['board']));
            }
            $_SESSION['username'] = $session_data['username'];
            $_SESSION['user_id'] = $session_data['user_id'];
            $_SESSION['logged'] = 1;
            if (!empty($timeoffset) && is_numeric($timeoffset))
            {
                $_SESSION['timeoffset'] = (int) $timeoffset;
            }
            $updatefullname = $this->config->item('shibb_updatefullname');
            if(!empty($updatefullname)&& $updatefullname === TRUE)
            {
               $fname = trim($this->get_shib_fname());
               $sname = trim($this->get_shib_sname());
               if(!empty($fname))
               {
                   $user->setGivenname(''.$fname.'');
                   $user->setSurname(''.$sname.'');
               }
            }
            $ip = $_SERVER['REMOTE_ADDR'];
            $user->setIP($ip);
            $user->updated();
            $this->em->persist($user);
            $this->load->library('tracker');
            $track_details = 'Authn from ' . $ip . '  with federated access';
            $this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
            $this->em->flush();
        } else
        {
            $can_autoregister = $this->config->item('autoregister_federated');
            if (!$can_autoregister)
            {
                log_message('error', 'User authorization failed: ' . $user_var . ' doesnt exist in RR');
                show_error(' ' . htmlentities($user_var) . ' - ' . lang('error_usernotexist') . ' ' . lang('applyforaccount') . ' <a href="mailto:' . $this->config->item('support_mailto') . '?subject=Access%20request%20from%20' . $user_var . '">' . lang('rrhere') . '</a>', 403);
            } else
            {
                $email_var = $this->get_shib_mail();

                if (empty($email_var))
                {
                    log_message('error', 'User cannot be autocreated: email address is missing');
                    show_error(lang('error_noemail'), 403);
                    return;
                }
                $attrs = array('username' => $user_var, 'mail' => $email_var);
                $reg = $this->registerUser($attrs);

                if ($reg !== TRUE)
                {
                    show_error('User couldnt be registered.', 403);
                }
                redirect(current_url(), 'location');
            }
        }
        redirect(base_url(), 'location');
    }

}
