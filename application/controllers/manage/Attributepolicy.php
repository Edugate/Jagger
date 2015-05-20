<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * Attributepolicy Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Attributepolicy extends MY_Controller
{

    protected $tmpProviders;
    protected $attributes;
    protected $tmpAttributes;
    protected $tmpArps;

    public function __construct()
    {
        parent::__construct();
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->helper('form');
        $this->load->library(array('table', 'form_element', 'show_element', 'zacl'));
        $this->tmpProviders = new models\Providers;
        $this->tmpArps = new models\AttributeReleasePolicies;
        $this->tmpAttributes = new models\Attributes;
        $this->attributes = $this->tmpAttributes->getAttributes();
    }

    private function displayDefaultPolicy($idp)
    {
        $result = $this->show_element->generateTableDefaultArp($idp, TRUE);
        return $result . '<br />';
    }

    private function displaySpecificPolicy($idp)
    {
        $result = $this->show_element->generateTableSpecificArp($idp, TRUE);
        return $result;
    }

    /**
     * @param $idp
     * @return string
     */
    private function displayFederationsPolicy($idp)
    {
        $result = $this->show_element->generateTableFederationsArp($idp, TRUE);
        return $result . '<br />';
    }


    /**
     * for global policy requester should be set to 0
     */
    public function detail($idpID, $attrID, $type, $requester)
    {
        if (!ctype_digit($idpID) || !ctype_digit($attrID) || !in_array($type, array('sp'))) {
            show_error(lang('error404'), 404);
        }
        $subtitle = null;
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmpProviders->getOneIdPById($idpID);
        if (empty($idp)) {
            show_error(lang('rerror_idpnotfound') . ' id:' . $idpID);
        }
        $myLang = MY_Controller::getLang();
        $providerNameInLang = $idp->getNameToWebInLang($myLang, 'idp');

        $data = array(
            'titlepage' => anchor(base_url('providers/detail/show/' . $idp->getId() . ''), lang('rr_provider') . ': ' . $providerNameInLang),
            'provider_entity' => $idp->getEntityId()
        );
        $hasWriteAccess = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$hasWriteAccess) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_nopermission');
            return $this->load->view('page', $data);
        }
        /**
         * @var $attribute models\Attribute
         */
        $attribute = $this->tmpAttributes->getAttributeById($attrID);
        if (empty($attribute)) {
            log_message('error', 'Attribute not found with id:' . $attrID);
            show_error('' . lang('error_attrnotfoundwithid') . ': ' . $attrID);
        }
        $attr_policy = $this->tmpArps->getOneSPPolicy($idpID, $attrID, $requester);

        /**
         * @var $sp models\Provider
         */
        $sp = $this->tmpProviders->getOneSpById($requester);
        if (empty($sp)) {
            show_error(lang('rerror_spnotfound') . ' id:' . $requester, 404);
        }
        $data['sp_name'] = $sp->getNameToWebInLang($myLang, 'sp');

        $link_sp = anchor(base_url() . "providers/detail/show/" . $sp->getId(), $data['sp_name']);
        $action = base_url('manage/attributepolicy/submit_sp/' . $idpID);
        $data['subtitlepage'] = lang('rr_specarpforsp') . ' : <br />' . $link_sp;

        if ($idp->getLocked()) {
            $subtitle .= '<div class="lblsubttitlepos" ><small > ' . makeLabel('locked', lang('rr_locked'), lang('rr_locked')) . ' </small ></div > ';
        }
        if (empty($attr_policy)) {
            $data['error_message'] = lang('arpnotfound');
            $message = 'Policy not found for: ';
            $message .= '[idp_id = ' . $idpID . ', attr_id = ' . $attrID . ', type = ' . $type . ', requester = ' . $requester . ']';
            log_message('debug', $message);
            $data['attribute_name'] = $attribute->getName();
            $data['idp_name'] = $providerNameInLang;
            $data['idp_id'] = $idpID;
            $data['requester_id'] = $requester;
            $data['type'] = $type;
            $narp = new models\AttributeReleasePolicy;
            $narp->setProvider($idp);
            $narp->setAttribute($attribute);
            $narp->setType('sp');
            $narp->setRequester($sp->getId());
            $narp->setPolicy(0);
            $submit_type = 'create';
            $data['edit_form'] = $this->form_element->generateEditPolicyForm($narp, $action, $submit_type);
        } else {
            log_message('debug', 'Policy has been found for: [idp_id = ' . $idpID . ', attr_id = ' . $attrID . ', type = ' . $type . ', requester = ' . $requester . ']');
            $data['attribute_name'] = $attribute->getName();
            $data['idp_name'] = $providerNameInLang;
            $data['idp_id'] = $idp->getId();
            $data['requester_id'] = $requester;
            $data['type'] = $type;
            $submit_type = 'modify';
            $data['edit_form'] = $this->form_element->generateEditPolicyForm($attr_policy, $action, $submit_type);
        }

        $data['subtitlepage'] = $subtitle;
        $data['breadcrumbs'] = array(
            array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
            array('url' => base_url('providers/detail/show/ ' . $idpID . ''), 'name' => '' . $providerNameInLang . ''),
            array('url' => base_url('manage/attributepolicy/globals/' . $idpID . ''), 'name' => lang('rr_attributereleasepolicy')),
        );

        $data['content_view'] = 'manage/attribute_policy_detail_view';
        return $this->load->view('page', $data);
    }

    /**
     * @param $idpID
     * @return object|string
     */
    public function globals($idpID)
    {

        $this->title = lang('rr_attributereleasepolicy');
        if (!ctype_digit($idpID)) {
            show_error('Incorrect id provided', 404);
        }
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmpProviders->getOneIdpById($idpID);

        /**
         * display 404 if idp not found
         */
        if (empty($idp)) {
            log_message('debug', 'Identity Provider with id ' . $idpID . ' not found');
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$has_write_access) {
            $data = array(
                'content_view' => 'nopermission',
                'error' => lang('rr_noperm'),
            );
            return $this->load->view('page', $data);
        }

        $myLang = MY_Controller::getLang();
        $providerNameInLang = $idp->getNameToWebInLang($myLang, 'idp');
        $data = array(
            'content_view' => 'manage/attribute_policy_view',
            'breadcrumbs' => array(
                array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
                array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . $providerNameInLang . ''),
                array('url' => '#', 'name' => lang('rr_attributereleasepolicy'), 'type' => 'current'),
            ),
            'titlepage' => lang('identityprovider') . ': ' . '<a href="' . base_url() . 'providers/detail/show/' . $idpID . '">' . $providerNameInLang . '</a>',
            'subtitlepage' => lang('rr_attributereleasepolicy'),
            'idpid' => $idpID,
            'idp_name' => $providerNameInLang,
            'idp_entityid' => $idp->getEntityId(),
            'default_policy' => $this->displayDefaultPolicy($idp),
            'federations_policy' => $this->displayFederationsPolicy($idp),
            'specific_policy' => $this->displaySpecificPolicy($idp)
        );

        /**
         * @var $supportedAttributes models\AttributeReleasePolicy[]
         */
        $supportedAttributes = $this->tmpArps->getSupportedAttributes($idp);
        $supportedArray = array();
        foreach ($supportedAttributes as $s) {
            $supportedArray[$s->getAttribute()->getId()] = $s->getAttribute()->getName();
        }

        $existingAttrs = $this->tmpArps->getGlobalPolicyAttributes($idp);
        $globalArray = array();
        if (!empty($existingAttrs)) {
            foreach ($existingAttrs as $e) {
                $globalArray[$e->getAttribute()->getId()] = $e->getAttribute()->getName();
            }
        }

        /**
         * array of attributes wchich dont exist in arp yet
         */
        $attrs_array_newform = array_diff_key($supportedArray, $globalArray);
        $data['attrs_array_newform'] = $attrs_array_newform;
        $data['spid'] = null;
        $data['formdown'][''] = lang('selectone') . '...';
        $sps = $this->tmpProviders->getCircleMembersLight($idp);
        foreach ($sps as $key) {
            $data['formdown'][$key->getId()] = $key->getNameToWebInLang($myLang, 'sp') . ' (' . $key->getEntityId() . ')';
        }

        $this->load->view('page', $data);
    }

    public function submit_sp($idp_id)
    {
        log_message('debug', 'submit_sp submited');
        $idpid = $this->input->post('idpid');
        $spid = $this->input->post('requester');
        $attributeid = $this->input->post('attribute');
        $policy = $this->input->post('policy');
        $action = $this->input->post('submit');
        if (empty($spid) || empty($idpid) || empty($attributeid) || !ctype_digit($spid) || !ctype_digit($idpid) || !ctype_digit($attributeid)) {
            log_message('error', 'spid in post not provided or not numeric');
            show_error(lang('missedinfoinpost'), 404);
        }

        if (!isset($policy) || !is_numeric($policy)) {
            log_message('error', 'policy in post not provided or not numeric:' . $policy);
            show_error(lang('missedinfoinpost'), 404);
        }
        if (!($policy == 0 || $policy == 1 || $policy == 2 || $policy == 100)) {
            log_message('error', 'wrong policy in post: ' . $policy);
            show_error(lang('wrongpolicyval'), 404);
        }
        if ($idp_id !== $idpid) {
            log_message('error', 'idp id from post is not equal with idp in url, idp in post:' . $idpid . ', idp in url:' . $idp_id);
            show_error(lang('unknownerror'), 404);
        }

        $sp = $this->tmpProviders->getOneSpById($spid);
        if (empty($sp)) {
            log_message('error', 'SP with id ' . $spid . ' doesnt exist');
            show_error(lang('rerror_spnotfound'), 404);
        }
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmpProviders->getOneIdpById($idp_id);
        if (empty($idp)) {
            log_message('error', 'IDP with id ' . $idp_id . ' doesnt exist');
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $locked = $idp->getLocked();
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if ($locked || !$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit');
            return $this->load->view('page', $data);
        }
        $tmp_attrs = new models\Attributes;
        $attribute = $tmp_attrs->getAttributeById($attributeid);
        if (empty($attribute)) {
            log_message('error', 'attribute  with id ' . $idp_id . ' doesnt exist');
            show_error(lang('reqattrnotexist'), 404);
        }
        $arp = $this->tmpArps->getOneSPPolicy($idp_id, $attributeid, $spid);
        if (!empty($arp)) {
            log_message('debug', 'Arp found in db, proceeding action');
            if ($action === 'delete') {
                $this->em->remove($arp);
                $this->em->flush();
                log_message('debug', 'action: delete - removing arp');
            } elseif ($action === 'modify') {
                $old_policy = $arp->getPolicy();
                $arp->setPolicy($policy);
                $this->em->persist($arp);
                $this->em->flush();
                log_message('debug', 'action: modify - modifying arp from policy ' . $old_policy . ' to ' . $policy);
            } else {
                log_message('error', 'wrong action in post, it should be modify or delete but got ' . $action);
                show_error(lang('unknownerror'), 403);
            }
        } else {
            log_message('debug', 'Arp not found');
            if ($action === 'create') {
                log_message('debug', 'Creating new arp');
                $narp = new models\AttributeReleasePolicy;
                $narp->setSpecificPolicy($idp, $attribute, $spid, $policy);
                $this->em->persist($narp);
                $this->em->flush();
            }
        }


        return $this->globals($idp_id);
    }

    private function genArpInArray()
    {
        $arpInArray = array();
        foreach ($this->attributes as $attribute) {
            $arpInArray['' . $attribute->getName() . ''] = array(
                'attr_name' => $attribute->getName(),
                'supported' => 0,
                'attr_id' => $attribute->getId(),
                'attr_policy' => null,
                'idp_id' => null,
                'sp_id' => null,
                'req_status' => null,
                'req_reason' => null
            );

        }
        return $arpInArray;
    }

    public function submit_multi($idpID)
    {
        $changes = array();
        $tmp_a = $this->config->item('policy_dropdown');
        $idpIdPosted = $this->input->post('idpid');
        if (empty($idpIdPosted) || !ctype_digit($idpIdPosted) || ($idpID !== $idpIdPosted)) {
            log_message('error', 'conflivt or empty');
            show_error(lang('unknownerror'), 403);
        }
        $submited_policies = $this->input->post('policy');
        $submited_requester_id = $this->input->post('spid');
        /**
         * @var $idp models\Provider
         * @var $sp models\Provider
         */
        $idp = $this->tmpProviders->getOneIdpById($idpIdPosted);
        $sp = $this->tmpProviders->getOneSpById($submited_requester_id);

        $isIdPLocked = $idp->getLocked();
        if (empty($idp) || empty($sp)) {
            log_message('error', 'IdP with id:' . $idpIdPosted . ' or SP with id:' . $submited_requester_id . ' not found');
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if ($isIdPLocked || !$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rrerror_noperm_provedit');
            return $this->load->view('page', $data);
        }

        foreach ($submited_policies as $key => $value) {
            $arp = $this->tmpArps->getOneSPPolicy($idp->getId(), $key, $sp->getId());
            if ($value === '100' && !empty($arp)) {
                $changes['attr:' . $arp->getAttribute()->getName() . '']['before'] = 'policy for ' . html_escape($sp->getEntityId()) . ' : ' . $tmp_a[$arp->getPolicy()];
                $this->em->remove($arp);
                $changes['attr:' . $arp->getAttribute()->getName() . '']['after'] = 'policy removed';

            } else {
                if (!empty($arp)) {
                    $old_policy = $arp->getPolicy();
                    if ($value == 0 || $value == 1 || $value == 2) {
                        if ($old_policy != $value) {
                            $changes['attr:' . $arp->getAttribute()->getName() . '']['before'] = 'policy for ' . htmlentities($sp->getEntityId()) . ' : ' . $tmp_a[$arp->getPolicy()];
                            $changes['attr:' . $arp->getAttribute()->getName() . '']['after'] = $tmp_a[$value];
                        }
                        $arp->setPolicy($value);
                        $this->em->persist($arp);
                        log_message('debug', 'policy changed for arp_id:' . $arp->getId() . ' from ' . $old_policy . ' to ' . $value . ' ready for sync');
                    } else {
                        log_message('error', 'policy couldnt be changed for arp_id:' . $arp->getId() . ' from ' . $old_policy . ' to ' . $value);
                    }
                } else {
                    if ($value == 0 || $value == 1 || $value == 2) {
                        log_message('debug', 'create new arp record for idp:' . $idp->getEntityId());
                        $newArpPolicy = new models\AttributeReleasePolicy;
                        $attr = $this->tmpAttributes->getAttributeById($key);

                        $newArpPolicy->setAttribute($attr);
                        $newArpPolicy->setProvider($idp);
                        $newArpPolicy->setType('sp');
                        $newArpPolicy->setPolicy($value);
                        $newArpPolicy->setRequester($sp->getId());
                        $this->em->persist($newArpPolicy);
                        $changes['attr:' . $attr->getName() . '']['before'] = 'no policy for ' . htmlentities($sp->getEntityId());
                        $changes['attr:' . $attr->getName() . '']['after'] = $tmp_a[$value];
                    }
                }
            }
        }
        if (!empty($changes) && count($changes) > 0) {
            $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
        }
        $this->em->flush();
        return $this->multi($idp->getId(), 'sp', $sp->getId());
    }

    /**
     * @param $idpID
     * @param $type
     * @param $requesterID
     * @return object|string|void
     */
    public function multi($idpID, $type, $requesterID)
    {
        if (!ctype_digit($idpID) || !ctype_digit($requesterID) || !($type === 'sp')) {
            show_error('wrong url request', 404);
        }

        $tmp_requirements = new models\AttributeRequirements;
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmpProviders->getOneIdPById($idpID);

        if (empty($idp)) {
            log_message('error', '(manage/attributepolicy/multi) Identity Provider not found with id:' . $idpID);
            show_error(lang('rerror_idpnotfound'), 404);

        }
        $excludedSPsFromArp = $idp->getExcarps();
        $hasWriteAccess = $this->zacl->check_acl($idpID, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->load->view('page', array(
                'content_view' => 'nopermission',
                'error' => lang('noperm_idpedit')
            ));
        }
        $idpNameInLang = $idp->getNameToWebInLang(MY_Controller::getLang(), 'idp');
        /**
         * @var $sp models\Provider
         */
        $sp = $this->tmpProviders->getOneSpById($requesterID);

        if (empty($sp)) {
            log_message('error', '(manage/attributepolicy/multi) Service Provider as requester not found with id:' . $requesterID);
            show_error(lang('rerror_spnotfound'), 404);
        }
        $spNameInLang = $sp->getNameToWebInLang(MY_Controller::getLang(), 'sp');
        $data = array(
            'breadcrumbs' => array(
                array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
                array('url' => base_url('providers/detail/show/' . $idpID . ''), 'name' => '' . $idpNameInLang . ''),
                array('url' => base_url('manage/attributepolicy/globals/' . $idpID . ''), 'name' => lang('rr_attributereleasepolicy')),
                array('url' => '#', 'name' => lang('rr_specpolicy') . ' : ' . $spNameInLang . '', 'type' => 'current')
            ),
            'provider' => $idpNameInLang,
            'provider_id' => $idpID,
            'provider_entityid' => $idp->getEntityId(),
            'requester_entityid' => $sp->getEntityId(),
            'titlepage' => lang('identityprovider') . ': <a href="' . base_url() . 'providers/detail/show/' . $idpID . '">' . $idpNameInLang . '</a>',
            'content_view' => 'manage/attribute_policy_multi_sp_view',
            'requester' => $spNameInLang,
            'requester_id' => $requesterID,
            'requester_type' => 'SP',
            'subtitlepage' => '' . lang('rr_specarpforsp') . ': <a href="' . base_url() . 'providers/detail/show/' . $requesterID . '">' . $spNameInLang . '</a>',
            'policy_dropdown' => $this->config->item('policy_dropdown'),
            'sp_available' => $sp->getAvailable(),
        );

        /**
         * @todo finish
         */
        /**
         * @var $arps models\AttributeReleasePolicy[]
         */
        $arps = $this->tmpArps->getSpecificPolicyAttributes($idp, $requesterID);
        $arpsInArray = $this->genArpInArray();
        foreach ($arps as $a) {
            $attributeName = $a->getAttribute()->getName();
            $arpsInArray['' . $attributeName . '']['attr_policy'] = $a->getPolicy();
            $arpsInArray['' . $attributeName . '']['idp_id'] = $idpID;
            $arpsInArray['' . $attributeName . '']['sp_id'] = $a->getRequester();
        }
        $supportedAttrs = $this->tmpArps->getSupportedAttributes($idp);
        foreach ($supportedAttrs as $p) {
            $attributeName = $p->getAttribute()->getName();
            $arpsInArray['' . $attributeName . '']['supported'] = 1;
        }
        $requirements = $tmp_requirements->getRequirementsBySP($sp);
        foreach ($requirements as $r) {
            $attributeName = $r->getAttribute()->getName();
            $arpsInArray[$attributeName]['req_status'] = $r->getStatus();
            $arpsInArray[$attributeName]['req_reason'] = $r->getReason();
        }

        $data['arps'] = $arpsInArray;

        $data['policy_dropdown']['100'] = lang('dropnotset');

        if (in_array($data['requester_entityid'], $excludedSPsFromArp)) {
            $data['excluded'] = true;
        }
        $this->load->view('page', $data);
    }

    public function specific($idp_id, $type)
    {
        if (!ctype_digit($idp_id)) {
            show_error('Id of IdP is not numeric', 404);
        }
        $has_write_access = $this->zacl->check_acl($idp_id, 'write', 'entity', '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit');
            return $this->load->view('page', $data);
        }
        $this->load->library('form_validation');
        if (strcmp($type, 'sp') == 0) {
            $this->form_validation->set_rules('service', 'Service ID', 'required');
            $sp_id = $this->input->post('service');
            if ($this->form_validation->run() === FALSE) {
                show_error(lang('emptyvalnotallowed'), 404);
            } else {
                redirect(base_url('manage/attributepolicy/multi/' . $idp_id . '/sp/' . $sp_id), 'location');
            }
        }
    }

}
