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
 * Attribute_requirement Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Attribute_requirement extends MY_Controller {

    private $current_sp;
    private $current_fed;
    private $current_idp;
    private $log_prefix;

    public function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->log_prefix =  "attribute_requirement: ";
        log_message('debug', $this->log_prefix . "started");
        $this->load->library('zacl');
    }

    public function fed($fedid = null) {

        $data['no_new_attr'] = 1;

        $this->title = lang('rr_attributerequirements');
        $data['content_view'] = 'manage/attribute_fed_requirement_view';
        log_message('debug', $this->log_prefix . "fedid= " . $fedid);
        log_message('debug', $this->log_prefix . "current_fed=" . $this->current_fed);
        if (empty($fedid) or !is_numeric($fedid)) {
            if (empty($this->current_fed)) {
                $this->session->set_flashdata('target', $this->current_site);
                redirect('manage/settings/fed', 'location');
            }
            $fedid = $this->current_fed;
        }
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if (empty($fed)) {
            log_message('error', $this->log_prefix . "federation not found");
            show_error('Federation not found', 404);
        }

        $resource = 'f_'.$fed->getId();
        $group = 'federation';

        $matched_owner = FALSE;
        $owner = $fed->getOwner();
        if($owner == $this->j_auth->current_user())
        {
            $matched_owner = TRUE;
        }
        if(!empty($owner) &&  $matched_owner)
        {
            $has_write_access = TRUE;
        }
        else
        {
            $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        }
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noperm_mngtattrforfed').': ' . $fed->getName();
            $this->load->view('page', $data);
            return;
        }


        log_message('debug', 'preparing for federation: ' . $fed->getName());
        $data['head'] = lang('rr_attributerequirements').': ' . $fed->getName();
        $add_attr = array();
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        $already_in_attr = array();
        $add_attr_final = array();

        log_message('debug', $this->log_prefix . "found " . count($attrs) . " global attributes");
        foreach ($attrs as $a_def) {
            $add_attr[$a_def->getId()] = $a_def->getName();
        }
        $attrCollection = $fed->getAttributesRequirement()->getValues();
        if (!empty($attrCollection)) {
            log_message('debug', $this->log_prefix . "found " . count($attrCollection) . " required attributes");
            foreach ($attrCollection as $a) {

                $already_in_attr[$a->getAttribute()->getId()]['name'] = $a->getAttribute()->getName();
                $already_in_attr[$a->getAttribute()->getId()]['fullname'] = $a->getAttribute()->getFullname();
                $already_in_attr[$a->getAttribute()->getId()]['urn'] = $a->getAttribute()->getUrn();
                $already_in_attr[$a->getAttribute()->getId()]['oid'] = $a->getAttribute()->getOid();
                $already_in_attr[$a->getAttribute()->getId()]['description'] = $a->getAttribute()->getDescription();
                $already_in_attr[$a->getAttribute()->getId()]['attr_id'] = $a->getAttribute()->getId();
                $already_in_attr[$a->getAttribute()->getId()]['status'] = $a->getStatus();
                $already_in_attr[$a->getAttribute()->getId()]['reason'] = $a->getReason();
            }
        }
        $add_attr_final = array_diff_key($add_attr, $already_in_attr);
        $data['already_in_attr'] = $already_in_attr;
        $data['add_attr_final'] = $add_attr_final;
        $data['fed_name'] = $fed->getName();
        $data['fed_encoded'] = base64url_encode($fed->getName());
        $data['fedid'] = $fed->getId();
        $this->load->view('page', $data);
    }

    public function sp($spid = null) {
        /**
         * how many new input forms (new attributes) to display 
         */
        $data['no_new_attr'] = 1;
        $this->title = lang('rr_attributerequirements');
        $data['content_view'] = 'manage/attribute_requirement_view';
        log_message('debug', $this->log_prefix . "spid= " . $spid);
        log_message('debug', $this->log_prefix . "current_sp=" . $this->current_sp);
        if (empty($spid) or !is_numeric($spid)) {
            if (empty($this->current_sp)) {
                $this->session->set_flashdata('target', $this->current_site);
                redirect('manage/settings/sp', 'location');
            }
            $spid = $this->current_sp;
        }
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $spid, 'type' => array('SP', 'BOTH')));
        if (!empty($sp)) {
            log_message('debug', $this->log_prefix . "found sp = " . $sp->getEntityId());
        } else {
            log_message('debug', $this->log_prefix . "sp not found");
            show_error(lang('rerror_spnotfound'), 404);
            return;
        }
        $resource = $sp->getId();
        $group = 'entity';
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = ''.lang('rr_noperm_mngtattrforsp').': ' . $sp->getEntityid();
            $this->load->view('page', $data);
            return;
        }

        $a = $sp->getName();
        if (empty($a)) {
            $b = $sp->getEntityId();
            log_message('debug', $this->log_prefix . "name is not set for " . $sp->getId());
        } else {
            $b = $a . " (" . $sp->getEntityId() . ")";
        }
        $data['head'] = ''.lang('rr_attributerequirements').': ' . $b;

        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        $add_attr = array();
        $already_in_attr = array();
        /**
         * $add_attr minus $already_in_attr 
         */
        $add_attr_final = array();
        log_message('debug', $this->log_prefix . "found " . count($attrs) . " global attributes");
        foreach ($attrs as $a_def) {
            $add_attr[$a_def->getId()] = $a_def->getName();
        }
        $attrCollection = $sp->getAttributesRequirement()->getValues();
        if (!empty($attrCollection)) {
            log_message('debug', $this->log_prefix . "found " . count($attrCollection) . " required attributes");
            foreach ($attrCollection as $a) {

                $already_in_attr[$a->getAttribute()->getId()]['name'] = $a->getAttribute()->getName();
                $already_in_attr[$a->getAttribute()->getId()]['fullname'] = $a->getAttribute()->getFullname();
                $already_in_attr[$a->getAttribute()->getId()]['urn'] = $a->getAttribute()->getUrn();
                $already_in_attr[$a->getAttribute()->getId()]['oid'] = $a->getAttribute()->getOid();
                $already_in_attr[$a->getAttribute()->getId()]['description'] = $a->getAttribute()->getDescription();
                $already_in_attr[$a->getAttribute()->getId()]['attr_id'] = $a->getAttribute()->getId();
                $already_in_attr[$a->getAttribute()->getId()]['status'] = $a->getStatus();
                $already_in_attr[$a->getAttribute()->getId()]['reason'] = $a->getReason();
            }
        }
        //print_r($already_in_attr);
        $add_attr_final = array_diff_key($add_attr, $already_in_attr);
        //	$add_attr_final = $add_attr;
        $data['already_in_attr'] = $already_in_attr;
        $data['add_attr_final'] = $add_attr_final;
        $data['spid'] = $sp->getId();
        $data['sp_name'] = $sp->getName();
        $data['sp_entityid'] = $sp->getEntityId();
        $lang = MY_Controller::getLang();
        $displayname = $sp->getNameToWebInLang($lang,'sp');
        if(empty($displayname))
        {
            $displayname = $sp->getEntityId();
        }

        $data['titlepage'] = lang('serviceprovider').': <a href="'.base_url().'providers/detail/show/'.$data['spid'].'">'.$displayname.'</a> ';
        $data['subtitlepage'] = lang('rr_attributerequirements');
        $this->load->view('page', $data);
    }


    private function _add($provider_id, $attr_req) {
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $provider_id, 'type' => array('SP','BOTH')));
        if (!empty($provider) && !empty($attr_req)) {
            $attr_req->setSP($provider);
            $provider->setAttributesRequirement($attr_req);
            $this->em->persist($provider);
            $this->em->persist($attr_req);
            $this->em->flush();
            return true;
        }
    }

    private function _addfed($federation_id, $attr_req) {
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $federation_id));
        if (!empty($federation) && !empty($attr_req)) {
            $attr_req->setFed($federation);
            $federation->setAttributesRequirement($attr_req);
            $this->em->persist($federation);
            $this->em->persist($attr_req);
            $this->em->flush();
            return true;
        }
    }

    private function _remove($attr_req) {
        if (!empty($attr_req)) {
            $this->em->remove($attr_req);
            $this->em->flush();
            return true;
        }
    }

    private function _removefed($attr_req) {
        if (!empty($attr_req)) {
            $this->em->remove($attr_req);
            $this->em->flush();
            return true;
        }
    }
    
    private function _submit_validate()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('attribute', 'attribute','trim|xss_clean');
        $this->form_validation->set_rules('requirement', 'requirement','trim|xss_clean|valid_requirement_attr');
        $this->form_validation->set_rules('reason', 'reason','trim|xss_clean');
        $this->form_validation->set_rules('submit', 'submit','trim|xss_clean');
        $this->form_validation->set_rules('spid', 'spid','trim|numeric|xss_clean');
        
	return $this->form_validation->run();

    }

    public function submit() {
        /**
         * @todo add better check if form submited correctly also add comparison if session sp equals input sp
         */
        log_message('debug', $this->log_prefix . "sp-submited");
        $spid = $this->input->post('spid');
        if($this->_submit_validate() === FALSE)
        {
           return $this->sp($spid);
        }
        
        $attr = $this->input->post('attribute');
        $status = $this->input->post('requirement');
        $reason = $this->input->post('reason');
        $action = $this->input->post('submit');
        //$spid = $this->input->post('spid');
        if (empty($spid) or !is_numeric($spid)) {
            show_error('Incorect sp id', 404);
        }
        $provider_tmp = new models\Providers();
        $sp = $provider_tmp->getOneSpById($spid);
        if(empty($sp))
        {
            show_error('Service Provider not found',404);
        }
        $resource = $spid;
        $group = 'sp';
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noperm_mngtattrforsp') . $dpid;
            $this->load->view('page', $data);
            return;
        }
        $locked = $sp->getLocked();
        if ($locked) {
            $data['content_view'] = 'nopermission';
            $data['error'] = ''.lang('rr_noperm_mngtattrforsp').':' . $sp->getEntityId().': '.lang('rr_locked');
            $this->load->view('page', $data);
            return;
        }
        if ($attr && $status && $action == 'Add') {
            $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $attr));
            $attr_req = new models\AttributeRequirement;
            $attr_req->setReason($reason);
            $attr_req->setStatus($status);
            $attr_req->setAttribute($attribute);
            $attr_req->setType('SP');
            $this->_add($spid, $attr_req);
        } elseif ($attr && $status && $action == 'Remove') {
            $attr_req = $this->em->getRepository("models\AttributeRequirement")->findOneBy(array('sp_id' => $spid, 'attribute_id' => $attr));
            if (!empty($attr_req)) {
                $this->_remove($attr_req);
            }
        } elseif ($attr && $status && $action == 'Modify') {
            log_message('debug', $this->log_prefix . 'for spid:' . $spid . ' and attr:' . $attr . ' submited for modification');
            $attr_req = $this->em->getRepository("models\AttributeRequirement")->findOneBy(array('sp_id' => $spid, 'attribute_id' => $attr));
            if (!empty($attr_req)) {
                log_message('debug',  'requirement found');
                log_message('debug',  'args passed. reason:' . $reason . ', status:' . $status);
            }
            $attr_req->setReason($reason);
            $attr_req->setStatus($status);
            $attr_req->setType('SP');
            $this->em->persist($attr_req);
            $this->em->flush();
        } else {
            echo $action;
        }
        
        return $this->sp($spid);
    }

    public function fedsubmit() {

        log_message('debug', $this->log_prefix . "fed-submited");
        $attr = $this->input->post('attribute');
        $status = $this->input->post('requirement');
        $reason = $this->input->post('reason');
        $action = $this->input->post('submit');
        $fedid = $this->input->post('fedid');
        $has_write_access = false;
        if(empty($fedid) || !is_numeric($fedid) || empty($action) || empty($status) || empty($attr))
        {
             show_error('Missing information in post',403);
        }
        $f = $this->em->getRepository("models\Federation")->findOneBy(array('id'=>''.$fedid.''));
        if(empty($f))
        {
           show_error(lang('error_fednotfound',404));
        }
        $resource = 'f_'.$f->getId().'';
        $group = 'federation';
        try{
           $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        }
        catch (Exception $e)
        {
           log_message('error',__METHOD__.' '.$e);
           show_error('Internal Server Error',500);
        }

        if (!$has_write_access)
        {
           //show_error('Internal Server Error',500);
           $data['content_view'] = 'nopermission';
           $data['error'] = lang('rr_noperm_mngtattrforfed').': '.$f->getName() ;
           $this->load->view('page', $data);
        }
        if ($attr && $status && $action == 'Add') {
            $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $attr));
            $attr_req = new models\AttributeRequirement;
            $attr_req->setReason($reason);
            $attr_req->setStatus($status);
            $attr_req->setAttribute($attribute);
            $attr_req->setType('FED');
            $this->_addfed($fedid, $attr_req);
        } elseif ($attr && $status && $action == 'Modify') {
            $attr_req = $this->em->getRepository("models\AttributeRequirement")->findOneBy(array('fed_id' => $fedid, 'attribute_id' => $attr));
            $attr_req->setReason($reason);
            $attr_req->setStatus($status);
            $attr_req->setType('FED');
            $this->em->persist($attr_req);
            $this->em->flush();
        } elseif ($attr && $status && $action == "Remove") {
            $attr_req = $this->em->getRepository("models\AttributeRequirement")->findOneBy(array('fed_id' => $fedid, 'attribute_id' => $attr));
            if (!empty($attr_req)) {
                $this->_removefed($attr_req);
            }
        } else {
            return $this->fed($fedid);
        }
        return $this->fed($fedid);
    }

}
