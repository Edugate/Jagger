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
 * Fvalidatoredit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Fvalidatoredit extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');
    }

    /**
     * @todo validate form
     */
    private function _submit_validate()
    {
        $this->form_validation->set_rules('vname', lang('fvalid_name'), 'required|trim|min_length[5]|max_length[20]|xss_clean');
        $this->form_validation->set_rules('vdesc', lang('rr_description'), 'required|trim|min_length[5]|max_length[200]|xss_clean');
        $this->form_validation->set_rules('vurl', lang('fvalid_url'), 'required|trim|min_length[5]|max_length[256]|valid_url');
        $methods = array('GET', 'POST');
        $this->form_validation->set_rules('vmethod', lang('rr_httpmethod'), 'required|trim|min_length[3]|max_length[4]|matches_inarray[' . serialize($methods) . ']');
        $this->form_validation->set_rules('vparam', lang('fvalid_entparam'), 'required|trim|min_length[1]|max_length[32]|xss_clean');
        $this->form_validation->set_rules('voptparams', lang('fvalid_optargs'), 'trim|min_length[1]|max_length[256]|xss_clean');
        $this->form_validation->set_rules('vargsep', lang('rr_argsep'), 'trim|max_length[256]|xss_clean');
        $doctypes = array('XML', 'xml');
        $this->form_validation->set_rules('vdoctype', lang('fvalid_doctype'), 'trim|required|max_length[256]|xss_clean|matches_inarray[' . serialize($doctypes) . ']');
        $this->form_validation->set_rules('vretelements', lang('fvalid_retelements'), 'trim|required|max_length[256]|xss_clean');
        $this->form_validation->set_rules('vsuccesscode', lang('fcode_succes'), 'trim|required|max_length[256]|xss_clean');
        $this->form_validation->set_rules('verrorcode', lang('fcode_error'), 'trim|max_length[256]|xss_clean');
        $this->form_validation->set_rules('vwarningcode', lang('fcode_warning'), 'trim|max_length[256]|xss_clean');
        $this->form_validation->set_rules('vcriticalcode', lang('fcode_crit'), 'trim|max_length[256]|xss_clean');
        $this->form_validation->set_rules('vmsgelements', lang('fvalid_msgelements'), 'trim|max_length[256]|xss_clean');
        return $this->form_validation->run();
    }

    public function vedit($fedid = NULL, $fvalidatorid = NULL)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        if (!ctype_digit($fedid))
        {
            show_error('not found', 404);
        }
        $this->load->library('zacl');
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if (empty($federation))
        {
            show_error('fed not found', 404);
        }

        $has_write_access = $this->zacl->check_acl('f_' . $federation->getId() . '', 'write', 'federation', '');
        if (!$has_write_access)
        {
            show_error('no access', 403);
        }
        $data['federationname'] = $federation->getName();
        $data['federationlink'] = base_url() . 'federations/manage/show/' . base64url_encode($federation->getName());
        $data['newfvalidator'] = FALSE;

        if (!empty($fvalidatorid))
        {
            if (!ctype_digit($fvalidatorid))
            {
                show_error('incorrect fvalidator id', 404);
            } else
            {
                /**
                 * edit existing validator
                 */
                $fvalidators = $federation->getValidators();
                $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id' => $fvalidatorid));

                if (empty($fvalidator) || !$fvalidators->contains($fvalidator))
                {
                    show_error('fvalidator not found', 404);
                }
            }
        } else
        {
            $data['newfvalidator'] = TRUE;
        }
        $postAction = $this->input->post('formsubmit');

        if (!empty($postAction) && strcmp($postAction, 'update') == 0 && $this->_submit_validate() === TRUE)
        {
            if ($data['newfvalidator'] === TRUE)
            {
                $fvalidator = new models\FederationValidator;
                $fvalidator->setFederation($federation);
            }

            $name = $this->input->post('vname');
            $fvalidator->setName($name);
            $desc = $this->input->post('vdesc');
            $fvalidator->setDescription($desc);
            $url = $this->input->post('vurl');
            $fvalidator->setUrl($url);
            $method = $this->input->post('vmethod');
            $fvalidator->setMethod($method);
            $entparam = $this->input->post('vparam');
            $fvalidator->setEntityParam($entparam);

            $optparamsTmp = explode('$$', $this->input->post('voptparams'));
            $optparams = array();
            if (!empty($optparamsTmp) && is_array($optparamsTmp) and count($optparamsTmp) > 0)
            {
                foreach ($optparamsTmp as $k => $v)
                {
                    if (empty($v))
                    {
                        unset($optparamsTmp[$k]);
                        continue;
                    }
                    $y = preg_split('/(\$:\$)/', $v, 2);
                    if (count($y) === 2)
                    {
                        $optparams['' . trim($y['0']) . ''] = trim($y['1']);
                    } elseif (count($y) == 1)
                    {
                        $optparams['' . trim($y['0']) . ''] = null;
                    }
                }
            }
            $fvalidator->setOptargs($optparams);

            $enabled = $this->input->post('venabled');
            if (!empty($enabled) && strcmp($enabled, 'yes') == 0)
            {
                $fvalidator->setEnabled(TRUE);
            } else
            {
                $fvalidator->setEnabled(FALSE);
            }

            $argseparator = $this->input->post('vargsep');
            $fvalidator->setSeparator($argseparator);


            $doctype = $this->input->post('vdoctype');
            $fvalidator->setDocumentType($doctype);

            $returcodeelements = array();
            $returcodeelementsTmp = explode(' ', $this->input->post('vretelements'));
            foreach ($returcodeelementsTmp as $v)
            {
                $v1 = trim($v);
                if ($v1 != '')
                {
                    $returcodeelements[] = $v1;
                }
            }
            $fvalidator->setReturnCodeElement($returcodeelements);

            $returnvalues = array();
            $codeTmp = explode('$$', $this->input->post('vsuccesscode'));
            if (!empty($codeTmp) && is_array($codeTmp))
            {
                foreach ($codeTmp as $v)
                {
                    if (trim($v) != '')
                    {
                        $returnvalues['success'][] = trim($v);
                    }
                }
            }
            $codeTmp = explode('$$', $this->input->post('verrorcode'));
            if (!empty($codeTmp) && is_array($codeTmp))
            {
                foreach ($codeTmp as $v)
                {
                    if (trim($v) != '')
                    {
                        $returnvalues['error'][] = trim($v);
                    }
                }
            }
            $codeTmp = explode('$$', $this->input->post('vwarningcode'));
            if (!empty($codeTmp) && is_array($codeTmp))
            {
                foreach ($codeTmp as $v)
                {
                    if (trim($v) != '')
                    {
                        $returnvalues['warning'][] = trim($v);
                    }
                }
            }
            $codeTmp = explode('$$', $this->input->post('vcriticalcode'));
            if (!empty($codeTmp) && is_array($codeTmp))
            {
                foreach ($codeTmp as $v)
                {
                    if (trim($v) != '')
                    {
                        $returnvalues['critical'][] = trim($v);
                    }
                }
            }
            $fvalidator->setReturnCodeValue($returnvalues);
            $msgelements = array();
            $codeTmp = explode(' ', $this->input->post('vmsgelements'));
            if (!empty($codeTmp) && is_array($codeTmp))
            {
                foreach ($codeTmp as $v)
                {
                    if (trim($v) != '')
                    {
                        $msgelements[] = trim($v);
                    }
                }
            }
            $fvalidator->setMessageElement($msgelements);
            $this->em->persist($fvalidator);
            $this->em->flush();
            $data['content_view'] = 'manage/fvalidator_edit_success';
            if ($data['newfvalidator'] === TRUE)
            {
                $data['successMsg'] = lang('fvalidaddsuccess');
            } else
            {
                $data['successMsg'] = lang('fvalidupdatesuccess');
            }

            $this->load->view('page', $data);
        } elseif (!empty($postAction) && strcmp($postAction, 'remove') == 0 && !empty($fvalidator))
        {
            $data['successMsg'] = 'Validator removed';
            $federation->getValidators()->removeElement($fvalidator);
            $this->em->remove($fvalidator);
            $this->em->flush();
            $data['content_view'] = 'manage/fvalidator_edit_success';
            $this->load->view('page', $data);
        } else
        {
            if (!empty($fvalidator))
            {
                $data['vname'] = $fvalidator->getName();
                $data['vdesc'] = $fvalidator->getDescription();
                $data['vurl'] = $fvalidator->getUrl();
                $data['vmethod'] = $fvalidator->getMethod();
                $data['vparam'] = $fvalidator->getEntityParam();
                $data['voptparams'] = $fvalidator->getOptargsToInputStr();
                $data['vargsep'] = $fvalidator->getSeparator();
                $data['vdoctype'] = $fvalidator->getDocutmentType();
                $data['vretelements'] = implode(' ', $fvalidator->getReturnCodeElement());
                $returncodes = $fvalidator->getReturnCodeValues();
                if (isset($returncodes['success']) && is_array($returncodes['success']))
                {
                    $data['vsuccesscode'] = implode('$$', $returncodes['success']);
                }
                if (isset($returncodes['error']) && is_array($returncodes['error']))
                {
                    $data['verrorcode'] = implode('$$', $returncodes['error']);
                }
                if (isset($returncodes['warning']) && is_array($returncodes['warning']))
                {
                    $data['vwarningcode'] = implode('$$', $returncodes['warning']);
                }
                if (isset($returncodes['critical']) && is_array($returncodes['critical']))
                {
                    $data['vcriticalcode'] = implode('$$', $returncodes['critical']);
                }
                $data['vmsgelements'] = implode(' ', $fvalidator->getMessageCodeElements());
                $data['venabled'] = $fvalidator->getEnabled();
            }
            $data['content_view'] = 'manage/fvalidator_edit_view';
            $this->load->view('page', $data);
        }
    }

}
