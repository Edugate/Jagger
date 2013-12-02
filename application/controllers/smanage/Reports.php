<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
use Doctrine\ORM\Tools\SchemaValidator;

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
 * Reports Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Reports extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('j_auth');
    }

    public function  index(){
        $loggedin = $this->j_auth->logged_in();
        if(!$loggedin)
        {

               $this->session->set_flashdata('target', $this->current_site);
               redirect('auth/login', 'location');

        }
        if(!$this->j_auth->isAdministrator())
        {
            show_error('no perm',403);
        }
        $data['content_view']= 'smanage/index_view';
        $this->load->view('page',$data);


    }

    public function vschema()
    {
       if(!$this->input->is_ajax_request()){
           show_error('Bad request',401);
           return;
       }
       if(!$this->j_auth->logged_in()){
           show_error('Session lost',403);
       }
       if(!$this->j_auth->isAdministrator()){
           show_error('No perm',403);
       }
       $validator = new SchemaValidator($this->em);
       $errors = $validator->validateMapping();
       if(count($errors)>0)
       {
           $result = '<div class="error"><ul>'.recurseTree($errors).'</ul></div>';

       }
       else
       {
           $result = '<div class="success">The mapping files are correct</div>';
       }
       echo $result;
       

    }

    public function vschemadb()
    {
       if(!$this->input->is_ajax_request()){
           show_error('Bad request',401);
           return;
       }
       if(!$this->j_auth->logged_in()){
           show_error('Unauthorized request',403);
       }
       if(!$this->j_auth->isAdministrator()){
           show_error('Unauthorized request',403);
       }
       $validator = new SchemaValidator($this->em);
       $result = $validator->schemaInSyncWithMetadata();
       if($result)
       {
          echo '<div class="success">The database schema is in sync with the mapping files</div>';
       }
       else
       {
          echo '<div class="error">The database schema is not in sync with the current mapping file</div>';
       }
 

    }

    public function vmigrate()
    {
       if(!$this->input->is_ajax_request()){
           show_error('Bad request',401);
           return;
       }
       if(!$this->j_auth->logged_in()){
           show_error('Session lost',403);
       }
       if(!$this->j_auth->isAdministrator()){
           show_error('No perm',403);
       }


    }


}
