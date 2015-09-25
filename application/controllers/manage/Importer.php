<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * ResourceRegistry3
 *
 * @package   RR3
 * @author    Middleware Team HEAnet
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Importer Class
 *
 * @package RR3
 * @author  Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Importer extends MY_Controller
{
    protected $otherErrors = array();
    protected $access = false;
    protected $xmlbody;
    protected $curlMaxsize, $curlTimeout;
    protected $xmlDOM;

    public function __construct()
    {
        parent::__construct();
        $this->curlMaxsize = $this->config->item('curl_metadata_maxsize');
        if ($this->curlMaxsize === null) {
            $this->curlMaxsize = 20000;
        }
        $this->curlTimeout = $this->config->item('curl_timeout');
        if ($this->curlTimeout === null) {
            $this->curlTimeout = 60;
        }
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        } else {
            $this->load->helper(array('cert', 'form'));
            $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element', 'xmlvalidator', 'zacl'));
            $this->access = $this->zacl->check_acl('importer', 'create', '', '');
        }
    }

    /**
     * display form
     */
    public function index()
    {
        if ($this->access !== true) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('error403');
        } else {
            $data = array(
                'titlepage' => lang('titleimportmeta'),
                'content_view' => 'manage/import_metadata_form',
                'other_error' => $this->otherErrors,
                'global_erros' => $this->globalerrors,
                'federations' => $this->form_element->getFederation(),
                'types' => array('' => lang('rr_pleaseselect'), 'idp' => lang('identityproviders'), 'sp' => lang('serviceproviders'), 'all' => lang('allentities')),
            );
        }
        $this->load->view('page', $data);
    }

    public function submit()
    {
        $this->globalerrors = array();
        $this->otherErrors = array();
        if ($this->access !== true) {
            show_error('no access', 403);
        }

        log_message('debug', 'importer submited');
        $data = array();
        if ($this->_submit_validate() !== true) {
            return $this->index();
        }

        $arg = array(
            'metadataurl' => $this->input->post('metadataurl'),
            'certurl' => $this->input->post('certurl'),
            'cert' => getPEM($this->input->post('cert')),
            'validate' => $this->input->post('validate'),
            'sslcheck' => $this->input->post('sslcheck'),
            'type' => $this->input->post('type'),
            'extorint' => $this->input->post('extorint'),
            'active' => $this->input->post('active'),
            'static' => $this->input->post('static'),
            'overwrite' => $this->input->post('overwrite'),
            'federation' => $this->input->post('federation'),
            'fullinformation' => $this->input->post('fullinformation')
        );


        $sslvalidate = true;
        if ($arg['sslcheck'] === 'ignore') {
            $sslvalidate = false;
        }

        if ($arg['validate'] === 'accept') {
            $mvalidate = true;
            if (!empty($arg['cert'])) {
                $mcerturl = false;
                $mcert = $arg['cert'];
            } elseif (!empty($arg['certurl'])) {
                $mcerturl = $arg['certurl'];
                $mcert = false;
            } else {
                $this->otherErrors[] = lang('certsignerurlbodymissing');
                return $this->index();
            }
        } else {
            $mvalidate = false;
            $mcerturl = false;
            $mcert = false;
        }

        if ($this->_metadatasigner_validate($arg['metadataurl'], $sslvalidate, $mvalidate, $mcerturl, $mcert) !== TRUE) {
            return $this->index();
        }


        /**
         * @todo  check if you have permission to add entities to this federation
         */
        $tmp = new models\Federations();

        /**
         * @var $fed models\Federation
         */
        $fed = $tmp->getOneByName($arg['federation']);
        if ($fed === null) {
            $this->otherErrors[] = 'No permission to add entities to selected federation';
            return $this->index();
        }


        /**
         * replace below if calling function
         * check if metadata_body if xml and valid against schema
         */
        $local = false;
        $active = false;
        $static = false;
        $overwrite = false;
        $full = false;
        if ($arg['extorint'] === 'int') {
            $local = true;
        }
        if ($arg['active'] === 'yes') {
            $active = true;
        }
        if ($arg['static'] === 'yes') {
            $static = true;
        }
        if ($arg['overwrite'] === 'yes') {
            $overwrite = true;
        }
        if ($arg['fullinformation'] === 'yes') {
            $full = true;
        }
        if (!($arg['type'] === 'idp' || $arg['type'] === 'sp' || $arg['type'] === 'all')) {
            log_message('error', 'Cannot import metadata because type of entities is not set correctly');
            return $this->index();
        }
        $defaults = array(
            'overwritelocal' => $overwrite,
            'active' => $active,
            'static' => $static,
            'local' => $local,
            'localimport' => true,
            'federationid' => $fed->getId(),
        );
        foreach ($defaults as $key => $value) {
            if (!is_array($value)) {
                log_message('debug', 'importer: defaults:' . $key . '=' . $value);
            }
        }
        $other = null;
        $type_of_entities = strtoupper($arg['type']);
        $result = $this->metadata2import->import($this->xmlDOM, $type_of_entities, $full, $defaults, $other);
        if ($result) {
            $this->j_ncache->cleanProvidersList('idp');
            $this->j_ncache->cleanProvidersList('sp');
            $this->j_ncache->cleanFederationMembers($fed->getId());
            $data['title'] = lang('titleimportmeta');
            $data['success_message'] = lang('okmetaimported');
            if (array_key_exists('metadataimportmessage', $this->globalnotices) && is_array($this->globalnotices['metadataimportmessage'])) {
                $data['success_details'] = $this->globalnotices['metadataimportmessage'];
            }
            $data['content_view'] = 'manage/import_metadata_success_view';
            return $this->load->view('page', $data);
        } else {
            return $this->index();
        }
    }

    /**
     * @todo more validation rules
     */
    private function _submit_validate()
    {
        $this->form_validation->set_rules('metadataurl', 'Metadata URL', 'trim|required|valid_url');
        $this->form_validation->set_rules('sslcheck', 'SSL check', 'trim');
        $this->form_validation->set_rules('validate', 'verify', 'trim');
        $this->form_validation->set_rules('cert', 'cert verify', 'trim|verify_cert');
        $this->form_validation->set_rules('certurl', 'cert url', 'trim|valid_url');
        $this->form_validation->set_rules('type', lang('typeofents'), 'trim|required');
        $this->form_validation->set_rules('extorint', 'Internal/External', 'trim|required');
        $this->form_validation->set_rules('federation', lang('rr_federation'), 'trim|required');
        $this->form_validation->set_rules('static', 'Static metadata by default', 'trim|required');
        $this->form_validation->set_rules('active', 'Decide if enabled by default', 'trim|required');
        $this->form_validation->set_rules('overwrite', 'Decide if enabled by default', 'trim|required');
        $this->form_validation->set_rules('fullinformation', 'Populate full information', 'trim|required');
        return $this->form_validation->run();
    }

    /**
     * @todo finish this function  if validate is set then check certbody or cerurl, certbody has higher priority
     */
    private function _metadatasigner_validate($metadataurl, $sslvalidate = FALSE, $signed = FALSE, $certurl = FALSE, $certbody = FALSE)
    {
        $maxsize = $this->curlMaxsize;
        $sslverifyhost = 0;
        if ($sslvalidate) {
            $sslverifyhost = 2;
        }
        $curloptions = array(
            CURLOPT_SSL_VERIFYPEER => $sslvalidate,
            CURLOPT_SSL_VERIFYHOST => $sslverifyhost,
            CURLOPT_TIMEOUT => $this->curlTimeout,
            CURLOPT_BUFFERSIZE => 128,
            CURLOPT_NOPROGRESS => FALSE,
            CURLOPT_PROGRESSFUNCTION => function ($DownloadSize, $Downloaded, $UploadSize, $Uploaded) use ($maxsize) {
                return ($Downloaded > ($maxsize * 1024)) ? 1 : 0;
            }
        );
        $this->xmlbody = $this->curl->simple_get('' . $metadataurl . '', array(), $curloptions);
        if (empty($this->xmlbody)) {
            $this->otherErrors[] = $this->curl->error_string;
            return FALSE;
        }
        libxml_use_internal_errors(true);
        $this->xmlDOM = new \DOMDocument();
        $this->xmlDOM->strictErrorChecking = FALSE;
        $this->xmlDOM->WarningChecking = FALSE;
        $this->xmlDOM->loadXML($this->xmlbody);
        log_message('debug', __METHOD__ . ' metadata xml loaded into DOMDocument - elements: ' . $this->xmlDOM->childNodes->length);
        $valid_metadata = FALSE;
        if ($signed === FALSE) {
            $valid_metadata = $this->xmlvalidator->validateMetadata($this->xmlDOM, FALSE, FALSE);
            return $valid_metadata;
        }

        if (!empty($certbody)) {
            if (validateX509($certbody)) {
                $valid_metadata = $this->xmlvalidator->validateMetadata($this->xmlDOM, TRUE, $certbody);
            } else {
                $this->otherErrors[] = lang('einvalidcertsignerdata');
                return FALSE;
            }
        } elseif (!empty($certurl)) {
            $certdata = $this->curl->simple_get('' . $certurl . '', array(), array(
                CURLOPT_SSL_VERIFYPEER => $sslvalidate,
                CURLOPT_SSL_VERIFYHOST => $sslverifyhost,
                CURLOPT_TIMEOUT => $this->curlTimeout,
                CURLOPT_BUFFERSIZE => 128,
                CURLOPT_NOPROGRESS => FALSE,
                CURLOPT_PROGRESSFUNCTION => function ($DownloadSize, $Downloaded, $UploadSize, $Uploaded) {
                    return ($Downloaded > (1000 * 1024)) ? 1 : 0;
                }
            ));

            if (!empty($certdata) && validateX509($certdata)) {
                $valid_metadata = $this->xmlvalidator->validateMetadata($this->xmlDOM, TRUE, $certdata);
            } else {
                $this->otherErrors[] = lang('einvalidcertsignerurl');
                return FALSE;
            }
        }
        return $valid_metadata;
    }

}
