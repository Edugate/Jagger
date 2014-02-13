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

    public function __construct()
    {
        parent::__construct();
        $this->tmp_providers = new models\Providers;
        $this->load->library('form_validation');
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('filename', 'Image', 'trim|required');
        $this->form_validation->set_rules('width', 'Width', 'trim|numeric|max=150');
        $this->form_validation->set_rules('height', 'Height', 'Width', 'numeric|max=150');
        return $this->form_validation->run();
    }

    private function _submit_manage_validate()
    {
        return true;
    }

    public function newlogo($type, $id)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
          
        if (!is_numeric($id))
        {
            show_error('wrong id of entity', 404);
        }
        if ($type === 'idp')
        {
            $provider = $this->tmp_providers->getOneIdpById($id);
        }
        elseif($type === 'sp')
        {
            $provider = $this->tmp_providers->getOneSpById($id);
        }
        else
        {
            show_error('wrong type of entity', 404);
        }
        if (empty($provider))
        {
            show_error('Provider not found', 404);
        }
        $this->load->library('zacl');

        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', $type, '');
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noperm_edit'). ': ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        $locked = $provider->getLocked();

        if ($this->_submit_validate() === true)
        {
            if ($locked)
            {
                show_error('Provider id locked and cannot be modified', 403);
            }
            $logoname_inputs = explode('_size_', $this->input->post('filename'));
            if (count($logoname_inputs) != 2)
            {
                log_message('error',  'incorrect  value given:' . $this->input->post('filename') . ' , must be in format: filename_size_widthxheight');
                show_error('incorrect image name', 500);
            }
            $new_logoname = $logoname_inputs['0'];
            $original_sizes = explode('x', $logoname_inputs['1']);

            $logo_attr = array();
            if (!empty($new_logoname))
            {
                $width = $this->input->post('width');
                $height = $this->input->post('height');
                if (!empty($width))
                {
                    $logo_attr['width'] = $width;
                }
                if (!empty($height))
                {
                    $logo_attr['height'] = $height;
                }
                if (empty($logo_attr['width']) && empty($logo_attr['height']))
                {
                    $logo_attr['width'] = $original_sizes['0'];
                    $logo_attr['height'] = $original_sizes['1'];
                }
                $element_name = 'Logo';
                $scheme = 'mdui';
                $parent = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('element' => 'UIInfo', 'provider' => $provider->getId(), 'namespace' => 'mdui', 'etype' => $type));
                if (empty($parent))
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
                $logo->setLogo($new_logoname, $provider, $parent, $logo_attr, $type);
                $this->em->persist($logo);
                $this->em->flush();
            }
        }

        $this->load->library('logo');
        $attributes = array('class' => 'span-20', 'id' => 'availablelogos');
        $availableImages = $this->logo->displayAvailableInGridForm('filename', 3);

        $form1 = form_open(base_url() . 'manage/logos/newlogo/' . $type . '/' . $id, $attributes);
        $form1 .= form_fieldset(''.lang('rr_selectimagetoassign').'');

        #$form1 .= '<li>';
        #$form1 .= form_label('Width in px (optional)', 'width');
        #$form1 .= form_input('width');
        #$form1 .= '</li>';
        #$form1 .= '<li>';
        #$form1 .= form_label('Height in px (optional)', 'height');
        #$form1 .= form_input('height');
        #$form1 .= '</li>';
        if(!empty($availableImages))
        {
           $form1 .= '<div class="buttons" style="display: none"><button name="submit" type="submit" value="submit" class="savebutton saveicon">
                      '.lang('rr_selectlogoandsubmit').'</button></div>';
           $form1 .= $availableImages;
        }
        else
        {
           $form1 .= '<div class="alert">'.lang('rr_nolocalimages').'</div>';
        }
        $form1 .= form_fieldset_close();
        $form1 .= form_close();


        $data['form1'] = $form1;
        $data['content_view'] = 'manage/logos_view';
        $data['sub'] = lang('rr_addnewlogofor');
        $data['backlink'] = true;
        $data['upload_enabled'] =  $this->config->item('rr_logoupload');
        $data['infomessage'] = lang('maxallowedimgdimsize').': '.$this->config->item('rr_logo_maxwidth').'x'.$this->config->item('rr_logo_maxheight').'<br />'.lang('rr_uploadinformat').': png'; 
        $data['show_upload'] = true;
        $data['provider_detail']['name'] = $provider->getName();
        $data['provider_detail']['id'] = $provider->getId();
        $data['provider_detail']['entityid'] = $provider->getEntityId();
        $data['provider_detail']['type'] = $type;
        if ($locked)
        {
            $data['provider_detail']['locked'] = '<img src="' . base_url() . 'images/icons/lock.png" title="'.lang('rr_lockedentity').'"/>';
        }
        else
        {
            $data['provider_detail']['locked'] ='';
        }


        $this->load->view('page', $data);
    }
   
    public function uploadlogos()
    {
        $isAjax = $this->input->is_ajax_request();
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            if($isAjax)
            {
                set_status_header(403);
                echo 'User session expired';
                return;
            
            }
            else
            {
               redirect('auth/login', 'location');
            }
        }
        $upload_enabled = $this->config->item('rr_logoupload');
        $upload_logos_path = trim($this->config->item('rr_logoupload_relpath'));
        if(empty($upload_enabled) || empty($upload_logos_path))
        {
            if($isAjax)
            {
                set_status_header(403);
                echo 'Upload images feature is disabled';
                return;
            }
            else
            {
               show_error('Upload images feature is disabled', 403);
            }
        }
        if(substr($upload_logos_path, 0, 1) == '/')
        {
           log_message('error','upload_logos_path in you config must not begin with forward slash');
           if($isAjax)
           {
                set_status_header(500);
                echo 'System error ocurred';
                return;
           }
           else
           {
              show_error('System error ocurred', 500);
           }

        }
        $path = realpath(APPPATH . '../'.$upload_logos_path);
        $config = array(
			'allowed_types' => ''.$this->config->item('rr_logo_types').'',
			'upload_path' => $path,
			'max_size' => $this->config->item('rr_logo_maxsize'),
                        'max_width' => $this->config->item('rr_logo_maxwidth'),
                        'max_height'=> $this->config->item('rr_logo_maxheight'),
		);
        $this->load->library('upload', $config);
        if ($this->input->post('upload')) {
           
           $data['backurl'] = $this->input->post('origurl');
           if( $this->upload->do_upload())
           {
              $data['message'] = lang('rr_imguploaded');
              
           }
           else
           {
               $data['error'] = array('error' => $this->upload->display_errors());
               if($isAjax)
               {
                   set_status_header(403);
                   echo $data['error']['error'];
                   return;
               }
           }
           if($isAjax)
           {
             echo "OK";
             return;
           }
        }
        else
        {
           if($isAjax)
           {
              set_status_header(403);
              echo "missing upload";
              return;
           }


        }
         
        $data['content_view'] = 'manage/uploadlogo_view';
        $this->load->view('page',$data);
    }

    public function provider($type = null, $id = null)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        if (empty($type) or !($type == 'idp' or $type == 'sp'))
        {
            show_error('wrong type of entity', 404);
        }
        if (empty($id) or !is_numeric($id))
        {
            show_error('wrong id of entity', 404);
        }
        if ($type == 'idp')
        {
            $provider = $this->tmp_providers->getOneIdpById($id);
        }
        else
        {
            $provider = $this->tmp_providers->getOneSpById($id);
        }
        if (empty($provider))
        {
            show_error(lang('rerror_provnotfound'), 404);
        }
        $this->load->library('zacl');

        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', $type, '');
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noperm_edit').': ' . $provider->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        $locked = $provider->getLocked();
        if ($this->_submit_manage_validate() === true)
        {
            if ($locked)
            {
                show_error('Provider locked', 403);
            }
            $action = $this->input->post('add');
            $raction = $this->input->post('remove');
            if (!empty($action) && $action === 'Add new image')
            {
                redirect(base_url() . 'manage/logos/newlogo/' . $type . '/' . $id, 'location');
            }
            elseif (!empty($raction) && $raction === 'Remove selected')
            {
                $logoid = $this->input->post('logoid');
                if (!empty($logoid) && is_numeric($logoid))
                {
                    $logo_obj = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('id' => $logoid, 'element' => 'Logo', 'namespace' => 'mdui', 'provider' => $id, 'etype' => $type));
                    if (!empty($logo_obj))
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
        $existing_logos = $this->em->getRepository("models\ExtendMetadata")->findBy(array('etype' => $type, 'namespace' => 'mdui', 'element' => 'Logo', 'provider' => $id));

        $count_existing_logos = count($existing_logos);
        // echo $count_existing_logos;

        $this->load->library('logo');
        $attributes = array('class' => 'span-16', 'id' => 'assignedlogos');

        //$available_images = $this->logo->getImageFiles();
        //$count_available_images = count($available_images);
        $target_url = base_url() . 'manage/logos/newlogo/' . $type . '/' . $id;
        $data['targeturl'] = $target_url;
        if($count_existing_logos > 0)
        {
            $form1 = '<span>';
            $form1 .= form_open(base_url() . 'manage/logos/provider/' . $type . '/' . $id, $attributes);
            $form1 .= $this->logo->displayCurrentInGridForm($provider, $type);
            $form1 .= '<div class="buttons">';
            $form1 .= '<button name="remove" type="submit" value="Remove selected" class="resetbutton reseticon" style="display: none">'.lang('rr_unsignselectedlogo').'</button> ';
            $form1 .= '</div>';
            $form1 .= form_close();
            $form1 .= '</span>';
            $data['form1'] = $form1;
        }
        if(!$locked)
        {
           $data['addnewlogobtn'] = true;
        }
        $data['content_view'] = 'manage/logos_view';
        $data['sub'] = lang('assignedlogoslistfor').' ';
        $data['provider_detail']['name'] = $provider->getName();
        $data['provider_detail']['id'] = $provider->getId();
        $data['provider_detail']['entityid'] = $provider->getEntityId();
        $data['provider_detail']['type'] = $type;
        if ($locked)
        {
            $data['provider_detail']['locked'] = '<img src="' . base_url() . 'images/icons/lock.png" title="'.lang('rr_lockedentity').'"/>';
        }
        else
        {
            $data['provider_detail']['locked'] ='';
        }
        $this->load->view('page', $data);
    }

}
