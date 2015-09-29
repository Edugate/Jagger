<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 * @property Curl $curl
 * @property ProviderUpdater $providerupdater
 * @property Formelement $formelement
 * @property Providerformelements $providerformelements
 * @property Providertoxml $providertoxml
 * @property Email_sender $email_sender
 * @property Xmlvalidator $xmlvalidator
 * @property Metadata2array $metadata2array
 *
 */
class Entityedit extends MY_Controller
{

    protected $tmpProviders;
    protected $tmpError;
    protected $type;
    protected $disallowedParts = array();
    protected $entityid;
    protected $idpssoscope = array();
    protected $aascope = array();
    protected $allowedDigestMethods;
    protected $allowedSignMethods;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('curl', 'formelement', 'form_validation', 'approval', 'providertoxml','j_ncache'));
        $this->tmpProviders = new models\Providers;
        $this->load->helper(array('shortcodes', 'form'));
        $this->tmpError = '';
        $this->type = null;
        $partsdisallowed = $this->config->item('entpartschangesdisallowed');
        if (!empty($partsdisallowed) && is_array($partsdisallowed)) {
            $this->disallowedParts = $this->config->item('entpartschangesdisallowed');
        }
        $this->allowedDigestMethods = j_DigestMethods();
        $this->allowedSignMethods = j_SignatureAlgorithms();
    }

    private function externalValidation($metaid, models\FederationValidator $fedValidator)
    {
        $metadataUrl = base_url() . '/metadata/preregister/' . $metaid . '';
        $method = $fedValidator->getMethod();
        $remoteUrl = $fedValidator->getUrl();
        $entityParam = $fedValidator->getEntityParam();
        $optArgs = $fedValidator->getOptargs();
        if (strcmp($method, 'GET') == 0) {
            $separator = $fedValidator->getSeparator();
            $optArgsStr = '';
            foreach ($optArgs as $k => $v) {
                if ($v === null) {
                    $optArgsStr .= $k . $separator;
                } else {
                    $optArgsStr .= $k . '=' . $v . '' . $separator;
                }
            }
            $optArgsStr .= $entityParam . '=' . urlencode($metadataUrl);
            $remoteUrl .= $optArgsStr;
            $this->curl->create('' . $remoteUrl . '');
        } else {
            $params = $optArgs;
            $params['' . $entityParam . ''] = $metadataUrl;
            $this->curl->create('' . $remoteUrl . '');
            $this->curl->post($params);
        }
        $this->curl->options(array());
        $data = $this->curl->execute();
        if (empty($data)) {
            log_message('warning', __METHOD__ . 'External validator : returned empty result: ' . $this->curl->error_string);
            $this->tmpError .= 'Federation validator : returned empty response, please tray again or contact support';
            return false;
        }
        log_message('debug', __METHOD__ . 'External validator : data received from validator: ' . $data);
        $expectedDocumentType = $fedValidator->getDocutmentType();
        if (strcmp($expectedDocumentType, 'xml') != 0) {
            log_message('warning', 'External validator : Other than xml not supported yet');
            return true;
        } else {
            libxml_use_internal_errors(true);
            $sxe = simplexml_load_string($data);
            if (!$sxe) {
                log_message('warning', __METHOD__ . ' Received invalid xml document from external validator');
                $this->tmpError .= 'Received invalid response from  External validator, please tray again or contact support';
                return false;
            }
            /**
             * @var $docxml \DomDocument
             */
            $docxml = new \DomDocument();
            $docxml->loadXML($data);
            $returncodeElements = $fedValidator->getReturnCodeElement();
            $codeDoms = null;
            if (count($returncodeElements) == 0) {
                log_message('error', 'External validator (' . $fedValidator->getId() . ') is misconfigured - has not defined returned codes');
                return true;
            }
            foreach ($returncodeElements as $v) {
                $codeDoms = $docxml->getElementsByTagName($v);
                if (!empty($codeDoms->length)) {
                    break;
                }
            }
            $codeDomeValue = null;
            if (empty($codeDoms->length)) {
                log_message('warning', __METHOD__ . ' External validator: expected return code element not received from externalvalidaor');
                $this->tmpError .= 'Received invalid response from  External validator, please tray again or contact support';
                return false;
            }
            $codeDomeValue = trim($codeDoms->item(0)->nodeValue);
            if (strlen($codeDomeValue) > 0) {
                log_message('debug', __METHOD__ . ' found expected element with value ' . $codeDomeValue);
            } else {
                log_message('warning', __METHOD__ . ' found expected element but with no value');
                $this->tmpError .= 'Received invalid response from  External validator, please tray again or contact support';
                return false;
            }
            $expectedReturnValues = $fedValidator->getReturnCodeValues();
            $typesOfReturns = array('success' => array(), 'error' => array(), 'warning' => array(), 'critical' => array());
            $mergedReturns = array_merge($typesOfReturns, $expectedReturnValues);
            if (in_array($codeDomeValue, $mergedReturns['success']) || in_array($codeDomeValue, $mergedReturns['warning'])) {
                log_message('info', __METHOD__ . ' returned value found in expected success/warning - passed');
                return true;
            }
            $elementWithMessage = $fedValidator->getMessageCodeElements();
            $result = array();
            foreach (array('error', 'critical') as $e) {
                if (in_array($codeDomeValue, $mergedReturns['' . $e . ''])) {
                    $result['returncode'] = $e;
                    break;
                }
            }


            if (!isset($result['returncode'])) {
                $result['returncode'] = 'unknown';
            }
            $result['message'] = array();
            foreach ($elementWithMessage as $v) {
                log_message('debug', __METHOD__ . ' searching for ' . $v . ' element');
                $o = $docxml->getElementsByTagName($v);
                if ($o->length > 0) {
                    $result['message'][$v] = array();
                    for ($i = 0; $i < $o->length; $i++) {
                        $g = trim($o->item($i)->nodeValue);
                        log_message('debug', __METHOD__ . ' value for ' . $v . ' element: ' . $g);
                        if (!empty($g)) {
                            $result['message'][$v][] = html_escape($g);
                        }
                    }
                }
            }
            if (count($result['message']) == 0) {
                $result['message']['unknown'] = 'no response message';
            }

        }
        $this->tmpError .= 'Validator reponse: ' . $result['returncode'] . '<br />';


        if (isset($result['message']) && is_array($result['message'])) {
            foreach ($result['message'] as $ke => $ee) {
                $this->tmpError .= html_escape($ke) . ':';
                if (is_array($ee)) {
                    foreach ($ee as $pe) {
                        $this->tmpError .= html_escape($pe) . ';';
                    }
                } else {
                    $this->tmpError .= html_escape($ee);
                }
                $this->tmpError .= '<br />';

            }
        }
        return false;


    }

    private function submitValidate($id)
    {
        $register = false;
        if (strcmp($id, 'idp') == 0 || strcmp($id, 'sp') == 0) {
            $register = true;
            $this->type = strtoupper($id);
        }
        $loggedin = $this->jauth->isLoggedIn();
        $optValidationsPassed = true;
        $result = false;
        $y = $this->input->post();
        $staticisdefault = false;
        if (isset($y['f'])) {
            $this->form_validation->set_rules('f[usestatic]', 'use metadata', "valid_static[" . base64_encode($this->input->post('f[static]')) . ":::" . $this->input->post('f[entityid]') . " ]");


            // required if not static is set
            if (isset($y['f']['usestatic']) && $y['f']['usestatic'] === 'accept') {
                $staticisdefault = true;
            }
            if (!$register) {
                if (in_array('entityid', $this->disallowedParts)) {
                    $this->form_validation->set_rules('f[entityid]', lang('rr_entityid'), 'trim|required|valid_urnorurl|min_length[4]|max_length[255]|matches_value[' . $this->entityid . ']');
                } else {
                    $this->form_validation->set_rules('f[entityid]', lang('rr_entityid'), 'trim|required|valid_urnorurl|min_length[4]|max_length[255]|entityid_unique_update[' . $id . ']');
                }
                if (in_array('scope', $this->disallowedParts)) {
                    $this->form_validation->set_rules('f[scopes][idpsso]', lang('rr_scope') . ' (IDPSSODescriptor)', 'trim|valid_scopes|strtolower|max_length[2500]|str_matches_array[' . serialize($this->idpssoscope) . ']');
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
            $this->form_validation->set_rules('f[algs][digest][]', 'DigestMethod', 'trim|in_list[' . implode(",", $this->allowedDigestMethods) . ']');
            $this->form_validation->set_rules('f[algs][signing][]', 'SigningMethod', 'trim|in_list[' . implode(",", $this->allowedSignMethods) . ']');

            if (array_key_exists('lname', $y['f'])) {
                foreach ($y['f']['lname'] as $k => $v) {
                    $this->form_validation->set_rules('f[lname][' . $k . ']', ucfirst(lang('e_orgname')) . ' ' . $k, 'strip_tags|trim');
                }
                if (count(array_filter($y['f']['lname'])) == 0) {
                    $this->tmpError = lang('errnoorgnames');
                    $optValidationsPassed = false;
                }
            } else {
                $this->tmpError = lang('errnoorgnames');
                $optValidationsPassed = false;
            }
            if (array_key_exists('ldisplayname', $y['f'])) {
                foreach ($y['f']['ldisplayname'] as $k => $v) {
                    $this->form_validation->set_rules('f[ldisplayname][' . $k . ']', ucfirst(lang('e_orgdisplayname')) . ' ' . $k, 'strip_tags|trim');
                }
                if (count(array_filter($y['f']['ldisplayname'])) == 0) {
                    $this->tmpError = lang('errnoorgdisnames');
                    $optValidationsPassed = false;
                }
            } else {
                $this->tmpError = lang('errnoorgdisnames');
                $optValidationsPassed = false;
            }

            if (isset($y['f']['uii']['idpsso']['geo']) && is_array($y['f']['uii']['idpsso']['geo'])) {
                foreach ($y['f']['uii']['idpsso']['geo'] as $k => $v) {
                    /**
                     * @todo GEO validation
                     */
                    $this->form_validation->set_rules('f[uii][idpsso][geo][' . $k . ']', lang('rr_geo') . ' ', 'strip_tags|trim|min_length[3]|max_length[40]|valid_latlng');
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
             if (isset($y['f']['uii']['idpsso']['logo']) && is_array($y['f']['uii']['idpsso']['logo'])) {
                foreach ($y['f']['uii']['idpsso']['logo'] as $k => $v) {
                    $this->form_validation->set_rules('f[uii][idpsso][logo][' . $k . '][url]', 'Logo', 'trim|required|validimageorurl');
                }
            }

            if (array_key_exists('lhelpdesk', $y['f'])) {
                foreach ($y['f']['lhelpdesk'] as $k => $v) {
                    $this->form_validation->set_rules('f[lhelpdesk][' . $k . ']', lang('e_orgurl') . ' ', 'strip_tags|trim|valid_url');
                }
                $y['f']['lhelpdesk'] = array_filter($y['f']['lhelpdesk']);
                if (count($y['f']['lhelpdesk']) == 0) {
                    $this->tmpError = lang('errnoorgurls');
                    $optValidationsPassed = false;
                }
            } else {
                $this->tmpError = lang('errnoorgurls');
                $optValidationsPassed = false;
            }


            if (array_key_exists('contact', $y['f'])) {
                foreach ($y['f']['contact'] as $k => $v) {
                    $this->form_validation->set_rules('f[contact][' . $k . '][email]', '' . lang('rr_contactemail') . '', 'trim|valid_email');
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
            $grantEncrMeths = j_KeyEncryptionAlgorithms();
            $grantEncrMethsList = implode(",", $grantEncrMeths);
            $certGroups = array(
                'spsso' => 'SPSSODescriptor',
                'idpsso' => 'IDPSSODescriptor',
                'aa' => 'AttributeAuthorityDescriptor'
            );
            if (array_key_exists('crt', $y['f'])) {
                foreach ($certGroups as $key => $val) {
                    if (array_key_exists($key, $y['f']['crt'])) {
                        foreach ($y['f']['crt']['' . $key . ''] as $k => $v) {
                            if (ctype_digit($k)) {
                                $this->form_validation->set_rules('f[crt][' . $key . '][' . $k . '][certdata]', $val . '/Certificate body', 'trim|required|getPEM|verify_cert_nokeysize');
                            } else {
                                $this->form_validation->set_rules('f[crt][' . $key . '][' . $k . '][certdata]', $val . '/Certificate body', 'trim|required|getPEM|verify_cert');
                            }
                            $this->form_validation->set_rules('f[crt][' . $key . '][' . $k . '][usage]', '' . lang('rr_certificateuse') . '', 'htmlspecialchars|trim|required');
                            $this->form_validation->set_rules('f[crt][' . $key . '][' . $k . '][encmethods][]', 'Certificate EncryptionMethod', 'trim|in_list[' . $grantEncrMethsList . ']');


                        }
                    }
                }
            }
            /**
             * service locations
             */
            $idpssoSrvsLocations = 0;

            $aaSrvsLocations = 0;
            $nossobindings = array();
            $noidpslo = array();
            if (array_key_exists('srv', $y['f'])) {
                if (array_key_exists('IDPAttributeService', $y['f']['srv'])) {
                    foreach ($y['f']['srv']['IDPAttributeService'] as $k => $v) {
                        $this->form_validation->set_rules('f[srv][IDPAttributeService][' . $k . '][url]', 'AttributeAuthorityDescriptor/AttributeService: ' . html_escape($y['f']['srv']['IDPAttributeService']['' . $k . '']['bind']), 'strip_tags|trim|max_length[254]|valid_url');
                        if (!empty($y['f']['srv']['IDPAttributeService']['' . $k . '']['url'])) {
                            ++$aaSrvsLocations;
                        }
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
                        ++$idpssoSrvsLocations;
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
                        $this->form_validation->set_rules('f[srv][AssertionConsumerService][' . $k . '][order]', 'AssertionConsumerService index', 'trim|required|numeric');

                        $tmpurl = trim($y['f']['srv']['AssertionConsumerService']['' . $k . '']['url']);
                        if (!empty($tmpurl)) {
                            if (!empty($v['order'])) {
                                $acsindexes[] = $v['order'];
                            }
                            $acsurls[] = 1;
                            if (array_key_exists('default', $y['f']['srv']['AssertionConsumerService']['' . $k . ''])) {
                                $acsdefault[] = 1;
                            }
                        }
                    }
                    if ($this->type != 'IDP') {
                        if (count($acsindexes) != count(array_unique($acsindexes))) {

                            $this->tmpError = 'Not unique indexes found for ACS';
                            $optValidationsPassed = false;
                        }
                        if (count($acsurls) < 1 && empty($staticisdefault)) {

                            $this->tmpError = lang('rr_acsurlatleastone');
                            $optValidationsPassed = false;
                        }
                        if (count($acsdefault) > 1) {

                            $this->tmpError = lang('rr_acsurlonlyonedefault');
                            $optValidationsPassed = false;
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
                            if (!empty($tmporder) && !ctype_digit($tmporder)) {
                                $this->tmpError = 'One of the index order in SP ArtifactResolutionService is not numeric';
                                $optValidationsPassed = false;
                            }
                        }
                    }
                    if (strcasecmp($this->type, 'IDP') != 0) {
                        if (count($spartindexes) != count(array_unique($spartindexes))) {

                            $this->tmpError = 'Not unique indexes found for SP ArtifactResolutionService';
                            $optValidationsPassed = false;
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
                            if (!empty($tmporder) && !ctype_digit($tmporder)) {
                                $this->tmpError = 'One of the index order in IDP ArtifactResolutionService is not numeric';
                                $optValidationsPassed = false;
                            }
                        }
                    }
                    if ($this->type != 'SP') {
                        if (count($idpartindexes) != count(array_unique($idpartindexes))) {

                            $this->tmpError = 'Not unique indexes found for IDP ArtifactResolutionService';
                            $optValidationsPassed = false;
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
                            if (!empty($tmporder) && !ctype_digit($tmporder)) {
                                $this->tmpError = 'One of the index order in DiscoveryResponse is not numeric';
                                $optValidationsPassed = false;
                            }
                        }
                    }
                    if (strcasecmp($this->type, 'IDP') != 0) {
                        if (count($drindexes) != count(array_unique($drindexes))) {
                            $this->tmpError = 'Not unique indexes found for DiscoveryResponse';
                            $optValidationsPassed = false;
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

                if (empty($idpssoSrvsLocations) && empty($aaSrvsLocations) && !$staticisdefault) {
                    $this->tmpError = lang('errmissssoaasrvs');
                    return false;
                }
                if (!empty($nossobindings) && is_array($nossobindings) && count($nossobindings) > 0 && count(array_unique($nossobindings)) < count($nossobindings)) {
                    $this->tmpError = 'duplicate binding protocols for SSO found in sent form';
                    $optValidationsPassed = false;
                }
                if (!empty($noidpslo) && is_array($noidpslo) && count($noidpslo) > 0 && count(array_unique($noidpslo)) < count($noidpslo)) {
                    $this->tmpError = 'duplicate binding protocols for IDP SLO found in sent form';
                    $optValidationsPassed = false;
                }
                if (!empty($nospslo) && is_array($nospslo) && count($nospslo) > 0 && count(array_unique($nospslo)) < count($nospslo)) {
                    $this->tmpError = 'duplicate binding protocols for SP SLO found in sent form';
                    $optValidationsPassed = false;
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
        return true;
    }
    private function notifyOnChange(\models\Provider $ent)
    {

        $tracker = null;
        $unitInsertCollection = $this->em->getUnitOfWork()->getScheduledEntityInsertions();
        foreach($unitInsertCollection as $objToInsert)
        {
            if($objToInsert instanceof models\Tracker)
            {

                if($objToInsert->getResourceType() === 'ent' && $objToInsert->getSubType() === 'modification')
                {
                    $tracker = $objToInsert;
                    break;
                }
            }
        }
        if(!empty($tracker))
        {
            $this->email_sender->providerIsModified($ent,$tracker);
        }
    }

    public function show($id)
    {
        if(!ctype_digit($id))
        {
            show_404();
        }
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        try {
            $this->load->library('zacl');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }


        $data = array(
            'error_messages2' => &$this->tmpError,
        );
        /**
         * @var $ent models\Provider
         */
        $ent = $this->tmpProviders->getOneById($id);
        if (empty($ent)) {
            show_error('Provider not found', '404');
        }
        $isLocked = $ent->getLocked();
        $isLocal = $ent->getLocal();
        if (!$isLocal) {
            show_error('Access Denied. Identity/Service Provider is not localy managed.', 403);
        }
        if ($isLocked) {
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
            redirect(base_url('providers/detail/show/' . $id . ''), 'location');
        } elseif ($this->submitValidate($id) === true) {
            $y = $this->input->post('f');
            $submittype = $this->input->post('modify');
            $this->saveToDraft($id, $y);
            if ($submittype === 'modify') {
                $this->load->library('providerupdater');
                $c = $this->getFromDraft($id);
                if (!empty($c) && is_array($c)) {

                    $updateresult = $this->providerupdater->updateProvider($ent, $c);
                    if ($updateresult) {
                        $this->em->persist($ent);

                        $this->notifyOnChange($ent);

                        try {
                            $this->em->flush();
                            $this->discardDraft($id);
                            $this->j_ncache->cleanMcirclceMeta($id);
                            $this->j_ncache->cleanEntityStatus($id);
                            $showsuccess = true;
                        }
                        catch(Exception $exception)
                        {
                            log_message('error',__METHOD__.' '.$exception);
                            show_error('Internal server error',500);
                        }
                    }
                }
            }
        }
        $entsession = $this->getFromDraft($id);
        if (!empty($entsession)) {
            $data['sessform'] = true;
        }

        $data['y'] = $entsession;
        $myLang = MY_Controller::getLang();

        $providerNameInLang = $ent->getNameToWebInLang($myLang, $ent->getType());
        $this->title = $providerNameInLang . ' :: ' . lang('title_provideredit');

        /**
         * @todo check locked
         */
        $data['entdetail'] = array('displayname' => $providerNameInLang, 'name' => $ent->getName(), 'id' => $ent->getId(), 'entityid' => $ent->getEntityId(), 'type' => $ent->getType());

        if (!empty($showsuccess)) {
            $data['success_message'] = lang('updated');
            $data['content_view'] = 'manage/entityedit_success_view';
            return $this->load->view('page', $data);

        }
        /**
         * menutabs array('id'=>xx,'v')
         */

        $this->load->library('providerformelements', array('provider' => $ent, 'session' => $entsession));


        $data['menutabs'] = $this->genTabs($ent, $entsession, false);


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
        return (!empty($fromSimpleMode) && strcmp($fromSimpleMode, 'advanced') == 0);

    }

    public function register($t = null)
    {
        MY_Controller::$menuactive = 'reg';

        $data = array(
            'registerForm' => true,
            'error_messages2' => &$this->tmpError
        );
        $data['jsAddittionalFiles'][] = 'https://maps.googleapis.com/maps/api/js?v=3.exp&signed_in=true&libraries=places';

        $t = trim($t);
        if (empty($t) || !(strcmp($t, 'idp') == 0 || strcmp($t, 'sp') == 0)) {
            show_error('Not found', 404);
        }
        $ent = new models\Provider;
        $ent->setLocal(true);
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
        /**
         * @var $u models\User
         */
        $data['anonymous'] = true;
        if ($this->jauth->isLoggedIn()) {
            $data['anonymous'] = FALSE;
            $currentusername = $this->jauth->getLoggedinUsername();
            $u = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $currentusername . ''));
            $data['loggeduser'] = array(
                'username' => '' . $currentusername . '',
                'fullname' => '' . $u->getFullname() . '',
                'fname' => '' . $u->getGivenname() . '',
                'lname' => '' . $u->getSurname() . '',
                'email' => '' . $u->getEmail() . '',
            );
        }

        /**
         * @var $fedCollection models\Federation[]
         */
        $fedCollection = $this->em->getRepository("models\Federation")->findBy(array('is_public' => true, 'is_active' => true));
        if (count($fedCollection) > 0) {
            $data['federations'] = array();
            /**
             *  generate dropdown list of public federations
             */
            $data['federations']['none'] = lang('noneatthemoment');
            foreach ($fedCollection as $key) {
                $keyName = $key->getName();
                $data['federations'][''.$keyName.''] = $keyName;
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
                /**
                 * @var $metadataDOM \DOMDocument
                 */

                $metadataDOM = new \DOMDocument();
                $metadataDOM->strictErrorChecking = FALSE;
                $metadataDOM->WarningChecking = FALSE;
                $metadataDOM->loadXML($metadatabody);

                $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
                if (!$isValid) {
                    log_message('warning', __METHOD__ . ' invalid metadata had been pasted in registration form');
                    $this->tmpError = lang('err_pastedtxtnotvalidmeta');
                } else {
                    $this->discardDraft($t);
                    $this->load->library('metadata2array');
                    $xpath = new DomXPath($metadataDOM);
                    $namespaces = h_metadataNamespaces();
                    foreach ($namespaces as $key => $value) {
                        $xpath->registerNamespace($key, $value);
                    }


                    /**
                     * @var $domlist DOMNodeList[]
                     */
                    $domlist = $metadataDOM->getElementsByTagName('EntityDescriptor');
                    if (count($domlist) == 1) {
                        foreach ($domlist as $domelement) {
                            $entarray = $this->metadata2array->entityDOMToArray($domelement, true);
                        }
                        $o = current($entarray);
                        if (isset($o['type']) && strcasecmp($o['type'], $t) == 0) {
                            $ent->setProviderFromArray($o);
                            if (isset($o['details']['reqattrs'])) {
                                /**
                                 * @var $attrsDefinitions models\Attribute[]
                                 */
                                $attrsDefinitions = $this->em->getRepository("models\Attribute")->findAll();
                                $attributes = array();
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
                            $this->tmpError = lang('regcantimporttype');
                        }
                    }
                }
            }
        } elseif ($this->input->post('discard')) {
            $this->discardDraft($t);
            redirect(base_url() . 'providers/' . strtolower($t) . '_registration', 'location');
        } elseif ($this->submitValidate($t) === true) {
            $y = $this->input->post('f');
            $submittype = $this->input->post('modify');
            if ($submittype === 'modify') {
                \log_message('debug', __METHOD__ . 'submittype=modify');
                $this->load->library('providerupdater');
                $c = $this->getFromDraft($t);
                if (is_array($c)) {

                    \log_message('debug', __METHOD__ . ' GKS data from draft: ' . serialize($c));
                    $ent = $this->providerupdater->updateProvider($ent, $c);

                    if ($ent) {
                        $registrationAutority = $this->config->item('registrationAutority');
                        if (!empty($registrationAutority)) {
                            $ent->setRegistrationAuthority(trim($registrationAutority));
                            $dateNow = new \DateTime("now");
                            $ent->setRegistrationDate($dateNow);

                        }
                        $ent->setActive(true);
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
                                $membership->setJoinstate('1');
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
                            if (!empty($fvalidators)) {
                                foreach ($fvalidators as $fv) {
                                    $isenabledForRegister = $fv->isEnabledForRegistration();
                                    if ($isenabledForRegister) {
                                        $fvalidator = $fv;

                                        break;
                                    }
                                }
                            }

                        }
                        $convertedToArray = $ent->convertToArray(true);
                        $this->load->library('providertoxml');
                        $options['attrs'] = 1;
                        $xmlOut = $this->providertoxml->entityConvertNewDocument($ent, $options);
                        $tmpid = rand(10, 1000);
                        log_message('debug', 'JAGGER RAND: ' . $tmpid);
                        $xmloutput = $xmlOut->outputMemory();
                        $this->j_ncache->savePreregisterMetadata($tmpid, $xmloutput);
                        $isFvalidatoryMandatory = false;
                        $externalValidatorPassed = true;
                        if ($fvalidator instanceof models\FederationValidator) {
                            $externalValidatorPassed = $this->externalValidation($tmpid, $fvalidator);
                            $isFvalidatoryMandatory = $fvalidator->getMandatory();

                        }
                        log_message('debug', 'JAGGER: externalValidatorPassed=' . (int)$externalValidatorPassed . ', isFvalidatoryMandatory:' . (int)$isFvalidatoryMandatory);

                        if ($externalValidatorPassed === true || $isFvalidatoryMandatory === false) {
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
                                $messageTemplate = $this->email_sender->providerRegRequest($ttype, $messageTemplateParams);
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
                        } else {
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
        $this->load->library('providerformelements', array('provider' => $ent, 'session' => $entsession));

        $data['menutabs'] = $this->genTabs($ent, $entsession, true);
        $data['content_view'] = 'manage/entityedit_view.php';
        $this->load->view('page', $data);
    }

    private function genTabs(\models\Provider $ent, $entsession, $register = false)
    {
        $tabs = array(
            array('id' => 'organization', 'value' => '' . lang('taborganization') . '', 'form' => $this->providerformelements->generateGeneral()),
            array('id' => 'contacts', 'value' => '' . lang('tabcnts') . '', 'form' => $this->formelement->NgenerateContactsForm($ent, $entsession)),
            array('id' => 'uii', 'value' => '' . lang('tabuii') . '', 'form' => $this->formelement->NgenerateUiiForm($ent, $entsession)),

        );
        if (strcasecmp($ent->getType(), 'SP') != 0) {
            $tabs[] = array('id' => 'uihints', 'value' => '' . lang('tabuihint') . '', 'form' => $this->formelement->generateUIHintForm($ent, $entsession));
        }
        $tabs[] = array('id' => 'tabsaml', 'value' => '' . lang('tabsaml') . '', 'form' => $this->formelement->NgenerateSAMLTab($ent, $entsession));
        $tabs[] = array('id' => 'certificates', 'value' => '' . lang('tabcerts') . '', 'form' => $this->formelement->NgenerateCertificatesForm($ent, $entsession));
        if ($register) {

            if (strcasecmp($ent->getType(), 'IDP') != 0) {
                $tabs[] = array('id' => 'reqattrs', 'value' => '' . lang('tabreqattrs') . '', 'form' => $this->formelement->nGenerateAttrsReqs($ent, $entsession));
            }
        } else {

            $tabs[] = array('id' => 'entcategories', 'value' => '' . lang('tabentcategories') . '', 'form' => $this->formelement->NgenerateEntityCategoriesForm($ent, $entsession));
            if (strcasecmp($this->type, 'IDP') != 0) {
                $tabs[] = array('id' => 'reqattrs', 'value' => '' . lang('tabreqattrs') . '', 'form' => $this->formelement->nGenerateAttrsReqs($ent, $entsession));
            }
            $tabs[] = array('id' => 'staticmetadata', 'value' => '' . lang('tabstaticmeta') . '', 'form' => $this->formelement->NgenerateStaticMetadataForm($ent, $entsession));

        }

        return $tabs;
    }

    function registersuccess()
    {

        $data['content_view'] = 'register_success';
        $this->load->view('page', $data);
    }

}
