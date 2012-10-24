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
 * Leavefed Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Leavefed extends MY_Controller {


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
   
    public function leavefederation($providerid=null)
    {
        if(empty($providerid) or !is_numeric($providerid))
        {
             show_error('Incorrect provider id provided',404);
             return;
        }
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$providerid));
        if(empty($provider))
        {
            show_error('Provider not found',404);
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
        $data['subtitle'] = $provider->getName().' ('.$provider->getEntityId().')'.anchor(base_url().'providers/provider_detail/'.strtolower($provider->getType()).'/'.$provider->getId(),'<img src="' . base_url() . 'images/icons/'.$icon.'" />');
        $has_write_access = $this->zacl->check_acl($provider->getId(),'write',strtolower($provider->getType()),'');
        
        if(!$has_write_access)
        {
           show_error('No access',403);
           return;
        }
        if($provider->getLocked())
        {
           show_error('Provider is locked',403);
           return;
        }
        $federations = $provider->getFederations();
        $feds_dropdown = array();
        foreach($federations as $f)
        {
           $feds_dropdown[$f->getId()] = $f->getName();
        }
        if($this->submit_validate() === TRUE)
        {
             $fedid = $this->input->post('fedid');
             $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id'=>$fedid));
             if(empty($federation))
             {
                 show_error('Federation you want  to leave doesnt exist',404);
                 return;
             }
             if($federations->contains($federation))
             {
                $p_tmp = new models\AttributeReleasePolicies;
                $arp_fed = $p_tmp->getFedPolicyAttributesByFed($provider,$federation);
                //print_r($arp_fed);
                if(!empty($arp_fed) && is_array($arp_fed) && count($arp_fed)>0)
                {
                    foreach($arp_fed as $r)
                    {
                        $this->em->remove($r);
                    }
                    $rm_arp_msg = "Also existing attribute release policy for this federation has been removed<br/>";
                    $rm_arp_msg .="It means when in the future you join this federation you will need to set attribute release policy for it again<br />";
                }
                else
                {
                    $rm_arp_msg = '';
                }
                $provider->removeFederation($federation);
                $this->em->persist($provider);
                $this->em->flush();

                $spec_arps_to_remove = $p_tmp->getSpecCustomArpsToRemove($provider);
                if(!empty($spec_arps_to_remove) && is_array($spec_arps_to_remove) and count($spec_arps_to_remove) > 0)
                {
                   foreach($spec_arps_to_remove as $rp)
                   {
                       $this->em->remove($rp);
                   }
                   $this->em->flush();
                }
                
                $data['success_message'] = "You just left federation: ".$federation->getName()."<br />";
                $data['success_message'] .= $rm_arp_msg;
                $data['content_view'] = 'manage/leavefederation_view';
                $this->load->view('page',$data);
                return;
             }
             else
             {
                $data['error_message'] = "You already left federation";
                $data['content_view'] = 'manage/leavefederation_view';
                $this->load->view('page',$data);
                return;
             }
        }
        else
        {    if(count($feds_dropdown) > 0)
             {
                  $this->load->helper('form');
                  $buttons = '<div class="buttons"><button type="submit" name="modify" value="submit" class="button positive"><span class="save">'.lang('rr_save').'</span></button></div>';
                  $form = form_open(current_url(),array('id'=>'formver2','class'=>'span-15'));
                  $form .= form_fieldset('Leaving federation form');
                  $form .= '<ol><li>';
                  $form .= form_label('Select federation you want to leave','fedid');
                  $form .= form_dropdown('fedid', $feds_dropdown);
                  $form .= '</li></ol>';
                  $form .= $buttons;
                  $form .= form_fieldset_close();
                  $form .= form_close();
                  $data['form'] = $form;
                  $data['content_view'] = 'manage/leavefederation_view';
                  $this->load->view('page',$data);
             }
             else
             {
                $data['error_message'] = "You can't leave any federation because you are not member of any";
                $data['content_view'] = 'manage/leavefederation_view';
                $this->load->view('page',$data);
              
             }
        }
    }
    
}
