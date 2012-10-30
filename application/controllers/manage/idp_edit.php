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
 * Idp_edit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Idp_edit extends MY_Controller {

    protected $idp;
    protected $idpid;
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
        $this->tmp_providers = new models\Providers;
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->load->library('metadata_validator');
        $this->load->library('zacl');
    }

    public function show($idpid)
    {
        $this->idpid = $idpid;
        $pref = $this->mid;

        //$li = new models\Providers;
        $idp = $this->tmp_providers->getOneIdpById($idpid);

        if (empty($idp))
        {
            log_message('error', $pref . "IdP edit: Identity Provider with id=" . $idpid . " not found");
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $locked = $idp->getLocked();
        $has_write_access = $this->zacl->check_acl($idp->getId(), 'write', 'idp', '');
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit idp: ' . $idp->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        if($locked)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'Identity Provider is locked: ' . $idp->getEntityid();
            log_message('debug',$idp->getEntityid(). ': is locked and cannot be edited');
            $this->load->view('page', $data);
            return;
            
        
        }
        log_message('debug', $pref . 'opening idp_edit form for:' . $idp->getEntityId());
        $is_local = $idp->getLocal();
        if (!$is_local)
        {
            $data['error_message'] = anchor(base_url() . "providers/provider_detail/idp/" . $idp->getId(), $idp->getName()) . lang('rerror_cannotmanageexternal');
            $data['content_view'] = "manage/idp_edit_view";
            $this->load->view('page', $data);
            return;
        }
        $sessiontostore = array('editedidp' => $idp->getId());
        $this->session->set_userdata($sessiontostore);
        $action = base_url() . "manage/idp_edit/submit";

        $attributes = array('id' => 'formver2', 'class' => 'editidp');
        $tmp_name = $idp->getName();
        if (empty($tmp_name))
        {
            $display_name = $idp->getEntityId();
        }
        else
        {
            $display_name = $idp->getName();
        }
        $data['form'] = "<div id=\"subtitle\">" . lang('rr_detailsfor') . $display_name . "&nbsp;&nbsp;" . anchor(base_url() . "providers/provider_detail/idp/" . $idp->getId(), '<img src="' . base_url() . 'images/icons/home.png" />') . " </div>";
        $data['form'] .= validation_errors('<p class="error">', '</p>');
        $data['form'] .= form_open($action, $attributes);
        $data['form'] .= $this->form_element->generateEntityForm($idp);
        $tf = '';

        $tf .='<div class="buttons">';
        $tf .='<button type="reset" name="reset" value="reset" class="button negative">
                  <span class="reset">'.lang('rr_reset').'</span></button>';
        $tf .='<button type="submit" name="modify" value="submit" class="button positive">
                  <span class="save">'.lang('rr_save').'</span></button>';
        $tf .= '</div>';
     
        $data['form'] .=$tf;
        $data['form'] .= form_close();
        $data['content_view'] = 'manage/idp_edit_view';
        $this->load->view('page', $data);
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('entityid',lang('rr_entityid'),'trim|required|min_length[5]|max_length[255]|entityid_unique_update['.$this->idpid.']|xss_clean');
        $this->form_validation->set_rules('displayname', lang('rr_displayname'), 'trim|required|min_length[5]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('homeorgname', lang('rr_homeorganisationname'), 'trim|required|min_length[5]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('privacyurl', lang('rr_privacystatement'), 'trim|valid_url');
        $this->form_validation->set_rules('helpdeskurl', lang('rr_helpdeskurl'), 'required|trim|valid_url');
        $this->form_validation->set_rules('homeurl', lang('rr_homeorganisationurl'), 'trim|valid_url');
        $this->form_validation->set_rules('description', lang('rr_description'), 'trim|xss_clean');
        $this->form_validation->set_rules('usestatic', lang('rr_staticmetadata'), "valid_static[".base64_encode($this->input->post('staticmetadatabody')).":::".$this->input->post('entityid')." ]");
        $this->form_validation->set_rules('validfrom', lang('rr_validfrom'), 'trim|xss_clean|valid_date');
        $this->form_validation->set_rules('validto', lang('rr_validto'), 'trim|xss_clean|valid_date');
        $this->form_validation->set_rules('registerdate', 'Registration date', 'trim|xss_clean|valid_date_past');
        $this->form_validation->set_rules('registar', 'Registration authority', 'trim|xss_clean|max_length[250]');
        return $this->form_validation->run();
    }

    public function submit()
    {
        $changes = array();
        $state_before = null;
        $state_after = null;
        $data = array();
        $pref = $this->mid . "idp_edit: submit: ";
        log_message('debug', $pref . "started");
        $data['content_view'] = 'manage/idp_edit_view';

        $editedidp = $this->session->userdata('editedidp');
        $this->idpid = $editedidp;
        log_message('debug', $pref . "idp_id: " . $editedidp);
        if (empty($editedidp))
        {
            show_error('Lost information about what idp to update', 404);
        }

        $has_write_access = $this->zacl->check_acl($editedidp, 'write', 'idp', '');
        if (!$has_write_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit idp ';
            $this->load->view('page', $data);
            return;
        }

        //$e = new models\Providers;
        $idp = $this->tmp_providers->getOneIdpById($editedidp);
        $state_before = $idp->__toString();
        $idp_before = clone($idp);
        if (empty($idp))
        {
            show_error(lang('rerror_idpnotfound'), 404);
        }
        $locked = $idp->getLocked();
        if($locked)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'Identity Provider is locked: ' . $idp->getEntityid();
            log_message('debug',$idp->getEntityid(). ': is locked and cannot be edited');
            $this->load->view('page', $data);
            return;
            
        
        }

        if ($this->_submit_validate() === FALSE)
        {
            return $this->show($editedidp);
        }


        /**
         * SingleSignOnService
         */
        $srv_no = $this->input->post('nosrvs');
        log_message('debug', $pref . "number of services inputs: " . $srv_no);
        $srv = array();
        $srv_new['SingleSignOnService'] = array();
        $ssotmpl = $this->config->item('ssohandler_saml2');
        if ($srv_no > 0)
        {
            for ($i = 1; $i <= $srv_no; $i++)
            {
                $url = $this->input->post('srvsso_' . $i . 'n_url');
                $type = $this->input->post('srvsso_' . $i . 'n_type');
                if (!empty($url) && !empty($type))
                {
                    log_message('debug', $pref . "adding service: " . $url);
                    $srv_new['SingleSignOnService'][$type] = $url;
                }
            }
        }

        /**
         * existing collection in db
         */
        $srv_coll = $idp->getServiceLocations();
        log_message('debug', $pref . "no of found SingleSignOnService: " . $srv_coll->count());
        if ($srv_coll->count() > 0)
        {
            log_message('debug', $pref . "found existing services");
            foreach ($srv_coll->getValues() as $s)
            {
                $s_type = $s->getType();
                if ($s_type == 'SingleSignOnService')
                {
                    $srv['SingleSignOnService'][$s->getBindingName()] = $s->getUrl();
                    if (array_key_exists($s->getBindingName(), $srv_new['SingleSignOnService']))
                    {
                        $s->setUrl($srv_new['SingleSignOnService'][$s->getBindingName()]);
                        unset($srv_new['SingleSignOnService'][$s->getBindingName()]);
                    }
                    else
                    {
                        $i = $s->getId();
                        $url = $this->input->post('srvsso_' . $i . '_url');
                        $type = $this->input->post('srvsso_' . $i . '_type');
                        if (empty($url))
                        {
                            $idp->removeServiceLocation($s);
                        }
                        else
                        {
                            $s->setUrl($url);
                        }
                    }
                    $this->em->persist($s);
                }
            }
        }
        else
        {
            log_message('debug', $pref . "not found existing services for idp");
        }          
        if (array_key_exists('SingleSignOnService', $srv_new))
        {
            log_message('debug', $pref . "key SingleSignOnService exists and number of values is: " . count($srv_new['SingleSignOnService']));        
            foreach ($srv_new['SingleSignOnService'] as $key => $ns)
            {

                $s = new models\ServiceLocation;
                $s->setType('SingleSignOnService');
                $s->setBindingName($key);
                $s->setUrl($ns);     
                $idp->setServiceLocation($s);
                $this->em->persist($s);
            }
        }


        /**
         * get submited values
         */
        $entityid = $this->input->post('entityid');
        $homeorgname = $this->input->post('homeorgname');
        $displayname = $this->input->post('displayname');
        $homeurl = $this->input->post('homeurl');
        $helpdeskurl = $this->input->post('helpdeskurl');
        $privacyurl = $this->input->post('privacyurl');
        $registrationdate = $this->input->post('registerdate');
        $registrar = $this->input->post('registrar');
        $scope = $this->input->post('scope');
        $description = $this->input->post('description');
        $usestatic = $this->input->post('usestatic');

        $staticmetadatabody = trim($this->input->post('staticmetadatabody'));
        if(empty($staticmetadatabody))
        {
              log_message('debug','static metadata empty in post');
        }
        else
        {
              log_message('debug','static metadata not empty in post');
        }
        //$is_static_metadata_valid = $this->metadata_validator->validateWithSchema($staticmetadatabody);
        if ($usestatic && empty($staticmetadatabody))
        {
            $this->error_message = "Static metadata must not be empty or invalid when you have enabled \"Use static metadata\" ";
            return $this->show($idp->getId());
        }
        $protocols = $this->input->post('protocols');
        $nameids = $this->input->post('nameids');

        $idp->setName($homeorgname);
        $idp->setEntityid($entityid);
        $idp->setDisplayName($displayname);
        $idp->setHomeUrl($homeurl);
        $idp->setHelpdeskUrl($helpdeskurl);
        $idp->setPrivacyUrl($privacyurl);
        if(!empty($registrar))
        {
            $idp->setRegistrationAuthority($registrar);
        }
        $validfrom = $this->input->post('validfrom');
        if (!empty($validfrom))
        {
            $validfrom = $validfrom . ' 00:01:00';
        }
        $validto = $this->input->post('validto');
        if (!empty($validto))
        {
            $validto = $validto . ' 23:59:59';
        }
        $idp->setValidFrom(\DateTime::createFromFormat('Y-m-d H:i:s', $validfrom));
        $idp->setValidTo(\DateTime::createFromFormat('Y-m-d H:i:s', $validto));
        if(!empty($registrationdate))
        {
            $idp->setRegistrationDate(\DateTime::createFromFormat('Y-m-d H:i:s', $registrationdate.' 00:00:00'));
        }
        else
        {
            $idp->setRegistrationDate(null);
        }
        $idp->setScope($scope);
        $idp->setDescription($description);
        if (isset($usestatic) && $usestatic == 'accept')
        {
            $idp->setStatic(true);
            log_message('debug', $pref . "static set: true");
        }
        else
        {
            $idp->setStatic(false);
            log_message('debug', $pref . "static set: false");
        }
        $e_static_metadata = $idp->getStaticMetadata();
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
        $s_metadata->setProvider($idp);
        $idp->setStaticMetadata($s_metadata);
        $this->em->persist($s_metadata);



        if (!empty($protocols) && is_array($protocols) && count($protocols) > 0)
        {
            log_message('debug', $pref . "setting protocols");
            $idp->resetProtocol();
            foreach ($protocols as $p)
            {
                $idp->setProtocol($p);
            }
        }
        //	print_r($nameids);
        //	return;
        if (!empty($nameids) && is_array($nameids) && count($nameids) > 0)
        {
            log_message('debug', $pref . "setting nameids");
            $idp->resetNameId();
            foreach ($nameids as $p)
            {
                $idp->setNameId($p);
            }
        }


        /**
         * certs if cert exists then cert[]=array('keyname'=>$keyname,'$certbody'=>$certbody)
         * where keyname and certbody 
         */
        log_message('debug', $pref . "setting certs");
        $certs = array();
        $existingCerts = $idp->getCertificates();
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
                    $idp->removeCertificate($ec);
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
        $cdata = $this->input->post('cert_0n_data');
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
                $cseencryption = false;
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
                    $newcert->setCertType($ctype);
                    if (!empty($cdata) && ($ctype == 'x509'))
                    {
                        if ($cusesigning === $cseencryption)
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
                    $newcert->setProvider($idp);
                    $newcert->setKeyname($ckeyname);
                    $idp->setCertificate($newcert);
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
        $contacts_col = $idp->getContacts();
        foreach ($contacts_col->getValues() as $c)
        {
            $type = $this->input->post('contact_' . $c->getId() . '_type');
            $fname = $this->input->post('contact_' . $c->getId() . '_fname');
            $sname = $this->input->post('contact_' . $c->getId() . '_sname');
            $email = $this->input->post('contact_' . $c->getId() . '_email');
            if (empty($email))
            {
                $idp->removeContact($c);
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
            $k->setProvider($idp);
            $idp->setContact($k);
            $this->em->persist($k);
        }





        $this->em->persist($idp);
        $state_after = $idp->__toString();
        $differ = $idp->diffProviderToArray($idp_before);

        $this->em->flush();

        if (count($differ) > 0)
        {
            $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($differ), true);
        }


        log_message('debug', $pref . " finished");
        redirect(base_url("manage/idp_edit/show/" . $editedidp . ""), 'refresh');


        $data['content_view'] = 'manage/idp_edit_view';
        $this->load->view('page', $data);
    }

}
