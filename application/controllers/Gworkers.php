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
 * Gworkers
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Gworkers extends MY_Controller {

    
    function __construct() {
        parent::__construct();
    }

     
    public function test()
    {
       $gmclient= new GearmanClient();
       $gmclient->addServer('127.0.0.1',4730);
       $job_handle = $gmclient->doNormal("externalstatcollection","ddd");
       print($job_handle);
    }


    function worker()
    {
        if($this->input->is_cli_request())
        {
             $this->load->library('gearmanw');
             $this->gearmanw->worker();
        }
        else
        {

           show_error('denied',403);
        }
    }




}
