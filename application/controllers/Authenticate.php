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
        log_message('debug', 'DoLogin');
        $this->load->library('form_validation');
    }

    public function loadloginform()
    {
        if ($this->input->is_ajax_request())
        {
            
        }
    }

    public function getloginform()
    {
        if (!$this->input->is_ajax_request())
        {
            set_status_header(401);
            echo 'no ajax request';
            return;
        }
        $isPartialLogged = $this->session->userdata('partiallogged');
        $currentuser = $this->session->userdata('username');
        $secondfactor = $this->session->userdata('secondfactor');
        $twofactoauthn = $this->config->item('twofactorauthn');
        if($this->j_auth->logged_in())
        {
            $result = array('logged'=>1);
            $this->output->set_content_type('application/json')->set_output(json_encode($result));
            return;
        }
        $result = array(
            'logged'=>0,
            'partiallogged'=> (int) $isPartialLogged,
            'username'=> $currentuser,
            'twofactor'=> (int) $twofactoauthn,
            'secondfactor'=>$secondfactor,
            'sess'=>$_SESSION
        );
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
        return;
        if($isPartialLogged && $currentuser && $twofactoauthn && $secondfactor)
        {
            $result = array('logged'=>0, 'partiallogged'=>1, 'username'=>$currentuser,'twofactor'=>1, 'secondfactor'=>$secondfactor);
        }
        else
        {
            $result = array('logged'=>0, 'partiallogged'=>0, 'inputfields'=>array('0'=>array('name'=>'username','label'=>lang('rr_username')),'1'=>array('name'=>'password','label'=>lang('rr_password'))));
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function dologin()
    {
        log_message('debug', 'DoLogin');
        $isReferrerOK = FALSE;
        $baseurl = base_url();
        if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $baseurl) === 0))
        {
            $isReferrerOK = TRUE;
        }
        $auth_error = '';
        if ($this->input->is_ajax_request() && $isReferrerOK && ($_SERVER['REQUEST_METHOD'] === 'POST'))
        {
            J_auth::$timeOffset = (int) $this->input->post('browsertimeoffset');
            log_message('debug', 'client browser timeoffset: ' . J_auth::$timeOffset);
            $_SESSION['timeoffset'] = J_auth::$timeOffset;
            if ($this->j_auth->logged_in())
            {
                log_message('debug', 'GLO User loggedin');
                echo 'OK';
            }
            else
            {


                $this->form_validation->set_rules('username', lang('rr_username'), 'trim|required');
                $this->form_validation->set_rules('password', lang('rr_password'), 'trim|required');
                $validated = $this->form_validation->run();
                if ($validated === TRUE)
                {
                    if ($this->j_auth->login($this->input->post('username'), $this->input->post('password')))
                    {
                        echo 'OK';
                        return;
                    }
                    else
                    {
                        $auth_error = '' . lang('error_authn') . '';
                    }
                }
                else
                {
                    $auth_error = '' . lang('error_incorrectinput') . '';
                }
            }
        }
        else
        {
            set_status_header(401);
            $auth_error = 'not ajax';
        }
        set_status_header(401);
        echo $auth_error;
        return;
    }

}
