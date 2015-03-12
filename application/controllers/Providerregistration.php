<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Class Providerregistration
 */
class Providerregistration extends MY_Controller{


    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        MY_Controller::$menuactive = 'reg';

    }
    function idp()
    {

    }
    function sp()
    {

        $this->title = lang('title_spreg');
        $data['titlepage'] = lang('rr_sp_register_title');
        $data['content_view'] = 'sp/sp_registration_form_view';
        $data['breadcrumbs'] = array(
            array('url'=>'#','name'=>lang('rr_sp_register_title'),'type'=>'current'),

        );
        $this->load->view('page', $data);
    }
}
