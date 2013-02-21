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
 * Idp_edit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Arpsexcl extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->tmp_providers = new models\Providers;
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->load->library('metadata_validator');
        $this->load->library('zacl');
        $this->load->helper('shortcodes');
    }

    public function idp($id)
    {
        $this->title = 'ff'; 
        $tmp_providers = new models\Providers;
        $idp = $tmp_providers->getOneIdpById($id);
        if (empty($idp))
        {
            log_message('error', $pref . "IdP edit: Identity Provider with id=" . $idpid . " not found");
            show_error(lang('rerror_idpnotfound'), 404);
        }
       
        $locked = $idp->getLocked();
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'idp', '');
        

        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit idp: ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        if($locked)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'Identity Provider is locked: ' . $idp->getEntityid();
            log_message('debug',$idp->getEntityid(). ': is locked and cannot be edited');
            $this->load->view('page', $data);
            return;
        }
        $is_local = $idp->getLocal();
        if (!$is_local)
        {
            $data['error_message'] = anchor(base_url() . "providers/provider_detail/idp/" . $idp->getId(), $idp->getName()) . lang('rerror_cannotmanageexternal');
            $data['content_view'] = "manage/idp_edit_view";
            $this->load->view('page', $data);
            return;
        }
                
        
       if($this->_submit_validate() === TRUE)
       {
           $excarray = $this->input->post('exc');
           foreach($excarray as $k=>$v)
           {
               if(empty($v))
               {
                   unset($excarray[$k]);
              
               }
           }
           $idp->setExcarps($excarray);
           $this->em->persist($idp);
           $this->em->flush();

       } 
        $data['rows'] = $this->form_element->excludedArpsForm($idp);
        $data['idp_name'] = $idp->getName();
        $data['idp_id'] = $idp->getId();
        $data['idp_entityid'] = $idp->getEntityId(); 
        $data['content_view'] =  'manage/arpsexcl_view';
        $this->load->view('page', $data);

    }

    private function _submit_validate()
    {
       $this->form_validation->set_rules('exc[]'.'eccc','required|max_length[1]');
       return $this->form_validation->run();
    }

}
