<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet Ltd.
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Sp_registration Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Sp_registration extends MY_Controller
{

    private $tmp_providers;
    private $tmp_federations;

    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->title = lang('title_spreg');
        $this->load->library('form_validation');
        $this->tmp_providers = new models\Providers;
        $this->tmp_federations = new models\Federations;
    }

    public function index()
    {
        if ($this->_submit_validate() === TRUE) {

            $fedid = $this->input->post('federation');
            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
            $contact_name = $this->input->post('contact_name');
            $contact_phone = $this->input->post('contact_phone');
            $contact_mail = $this->input->post('contact_mail');
            $helpdeskurl = $this->input->post('helpdeskurl');
            $homeurl = $this->input->post('homeurl');
            $entityid = $this->input->post('entityid');
            $resource = $this->input->post('resource');
            $descresource = $this->input->post('descresource');
            $acs_url = $this->input->post('acs_url');
            $acs_bind = $this->input->post('acs_bind');
            $acs_order = $this->input->post('acs_order');
            $nameids = $this->input->post('nameids');
            $encrypt_cert_body = $this->input->post('encrypt_cert_body');
            $sign_cert_body = $this->input->post('sign_cert_body');



            $newSP = new models\Provider;
            $newSP->setName($resource);
            $newSP->setDisplayName($descresource);
            $newSP->setEntityId($entityid);
            $newSP->setAsSP();
            $newSP->setDefaultState();
            $newSP->setHelpdeskUrl($helpdeskurl);
            $newSP->setHomeUrl($homeurl);

            if (!empty($federation)) {
                $ispublic = $federation->getPublic();
                if ($ispublic) {
                    $newSP->setFederation($federation);
                }
                else {
                    log_message('warning', 'Federation is not public, cannot register sp with join fed with name ' . $federation->getName());
                }
            }
            $contact = new models\Contact;
            $contact->setFullName($contact_name);
            $contact->setEmail($contact_mail);
            $contact->setPhone($contact_phone);
            $contact->setType('administrative');
            $newSP->setContact($contact);
            if (!empty($encrypt_cert_body)) {
                $crt_enc = new models\Certificate;
                $crt_enc->setCertUse('encryption');
                $crt_enc->setAsSPSSO();
                $crt_enc->setCertType('x509');
                $crt_enc->setCertData($encrypt_cert_body);
                $crt_enc->setAsDefault();
                $crt_enc->generateFingerprint();
                $newSP->setCertificate($crt_enc);
            }

            if (!empty($sign_cert_body)) {
                $crt_sign = new models\Certificate;
                $crt_sign->setCertUse('signing');
                $crt_sign->setAsSPSSO();
                $crt_sign->setCertType('x509');
                $crt_sign->setCertData($sign_cert_body);
                $crt_sign->setAsDefault();
                $crt_sign->generateFingerprint();
                $newSP->setCertificate($crt_sign);
            }

            if (!empty($nameids)) {
                $nameids = trim(preg_replace('/\s\s+/', ' ', $nameids));
                $nameidsInArray = array();
                $nameidsInArray = explode(' ', $nameids);
                $newSP->setNameIds('spsso', $nameidsInArray);
            }
            $acs = new models\ServiceLocation;
            $acs->setUrl($acs_url);
            $acs->setDefault(TRUE);
            $acs->setOrder($acs_order);
            $acs->setAsACS();
            $acs->setBindingName($acs_bind);
            $newSP->setServiceLocation($acs);

            $queue = new models\Queue;
            $loggedin_user = null;
            if (!empty($_SESSION['username'])) {
                $loggedin_user = $_SESSION['username'];
            }

            if (!empty($loggedin_user)) {
                $creator = $this->em->getRepository("models\User")->findOneBy(array('username' => $loggedin_user));
            }
            if (!empty($creator)) {
                $queue->setCreator($creator);
            }

            $queue->setAction('Create');
            $queue->setName($newSP->getName());
            $queue->addSP($newSP->convertToArray());
            $queue->setEmail($this->input->post('contact_mail'));
            $queue->setToken();

            $this->em->persist($queue);
            $this->em->flush();
            $recipients = array();
            $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
            $a_members = $a->getMembers();
            foreach ($a_members as $m) {
                $recipients[] = $m->getEmail();
            }
            $sbj = 'SP registration request';
            $body = 'Dear Administrator'.PHP_EOL;
            $body .= $queue->getEmail() . ' just completed a Service Provider registration'.PHP_EOL;
            $body .= 'Resource name: '.$resource.PHP_EOL;
            $body .= 'entityID: ' . $entityid .PHP_EOL;
            $body .= 'You can approve or reject it on ' . base_url() . 'reports/awaiting/detail/' . $queue->getToken() . PHP_EOL;
            $this->load->library('email_sender');
            $this->email_sender->send($recipients, $sbj, $body);
            redirect(base_url().'providers/sp_registration/success','refresh');
        }
        else
        {



        $data['federations'] = $this->_getPublicFeds();

        $data['acs_dropdown'][''] = lang('selectone');
        $tmpacsprotocols = getBindACS();
        foreach ($tmpacsprotocols as $v) {
            $acsbindprotocols['' . $v . ''] = $v;
        }
        $data['acs_dropdown'] = array_merge($data['acs_dropdown'], $acsbindprotocols);


        $data['content_view'] = 'sp/sp_registration_form_view';
        $this->load->view('page', $data);
        }
    }
 
    public function success()
    {
        $data['content_view'] = 'sp/sp_registration_success';
        $this->load->view('page',$data);

    }

    private function _getPublicFeds()
    {
        $fedCollection = $this->em->getRepository("models\Federation")->findBy(array('is_public' => TRUE));
        if (!empty($fedCollection)) {
            $federations[''] = lang('selectone') . '...';
            foreach ($fedCollection as $f) {
                if (!$f->getActive()) {
                    $federations[$f->getId()] = $f->getName() . ' (' . lang('rr_fed_inactive') . ')';
                }
                else {
                    $federations[$f->getId()] = $f->getName();
                }
            }
            $federations['none'] = lang('noneatthemoment');
        }
        else {
            $federations[''] = lang('notfound');
            $federations['none'] = lang('noneatthemoment');
        }
        return $federations;
    }

    private function _submit_validate()
    {
        log_message('debug', 'validating form initialized');

        $this->form_validation->set_rules('resource', '' . lang('rr_resource') . '', 'required|min_length[3]|max_length[255]');
        $this->form_validation->set_rules('descresource', '' . lang('rr_descriptivename') . '', 'required|min_length[3]|max_length[255]');
        $this->form_validation->set_rules('entityid', '' . lang('rr_entityid') . '', 'required|trim|no_white_spaces|min_length[3]|max_length[255]|entity_unique[entityid]');
        $this->form_validation->set_rules('helpdeskurl', '' . lang('rr_helpdeskurl') . '', 'required|valid_url|min_length[6]|max_length[255]');
        $this->form_validation->set_rules('homeurl', '' . lang('rr_homeurl') . '', 'required|valid_url|min_length[6]|max_length[255]');
        $this->form_validation->set_rules('contact_name', '' . lang('rr_contactname') . '', 'required|min_length[3]|max_length[255]');
        $this->form_validation->set_rules('contact_mail', '' . lang('rr_contactemail') . '', 'required|min_length[3]|max_length[255]|valid_email');
        $this->form_validation->set_rules('contact_phone', '' . lang('rr_contactphone') . '', 'numeric');
        $this->form_validation->set_rules('acs_url', 'AccertionConsumerService URL', 'required|valid_url[acs_url]');
        $this->form_validation->set_rules('acs_bind', 'AccertionConsumerService Binding', 'required');
        $this->form_validation->set_rules('acs_order', 'AccertionConsumerService index', 'required|numeric');
        $this->form_validation->set_rules('nameids', 'NameIdFormat', 'trim|xss_clean');
        $this->form_validation->set_rules('encrypt_cert_body', '' . lang('rr_certificateencrypting') . '', 'trim|verify_cert[encrypt_cert_body]');
        $this->form_validation->set_rules('sign_cert_body', '' . lang('rr_certificatesigning') . '', 'trim|verify_cert[sign_cert_body]');
        return $this->form_validation->run();
    }

}
