<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Class Providerregistration
 */
class Providerregistration extends MY_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        MY_Controller::$menuactive = 'reg';

    }

    public function idp()
    {
        $data['titlepage'] = lang('rr_idp_register_title');
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_idp_register_title'), 'type' => 'current'),

        );
        $data['content_view'] = 'providerregisterform_view';
        $data['formtype'] = 'idp_registration';
        $this->load->view('page', $data);
    }

    public function sp()
    {

        $this->title = lang('title_spreg');
        $data['formtype'] = 'sp_registration';
        $data['titlepage'] = lang('rr_sp_register_title');
        $data['content_view'] = 'providerregisterform_view';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_sp_register_title'), 'type' => 'current'),

        );
        $this->load->view('page', $data);
    }
    public function idpsp(){
        $this->title = lang('title_spreg');
        $data['formtype'] = 'idpsp_registration';
        $data['titlepage'] = lang('rr_idpsp_register_title');
        $data['content_view'] = 'providerregisterform_view';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_idpsp_register_title'), 'type' => 'current'),

        );
        $this->load->view('page', $data);
    }
}
