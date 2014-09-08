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
          $this->form_validation->set_rules('formmessage','Message','trim|required|xss_clean');
          return $this->form_validation->run();

    }
   
    public function joinfederation($providerid=null)
    {
        if(empty($providerid) || !is_numeric($providerid))
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
        $lang = MY_Controller::getLang();
        $enttype = $provider->getType();
        
        $data['name'] = $provider->getNameToWebInLang($lang,$enttype);
        if(empty($data['name']))
        {
           $data['name'] = $provider->getEntityId();
        }
        $data['entityid'] = $provider->getEntityId();
        $data['providerid'] = $provider->getId();

        $has_write_access = $this->zacl->check_acl($provider->getId(),'write',strtolower($enttype),'');
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

        
        $data['titlepage'] = '<a href="'.base_url().'providers/detail/show/'.$provider->getId().'">'.$data['name'].'</a>';
        $data['subtitlepage'] = lang('fedejoinform');
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
             $message = $this->input->post('formmessage');
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
                 
                  $this->load->library('approval');
                  $add_to_queue = $this->approval->invitationFederationToQueue($provider ,$federation,'Join',$message);
                  if($add_to_queue)
                  {
                               $this->load->library('tracker');
                               $mail_recipients = array();
                               $mail_sbj = "Request  to join federation: ".$federation->getName();
                                
                            
                               $providername = $provider->getName();
                               if(empty($providername))
                               {
                                  $providername = $provider->getEntityId();
                               }
                               $providerentityid = $provider->getEntityId();
                               $awaitingurl = base_url().'reports/awaiting';
                               $fedname = $federation->getName();
                               if(empty($message))
                               {
                                  $message = '';
                               }                               
                               $mail_body = '';
                               $this->tracker->save_track(strtolower($provider->getType()), 'request', $provider->getEntityId(), 'requested to join federation: '.$federation->getName().'. Message attached: '.htmlspecialchars($message).'', false);
                             
                               $overrideconfig = $this->config->item('defaultmail');
                               if(!empty($overrideconfig) && is_array($overrideconfig) && array_key_exists('joinfed',$overrideconfig) && !empty($overrideconfig['joinfed']))
                               {
                                   $b = $overrideconfig['joinfed'];
                               }
                               else
                               {
                                   $b = "Hi,\r\nJust few moments ago Administator of Provider %s (%s) \r\n";
                                   $b .= "sent request to Administrators of Federation: %s \r\n";
                                   $b .= "to access  him as new federation member.\r\n";
                                   $b .= "To accept or reject this request please go to Resource Registry\r\n %s \r\n";
                                   $b .= "\r\n\r\n======= additional message attached by requestor ===========\r\n";
                                   $b .= "%s";
                                   $b .= "\r\n=============================================================\r\n";
                               }
                               $localizedmail = $this->config->item('localizedmail');
                               if(!empty($localizedmail) && is_array($localizedmail) && array_key_exists('joinfed',$localizedmail) && !empty($localizedmail['joinfed']))
                               {
                                   $c = $localizedmail['joinfed'];
                                   $mail_body .= sprintf($c, $providername, $providerentityid, $fedname, $awaitingurl,$message);
                                   $mail_body .= "\r\n\r\n".sprintf($b, $providername, $providerentityid, $fedname, $awaitingurl,$message);
                               }
                               else
                               {
                                    $mail_body .= sprintf($b, $providername, $providerentityid, $fedname, $awaitingurl,$message);    
                               }
                               $subscribers = $this->em->getRepository("models\NotificationList")->findBy(
                                        array('type'=>'joinfedreq','federation'=>$federation->getId(),'is_enabled'=>true,'is_approved'=>true));

                               foreach($subscribers as $s)
                               {
                                  $m = new models\MailQueue();
                                  $m->setSubject($mail_sbj);
                                  $m->setBody($mail_body);
                                  $m->setDeliveryType($s->getNotificationType());
                                  $m->setRcptto($s->getRcpt());
                                  $this->em->persist($m);
                               }
                               $this->email_sender->addToMailQueue(array('joinfedreq','gjoinfedreq'),$federation,$mail_sbj,$mail_body,array(),FALSE);
                               try
                               {
                                  $this->em->flush();
                               }
                               catch(Exception $e) {
                                  log_message('error',$e);
                                  show_error('Internal server error',500);

                               }
                               
                               $data['content_view'] = 'manage/joinfederation_view';
                               $data['success_message'] = lang('confirmreqsuccess');
                               $this->load->view('page',$data);
                               return;
                              

                  }

                 
             }
        }
        else
        {  
             $data['error_message'] = validation_errors('<div>', '</div>');
               if(count($feds_dropdown) > 0)
             {
                  $n['']=lang('selectfed');
                  $feds_dropdown = $n + $feds_dropdown; 
                  $this->load->helper('form');
                  $buttons = '<div class="buttons small-9 columns text-right end"><button type="submit" name="modify" value="submit" class="savebutton saveicon">'.lang('rr_apply').'</button></div>';
                   
                  $form = form_open(current_url(),array('id'=>'joinfed'));
                  $form .= form_fieldset(lang('joinfederation'));

                  $form .= '<div class="small-12 columns"><div class="small-3 columns">';
                  $form .= '<label for="fedid" class="right inline">'.lang('rr_selectfedtojoin').'</label></div>';
                  $addid = 'id="fedid"'; 
                  $form .= '<div class="small-6 large-7 columns end">'.form_dropdown('fedid', $feds_dropdown,'0',$addid).'</div>';
                  $form .= '</div><div class="small-12 columns">';

                  $form .= '<div class="small-3 columns"><label for="formmessage" class="inline right">'.lang('rr_message').'</label></div>';
                  $form .= '<div class="small-6 large-7 columns end">'.form_textarea('formmessage',set_value('formmessage')).'</div>';
                  $form .= '</div>';
                  $form .= form_fieldset_close();

                  $form .= $buttons;
                  $form .= form_close();
                  $data['form'] = $form;
                  $data['content_view'] = 'manage/joinfederation_view';
                  $this->load->view('page',$data);
             }
             else
             {
                $data['error_message'] = lang('cantjoinnonefound');
                $data['content_view'] = 'manage/joinfederation_view';
                $this->load->view('page',$data);
              
             }
        }
    }
    
}
