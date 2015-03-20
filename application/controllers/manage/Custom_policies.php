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
 * Custom_policies Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Custom_policies extends MY_Controller
{

    private $tmp_providers;
    private $tmp_arps;
    private $tmp_attrs;

    function __construct()
    {
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
        $this->tmp_providers = new models\Providers;
        $this->tmp_arps = new models\AttributeReleasePolicies;
        $this->tmp_attrs = new models\Attributes;
        $this->attributes = $this->tmp_attrs->getAttributes();
        $this->load->library('zacl');
    }

    private function _submit_validate()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('values', '' . lang('permdenvalue') . '', 'trim|alpha_dash_comma|xss_clean');
        $this->form_validation->set_rules('policy', 'Policy', 'trim');
        return $this->form_validation->run();
    }

    public function idp($idp_id, $sp_id, $attributeId = null)
    {
        /**
         * @var $idp models\Provider
         * @var $sp models\Provider
         */
        $idp = $this->tmp_providers->getOneIdPById($idp_id);
        $sp = $this->tmp_providers->getOneSpById($sp_id);
        if (empty($idp) || empty($sp)) {
            show_error('' . lang('rerror_providernotexist') . '', 404);
        }
        $hasWriteAccess = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$hasWriteAccess) {
            show_error(lang('error403'), 403);
        }
        $isLocked = $idp->getLocked();
        if (!empty($attributeId)) {
            /**
             * @var $attribute models\Attribute
             */
            $attribute = $this->tmp_attrs->getAttributeById($attributeId);
            $data['form_action'] = current_url();
            if (empty($attribute)) {
                show_error('Attribute doesnt exist', 404);
            }
            if ($this->_submit_validate() === TRUE) {
                if ($isLocked) {
                    show_error('' . lang('error_lockednoedit') . '', 403);
                }
                /**
                 * @var $custom_arp models\AttributeReleasePolicy
                 */
                $custom_arp = $this->tmp_arps->getCustomSpArpByAttribute($idp, $sp, $attribute);
                $values = trim($this->input->post('values'));
                $policy = $this->input->post('policy');

                if (empty($values)) {
                    if (!empty($custom_arp)) {
                        $this->em->remove($custom_arp);
                        $this->em->flush();
                        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp->getId()), -1);
                    }
                } else {

                    $vdata = explode(",", $values);
                    array_walk($vdata, function (&$n) {
                        $n = trim($n);
                    });
                    $vdata = array_filter($vdata);
                    if ($policy == 'permit' or $policy == 'deny') {
                        $arraydata[$policy] = $vdata;


                        if (!empty($custom_arp)) {
                            $custom_arp->setRawdata($arraydata);
                        } else {
                            $custom_arp = new models\AttributeReleasePolicy;
                            $custom_arp->setAttribute($attribute);
                            $custom_arp->setProvider($idp);
                            $custom_arp->setRequester($sp->getId());
                            $custom_arp->setType('customsp');
                            $custom_arp->setRawdata($arraydata);
                        }
                        $this->em->persist($custom_arp);
                        $this->em->flush();
                        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp->getId()), -1);
                        $data['success_message'] = lang('customarpupdated');
                    }
                }
            }
            $custom_arp = $this->tmp_arps->getCustomSpArpByAttribute($idp, $sp, $attribute);

            $data['policy_selected'] = '';
            if (!empty($custom_arp)) {
                $arraydata = $custom_arp->getRawdata();
                if (!empty($arraydata) && is_array($arraydata)) {
                    if (array_key_exists('permit', $arraydata) && is_array($arraydata['permit'])) {
                        $data['values'] = implode(",", $arraydata['permit']);
                        $data['policy_selected'] = 'permit';
                    } elseif (array_key_exists('deny', $arraydata) && is_array($arraydata['deny'])) {
                        $data['values'] = implode(",", $arraydata['deny']);
                        $data['policy_selected'] = 'deny';
                    }
                }
            }


            /**
             * @var $supported_attributes models\AttributeReleasePolicy[]
             */
            $supported_attrs = $this->tmp_arps->getSupportedAttributes($idp);
            $myLang = MY_Controller::getLang();


            $data['idp_id'] = $idp->getId();
            $data['idp_name'] = $idp->getNameToWebInLang($myLang, 'idp');
            $data['titlepage'] = lang('identityprovider') . ': <a href="' . base_url('providers/detail/show/' . $idp->getId() . '') . '">' . $data['idp_name'] . '</a>';

            $data['isLocked'] = $isLocked;
            $data['idp_entityid'] = $idp->getEntityId();
            $data['sp_id'] = $sp->getId();
            $data['sp_name'] = $sp->getNameToWebInLang($myLang, 'sp');
            $data['sp_entityid'] = $sp->getEntityId();
            $data['attribute_name'] = $attribute->getName();
            $data['subtitlepage'] = ucfirst(lang('customarpforattr')) . ': ' . $data['attribute_name'];
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
            $data['breadcrumbs'] = array(
                $plist,
                array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . $data['idp_name'] . ''),
                array('url' => base_url('manage/attributepolicy/globals/' . $idp->getId() . ''), 'name' => '' . lang('rr_attributereleasepolicy') . ''),
                array('url' => '#', 'name' => lang('customarpforattr'), 'type' => 'current'),

            );
        }
        $data['content_view'] = 'manage/custom_policies_view';
        $this->load->view('page', $data);
    }

}
