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
 * Importer Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class Importer extends MY_Controller {

    private $tmp_providers;
    private $tmp_attributes;
    private $tmp_arps;
    private $other_error;
    private $access;

    function __construct() {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->helper(array('cert', 'form'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element'));
        $this->tmp_providers = new models\Providers;
        $this->tmp_attributes = new models\Attributes;
        $this->tmp_arps = new models\AttributeReleasePolicies;
        $this->load->library('zacl');
        $this->access = $this->zacl->check_acl('importer', 'create', '', '');
    }

    /**
     * display form
     */
    function index() 
    {
        $access = $this->access;
        if (!$access)
        {
            $data['content_view'] = "nopermission";
            $data['error'] = 'You dont have permission to import tool';
            $this->load->view('page', $data);
        }
        else 
        {

            $data['title'] = "import metadata";
            $data['content_view'] = "manage/import_metadata_form";
            $data['other_error'] = $this->other_error;
            $data['federations'] = $this->form_element->getFederation();
            $data['types'] = $this->form_element->buildTypeOfEntities();
            $this->load->view('page', $data);
        }
    }

    function submit() {
        $access = $this->access;
        if (!$access) {
            show_error('no access', 403);
        }

        log_message('debug',  "importer submited");
        $data = array();
        if ($this->_submit_validate() === FALSE) {
            return $this->index();
        }
        if ($this->_metadatasigner_validate() === FALSE) {
            return $this->index();
        }
        $arg['type'] = $this->input->post('type');
        $arg['extorint'] = $this->input->post('extorint');
        $arg['active'] = $this->input->post('active');
        $arg['static'] = $this->input->post('static');
        $arg['overwrite'] = $this->input->post('overwrite');
        $arg['federation'] = $this->input->post('federation');
        $arg['metadataurl'] = $this->input->post('metadataurl');
        $arg['validate'] = $this->input->post('validate');
        $arg['certurl'] = trim($this->input->post('certurl'));
        $arg['cert'] = trim($this->input->post('cert'));
        $arg['fullinformation'] = trim($this->input->post('fullinformation'));

        /**
         * @todo  check if you have permission to add entities to this federation
         */
        $tmp = new models\Federations();

        $fed = $tmp->getOneByName($arg['federation']);
        if (empty($fed)) {
            return $this->index();
        }
        $metadata_body = $this->curl->simple_get($arg['metadataurl']);

        if (empty($metadata_body)) {
            $this->other_error = "Metadata location has given empty file";
            return $this->index();
        }


        /**
         * replace below if calling function
         * check if metadata_body if xml and valid against schema
         */
        $this->load->library('metadata_validator');

        $is_valid_metadata = $this->metadata_validator->validateWithSchema($metadata_body);
        if (empty($is_valid_metadata)) {
            $this->other_error = "Metadata is not valid against schema";
            return $this->index();
        }
        if ($arg['extorint'] == 'int') {
            $local = true;
        } else {
            $local = false;
        }
        if ($arg['active'] == 'yes') {
            $active = true;
        } else {
            $active = false;
        }
        if ($arg['static'] == 'yes') {
            $static = true;
        } else {
            $static = false;
        }
        if ($arg['overwrite'] == 'yes') {
            $overwrite = true;
        } else {
            $overwrite = false;
        }
        if ($arg['fullinformation'] == 'yes') {
            $full = true;
        } else {
            $full = false;
        }
        if (!($arg['type'] == 'idp' OR $arg['type'] == 'sp' OR $arg['type'] == 'all')) {
            log_message('error', 'Cannot import metadata because type of entities is not set correctly');
            return $this->index();
        }
        $defaults = array(
            'overwritelocal' => $overwrite,
            'active' => $active,
            'static' => $static,
            'local' => $local,
            'federations' => array($fed->getName())
        );
        foreach ($defaults as $key => $value) {
            if (!is_array($value)) {
                log_message('debug', 'importer: defaults:' . $key . '=' . $value);
            }
        }
        $other = null;
        $type_of_entities = strtoupper($arg['type']);
        $result = $this->metadata2import->import($metadata_body, $type_of_entities, $full, $defaults, $other);
        if ($result) 
        {
            $data['title'] = "Import metadata";
            $data['success_message'] = "Metadata looks to be imported";
            $data['content_view'] = "manage/import_metadata_success_view";
            $this->load->view('page', $data);
        }
        else
        {
            return $this->index();
        }
    }

    /**
     * @todo more validation rules
     */
    private function _submit_validate() {
        $this->form_validation->set_rules('metadataurl', 'Metadata URL', 'trim|required|valid_url');
        $this->form_validation->set_rules('type', 'Type of entities', 'trim|required');
        $this->form_validation->set_rules('extorint', 'Internal/External', 'trim|required');
        $this->form_validation->set_rules('federation', 'Federation', 'trim|required');
        $this->form_validation->set_rules('static', 'Static metadata by default', 'trim|required');
        $this->form_validation->set_rules('active', 'Decide if enabled by default', 'trim|required');
        $this->form_validation->set_rules('overwrite', 'Decide if enabled by default', 'trim|required');
        $this->form_validation->set_rules('fullinformation', 'Populate full information', 'trim|required');
        return $this->form_validation->run();
    }

    /**
     * @todo finish this function  if validate is set then check certbody or cerurl, certbody has higher priority
     */
    private function _metadatasigner_validate() {
        return TRUE;
    }




}
