<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Authenticate Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Authenticate extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        log_message('debug','DoLogin');
        $this->load->library('form_validation');
    }

    public function loadloginform()
    {
        if($this->input->is_ajax_request())
        {

        }

    }
    
    public function dologin()
    {
        log_message('debug','DoLogin');
        $isReferrerOK = FALSE;
        $baseurl = base_url();
        if(isset($_SERVER['HTTP_REFERER']))
        {
           if(strpos($_SERVER['HTTP_REFERER'],$baseurl) === 0)
           {
               $isReferrerOK = TRUE;
           } 
        }
        
        $auth_error = '';
        if($this->input->is_ajax_request() && $isReferrerOK && ($_SERVER['REQUEST_METHOD'] === 'POST'))
        {
               J_auth::$timeOffset = (int) $this->input->post('browsertimeoffset');
               log_message('debug','client browser timeffset: '.J_auth::$timeOffset);
               $_SESSION['timeoffset'] = J_auth::$timeOffset;
           if($this->j_auth->logged_in())
           {
               echo 'OK';
           }
           else
           {
               $this->form_validation->set_rules('username', lang('rr_username'), 'required|xss_clean');
               $this->form_validation->set_rules('password', lang('rr_password'), 'required');
               $validated = $this->form_validation->run();
               if ($validated === TRUE)
               {
                   if ($this->j_auth->login($this->input->post('username'), $this->input->post('password')))
                   {
                       echo 'OK';
                   }
                   else
                   {
                         $auth_error = '<div id="notification_error">'.lang('error_authn').'</div>';
                   }
               }
               else
               {
                    $auth_error = '<div id="notification_error">'.lang('error_incorrectinput').'</div>';
               }

           }


        }
        else
        {
             set_status_header(401);
             $auth_error = '<div id="notification_error">not ajax</div>';
        }
        echo $auth_error;
       
    }
    


}
