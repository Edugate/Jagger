<?php 
class Eds extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        parse_str($_SERVER['QUERY_STRING'], $_GET);
      
    }
    function index()
    {
        $data['content_view'] = 'eds_view';
        $this->load->view('page', $data);
    }

}
