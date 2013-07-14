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
 * Entityedit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Entityedit extends MY_Controller {

    protected $current_site;
    protected $tmp_providers;
    protected $tmp_error;
    protected $type;

    //protected $current_site;
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
        $this->load->library(array('form_element', 'form_validation', 'zacl'));
        $this->tmp_providers = new models\Providers;
        $this->load->helper(array('shortcodes', 'form'));
        $this->tmp_error = '';
        $this->type = null;
       
    }

    private function _submit_validate($id)
    {
        $result = false;
        $y = $this->input->post();
        $staticisdefault = FALSE;

        if (isset($y['f']))
        {

          //  $this->form_validation->set_rules('f[static]', 'Static metadata', 'trim|xss_clean');
            $this->form_validation->set_rules('f[usestatic]', 'use metadata',"valid_static[".base64_encode($this->input->post('f[static]')).":::".$this->input->post('f[entityid]')." ]");


         // required if not static is set
            if(isset($y['f']['usestatic']) && $y['f']['usestatic'] === 'accept')
            {
               $staticisdefault = TRUE;
            
            }
            $this->form_validation->set_rules('f[entityid]', lang('rr_entityid'), 'trim|required|min_length[5]|max_length[255]|entityid_unique_update[' . $id . ']');
            $this->form_validation->set_rules('f[orgname]', lang('rr_homeorganisationname'), 'trim|required|min_length[5]|max_length[255]|xss_clean');
            $this->form_validation->set_rules('f[scopes][idpsso]', lang('rr_scope'), 'trim|xss_clean|valid_scopes|max_length[255]');
            $this->form_validation->set_rules('f[scopes][aa]', lang('rr_scope'), 'trim|xss_clean|valid_scopes|max_length[255]');

            if($staticisdefault)
            {
                $this->form_validation->set_rules('f[homeurl]', lang('rr_homeurl'), 'trim|xss_clean|valid_url');
                $this->form_validation->set_rules('f[displayname]', lang('rr_displayname'), 'trim|min_length[5]|max_length[255]|xss_clean');
                $this->form_validation->set_rules('f[helpdeskurl]', lang('rr_helpdeskurl'), 'trim|xss_clean|valid_url');
            }
            else
            {
                $this->form_validation->set_rules('f[homeurl]', lang('rr_homeurl'), 'trim|required|xss_clean|valid_url');
                $this->form_validation->set_rules('f[displayname]', lang('rr_displayname'), 'trim|required|min_length[5]|max_length[255]|xss_clean');
                $this->form_validation->set_rules('f[helpdeskurl]', lang('rr_helpdeskurl'), 'trim|required|xss_clean|valid_url');

            }
            



            $this->form_validation->set_rules('f[regauthority]', lang('rr_regauthority'), 'trim|xss_clean');
            $this->form_validation->set_rules('f[registrationdate]', lang('rr_regdate'), 'trim|xss_clean|valid_date_past');
            $this->form_validation->set_rules('f[privacyurl]', lang('rr_defaultprivacyurl'), 'trim|xss_clean|valid_url');
            $this->form_validation->set_rules('f[validfrom]', lang('rr_validfrom'), 'trim|xss_clean');
            $this->form_validation->set_rules('f[validto]', lang('rr_validto'), 'trim|xss_clean');
            $this->form_validation->set_rules('f[description]', lang('rr_description'), 'trim|xss_clean');
            if (array_key_exists('lname', $y['f']))
            {
                foreach ($y['f']['lname'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[lname][' . $k . ']', 'localicalized name in ' . $k, 'xss_clean|trim');
                }
            }
            if (array_key_exists('regpolicy', $y['f']))
            {
                foreach ($y['f']['regpolicy'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[regpolicy][' . $k . ']', ''.sprintf(lang('localizedregpolicy'),$k).'' , 'xss_clean|trim|valid_url');
                }
            }
            if (isset($y['f']['uii']['idpsso']['displayname']) && is_array($y['f']['uii']['idpsso']['displayname']))
            {
                foreach ($y['f']['uii']['idpsso']['displayname'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[uii][idpsso][displayname][' . $k . ']', 'UUI ' . sprintf(lang('lrr_displayname'),  $k) . '', 'trim|min_length[5]|max_length[255]|xss_clean');
                }
            }
            if (isset($y['f']['uii']['idpsso']['desc']) && is_array($y['f']['uii']['idpsso']['desc']))
            {
                foreach ($y['f']['uii']['idpsso']['desc'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[uii][idpsso][desc][' . $k . ']', 'UUI ' . lang('rr_description') . ' '.lang('in') .' ' . $k . '', 'trim|min_length[5]|max_length[500]|xss_clean');
                }
            }
            if (isset($y['f']['uii']['idpsso']['helpdesk']) && is_array($y['f']['uii']['idpsso']['helpdesk']))
            {
                foreach ($y['f']['uii']['idpsso']['helpdesk'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[uii][idpsso][helpdesk][' . $k . ']', 'UUI ' . lang('rr_helpdeskurl') . ' '.lang('in').' ' . $k . '', 'trim|valid_url|min_length[5]|max_length[500]|xss_clean');
                }
            }
            if (array_key_exists('ldisplayname', $y['f']))
            {
                foreach ($y['f']['ldisplayname'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[ldisplayname][' . $k . ']', 'localicalized displayname in ' . $k, 'xss_clean|trim');
                }
            }
            if (array_key_exists('lhelpdesk', $y['f']))
            {
                foreach ($y['f']['lhelpdesk'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[lhelpdesk][' . $k . ']', 'localicalized helpdesk url in ' . $k, 'trim|valid_url');
                }
            }

            if (array_key_exists('ldesc', $y['f']))
            {
                foreach ($y['f']['ldesc'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[ldesc][' . $k . ']', 'localicalized description in ' . $k, 'xss_clean|trim');
                }
            }

            if (array_key_exists('contact', $y['f']))
            {
                foreach ($y['f']['contact'] as $k => $v)
                {
                    $this->form_validation->set_rules('f[contact][' . $k . '][email]', 'contact email', 'trim|valid_email');
                    $this->form_validation->set_rules('f[contact][' . $k . '][type]', 'contact type', 'trim|valid_contact_type');
                    $this->form_validation->set_rules('f[contact][' . $k . '][fname]', 'first name', 'trim|xss_clean');
                    $this->form_validation->set_rules('f[contact][' . $k . '][sname]', 'surname', 'trim|xss_clean');
                }
            }
            if (array_key_exists('prot', $y['f']))
            {
                foreach ($y['f']['prot'] as $key => $value)
                {
                    foreach ($value as $k => $v)
                    {
                        $this->form_validation->set_rules('f[contact][' . $key . '][' . $k . ']', 'trim');
                    }
                }
            }
            /**
             * certificates
             */
            if (array_key_exists('crt', $y['f']))
            {
                if (array_key_exists('spsso', $y['f']['crt']))
                {
                    foreach ($y['f']['crt']['spsso'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[crt][spsso][' . $k . '][certdata]', 'cert data', 'trim|verify_cert');
                        $this->form_validation->set_rules('f[crt][spsso][' . $k . '][remove]', 'remove', 'trim');
                        $this->form_validation->set_rules('f[crt][spsso][' . $k . '][usage]', 'cert usage', 'trim|required');
                    }
                }
                if (array_key_exists('idppsso', $y['f']['crt']))
                {
                    foreach ($y['f']['crt']['idpsso'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][certdata]', 'cert data', 'trim|verify_cert');
                        $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][remove]', 'remove', 'trim');
                        $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][usage]', 'cert usage', 'trim|required');
                    }
                }
            }

            /**
             * service locations
             */
            $nosso = 0;
            $nossobindings = array();
            $noidpslo = array();
            if (array_key_exists('srv', $y['f']))
            {
                log_message('debug', 'GGGG f[srv] exists ');
            }
            if (array_key_exists('srv', $y['f']))
            {
                if(!array_key_exists('SingleSignOnService', $y['f']['srv']) && ($this->type === 'IDP' or $this->type === 'BOTH'))
                {
                    $y['f']['srv']['SingleSignOnService'] = array();
                }
                if (array_key_exists('SingleSignOnService', $y['f']['srv']))
                {
                    foreach ($y['f']['srv']['SingleSignOnService'] as $k => $v)
                    {
                        $nossobindings[] = $y['f']['srv']['SingleSignOnService'][$k]['bind'];
                        $tmp1 = $this->form_validation->set_rules('f[srv][SingleSignOnService][' . $k . '][url]', 'SingleSignOnService URL for: ' . $y['f']['srv']['SingleSignOnService']['' . $k . '']['bind'], 'trim|max_length[254]|valid_url');
                        $tmp2 = $this->form_validation->set_rules('f[srv][SingleSignOnService][' . $k . '][bind]', 'SingleSignOnService Binding protocol', 'required');
                        if ($tmp1 && $tmp2 && !empty($y['f']['srv']['SingleSignOnService']['' . $k . '']['url']))
                        {
                            ++$nosso;
                        }
                    }
                }
                if (array_key_exists('IDPSingleLogoutService', $y['f']['srv']))
                {
                    foreach ($y['f']['srv']['IDPSingleLogoutService'] as $k => $v)
                    {
                        $noidpslo[] = $y['f']['srv']['IDPSingleLogoutService']['' . $k . '']['bind'];
                        $this->form_validation->set_rules('f[srv][IDPSingleLogoutService][' . $k . '][url]', 'IDP SingleLogoutService URL for: ' . $y['f']['srv']['IDPSingleLogoutService']['' . $k . '']['bind'], 'trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][IDPSingleLogoutService][' . $k . '][bind]', 'IDP SingleLogoutService Binding protocol', 'required');
                    }
                }
                if (array_key_exists('SPSingleLogoutService', $y['f']['srv']))
                {
                    foreach ($y['f']['srv']['SPSingleLogoutService'] as $k => $v)
                    {
                        $nospslo[] = $y['f']['srv']['SPSingleLogoutService']['' . $k . '']['bind'];
                        $this->form_validation->set_rules('f[srv][SPSingleLogoutService][' . $k . '][url]', 'SP SingleLogoutService URL for: ' . $y['f']['srv']['SPSingleLogoutService']['' . $k . '']['bind'], 'trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][SPSingleLogoutService][' . $k . '][bind]', 'SP SingleLogoutService Binding protocol', 'required');
                    }
                }
                if(!array_key_exists('AssertionConsumerService', $y['f']['srv']) && ($this->type === 'SP' or $this->type === 'BOTH') )
                {
                      $y['f']['srv']['AssertionConsumerService'] = array();
                      log_message('debug','GGGG : creating AssertionConsumerService array');
                }
                if (array_key_exists('AssertionConsumerService', $y['f']['srv']))
                {
                    log_message('debug','GGGG : AssertionConsumerService array exists');
                    $acsindexes = array();
                    $acsurls = array();
                    $acsdefault = array();
                    foreach ($y['f']['srv']['AssertionConsumerService'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][url]', 'ACS URL', 'trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][bind]', 'ACS Binding protocol', 'trim|xss_clean');
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][order]', 'ACS Index', 'trim|xss_clean');

                        $tmpurl = trim($y['f']['srv']['AssertionConsumerService']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['AssertionConsumerService']['' . $k . '']['order']);
                        $tmpdefaultexist = array_key_exists('default', $y['f']['srv']['AssertionConsumerService']['' . $k . '']);
                        if (!empty($tmpurl))
                        {
                            if (!empty($v['order']))
                            {
                                $acsindexes[] = $v['order'];
                            }
                            $acsurls[] = 1;
                            if (!empty($tmporder) && !is_numeric($tmporder))
                            {
                                $this->tmp_error = 'One of the index order in ACS is not numeric';
                                return false;
                            }
                            if (array_key_exists('default', $y['f']['srv']['AssertionConsumerService']['' . $k . '']))
                            {
                                $acsdefault[] = 1;
                            }
                        }
                    }
                    if ($this->type != 'IDP')
                    {
                        if (count($acsindexes) != count(array_unique($acsindexes)))
                        {

                            $this->tmp_error = 'Not unique indexes found for ACS';
                            return false;
                        }
                        log_message('debug','GGGG staticdefault:'.$staticisdefault); 
                        if (count($acsurls) < 1 && empty($staticisdefault))
                        {

                            $this->tmp_error = 'At least one ACS URL must be set';
                            return false;
                        }
                        if (count($acsdefault) > 1)
                        {

                            $this->tmp_error = 'Only one ACS URL must be set as default';
                            return false;
                        }
                    }
                }

                if (array_key_exists('SPArtifactResolutionService', $y['f']['srv']))
                {
                    log_message('debug','GGGG : SPArtifactResolutionService array exists');
                    $spartindexes = array();
                    $sparturls = array();
                    foreach ($y['f']['srv']['SPArtifactResolutionService'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[srv][SPArtifactResolutionService][' . $k . '][url]', 'SP ArtifactResolutionService URL', 'trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][SPArtifactResolutionService][' . $k . '][bind]', 'SP ArtifactResolutionService Binding protocol', 'trim|xss_clean');

                        $tmpurl = trim($y['f']['srv']['SPArtifactResolutionService']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['SPArtifactResolutionService']['' . $k . '']['order']);
                        if (!empty($tmpurl))
                        {
                            if (!empty($v['order']))
                            {
                                $spartindexes[] = $v['order'];
                            }
                            $sparturls[] = 1;
                            if (!empty($tmporder) && !is_numeric($tmporder))
                            {
                                $this->tmp_error = 'One of the index order in SP ArtifactResolutionService is not numeric';
                                return false;
                            }
                        }
                    }
                    if ($this->type != 'IDP')
                    {
                        if (count($spartindexes) != count(array_unique($spartindexes)))
                        {

                            $this->tmp_error = 'Not unique indexes found for SP ArtifactResolutionService';
                            return false;
                        }
                    }
                }
                if (array_key_exists('IDPArtifactResolutionService', $y['f']['srv']))
                {
                    $idpartindexes = array();
                    $idparturls = array();
                    foreach ($y['f']['srv']['IDPArtifactResolutionService'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[srv][IDPArtifactResolutionService][' . $k . '][url]', 'IDP ArtifactResolutionService URL', 'trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][IDPArtifactResolutionService][' . $k . '][bind]', 'IDP ArtifactResolutionService Binding protocol', 'trim|xss_clean');

                        $tmpurl = trim($y['f']['srv']['IDPArtifactResolutionService']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['IDPArtifactResolutionService']['' . $k . '']['order']);
                        if (!empty($tmpurl))
                        {
                            if (!empty($v['order']))
                            {
                                $idpartindexes[] = $v['order'];
                            }
                            $idparturls[] = 1;
                            if (!empty($tmporder) && !is_numeric($tmporder))
                            {
                                $this->tmp_error = 'One of the index order in IDP ArtifactResolutionService is not numeric';
                                return false;
                            }
                        }
                    }
                    if ($this->type != 'SP')
                    {
                        if (count($idpartindexes) != count(array_unique($idpartindexes)))
                        {

                            $this->tmp_error = 'Not unique indexes found for IDP ArtifactResolutionService';
                            return false;
                        }
                    }
                }


                if (array_key_exists('DiscoveryResponse', $y['f']['srv']))
                {
                    $drindexes = array();
                    $drurls = array();

                    foreach ($y['f']['srv']['DiscoveryResponse'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[srv][DiscoveryResponse][' . $k . '][url]', 'DiscoveryResponse URL', 'trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][DiscoveryResponse][' . $k . '][bind]', 'DiscoveryResponse Binding protocol', 'trim|xss_clean');
                        $this->form_validation->set_rules('f[srv][DiscoveryResponse][' . $k . '][order]', 'DiscoveryResponse Index', 'trim|xss_clean');

                        $tmpurl = trim($y['f']['srv']['DiscoveryResponse']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['DiscoveryResponse']['' . $k . '']['order']);

                        if (!empty($tmpurl))
                        {
                            if (!empty($v['order']))
                            {
                                $drindexes[] = $v['order'];
                            }
                            $drurls[] = 1;
                            if (!empty($tmporder) && !is_numeric($tmporder))
                            {
                                $this->tmp_error = 'One of the index order in DiscoveryResponse is not numeric';
                                return false;
                            }
                        }
                    }
                    if ($this->type != 'IDP')
                    {
                        if (count($drindexes) != count(array_unique($drindexes)))
                        {
                            log_message('error', 'GGG: not unique ACS indexes found');
                            $this->tmp_error = 'Not unique indexes found for DiscoveryResponse';
                            return false;
                        }
                    }
                }
                if (array_key_exists('RequestInitiator', $y['f']['srv']))
                {
                    foreach ($y['f']['srv']['RequestInitiator'] as $k => $v)
                    {
                        $this->form_validation->set_rules('f[srv][RequestInitiator][' . $k . '][url]', 'RequestInitiator URL', 'trim|max_length[254]|valid_url');
                    }
                }
            }
            $result = $this->form_validation->run();
            if ($this->type != 'SP')
            {

                if (empty($nosso) && !$staticisdefault)
                {
                    $this->tmp_error = 'At least one SSO must be set';
                    return false;
                }
                if (!empty($nossobindings) && is_array($nossobindings) && count($nossobindings) > 0 && count(array_unique($nossobindings)) < count($nossobindings))
                {
                    $this->tmp_error = 'duplicate binding protocols for SSO found in sent form';
                    return false;
                }
                if (!empty($noidpslo) && is_array($noidpslo) && count($noidpslo) > 0 && count(array_unique($noidpslo)) < count($noidpslo))
                {
                    $this->tmp_error = 'duplicate binding protocols for IDP SLO found in sent form';
                    return false;
                }
                if (!empty($nosplo) && is_array($nosplo) && count($nosplo) > 0 && count(array_unique($nospslo)) < count($nospslo))
                {
                    $this->tmp_error = 'duplicate binding protocols for SP SLO found in sent form';
                    return false;
                }
            }
        }
        return $result;
    }

    private function _save_draft($id, $data)
    {
        $n = 'entform' . $id;
        $this->session->set_userdata($n, $data);
    }

    private function _get_draft($id)
    {
        $n = 'entform' . $id;
        return $this->session->userdata($n);
    }

    private function _discard_draft($id)
    {
        $n = 'entform' . $id;
        $this->session->unset_userdata($n);
    }

    private function _check_perms($id)
    {
        $has_write_access = $this->zacl->check_acl($id, 'write', 'entity');

        if (!$has_write_access)
        {
            show_error('No access to edit', 403);
            return false;
        }
    }


    public function show($id)
    {

        $ent = $this->tmp_providers->getOneById($id);
        if (empty($ent))
        {
            show_error('Provider not found', '404');
        }
        $locked = $ent->getLocked();
        $is_local = $ent->getLocal();
        if(!$is_local) 
        {
             show_error('Access Denied. Identity/Service Provider is not localy managed.',403);
        }
        if($locked)
        {
             show_error('Access Denied. Identity/Service Provider is locked and cannod be modified.', 403);
        }
        
        $this->type = $ent->getType();
        $this->_check_perms($id);
        $n = 'entform' . $id;

        if ($this->input->post('discard'))
        {
            $this->_discard_draft($id);
            redirect(base_url() . 'manage/entityedit/show/' . $id, 'location');
        }
        elseif ($this->_submit_validate($id) === TRUE)
        {
            $y = $this->input->post('f');
            $submittype = $this->input->post('modify');
            $this->_save_draft($id, $y);
            if ($submittype == 'modify')
            {

                $this->load->library('providerupdater');
                $c = $this->_get_draft($id);
                if (!empty($c) && is_array($c))
                {

                    $updateresult = $this->providerupdater->updateProvider($ent, $c);
                    if ($updateresult)
                    {
                        $this->em->persist($ent);
                        $this->em->flush();
                        $this->_discard_draft($id);
                        $showsuccess = TRUE;
                    }
                }
            }
        }
        $entsession = $this->_get_draft($id);

        $data['y'] = $entsession;

        $titlename = $ent->getName();
        if (empty($titlename))
        {
            $titlename = $ent->getEntityId();
        }
        $this->title = $titlename . ' :: ' . lang('title_provideredit');

        /**
         * @todo check locked
         */
        $data['entdetail'] = array('displayname' => $titlename, 'name' => $ent->getName(), 'id' => $ent->getId(), 'entityid' => $ent->getEntityId(), 'type' => $ent->getType());

        if (!empty($showsuccess))
        {
            $data['success_message'] = lang('updated');
            $data['content_view'] = 'manage/entityedit_success_view';
            $this->load->view('page', $data);
            return;
        }
        /**
         * menutabs array('id'=>xx,'v')
         */
        $data['error_messages'] = validation_errors('<p>', '</p>');
        $data['error_messages2'] = $this->tmp_error;
        $this->session->set_flashdata('entformerror', '');

        $menutabs[] = array('id' => 'general', 'value' => ''.lang('tabgeneral').'', 'form' => $this->form_element->NgenerateEntityGeneral($ent, $entsession));
        $menutabs[] = array('id' => 'dataprotection', 'value' => ''.lang('tabprivacy').'', 'form' => $this->form_element->NgeneratePrivacy($ent, $entsession));
        $menutabs[] = array('id' => 'protocols', 'value' => ''.lang('tabprotonameid').'', 'form' => $this->form_element->NgenerateProtocols($ent, $entsession));
        $menutabs[] = array('id' => 'services', 'value' => ''.lang('tabsrvs').'', 'form' => $this->form_element->NgenerateServiceLocationsForm($ent, $entsession));
        $menutabs[] = array('id' => 'certificates', 'value' => ''.lang('tabcerts').'', 'form' => $this->form_element->NgenerateCertificatesForm($ent, $entsession));
        $menutabs[] = array('id' => 'contacts', 'value' => ''.lang('tabcnts').'', 'form' => $this->form_element->NgenerateContactsForm($ent, $entsession));
        $menutabs[] = array('id' => 'uui', 'value' => ''.lang('tabuii').'', 'form' => $this->form_element->NgenerateUiiForm($ent, $entsession));
        $menutabs[] = array('id' => 'staticmetadata', 'value' => ''.lang('tabstaticmeta').'', 'form' => $this->form_element->NgenerateStaticMetadataForm($ent, $entsession));
        $menutabs[] = array('id' => 'other', 'value' => ''.lang('tabotherforms').'', 'form' => $this->form_element->NgenerateOtherFormLinks($ent));








        $data['menutabs'] = $menutabs;
        $data['content_view'] = 'manage/entityedit_view.php';
        // echo '<pre>';
        //print_r($this->session->all_userdata());
        //echo '</pre>';
        $this->load->view('page', $data);
    }

    public function reviewchanges($id)
    {
        $ent = $this->tmp_providers->getOneById($id);
        if (empty($ent))
        {
            show_error('Provider not found', 404);
        }
        $this->_check_perms($id);
        $titlename = $ent->getName();
        if (empty($titlename))
        {
            $titlename = $ent->getEntityId();
        }
        $n = 'entform' . $id;
        $this->title = $titlename . ' :: ' . lang('title_provideredit');
        $entsession = $this->_get_draft($id);
        $orig = array();
        $orig['entityid'] = $ent->getEntityId();
        //echo '<pre>';
        //print_r($entsession);
        //echo "=======\n";
        //print_r($orig);
        // echo '</pre>';
    }

    public function discardchanges($id)
    {
        
    }

    public function submitchanges($i)
    {
        
    }

}
