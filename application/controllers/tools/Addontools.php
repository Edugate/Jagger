<?php


class Addontools extends MY_Controller
{

	function __construct()
	{
		parent::__construct();
	}

	public function msgdecoder()
	{
		$loggedin = $this->j_auth->logged_in();
		if(!$loggedin)
		{
			if(!$this->input->is_ajax_request()) {
				redirect('auth/login', 'location');
				return;
			}
			else
			{
				set_status_header(403);
				echo 'No session';
				return;
			}
		}

		if($this->input->is_ajax_request() && $this->input->post())
		{
			$encodedmsg = trim($this->input->post('inputmsg'));

			if(empty($encodedmsg))
			{
				echo 'No imput';
				return;
			}
			$decodedmsg = jSAMLDecoder($encodedmsg);
			echo htmlspecialchars($decodedmsg);
			return;
		}

		$data['titlepage'] = 'SAML decode';
		$data['content_view'] = 'tools/msgdecoder_view';
		$this->load->view('page',$data);

	}
	public function msgencoder()
	{

	}
}