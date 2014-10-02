<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
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

    protected $additional_error;
    protected $ssonamekeys;

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'cert'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element'));

        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        $this->session->set_userdata(array('currentMenu' => 'register'));
        $this->additional_error = null;
        $this->ssonamekeys = array('saml2httppost', 'saml2httppostsimplesign', 'saml2httpredirect');
        MY_Controller::$menuactive = 'reg';
    }

    function index()
    {
        $data['titlepage'] = lang('rr_idp_register_title');
        $idpssobindprotocols = array(
            'saml2httppost' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'saml2httppostsimplesign' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
            'saml2httpredirect' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
        );
        $data['idpssobindprotocols'] = $idpssobindprotocols;

        if ($this->_submit_validate() === TRUE)
        {
            $idp = new models\Provider;
            $idp->setType('IDP');
            $idpsso = $this->input->post('sso');
            $sourceIP = $this->input->ip_address();
            if (!empty($idpsso) && is_array($idpsso))
            {
                $i = 0;
                $idpsso = array_filter($idpsso);
                foreach ($idpsso as $k => $v)
                {
                    if (in_array($k, $this->ssonamekeys))
                    {
                        $s = new models\ServiceLocation;
                        $s->setType('SingleSignOnService');
                        $s->setBindingName($idpssobindprotocols['' . $k . '']);
                        $s->setUrl($v);
                        $s->setOrder($i++);
                        $s->setDefault(FALSE);
                        $idp->setServiceLocation($s);
                    }
                }
            }
            /**
             * create 3 the same contacts objects (administrative,technical,support)
             */
            $contact1 = new models\Contact;
            $contact1->setGivenName($this->input->post('contactfname'));
            $contact1->setSurName($this->input->post('contactlname'));
            $contact1->setType('administrative');
            $contact1->setEmail($this->input->post('contact_mail'));
            $contact1->setPhone($this->input->post('contact_phone'));
            /* clone object */
            $contact2 = clone $contact1;
            $contact2->setType('technical');
            
            /* add contacts to idp collection */
            $idp->setContact($contact1);
            $idp->setContact($contact2);
            

            $signcert = new models\Certificate;
            $signcert->setAsIDPSSO();
            $signcert->setAsDefault();
            $signcert->setCertUse('signing');
            $signcertbody = $this->input->post('sign_cert_body');
            $signcert->setCertdata($signcertbody);
            $signcert->setProvider($idp);
            $idp->setCertificate($signcert);

            $encryptcert = new models\Certificate;
            $encryptcert->setAsIDPSSO();
            $encryptcert->setAsDefault();
            $encryptcert->setCertUse('encryption');
            $encryptcertbody = $this->input->post('encrypt_cert_body');
            $encryptcert->setCertdata($encryptcertbody);
            $encryptcert->setProvider($idp);
            $idp->setCertificate($encryptcert);


            $federpost = $this->input->post('federation');
            if (!empty($federpost))
            {
                try
                {
                    $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $federpost));
                }
                catch (Exception $e)
                {
                    log_message('error', __METHOD__ . ' ' . $e);
                    show_error('Internal Server Error', 500);
                    return;
                }
            }
            if (!empty($federation))
            {
                $ispublic = $federation->getPublic();
                $isactive = $federation->getActive();
                if ($ispublic && $isactive)
                {
                    $membership = new models\FederationMembers;
                    $membership->setJoinState('1');
                    $membership->setProvider($idp);
                    $membership->setFederation($federation);
                    $idp->getMembership()->add($membership);
                }
                else
                {
                    log_message('warning', 'Federation is not public, cannot register sp with join fed with name ' . $federation->getName());
                }
            }
            $idp->setName($this->input->post('homeorg'));
            $idp->setDisplayname($this->input->post('deschomeorg'));
            $idp->setEntityId($this->input->post('entityid'));
            $idp->setDefaultState();
            $idp->setHelpdeskUrl($this->input->post('helpdeskurl'));
            $idpssoscope = $this->input->post('idpssoscope');
            if (empty($idpssoscope))
            {
                $scopeset = array();
            }
            else
            {
                $scopeset = explode(',', $idpssoscope);
            }
            foreach ($scopeset as $k => $v)
            {
                $scopeset['' . $k . ''] = trim($v);
            }
            $scopeset = array_filter($scopeset);

            $idp->setScope('idpsso', $scopeset);
            $this->load->helper('protocols');
            $allowedNameIds = getAllowedNameId();
            $nameids = trim($this->input->post('nameids'));
            if (!empty($nameids))
            {
                $nameidsArray = explode(' ', $nameids);
                foreach ($nameidsArray as $k => $v)
                {
                    $v = trim($v);
                    if (!empty($v))
                    {
                        if (!in_array($v, $allowedNameIds))
                        {
                            unset($nameidsArray['' . $k . '']);
                        }
                        else
                        {
                            $nameidsArray['' . $k . ''] = $v;
                        }
                    }
                    else
                    {
                        unset($nameidsArray['' . $k . '']);
                    }
                }
                $idp->setNameIds('idpsso', array_values($nameidsArray));
            }


            /* create queue object */
            $qu = new models\Queue;
            $loggedin_user = $this->session->userdata('username');
            if (!empty($_SESSION['username']))
            {
                $loggedin_user = $_SESSION['username'];
            }
            else
            {
                $loggedin_user = null;
            }
            if (!empty($loggedin_user))
            {
                $creator = $this->em->getRepository("models\User")->findOneBy(array('username' => $loggedin_user));
                $qu->setCreator($creator);
            }
            $qu->setAction("Create");
            $servicename = $this->input->post('homeorg');
            $qu->setName($servicename);
            $qu->addIDP($idp->convertToArray());
            $contactMail = $this->input->post('contact_mail');
            $qu->setEmail($contactMail);
            $qu->setToken();
            $this->em->persist($qu);

            /**
             * send email
             */
            if (!empty($sourceIP))
            {
                $sourceIP = '';
            }

            $messageTemplateParams = array(
                'requestermail' => $qu->getEmail(),
                'token' => $qu->getToken(),
                'requestersourceip' => $sourceIP,
                'orgname' => $servicename,
                'serviceentityid' => $idp->getEntityId(),
            );
            $messageTemplate = $this->email_sender->providerRegRequest('idp', $messageTemplateParams, NULL);

            if (!empty($messageTemplate))
            {
                $this->email_sender->addToMailQueue(array('greqisterreq', 'gidpregisterreq'), null, $messageTemplate['subject'], $messageTemplate['body'], array(), FALSE);
            }

            $sbj = 'IDP registration request';
            $body2 = 'Dear user' . PHP_EOL;
            $body2 .= 'You have received this mail as your email (' . $contactMail . ') was provided during IdentityProvider Registration request on site ' . base_url() . PHP_EOL;
            $body2 .= 'You request has been sent for approval. It might take a while so please be patient';
            $areciepents[] = $contactMail;
            $this->email_sender->addToMailQueue(null, null, $sbj, $body2, $areciepents, FALSE);
            try
            {
                $this->em->flush();
                $redirect_to = current_url();
                redirect($redirect_to . "/success");
            }
            catch (PDOException $e)
            {
                log_message('error', __METHOD__ . ' ' . $e);
                show_error('Internal Server Error', 500);
                return;
            }
        }
        else
        {
            $data['additional_error'] = $this->additional_error;
            $this->title = lang('title_idpreg');
            $data['content_view'] = 'idp/idp_register_form';
            /**
             *  get list of public federations
             */
            $fedCollection = $this->em->getRepository("models\Federation")->findBy(array('is_public' => TRUE, 'is_active' => TRUE));
            if (count($fedCollection) > 0)
            {
                $data['federations'] = array();
                /**
                 *  generate dropdown list of public federations
                 */
                $data['federations']['none'] = lang('noneatthemoment');
                foreach ($fedCollection as $key)
                {
                    $data['federations'][$key->getName()] = $key->getName();
                }
            }
            $this->load->view('page', $data);
        }
    }

    public function success()
    {
        $data['content_view'] = 'idp/idp_register_form_success';
        $this->load->view('page', $data);
    }

    private function _submit_validate()
    {
        $ssourls = $this->input->post('sso');
        if (is_array($ssourls))
        {
            foreach ($ssourls as $k => $p)
            {
                $ssourls['' . $k . ''] = trim($p);
                if (!in_array($k, $this->ssonamekeys))
                {
                    unset($ssourls['' . $k . '']);
                }
            }
            $ssourls = array_filter($ssourls);
            if (count($ssourls) < 1)
            {
                $this->additional_error = lang('err_atleastonesso');
                return false;
            }
        }
        $this->form_validation->set_rules('homeorg', lang('e_orgname'), 'trim|required|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('deschomeorg', lang('e_orgdisplayname'), 'trim|required|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('entityid', 'entityID', 'trim|required|no_white_spaces|min_length[5]|max_length[128]|entity_unique[entity]|xss_clean');
        $this->form_validation->set_rules('sso[]', 'SingleSignOn', 'trim|valid_url');
        $this->form_validation->set_rules('sign_cert_body', lang('rr_certificatesigning'), 'trim|required|xss_clean|verify_cert[certbody]');
        $this->form_validation->set_rules('encrypt_cert_body', lang('rr_certificateencrypting'), 'trim|required|xss_clean|verify_cert[certbody]');
        $this->form_validation->set_rules('contact_phone', lang('rr_contactphone'), 'trim|xss_clean');
        $this->form_validation->set_rules('contactfname', lang('rr_contactfirstname'), 'trim|required|min_length[1]|max_length[255]|xss_clean');
         $this->form_validation->set_rules('contactlname', lang('rr_contactlastname'), 'trim|required|min_length[1]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('contact_mail', lang('rr_contactemail'), 'trim|required|max_length[255]|valid_email');
        $this->form_validation->set_rules('helpdeskurl', lang('e_orgurl'), 'trim|required|valid_url|xss_clean');
        $this->form_validation->set_rules('idpssoscope', 'Scope', 'trim|required|xss_clean');
        $this->form_validation->set_rules('privacyurl', 'Privacy Statement URL', 'trim|valid_url');
        $this->form_validation->set_rules('nameids', 'NameIdentifiers(s)', 'trim|xss_clean');
        return $this->form_validation->run();
    }

}
