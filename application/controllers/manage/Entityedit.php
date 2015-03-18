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

/**
 * @property Curl $curl
 */
class Entityedit extends MY_Controller
{

    protected $current_site;
    protected $tmp_providers;
    protected $tmp_error;
    protected $type;
    protected $disallowedparts = array();
    protected $entityid;
    protected $idpsscoscope = array();
    protected $aascope = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('curl','form_element', 'form_validation', 'approval', 'providertoxml'));
        $this->tmp_providers = new models\Providers;
        $this->load->helper(array('shortcodes', 'form'));
        $this->tmp_error = '';
        $this->type = null;
        $entpartschangesdisallowed = $this->config->item('entpartschangesdisallowed');
        if (!empty($entpartschangesdisallowed) && is_array($entpartschangesdisallowed)) {
            $this->disallowedparts = $this->config->item('entpartschangesdisallowed');
        }
    }

    private function externalValidation($metaid, models\FederationValidator $fvalidator)
    {
        $metadataUrl = base_url().'/metadata/preregister/'.$metaid.'';
        $method = $fvalidator->getMethod();
        $remoteUrl = $fvalidator->getUrl();
        $entityParam = $fvalidator->getEntityParam();
        $optArgs = $fvalidator->getOptargs();
        $params = array();
        if (strcmp($method, 'GET') == 0)
        {
            $separator = $fvalidator->getSeparator();
            $optArgsStr = '';
            foreach ($optArgs as $k => $v)
            {
                if ($v === null)
                {
                    $optArgsStr .=$k . $separator;
                }
                else
                {
                    $optArgsStr .= $k . '=' . $v . '' . $separator;
                }
            }
            $optArgsStr .=$entityParam . '=' . urlencode($metadataUrl);
            $remoteUrl = $remoteUrl . $optArgsStr;
            $this->curl->create('' . $remoteUrl . '');
        }
        else
        {
            $params = $optArgs;
            $params['' . $entityParam . ''] = $metadataUrl;
            $this->curl->create('' . $remoteUrl . '');
            $this->curl->post($params);
        }

        $addoptions = array();
        $this->curl->options($addoptions);
        $data = $this->curl->execute();
        if (empty($data))
        {
            log_message('warning', 'External validator returned empty result');
            $this->tmp_error = 'External validator: timeout or returned empty result';
            return false;
        }
        log_message('debug', __METHOD__ . ' data received: ' . $data);
        $expectedDocumentType = $fvalidator->getDocutmentType();
        if (strcmp($expectedDocumentType, 'xml') != 0)
        {
            log_message('warning','Other than xml not supported yet');
            return true;
        }
        else
        {
            libxml_use_internal_errors(true);
            $sxe = simplexml_load_string($data);
            if (!$sxe)
            {
                log_message('warning','Received invalid xml document from external validator');
                $this->tmp_error = 'Received invalid response from external validator';
                return false;
            }
            $docxml = new \DomDocument();
            $docxml->loadXML($data);
            $returncodeElements = $fvalidator->getReturnCodeElement();
            if (count($returncodeElements) == 0)
            {
                log_message('error','FedValidator ('.$fvalidator->getId().') has not defined returned codes');
                return  true;
            }
            foreach ($returncodeElements as $v)
            {
                $codeDoms = $docxml->getElementsByTagName($v);
                if (!empty($codeDoms->length))
                {
                    break;
                }
            }
            $codeDomeValue = null;
            if (empty($codeDoms->length))
            {
                log_message('warning','Expected return code not received from externalvalidaor ignoring...');
                return true;
            }
            $codeDomeValue = trim($codeDoms->item(0)->nodeValue);
            log_message('debug', __METHOD__ . ' found expected value ' . $codeDomeValue);
            $expectedReturnValues = $fvalidator->getReturnCodeValues();
            $elementWithMessage = $fvalidator->getMessageCodeElements();
            $result = array();
            foreach ($expectedReturnValues as $k => $v)
            {

                if (is_array($v))
                {

                    foreach ($v as $v1)
                    {
                        if (strcasecmp($codeDomeValue, $v1) == 0)
                        {
                            $result['returncode'] = $k;
                            break;
                        }
                    }
                }
            }
            if (!isset($result['returncode']))
            {
                $result['returncode'] = 'unknown';
            }
            $result['message'] = array();
            foreach ($elementWithMessage as $v)
            {
                log_message('debug', __METHOD__ . ' searching for ' . $v . ' element');
                $o = $docxml->getElementsByTagName($v);
                if ($o->length > 0)
                {
                    $result['message'][$v] = array();
                    for ($i = 0; $i < $o->length; $i++)
                    {
                        $g = trim($o->item($i)->nodeValue);
                        log_message('debug', __METHOD__ . ' value for ' . $v . ' element: ' . $g);
                        if (!empty($g))
                        {
                            $result['message'][$v][] = htmlspecialchars($g);
                        }
                    }
                }
            }
            if (count($result['message']) == 0)
            {
                $result['message']['unknown'] = 'no response message';
            }

        }
        $this->tmp_error = 'externar validator: failed';
        if(isset($result['error']))
        {
            foreach ($result['error'] as $er)
            {
                $this->tmp_error  .= html_escape($er).PHP_EOL;
            }
        }
        return true;

    }

    private function submitValidate($id)
    {
        $register = false;
        if (strcmp($id, 'idp') == 0 || strcmp($id, 'sp') == 0) {
            $register = true;
            $this->type = strtoupper($id);
        }
        $optValidationsPassed = TRUE;
        $result = false;
        $y = $this->input->post();
        $staticisdefault = FALSE;
        if (isset($y['f'])) {
            $loggedin = $this->j_auth->logged_in();


            $this->form_validation->set_rules('f[usestatic]', 'use metadata', "valid_static[" . base64_encode($this->input->post('f[static]')) . ":::" . $this->input->post('f[entityid]') . " ]");


            // required if not static is set
            if (isset($y['f']['usestatic']) && $y['f']['usestatic'] === 'accept') {
                $staticisdefault = TRUE;
            }
            if (!$register) {
                if (in_array('entityid', $this->disallowedparts)) {
                    $this->form_validation->set_rules('f[entityid]', lang('rr_entityid'), 'trim|required|valid_urnorurl|min_length[4]|max_length[255]|matches_value[' . $this->entityid . ']');
                } else {
                    $this->form_validation->set_rules('f[entityid]', lang('rr_entityid'), 'trim|required|valid_urnorurl|min_length[4]|max_length[255]|entityid_unique_update[' . $id . ']');
                }
                if (in_array('scope', $this->disallowedparts)) {
                    $this->form_validation->set_rules('f[scopes][idpsso]', lang('rr_scope') . ' (IDPSSOdescriptor)', 'trim|valid_scopes|strtolower|max_length[2500]|str_matches_array[' . serialize($this->idpssoscope) . ']');
                    $this->form_validation->set_rules('f[scopes][aa]', lang('rr_scope') . ' (AuttributeAuthorityDescriptor)', 'trim|strtolower|valid_scopes|max_length[2500]|str_matches_array[' . serialize($this->aascope) . ']');
                } else {
                    $this->form_validation->set_rules('f[scopes][idpsso]', lang('rr_scope'), 'trim|strtolower|valid_scopes|max_length[2500]');
                    $this->form_validation->set_rules('f[scopes][aa]', lang('rr_scope'), 'trim|strtolower|valid_scopes|max_length[2500]');
                }
            } else {
                $this->form_validation->set_rules('f[entityid]', lang('rr_entityid'), 'trim|required|valid_urnorurl|min_length[5]|max_length[255]|entity_unique');
                if (!$loggedin) {
                    $this->form_validation->set_rules('f[primarycnt][mail]', lang('rr_youcntmail'), 'trim|required|valid_email');
                }
            }

            if (isset($y['reqattr'])) {
                foreach ($y['reqattr'] as $k => $r) {
                    $this->form_validation->set_rules('f[reqattr][' . $k . '][reason]', 'Attribute requirement reason', 'trim||htmlspecialchars');
                    $this->form_validation->set_rules('f[reqattr][' . $k . '][attrid]', 'Attribute requirement - attribute id is missing', 'trim|required|');
                }
            }
            $this->form_validation->set_rules('f[regauthority]', lang('rr_regauthority'), 'trim|htmlspecialchars');
            $this->form_validation->set_rules('f[registrationdate]', lang('rr_regdate'), 'trim|valid_date_past');
            $this->form_validation->set_rules('f[registrationtime]', lang('rr_regtime'), 'trim|valid_time_hhmm');
            $this->form_validation->set_rules('f[privacyurl]', lang('rr_defaultprivacyurl'), 'trim|valid_url');
            $allowedDigestMethods = j_DigestMethods();
            $allowedSigningMethods = j_SignatureAlgorithms();

            $this->form_validation->set_rules('f[algs][digest][]', 'DigestMethod', 'trim|in_list[' . implode(",", $allowedDigestMethods) . ']');
            $this->form_validation->set_rules('f[algs][signing][]', 'SigningMethod', 'trim|in_list[' . implode(",", $allowedSigningMethods) . ']');

            if (array_key_exists('lname', $y['f'])) {
                foreach ($y['f']['lname'] as $k => $v) {
                    $this->form_validation->set_rules('f[lname][' . $k . ']', ucfirst(lang('localizednamein')) . ' ' . $k, 'strip_tags|trim');
                }
                if (count(array_filter($y['f']['lname'])) == 0) {
                    $this->tmp_error = lang('errnoorgnames');
                    $optValidationsPassed = FALSE;
                }
            } else {
                $this->tmp_error = lang('errnoorgnames');
                $optValidationsPassed = FALSE;
            }
            if (array_key_exists('ldisplayname', $y['f'])) {
                foreach ($y['f']['ldisplayname'] as $k => $v) {
                    $this->form_validation->set_rules('f[ldisplayname][' . $k . ']', ucfirst(lang('localizeddisplaynamein')) . ' ' . $k, 'strip_tags|trim');
                }
                if (count(array_filter($y['f']['ldisplayname'])) == 0) {
                    $this->tmp_error = lang('errnoorgdisnames');
                    $optValidationsPassed = FALSE;
                }
            } else {
                $this->tmp_error = lang('errnoorgdisnames');
                $optValidationsPassed = FALSE;
            }

            if (isset($y['f']['uii']['idpsso']['geo']) && is_array($y['f']['uii']['idpsso']['geo'])) {
                foreach ($y['f']['uii']['idpsso']['geo'] as $k => $v) {
                    /**
                     * @todo GEO validation
                     */
                    $this->form_validation->set_rules('f[uii][idpsso][geo][' . $k . ']', 'Geolocation ', 'strip_tags|trim|min_length[3]|max_length[40]|valid_latlng');
                }
            }

            if (isset($y['f']['uii']['idpsso']['displayname']) && is_array($y['f']['uii']['idpsso']['displayname'])) {
                foreach ($y['f']['uii']['idpsso']['displayname'] as $k => $v) {
                    $this->form_validation->set_rules('f[uii][idpsso][displayname][' . $k . ']', 'UUI ' . sprintf(lang('lrr_displayname'), $k) . '', 'strip_tags|trim|min_length[3]|max_length[255]');

                }
            }
            if (isset($y['f']['uii']['idpsso']['desc']) && is_array($y['f']['uii']['idpsso']['desc'])) {
                foreach ($y['f']['uii']['idpsso']['desc'] as $k => $v) {
                    $this->form_validation->set_rules('f[uii][idpsso][desc][' . $k . ']', 'UUI ' . lang('rr_description') . ' ' . lang('in') . ' ' . $k . '', 'trim|min_length[3]|max_length[500]');
                }
            }
            if (isset($y['f']['uii']['idpsso']['helpdesk']) && is_array($y['f']['uii']['idpsso']['helpdesk'])) {
                foreach ($y['f']['uii']['idpsso']['helpdesk'] as $k => $v) {
                    $this->form_validation->set_rules('f[uii][idpsso][helpdesk][' . $k . ']', 'UUI ' . lang('rr_helpdeskurl') . ' ' . lang('in') . ' ' . $k . '', 'strip_tags|trim|valid_url|min_length[5]|max_length[500]');
                }
            }
            if (isset($y['f']['uii']['idpsso']['iphint']) && is_array($y['f']['uii']['idpsso']['iphint'])) {
                foreach ($y['f']['uii']['idpsso']['iphint'] as $k => $v) {
                    $this->form_validation->set_rules('f[uii][idpsso][iphint][' . $k . ']', 'IPHint', 'trim|valid_ip_with_prefix|min_length[5]|max_length[500]');
                }
            }
            if (isset($y['f']['uii']['idpsso']['domainhint']) && is_array($y['f']['uii']['idpsso']['domainhint'])) {
                foreach ($y['f']['uii']['idpsso']['domainhint'] as $k => $v) {
                    $this->form_validation->set_rules('f[uii][idpsso][domainhint][' . $k . ']', 'DomainHint', 'trim|valid_domain|min_length[4]|max_length[500]');
                }
            }

            if (array_key_exists('lhelpdesk', $y['f'])) {
                foreach ($y['f']['lhelpdesk'] as $k => $v) {
                    $this->form_validation->set_rules('f[lhelpdesk][' . $k . ']', lang('localizedhelpdeskin') . ' ' . $k, 'strip_tags|trim|required|valid_url');
                }

            } else {
                $this->tmp_error = lang('errnoorgurls');
                $optValidationsPassed = FALSE;
            }


            if (array_key_exists('contact', $y['f'])) {
                foreach ($y['f']['contact'] as $k => $v) {
                    $this->form_validation->set_rules('f[contact][' . $k . '][email]', '' . lang('rr_contactemail') . '', 'trim|htmlspecialchars|valid_email');
                    $this->form_validation->set_rules('f[contact][' . $k . '][type]', '' . lang('rr_contacttype') . '', 'trim|htmlspecialchars|valid_contact_type');
                    $this->form_validation->set_rules('f[contact][' . $k . '][fname]', '' . lang('rr_contactfirstname') . '', 'trim|htmlspecialchars');
                    $this->form_validation->set_rules('f[contact][' . $k . '][sname]', '' . lang('rr_contactlastname') . '', 'trim|htmlspecialchars');
                }
            }
            if (array_key_exists('prot', $y['f'])) {
                foreach ($y['f']['prot'] as $key => $value) {
                    foreach ($value as $k => $v) {
                        $this->form_validation->set_rules('f[prot][' . $key . '][' . $k . ']', 'trim|htmlspecialchars');
                    }
                }
            }
            /**
             * certificates
             */
            $allowedEnryptionMethods = j_KeyEncryptionAlgorithms();
            $allowedEnryptionMethodsInList = implode(",", $allowedEnryptionMethods);
            if (array_key_exists('crt', $y['f'])) {
                if (array_key_exists('spsso', $y['f']['crt'])) {
                    foreach ($y['f']['crt']['spsso'] as $k => $v) {
                        if (is_numeric($k)) {
                            $this->form_validation->set_rules('f[crt][spsso][' . $k . '][certdata]', 'cert data', 'trim|getPEM|verify_cert_nokeysize');
                        } else {
                            $this->form_validation->set_rules('f[crt][spsso][' . $k . '][certdata]', 'cert data', 'trim|getPEM|verify_cert');
                        }
                        $this->form_validation->set_rules('f[crt][spsso][' . $k . '][usage]', '' . lang('rr_certificateuse') . '', 'htmlspecialchars|trim|required');
                        $this->form_validation->set_rules('f[crt][spsso][' . $k . '][encmethods][]', 'Certificate EncryptionMethod', 'trim|in_list[' . $allowedEnryptionMethodsInList . ']');

                    }
                }
                if (array_key_exists('idpsso', $y['f']['crt'])) {
                    foreach ($y['f']['crt']['idpsso'] as $k => $v) {
                        if (is_numeric($k)) {
                            $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][certdata]', 'Certificate', 'trim|getPEM|verify_cert_nokeysize');
                        } else {
                            $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][certdata]', 'Certificate', 'trim|getPEM|verify_cert');
                        }
                        $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][usage]', '' . lang('rr_certificateuse') . '', 'htmlspecialchars|trim|required');
                        $this->form_validation->set_rules('f[crt][idpsso][' . $k . '][encmethods][]', 'Certificate EncryptionMethod', 'trim|in_list[' . $allowedEnryptionMethodsInList . ']');
                    }
                }
                if (array_key_exists('aa', $y['f']['crt'])) {
                    foreach ($y['f']['crt']['aa'] as $k => $v) {
                        if (is_numeric($k)) {
                            $this->form_validation->set_rules('f[crt][aa][' . $k . '][certdata]', 'Certificate', 'trim|getPEM|verify_cert_nokeysize');
                        } else {
                            $this->form_validation->set_rules('f[crt][aa][' . $k . '][certdata]', 'Certificate', 'trim|getPEM|verify_cert');
                        }
                        $this->form_validation->set_rules('f[crt][aa][' . $k . '][usage]', '' . lang('rr_certificateuse') . '', 'htmlspecialchars|trim|required');
                        $this->form_validation->set_rules('f[crt][aa][' . $k . '][encmethods][]', 'Certificate EncryptionMethod', 'trim|in_list[' . $allowedEnryptionMethodsInList . ']');
                    }
                }
            }

            /**
             * service locations
             */
            $nosso = 0;
            $nossobindings = array();
            $noidpslo = array();
            if (array_key_exists('srv', $y['f'])) {
                if (array_key_exists('IDPAttributeService', $y['f']['srv'])) {
                    foreach ($y['f']['srv']['IDPAttributeService'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][IDPAttributeService][' . $k . '][url]', 'AttributeAuthorityDescriptor/AttributeService: ' . html_escape($y['f']['srv']['SingleSignOnService']['' . $k . '']['bind']), 'strip_tags|trim|max_length[254]|valid_url');
                    }
                }
                if (!array_key_exists('SingleSignOnService', $y['f']['srv'])) {
                    $y['f']['srv']['SingleSignOnService'] = array();
                }
                foreach ($y['f']['srv']['SingleSignOnService'] as $k => $v) {
                    $nossobindings[] = $y['f']['srv']['SingleSignOnService'][$k]['bind'];
                    $tmp1 = $this->form_validation->set_rules('f[srv][SingleSignOnService][' . $k . '][url]', 'SingleSignOnService URL for: ' . $y['f']['srv']['SingleSignOnService']['' . $k . '']['bind'], 'strip_tags|trim|max_length[254]|valid_url');
                    $tmp2 = $this->form_validation->set_rules('f[srv][SingleSignOnService][' . $k . '][bind]', 'SingleSignOnService Binding protocol', 'htmlspecialchars|required');
                    if ($tmp1 && $tmp2 && !empty($y['f']['srv']['SingleSignOnService']['' . $k . '']['url'])) {
                        ++$nosso;
                    }
                }
                if (array_key_exists('IDPSingleLogoutService', $y['f']['srv'])) {
                    foreach ($y['f']['srv']['IDPSingleLogoutService'] as $k => $v) {
                        $noidpslo[] = $y['f']['srv']['IDPSingleLogoutService']['' . $k . '']['bind'];
                        $this->form_validation->set_rules('f[srv][IDPSingleLogoutService][' . $k . '][url]', 'IDP SingleLogoutService URL for: ' . $y['f']['srv']['IDPSingleLogoutService']['' . $k . '']['bind'], 'strip_tags|trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][IDPSingleLogoutService][' . $k . '][bind]', 'IDP SingleLogoutService Binding protocol', 'required');
                    }
                }
                if (array_key_exists('SPSingleLogoutService', $y['f']['srv'])) {

                    foreach ($y['f']['srv']['SPSingleLogoutService'] as $k => $v) {
                        $nospslo[] = $y['f']['srv']['SPSingleLogoutService']['' . $k . '']['bind'];
                        $this->form_validation->set_rules('f[srv][SPSingleLogoutService][' . $k . '][url]', 'SP SingleLogoutService URL for: ' . $y['f']['srv']['SPSingleLogoutService']['' . $k . '']['bind'], 'strip_tags|trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][SPSingleLogoutService][' . $k . '][bind]', 'SP SingleLogoutService Binding protocol', 'required|htmlspecialchars');
                    }
                }
                if (!array_key_exists('AssertionConsumerService', $y['f']['srv']) && ($this->type === 'SP' || $this->type === 'BOTH')) {
                    $y['f']['srv']['AssertionConsumerService'] = array();
                }
                if (array_key_exists('AssertionConsumerService', $y['f']['srv'])) {
                    $acsindexes = array();
                    $acsurls = array();
                    $acsdefault = array();
                    foreach ($y['f']['srv']['AssertionConsumerService'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][url]', 'AssertionConsumerService URL', 'strip_tags|trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][bind]', 'AssertionConsumerService Binding protocol', 'trim|htmlspecialchars');
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][order]', 'AssertionConsumerService Index', 'trim|htmlspecialchars');

                        $tmpurl = trim($y['f']['srv']['AssertionConsumerService']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['AssertionConsumerService']['' . $k . '']['order']);
                        if (!empty($tmpurl)) {
                            if (!empty($v['order'])) {
                                $acsindexes[] = $v['order'];
                            }
                            $acsurls[] = 1;
                            if (!empty($tmporder) && !is_numeric($tmporder)) {
                                $this->tmp_error = 'One of the index order in ACS is not numeric';
                                $optValidationsPassed = FALSE;
                            }
                            if (array_key_exists('default', $y['f']['srv']['AssertionConsumerService']['' . $k . ''])) {
                                $acsdefault[] = 1;
                            }
                        }
                    }
                    if ($this->type != 'IDP') {
                        if (count($acsindexes) != count(array_unique($acsindexes))) {

                            $this->tmp_error = 'Not unique indexes found for ACS';
                            $optValidationsPassed = FALSE;
                        }
                        if (count($acsurls) < 1 && empty($staticisdefault)) {

                            $this->tmp_error = lang('rr_acsurlatleastone');
                            $optValidationsPassed = FALSE;
                        }
                        if (count($acsdefault) > 1) {

                            $this->tmp_error = lang('rr_acsurlonlyonedefault');
                            $optValidationsPassed = FALSE;
                        }
                    }
                }

                if (array_key_exists('SPArtifactResolutionService', $y['f']['srv'])) {
                    $spartindexes = array();
                    foreach ($y['f']['srv']['SPArtifactResolutionService'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][SPArtifactResolutionService][' . $k . '][url]', 'SP ' . lang('ArtifactResolutionService') . ' URL', 'strip_tags|trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][SPArtifactResolutionService][' . $k . '][bind]', 'SP ' . lang('ArtifactResolutionService') . ' Binding protocol', 'htmlspecialchars|trim');


                        $tmpurl = trim($this->input->post('f[srv][SPArtifactResolutionService][' . $k . '][url]'));
                        $tmporder = $this->input->post('f[srv][SPArtifactResolutionService][' . $k . '][order]');

                        if (!empty($tmpurl)) {
                            if (!empty($v['order'])) {
                                $spartindexes[] = $v['order'];
                            }
                            if (!empty($tmporder) && !is_numeric($tmporder)) {
                                $this->tmp_error = 'One of the index order in SP ArtifactResolutionService is not numeric';
                                $optValidationsPassed = FALSE;
                            }
                        }
                    }
                    if (strcasecmp($this->type, 'IDP') != 0) {
                        if (count($spartindexes) != count(array_unique($spartindexes))) {

                            $this->tmp_error = 'Not unique indexes found for SP ArtifactResolutionService';
                            $optValidationsPassed = FALSE;
                        }
                    }
                }
                if (array_key_exists('IDPArtifactResolutionService', $y['f']['srv'])) {
                    $idpartindexes = array();
                    foreach ($y['f']['srv']['IDPArtifactResolutionService'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][IDPArtifactResolutionService][' . $k . '][url]', 'IDP ArtifactResolutionService URL', 'strip_tags|trim|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][IDPArtifactResolutionService][' . $k . '][bind]', 'IDP ArtifactResolutionService Binding protocol', 'strip_tags|trim');

                        $tmpurl = trim($y['f']['srv']['IDPArtifactResolutionService']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['IDPArtifactResolutionService']['' . $k . '']['order']);
                        if (!empty($tmpurl)) {
                            if (!empty($v['order'])) {
                                $idpartindexes[] = $v['order'];
                            }
                            if (!empty($tmporder) && !is_numeric($tmporder)) {
                                $this->tmp_error = 'One of the index order in IDP ArtifactResolutionService is not numeric';
                                $optValidationsPassed = FALSE;
                            }
                        }
                    }
                    if ($this->type != 'SP') {
                        if (count($idpartindexes) != count(array_unique($idpartindexes))) {

                            $this->tmp_error = 'Not unique indexes found for IDP ArtifactResolutionService';
                            $optValidationsPassed = FALSE;
                        }
                    }
                }
                if (array_key_exists('DiscoveryResponse', $y['f']['srv'])) {
                    $drindexes = array();

                    foreach ($y['f']['srv']['DiscoveryResponse'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][DiscoveryResponse][' . $k . '][url]', 'DiscoveryResponse URL', 'strip_tags|trim|required|max_length[254]|valid_url');
                        $this->form_validation->set_rules('f[srv][DiscoveryResponse][' . $k . '][bind]', 'DiscoveryResponse Binding protocol', 'trim|required');
                        $this->form_validation->set_rules('f[srv][DiscoveryResponse][' . $k . '][order]', 'DiscoveryResponse Index', 'trim|required|numeric');
                        $tmpurl = trim($y['f']['srv']['DiscoveryResponse']['' . $k . '']['url']);
                        $tmporder = trim($y['f']['srv']['DiscoveryResponse']['' . $k . '']['order']);

                        if (!empty($tmpurl)) {
                            if (!empty($v['order'])) {
                                $drindexes[] = $v['order'];
                            }
                            if (!empty($tmporder) && !is_numeric($tmporder)) {
                                $this->tmp_error = 'One of the index order in DiscoveryResponse is not numeric';
                                $optValidationsPassed = FALSE;
                            }
                        }
                    }
                    if (strcasecmp($this->type, 'IDP') != 0) {
                        if (count($drindexes) != count(array_unique($drindexes))) {
                            $this->tmp_error = 'Not unique indexes found for DiscoveryResponse';
                            $optValidationsPassed = FALSE;
                        }
                    }
                }
                if (array_key_exists('RequestInitiator', $y['f']['srv'])) {
                    foreach ($y['f']['srv']['RequestInitiator'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][RequestInitiator][' . $k . '][url]', 'RequestInitiator URL', 'strip_tags|trim|max_length[254]|valid_url');
                    }
                }
            }
            $result = $this->form_validation->run();
            if (strcasecmp($this->type, 'SP') != 0) {

                if (empty($nosso) && !$staticisdefault) {
                    $this->tmp_error = 'At least one SSO must be set';
                    return false;
                }
                if (!empty($nossobindings) && is_array($nossobindings) && count($nossobindings) > 0 && count(array_unique($nossobindings)) < count($nossobindings)) {
                    $this->tmp_error = 'duplicate binding protocols for SSO found in sent form';
                    $optValidationsPassed = FALSE;
                }
                if (!empty($noidpslo) && is_array($noidpslo) && count($noidpslo) > 0 && count(array_unique($noidpslo)) < count($noidpslo)) {
                    $this->tmp_error = 'duplicate binding protocols for IDP SLO found in sent form';
                    $optValidationsPassed = FALSE;
                }
                if (!empty($nosplo) && is_array($nosplo) && count($nosplo) > 0 && count(array_unique($nospslo)) < count($nospslo)) {
                    $this->tmp_error = 'duplicate binding protocols for SP SLO found in sent form';
                    $optValidationsPassed = FALSE;
                }
            }
        }

        $r = $this->input->post();
        if (isset($r['f'])) {
            $this->saveToDraft($id, $this->input->post('f'));
        }

        return ($result && $optValidationsPassed);
    }

    private function saveToDraft($id, $data)
    {
        $attrs1 = array('lname', 'ldisplayname', 'lhelpdesk', 'coc');
        foreach ($attrs1 as $a1) {
            if (isset($data['' . $a1 . ''])) {
                $data['' . $a1 . ''] = array_filter($data['' . $a1 . '']);
            } else {
                $data['' . $a1 . ''] = array();
            }
        }
        // global algs
        $algstypes = array('digest', 'signing');
        foreach ($algstypes as $alg) {
            if (isset($data['algs']['' . $alg . ''])) {
                $data['algs']['' . $alg . ''] = array_filter($data['algs']['' . $alg . '']);
            } else {
                $data['algs']['' . $alg . ''] = array();
            }
        }
        //  crt
        $crts = array('idpsso', 'aa', 'spsso');
        foreach ($crts as $a1) {
            if (isset($data['crt']['' . $a1 . ''])) {
                $data['crt']['' . $a1 . ''] = array_filter($data['crt']['' . $a1 . '']);
            } else {
                $data['crt']['' . $a1 . ''] = array();
            }
        }
        $srvs = array(
            'AssertionConsumerService', 'RequestInitiator', 'SPArtifactResolutionService',
            'IDPArtifactResolutionService', 'IDPAttributeService', 'DiscoveryResponse',
            'SingleSignOnService', 'IDPSingleLogoutService', 'SPSingleLogoutService'
        );
        foreach ($srvs as $a1) {
            if (isset($data['srv']['' . $a1 . ''])) {
                $data['srv']['' . $a1 . ''] = array_filter($data['srv']['' . $a1 . '']);
            } else {
                $data['srv']['' . $a1 . ''] = array();
            }
        }
        // uii
        $uiitTypes = array('idpsso', 'spsso');
        $uiiSubTypes = array('desc', 'logo', 'helpdesk', 'displayname');
        foreach ($uiitTypes as $t) {
            if ($t === 'idpsso') {
                $uiiSubTypes[] = 'iphint';
                $uiiSubTypes[] = 'domainhint';
                $uiiSubTypes[] = 'geo';
            }
            foreach ($uiiSubTypes as $p) {
                if (isset($data['uii']['' . $t . '']['' . $p . ''])) {
                    $data['uii']['' . $t . '']['' . $p . ''] = array_filter($data['uii']['' . $t . '']['' . $p . '']);
                } else {
                    $data['uii']['' . $t . '']['' . $p . ''] = array();
                }
            }
        }

        if (isset($data['reqattr'])) {
            $data['reqattr'] = array_filter($data['reqattr']);
        } else {
            $data['reqattr'] = array();
        }
        if (isset($data['prvurl']['idpsso'])) {
            $data['prvurl']['idpsso'] = array_filter($data['prvurl']['idpsso']);
        } else {
            $data['prvurl']['idpsso'] = array();
        }

        if (isset($data['prvurl']['spsso'])) {
            $data['prvurl']['spsso'] = array_filter($data['prvurl']['spsso']);
        } else {
            $data['prvurl']['spsso'] = array();
        }
        if (isset($data['prot']['spsso'])) {
            $data['prot']['spsso'] = array_filter($data['prot']['spsso']);
        } else {
            $data['prot']['spsso'] = array();
        }
        if (isset($data['contact'])) {
            foreach ($data['contact'] as $k => $v) {
                if (empty($v['email'])) {
                    unset($data['contact']['' . $k . '']);
                }
            }
        } else {
            $data['contact'] = array();
        }
        $n = 'entform' . $id;
        $this->session->set_userdata($n, $data);
    }

    private function getFromDraft($id)
    {
        $n = 'entform' . $id;
        return $this->session->userdata($n);
    }

    private function discardDraft($id)
    {
        $n = 'entform' . $id;
        $this->session->unset_userdata($n);
    }

    private function checkPermissions($id)
    {
        $has_write_access = $this->zacl->check_acl($id, 'write', 'entity');

        if (!$has_write_access) {
            show_error('No access to edit', 403);
            return false;
        }
    }

    public function show($id)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        } else {
            $this->load->library('zacl');
        }

        $ent = $this->tmp_providers->getOneById($id);
        if (empty($ent)) {
            show_error('Provider not found', '404');
        }
        $locked = $ent->getLocked();
        $is_local = $ent->getLocal();
        if (!$is_local) {
            show_error('Access Denied. Identity/Service Provider is not localy managed.', 403);
        }
        if ($locked) {
            show_error('Access Denied. Identity/Service Provider is locked and cannod be modified.', 403);
        }
        $this->entityid = $ent->getEntityId();
        $this->idpssoscope = $ent->getScope('idpsso');
        $this->aascope = $ent->getScope('aa');
        $this->type = $ent->getType();
        $this->checkPermissions($id);
        $data['jsAddittionalFiles'][] = 'https://maps.googleapis.com/maps/api/js?v=3.exp&signed_in=true&libraries=places';


        if ($this->input->post('discard')) {
            $this->discardDraft($id);
            redirect(base_url() . 'providers/detail/show/' . $id, 'location');
        } elseif ($this->submitValidate($id) === TRUE) {
            $y = $this->input->post('f');
            $submittype = $this->input->post('modify');
            $this->saveToDraft($id, $y);
            if ($submittype === 'modify') {
                $this->load->library('providerupdater');
                $c = $this->getFromDraft($id);
                if (!empty($c) && is_array($c)) {

                    $updateresult = $this->providerupdater->updateProvider($ent, $c);
                    if ($updateresult) {
                        $cacheId = 'mcircle_' . $ent->getId();
                        $cacheId2 = 'mstatus_' . $ent->getId();
                        $this->em->persist($ent);
                        $this->em->flush();
                        $this->discardDraft($id);
                        $keyPrefix = getCachePrefix();
                        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
                        $this->cache->delete($cacheId);
                        $this->cache->delete($cacheId2);
                        $showsuccess = TRUE;
                    }
                }
            }
        }
        $entsession = $this->getFromDraft($id);
        if (!empty($entsession)) {
            $data['sessform'] = true;
        }

        $data['y'] = $entsession;
        $lang = MY_Controller::getLang();

        $titlename = $ent->getNameToWebInLang($lang, $ent->getType());
        $this->title = $titlename . ' :: ' . lang('title_provideredit');

        /**
         * @todo check locked
         */
        $data['entdetail'] = array('displayname' => $titlename, 'name' => $ent->getName(), 'id' => $ent->getId(), 'entityid' => $ent->getEntityId(), 'type' => $ent->getType());

        if (!empty($showsuccess)) {
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


        $this->load->library('providerformelements', array('provider' => $ent, 'session' => $entsession));


        $menutabs[] = array('id' => 'organization', 'value' => '' . lang('taborganization') . '', 'form' => $this->providerformelements->generateGeneral());
        $menutabs[] = array('id' => 'contacts', 'value' => '' . lang('tabcnts') . '', 'form' => $this->form_element->NgenerateContactsForm($ent, $entsession));
        $menutabs[] = array('id' => 'uii', 'value' => '' . lang('tabuii') . '', 'form' => $this->form_element->NgenerateUiiForm($ent, $entsession));
        if (strcasecmp($this->type, 'SP') != 0) {
            $menutabs[] = array('id' => 'uihints', 'value' => '' . lang('tabuihint') . '', 'form' => $this->form_element->generateUIHintForm($ent, $entsession));
        }
        $menutabs[] = array('id' => 'tabsaml', 'value' => '' . lang('tabsaml') . '', 'form' => $this->form_element->NgenerateSAMLTab($ent, $entsession));
        $menutabs[] = array('id' => 'certificates', 'value' => '' . lang('tabcerts') . '', 'form' => $this->form_element->NgenerateCertificatesForm($ent, $entsession));
        $menutabs[] = array('id' => 'entcategories', 'value' => '' . lang('tabentcategories') . '', 'form' => $this->form_element->NgenerateEntityCategoriesForm($ent, $entsession));
        if (strcasecmp($this->type, 'IDP') != 0) {
            $menutabs[] = array('id' => 'reqattrs', 'value' => '' . lang('tabreqattrs') . '', 'form' => $this->form_element->nGenerateAttrsReqs($ent, $entsession));
        }
        $menutabs[] = array('id' => 'staticmetadata', 'value' => '' . lang('tabstaticmeta') . '', 'form' => $this->form_element->NgenerateStaticMetadataForm($ent, $entsession));
        $data['menutabs'] = $menutabs;
        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $data['entdetail']['id'] . '">' . $data['entdetail']['displayname'] . '</a>';
        $data['content_view'] = 'manage/entityedit_view.php';

        $data['rawJs'][] = "";
        if (strcasecmp($this->type, 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        } else {
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        }
        $data['breadcrumbs'] = array(
            $plist,
            array('url' => base_url('providers/detail/show/' . $data['entdetail']['id'] . ''), 'name' => '' . $data['entdetail']['displayname'] . ''),
            array('url' => '#', 'name' => lang('rr_edit'), 'type' => 'current'),

        );
        $this->load->view('page', $data);
    }

    private function isFromSimpleRegistration()
    {
        $fromSimpleMode = $this->input->post('advanced');
        if (!empty($fromSimpleMode) && strcmp($fromSimpleMode, 'advanced') == 0) {
            return true;
        }

        return false;
    }

    public function register($t = null)
    {
        MY_Controller::$menuactive = 'reg';
        $data['jsAddittionalFiles'][] = 'https://maps.googleapis.com/maps/api/js?v=3.exp&signed_in=true&libraries=places';
        $data['registerForm'] = TRUE;
        $t = trim($t);
        if (empty($t) || !(strcmp($t, 'idp') == 0 || strcmp($t, 'sp') == 0)) {
            show_error('Not found', 404);
            return;
        }
        $ent = new models\Provider;
        $ent->setLocal(TRUE);
        if (strcmp($t, 'idp') == 0) {
            $ent->setType('IDP');
            $data['titlepage'] = lang('rr_idp_register_title');
            $data['breadcrumbs'] = array(
                array('url' => '#', 'name' => lang('rr_idp_register_title'), 'type' => 'current'),

            );
        } else {
            $ent->setType('SP');
            $data['titlepage'] = lang('rr_sp_register_title');
            $data['breadcrumbs'] = array(
                array('url' => '#', 'name' => lang('rr_sp_register_title'), 'type' => 'current'),

            );
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            $data['anonymous'] = TRUE;
        } else {
            $data['anonymous'] = FALSE;
            $currentusername = $this->j_auth->current_user();
            $u = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $currentusername . ''));

            $data['loggeduser'] = array(
                'username' => '' . $currentusername . '',
                'fullname' => '' . $u->getFullname() . '',
                'fname' => '' . $u->getGivenname() . '',
                'lname' => '' . $u->getSurname() . '',
                'email' => '' . $u->getEmail() . '',
            );
        }

        $fedCollection = $this->em->getRepository("models\Federation")->findBy(array('is_public' => TRUE, 'is_active' => TRUE));
        if (count($fedCollection) > 0) {
            $data['federations'] = array();
            /**
             *  generate dropdown list of public federations
             */
            $data['federations']['none'] = lang('noneatthemoment');
            foreach ($fedCollection as $key) {
                $data['federations'][$key->getName()] = $key->getName();
            }
        }


        /**
         * check if submit from simpleform
         */
        if ($this->isFromSimpleRegistration()) {
            $metadatabody = trim($this->input->post('metadatabody'));
            if (!empty($metadatabody)) {
                $this->load->library('xmlvalidator');
                libxml_use_internal_errors(true);
                $metadataDOM = new \DOMDocument();
                $metadataDOM->strictErrorChecking = FALSE;
                $metadataDOM->WarningChecking = FALSE;
                $metadataDOM->loadXML($metadatabody);
                $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
                if (!$isValid) {
                    log_message('warning', __METHOD__ . ' invalid metadata had been pasted in registration form');
                    $this->tmp_error = lang('err_pastedtxtnotvalidmeta');
                } else {
                    $this->discardDraft($t);
                    $this->load->library('metadata2array');
                    $xpath = new DomXPath($metadataDOM);
                    $namespaces = h_metadataNamespaces();
                    foreach ($namespaces as $key => $value) {
                        $xpath->registerNamespace($key, $value);
                    }
                    $domlist = $metadataDOM->getElementsByTagName('EntityDescriptor');
                    if (count($domlist) == 1) {
                        foreach ($domlist as $l) {
                            $entarray = $this->metadata2array->entityDOMToArray($l, TRUE);
                        }
                        $o = current($entarray);
                        if (isset($o['type']) && strcasecmp($o['type'], $t) == 0) {
                            $ent->setProviderFromArray($o);
                            if (isset($o['details']['reqattrs'])) {
                                $attrsDefinitions = $this->em->getRepository("models\Attribute")->findAll();
                                foreach ($attrsDefinitions as $v) {
                                    $attributes['' . $v->getOid() . ''] = $v;
                                }
                                $attrsset = array();
                                foreach ($o['details']['reqattrs'] as $r) {
                                    if (array_key_exists($r['name'], $attributes)) {
                                        if (!in_array($r['name'], $attrsset)) {
                                            $reqattr = new models\AttributeRequirement;
                                            $reqattr->setAttribute($attributes['' . $r['name'] . '']);
                                            $reqattr->setType('SP');
                                            if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0) {
                                                $reqattr->setStatus('required');
                                            } else {
                                                $reqattr->setStatus('desired');
                                            }
                                            $reqattr->setReason('');

                                            $ent->setAttributesRequirement($reqattr);
                                            $this->em->persist($reqattr);
                                            $attrsset[] = $r['name'];
                                        }
                                    } else {
                                        log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
                                    }
                                }
                            }
                        } else {
                            $this->tmp_error = lang('regcantimporttype');
                        }
                    }
                }
            }
        } elseif ($this->input->post('discard')) {
            $this->discardDraft($t);
            redirect(base_url() . 'providers/' . strtolower($t) . '_registration', 'location');
        } elseif ($this->submitValidate($t) === TRUE) {
            $y = $this->input->post('f');
            $submittype = $this->input->post('modify');
            if ($submittype === 'modify') {
                \log_message('debug', __METHOD__ . 'submittype=modify');
                $this->load->library('providerupdater');
                $c = $this->getFromDraft($t);
                if (!empty($c) && is_array($c)) {

                    \log_message('debug', __METHOD__ . ' GKS data from draft: ' . serialize($c));
                    $ent = $this->providerupdater->updateProvider($ent, $c);

                    if ($ent) {
                        $registrationAutority = $this->config->item('registrationAutority');
                        if (!empty($registrationAutority)) {
                            $ent->setRegistrationAuthority(trim($registrationAutority));
                            $dateNow = new \DateTime("now");
                            $ent->setRegistrationDate($dateNow);

                        }
                        $ent->setActive(TRUE);
                        /// create queue
                        $q = new models\Queue;
                        if (!empty($u)) {
                            $contactMail = $u->getEmail();
                            $q->setCreator($u);
                        }
                        $q->setAction("Create");
                        $lnames = $ent->getMergedLocalName();
                        if (is_array($lnames) && count($lnames) > 0) {
                            $q->setName(current($lnames));
                        } else {
                            $q->setName('unknown');
                        }
                        $ttype = $ent->getType();

                        /**
                         * @var $federation models\Federation
                         */
                        if (!empty($y['federation'])) {
                            try {
                                $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => '' . $y['federation'] . ''));
                            } catch (Exception $e) {
                                log_message('error', __METHOD__ . ' ' . $e);
                                show_error('Internal Server Error', 500);
                                return;
                            }
                        }
                        $fvalidator = null;
                        if (!empty($federation)) {
                            $ispublic = $federation->getPublic();
                            $isactive = $federation->getActive();
                            if ($ispublic && $isactive) {
                                $membership = new models\FederationMembers;
                                $membership->setJoinState('1');
                                $membership->setProvider($ent);
                                $membership->setFederation($federation);
                                $ent->getMembership()->add($membership);
                            } else {
                                log_message('warning', 'Federation is not public, cannot register sp with join fed with name ' . $federation->getName());
                            }
                            /**
                             * @var $fvalidators models\FederationValidator[]
                             */
                            $fvalidators = $federation->getValidators();
                            if(!empty($fvalidators))
                            {
                                foreach($fvalidators as $fv)
                                {
                                    $isenabledForRegister = $fv->isEnabledForRegistration();
                                    if($isenabledForRegister)
                                    {
                                        $fvalidator = $fv;

                                        break;
                                    }
                                }
                            }

                        }
                        $convertedToArray = $ent->convertToArray(True);
                        $this->load->library('providertoxml');
                        $options['attrs'] = 1;
                        $xmlOut = $this->providertoxml->entityConvertNewDocument($ent, $options);
                        $this->load->library('j_ncache');
                        $tmpid = rand(10,1000);
                        log_message('debug','JAGGER RAND: '.$tmpid);
                        $xmloutput = $xmlOut->outputMemory();
                        $this->j_ncache->savePreregisterMetadata($tmpid, $xmloutput);
                        $isFvalidatoryMandatory = false;
                        $externalValidatorPassed = true;
                        if($fvalidator instanceof models\FederationValidator) {
                            $externalValidatorPassed = $this->externalValidation($tmpid,$fvalidator);
                            $isFvalidatoryMandatory = $fvalidator->getMandatory();

                        }

                        if($externalValidatorPassed === true && $isFvalidatoryMandatory === false) {
                            $convertedToArray['metadata'] = base64_encode($xmloutput);

                            log_message('debug', 'GKS convertedToArray: ' . serialize($convertedToArray));

                            if (strcmp($ttype, 'IDP') == 0) {
                                $q->addIDP($convertedToArray);
                                $mailTemplateGroup = 'idpregresquest';
                                $notificationGroup = 'gidpregisterreq';
                            } else {
                                $q->addSP($convertedToArray);
                                $mailTemplateGroup = 'spregresquest';
                                $notificationGroup = 'gspregisterreq';
                            }
                            if (empty($contactMail)) {
                                $contactMail = $this->input->post('f[primarycnt][mail]');
                            }
                            $q->setEmail($contactMail);

                            $q->setToken();
                            $sourceIP = $this->input->ip_address();
                            $messageTemplateParams = array(
                                'requestermail' => $contactMail,
                                'token' => $q->getToken(),
                                'requestersourceip' => $sourceIP,
                                'orgname' => $ent->getName(),
                                'serviceentityid' => $ent->getEntityId(),
                            );
                            if (!empty($u)) {

                                $requsername = $u->getUsername();
                                $reqfullname = $u->getFullname();
                            } else {
                                $requsername = 'anonymous';
                                $reqfullname = '';
                            }
                            $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

                            $messageTemplateArgs = array(
                                'token' => $q->getToken(),
                                'srcip' => $sourceIP,
                                'entorgname' => $ent->getName(),
                                'entityid' => $ent->getEntityId(),
                                'reqemail' => $contactMail,
                                'requsername' => '' . $requsername . '',
                                'reqfullname' => $reqfullname,
                                'datetimeutc' => '' . $nowUtc->format('Y-m-d h:i:s') . ' UTC',
                                'qurl' => '' . base_url() . 'reports/awaiting/detail/' . $q->getToken() . '');


                            $messageTemplate = $this->email_sender->generateLocalizedMail($mailTemplateGroup, $messageTemplateArgs);
                            if (empty($messageTemplate)) {
                                $messageTemplate = $this->email_sender->providerRegRequest($ttype, $messageTemplateParams, NULL);
                            }
                            if (!empty($messageTemplate)) {
                                $this->email_sender->addToMailQueue(array('greqisterreq', $notificationGroup), null, $messageTemplate['subject'], $messageTemplate['body'], array(), FALSE);
                            }


                            $this->em->persist($q);
                            $this->em->detach($ent);
                            try {
                                $this->em->flush();
                                redirect(base_url() . 'manage/entityedit/registersuccess');
                            } catch (Exception $e) {
                                log_message('error', __METHOD__ . ' ' . $e);
                                show_error('Internal Server Error', 500);
                                return;
                            }
                        }
                        else
                        {
                            $this->em->detach($ent);
                        }
                    }
                }
            }
        }
        ////////////////////////////////
        $entsession = $this->getFromDraft($t);
        if (!empty($entsession)) {
            $data['sessform'] = true;
        }
        $data['titlepage'] .= '  - ' . lang('subtl_advancedmode') . '';
        $data['error_messages'] = validation_errors('<div>', '</div>');
        $data['error_messages2'] = $this->tmp_error;
        $this->session->set_flashdata('entformerror', '');
        $this->load->library('providerformelements', array('provider' => $ent, 'session' => $entsession));
        $menutabs[] = array('id' => 'organization', 'value' => '' . lang('taborganization') . '', 'form' => $this->providerformelements->generateGeneral());
        $menutabs[] = array('id' => 'contacts', 'value' => '' . lang('tabcnts') . '', 'form' => $this->form_element->NgenerateContactsForm($ent, $entsession));
        $menutabs[] = array('id' => 'uii', 'value' => '' . lang('tabuii') . '', 'form' => $this->form_element->NgenerateUiiForm($ent, $entsession));
        if (strcasecmp($ent->getType(), 'SP') != 0) {
            $menutabs[] = array('id' => 'uihints', 'value' => '' . lang('tabuihint') . '', 'form' => $this->form_element->generateUIHintForm($ent, $entsession));
        }
        $menutabs[] = array('id' => 'tabsaml', 'value' => '' . lang('tabsaml') . '', 'form' => $this->form_element->NgenerateSAMLTab($ent, $entsession));
        $menutabs[] = array('id' => 'certificates', 'value' => '' . lang('tabcerts') . '', 'form' => $this->form_element->NgenerateCertificatesForm($ent, $entsession));
        if (strcasecmp($ent->getType(), 'IDP') != 0) {
            $menutabs[] = array('id' => 'reqattrs', 'value' => '' . lang('tabreqattrs') . '', 'form' => $this->form_element->nGenerateAttrsReqs($ent, $entsession));
        }
        $data['menutabs'] = $menutabs;
        $data['content_view'] = 'manage/entityedit_view.php';

        $this->load->view('page', $data);
    }

    function registersuccess()
    {

        $data['content_view'] = 'register_success';
        $this->load->view('page', $data);
    }

}
