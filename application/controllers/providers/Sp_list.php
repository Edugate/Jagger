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
 *
 */

class Sp_list extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'sp'));
        $this->load->library('table');
        $this->load->library('zacl');
    }

    public function showlist() {

        MY_Controller::$menuactive = 'sps';
        $this->title = lang('title_splist');
        $this->load->helper('iconhelp');
        $resource = 'sp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_read_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistidps');
            $this->load->view('page', $data);
            return;
        }

        $data['entitytype'] = 'sp';
        $data['titlepage'] = lang('rr_tbltitle_listsps');
        $data['subtitlepage'] = ' ';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('serviceproviders'), 'type' => 'current'),

        );

        $data['content_view'] = 'providers/spListV2';
        $this->load->view('page', $data);

    }


}

