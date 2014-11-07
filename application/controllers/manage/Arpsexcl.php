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
            redirect('auth/login', 'location');
        }
        $this->tmp_providers = new models\Providers;
        $this->load->library(array('form_element','form_validation','metadata_validator','zacl'));
    }

    public function idp($id)
    {
        $this->title = 'ff'; 
        $tmp_providers = new models\Providers;
        $idp = $tmp_providers->getOneIdpById($id);
        if (empty($idp))
        {
            log_message('error', __METHOD__ . "IdP edit: Identity Provider with id=" . $id . " not found");
            show_error(lang('rerror_idpnotfound'), 404);
            return;
        }     
        $locked = $idp->getLocked();
        $hasWriteAccess = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$hasWriteAccess)
        {
            $data = array(
                'content_view'=>'nopermission',
                'error'=> ''.lang('rrerror_noperm_provedit').': ' . $idp->getEntityid().'',
            );
            $this->load->view('page', $data);
            return;
        }
        if($locked)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_lockedentity') . $idp->getEntityid();
            log_message('debug',$idp->getEntityid(). ': is locked and cannot be edited');
            $this->load->view('page', $data);
            return;
        }
        $isLocal = $idp->getLocal();
        if (!$isLocal)
        {
            $data['error'] = anchor(base_url() . "providers/detail/show/" . $idp->getId(), $idp->getName()) .' ' . lang('rerror_cannotmanageexternal');
            $data['content_view'] = "nopermission";
            $this->load->view('page', $data);
            return;
        }     
       if($this->_submit_validate() === TRUE)
       {
           $excarray = $this->input->post('exc');
           if(empty($excarray))
           {
               $excarray = array();
           }
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
       $lang = MY_Controller::getLang();
       $displayname = $idp->getNameToWebInLang($lang,'idp');
       $this->title = $displayname .': ARP excludes'; 
       $data = array(
           'rows'=>$this->form_element->excludedArpsForm($idp),
           'idp_name'=>$idp->getName(),
           'idp_id'=>$idp->getId(),
           'idp_entityid'=> $idp->getEntityId(),
           'content_view'=>'manage/arpsexcl_view',
           'titlepage'=>anchor(base_url().'providers/detail/show/'.$idp->getId(),  $displayname ),
           'subtitlepage'=>lang('rr_arpexcl1')
       );
        $this->load->view('page', $data);

    }

    private function _submit_validate()
    {
       $this->form_validation->set_rules('exc[]'.'eccc','required|max_length[1]');
       return $this->form_validation->run();
    }

}
