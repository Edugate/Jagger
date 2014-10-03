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
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->helper('form');
        $this->load->library(array('table', 'form_element'));
        $this->load->library('zacl');
    }

    private function cleanArpCache($idpId)
    {
        $arpinherit = $this->config->item('arpbyinherit');
        if (empty($arpinherit))
        {
            $this->j_cache->library('arp_generator', 'arpToArray', array($idpId), -1);
        }
        else
        {
            $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idpId), -1);
        }
        return true;
    }

    public function submit()
    {
        $idpId = trim($this->input->post('idpid'));
        if (empty($idpId) || !ctype_digit($idpId))
        {
            show_error('Missing or incorrect id of IdP', 503);
            return;
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpId, 'type' => array('IDP', 'BOTH')));
        if (empty($idp))
        {
            log_message('error', "Lost idp");
            show_error('Lost idp', 503);
        }
        $hasWriteAccess = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$hasWriteAccess)
        {
            $data = array(
                'content_view' => 'nopermission',
                'error' => '' . lang('noperm_idpedit') . ': ' . $idp->getEntityid() . '',
            );
            $this->load->view('page', $data);
        }
        $new_attrs = $this->input->post('attr');
        if (empty($new_attrs) || !is_array($new_attrs))
        {
            log_message('debug', 'No supported attrs are submited for idp: ' . $idp->getEntityId());
            $new_attrs = array();
        }
        $tmp = new models\AttributeReleasePolicies();
        $existingAttrs = $tmp->getSupportedAttributes($idp);
        $changes = array();
        $tempAttr = new models\Attributes();
        foreach ($existingAttrs as $a)
        {
            log_message('debug', 'current ' . $a->getAttribute()->getId());
            if (array_key_exists($a->getAttribute()->getId(), $new_attrs))
            {
                log_message('debug', $a->getAttribute()->getName() . "is in current and in selection ");
                unset($new_attrs[$a->getAttribute()->getId()]);
            }
            else
            {
                $changes['attr: ' . $a->getAttribute()->getName() . ''] = array(
                    'before' => '',
                    'after' => 'support removed',
                );
                $this->em->remove($a);
            }
        }
        if (count($new_attrs) > 0)
        {
            foreach ($new_attrs as $key => $value)
            {
                log_message('debug', $key . ' will be added to supported pool');              
                $attribute = $tempAttr->getAttributeById($key);
                $newOne = new models\AttributeReleasePolicy();
                $newOne->setSupportedAttribute($idp, $attribute);
                $this->em->persist($newOne);
                $changes['attr: ' . $newOne->getAttribute()->getName() . ''] = array(
                    'before' => '',
                    'after' => 'support added'
                );
            }
        }
        if (count($changes) > 0)
        {
            $idp->updated();
            $this->em->persist($idp);    
            $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
        }
        $this->em->flush();
        $this->cleanArpCache($idpId);
        return $this->idp($idp->getId());
    }

    public function idp($idp_id = null)
    {
        if (empty($idp_id) || !is_numeric($idp_id))
        {
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idp_id, 'type' => array('IDP', 'BOTH')));
        if (empty($idp))
        {
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'idp', '');
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit') . ': ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }


        $form_attributes = $this->form_element->supportedAttributesForm($idp);
        if (empty($form_attributes))
        {
            show_error(lang('error_noattrdefs'), 503);
        }
        $lang = MY_Controller::getLang();
        $data = array();
        $buttons = form_submit(array('name' => 'submit', 'value' => 'submit'));
        $buttons = '<div class="buttons"><button type="submit" value="submit" name="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div>';
        $formstyle = array('id' => 'supportedattrs');
        $hidden = form_input(array('name' => 'idpid', 'type' => 'hidden', 'value' => $idp->getId()));
        $form_attributes = form_open(base_url() . 'manage/supported_attributes/submit', $formstyle) . $hidden . $form_attributes . $buttons . form_close();
        $data['form_attributes'] = $form_attributes;
        $data['idp_id'] = $idp->getId();
        $data['idp_name'] = $idp->getName();
        $data['idp_entityid'] = $idp->getEntityId();
        $data['titlepage'] = lang('identityprovider') . ': ' . anchor(base_url() . "providers/detail/show/" . $idp->getId(), $idp->getNameToWebInLang($lang, 'idp'));
        $data['subtitlepage'] = lang('rr_supportedattributes');
        $data['submenupage'] = array(array('name' => '' . lang('rr_attributereleasepolicy') . '', 'url' => '' . base_url() . 'manage/attributepolicy/globals/' . $idp_id . ''));
        $data['content_view'] = 'manage/supported_attributes_view.php';
        $this->load->view('page', $data);

        //
    }

}
