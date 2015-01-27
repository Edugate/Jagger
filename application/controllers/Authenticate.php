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

    public function resetloginform()
    {
        if (!$this->input->is_ajax_request())
        {
            set_status_header(401);
            echo 'no ajax request';
            return;
        }
        return $this->j_auth->logout();
        
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
        if ($this->j_auth->logged_in())
        {
            $result = array('logged' => 1);
            $this->output->set_content_type('application/json')->set_output(json_encode($result));
            return;
        }
        $result = array(
            'logged' => 0,
            'partiallogged' => (int) $isPartialLogged,
            'username' => $currentuser,
            'twofactor' => (int) $twofactoauthn,
            'secondfactor' => $secondfactor,
            'sess' => $_SESSION
        );
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
        return;
        if ($isPartialLogged && $currentuser && $twofactoauthn && $secondfactor)
        {
            $result = array('logged' => 0, 'partiallogged' => 1, 'username' => $currentuser, 'twofactor' => 1, 'secondfactor' => $secondfactor);
        }
        else
        {
            $result = array('logged' => 0, 'partiallogged' => 0, 'inputfields' => array('0' => array('name' => 'username', 'label' => lang('rr_username')), '1' => array('name' => 'password', 'label' => lang('rr_password'))));
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    private function genDuo()
    {
        $sig_request = Duo::signRequest($this->config->item('duo-ikey'), $this->config->item('duo-skey'), $this->config->item('duo-akey'), $this->session->userdata('username'));
        $html = '<script src="' . base_url() . 'js/duo/Duo-Web-v1.js"></script>';

        $html .='<input type="hidden" id="duo_host" value="' . $this->config->item('duo-host') . '">';
        $html .= '<input type="hidden" id="duo_sig_request" value="' . $sig_request . '">';


        $html .= "<script>
                                   
                                     $(document).ready(function() {
                                     Duo.init({
                                   'host': '" . $this->config->item('duo-host') . "',
                                   
                                     'post_action': '" . base_url() . "authenticate/dologin',
                                    'sig_request': '" . $sig_request . "',
                                  
                                     });
                                      Duo.ready();
                                      });
                              </script>  ";
        $html .= '<iframe id="duo_iframe" width="600" height="250" frameborder="0" allowtransparency="true" style="background: transparent;"></iframe>';
        
        return $html;
    }

    public function dologin()
    {
        log_message('debug', 'DoLogin');
        $isReferrerOK = FALSE;
        $baseurl = base_url();
        $twofactorauthn = $this->config->item('twofactorauthn');

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
                $result = array('success' => true, 'result' => 'OK');
                $this->output->set_content_type('application/json')->set_output(json_encode($result));
                return;
            }
            else
            {

                $userSessionData = $this->session->userdata();
                if (!empty($userSessionData) && isset($userSessionData['secondfactor']) && isset($userSessionData['partiallogged']) && !empty($twofactorauthn) && isset($userSessionData['username']))
                {
                    if ($userSessionData['secondfactor'] === 'duo')
                    {
                        $sig_response = $this->input->post('sig_response');
                        if (!empty($sig_response))
                        {
                            $resp = Duo::verifyResponse($this->config->item('duo-ikey'), $this->config->item('duo-skey'), $this->config->item('duo-akey'), $sig_response);
                            if ($resp != NULL)
                            {
                                $_SESSION['logged'] = 1;
                                $result = array('success' => true, 'result' => 'OK');
                                $this->output->set_content_type('application/json')->set_output(json_encode($result));
                                return;
                            }
                        }
                        else
                        {


                            $html = $this->genDuo();
                            $result = array('result' => 'secondfactor', 'html' => $html);
                            $this->output->set_content_type('application/json')->set_output(json_encode($result));
                            return;
                        }
                    }
                }

                $this->form_validation->set_rules('username', lang('rr_username'), 'trim|required');
                $this->form_validation->set_rules('password', lang('rr_password'), 'trim|required');
                $validated = $this->form_validation->run();
                if ($validated === TRUE)
                {
                    if ($this->j_auth->login($this->input->post('username'), $this->input->post('password')))
                    {
                        if (isset($_SESSION['partiallogged']) && $_SESSION['partiallogged'] === 1 && isset($_SESSION['logged']) && $_SESSION['logged'] === 0)
                        {
                            

                            $html = $this->genDuo();
                            $result = array('result' => 'secondfactor', 'html' => $html);
                            $this->output->set_content_type('application/json')->set_output(json_encode($result));
                            return;
                        }
                        else
                        {
                            $result = array('success' => true, 'result' => 'OK');
                            $this->output->set_content_type('application/json')->set_output(json_encode($result));
                            return;
                        }
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
