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
 * Joinfed Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Joinfed extends MY_Controller {


    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) 
        {
           $this->session->set_flashdata('target', $this->current_site);
           redirect('auth/login', 'location');
        }
        else
        {
           $this->load->library('zacl');
           
        }
    }
    private function submit_validate()
    {
          $this->load->library('form_validation');
          $this->form_validation->set_rules('fedid','Federation','trim|required|numeric|xss_clean');
          return $this->form_validation->run();

    }
   
    public function joinfederation($providerid=null)
    {
        if(empty($providerid) or !is_numeric($providerid))
        {
             show_error(lang('error_incorrectprovid'),404);
             return;
        }
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$providerid));
        if(empty($provider))
        {
            show_error(lang('rerror_provnotfound'),404);
            return;
        }
        $icon ='';
        if($provider->getType() == 'IDP')
        {
           $icon = 'home.png'; 
        } 
        else 
        {
           $icon = 'block-share.png';
        }
        $data['name'] = $provider->getName();
        if(empty($data['name']))
        {
           $data['name'] = $provider->getEntityId();
        }
        $data['entityid'] = $provider->getEntityId();
        $data['providerid'] = $provider->getId();

        $has_write_access = $this->zacl->check_acl($provider->getId(),'write',strtolower($provider->getType()),'');
        if(!$has_write_access)
        {
           show_error('No access',403);
           return;
        }
        if($provider->getLocked())
        {
           show_error(lang('error_lockednoedit'),403);
           return;
        }
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $federations = $provider->getFederations();
        
        $available_federations = array();
        foreach ($all_federations as $ff)
        {
            if(!$federations->contains($ff))
            {
                $available_federations[$ff->getId()] = $ff->getName();
            }
        }
        
        $feds_dropdown = $available_federations;
       
        if($this->submit_validate() === TRUE)
        {
             $fedid = $this->input->post('fedid');
             $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id'=>$fedid));
             if(empty($federation))
             {
                 show_error(''.lang('error_nofedyouwantjoin').'',404);
                 return;
             }
             if(!$federations->contains($federation))
             {
                 /**
                  *@todo create queue 
                  */
                 
//                $provider->removeFederation($federation);
//                $this->em->persist($provider);
//                $this->em->flush();
//                $data['success_message'] = "You just left federation: ".$federation->getName();
//                $data['content_view'] = 'manage/leavefederation_view';
//                $this->load->view('page',$data);
                  $this->load->library('approval');
                  $add_to_queue = $this->approval->invitationFederationToQueue($provider ,$federation,'Join');
                  if($add_to_queue)
                  {
                               $mail_recipients = array();
                               $mail_sbj = "Request  to join federation: ".$federation->getName();
                               $mail_body = "Hi,\r\nJust few moments ago Administator of Provider \"".$provider->getName()." (".$provider->getEntityId().")\"\r\n";
                               $mail_body .= "sent request to Administrators/Owner of Federation: \"".$federation->getName() ."\"\r\n";
                               $mail_body .= "to access  him as new federation member.\r\n";
                               $mail_body .= "To accept or reject this request please go to Resource Registry\r\n";
                               $mail_body .= base_url()."reports/awaiting\r\n";
                               $mail_body .= "\r\n\r\n======= additional message attached by requestor ===========\r\n";
                               if(!empty($message))
                               {
                                       $mail_body .= $message."\r\n";
                               }
                               $mail_body .= "=============================================================\r\n";

                               $fedownerusername = $federation->getOwner();
                               if(!empty($fedownerusername))
                               {
                                    $fedowner = $this->em->getRepository("models\User")->findOneBy(array('username'=>$fedownerusername));
                               }
                               if(!empty($fedowner))
                               {
                                     $mail_recipients[] = $fedowner->getEmail();
                               }
                               $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
                               $a_members = $a->getMembers();
                               foreach ($a_members as $m)
                               {
                                   $mail_recipients[] = $m->getEmail();
                               }
                               $mail_recipients = array_unique($mail_recipients);
                               $this->load->library('email_sender');

                               $this->email_sender->send($mail_recipients, $mail_sbj, $mail_body);
                               
                               $data['content_view'] = 'manage/joinfederation_view';
                               $data['success_message'] = lang('confirmreqsuccess');
                               $this->load->view('page',$data);
                               return;
                              

                  }

                 
             }
        }
        else
        {    if(count($feds_dropdown) > 0)
             {
                  $this->load->helper('form');
                  $buttons = '<div class="buttons"><button type="submit" name="modify" value="submit" class="button positive"><span class="save">'.lang('rr_save').'</span></button></div>';
                  
                  $form = form_open(current_url(),array('id'=>'formver2','class'=>'span-15'));
                  $form .= form_fieldset(lang('joinfederation'));
                  $form .= '<ol><li>';
                  $form .= form_label(''.lang('rr_selectfedtojoin').'','fedid');
                  $form .= form_dropdown('fedid', $feds_dropdown);
                  $form .= '</li></ol>';
                  $form .= $buttons;
                  $form .= form_fieldset_close();
                  $form .= form_close();
                  $data['form'] = $form;
                  $data['content_view'] = 'manage/joinfederation_view';
                  $this->load->view('page',$data);
             }
             else
             {
                $data['error_message'] = "You can't join any federation because no available federations found";
                $data['content_view'] = 'manage/joinfederation_view';
                $this->load->view('page',$data);
              
             }
        }
    }
    
}
