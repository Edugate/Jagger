<?php
if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
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
 * Idp_registration Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Idp_registration extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'cert'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element'));

        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        $this->session->set_userdata(array('currentMenu' => 'register'));
    }

    function index() {
        $this->title = "Identity Provider (Home Organization) registration form";
        $data['content_view'] = 'idp/idp_register_form';
        /**
         *  get list of public federations
         */
        $fedCollection = $this->em->getRepository("models\Federation")->findBy(array('is_public' => TRUE));
        $data['federations'] = array();
        /**
         *  generate dropdown list of public federations
         */
        foreach ($fedCollection as $key) {
            $data['federations'][$key->getName()] = $key->getName();
        }
        $data['federations']['none'] = '>>None<<';

        $this->load->view('page', $data);
    }

    public function submit($i = NULL) {
        if (!empty($i) AND $i === 'success') {
            $this->title = lang('idpregsuccess');
            $data['content_view'] = 'idp/idp_register_form_success';
            $this->load->view('page', $data);
        } else {
            if ($this->_submit_validate() === FALSE) {
                $this->index();
                return;
            }

            $idp = new models\Provider;
            $federpost = $this->input->post('federation');
            if(!empty($federpost))
            {
                 $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $federpost));
            }
	    if(!empty($federation))
            {
                $idp->setFederation($federation);
            }
            $idp->setName($this->input->post('homeorg'));
            $idp->setEntityId($this->input->post('entity'));
            $idp->setIDP();
            $idp->setHelpdeskUrl($this->input->post('helpdeskurl'));
            $idp->setHomeUrl($this->input->post('homeurl'));
            $idp->setDefaultState();
            $scope = $this->input->post('scope');
            if(empty($scope))
            {
               $scopeset = array();
            }
            else
            {
               $scopeset = explode(',',$scope);
            }
            $idp->setScope('idpsso',$scopeset);
            $privurl = $this->input->post('privacyurl');
            if(!empty($privurl))
            {
               $idp->setPrivacyUrl($privurl);
            }

            $cert = new models\Certificate;
            $cert->setAsIDPSSO();
            $cert->setAsDefault();
            $certbody = $this->input->post('certbody');
            $cert->setCertdata($certbody);

            /**
             * create 3 the same contacts objects (administrative,technical,support)
             */
            $contact1 = new models\Contact;
            $contact1->setFullname($this->input->post('contactname'));
            $contact1->setType('administrative');
            $contact1->setEmail($this->input->post('contactmail'));
            $contact1->setPhone($this->input->post('phone'));
            /* clone object */
            $contact2 = clone $contact1;
            $contact2->setType('technical');
            /* clone object */
            $contact3 = clone $contact1;
            $contact3->setType('support');
            /* add contacts to idp collection */
            $idp->setContact($contact1);
            $idp->setContact($contact2);
            $idp->setContact($contact3);
            $idp->setCertificate($cert);
            $cert->setProvider($idp);
            /* add defaultssohandler */
            $ssoLogin = new models\ServiceLocation;
            $ssoLogin->setType('SingleSignOnService');
            $ssoLogin->setBindingName($this->input->post('bindingname'));
            $ssoLogin->setUrl($this->input->post('ssohandler'));
            $ssoLogin->setOrder(1);
            $ssoLogin->setDefault(TRUE);

            $idp->setServiceLocation($ssoLogin);
            /* create queue object */
            $qu = new models\Queue;
            $loggedin_user = $this->session->userdata('username');
            if(!empty($_SESSION['username']))
            {
                $loggedin_user = $_SESSION['username'];
            }
            else {
               $loggedin_user = null;
            }
            if (!empty($loggedin_user)) {
                $creator = $this->em->getRepository("models\User")->findOneBy(array('username' => $loggedin_user));
                $qu->setCreator($creator);
            }
            $qu->setAction("Create");
            $qu->setName($this->input->post('homeorg'));
            $qu->addIDP($idp->convertToArray());
            $qu->setEmail($this->input->post('contactmail'));
            $qu->setToken();
            $this->em->persist($qu);
          
            /**
             * send email
             */
            $sbj = 'IDP registration request';
            $body = 'Dear user,'.PHP_EOL;
            $body = 'You have received this mail because your email address is on the notification list'.PHP_EOL;
            $body .= ''.$qu->getEmail().' completed a new Identity Provider Registration'.PHP_EOL;
            $body .='You can approve or reject it on '.base_url().'reports/awaiting/detail/'.$qu->getToken().PHP_EOL;
            $this->email_sender->addToMailQueue(array('greqisterreq','gidpregisterreq'),null,$sbj,$body,array(),FALSE);
            $this->em->flush();
            $redirect_to = current_url();
            redirect($redirect_to . "/success");
        }
    }

    private function _submit_validate() {
        $this->form_validation->set_rules('homeorg', 'Homeorg Organization', 'trim|required|min_length[5]|max_length[128]|homeorg_unique[homeorg]|xss_clean');
        $this->form_validation->set_rules('entity', 'entityID', 'trim|required|no_white_spaces|min_length[10]|max_length[128]|entity_unique[entity]|xss_clean');
        $this->form_validation->set_rules('ssohandler', 'SSO Handler', 'trim|required|min_length[10]|valid_url[sshohandler]|ssohandler_unique[ssohandler]|xss_clean');
        $this->form_validation->set_rules('certbody', 'Certificate', 'trim|required|xss_clean|verify_cert[certbody]');
        $this->form_validation->set_rules('phone', 'Phone','trim|xss_clean');
        $this->form_validation->set_rules('contactname', 'Contact name', 'trim|required|min_length[5]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('contactmail', 'Contact email', 'trim|required|max_length[255]|valid_email');
        $this->form_validation->set_rules('helpdeskurl', 'helpdesk Url or E-mail', 'trim|required|xss_clean');
        $this->form_validation->set_rules('scope', 'Scope', 'trim|required|xss_clean');
        $this->form_validation->set_rules('homeurl', 'Home Url', 'trim|required|valid_url[homeurl]');
        $this->form_validation->set_rules('metadataurl', 'Metadata URL', 'trim|valid_url');
        $this->form_validation->set_rules('privacyurl', 'Privacy Statement URL', 'trim|valid_url');
        return $this->form_validation->run();
    }

}
