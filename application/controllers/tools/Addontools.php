<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet <support@edugate.ie>
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Addontools extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    public function show()
    {
        $loggedin = $this->jauth->logged_in();
        if (!$loggedin) {
            if (!$this->input->is_ajax_request()) {
                redirect('auth/login', 'location');
                return;
            } else {
                set_status_header(403);
                echo 'No session';
                return;
            }
        }
        $data['titlepage'] = 'Tools';
        $data['content_view'] = 'tools/list_view';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rrtools'), 'type' => 'current'),

        );
        $this->load->view('page', $data);
    }

    public function msgdecoder()
    {
        $publicAccess = FALSE;
        $access = $this->config->item('addontools');
        if (!empty($access) && is_array($access) && isset($access['msgdecoder']) && $access['msgdecoder'] === true) {
            $publicAccess = true;
        }
        $loggedin = $this->jauth->logged_in();
        if (!$loggedin && !$publicAccess) {
            if (!$this->input->is_ajax_request()) {
                redirect('auth/login', 'location');
                return;
            } else {
                set_status_header(403);
                echo 'No session';
                return;
            }
        }

        if ($this->input->is_ajax_request() && $this->input->post()) {
            $encodedmsg = trim($this->input->post('inputmsg'));

            if (empty($encodedmsg)) {
                echo lang('error_noinput');
                return;
            }

            $isurl = parse_url($encodedmsg, PHP_URL_QUERY);
            if (!empty($isurl)) {
                $encodedmsg = $isurl;
            }

            $arr = array();
            $query = parse_str($encodedmsg, $arr);
            if (array_key_exists('SAMLResponse', $arr)) {
                $encodedmsg = $arr['SAMLResponse'];
            } elseif (array_key_exists('SAMLRequest', $arr)) {
                $encodedmsg = $arr['SAMLRequest'];
            } elseif (array_key_exists('LogoutRequest', $arr)) {
                $encodedmsg = $arr['LogoutRequest'];
            } elseif (array_key_exists('LogoutResponse', $arr)) {
                $encodedmsg = $arr['LogoutResponse'];
            } else {

                $encodedmsg = rawurldecode(stripslashes($encodedmsg));
            }


            $decodedmsg = jSAMLDecoder($encodedmsg);
            echo htmlspecialchars($decodedmsg);
            return;
        }

        $data['titlepage'] = 'SAML decoder';
        $data['content_view'] = 'tools/msgdecoder_view';
        $this->load->view('page', $data);
    }


}
