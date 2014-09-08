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
          $this->form_validation->set_rules('fedid',lang('rr_federation'),'trim|required|numeric|xss_clean');
          return $this->form_validation->run();

    }
   
    public function leavefederation($providerid=null)
    {
        if(empty($providerid) || !is_numeric($providerid))
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
        $data['subtitle'] = $provider->getName().' ('.$provider->getEntityId().')'.anchor(base_url().'providers/detail/show/'.$provider->getId(),'<img src="' . base_url() . 'images/icons/'.$icon.'" />');
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
        $lang = MY_Controller::getLang();
        $enttype = $provider->getType();
        
        $data['name'] = $provider->getNameToWebInLang($lang,$enttype);
        $data['titlepage'] = anchor(base_url().'providers/detail/show/'.$provider->getId().'',$data['name']);
        $data['subtitlepage']=lang('leavefederation');

        if($this->submit_validate() === TRUE)
        {
             $fedid = $this->input->post('fedid');
             $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id'=>$fedid));
             if(empty($federation))
             {
                 show_error('Federation you want  to leave doesnt exist',404);
                 return;
             }
             $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider'=>$provider->getId(),'federation'=>$federation->getId()));
             if(!empty($membership))
             {
                $p_tmp = new models\AttributeReleasePolicies;
                $arp_fed = $p_tmp->getFedPolicyAttributesByFed($provider,$federation);
                $rm_arp_msg = '';
                if(!empty($arp_fed) && is_array($arp_fed) && count($arp_fed)>0)
                {
                   foreach($arp_fed as $r)
                   {
                        $this->em->remove($r);
                   }
                   $rm_arp_msg = "Also existing attribute release policy for this federation has been removed<br/>";
                   $rm_arp_msg .="It means when in the future you join this federation you will need to set attribute release policy for it again<br />";
                }
                $spec_arps_to_remove = $p_tmp->getSpecCustomArpsToRemove($provider);
                if(!empty($spec_arps_to_remove) && is_array($spec_arps_to_remove) && count($spec_arps_to_remove) > 0)
                {
                   foreach($spec_arps_to_remove as $rp)
                   {
                       $this->em->remove($rp);
                   }
                }
              
                if($provider->getLocal())
                {
                   $membership->setJoinState('2');
                   $this->em->persist($membership);
                 }
                else
                {
                   $this->em->remove($membership);

                }
                try
                {
                    $this->em->flush();
                
                    $data['success_message'] = lang('rr_youleftfed').': '.$federation->getName().'<br />';
                    $data['success_message'] .= $rm_arp_msg;
                    $data['content_view'] = 'manage/leavefederation_view';
                    $this->load->view('page',$data);
                }
                catch(Exception $e)
                {
                    log_message('error',__METHOD__.' ' . $e);
                    $data['error_message'] = 'Unknown error occured';
                    $data['content_view'] = 'manage/leavefederation_view';
                    $this->load->view('page',$data);
                    return;

                }
                
             }
             else
             {
                $data['error_message'] = lang('rr_youleftfed');
                $data['content_view'] = 'manage/leavefederation_view';
                $this->load->view('page',$data);
                return;

             }
        }
        else
        {    if(count($feds_dropdown) > 0)
             {
                  $this->load->helper('form');
                  $buttons = '<div class="buttons"><button type="submit" name="modify" value="submit" class="savebutton saveicon">'.lang('rr_save').'</button></div>';
                  $form = form_open(current_url(),array('id'=>'formver2','class'=>'span-15'));
                  $form .= form_fieldset('Leaving federation form');
                  $form .= '<div class="small-12 columns">';
                  $form .= '<div class="small-3 columns">';
                  $form .= '<label for="fedid" class="right inline">'.lang('rr_selectfedtoleave').'</label>';
                  $form .= '</div>';
                  $form .= '<div class="small-9 medium-7 columns end">'.form_dropdown('fedid', $feds_dropdown, set_value('fedid')).'</div></div>';
                  $form .= '<div class="small-12 center columns">';
                  $type = $provider->getType();
                  if(strcmp($type,'IDP')!=0)
                  {
                      $form .='<div data-alert class="alert-box warning"><p>'.lang('rr_alertrmspecpoliciecsp').'</p></div>';
                  }
                  else
                  {
                      $form .='<div data-alert class="alert-box warning"><p>'.lang('rr_alertrmspecpoliciecidp').'</p></div>';
                  }
                  $form .= '</div></div>';
                  $form .= $buttons;
                  $form .= form_fieldset_close();
                  $form .= form_close();
                  $data['form'] = $form;
                  $data['content_view'] = 'manage/leavefederation_view';
                  $this->load->view('page',$data);
             }
             else
             {
                $data['error_message'] = lang('cantleavefednonefound');
                $data['content_view'] = 'manage/leavefederation_view';
                $this->load->view('page',$data);
              
             }
        }
    }
    
}
