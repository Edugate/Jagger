<?php 
class Eds extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        parse_str($_SERVER['QUERY_STRING'], $_GET);
      
    }
    public function index()
    {
        $data['content_view'] = 'eds_view';
        $this->load->view(MY_Controller::$page, $data);
    }

}
