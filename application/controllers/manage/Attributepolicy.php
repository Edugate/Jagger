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

    protected $tmp_providers;
    protected $attributes;
    protected $tmp_attrs;
    protected $tmp_arps;

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }

        $this->load->helper('form');
        $this->load->library(array('table', 'form_element', 'show_element', 'zacl'));
        $this->tmp_providers = new models\Providers;
        $this->tmp_arps = new models\AttributeReleasePolicies;
        $this->tmp_attrs = new models\Attributes;
        $this->attributes = $this->tmp_attrs->getAttributes();
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

    private function displayFederationsPolicy($idp)
    {
        $result = $this->show_element->generateTableFederationsArp($idp, TRUE);
        return $result . '<br />';
    }

    public function submit_global()
    {

        /**
         * @todo add validate submit
         */
        $idpid = $this->input->post('idpid');
        $attr = $this->input->post('attribute');
        $policy = $this->input->post('policy');
        $action = $this->input->post('submit');
        $is_policy = false;
        if (($policy == 0) || ($policy == 1) || ($policy == 2)) {
            $is_policy = true;
        }
        if (empty($idpid) || !is_numeric($idpid)) {
            show_error(lang('unknownerror'), 503);
        }
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdpById($idpid);
        if (empty($idp)) {
            show_error(lang('rerror_providernotexist'), 404);
        }
        $resource = $idpid;
        $group = 'idp';
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_nopermission');
            $this->load->view('page', $data);
            return;
        }
        $locked = $idp->getLocked();
        if ($locked) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_lockedentity');
            $this->load->view('page', $data);
            return;
        }


        if (!$is_policy) {
            log_message('error', 'Policy wasnt set');
            show_error(lang('rr_policynotset'), 503);
        }

        $tmp_a = $this->config->item('policy_dropdown');
        /**
         * @var $attribute models\Attribute
         */
        $attribute = $this->tmp_attrs->getAttributeById($attr);
        $attrPolicy = $this->tmp_arps->getOneGlobalPolicy($idpid, $attr);
        $changes = array();
        if (empty($attrPolicy) && ($action === 'modify' || $action === 'Add default policy')) {
            $attrPolicy = new models\AttributeReleasePolicy;
            if (empty($idp) || empty($attribute)) {
                log_message('debug', 'Cannot create new policy for idpid = ' . $idpid . ' because idp attribute not found');
                show_error(lang('unknownerror'), 503);
            }
            $changes['attr: ' . $attribute->getName() . '']['before'] = 'no default policy';
            $attrPolicy->setGlobalPolicy($idp, $attribute, $policy);
            $changes['attr: ' . $attribute->getName() . '']['after'] = $tmp_a[$policy];
        } elseif ($action === 'Cancel') {
            return $this->globals($idpid);
        } else {
            if ($attrPolicy->getPolicy() === $policy) {
                $changes['attr: ' . $attribute->getName() . '']['before'] = $tmp_a[$attrPolicy->getPolicy()] . ' (default policy)';
                $changes['attr: ' . $attribute->getName() . '']['after'] = $tmp_a[$policy];
            }
            $attrPolicy->setPolicy($policy);
        }


        if ($action === 'modify' || $action === 'Add default policy') {
            $this->em->persist($attrPolicy);
        } elseif ($action == 'delete' && !empty($attrPolicy)) {
            $changes['attr: ' . $attribute->getName() . '']['before'] = $tmp_a[$attrPolicy->getPolicy()] . ' (default policy)';
            $this->em->remove($attrPolicy);
            $changes['attr: ' . $attribute->getName() . '']['after'] = 'policy removed';
        }

        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idpid), -1);
        if (!empty($changes) && count($changes) > 0) {
            $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
        }
        $this->em->flush();
        return $this->globals($idpid);
    }

    /**
     * for global policy requester should be set to 0
     */
    public function detail($idp_id, $attr_id, $type, $requester)
    {
        $data = array();
        $subtitle = "";

        if (!ctype_digit($idp_id) || !ctype_digit($attr_id)) {
            log_message('error', "Idp id or attr id is set incorectly");
            show_error(lang('error404'), 404);
        }
        if (!($type === 'global' || $type === 'fed' || $type === 'sp')) {
            log_message('error', "The type of policy is: " . $type . ". Should be one of: global,fed,sp");
            show_error(lang('error_wrongpolicytype'), 404);
        }

        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdPById($idp_id);
        if (empty($idp)) {
            log_message('error', 'IdP not found with id:' . $idp_id);
            show_error(lang('rerror_idpnotfound') . ' id:' . $idp_id);
        }


        $myLang = MY_Controller::getLang();
        $providerNameInLang = $idp->getNameToWebInLang($myLang, 'idp');

        $data['titlepage'] = anchor(base_url() . "providers/detail/show/" . $idp->getId(), lang('rr_provider') . ': ' . $providerNameInLang);


        $resource = $idp->getId();
        $data['provider_entity'] = $idp->getEntityId();
        $group = 'idp';
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_nopermission');
            $this->load->view('page', $data);
            return;
        }
        $locked = $idp->getLocked();

        $attribute = $this->tmp_attrs->getAttributeById($attr_id);
        if (empty($attribute)) {
            log_message('error', 'Attribute not found with id:' . $attr_id);
            show_error('' . lang('error_attrnotfoundwithid') . ': ' . $attr_id);
        }

        if ($type === 'global') {
            $attr_policy = $this->tmp_arps->getOneGlobalPolicy($idp_id, $attr_id);
            $action = base_url('manage/attributepolicy/submit_global');
            $subtitle = lang('rr_defaultarp');
            if ($locked) {
                $subtitle .= '<div class="lblsubttitlepos"><small>' . makeLabel('locked', lang('rr_locked'), lang('rr_locked')) . '</small></div>';
            }
        } elseif ($type === 'fed') {
            $attr_policy = $this->tmp_arps->getOneFedPolicy($idp_id, $attr_id, $requester);
            $tmp_feds = new models\Federations;
            $fed = $tmp_feds->getOneFederationById($requester);
            if (!empty($fed)) {
                $data['fed_name'] = $fed->getName();
                $data['fed_url'] = base64url_encode($fed->getName());
            }
            $action = base_url('manage/attributepolicy/submit_fed/' . $idp_id);
            $subtitle = lang('rr_arpforfed');
            if ($locked) {
                $subtitle .= '<div class="lblsubttitlepos"><small>' . makeLabel('locked', lang('rr_locked'), lang('rr_locked')) . '</small></div>';
            }
        } else {  //type==sp
            $attr_policy = $this->tmp_arps->getOneSPPolicy($idp_id, $attr_id, $requester);

            /**
             * @var $sp models\Provider
             */
            $sp = $this->tmp_providers->getOneSpById($requester);
            if (!empty($sp)) {
                log_message('debug', 'SP found with id: ' . $requester);
                $data['sp_name'] = $sp->getNameToWebInLang($myLang, 'sp');
            } else {
                log_message('debug', 'SP not found with id: ' . $requester);
                show_error(lang('rerror_spnotfound') . ' id:' . $requester, 404);
            }
            $link_sp = anchor(base_url() . "providers/detail/show/" . $sp->getId(), $data['sp_name']);
            $action = base_url('manage/attributepolicy/submit_sp/' . $idp_id);
            $data['subtitlepage'] = lang('rr_specarpforsp') . ' : <br />' . $link_sp;
            if ($locked) {
                $subtitle .= '<div class="lblsubttitlepos"><small>' . makeLabel('locked', lang('rr_locked'), lang('rr_locked')) . '</small></div>';
            }
        }
        if (empty($attr_policy)) {
            $data['error_message'] = lang('arpnotfound');
            $message = 'Policy not found for: ';
            $message .= '[idp_id=' . $idp_id . ', attr_id=' . $attr_id . ', type=' . $type . ', requester=' . $requester . ']';
            log_message('debug', $message);
            $data['attribute_name'] = $attribute->getName();
            $data['idp_name'] = $idp->getName();
            $data['idp_id'] = $idp_id;
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
            log_message('debug', 'Policy has been found for: [idp_id=' . $idp_id . ', attr_id=' . $attr_id . ', type=' . $type . ', requester=' . $requester . ']');
            $data['attribute_name'] = $attribute->getName();
            $data['idp_name'] = $idp->getName();
            $data['idp_id'] = $idp->getId();
            $data['requester_id'] = $requester;
            $data['type'] = $type;
            $submit_type = 'modify';
            $data['edit_form'] = $this->form_element->generateEditPolicyForm($attr_policy, $action, $submit_type);
        }

        $data['subtitlepage'] = $subtitle;
        $data['breadcrumbs'] = array(
            array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
            array('url' => base_url('providers/detail/show/' . $idp_id . ''), 'name' => '' . $providerNameInLang . ''),
            array('url' => base_url('manage/attributepolicy/globals/' . $idp_id . ''), 'name' => lang('rr_attributereleasepolicy')),
        );

        $data['content_view'] = 'manage/attribute_policy_detail_view';
        $this->load->view('page', $data);
    }

    public function globals($idp_id)
    {
        $data['content_view'] = 'manage/attribute_policy_view';
        $this->title = lang('rr_attributereleasepolicy');
        if (empty($idp_id) || !ctype_digit($idp_id)) {
            show_error('Incorrect id provided', 404);
            return;
        }

        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdpById($idp_id);

        /**
         * display 404 if idp not found
         */
        if (empty($idp)) {
            log_message('debug', 'Identity Provider with id ' . $idp . ' not found');
            show_error(lang('rerror_idpnotfound'), 404);
            return;
        }
        $myLang = MY_Controller::getLang();
        $providerNameInLang = $idp->getNameToWebInLang($myLang, 'idp');
        $data['breadcrumbs'] = array(
            array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
            array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . $providerNameInLang . ''),
            array('url' => '#', 'name' => lang('rr_attributereleasepolicy'), 'type' => 'current'),

        );
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$has_write_access) {
            $data = array(
                'content_view' => 'nopermission',
                'error' => lang('rr_noperm'),
            );
            $this->load->view('page', $data);
            return;
        }
        $data['titlepage'] = lang('identityprovider') . ': ' . '<a href="' . base_url() . 'providers/detail/show/' . $idp_id . '">' . $providerNameInLang . '</a>';
        $data['subtitlepage'] = lang('rr_attributereleasepolicy');

        /**
         * pull default arp - it's equal to supported attributes
         */
        $data['default_policy'] = $this->displayDefaultPolicy($idp);

        $data['federations_policy'] = $this->displayFederationsPolicy($idp);

        $data['specific_policy'] = $this->displaySpecificPolicy($idp);

        /**
         * pull all attributes defitnitions
         */
        $supportedAttrs = $this->tmp_arps->getSupportedAttributes($idp);
        $supportedArray = array();
        foreach ($supportedAttrs as $s) {
            $supportedArray[$s->getAttribute()->getId()] = $s->getAttribute()->getName();
        }

        $existingAttrs = $this->tmp_arps->getGlobalPolicyAttributes($idp);
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
        $sps = $this->tmp_providers->getCircleMembersLight($idp);
        foreach ($sps as $key) {
            $data['formdown'][$key->getId()] = $key->getNameToWebInLang($myLang, 'sp') . ' (' . $key->getEntityId() . ')';
        }
        $data['idpid'] = $idp_id;
        $data['idp_name'] = $idp->getName();
        $data['idp_entityid'] = $idp->getEntityId();
        $this->load->view('page', $data);
    }

    public function show_feds($idp_id, $fed_id = null)
    {
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdpById($idp_id);
        if (empty($idp)) {
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $resource = $idp->getId();
        $group = 'idp';
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_nopermission');
            $this->load->view('page', $data);
            return;
        }
        $myLang = MY_Controller::getLang();
        $providerNameInLang = $idp->getNameToWebInLang($myLang, 'idp');
        if (empty($providerNameInLang)) {
            $providerNameInLang = $idp->getEntityId();
        }
        $data['idpname'] = $providerNameInLang;
        $data['titlepage'] = lang('identityprovider') . ': <a href="' . base_url() . 'providers/detail/show/' . $idp->getId() . '">' . $providerNameInLang . '</a>';

        if (($this->input->post('fedid')) && empty($fed_id)) {
            redirect(base_url('manage/attributepolicy/show_feds/' . $idp_id . '/' . $this->input->post('fedid')), 'location');
        } elseif (empty($fed_id)) {
            $data = array();

            $feds = $idp->getFederations();
            $data['federations'] = $this->form_element->generateFederationsElement($feds);
            $data['idpid'] = $idp->getId();
            $data['idpname'] = $providerNameInLang;
            $data['titlepage'] = lang('identityprovider') . ': <a href="' . base_url() . 'providers/detail/show/' . $idp->getId() . '">' . $providerNameInLang . '</a>';
            $data['subtitlepage'] = lang('rr_arpforfed');
            $data['content_view'] = 'manage/attribute_policy_feds_view';
            $this->load->view('page', $data);
        } else {
            $data = array();
            $data['idpname'] = $providerNameInLang;
            $data['titlepage'] = lang('identityprovider') . ': <a href="' . base_url() . 'providers/detail/show/' . $idp->getId() . '">' . $providerNameInLang . '</a>';

            $tmp_fed = new models\Federations();
            /**
             * @var $fed models\Federation
             */
            $fed = $tmp_fed->getOneFederationById($fed_id);
            if (empty($fed)) {
                return $this->show_feds($idp->getId());
            }
            /**
             * getting supported attrs
             */
            $supportedAttrs = $this->tmp_arps->getSupportedAttributes($idp);


            /**
             * getting set arp for this fed
             */
            $existingAttrs = $this->tmp_arps->getFedPolicyAttributesByFed($idp, $fed);

            /**
             * build array
             */
            if (empty($supportedAttrs)) {
                show_error(lang('error_setsupattrfirst'), 404);
            }
            $attrs_tmp = array();
            $attrs = array();
            foreach ($supportedAttrs as $s) {
                $attrs[$s->getAttribute()->getId()][$fed->getId()] = 100;
                $attrs[$s->getAttribute()->getId()]['name'] = $s->getAttribute()->getName();
            }

            if (!empty($existingAttrs)) {
                foreach ($existingAttrs as $e) {
                    $attrs_tmp[$e->getAttribute()->getId()][$e->getRequester()] = $e->getPolicy();
                }
            }
            $attrs_merged = array_replace_recursive($attrs, $attrs_tmp);

            $tbl_array = array();


            $i = 0;
            $drop100 = lang('dropnotset');
            $drop0 = lang('dropnever');
            $drop1 = lang('dropokreq');
            $drop2 = lang('dropokreqdes');
            foreach ($attrs_merged as $key => $value) {
                $policy = $value[$fed->getId()];
                $col2 = form_dropdown('attrid[' . $key . ']', array('100' => '' . $drop100 . '', '0' => '' . $drop0 . '', '1' => '' . $drop1 . '', '2' => '' . $drop2 . ''), $policy);
                $tbl_array[$i] = array($value['name'], $col2);
                $i++;
            }
            $submit_buttons_row = '<span class="buttons"><button name="submit" value="delete all"  class="resetbutton deleteicon">' . lang('btn_deleteall') . '</button> <button type="submit" name="submit" value="modify" class="savebutton saveicon">' . lang('btn_modify') . '</button></span>';
            $tbl_array[$i] = array('data' => array('data' => $submit_buttons_row, 'colspan' => 2));
            $data['tbl_array'] = $tbl_array;
            $data['fedid'] = $fed->getId();
            $data['idpid'] = $idp->getId();
            $data['fedname'] = $fed->getName();
            $data['fedencoded'] = base64url_encode($fed->getName());
            $data['entityid'] = $idp->getEntityId();
            $data['subtitlepage'] = lang('rr_arpforfed') . ': <a fref="' . base_url() . 'federations/manage/show/' . $data['fedencoded'] . '">' . $data['fedname'];

            $data['caption'] = $idp->getName() . '<br /><br />' . lang('rr_arpforfed') . ': ' . $fed->getName();

            $data['content_view'] = 'manage/attribute_policy_form_for_fed_view';
            $this->load->view('page', $data);
        }
    }

    public function submit_fed($idp_id)
    {
        log_message('debug', 'submit_fed submited');
        $idpid = $this->input->post('idpid');
        $fedid = $this->input->post('fedid');
        if (!empty($idpid) && !empty($fedid)) {
            if ($idp_id === $idpid) {
                log_message('debug', 'idpid is OK: ' . $idpid);


                $tmp_feds = new models\Federations;

                /**
                 * @var $idp models\Provider
                 */
                $idp = $this->tmp_providers->getOneIdpById($idpid);
                if (empty($idp)) {
                    log_message('error', 'Form attribute_policy for fed. IdP not found with id: ' . $this->input->post('idpid'));
                    show_error(lang('rerror_idpnotfound'), 404);
                } else {
                    log_message('debug', 'IDP found with id: ' . $idpid);
                }

                $resource = $idp->getId();
                $group = 'idp';
                $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
                if (!$has_write_access) {
                    $data['content_view'] = 'nopermission';
                    $data['error'] = lang('noperm_idpedit');
                    $this->load->view('page', $data);
                    return;
                }
                $locked = $idp->getLocked();
                if ($locked) {
                    $data['content_view'] = 'nopermission';
                    $data['error'] = lang('noperm_idpeditlocked');
                    $this->load->view('page', $data);
                    return;
                }

                /**
                 * @var $fed models\Federation
                 */
                $fed = $tmp_feds->getOneFederationById($fedid);
                if (empty($fed)) {
                    log_message('error', 'Form attributepolicy for fed. Federation not found with id: ' . $this->input->post('fedid'));
                    show_error(lang('error_fednotfound'), 404);
                } else {
                    log_message('debug', 'Federation found with id: ' . $fedid);
                }

                $attrlist = $this->input->post('attrid');
                $attribute = $this->input->post('attribute');

                if (!empty($attrlist) && is_array($attrlist) && count($attrlist) > 0) {
                    $submit_action = $this->input->post('submit');
                    log_message('debug', 'Found attributes');
                    foreach ($attrlist as $key => $value) {
                        $attr_pol = $this->tmp_arps->getOneFedPolicyAttribute($idp, $fed, $key);
                        if (empty($attr_pol) && ($value != '100')) {
                            $attr_pol = new models\AttributeReleasePolicy;
                            $attr_pol->setProvider($idp);
                            $tmp_attrs = new models\Attributes;
                            $attribute = $tmp_attrs->getAttributeById($key);
                            $attr_pol->setAttribute($attribute);
                            $attr_pol->setType('fed');
                            $attr_pol->setRequester($fed->getId());
                            $attr_pol->setPolicy($value);
                            $this->em->persist($attr_pol);
                        } elseif (!empty($attr_pol)) {
                            if ($value == '100' || $submit_action == 'delete all') {
                                $this->em->remove($attr_pol);
                            } else {
                                $attr_pol->setPolicy($value);
                                $this->em->persist($attr_pol);
                            }
                        }
                    }
                    $this->em->flush();
                    return $this->globals($idpid);
                } elseif (!empty($attribute) && is_numeric($attribute)) {
                    $submit_action = $this->input->post('submit');
                    $policy = $this->input->post('policy');
                    log_message('debug', "Found numeric attr id: " . $attribute);
                    $attr_pol = $this->tmp_arps->getOneFedPolicyAttribute($idp, $fed, $attribute);
                    if (empty($attr_pol)) {
                        log_message('debug', 'Attribute policy not found with idp:' . $idp->getId() . ' fed:' . $fed->getId() . ' attr:' . $attribute);
                    } else {
                        log_message('debug', 'Found attribute policy idp:' . $idp->getId() . ' fed:' . $fed->getId() . ' attr:' . $attribute);
                        if ($policy == '100' || $submit_action == 'delete') {
                            $this->em->remove($attr_pol);
                        } else {
                            $attr_pol->setPolicy($policy);
                            $this->em->persist($attr_pol);
                        }
                    }
                    $this->em->flush();
                    return $this->globals($idp_id);
                }
            } else {
                log_message('error', 'Id of idp in form is different than post-target idp id');
                show_error('POST target doesnt match attrs', 503);
            }
        }
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

        $sp = $this->tmp_providers->getOneSpById($spid);
        if (empty($sp)) {
            log_message('error', 'SP with id ' . $spid . ' doesnt exist');
            show_error(lang('rerror_spnotfound'), 404);
        }
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdpById($idp_id);
        if (empty($idp)) {
            log_message('error', 'IDP with id ' . $idp_id . ' doesnt exist');
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $locked = $idp->getLocked();
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if ($locked || !$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit');
            $this->load->view('page', $data);
            return;
        }
        $tmp_attrs = new models\Attributes;
        $attribute = $tmp_attrs->getAttributeById($attributeid);
        if (empty($attribute)) {
            log_message('error', 'attribute  with id ' . $idp_id . ' doesnt exist');
            show_error(lang('reqattrnotexist'), 404);
        }
        $arp = $this->tmp_arps->getOneSPPolicy($idp_id, $attributeid, $spid);
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

    public function submit_multi($idp_id)
    {
        $changes = array();
        $tmp_a = $this->config->item('policy_dropdown');
        $idpIdPosted = $this->input->post('idpid');
        if (empty($idpIdPosted) || !ctype_digit($idpIdPosted) || ($idp_id !== $idpIdPosted)) {
            log_message('error', 'conflivt or empty');
            show_error(lang('unknownerror'), 403);
        }
        $submited_policies = $this->input->post('policy');
        $submited_requester_id = $this->input->post('spid');
        /**
         * @var $idp models\Provider
         * @var $sp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdpById($idpIdPosted);
        $sp = $this->tmp_providers->getOneSpById($submited_requester_id);

        $isIdPLocked = $idp->getLocked();
        if (empty($idp) || empty($sp)) {
            log_message('error', 'IdP with id:' . $idpIdPosted . ' or SP with id:' . $submited_requester_id . ' not found');
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if ($isIdPLocked || !$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rrerror_noperm_provedit');
            $this->load->view('page', $data);
            return;
        }

        foreach ($submited_policies as $key => $value) {
            $arp = $this->tmp_arps->getOneSPPolicy($idp->getId(), $key, $sp->getId());
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
                        $new_arp = new models\AttributeReleasePolicy;
                        $attr = $this->tmp_attrs->getAttributeById($key);

                        $new_arp->setAttribute($attr);
                        $new_arp->setProvider($idp);
                        $new_arp->setType('sp');
                        $new_arp->setPolicy($value);
                        $new_arp->setRequester($sp->getId());
                        $this->em->persist($new_arp);
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

    public function multi($idpID, $type, $requesterID)
    {
        if (!ctype_digit($idpID) || !ctype_digit($requesterID) || !($type === 'sp')) {
            show_error('wrong url request', 404);
        }

        $tmp_requirements = new models\AttributeRequirements;
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdPById($idpID);

        if (empty($idp)) {
            log_message('error', '(manage/attributepolicy/multi) Identity Provider not found with id:' . $idpID);
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $excluded_arp = $idp->getExcarps();
        $has_write_access = $this->zacl->check_acl($idpID, 'write', 'entity', '');
        if (!$has_write_access) {
            $data = array(
                'content_view' => 'nopermission',
                'error' => lang('noperm_idpedit')
            );
            $this->load->view('page', $data);
            return;
        }
        $myLang = MY_Controller::getLang();
        $idpNameInLang = $idp->getNameToWebInLang(MY_Controller::getLang(), 'idp');
        /**
         * @var $sp models\Provider
         */
        $sp = $this->tmp_providers->getOneSpById($requesterID);

        if (empty($sp)) {
            log_message('error', '(manage/attributepolicy/multi) Service Provider as requester not found with id:' . $requesterID);
            show_error(lang('rerror_spnotfound'), 404);
        }
        $spNameInLang = $sp->getNameToWebInLang($myLang, 'sp');
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
         * @var $arps models\AttributeReleasePolicy[]
         */
        $arps = $this->tmp_arps->getSpecificPolicyAttributes($idp, $requesterID);
        $arpsInArray = array();
        foreach ($arps as $a) {
            $attributeName = $a->getAttribute()->getName();
            $arpsInArray['' . $attributeName . ''] = array(
                'attr_name' => $attributeName,
                'supported' => 0,
                'attr_id' => $a->getAttribute()->getId(),
                'attr_policy' => $a->getPolicy(),
                'idp_id' => $a->getProvider()->getId(),
                'sp_id' => $a->getRequester(),
                'req_status' => null,
                'req_reason' => null,
            );
        }
        $supportedAttrs = $this->tmp_arps->getSupportedAttributes($idp);
        foreach ($supportedAttrs as $p) {
            $attributeName = $p->getAttribute()->getName();
            $arpsInArray['' . $attributeName . '']['supported'] = 1;
            if (!array_key_exists('attr_id', $arpsInArray[$attributeName])) {
                $arpsInArray[$attributeName]['attr_name'] = $attributeName;
                $arpsInArray[$attributeName]['attr_id'] = $p->getAttribute()->getId();
                $arpsInArray[$attributeName]['attr_policy'] = null;
                $arpsInArray[$attributeName]['idp_id'] = $p->getProvider()->getId();
                $arpsInArray[$attributeName]['sp_id'] = $requesterID;
                $arpsInArray[$attributeName]['req_status'] = null;
                $arpsInArray[$attributeName]['req_reason'] = null;
            }
        }
        $requirements = $tmp_requirements->getRequirementsBySP($sp);
        foreach ($requirements as $r) {
            $attributeName = $r->getAttribute()->getName();
            if (!array_key_exists($attributeName, $arpsInArray)) {
                $arpsInArray[$attributeName] = array(
                    'attr_name' => $attributeName,
                    'supported' => 0,
                    'attr_id' => $r->getAttribute()->getId(),
                    'attr_policy' => null,
                    'idp_id' => null,
                    'sp_id' => $r->getSP()->getId(),
                    'req_status' => null,
                    'req_reason' => null,
                );
            }

            $arpsInArray[$attributeName]['attr_name'] = $attributeName;
            $arpsInArray[$attributeName]['req_status'] = $r->getStatus();
            $arpsInArray[$attributeName]['req_reason'] = $r->getReason();
        }
        $data['arps'] = $arpsInArray;

        $data['policy_dropdown']['100'] = lang('dropnotset');

        if (in_array($data['requester_entityid'], $excluded_arp)) {
            $data['excluded'] = true;
        }
        $this->load->view('page', $data);
    }

    public function specific($idp_id, $type)
    {
        if (!is_numeric($idp_id)) {
            show_error('Id of IdP is not numeric', 404);
        }
        $resource = $idp_id;
        $group = 'idp';
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('noperm_idpedit');
            $this->load->view('page', $data);
            return;
        }
        $this->load->library('form_validation');
        if ($type == 'sp') {
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
