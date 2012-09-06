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
 * Sp_registration Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Sp_registration extends MY_Controller {

    private $tmp_providers;
    private $tmp_federations;

    function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->helper('url');
        $this->tmp_providers = new models\Providers;
        $this->tmp_federations = new models\Federations;
        $this->lang->load('rrhelp_lang', $this->current_language);
    }

    function index() {
        log_message('debug', $this->mid . 'SP registration form opened');
        $this->title = "Service Provider Registration";

        /**
         * @todo add select homerorg orn partner optional 
         */
        /**
         * get federations list
         */
        $fedCollection = $this->tmp_federations->getFederations();
        if (!empty($fedCollection)) {
            $federations[''] = 'Select one ...';
            foreach ($fedCollection as $f) {
                if (!$f->getActive()) {
                    $federations[$f->getId()] = $f->getName() . ' (inactive)';
                } else {
                    $federations[$f->getId()] = $f->getName();
                }
            }
            $federations['none'] = "None at the moment";
        } else {
            $federations[''] = 'not found';
            $federations['none'] = "None at the moment";
        }
        $data['federations'] = $federations;
        $data['acs_dropdown'][''] = 'Select one...';
        $data['acs_dropdown'] = array_merge($data['acs_dropdown'], $this->config->item('acs_binding'));


        $data['content_view'] = 'sp/sp_registration_form_view';
        $this->load->view('page', $data);
    }

    public function submit() {
        if ($this->_submit_validate() === FALSE) {
            return $this->index();
        }

        $fed_id = $this->input->post('federation');
        $federation = $this->tmp_federations->getOneFederationById((int) $fed_id);
        $contact_name = $this->input->post('contact_name');
        $contact_phone = $this->input->post('contact_phone');
        $contact_mail = $this->input->post('contact_mail');


        $helpdesk_url = $this->input->post('helpdesk_url');
        $entityid = $this->input->post('entityid');
        $resource = $this->input->post('resource');


        $new_sp = new models\Provider;
        $new_sp->setName($resource);
        $new_sp->setDisplayName($resource);
        $new_sp->setEntityId($entityid);
        $new_sp->setAsSP();
        $new_sp->setDefaultState();
        $new_sp->setHelpdeskUrl($helpdesk_url);
        if (!empty($federation)) {
            $new_sp->setFederation($federation);
        }

        $contact = new models\Contact;
        $contact->setFullName($contact_name);
        $contact->setEmail($contact_mail);
        $contact->setPhone($contact_phone);
        $contact->setType('administrative');

        $new_sp->setContact($contact);

        $encrypt_cert_body = $this->input->post('encrypt_cert_body');
        $sign_cert_body = $this->input->post('sign_cert_body');

        if (!empty($encrypt_cert_body)) {
            $crt_enc = new models\Certificate;
            $crt_enc->setCertUse('encryption');
            $crt_enc->setAsSSO();
            $crt_enc->setCertType('x509');
            $crt_enc->setCertData($encrypt_cert_body);
            $crt_enc->setAsDefault();
            $crt_enc->generateFingerprint();
            //	$crt_enc->setProvider($new_sp);
            $new_sp->setCertificate($crt_enc);
        }

        if (!empty($sign_cert_body)) {
            $crt_sign = new models\Certificate;
            $crt_sign->setCertUse('signing');
            $crt_sign->setAsSSO();
            $crt_sign->setCertType('x509');
            $crt_sign->setCertData($sign_cert_body);
            $crt_sign->setAsDefault();
            $crt_sign->generateFingerprint();
            //	$crt_sign->setProvider($new_sp);
            $new_sp->setCertificate($crt_sign);
        }

        $acs_url = $this->input->post('acs_url');
        $acs_bind = $this->input->post('acs_bind');
        $acs_order = $this->input->post('acs_order');

        $acs = new models\ServiceLocation;
        $acs->setUrl($acs_url);
        $acs->setDefault(TRUE);
        $acs->setOrder($acs_order);
        $acs->setAsACS();
        $acs->setBindingName($acs_bind);

        $new_sp->setServiceLocation($acs);


        $queue = new models\Queue;
        $loggedin_user = null;
        if(!empty($_SESSION['username']))
        {
             $loggedin_user = $_SESSION['username'];
        }

        if (!empty($loggedin_user)) {
            $creator = $this->em->getRepository("models\User")->findOneBy(array('username' => $loggedin_user));
        }
        if (!empty($creator)) {
            $queue->setCreator($creator);
        }
       
        $queue->setAction('Create');
        $queue->setName($new_sp->getName());
        $queue->addSP($new_sp->convertToArray());
        $queue->setEmail($this->input->post('contact_mail'));
        $queue->setToken();

        $this->em->persist($queue);
        $this->em->flush();
            /**
             * send email
             */
            $recipients = array();
            $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name'=>'Administrator'));
            $a_members = $a->getMembers();
            foreach($a_members as $m)
            {
                $recipients[] = $m->getEmail();
            }
            $sbj = "SP registration request";
            $body = "Dear Administrator\r\n";
            $body .= "".$queue->getEmail()." just filled SP Registration form\r\n";
            $body .="entityID: ".$entityid."\r\n";
            $body .="You can approve or reject it on ".base_url()."reports/awaiting/detail/".$queue->getToken()."\r\n";
            $this->load->library('email_sender');
            $this->email_sender->send($recipients,$sbj,$body);

        $data['content_view'] = 'sp/sp_registration_success';
        $this->load->view('page', $data);
    }

    private function _submit_validate() {
        log_message('debug', $this->mid . 'validating form initialized');

        $this->form_validation->set_rules('resource', 'Resource name', 'required|min_length[3]|max_length[64]');
        $this->form_validation->set_rules('entityid', 'EntityID', 'required|min_length[3]|max_length[256]|entity_unique[entityid]');
        $this->form_validation->set_rules('contact_name', 'Contact name', 'required|min_length[3]|max_length[25]');
        $this->form_validation->set_rules('contact_mail', 'Contact mail', 'required|min_length[3]|max_length[128]|valid_email');
        $this->form_validation->set_rules('contact_phone', 'Contact phone', 'numeric');
        $this->form_validation->set_rules('acs_url', 'AccertionConsumerService URL', 'required|valid_url[acs_url]');
        $this->form_validation->set_rules('acs_bind', 'AccertionConsumerService Binding', 'required');
        $this->form_validation->set_rules('acs_order', 'AccertionConsumerService index', 'required|numeric');
        $this->form_validation->set_rules('contact_phone', 'Contact phone', 'min_length[6]|max_length[21]');
        $this->form_validation->set_rules('helpdesk_url', 'Helpdesk URL', 'required|min_length[6]|max_length[255]');
        $this->form_validation->set_rules('encrypt_cert_body', 'Certificate for encryption', 'trim|verify_cert[encrypt_cert_body]');
        $this->form_validation->set_rules('sign_cert_body', 'Certificate for signing', 'trim|verify_cert[sign_cert_body]');

        return $this->form_validation->run();
    }

}
