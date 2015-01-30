<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');



class Sysprefs extends MY_Controller{

	function __construct()
	{
		parent:: __construct();
	}

	public function show()
	{
		$loggedin = $this->j_auth->logged_in();
		if(!$loggedin)
		{
			redirect('auth/login', 'location');
		}
		$isAdmin = $this->j_auth->isAdministrator();
		if(!$isAdmin) {
			show_error('Permission denied', 403);
			return;
		}
		$this->title = lang('title_sysprefs');
		$data['titlepage'] = lang('title_sysprefs');
		MY_Controller::$menuactive = 'admins';


		$f = $this->em->getRepository("models\Preferences")->findAll();

		$sprefs = array();

		foreach($f as $s)
		{
			$sprefs[''.$s->getCategory().''][] = $s;
		}






		$data['content_view'] = 'manage/sysprefs_view';
		$this->load->view('page',$data);
	}
}