<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * Dashboard Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Dashboard extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('j_auth');
        $this->load->helper('url');
        $loggedin = $this->j_auth->logged_in();

        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'home'));
            $this->load->library('zacl');
            return;
        }
        else
        {
            redirect('auth/login', 'refresh');
        }
    }

    function index()
    {
        $q = $this->em->getRepository("models\Queue")->findAll();
        $this->inqueue = count($q);

        $acc = FALSE;
        $acc = $this->zacl->check_acl('dashboard', 'read', 'default', '');
        $data['inqueue'] = $this->inqueue;

        if (empty($acc))
        {
            $this->title = "denied";
            $data['error'] = $this->mid . 'You have no persmission to view this page';
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
        }
        else
        {
            $this->title = "Dashboard";
            $data['content_view'] = 'default_body';
            $this->load->view('page', $data);
        }
    }

}
