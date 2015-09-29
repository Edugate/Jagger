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
 * Sp_list Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Sp_list extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->jauth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'sp'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->library('table');
        $this->load->library('zacl');
    }

    function showlist()
    {

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
            array('url' => '#', 'name' => lang('serviceproviders'),'type'=>'current'),

        );

        $data['content_view'] = 'providers/providers_list_view';
        $this->load->view('page', $data);

    }


}

