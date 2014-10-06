<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * J_queue Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class J_queue
{

    private $ci;
    private $em;
    private $tmp_providers;
    private $tmp_federations;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->tmp_providers = new models\Providers;
        $this->tmp_federations = new models\Federations;
    }

    /**
     * generate approve/reject buttons for queue detail
     * @param type $qid
     * @return string 
     */
    function displayFormsButtons($qid, $onlycancel = FALSE)
    {
        /* add approve form */
        $rejecttext = lang('rr_submitreject');
        if (!$onlycancel)
        {
            $approve_hidden_attributes = array('qaction' => 'approve', 'qid' => $qid, 'setfederation' => 'yes');
            $approve_attrid = array('id' => 'approvequeue');
            $approve_form = form_open('reports/awaiting/approve', $approve_attrid, $approve_hidden_attributes);
            $approve_form .= '<button type="submit" name="mysubmit" value="Accept request!" class="savebutton saveicon right">' . lang('rr_submitapprove') . '</button>';
            $approve_form .= form_close();
        }
        else
        {
            $approve_form = '';
            $rejecttext = lang('rr_cancel');
        }
        /* add reject form */
        $reject_hidden_attributes = array('qaction' => 'reject', 'qid' => $qid);
        $reject_attrid = array('id' => 'rejectqueue');
        $reject_form = form_open('reports/awaiting/reject', $reject_attrid, $reject_hidden_attributes);
        $reject_form .= '<button type="submit" name="mysubmit" value="Reject request!" class="resetbutton reseticon left alert">' . $rejecttext . '</button>';
        $reject_form .= form_close();


        $result = '<div class="small-12 large-6 columns"><div class="buttons panel clearfix" >' . $reject_form . '' . $approve_form . '</div></div>';
        return $result;
    }

    function createUserFromQueue(models\Queue $q)
    {
        $objdata = $q->getData();
        if (!is_array($objdata))
        {
            log_message('error', __METHOD__ . ' data not in array');
            return false;
        }
        if (!isset($objdata['username']) || !isset($objdata['email']) || !isset($objdata['type']))
        {
            log_message('error', __METHOD__ . ' data doesnt contain information about username/email');
            return false;
        }
        $checkuser = $this->em->createQuery("SELECT u FROM models\User u WHERE u.username = '{$objdata['username']}' OR u.email = '{$objdata['email']}'")->getResult();


        if ($checkuser)
        {
            $this->ci->globalerrors[] = lang('useralredyregistered');
            $this->ci->globalerrors[] = lang('queremoved');
            log_message('error', __METHOD__ . ' User ' . $objdata['username'] . ' already exists, remove request from the queue with id: ' . $q->getId());
            $this->em->remove($q);
            $this->em->flush();
            return false;
        }
        $u = new models\User;
        $u->setUsername($objdata['username']);
        $u->setEmail($objdata['email']);
        $type = $objdata['type'];
        if (strcmp($type, 'federated') == 0)
        {
            $u->setFederatedEnabled();
        }
        else
        {
            if ($type === 'local')
            {
                $u->setLocalEnabled();
            }
            elseif ($type === 'both')
            {
                $u->setFederatedEnabled();
                $u->setLocalEnabled();
            }
        }
        $u->setAccepted();

        if (!empty($objdata['fname']))
        {
            $u->setGivenname($objdata['fname']);
        }
        if (!empty($objdata['sname']))
        {
            $u->setSurname($objdata['sname']);
        }
        $u->setEnabled();
        $u->setSalt();
        if (!empty($objdata['pass']))
        {
            $u->setPassword($objdata['pass']);
        }
        else
        {
            $u->setRandomPassword();
        }

        $u->setValid();
        $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Member'));
        if (!empty($member))
        {
            $u->setRole($member);
        }
        $p_role = new models\AclRole;
        $p_role->setName($u->getUsername());
        $p_role->setType('user');
        $p_role->setDescription('personal role for user ' . $u->getUsername());
        $u->setRole($p_role);
        $this->em->persist($p_role);
        $this->em->persist($u);

        $m_subj = 'User Registration';
        $m_body = 'Dear user,' . PHP_EOL;
        $m_body .= 'User registration request to use the service ' . base_url() . ' has been accepted' . PHP_EOL;
        $m_body .= 'Details:' . PHP_EOL;
        $m_body .= 'Username: ' . $u->getUsername() . PHP_EOL;
        $m_body .= 'E-mail: ' . $u->getEmail() . PHP_EOL;
        $reciepient[] = $u->getEmail();
        $this->ci->email_sender->addToMailQueue(array(), null, $m_subj, $m_body, $reciepient, $sync = false);
        return true;
    }

    function displayApplyForEntityCategory(models\Queue $q)
    {

        $result['entityid'] = $q->getName();
        $result['entcatid'] = $q->getRecipient();
        $r = array();
        $r[] = array('header' => lang('request'));

        $r[] = array('name' => lang('type'), 'value' => lang('req_entcatapply'));
        $creator = $q->getCreator();
        if ($creator)
        {
            $r[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        }
        else
        {
            $r[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        $entityid = $q->getName();
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));

        if (!empty($provider))
        {
            $r[] = array('name' => lang('rr_provider'), 'value' => $entityid);
        }
        else
        {

            $r[] = array('name' => lang('rr_provider'), 'value' => $entityid . ' <span class="label alert">' . lang('prov_notexist') . '</span>');
        }

        $entcatid = $q->getRecipient();
        $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $entcatid, 'type' => 'entcat'));
        $cocenabled = $coc->getAvailable();
        if ($cocenabled)
        {
            $lenabled = '';
        }
        else
        {
            $lenabled = '<span class="label alert">' . lang('rr_disabled') . '</span>';
        }
        if (empty($coc))
        {
            $r[] = array('name' => lang('entcat'), 'value' => '<div data-alert class="alert-box alert">' . lang('entcat_notexist') . '</div>');
        }
        else
        {
            $r[] = array('name' => lang('entcat'), 'value' => $lenabled . ' ' . $coc->getName() . ' ' . $coc->getUrl());
        }
        return $r;
    }

    function displayApplyForRegistrationPolicy(models\Queue $q)
    {

        $result['entityid'] = $q->getName();
        $result['entcatid'] = $q->getRecipient();
        $r = array();
        $r[] = array('header' => lang('request'));

        $r[] = array('name' => lang('type'), 'value' => lang('req_reqpolapply'));
        $creator = $q->getCreator();
        if ($creator)
        {
            $r[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        }
        else
        {
            $r[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        $entityid = $q->getName();
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));

        if (!empty($provider))
        {
            $r[] = array('name' => lang('rr_provider'), 'value' => $entityid);
        }
        else
        {

            $r[] = array('name' => lang('rr_provider'), 'value' => $entityid . ' <span class="label alert">' . lang('prov_notexist') . '</span>');
        }

        $entcatid = $q->getRecipient();
        $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $entcatid, 'type' => 'regpol'));
        $cocenabled = $coc->getAvailable();
        if ($cocenabled)
        {
            $lenabled = '';
        }
        else
        {
            $lenabled = '<span class="label alert">' . lang('rr_disabled') . '</span>';
        }
        if (empty($coc))
        {
            $r[] = array('name' => lang('rr_regpolicy'), 'value' => '<div data-alert class="alert-box alert">' . lang('regpol_notexist') . '</div>');
        }
        else
        {
            $r[] = array('name' => lang('rr_regpolicy'), 'value' => '<span class="label info">' . $coc->getLang() . '</span> ' . $coc->getName() . ': ' . $coc->getUrl() . ' ' . $lenabled);
        }
        return $r;
    }

    function displayRegisterUser(models\Queue $q)
    {
        $objdata = $q->getData();
        $r = array();
        $r[] = array('header' => lang('request'));
        $r[] = array('name' => lang('type'), 'value' => lang('req_userregistration'));
        $creator = $q->getCreator();
        if ($creator)
        {
            $r[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        }
        else
        {
            $r[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        $r[] = array('name' => lang('rr_regdate'), 'value' => $q->getCreatedAt());
        $r[] = array('name' => lang('rr_username'), 'value' => $q->getName());
        $r[] = array('name' => lang('rr_uemail'), 'value' => $objdata['email']);
        $r[] = array('name' => lang('rr_fname'), 'value' => $objdata['fname']);
        $r[] = array('name' => lang('rr_lname'), 'value' => $objdata['sname']);
        if (isset($objdata['ip']))
        {
            $r[] = array('name' => 'IP', 'value' => $objdata['ip']);
        }
        if (isset($objdata['type']))
        {
            if ($objdata['type'] === 'federated')
            {
                $r[] = array('name' => 'Type of account', 'value' => '' . lang('rr_onlyfedauth') . '');
            }
            elseif ($objdata['type'] === 'local')
            {
                $r[] = array('name' => 'Type of account', 'value' => '' . lang('rr_onlylocalauthn') . '');
            }
            elseif ($objdata['type'] === 'both')
            {
                $r[] = array('name' => 'Type of account', 'value' => '' . lang('rr_bothauth') . '');
            }
            else
            {
                $r[] = array('name' => 'Type of account', 'value' => '<span class="alert">' . lang('unknown') . '</span>');
            }
        }


        return $r;
    }

    function displayRegisterFederation(models\Queue $q)
    {
        $objData = new models\Federation;

        $objData->importFromArray($q->getData());



        $fedrows = array();
        $fedrows[] = array('header' => lang('request'));
        $fedrows[] = array('name' => lang('type'), 'value' => lang('reqregnewfed'));

        $creator = $q->getCreator();
        if ($creator)
        {
            $fedrows[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        }
        else
        {
            $fedrows[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }

        $fedrows[] = array('name' => lang('rr_regdate'), 'value' => $q->getCreatedAt());
        $fedrows[] = array('header' => lang('rr_basicinformation'));
        $fedrows[] = array('name' => lang('rr_fed_name'), 'value' => $objData->getName());
        $fedrows[] = array('name' => lang('fednameinmeta'), 'value' => $objData->getUrn());
        $fedrows[] = array('name' => lang('Description'), 'value' => $objData->getDescription());
        $fedrows[] = array('name' => lang('rr_fed_tou'), 'value' => $objData->getTou());

        return $fedrows;
    }

    function displayDeleteFederation(models\Queue $q)
    {
        $objData = new models\Federation;

        $objData->importFromArray($q->getData());



        $fedrows = array();
        $fedrows[] = array('header' => lang('request'));
        $fedrows[] = array('name' => lang('type'), 'value' => lang('reqdelfed'));

        $creator = $q->getCreator();
        if ($creator)
        {
            $fedrows[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        }
        else
        {
            $fedrows[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }

        $fedrows[] = array('name' => lang('rr_requestdate'), 'value' => $q->getCreatedAt());
        $fedrows[] = array('header' => lang('rr_basicinformation'));
        $fedrows[] = array('name' => lang('rr_fed_name'), 'value' => $objData->getName());
        $fedrows[] = array('name' => lang('fednameinmeta'), 'value' => $objData->getUrn());

        return $fedrows;
    }

    function displayRegisterProvider(models\Queue $q)
    {
        $showXML = FALSE;
        $objData = null;
        $data = $q->getData();
        $objType = $q->getObjType();
        $objData = new models\Provider;
        if (!isset($data['metadata']))
        {
            $objData->importFromArray($data);
        }
        else
        {
            $metadataXml = base64_decode($data['metadata']);
            $this->ci->load->library('xmlvalidator');
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = FALSE;
            $metadataDOM->WarningChecking = FALSE;
            $metadataDOM->loadXML($metadataXml);
            $isValid = $this->ci->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
            if (!$isValid)
            {
                log_message('error', __METHOD__ . ' invalid metadata in the queue ');
            }
            else
            {
                $this->ci->load->library('metadata2array');
                $xpath = new DomXPath($metadataDOM);
                $namespaces = h_metadataNamespaces();
                foreach ($namespaces as $key => $value)
                {
                    $xpath->registerNamespace($key, $value);
                }
                $domlist = $metadataDOM->getElementsByTagName('EntityDescriptor');
                if (count($domlist) == 1)
                {
                    $d = array();
                    foreach ($domlist as $l)
                    {
                        $entarray = $this->ci->metadata2array->entityDOMToArray($l, TRUE);
                    }
                    $objData = new models\Provider;
                    $objData->setProviderFromArray(current($entarray), TRUE);
                    $y = $objData->getProviderToXML();
                    $y->formatOutput = true;
                    $metadataXML = $y->saveXML();
                    $showXML = TRUE;
                }
            }
        }
        $i = 0;
        $feds = $objData->getFederations();
        $fedIdsCollection = array();

        $dataRows[$i++]['header'] = lang('rr_fedstojoin');
        if ($feds->count() > 0)
        {

            foreach ($objData->getFederations() as $fed)
            {
                $realFed = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $fed->getSysname()));
                if (!empty($realFed))
                {
                    $fedIdsCollection[] = $realFed->getId();
                }
                $dataRows[$i]['name'] = lang('rr_federation');
                $dataRows[$i]['value'] = $fed->getName();
                $i++;
            }
        }
        elseif (isset($data['federations']))
        {
            foreach ($data['federations'] as $f)
            {
                $p = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $f['sysname']));
                if (!empty($p))
                {
                    $fedIdsCollection[] = $p->getId();

                    $dataRows[$i]['name'] = lang('rr_federation');
                    $dataRows[$i]['value'] = $p->getName();
                    $i++;
                }
            }
        }
        else
        {
            $dataRows[$i++] = array('name' => '','value' => lang('noneatthemoment'));
        }
        
        /**
         * @todo show all fedvalidators which are assigned to federations
         */
        $valMandatory = null;
        $valOptional = null;
        $attrs = array('id' => 'fvform', 'style' => 'display: inline', 'class' => '');
        if (count($fedIdsCollection) > 0)
        {
            $validators = $this->em->getRepository("models\FederationValidator")->findBy(array('federation' => $fedIdsCollection, 'isEnabled' => true));
            foreach ($validators as $v)
            {
                if ($v->getMandatory())
                {
                    $hidden = array('fedid' => $v->getFederation()->getId(), 'qtoken' => $q->getToken(), 'fvid' => $v->getId());
                    $valMandatory .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valMandatory .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '">' . $v->getName() . '</button> ';
                    $valMandatory .= form_close();
                }
                else
                {
                    $hidden = array('fedid' => $v->getFederation()->getId(), 'qtoken' => $q->getToken(), 'fvid' => $v->getId());
                    $valOptional .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valOptional .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '">' . $v->getName() . '</button> ';
                    $valOptional .= form_close();
                }
            }
            $dataRows[$i++] = array('name' => lang('manValidator'),'value' => $valMandatory);
            $dataRows[$i++] = array('name' => lang('optValidator'),'value' => $valOptional);
            $resultValidation = '<div id="fvresult" style="display:none;" data-alert class="alert-box info"><div><b>' . lang('fvalidcodereceived') . '</b>: <span id="fvreturncode"></span></div><div><p><b>' . lang('fvalidmsgsreceived') . '</b>:</p><div id="fvmessages"></div></div></div>';
            $resultValidation .= '<div id="fvalidesc"></div>';
            $dataRows[$i++] = array('2cols'=>$resultValidation);
        }



        $dataRows[$i++]['header'] = lang('rr_basicinformation');
        $dataRows[$i]['name'] = lang('rr_homeorganisationname');
        $dataRows[$i++]['value'] = $objData->getName();

        $dataRows[$i]['name'] = 'entityID';

        $dataRows[$i++]['value'] = $objData->getEntityId();
        $type = $objData->getType();
        if ($type === 'IDP')
        {
            $dataRows[$i]['name'] = lang('type');
            $dataRows[$i++]['value'] = lang('identityprovider');

            $dataRows[$i]['name'] = lang('rr_scope') . ' <br /><small>IDPSSODescriptor</small>';
            $dataRows[$i++]['value'] = implode(';', $objData->getScope('idpsso'));
        }
        elseif ($type === 'SP')
        {
            $dataRows[$i]['name'] = lang('type');
            $dataRows[$i++]['value'] = lang('serviceprovider');
        }

        $dataRows[$i]['name'] = lang('rr_helpdeskurl');
        $dataRows[$i++]['value'] = $objData->getHelpdeskUrl();


        $dataRows[$i++]['header'] = lang('rr_servicelocations');
        $servicetypesWithIndex = array('IDPArtifactResolutionService', 'DiscoveryResponse', 'AssertionConsumerService', 'SPArtifactResolutionService');
        foreach ($objData->getServiceLocations() as $service)
        {
            $serviceType = $service->getType();
            $dataRows[$i]['name'] = $serviceType;
            if (in_array($serviceType, $servicetypesWithIndex))
            {
                $orderString = 'index: ' . $service->getOrder();
            }
            else
            {
                $orderString = '';
            }
            $dataRows[$i]['value'] = "" . $service->getUrl() . "<br /><small>" . $service->getBindingName() . " " . $orderString . " </small><br />";
            $i++;
        }
        $dataRows[$i++]['header'] = lang('rr_supportednameids');
        $dataRows[$i]['name'] = lang('nameid');
        if ($type === 'IDP')
        {
            $dataRows[$i++]['value'] = implode(', ', $objData->getNameIds('idpsso'));
        }
        elseif ($type === 'SP')
        {
            $dataRows[$i++]['value'] = implode(', ', $objData->getNameIds('spsso'));
        }



        $dataRows[$i++]['header'] = lang('rr_certificates');
        foreach ($objData->getCertificates() as $cert)
        {
            $dataRows[$i]['name'] = "Certificate (" . $cert->getCertUse() . ")";
            $certdatacell = reformatPEM($cert->getCertdata());


            $dataRows[$i]['value'] = "<span class=\"span-10\"><code>" . $certdatacell . "</code></span>";
            $i++;
        }

        $dataRows[$i++]['header'] = lang('rr_contacts');
        foreach ($objData->getContacts() as $contact)
        {
            $phone = $contact->getPhone();
            if (!empty($phone))
            {
                $phoneStr = 'Tel:' . $phone;
            }
            else
            {
                $phoneStr = '';
            }
            $dataRows[$i]['name'] = lang('rr_contact') . ' (' . $contact->getType() . ')';
            $dataRows[$i]['value'] = $contact->getFullName() . " &lt;" . $contact->getEmail() . "&gt; " . $phoneStr;
            $i++;
        }
        if ($showXML)
        {
            $params = array(
                'enable_classes' => true,
            );

            $dataRows[$i]['name'] = 'XML';
            $this->ci->load->library('geshilib');
            $dataRows[$i]['value'] = '' . $this->ci->geshilib->highlight($metadataXML, 'xml', $params) . '';
            $i++;
        }
        return $dataRows;
    }

    function displayInviteProvider(models\Queue $queue)
    {

        $this->ci->load->library('table');
        if ($queue->getRecipientType() == 'provider')
        {
            $provider = $this->tmp_providers->getOneById($queue->getRecipient());
        }
        if (empty($provider))
        {
            return false;
        }
        $tmpl = array('table_open' => '<table id="details" class="zebra">');
        $this->ci->table->set_template($tmpl);
        $this->ci->table->set_caption(lang('rr_requestawaiting'));


        $text = '<span style="white-space: normal">' . lang('adminoffed') . ': ' . $queue->getName() . ' ' . lang('invyourprov') . ': (' . $provider->getEntityId() . ')';
        $text .= "</span>";
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => lang('rr_details'), 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array(lang('requestor'), $queue->getCreator()->getUsername() . ' (' . $queue->getCreator()->getFullname() . ') : email: ' . $queue->getCreator()->getEmail());
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_federation'), $queue->getName());
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_provider'), $provider->getName());
        $this->ci->table->add_row($cell);
        $cell = array(lang('request'), lang('joinfederation'));
        $this->ci->table->add_row($cell);
        $cell = array('data' => $this->displayFormsButtons($queue->getId()), 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $result = '';
        $result .= $this->ci->table->generate();
        $result .= '';
        $this->ci->table->clear();
        return $result;
    }

    function displayInviteFederation(models\Queue $queue, $canApprove = false)
    {

        $this->ci->load->library('table');
        $recipientType = $queue->getRecipientType();
        if (strcasecmp($recipientType, 'federation') == 0)
        {
            $federation = $this->tmp_federations->getOneFederationById($queue->getRecipient());
        }
        if (empty($federation))
        {
            \log_message('error', __METHOD__ . ' Federation (' . $queue->getRecipient() . ') does not exist anymore');
            return false;
        }
        $tmpl = array('table_open' => '<table id="details" class="zebra">');
        $this->ci->table->set_template($tmpl);
        $this->ci->table->set_caption(lang('rr_requestawaiting'));


        $text = '<span style="white-space: normal">' . lang('adminofprov') . ': ' . $queue->getName() . ' ' . lang('askedyourfed') . ': (' . $federation->getName() . ')';
        $text .= "</span>";
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => lang('rr_details'), 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array(lang('requestor'), $queue->getCreator()->getUsername() . ' (' . $queue->getCreator()->getFullname() . ') : email: ' . $queue->getCreator()->getEmail());
        $this->ci->table->add_row($cell);
        $validators = $federation->getValidators();
        $fedValidator = null;
        foreach ($validators as $v)
        {
            $g = $v->getEnabled();
            if ($g)
            {
                $fedValidator = $v;
                break;
            }
        }
        if ($fedValidator)
        {
            $nname = $fedValidator->getName();
        }
        else
        {
            $nname = '';
        }
        $data = $queue->getData();
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $data['entityid']));
        $validators = $federation->getValidators();
        $valMandatory = null;
        $valOptional = null;
        $attrs = array('id' => 'fvform', 'style' => 'display: inline', 'class' => '');
        foreach ($validators as $v)
        {
            if ($v->getEnabled())
            {
                if ($v->getMandatory())
                {
                    $hidden = array('fedid' => $federation->getId(), 'provid' => $provider->getId(), 'fvid' => $v->getId());
                    $valMandatory .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valMandatory .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '">' . $v->getName() . '</button> ';
                    $valMandatory .= form_close();
                }
                else
                {
                    $hidden = array('fedid' => $federation->getId(), 'provid' => $provider->getId(), 'fvid' => $v->getId());
                    $valOptional .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valOptional .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '">' . $v->getName() . '</button> ';
                    $valOptional .= form_close();
                }
            }
        }
        $cell = array(lang('manValidator'), $valMandatory);
        $this->ci->table->add_row($cell);
        $cell = array(lang('optValidator'), $valOptional);
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_federation'), $federation->getName() . ' ');
        $this->ci->table->add_row($cell);
        $data = $queue->getData();
        $cell = array(lang('rr_provider'), $data['name']);
        $this->ci->table->add_row($cell);
        $cell = array(lang('request'), lang('acceptprovtofed'));
        $this->ci->table->add_row($cell);

        if (isset($data['message']))
        {
            $cell = array(lang('rr_message'), $data['message']);
            $this->ci->table->add_row($cell);
        }
        $cell = array('data' => $this->displayFormsButtons($queue->getId(), !$canApprove), 'colspan' => 2);
        $this->ci->table->add_row($cell);
        # show additional information returned by validator
        $text = '<div id="fvresult" style="display:none;" data-alert class="alert-box info"><div><b>' . lang('fvalidcodereceived') . '</b>: <span id="fvreturncode"></span></div><div><p><b>' . lang('fvalidmsgsreceived') . '</b>:</p><div id="fvmessages"></div></div></div>';
        $text .= '<div id="fvalidesc"></div>';
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $result = '';
        $result .= $this->ci->table->generate();
        $result .= '';
        $this->ci->table->clear();
        return $result;
    }

}
