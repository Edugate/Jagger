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
 * Logos Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Logos extends MY_Controller {

    protected $tmp_providers;

	
    public function __construct() {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->tmp_providers = new models\Providers;
        $this->load->library('form_validation');
        $this->load->library('zacl');
    }

    private function _submit_validate()
    {
          $this->form_validation->set_rules('filename', 'Image', 'trim|required');
          $this->form_validation->set_rules('width','Width','trim|numeric|max=150');
          $this->form_validation->set_rules('height','Height','Width','numeric|max=150');
          return $this->form_validation->run();

    
    }
    private function _submit_manage_validate()
    {
       return true;
    }

    public function newlogo ($type,$id)
    {
        if(!($type == 'idp' or $type=='sp'))
        {
            show_error('wrong type of entity',404);
        }
        if(!is_numeric($id)) 
        {
            show_error('wrong id of entity',404);
        }
        if($type == 'idp')
        {
           $provider = $this->tmp_providers->getOneIdpById($id);
        }
        else
        {
           $provider = $this->tmp_providers->getOneSpById($id);
        }
        if(empty($provider))
        {
           show_error('Provider not found',404);
        }

        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', $type, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit provider\'s logo: ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        if($this->_submit_validate() === true)
        {
                $logoname_inputs = explode('_size_',$this->input->post('filename'));
                if(count($logoname_inputs) != 2) 
                {
                    log_message('error',$this->mid.'incorrect  value given:'.$this->input->post('filename').' , must be in format: filename_size_widthxheight');
                    show_error('incorrect image name',500);
                }
		$new_logoname = $logoname_inputs['0'];
                $original_sizes = explode('x',$logoname_inputs['1']);
             
                $logo_attr = array();
                if(!empty($new_logoname))
                {
                      $width = $this->input->post('width');
                      $height = $this->input->post('height');
                      if(!empty($width))
                      {
                         $logo_attr['width'] = $width;
                      }
                      if(!empty($height))
                      {
                         $logo_attr['height'] = $height;
                      }
                      if(empty($logo_attr['width']) && empty($logo_attr['height']))
                      {
                           $logo_attr['width'] = $original_sizes['0'];
                           $logo_attr['height'] = $original_sizes['1'];
                      }
                $element_name = 'Logo';
                $scheme = 'mdui';
                $parent = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('element'=>'UIInfo','provider'=>$provider->getId(),'namespace'=>'mdui','etype'=>$type));
                if(empty($parent))
                {
                    $parent = new models\ExtendMetadata;
                    $parent->setElement('UIInfo');
                    $parent->setProvider($provider);
                    $parent->setParent(null);
                    $parent->setNamespace('mdui');
                    $parent->setType($type);
                    $this->em->persist($parent);
                }
                $logo = new models\ExtendMetadata;
                $logo->setLogo($new_logoname,$provider,$parent,$logo_attr,$type);
                $this->em->persist($logo);
                $this->em->flush();
                      
                }


        }
     
      $this->load->library('logo');
      $attributes = array('class' => 'span-20', 'id' => 'formver2');
      $form1 = form_open(base_url() . 'manage/logos/newlogo/'.$type.'/'.$id,$attributes);
      $form1 .= form_fieldset('Select image you want to assign');
      $form1 .= '<ol> ';
      
      $form1 .= '<li>';
      $form1 .= form_label('Width in px (optional)','width');
      $form1 .= form_input('width');
      $form1 .= '</li>';
      $form1 .= '<li>';
      $form1 .= form_label('Height in px (optional)','height');
      $form1 .= form_input('height');
      $form1 .= "</li>";
      $form1 .= '<li><div class="buttons"><button name="submit" type="submit" value="submit" class="btn positive"><span class="save">Select logo and submit</span></button></div></li>';
      $form1 .= "</ol>";
      $form1 .= $this->logo->displayAvailableInGridForm('filename',3);
      $form1 .= form_fieldset_close();
      $form1 .= form_close();
 

      $data['form1'] = $form1;
      $data['content_view'] = 'manage/logos_view';
      $data['add_applet'] = true;
      $data['sub'] = 'Add new logo for';
      $data['backlink'] = true;
      $data['provider_detail']['name'] = $provider->getName();
      $data['provider_detail']['id'] = $provider->getId();
      $data['provider_detail']['entityid'] = $provider->getEntityId();
      $data['provider_detail']['type'] = $type;

      

      $this->load->view('page',$data);
      

    }

    public function provider($type=null,$id=null)
    {
        if(empty($type) or !($type == 'idp' or $type=='sp'))
        {
            show_error('wrong type of entity',404);
        }
        if(empty($id) or !is_numeric($id)) 
        {
            show_error('wrong id of entity',404);
        }
        if($type == 'idp')
        {
           $provider = $this->tmp_providers->getOneIdpById($id);
        }
        else
        {
           $provider = $this->tmp_providers->getOneSpById($id);
        }
        if(empty($provider))
        {
           show_error('Provider not found',404);
        }

        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', $type, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit provider\'s logo: ' . $provider->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        if($this->_submit_manage_validate() === true)
        {
             $action = $this->input->post('add');
             $raction = $this->input->post('remove');
             if(!empty($action) && $action == 'Add new image')
             {
                 redirect(base_url().'manage/logos/newlogo/'.$type.'/'.$id, 'refresh');
             }
             elseif(!empty($raction) && $raction == 'Remove selected')
             {
                 $logoid = $this->input->post('logoid');
		 if(!empty($logoid) && is_numeric($logoid))
                 {
                     $logo_obj = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('id'=>$logoid,'element'=>'Logo','namespace'=>'mdui','provider'=>$id,'etype'=>$type));
                     if(!empty($logo_obj))
                     {
                         $this->em->remove($logo_obj);
                         $this->em->flush();
                     }

                     
                 }

	     }
        }

        /**
         * getting existing logos from extended metadata 
         */
         $existing_logos = $this->em->getRepository("models\ExtendMetadata")->findBy(array('etype'=>$type,'namespace'=>'mdui','element'=>'Logo','provider'=>$id));
         
         $count_existing_logos = count($existing_logos);
        // echo $count_existing_logos;
     
      $this->load->library('logo');
      $attributes = array('class' => 'span-16', 'id' => 'formver2');

      //$available_images = $this->logo->getImageFiles();
      //$count_available_images = count($available_images);
      $form1 = '<span>'; 
      $form1 .= form_open(base_url() . 'manage/logos/provider/'.$type.'/'.$id,$attributes);
      $form1 .= $this->logo->displayCurrentInGridForm($provider,$type);
      $form1 .= '<div class="buttons">';
      
      if($count_existing_logos > 0)
      {
      	$form1 .= '<button name="remove" type="submit" value="Remove selected" class="btn negative"><span class="cancel">Unsign selected</span></button>';

      }
      $target_url = base_url().'manage/logos/newlogo/'.$type.'/'.$id;
      $form1 .= '<a href="'.$target_url.'"><button name="add" type="button" value="Add new image" class="btn positive" onclick="window.open(\''.$target_url.'\',\'_self\')"><span class="save">Assign new logo</span></button></a>';
      $form1 .= '</div>';
      $form1 .= form_close();
      $form1 .= '</span>';

      $data['form1'] = $form1;
      $data['content_view'] = 'manage/logos_view';
      $data['sub'] = 'List assigned logos for ';
      $data['provider_detail']['name'] = $provider->getName();
      $data['provider_detail']['id'] = $provider->getId();
      $data['provider_detail']['entityid'] = $provider->getEntityId();
      $data['provider_detail']['type'] = $type;
      $this->load->view('page',$data);
    

    }

}
