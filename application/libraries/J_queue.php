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
    private $attributesByName;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->tmp_providers = new models\Providers;
        $this->tmp_federations = new models\Federations;
        /**
         * @var $attrs models\Attribute[]
         */
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        foreach ($attrs as $a) {
            $this->attributesByName['' . $a->getOid() . ''] = $a;
        }
    }

    /**
     * @param $qid
     * @param bool $onlycancel
     * @return string
     */
    function displayFormsButtons($qid, $onlycancel = FALSE)
    {
        /* add approve form */
        $approveForm = '';
        $rejecttext = lang('rr_cancel');
        if (!$onlycancel) {
            $rejecttext = lang('rr_submitreject');
            $approveForm = form_open('reports/awaiting/approve', array('id' => 'approvequeue'), array('qaction' => 'approve', 'qid' => $qid, 'setfederation' => 'yes'));
            $approveForm .= '<button type="submit" name="mysubmit" value="Accept request!" class="savebutton saveicon right">' . lang('rr_submitapprove') . '</button>' . form_close();
        }

        /* add reject form */
        $reject_hidden_attributes = array('qaction' => 'reject', 'qid' => $qid);
        $reject_attrid = array('id' => 'rejectqueue');
        $rejectForm = form_open('reports/awaiting/reject', $reject_attrid, $reject_hidden_attributes);
        $rejectForm .= '<button type="submit" name="mysubmit" value="Reject request!" class="resetbutton reseticon left alert">' . $rejecttext . '</button>' . form_close();
        $result = '<div class="small-12 large-6 columns"><div class="buttons panel clearfix" >' . $rejectForm . '' . $approveForm . '</div></div>';
        return $result;
    }

    function createUserFromQueue(models\Queue $q)
    {
        $objdata = $q->getData();
        if (!is_array($objdata)) {
            log_message('error', __METHOD__ . ' data not in array');
            return false;
        }
        if (!isset($objdata['username']) || !isset($objdata['email']) || !isset($objdata['type'])) {
            log_message('error', __METHOD__ . ' data doesnt contain information about username/email');
            return false;
        }
        /**
         * @var $checkuser models\User
         */
        $checkuser = $this->em->createQuery("SELECT u FROM models\User u WHERE u.username = '{$objdata['username']}'")->getResult();
        if (!empty($checkuser)) {
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
        if (strcmp($type, 'federated') == 0) {
            $u->setFederatedEnabled();
        } elseif (strcmp($type, 'local') == 0) {
            $u->setLocalEnabled();
        } else {
            $u->setFederatedEnabled();
            $u->setLocalEnabled();
        }
        $u->setAccepted();
        if (!empty($objdata['fname'])) {
            $u->setGivenname($objdata['fname']);
        }
        if (!empty($objdata['sname'])) {
            $u->setSurname($objdata['sname']);
        }
        $u->setEnabled();
        $u->setSalt();
        if (!empty($objdata['pass'])) {
            $u->setPassword($objdata['pass']);
        } else {
            $u->setRandomPassword();
        }
        $u->setValid();
        $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Member'));
        if (!empty($member)) {
            $u->setRole($member);
        }
        $personalRole = new models\AclRole;
        $personalRole->setName($u->getUsername());
        $personalRole->setType('user');
        $personalRole->setDescription('personal role for user ' . $u->getUsername());
        $u->setRole($personalRole);
        $this->em->persist($personalRole);
        $this->em->persist($u);

        $mailSubject = 'User Registration';
        $mailBody = 'Dear user,' . PHP_EOL;
        $mailBody .= 'User registration request to use the service ' . base_url() . ' has been accepted' . PHP_EOL;
        $mailBody .= 'Details:' . PHP_EOL . 'Username: ' . $u->getUsername() . PHP_EOL;
        $mailBody .= 'E-mail: ' . $u->getEmail() . PHP_EOL;
        $recipient[] = $u->getEmail();
        $this->ci->email_sender->addToMailQueue(array(), null, $mailSubject, $mailBody, $recipient, $sync = false);
        return true;
    }

    private function genCocArray(models\Queue $q, $type)
    {
        if ($type === 'entcat') {
            $r = array(
                array('header' => lang('request')),
                array('name' => lang('type'), 'value' => lang('req_entcatapply')),
                array('name' => lang('rr_sourceip'), 'value' => $q->getIP())
            );
            $typeLabel = lang('entcat');
        } elseif ($type === 'regpol') {
            $r = array(
                array('header' => lang('request')),
                array('name' => lang('type'), 'value' => lang('req_reqpolapply')),
                array('name' => lang('rr_sourceip'), 'value' => $q->getIP())
            );
            $typeLabel = lang('rr_regpolicy');
        }
        $creator = $q->getCreator();
        if ($creator) {
            $r[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        } else {
            $r[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        $entityid = $q->getName();
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));

        if (!empty($provider)) {
            $r[] = array('name' => lang('rr_provider'), 'value' => $entityid);
        } else {

            $r[] = array('name' => lang('rr_provider'), 'value' => $entityid . ' <span class="label alert">' . lang('prov_notexist') . '</span>');
        }
        $entcatid = $q->getRecipient();
        $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $entcatid, 'type' => ''.$type.''));

        if (empty($coc)) {
            $r[] = array('name' => $typeLabel, 'value' => '<div data-alert class="alert-box alert">' . lang('regpol_notexist') . '</div>');
        } else {
            $lenabled = '';
            if (!$coc->getAvailable())  {
                $lenabled = '<span class="label alert">' . lang('rr_disabled') . '</span>';
            }
            $r[] = array('name' => $typeLabel, 'value' => '<span class="label info">' . $coc->getLang() . '</span> ' . $coc->getName() . ': ' . $coc->getUrl() . ' ' . $lenabled);
        }
        return $r;

    }

    function displayApplyForEntityCategory(models\Queue $q)
    {
        return $this->genCocArray($q,'entcat');
    }

    function displayApplyForRegistrationPolicy(models\Queue $q)
    {
        return $this->genCocArray($q,'regpol');
    }

    function displayRegisterUser(models\Queue $q)
    {
        $objdata = $q->getData();
        $r = array(
            array('header' => lang('request')),
            array('name' => lang('rr_sourceip'), 'value' => $q->getIP()),
            array('name' => lang('type'), 'value' => lang('req_userregistration')),
            array('name' => lang('rr_regdate'), 'value' => $q->getCreatedAt()),
            array('name' => lang('rr_username'), 'value' => $q->getName()),
            array('name' => lang('rr_uemail'), 'value' => $objdata['email']),
            array('name' => lang('rr_fname'), 'value' => $objdata['fname']),
            array('name' => lang('rr_lname'), 'value' => $objdata['sname'])
        );
        $creator = $q->getCreator();
        if ($creator) {
            $r[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        } else {
            $r[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        if (isset($objdata['ip'])) {
            $r[] = array('name' => 'IP', 'value' => $objdata['ip']);
        }
        if (isset($objdata['type'])) {
            if ($objdata['type'] === 'federated') {
                $r[] = array('name' => 'Type of account', 'value' => '' . lang('rr_onlyfedauth') . '');
            } elseif ($objdata['type'] === 'local') {
                $r[] = array('name' => 'Type of account', 'value' => '' . lang('rr_onlylocalauthn') . '');
            } elseif ($objdata['type'] === 'both') {
                $r[] = array('name' => 'Type of account', 'value' => '' . lang('rr_bothauth') . '');
            } else {
                $r[] = array('name' => 'Type of account', 'value' => '<span class="alert">' . lang('unknown') . '</span>');
            }
        }


        return $r;
    }

    /**
     * @param \models\Queue $q
     * @return array
     */
    function displayRegisterFederation(models\Queue $q)
    {
        $objData = new models\Federation;
        $objData->importFromArray($q->getData());
        $creator = $q->getCreator();
        if ($creator) {
            $row1 = array('name' => lang('requestor'), 'value' => $creator->getFullname() . ' (' . $creator->getUsername() . ')');
        } else {
            $row1 = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        $fedrows = array(
            array('header' => lang('request')),
            array('name' => lang('type'), 'value' => lang('reqregnewfed')),
            $row1,
            array('name' => lang('rr_sourceip'), 'value' => $q->getIP()),
            array('name' => lang('rr_regdate'), 'value' => $q->getCreatedAt()),
            array('header' => lang('rr_basicinformation')),
            array('name' => lang('rr_fed_name'), 'value' => $objData->getName()),
            array('name' => lang('fednameinmeta'), 'value' => $objData->getUrn()),
            array('name' => lang('Description'), 'value' => $objData->getDescription()),
            array('name' => lang('rr_fed_tou'), 'value' => $objData->getTou())
        );
        return $fedrows;
    }

    /**
     * @param \models\Queue $q
     * @return array
     */
    function displayDeleteFederation(models\Queue $q)
    {
        $objData = new models\Federation;
        $objData->importFromArray($q->getData());
        $creator = $q->getCreator();
        if ($creator) {
            $row1 = array('name' => lang('requestor'), 'value' => $creator->getUsername());
        } else {
            $row1 = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }
        $fedrows = array(
            array('header' => lang('request')),
            array('name' => lang('type'), 'value' => lang('reqdelfed')),
            $row1,
            array('name' => lang('rr_sourceip'), 'value' => $q->getIP()),
            array('name' => lang('rr_requestdate'), 'value' => $q->getCreatedAt()),
            array('header' => lang('rr_basicinformation')),
            array('name' => lang('rr_fed_name'), 'value' => $objData->getName()),
            array('name' => lang('fednameinmeta'), 'value' => $objData->getUrn())
        );
        return $fedrows;
    }

    function displayRegisterProvider(models\Queue $q)
    {
        $showXML = FALSE;
        $objData = null;
        $data = $q->getData();
        $objData = new models\Provider;
        if (!isset($data['metadata'])) {
            $objData->importFromArray($data);
        } else {
            $metadataXml = base64_decode($data['metadata']);
            $this->ci->load->library('xmlvalidator');
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = FALSE;
            $metadataDOM->WarningChecking = FALSE;
            $metadataDOM->loadXML($metadataXml);
            $isValid = $this->ci->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
            if (!$isValid) {
                log_message('error', __METHOD__ . ' invalid metadata in the queue ');
            } else {
                $this->ci->load->library('metadata2array');
                $xpath = new DomXPath($metadataDOM);
                $namespaces = h_metadataNamespaces();
                foreach ($namespaces as $key => $value) {
                    $xpath->registerNamespace($key, $value);
                }
                $domlist = $metadataDOM->getElementsByTagName('EntityDescriptor');
                if (count($domlist) == 1) {
                    foreach ($domlist as $l) {
                        $entarray = $this->ci->metadata2array->entityDOMToArray($l, TRUE);
                    }
                    $objData = new models\Provider;
                    $objData->setProviderFromArray(current($entarray), TRUE);
                    $objData->setReqAttrsFromArray(current($entarray), $this->attributesByName);
                    $metadataXML = $this->ci->providertoxml->entityConvertNewDocument($objData, array('attrs' => 1), true);
                    $showXML = TRUE;
                }
            }
        }
        $i = 0;
        $feds = $objData->getFederations();
        $fedIdsCollection = array();

        $dataRows[$i++]['header'] = lang('rr_details');
        $dataRows[$i]['name'] = lang('requestor');
        $creatorUN = 'anonymous';
        $creatorFN = 'Anonymous';
        $creator = $q->getCreator();
        if (!empty($creator)) {
            $creatorUN = $creator->getUsername();
            $creatorFN = $creator->getFullname();
        }
        $dataRows[$i++]['value'] = "$creatorFN ($creatorUN)";
        $dataRows[$i++] = array('name' => lang('rr_sourceip'), 'value' => $q->getIP());

        $dataRows[$i++]['header'] = lang('rr_fedstojoin');
        if ($feds->count() > 0) {

            foreach ($objData->getFederations() as $fed) {
                $realFed = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $fed->getSysname()));
                if (!empty($realFed)) {
                    $fedIdsCollection[] = $realFed->getId();
                }
                $dataRows[$i]['name'] = lang('rr_federation');
                $dataRows[$i]['value'] = $fed->getName();
                $i++;
            }
        } elseif (isset($data['federations'])) {
            foreach ($data['federations'] as $f) {
                $p = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $f['sysname']));
                if (!empty($p)) {
                    $fedIdsCollection[] = $p->getId();

                    $dataRows[$i]['name'] = lang('rr_federation');
                    $dataRows[$i]['value'] = $p->getName();
                    $i++;
                }
            }
        } else {
            $dataRows[$i++] = array('name' => '', 'value' => lang('noneatthemoment'));
        }

        /**
         * @todo show all fedvalidators which are assigned to federations
         */
        $valMandatory = null;
        $valOptional = null;
        $attrs = array('id' => 'fvform', 'style' => 'display: inline', 'class' => '');
        if (count($fedIdsCollection) > 0) {
            $validators = $this->em->getRepository("models\FederationValidator")->findBy(array('federation' => $fedIdsCollection, 'isEnabled' => true));
            foreach ($validators as $v) {
                if ($v->getMandatory()) {
                    $hidden = array('fedid' => $v->getFederation()->getId(), 'qtoken' => $q->getToken(), 'fvid' => $v->getId());
                    $valMandatory .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valMandatory .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '" name="mandatory">' . $v->getName() . '</button> ';
                    $valMandatory .= form_close();
                } else {
                    $hidden = array('fedid' => $v->getFederation()->getId(), 'qtoken' => $q->getToken(), 'fvid' => $v->getId());
                    $valOptional .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valOptional .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '">' . $v->getName() . '</button> ';
                    $valOptional .= form_close();
                }
            }
            $dataRows[$i++] = array('name' => lang('manValidator'), 'value' => $valMandatory);
            $dataRows[$i++] = array('name' => lang('optValidator'), 'value' => $valOptional);
            $resultValidation = '<div id="fvresult" style="display:none;" data-alert class="alert-box info"><div><b>' . lang('fvalidcodereceived') . '</b>: <span id="fvreturncode"></span></div><div><p><b>' . lang('fvalidmsgsreceived') . '</b>:</p><div id="fvmessages"></div></div></div>';
            $resultValidation .= '<div id="fvalidesc"></div>';
            $dataRows[$i++] = array('2cols' => $resultValidation);
        }


        $dataRows[$i++]['header'] = lang('rr_basicinformation');
        $dataRows[$i]['name'] = lang('rr_homeorganisationname');
        $dataRows[$i++]['value'] = $objData->getName();

        $dataRows[$i]['name'] = 'entityID';

        $dataRows[$i++]['value'] = $objData->getEntityId();
        $type = $objData->getType();
        if ($type === 'IDP') {
            $dataRows[$i]['name'] = lang('type');
            $dataRows[$i++]['value'] = lang('identityprovider');

            $dataRows[$i]['name'] = lang('rr_scope') . ' <br /><small>IDPSSODescriptor</small>';
            $dataRows[$i++]['value'] = implode(';', $objData->getScope('idpsso'));
        } elseif ($type === 'SP') {
            $dataRows[$i]['name'] = lang('type');
            $dataRows[$i++]['value'] = lang('serviceprovider');
        }

        $dataRows[$i]['name'] = lang('rr_helpdeskurl');
        $dataRows[$i++]['value'] = $objData->getHelpdeskUrl();


        $dataRows[$i++]['header'] = lang('rr_servicelocations');
        $servicetypesWithIndex = array('IDPArtifactResolutionService', 'DiscoveryResponse', 'AssertionConsumerService', 'SPArtifactResolutionService');
        foreach ($objData->getServiceLocations() as $service) {
            $serviceType = $service->getType();
            $dataRows[$i]['name'] = $serviceType;
            if (in_array($serviceType, $servicetypesWithIndex)) {
                $orderString = 'index: ' . $service->getOrder();
            } else {
                $orderString = '';
            }
            $dataRows[$i]['value'] = "" . $service->getUrl() . "<br /><small>" . $service->getBindingName() . " " . $orderString . " </small><br />";
            $i++;
        }
        $dataRows[$i++]['header'] = lang('rr_supportednameids');
        $dataRows[$i]['name'] = lang('nameid');
        if ($type === 'IDP') {
            $dataRows[$i++]['value'] = implode(', ', $objData->getNameIds('idpsso'));
        } elseif ($type === 'SP') {
            $dataRows[$i++]['value'] = implode(', ', $objData->getNameIds('spsso'));
        }


        $dataRows[$i++]['header'] = lang('rr_certificates');
        foreach ($objData->getCertificates() as $cert) {
            $dataRows[$i]['name'] = "Certificate (" . $cert->getCertUse() . ")";
            $certdatacell = reformatPEM($cert->getCertdata());


            $dataRows[$i]['value'] = "<span class=\"span-10\"><code>" . $certdatacell . "</code></span>";
            $i++;
        }

        $dataRows[$i++]['header'] = lang('rr_contacts');
        foreach ($objData->getContacts() as $contact) {
            $phone = $contact->getPhone();
            if (!empty($phone)) {
                $phoneStr = 'Tel:' . $phone;
            } else {
                $phoneStr = '';
            }
            $dataRows[$i]['name'] = lang('rr_contact') . ' (' . $contact->getType() . ')';
            $dataRows[$i]['value'] = $contact->getFullName() . " &lt;" . $contact->getEmail() . "&gt; " . $phoneStr;
            $i++;
        }
        if ($showXML) {
            $params = array(
                'enable_classes' => true,
            );

            $dataRows[$i]['name'] = 'XML';
            $this->ci->load->library('geshilib');
            $dataRows[$i]['value'] = '' . $this->ci->geshilib->highlight($metadataXML, 'xml', $params) . '';
        }
        return $dataRows;
    }

    function displayInviteProvider(models\Queue $queue)
    {

        $this->ci->load->library('table');
        if ($queue->getRecipientType() == 'provider') {
            $provider = $this->tmp_providers->getOneById($queue->getRecipient());
        }
        if (empty($provider)) {
            return false;
        }
        $tmpl = array('table_open' => '<table id="details" class="zebra">');
        $this->ci->table->set_template($tmpl);
        $this->ci->table->set_caption(lang('rr_requestawaiting'));


        $text = '<span style="white-space: normal">' . lang('adminoffed') . ': ' . $queue->getName() . ' ' . lang('invyourprov') . ': (' . $provider->getEntityId() . ')</span>';
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => lang('rr_details'), 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array(lang('requestor'), $queue->getCreator()->getUsername() . ' (' . $queue->getCreator()->getFullname() . ') : email: ' . $queue->getCreator()->getEmail());
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_sourceip'), '' . $queue->getIP() . '');
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_federation'), $queue->getName());
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_provider'), $provider->getName());
        $this->ci->table->add_row($cell);
        $cell = array(lang('request'), lang('joinfederation'));
        $this->ci->table->add_row($cell);
        $cell = array('data' => $this->displayFormsButtons($queue->getId()), 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $result = $this->ci->table->generate();
        $this->ci->table->clear();
        return $result;
    }

    function displayInviteFederation(models\Queue $queue, $canApprove = false)
    {

        $this->ci->load->library('table');
        $recipientType = $queue->getRecipientType();
        if (strcasecmp($recipientType, 'federation') == 0) {
            $federation = $this->tmp_federations->getOneFederationById($queue->getRecipient());
        }
        if (empty($federation)) {
            \log_message('error', __METHOD__ . ' Federation (' . $queue->getRecipient() . ') does not exist anymore');
            return false;
        }
        $tmpl = array('table_open' => '<table id="details" class="zebra">');
        $this->ci->table->set_template($tmpl);
        $this->ci->table->set_caption(lang('rr_requestawaiting'));
        $text = '<span style="white-space: normal">' . lang('adminofprov') . ': ' . $queue->getName() . ' ' . lang('askedyourfed') . ': (' . $federation->getName() . ')</span>';
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => lang('rr_details'), 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array(lang('requestor'), $queue->getCreator()->getFullname() . ' (' . $queue->getCreator()->getUsername() . ')');
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_sourceip'), $queue->getIP());
        $this->ci->table->add_row($cell);

        $data = $queue->getData();
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $data['entityid']));
        $validators = $federation->getValidators();
        $valMandatory = null;
        $valOptional = null;
        $attrs = array('id' => 'fvform', 'style' => 'display: inline', 'class' => '');
        foreach ($validators as $v) {
            if ($v->getEnabled()) {
                if ($v->getMandatory()) {
                    $hidden = array('fedid' => $federation->getId(), 'provid' => $provider->getId(), 'fvid' => $v->getId());
                    $valMandatory .= form_open(base_url() . 'federations/fvalidator/validate', $attrs, $hidden);
                    $valMandatory .= '<button id="' . $v->getId() . '" title="' . $v->getDescription() . '" name="mandatory">' . $v->getName() . '</button> ';
                    $valMandatory .= form_close();
                } else {
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
        $cell = array(lang('rr_entityid'), $data['entityid']);
        $this->ci->table->add_row($cell);


        $cell = array('Provider status', '<div  data-jagger-getmoreajax= "' . base_url() . 'providers/detail/status/' . $data['id'] . '" data-jagger-response-msg="providerstatus"></div><div id="providerstatus" data-alert class="alert-box info">' . lang('rr_noentitywarnings') . '</div>');
        $this->ci->table->add_row($cell);
        $cell = array(lang('request'), lang('acceptprovtofed'));
        $this->ci->table->add_row($cell);

        if (isset($data['message'])) {
            $cell = array(lang('rr_message'), $data['message']);
            $this->ci->table->add_row($cell);
        }
        $cell = array('data' => $this->displayFormsButtons($queue->getId(), !$canApprove), 'colspan' => 2);
        $this->ci->table->add_row($cell);
        # show additional information returned by validator
        $text = '<div id="fvresult" style="display:none;" data-alert class="alert-box info"><div><b>' . lang('fvalidcodereceived') . '</b>: <span id="fvreturncode"></span></div><div><p><b>' . lang('fvalidmsgsreceived') . '</b>:</p><div id="fvmessages"></div></div></div><div id="fvalidesc"></div>';
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $result = $this->ci->table->generate();
        $this->ci->table->clear();
        return $result;
    }

}
