<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Sysprefs extends MY_Controller
{

	function __construct()
	{
		parent:: __construct();
	}


	private function prefconftoarray(\models\Preferences $c)
	{
		$status = $c->getEnabled();
		$type = $c->getType();

		if($status)
		{


			$statusString = lang('rr_enabled');
		}
		else
		{
			$statusString = lang('rr_disabled');
		}
		$r = array(
			'confname' => $c->getName(),
			'displayname' => $c->getDescname(),
			'desc' => $c->getDescription(),
			'status' => $status,
			'type' => $type,
			'vtext' => $c->getValue(),
			'varray' => $c->getSerializedValue(),
			'cat' => $c->getCategory(),
			'statusstring'=>$statusString

		);
		return $r;

	}


	public function updateconf()
	{
		if (!$this->input->is_ajax_request() || !($this->input->method(TRUE) === 'POST')) {
			set_status_header(401);
			echo 'invalid request';
			return;
		}
		$loggedin = $this->j_auth->logged_in();
		if (!$loggedin) {
			set_status_header(401);
			echo 'invalid session';
			return;
		}
		$isAdmin = $this->j_auth->isAdministrator();
		if (!$isAdmin) {
			set_status_header(401);
			echo 'denied';
			return;
		}
		$this->load->library('form_validation');

		$this->form_validation->set_rules('confname', 'Conf name', 'required|trim|alpha_numeric');
		if ($this->form_validation->run() !== true) {
			$result['error'] = validation_errors('<div>', '</div>');
			$this->output->set_content_type('application/json')->set_output(json_encode($result));
			return;
		}


		$postConfname = $this->input->post('confname');

		$c = $this->em->getRepository("models\Preferences")->findOneBy(array('name' => $postConfname));
		if (empty($c)) {
			$result['error'] = 'No conf found';
			$this->output->set_content_type('application/json')->set_output(json_encode($result));
			return;
		}
		$type = $c->getType();

		$this->form_validation->reset_validation();
		if ($type === 'text') {
			$this->form_validation->set_rules('vtext', lang('label_text'), 'trim|htmlspecialchars');

			if ($this->form_validation->run() !== true) {
				$result['error'] = 'd' . validation_errors('<div>', '</div>');
				$this->output->set_content_type('application/json')->set_output(json_encode($result));
				return;
			}
			$t = $this->input->post('vtext');
			$c->setValue($t);
		}


		$postEnabled = $this->input->post('status');
		if (!empty($postEnabled) && $postEnabled === '1') {
			$c->setEnabled();
		} else {
			$c->setDisabled();
		}

		$this->em->persist($c);
		try {
			$tmpresult = $this->prefconftoarray($c);
			$this->em->flush();

			$this->j_cache->library('rrpreference', 'prefToArray', array('global'),-1);
			$result = $tmpresult;
			$result['result'] = 'OK';

			$this->output->set_content_type('application/json')->set_output(json_encode($result));
			return;
		} catch (Exception $e) {
			$result['error'] = 'Error occurred';
			log_message('error', __METHOD__ . ' ' . $e);
			$this->output->set_content_type('application/json')->set_output(json_encode($result));
			return;
		}


	}

	public function retgconf($confparam = null)
	{
		if (!$this->input->is_ajax_request() || !($this->input->method(TRUE) === 'GET') || empty($confparam)) {
			set_status_header(401);
			echo 'invalid request';
			return;
		}
		$loggedin = $this->j_auth->logged_in();
		if (!$loggedin) {
			set_status_header(401);
			echo 'invalid session';
			return;
		}
		$isAdmin = $this->j_auth->isAdministrator();
		if (!$isAdmin) {
			set_status_header(401);
			echo 'denied';
			return;
		}
		$arg = $confparam;
		$c = $this->em->getRepository("models\Preferences")->findOneBy(array('name' => $arg));
		if (empty($c)) {
			set_status_header(404);
			echo 'conf param not found';
			return;
		}

		$result = $this->prefconftoarray($c);

		$this->output->set_content_type('application/json')->set_output(json_encode($result));

	}

	public function show()
	{
		$loggedin = $this->j_auth->logged_in();
		if (!$loggedin) {
			redirect('auth/login', 'location');
		}
		$isAdmin = $this->j_auth->isAdministrator();
		if (!$isAdmin) {
			show_error('Permission denied', 403);
			return;
		}
		$this->title = lang('title_sysprefs');
		$data['titlepage'] = lang('title_sysprefs');
		MY_Controller::$menuactive = 'admins';


		$f = $this->em->getRepository("models\Preferences")->findAll();

		$sprefs = array();

		foreach ($f as $s) {
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
