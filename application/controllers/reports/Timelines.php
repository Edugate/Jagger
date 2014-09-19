<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Timelines extends MY_Controller {

    private $alert;
    private $error_message;

    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->helper('url');
        $this->load->helper('cert');
        $this->load->library('table');
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        $this->title = lang('rr_timeline');


        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'general'));
            $this->load->library('zacl');           
            return;
        } else
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
    }

    public function showregistered($fedid = null)
    {
        if (!empty($fedid) && is_numeric($fedid))
        {
            $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
            if (empty($fed))
            {
                show_error('Federation not found', 404);
            }
            $providers = $fed->getActiveMembers();
            $data['fedname'] = $fed->getName();
        } else
        {
            $providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true));
        }
        $diag = array();
        if (!empty($providers))
        {
            foreach ($providers as $p)
            {
                $regdate = $p->getRegistrationDate();
                if (!empty($regdate))
                {
                    $d = date('Ymd',$regdate->format('U')+j_auth::$timeOffset);
                    $diag['known'][$d][$p->getId()] = array('n' => $p->getName(), 't' => $p->getType());
                } else
                {
                    $diag['unknown'][$p->getId()] = array('n' => $p->getName(), 't' => $p->getType());
                }
            }
        }
        $data['titlepage'] = '<a href="'.base_url().'federations/manage/show/'.base64url_encode($fed->getName()).'">'.lang('rr_federation').': '.$fed->getName().'</a>';
        $data['subtitlepage'] = lang('rr_progressforfed');
        $data['content_view'] = 'reports/registered_timeline_view';
        $data['grid'] = $diag;
        $this->load->view('page', $data);
    }

}
