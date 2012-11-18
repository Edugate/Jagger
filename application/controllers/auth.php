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
            //redirect them to the login page
            //redirect('auth/login', 'refresh');
            return $this->login();
        }
        elseif (!$this->j_auth->is_admin())
        {
            //redirect them to the home page because they must be an administrator to view this
            redirect($this->config->item('base_url'), 'refresh');
        }
        else
        {
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            //list the users
            //$this->data['users'] = $this->j_auth->get_users_array();
            $this->load->view('auth/index', $this->data);
        }
    }

    function logout()
    {
        if ($this->j_auth->logged_in())
        {
            $this->j_auth->logout();
            //redirect($this->config->item('base_url'), 'refresh');
        }
        $this->load->view('auth/logout');
    }

    function login()
    {

        $shib = $this->config->item('Shibboleth');
        if ($shib['enabled'] === TRUE)
        {
            $this->data['shib_url'] = base_url() . $shib['loginapp_uri'];
        }
        else
        {
            $this->data['shib_url'] = null;
        }
        $this->data['title'] = "Login";
        /**
         * @todo check if no looping
         */
        $cu = $this->session->flashdata('target');
        $this->session->set_flashdata('target', $cu);

        if ($this->j_auth->logged_in())
        {
            //already logged in so no need to access this page
            redirect($this->config->item('base_url'), 'refresh');
        }

        //validate form input
        $this->form_validation->set_rules('username', lang('rr_username'), 'required|xss_clean');
        $this->form_validation->set_rules('password', lang('rr_password'), 'required');

        if ($this->form_validation->run() == true)
        {
            if ($this->j_auth->login($this->input->post('username'), $this->input->post('password')))
            {
                $cu = $this->session->flashdata('target');
                $this->session->set_flashdata('message', $this->j_auth->messages());
                if (!empty($cu))
                {
                    redirect($cu, 'refresh');
                }
                else
                {
                    redirect($this->config->item('base_url'), 'refresh');
                }
            }
            else
            {
                $this->session->set_flashdata('message', $this->j_auth->errors());

                redirect('auth/login', 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
            }
        }
        else
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
            $this->data['content_view'] = 'auth/login';
            $this->load->view('page', $this->data);
        }
    }

    private function get_shib_username()
    {
        $username_var = $this->config->item('Shib_username');
        if (isset($_SERVER[$username_var]))
        {
            return $_SERVER[$username_var];
        }
        elseif (isset($_SERVER['REDIRECT_' . $username_var]))
        {
            return $_SERVER['REDIRECT_' . $username_var];
        }
        else
        {
            return '';
        }
    }

    private function get_shib_idp()
    {
        if (isset($_SERVER['Shib-Identity-Provider']))
        {
            return $_SERVER['Shib-Identity-Provider'];
        }
        elseif (isset($_SERVER['REDIRECT_Shib-Identity-Provider']))
        {
            return $_SERVER['REDIRECT_Shib-Identity-Provider'];
        }
        elseif (isset($_SERVER['Shib_Identity_Provider']))
        {
            return $_SERVER['Shib_Identity_Provider'];
        }
        elseif (isset($_SERVER['REDIRECT_Shib_Identity_Provider']))
        {
            return $_SERVER['REDIRECT_Shib_Identity_Provider'];
        }
        else
        {
            return '';
        }
    }

    public function fedauth()
    {
        $shibb_valid = (bool) $this->get_shib_idp();
        if (!$shibb_valid)
        {
            log_message('error', $this->mid . 'This location should be protected by shibboleth in apache');
            show_error($this->mid . 'Internal server error', 500);
        }
        if ($this->j_auth->logged_in())
        {
            //$this->j_auth->logout();
            //show_error($this->mid.'To login via federated access you need to logout first',401);
        }
        $username_set = (bool) $this->get_shib_username();
        if (!$username_set)
        {
            log_message('error', $this->mid . 'IdP didnt provide username');
            show_error($this->mid . 'Internal server error', 500);
        }
        $user_var = $this->get_shib_username();
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user_var));
        if (!empty($user))
        {
            $can_access = (bool) ($user->isEnabled() && $user->getFederated());
            if (!$can_access)
            {
                show_error($this->mid . ' ' . lang('rerror_youraccountdisorfeddis'), 403);
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
        }
        else
        {
            $can_autoregister = $this->config->item('autoregister_federated');
            if (!$can_autoregister)
            {
                show_error('An account for ' . $user_var . ' doesn\'t exist in the Resource Registry. You can request access <a href="mailto:' . $this->config->item('support_mailto') . '?subject=Access%20request%20from%20' . $user_var . '">here</a>', 403);
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $user->setIP($ip);
        $user->updated();
        $this->em->persist($user);
        $this->load->library('tracker');
        $track_details = 'authenticated from ' . $ip . ' using federated access';
        $this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
        $this->em->flush();
        redirect(base_url(), 'refresh');
    }

}

