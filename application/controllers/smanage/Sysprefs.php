<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class Sysprefs extends MY_Controller
{

    function __construct()
    {
        parent:: __construct();
        $this->load->library('form_validation');
    }


    private function prefconftoarray(\models\Preferences $pref)
    {
        $status = $pref->getEnabled();
        $type = $pref->getType();
        $statusString = lang('rr_disabled');
        if ($status) {
            $statusString = lang('rr_enabled');
        }

        $result = array(
            'confname' => $pref->getName(),
            'displayname' => $pref->getDescname(),
            'desc' => $pref->getDescription(),
            'status' => $status,
            'type' => $type,
            'vtext' => $pref->getValue(),
            'varray' => $pref->getSerializedValue(),
            'cat' => $pref->getCategory(),
            'statusstring' => $statusString

        );
        return $result;

    }


    public function updateconf()
    {
        if (!$this->input->is_ajax_request() || !($this->input->method(TRUE) === 'POST')) {
            return $this->output->set_status_header(401)->set_output('Invalid request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Invalid session');
        }
        $isAdmin = $this->jauth->isAdministrator();
        if ($isAdmin!==true) {
            return $this->output->set_status_header(401)->set_output('Access denied');
        }


        $this->form_validation->set_rules('confname', 'Conf name', 'required|trim|alpha_numeric');
        if ($this->form_validation->run() !== true) {
            $result['error'] = validation_errors('<div>', '</div>');
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));
        }


        $postConfname = $this->input->post('confname');

        /**
         * @var models\Preferences $cpref
         */
        $cpref = $this->em->getRepository("models\Preferences")->findOneBy(array('name' => $postConfname));
        if ($cpref === null) {
            $result['error'] = 'No conf found';
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));
        }
        $type = $cpref->getType();

        $this->form_validation->reset_validation();
        if ($type === 'text') {
            $this->form_validation->set_rules('vtext', lang('label_text'), 'trim');

            if ($this->form_validation->run() !== true) {
                $result['error'] = 'd' . validation_errors('<div>', '</div>');
                return $this->output->set_content_type('application/json')->set_output(json_encode($result));
            }
            $tstring = $this->input->post('vtext');
            $cpref->setValue(strip_tags($tstring, '<a>'));
        }


        $postEnabled = $this->input->post('status');
        if (!empty($postEnabled) && $postEnabled === '1') {
            $cpref->setEnabled();
        } else {
            $cpref->setDisabled();
        }

        $this->em->persist($cpref);
        try {
            $tmpresult = $this->prefconftoarray($cpref);
            $this->em->flush();
            $this->j_cache->library('rrpreference', 'prefToArray', array('global'), -1);
            $result = $tmpresult;
            $result['result'] = 'OK';
        } catch (Exception $e) {
            $result['error'] = 'Error occurred';
            log_message('error', __METHOD__ . ' ' . $e);
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));


    }

    public function retgconf($confparam = null)
    {
        if (!$this->input->is_ajax_request() || !($this->input->method(TRUE) === 'GET') || empty($confparam)) {
            return $this->output->set_status_header(401)->set_output('Invalid request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Invalid session');
        }
        $isAdmin = $this->jauth->isAdministrator();
        if (!$isAdmin) {
            return $this->output->set_status_header(401)->set_output('Access denied');
        }
        $arg = $confparam;
        /**
         * @var models\Preferences $pref
         */
        $pref = $this->em->getRepository("models\Preferences")->findOneBy(array('name' => $arg));
        if ($pref === null) {
            return $this->output->set_status_header(404)->set_output('configuration param not found');
        }

        $result = $this->prefconftoarray($pref);

        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function show()
    {
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $isAdmin = $this->jauth->isAdministrator();
        if (!$isAdmin) {
            show_error('Permission denied', 403);
            return;
        }
        $this->title = lang('title_sysprefs');
        $data['titlepage'] = lang('title_sysprefs');
        MY_Controller::$menuactive = 'admins';


        /**
         * @var models\Preferences[] $prefs
         */
        $prefs = $this->em->getRepository("models\Preferences")->findAll();

        $sprefs = array();

        foreach ($prefs as $s) {
            $sprefs['' . $s->getCategory() . ''][] = $this->prefconftoarray($s);
        }


        $data['datas'] = $sprefs;

        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => '#', 'name' => lang('title_sysprefs'), 'type' => 'current'),

        );

        $data['content_view'] = 'manage/sysprefs_view';
        $this->load->view('page', $data);
    }
}
