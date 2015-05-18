<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
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
 * Idp_list Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Idp_list extends MY_Controller {

    //put your code here
    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'idp'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->library('table');
        $this->load->library('zacl');
    }

    function showlist()
    {

        MY_Controller::$menuactive = 'idps';
        $this->title = lang('title_idplist');
        $this->load->helper('iconhelp');
        $resource = 'idp_list';
        $action = 'read';
        $group = 'default';
        $hasReadAccess = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$hasReadAccess)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistidps');
            return $this->load->view('page', $data);
        }

        $data['entitytype'] = 'idp';
        $data['titlepage'] = lang('rr_tbltitle_listidps');
        $data['subtitlepage'] = ' ';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('identityproviders'),'type'=>'current'),


        );

        $data['content_view'] = 'providers/providers_list_view';
        $this->load->view('page',$data);

    }

}

