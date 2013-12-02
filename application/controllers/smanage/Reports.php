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
          echo '<div class="success">'.lang('rr_dbinsync').'</div>';
       }
       else
       {
          echo '<div class="error">'.lang('rerror_dbinsync').'</div>';
       }
    }

    /**
     * @todofinish 
     */
    private function cleanarplogs()
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

       $validator = new SchemaValidator($this->em);
       $errors = $validator->validateMapping();
       $errors2 = $validator->schemaInSyncWithMetadata();
       if(count($errors)>0 || !$errors2)
       {
           echo '<h5 class="error">'.lang('rerror_migrate1').'</h5>';
           if(count($errors)>0)
           {
              echo '<div class="error"><ul>'.recurseTree($errors).'</ul></div>';
           }
           if(!$errors2)
           {
              echo '<div class="error">'.lang('rerror_dbinsync').'</div>';
           }
       }
       else
       {
           $i = $this->em->getRepository("models\Migration")->findAll();
           if(count($i) == 0)
           {
               $y = new models\Migration;
               $y->setVersion(0);
               $this->em->persist($y);
               $this->em->flush();
           }

           $this->load->library('migration');
           if($this->migration->current() === $this->migration->latest())
           {
                echo  '<div class="success">'.lang('rr_sysuptodate').'</div>';
           }
           else
           {
                echo 'Target version: '.$this->migration->current();
           }
       }
      
       
    }


}
