<?php
if (!defined('BASEPATH')) {
    exit('Ni direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Idp_list extends MY_Controller
{

    //put your code here
    public function __construct() {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'idp'));
        $this->load->library('table');
        $this->load->library('zacl');
    }


    public function showlist() {

        MY_Controller::$menuactive = 'idps';
        $this->title = lang('title_idplist');
        $this->load->helper('iconhelp');
        $resource = 'idp_list';
        $action = 'read';
        $group = 'default';
        $hasReadAccess = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$hasReadAccess) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistidps');
            return $this->load->view('page', $data);
        }

        $data['entitytype'] = 'idp';
        $data['titlepage'] = lang('rr_tbltitle_listidps');
        $data['subtitlepage'] = ' ';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('identityproviders'), 'type' => 'current'),
        );
        $data['content_view'] = 'providers/idpListV2';
        $this->load->view('page', $data);

    }

}

