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
 * Sp_edit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Sp_edit extends MY_Controller {

    protected $sp;
    protected $tmp_providers;

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->tmp_providers = new models\Providers;
        $this->load->library('zacl');
        $this->load->helper('shortcodes');
    }

    public function show($spid)
    {
        $this->sp = $this->tmp_providers->getOneSpById($spid);

        if (empty($this->sp))
        {
            log_message('error', $this->mid . "SP edit: Service Provider with id=" . $spid . " not found");
            show_error(lang('rerror_spnotfound'), 404);
        }
        $this->title = lang('title_spedit');
        $has_write_access = $this->zacl->check_acl($this->sp->getId(), 'write', 'sp', '');
        $locked = $this->sp->getLocked();
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit sp: ' . $this->sp->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        if ($locked)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'Identity Provider is locked: ' . $this->sp->getEntityid();
            log_message('debug', $this->sp->getEntityid() . ': is locked and cannot be edited');
            $this->load->view('page', $data);
            return;
        }

        log_message('debug', $this->mid . 'opening sp_edit form for:' . $this->sp->getEntityId());

        $is_local = $this->sp->getLocal();
        if (!$is_local)
        {
            $data['error_message'] = anchor(base_url() . "providers/provider_detail/sp/" . $this->sp->getId(), $this->sp->getName()) . lang('rerror_cannotmanageexternal');
            $data['content_view'] = "manage/sp_edit_view";
            $this->load->view('page', $data);
            return;
        }
        $sessiontostore = array('editedsp' => $this->sp->getId());
        $this->session->set_userdata($sessiontostore);
        $data['error_messages'] = validation_errors('<p class="error">', '</p>');

        $data['sp_detail']['name'] = $this->sp->getName();
        $data['sp_detail']['id'] = $this->sp->getId();
        $data['sp_detail']['entityid'] = $this->sp->getEntityId();

#        $data['entityform'] = $this->form_element->generateEntityForm($this->sp);
        $entype = $this->sp->getType();
   
        if($entype == 'BOTH')
        {
            $data['entityform'] = $this->form_element->generateEntityForm($this->sp,null,'sp');
        }
        else
        {
            $data['entityform'] = $this->form_element->generateEntityForm($this->sp);
        }


        $data['content_view'] = 'manage/sp_edit_view';
        $this->load->view('page', $data);
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('entityid', lang('rr_entityid'), 'trim|required|min_length[5]|max_length[255]|entityid_unique_update[' . $this->spid . ']|xss_clean');
        $this->form_validation->set_rules('displayname', lang('rr_displayname'), 'trim|required|min_length[5]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('ldisplayname[]', 'Localized '.lang('rr_displayname'), 'trim|max_length[255]|xss_clean');
        $this->form_validation->set_rules('homeorgname', lang('rr_resource'), 'trim|required|min_length[5]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('privacyurl', lang('rr_privacystatement'), 'trim|xss_clean');
        $this->form_validation->set_rules('lprivacyurl[]','Localized '. lang('rr_privacystatement'), 'trim|valid_url_or_empty|min_length[0]');
        $this->form_validation->set_rules('description', lang('rr_description'), 'trim|max_length[512]|xss_clean');
        $this->form_validation->set_rules('homeurl', lang('rr_resourceurl'), 'xss_clean|valid_url');
        $this->form_validation->set_rules('helpdeskurl', lang('rr_helpdeskurl'), 'required|xss_clean');
        $this->form_validation->set_rules('lhelpdeskurl[]', 'Localized '.lang('rr_helpdeskurl'), 'trim|valid_url_or_empty');
        $this->form_validation->set_rules('description', lang('rr_description'), 'xss_clean');
        $this->form_validation->set_rules('ldescription[]', 'Localized' .lang('rr_description'), 'trim|xss_clean');
        $this->form_validation->set_rules('validfrom', lang('rr_validfrom'), 'trim|xss_clean|valid_date');
        $this->form_validation->set_rules('validto', lang('rr_validto'), 'trim|xss_clean|valid_date');
        $this->form_validation->set_rules('registerdate', 'Registration date', 'trim|xss_clean|valid_date_past');
        $this->form_validation->set_rules('registar', 'Registration authority', 'trim|xss_clean|max_length[250]');
        $this->form_validation->set_rules('usestatic', 'Static metdatada', "valid_static[" . base64_encode($this->input->post('staticmetadatabody')) . ":::" . $this->input->post('entityid') . " ]");
        $this->form_validation->set_rules('acs_index[]', 'Assertion Consumer Service index', 'acs_index_check');
        $this->form_validation->set_rules('acs_url[]', 'Assertion Consumer Service url', 'array_valid_url');
        $this->form_validation->set_rules('discindex[]', 'Discovery  Service index', 'acs_index_check');
        $this->form_validation->set_rules('disc[]', 'Discovery Service url', 'array_valid_url');
        $this->form_validation->set_rules('initdisc[]', 'RequestInitiator', 'xss_clean|array_valid_url');
       
        /**
         * @todo add validation of service locations
         */
        return $this->form_validation->run();
    }

    public function submit()
    {
        $data = array();
        $pref = $this->mid . "sp_edit: submit: ";
        log_message('debug', $pref . "started");
        $data['content_view'] = 'manage/sp_edit_view';

        $editedsp = $this->session->userdata('editedsp');
        $this->spid = $editedsp;
        if (empty($editedsp))
        {
            show_error(lang('rerror_splostinfo'), 404);
        }
        log_message('debug', $pref . "sp_id: " . $editedsp);
        $this->sp = $this->tmp_providers->getOneSpById($editedsp);
        if (empty($this->sp))
        {
            show_error($this->mid . lang('rerror_spnotfound'), 404);
        }
        $has_write_access = $this->zacl->check_acl($this->sp->getId(), 'write', 'sp', '');
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit sp: ' . $this->sp->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        $locked = $this->sp->getLocked();
        if ($locked)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'Identity Provider is locked: ' . $this->sp->getEntityid();
            log_message('debug', $this->sp->getEntityid() . ': is locked and cannot be edited');
            $this->load->view('page', $data);
            return;
        }

        if ($this->_submit_validate() === FALSE)
        {
            return $this->show($editedsp);
        }
	$sp_before = clone($this->sp);

        

        /**
         * @todo finish ACS input
         * AssertionConsumerService
         */
        $serviceLocations = $this->sp->getServiceLocations();
        $acs_bind = $this->input->post('acs_bind');
        $acs_url = $this->input->post('acs_url');
        $acs_index = $this->input->post('acs_index');
        $acs_default = $this->input->post('acs_default');
        $acs_keys = array_keys($acs_bind);

        $idpdisc = $this->input->post('disc');
        $idpdisc_index = $this->input->post('discindex');
        $initdisc = $this->input->post('initdisc');
        foreach ($serviceLocations as $srv)
        {
            $srvid = $srv->getId();
            if ($srv->getType() == 'AssertionConsumerService')
            {
                if (array_key_exists($srvid, $acs_bind))
                {
                    if (empty($acs_url[$srvid]))
                    {
                        $this->sp->removeServiceLocation($srv);
                        $this->em->persist($srv);
                    }
                    else
                    {
                        $srv->setBindingName($acs_bind[$srvid]);
                        $srv->setUrl($acs_url[$srvid]);
                        $srv->setOrder($acs_index[$srvid]);
                        if ($acs_default == $srvid)
                        {
                            $srv->setDefault(true);
                        }
                        else
                        {
                            $srv->setDefault(false);
                        }
                        $this->em->persist($srv);
                    }
                }
                else
                {
                    log_message('warn', $this->mid . 'Some inconsistency with submited edit sp form for entity:' . $this->sp->getEntityId() . ' AssertionConsumerService didnt exist in submited form');
                }
            }
            elseif($srv->getType() == 'DiscoveryResponse')
            {
                if (array_key_exists($srvid, $idpdisc))
                {
                    if(empty($idpdisc[$srvid]))
                    {
                        $this->sp->removeServiceLocation($srv);
                    } 
                    else
                    {
                       $srv->setDiscoveryResponse($idpdisc[$srvid],$idpdisc_index[$srvid]);
                       $this->em->persist($srv);
                    }
                }
                 
            }
            elseif($srv->getType() == 'RequestInitiator')
            {
               if (array_key_exists($srvid, $initdisc))
               {
                        log_message('debug', 'kkkkkkkkkkkkkkkkkkkkk'.$srvid.'::::::'.serialize($initdisc)); 
                    if(empty($initdisc[$srvid]))
                    {
                        $this->sp->removeServiceLocation($srv);
                        $this->em->remove($srv);
                    } 
                    else
                    {
                       $srv->setRequestInitiator($initdisc[$srvid]);
                       $this->em->persist($srv);
                    }
                  
               }
               elseif(array_key_exists('n', $initdisc) && !empty($initdisc['n']))
               {
                        log_message('debug', 'kkkkkkkkkkkkkkkkkkkkk'.$srvid.'::::::'.serialize($initdisc)); 
                    $newinitdisc = new models\ServiceLocation;
                    $newinitdisc->setRequestInitiator($initdisc['n']);
                    $newinitdisc->setProvider($this->sp);
                    $this->em->persist($newinitdisc);
               }
            }
        }
        /**
         * add new discovery service if filled
         */
        if(!empty($idpdisc['n']))
        {
            $newidpdisc = new models\ServiceLocation;
            $newidpdisc->setDiscoveryResponse($idpdisc['n'],$idpdisc_index['n']);
            $newidpdisc->setProvider($this->sp);
            $this->em->persist($newidpdisc);
        } 
        /**
         * add new acs to database if filled
         */
        if (!empty($acs_url['n']))
        {
            $newsrv_acs = new models\ServiceLocation;
            $newsrv_acs->setAsACS();
            $newsrv_acs->setBindingName($acs_bind['n']);
            if (!isset($acs_index['n']) or $acs_index['n'] < 0 or $acs_index['n'] == null or !is_numeric($acs_index['n']))
            {
                $acs_index['n'] = null;
                $acs_index['n'] = max($acs_index) + 1;
            }

            $newsrv_acs->setOrder($acs_index['n']);
            if ($acs_default == 'n')
            {
                $newsrv_acs->setDefault(true);
            }
            $newsrv_acs->setProvider($this->sp);
            $newsrv_acs->setUrl($acs_url['n']);
            $this->em->persist($newsrv_acs);
        }



        /**
         * get submited values
         */
        $homeorgname = $this->input->post('homeorgname');
        $lhomeorgname= $this->input->post('lname');
        $entityid = $this->input->post('entityid');
        $displayname = $this->input->post('displayname');
        $ldisplayname = $this->input->post('ldisplayname');
        $homeurl = $this->input->post('homeurl');
        $helpdeskurl = $this->input->post('helpdeskurl');
        $lhelpdeskurl = $this->input->post('lhelpdeskurl');
        $privacyurl = $this->input->post('privacyurl');
        $lprivacyurl = $this->input->post('lprivacyurl');
        $description = $this->input->post('description');
        $ldescription = $this->input->post('ldescription');
        $usestatic = $this->input->post('usestatic');
        $staticmetadatabody = $this->input->post('staticmetadatabody');
        $protocols = $this->input->post('protocols');
        $nameids = $this->input->post('nameids');
        $registrationdate = $this->input->post('registerdate');
        $registrar = $this->input->post('registrar');
        if(!empty($registrar))
        {
            $this->sp->setRegistrationAuthority($registrar);
        }
        if(!empty($registrationdate))
        {
            $this->sp->setRegistrationDate(\DateTime::createFromFormat('Y-m-d H:i:s', $registrationdate.' 00:00:00'));
        }
        else
        {
            $this->sp->setRegistrationDate(null);
        }


        $langcodes = languagesCodes();
        if(is_array($lhomeorgname))
        {
            foreach($lhomeorgname as $k=>$v)
            {
               if(!array_key_exists($k,$langcodes))
               {
                   unset($lhomeorgname[$k]);
               }
               elseif(empty($v))
               {
                   unset($lhomeorgname[$k]);
               }
            }
            if(count($lhomeorgname)>0)
            {
              $this->sp->setLocalName($lhomeorgname);
            }
            else
            {
              $this->sp->setLocalName(NULL);
            }
        }
        if(is_array($ldisplayname))
        {
            foreach($ldisplayname as $k=>$v)
            {
               if(!array_key_exists($k,$langcodes))
               {
                   unset($ldisplayname[$k]);
               }
               elseif(empty($v))
               {
                   unset($ldisplayname[$k]);
               }
            }
            if(count($ldisplayname)>0)
            {
              $this->sp->setLocalDisplayName($ldisplayname);
            }
            else
            {
              $this->sp->setLocalDisplayName(NULL);
            }
        }

        if(is_array($lhelpdeskurl))
        {
            foreach($lhelpdeskurl as $k=>$v)
            {
               if(!array_key_exists($k,$langcodes))
               {
                   unset($lhelpdeskurl[$k]);
               }
               elseif(empty($v))
               {
                   unset($lhelpdeskurl[$k]);
               }
            }
            if(count($lhelpdeskurl>0))
            {
              $this->sp->setLocalHelpdeskURL($lhelpdeskurl);
            }
            else
            {
              $this->sp->setLocalHelpdeskURL(NULL);
            }
        }
        if(is_array($lprivacyurl))
        {
            foreach($lprivacyurl as $k=>$v)
            {
               if(!array_key_exists($k,$langcodes))
               {
                   unset($lprivacyurl[$k]);
               }
               elseif(empty($v))
               {
                   unset($lprivacyurl[$k]);
               }
            }
            if(count($lprivacyurl>0))
            {
              $this->sp->setLocalPrivacyUrl($lprivacyurl);
            }
            else
            {
              $this->sp->setLocalPrivacyUrl(NULL);
            }
        }

        if(is_array($ldescription))
        {
            foreach($ldescription as $k=>$v)
            {
               if(!array_key_exists($k,$langcodes))
               {
                   unset($ldescription[$k]);
               }
               elseif(empty($v))
               {
                   unset($ldescription[$k]);
               }
            }
            if(count($ldescription)>0)
            {
              $this->sp->setLocalDescription($ldescription);
            }
            else
            {
              $this->sp->setLocalDescription(NULL);
            }
        }
        
        $this->sp->setName($homeorgname);
        $this->sp->setEntityid($entityid);
        $this->sp->setDisplayName($displayname);
        $this->sp->setHomeUrl($homeurl);
        $this->sp->setHelpdeskUrl($helpdeskurl);
        $this->sp->setPrivacyUrl($privacyurl);

        $this->sp->setValidFrom(\DateTime::createFromFormat('Y-m-d', $this->input->post('validfrom')));
        $this->sp->setValidTo(\DateTime::createFromFormat('Y-m-d', $this->input->post('validto')));
        $this->sp->setScope(null);
        $this->sp->setDescription($description);
        if (isset($usestatic) && $usestatic == 'accept')
        {
            $this->sp->setStatic(true);
            log_message('debug', $pref . "static set: true");
        }
        else
        {
            $this->sp->setStatic(false);
            log_message('debug', $pref . "static set: false");
        }
        $e_static_metadata = $this->sp->getStaticMetadata();
        if (!empty($e_static_metadata))
        {
            $s_metadata = $e_static_metadata;
            log_message('debug', $pref . "static metadata is not empty");
        }
        else
        {
            $s_metadata = new models\StaticMetadata;
            log_message('debug', $pref . "static metadata is empty");
        }

        $s_metadata->setMetadata($staticmetadatabody);
        $s_metadata->setProvider($this->sp);
        $this->sp->setStaticMetadata($s_metadata);
        $this->em->persist($s_metadata);



        if (!empty($protocols) && is_array($protocols) && count($protocols) > 0)
        {
            log_message('debug', $pref . "setting protocols");
            $this->sp->resetProtocol();
            foreach ($protocols as $p)
            {
                $this->sp->setProtocol($p);
            }
        }
        if (!empty($nameids) && is_array($nameids) && count($nameids) > 0)
        {
            log_message('debug', $pref . "setting nameids");
            $this->sp->resetNameId();
            foreach ($nameids as $p)
            {
                $this->sp->setNameId($p);
            }
        }


        /**
         * certs if cert exists then cert[]=array('keyname'=>$keyname,'$certbody'=>$certbody)
         * where keyname and certbody 
         */
        log_message('debug', $pref . "setting certs");
        $certs = array();
        $existingCerts = $this->sp->getCertificates();
        log_message('debug', $pref . "number of existing certs is: " . $existingCerts->count());
        //$count_existingCerts = count($existingCerts);
        if ($existingCerts->count() > 0)
        {

            foreach ($existingCerts->getValues() as $ec)
            {
                $id = $ec->getId();
                $ecdata = trim($this->input->post('cert_' . $id . '_data'));
                $ectype = $this->input->post('cert_' . $id . '_type');
                $ecuse = $this->input->post('cert_' . $id . '_use');
                $eckeyname = $this->input->post('cert_' . $id . '_keyname');
                $ecremove = $this->input->post('cert_' . $id . '_remove');

                if ($ecremove == 'yes')
                {
                    log_message('debug', $pref . 'cert action: ' . $ecremove);
                    $this->sp->removeCertificate($ec);
                }
                elseif (!empty($ecdata) OR !empty($eckeyname))
                {
                    $ecusesigning = false;
                    $ecuseencryption = false;
                    if (empty($ecuse))
                    {
                        $ecuse = array();
                    }
                    foreach ($ecuse as $pec)
                    {
                        if ($pec == 'signing')
                        {
                            $ecusesigning = true;
                        }
                        if ($pec == 'encryption')
                        {
                            $ecuseencryption = true;
                        }
                        if ($ecusesigning === $ecuseencryption)
                        {
                            $ec->setCertUse();
                        }
                        elseif ($ecusesigning)
                        {
                            $ec->setCertUse('signing');
                        }
                        else
                        {
                            $ec->setCertUse('encryption');
                        }
                        $ec->setCertdata($ecdata);
                        $ec->setKeyname($eckeyname);
                        $ec->setCertType($ectype);
                        $ec->setType('sso');
                        $this->em->persist($ec);
                    }
                }
            }
        }

        /**
         * add new certificate
         */
        $cdata = reformatPEM($this->input->post('cert_0n_data'));
        $ctype = 'sso';
        $ccerttype = $this->input->post('cert_0n_type');
        $cuse = $this->input->post('cert_0n_use');
        $ckeyname = $this->input->post('cert_0n_keyname');
        /**
         * check any input (*_0n_*) if new certificate added in form
         */
        if (!empty($cdata) OR !empty($ckeyname))
        {
            log_message('debug', $pref . "setting new cert");
            if ($ccerttype)
            {

                $cusesigning = false;
                $cuseencryption = false;
                foreach ($cuse as $c)
                {
                    if ($c == 'signing')
                    {
                        $cusesigning = true;
                    }
                    if ($c == 'encryption')
                    {
                        $cuseencryption = true;
                    }
                }
                $cvalid = false;
                if ($ccerttype == 'x509')
                {
                    if (!empty($cdata))
                    {
                        log_message('debug', $pref . "setting new cert 2");
                        $cvalid = validateX509($cdata);
                    }
                    elseif (!empty($ckeyname))
                    {
                        log_message('debug', $pref . "setting new cert 3");
                        $cvalid = true;
                    }
                }
                {
                    
                }
                if (!empty($cvalid))
                {

                    $newcert = new models\Certificate;
                    $newcert->setCertType($ccerttype);
                    if (!empty($cdata) && ($ccerttype == 'x509'))
                    {
                        if ($cusesigning === $cuseencryption)
                        {
                            $newcert->setCertUse();
                        }
                        elseif ($cusesigning)
                        {
                            $newcert->setCertUse('signing');
                        }
                        else
                        {
                            $newcert->setCertUse('encryption');
                        }
                    }
                    $newcert->setAsSSO();
                    $newcert->setCertData($cdata);
                    $newcert->setProvider($this->sp);
                    $newcert->setKeyname($ckeyname);
                    $this->sp->setCertificate($newcert);
                    $this->em->persist($newcert);
                }
            }
        }
        /**
         * /end  newcert
         */
        /**
         * contacts
         */
        log_message('debug', $pref . "2 finished");
        $no_contacts = $this->input->post('no_contacts');
        $contacts_col = $this->sp->getContacts();
        foreach ($contacts_col->getValues() as $c)
        {
            $type = $this->input->post('contact_' . $c->getId() . '_type');
            $fname = $this->input->post('contact_' . $c->getId() . '_fname');
            $sname = $this->input->post('contact_' . $c->getId() . '_sname');
            $email = $this->input->post('contact_' . $c->getId() . '_email');
            if (empty($email))
            {
                $this->sp->removeContact($c);
            }
            else
            {
                $c->setType($type);
                $c->setGivenname($fname);
                $c->setSurname($sname);
                $c->setEmail($email);
                $this->em->persist($c);
            }
        }
        log_message('debug', $pref . "1 finished");
        $cnt_newmail = trim($this->input->post('contact_0n_email'));
        $cnt_newtype = $this->input->post('contact_0n_type');
        $cnt_newgivenname = $this->input->post('contact_0n_fname');
        $cnt_newsurname = $this->input->post('contact_0n_sname');
        if (!empty($cnt_newmail))
        {
            $k = new models\Contact;
            $k->setEmail($cnt_newmail);
            $k->setType($cnt_newtype);
            $k->setGivenname($cnt_newgivenname);
            $k->setSurname($cnt_newsurname);
            $k->setProvider($this->sp);
            $this->sp->setContact($k);
            $this->em->persist($k);
        }
        $this->em->persist($this->sp);
        $differ = $this->sp->diffProviderToArray($sp_before);
        $this->em->flush();
        $this->load->library('tracker');
        if (count($differ) > 0)
        {
            $this->tracker->save_track('sp', 'modification', $this->sp->getEntityId(), serialize($differ), true);
        }


        log_message('debug', $pref . " finished");
        redirect(base_url("manage/sp_edit/show/" . $editedsp), 'refresh');


        $data['content_view'] = 'manage/sp_edit_view';
        $this->load->view('page', $data);
    }

}
