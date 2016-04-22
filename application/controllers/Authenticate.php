<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Authenticate extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
    }


    public function resetloginform() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('no ajax');
        }
        return $this->jauth->logout();

    }

    public function getloginform() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_content_type('text/html')->set_status_header(401)->set_output('no ajax request');
        }
        $isPartialLogged = $this->session->userdata('partiallogged');
        $currentuser = $this->session->userdata('username');
        $secondfactor = $this->session->userdata('secondfactor');
        $twofactoauthn = $this->config->item('twofactorauthn');
        if ($this->jauth->isLoggedIn()) {
            $result = array('logged' => 1);
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));
        }
        $result = array(
            'logged' => 0,
            'partiallogged' => (int)$isPartialLogged,
            'username' => $currentuser,
            'twofactor' => (int)$twofactoauthn,
            'secondfactor' => $secondfactor
        );
        if ($isPartialLogged && !empty($currentuser) && $twofactoauthn && !empty($secondfactor)) {
            if ($secondfactor === 'duo') {
                $result['html'] = $this->genDuo();
            }
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    private function genDuo() {
        $sig_request = Duo::signRequest($this->config->item('duo-ikey'), $this->config->item('duo-skey'), $this->config->item('duo-akey'), $this->session->userdata('username'));
        $html = '<script src="' . base_url() . 'js/duo/Duo-Web-v2.js"></script>' .
            form_open('', array('id' => 'duo_form')) . '<input type="hidden" id="duo_host" value="' . $this->config->item('duo-host') . '">' .
            '<input type="hidden" id="duo_sig_request" value="' . $sig_request . '">' . form_close() .
            "<script>
			$(document).ready(function() {
			$('#spinner').show();
			Duo.init({
			'host': '" . $this->config->item('duo-host') . "',
			'post_action': '" . base_url() . "authenticate/dologin',
			'sig_request': '" . $sig_request . "'
		});
	//	Duo.ready();
		});
		</script>  " .
            '<iframe id="duo_iframe" width="600" height="250" frameborder="0" allowtransparency="true" style="background: transparent;" onload="document.getElementById(\'spinner\').style.display=\'none\';"></iframe>';

        return $html;
    }

    /**
     * @return bool
     */
    private function validateDoLogin() {
        $baseurl = base_url();
        $httpReferer = (string)$this->input->server('HTTP_REFERER');
        if ((strpos($httpReferer, $baseurl) === 0)) {
            $isReferrerOK = true;
        }

        return (bool)($this->input->is_ajax_request() && $isReferrerOK && ($_SERVER['REQUEST_METHOD'] === 'POST'));
    }

    public function dologin() {
        $twofactorauthn = $this->config->item('twofactorauthn');
        if ($this->validateDoLogin() !== true) {
            return $this->output->set_status_header(401)->set_output('no ajax');
        }
        if (empty(jauth::$timeOffset)) {
            jauth::$timeOffset = (int)$this->input->post('browsertimeoffset');
            log_message('debug', 'client browser timeoffset: ' . jauth::$timeOffset);
            $this->session->set_userdata('timeoffset', '' . jauth::$timeOffset . '');
        }
        if ($this->jauth->isLoggedIn()) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => true, 'result' => 'OK')));
        }
        $userSessionData = $this->session->userdata();
        if (!empty($userSessionData) && isset($userSessionData['secondfactor']) && isset($userSessionData['partiallogged']) && !empty($twofactorauthn) && isset($userSessionData['username']) && $userSessionData['secondfactor'] === 'duo') {
            $sig_response = $this->input->post('sig_response');
            if (!empty($sig_response)) {
                $resp = Duo::verifyResponse($this->config->item('duo-ikey'), $this->config->item('duo-skey'), $this->config->item('duo-akey'), $sig_response);
                if ($resp !== NULL) {
                    $this->session->set_userdata('logged', 1);
                    $finalize = $this->jauth->finalizepartiallogin();
                    if ($finalize) {
                        $result = array('success' => true, 'result' => 'OK');
                    } else {
                        $result = array('success' => false, 'result' => 'unknown');
                    }
                    return $this->output->set_content_type('application/json')->set_output(json_encode($result));
                }
            } else {
                $html = $this->genDuo();
                $result = array('result' => 'secondfactor', 'html' => $html);
                return $this->output->set_content_type('application/json')->set_output(json_encode($result));
            }

        }
        $this->form_validation->set_rules('username', lang('rr_username'), 'trim|required');
        $this->form_validation->set_rules('password', lang('rr_password'), 'trim|required');
        if ($this->form_validation->run() === true) {
            if ($this->jauth->login($this->input->post('username'), $this->input->post('password'))) {
                $result = array('success' => true, 'result' => 'OK');
                if ($this->session->userdata('partiallogged') === 1 && $this->session->userdata('logged') === 0) {
                    $html = $this->genDuo();
                    $result = array('result' => 'secondfactor', 'html' => $html);
                }
                return $this->output->set_content_type('application/json')->set_output(json_encode($result));
            }
            return $this->output->set_status_header(401)->set_output('' . lang('error_authn') . '');

        }
        return $this->output->set_status_header(401)->set_output('' . lang('error_incorrectinput') . '');
    }

}
