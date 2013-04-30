<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Update extends MY_Controller {
    function __construct()
    {
       parent::__construct();
       $loggedin = $this->j_auth->logged_in();
       $this->current_site = current_url();
       if (!$loggedin)
       {
           $this->session->set_flashdata('target', $this->current_site);
           redirect('auth/login', 'refresh');
       }
       $i = $this->em->getRepository("models\Migration")->findAll();
       if(count($i) == 0)
       {
          $y = new models\Migration;
          $y->setVersion(0);
          $this->em->persist($y);
          $this->em->flush();
       } 
       $this->load->library(array('zacl'));

    }

    function upgrade()
    {
         $upgradeaccess = $this->zacl->check_acl('migration', 'edit', 'default');
         if(!$upgradeaccess)
         {
             $data['error'] = 'No permission';
              $data['content_view'] = 'nopermission';
             $this->load->view('page',$data);
         }
         else
         {

         
            $this->load->library('migration');
            
            if($this->migration->current() === $this->migration->latest())
            {
                $data['message'] = 'System is uptodate';
                $data['content_view'] = 'update_view';
                $this->load->view('page',$data);
            }
            else
            {
                $data['message'] = 'Target version: '.$this->migration->current().'<br />';
                //if ( ! $this->migration->current())
                //{
      	        //    show_error($this->migration->error_string());
                //}
                $data['content_view'] = 'update_view';
                $this->load->view('page',$data);
            }
         }
    }
       
}

?>
