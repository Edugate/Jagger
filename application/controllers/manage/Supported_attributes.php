<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Supported_attributes Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Supported_attributes extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();

        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');

        $this->load->helper('form');
        $this->load->library(array('table', 'form_element'));
        $this->load->library('zacl');
    }

    public function submit() {
        $idp_id = $this->input->post('idpid');
        /**
         * @todo add check idpid
         */
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idp_id, 'type' => array('IDP', 'BOTH')));
        if (empty($idp)) {
            log_message('error',  "Lost idp");
            show_error('Lost idp', 503);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'idp', '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit').': ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }

        $new_attrs = $this->input->post('attr');
        if (empty($new_attrs) OR !is_array($new_attrs)) {
            log_message('debug', 'No supported attrs are submited for idp: ' . $idp->getEntityId());
            $new_attrs = array();
        }


        /**
         * get current supported attrs
         */
        $tmp = new models\AttributeReleasePolicies();
        $existingAttrs = $tmp->getSupportedAttributes($idp);
        $changes = array();
        foreach ($existingAttrs as $a) {
            log_message('debug',  'current ' . $a->getAttribute()->getId());
            if (array_key_exists($a->getAttribute()->getId(), $new_attrs)) {
                log_message('debug',  $a->getAttribute()->getName() . "is in current and in selection ");
                unset($new_attrs[$a->getAttribute()->getId()]);
            } else {
                $this->em->remove($a);
                $changes['attr: ' . $a->getAttribute()->getName() . '']['before'] = '';
                $changes['attr: ' . $a->getAttribute()->getName() . '']['after'] = 'support removed';
                log_message('debug',  $a->getAttribute()->getName() . " is removed from supported attributes");
            }
        }
        if (count($new_attrs) > 0) {
            log_message('debug',  'New attributed to be added to supported pool');
            foreach ($new_attrs as $key => $value) {
                log_message('debug',  $key . ' will be added to supported pool');
                $tempAttr = new models\Attributes();
                $attribute = $tempAttr->getAttributeById($key);
                $newOne = new models\AttributeReleasePolicy();
                $newOne->setSupportedAttribute($idp, $attribute);
                $this->em->persist($newOne);
                $changes['attr: ' . $newOne->getAttribute()->getName() . '']['before'] = '';
                $changes['attr: ' . $newOne->getAttribute()->getName() . '']['after'] = 'support added';
            }
        }
        if (count($changes) > 0) {
            $idp->updated();
            $this->em->persist($idp);
            $arpinherit = $this->config->item('arpbyinherit');
            if(empty($arpinherit))
            {
                $this->j_cache->library('arp_generator', 'arpToArray', array($idp->getId()),-1);
            }
            else
            {
                $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp->getId()),-1);

            }
            $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
        }
        $this->em->flush();
        return $this->idp($idp->getId());
    }

    public function idp($idp_id = null) {
        if (empty($idp_id) OR !is_numeric($idp_id)) {
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idp_id, 'type' => array('IDP', 'BOTH')));
        if (empty($idp)) {
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'idp', '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit').': ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }


        $form_attributes = $this->form_element->supportedAttributesForm($idp);
        if (empty($form_attributes)) {
            show_error(lang('error_noattrdefs'), 503);
        }

        $data = array();
        $buttons = form_submit(array('name' => 'submit', 'value' => 'submit'));
        $buttons = '<div class="buttons"><button type="submit" value="submit" name="submit" class="savebutton saveicon">'.lang('rr_save').'</button></div>';
        $formstyle = array('id' => 'supportedattrs');
        $hidden = form_input(array('name' => 'idpid', 'type' => 'hidden', 'value' => $idp->getId()));
        $form_attributes = form_open(base_url() . 'manage/supported_attributes/submit', $formstyle) . $hidden . $form_attributes . $buttons . form_close();
        $data['form_attributes'] = $form_attributes;
        $data['idp_id'] = $idp->getId();
        $data['idp_name'] = $idp->getName();
        $data['idp_entityid'] = $idp->getEntityId();
        $data['content_view'] = 'manage/supported_attributes_view.php';
        $this->load->view('page', $data);

        //
    }

}
