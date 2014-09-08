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

class Custom_policies extends MY_Controller {

    private $tmp_providers;
    private $tmp_arps;
    private $tmp_attrs;

    function __construct()
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
        $this->tmp_providers = new models\Providers;
        $this->tmp_arps = new models\AttributeReleasePolicies;
        $this->tmp_attrs = new models\Attributes;
        $this->attributes = $this->tmp_attrs->getAttributes();
        $this->load->library('zacl');
    }

    private function _submit_validate()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('values', ''.lang('permdenvalue').'', 'trim|alpha_dash_comma|xss_clean');
        $this->form_validation->set_rules('policy', 'Policy', 'trim');
        return $this->form_validation->run();
    }

    public function idp($idp_id, $sp_id, $attr_id = null)
    {
        $idp = $this->tmp_providers->getOneIdPById($idp_id);
        $sp = $this->tmp_providers->getOneSpById($sp_id);
        if (empty($idp) || empty($sp))
        {
            show_error(''.lang('rerror_providernotexist').'', 404);
        }
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'idp', '');
        if (!$has_write_access)
        {
            show_error(lang('error403'), 403);
        }
        $locked = $idp->getLocked();
        if (!empty($attr_id))
        {
            $attribute = $this->tmp_attrs->getAttributeById($attr_id);
            $data['form_action'] = current_url();
            if (empty($attribute))
            {
                show_error('Attribute doesnt exist', 404);
            }
            if ($this->_submit_validate() === TRUE)
            {
                if($locked)
                {
                   show_error(''.lang('error_lockednoedit').'', 403);
                }
                $custom_arp = $this->tmp_arps->getCustomSpArpByAttribute($idp, $sp, $attribute);
                $values = trim($this->input->post('values'));
                $policy = $this->input->post('policy');

                if (empty($values))
                {
                    if (!empty($custom_arp))
                    {
                        $this->em->remove($custom_arp);
                        $this->em->flush();
                        $arpinherit = $this->config->item('arpbyinherit');
                        if(empty($arpinherit))
                        {
                           $this->j_cache->library('arp_generator', 'arpToArray', array($idp->getId()), -1);
                        }
                        else
                        {
                           $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp->getId()), -1);
                        }
                    }
                }
                else
                {

                    $vdata = explode(",", $values);
                    array_walk($vdata, function(&$n) {
                                $n = trim($n);
                            });
                    $vdata = array_filter($vdata);
                    if ($policy == 'permit' or $policy == 'deny')
                    {
                        $arraydata[$policy] = $vdata;


                        if (!empty($custom_arp))
                        {
                            $custom_arp->setRawdata($arraydata);
                        }
                        else
                        {
                            $custom_arp = new models\AttributeReleasePolicy;
                            $custom_arp->setAttribute($attribute);
                            $custom_arp->setProvider($idp);
                            $custom_arp->setRequester($sp->getId());
                            $custom_arp->setType('customsp');
                            $custom_arp->setRawdata($arraydata);
                        }
                        $this->em->persist($custom_arp);
                        $this->em->flush();
                        $this->j_cache->library('arp_generator', 'arpToArray', array($idp->getId()), -1);
                        $data['success_message'] = lang('customarpupdated');
                    }
                }
            }
            $custom_arp = $this->tmp_arps->getCustomSpArpByAttribute($idp, $sp, $attribute);

            $data['policy_selected'] = '';
            if (!empty($custom_arp))
            {
                $arraydata = $custom_arp->getRawdata();
                if (!empty($arraydata) && is_array($arraydata))
                {
                    if (array_key_exists('permit', $arraydata) && is_array($arraydata['permit']))
                    {
                        $data['values'] = implode(",", $arraydata['permit']);
                        $data['policy_selected'] = 'permit';
                    }
                    elseif (array_key_exists('deny', $arraydata) && is_array($arraydata['deny']))
                    {
                        $data['values'] = implode(",", $arraydata['deny']);
                        $data['policy_selected'] = 'deny';
                    }
                }
            }


            $supported_attrs = $this->tmp_arps->getSupportedAttributes($idp);
            $attr_formdown = array();
            foreach ($supported_attrs as $s)
            {
                $attr_formdown[$s->getAttribute()->getName()] = $s->getAttribute()->getFullname();
            }
            $data['idp_id'] = $idp->getId();
            $data['idp_name'] = $idp->getName();
            $data['locked'] = $locked;
            $data['idp_entityid'] = $idp->getEntityId();
            $data['sp_id'] = $sp->getId();
            $data['sp_name'] = $sp->getName();
            $data['sp_entityid'] = $sp->getEntityId();
            $data['attribute_name'] = $attribute->getName();
        }
        $data['content_view'] = 'manage/custom_policies_view';
        $this->load->view('page', $data);
    }

}
