<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * ResourceRegistry3
 *
 * @package   RR3
 * @author    Middleware Team HEAnet
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Awaiting extends MY_Controller
{

    private $error_message;

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'cert'));
        $this->load->library(array('table', 'tracker', 'jqueueaccess'));
        $this->title = lang('title_approval');
    }

    /**
     * @return CI_Output
     */
    public function dashz() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(400)->set_output('Incorrect request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Not authenticated');
        }
        $this->load->library(array('zacl', 'j_queue'));
        $result['data'] = $this->getQueueList();

        return $this->output->set_content_type('application/json')->set_status_header(200)->set_output(json_encode($result));

    }

    public function ajaxrefresh() {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }
        $this->load->library(array('zacl', 'j_queue'));
        $data = array(
            'list' => $this->getQueueList(),
            'error_message' => $this->session->flashdata('error_message'),
            'content_view' => 'reports/awaiting_list_view'
        );
        $this->load->view('reports/awaiting_list_view', $data);
    }

    private function getQueueList() {
        $userid = $this->session->userdata('user_id');
        $cached = $this->j_ncache->getUserQList($userid);
        if ($cached) {
            return $cached;
        }
        $this->load->library(array('zacl', 'j_queue', 'jqueueaccess'));
        /**
         * @var  models\Queue[] $queueArray
         */
        $queueArray = $this->em->getRepository("models\Queue")->findAll();
        $result = array('q' => array(), 's' => array());

        foreach ($queueArray as $q) {
            $cUsername = 'anonymous';
            $cCN = 'Anonymous';
            $creator = $q->getCreator();
            $access = $this->jqueueaccess->hasQAccess($q);
            if (!$access) {
                continue;
            }
            if ($creator !== null) {
                $cUsername = $creator->getUsername();
                $cCN = $creator->getFullname();
            }
            $recipientid = $q->getRecipient();
            $recipenttype = $q->getRecipientType();
            $recipientname = '';
            if (strcasecmp($recipenttype, 'provider') == 0) {
                /**
                 * @var models\Provider $p
                 */
                $p = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $recipientid));
                if ($p !== null) {
                    $recipientname = $p->getName();
                }
            }
            if ($access) {
                $result['q'][] = array(
                    'issubscription' => 0,
                    'requester' => $cUsername,
                    'requesterCN' => $cCN,
                    'idate' => $q->getCreatedAt(),
                    'datei' => $q->getCreatedAt(),
                    'iname' => $q->getCN(),
                    'qid' => $q->getId(),
                    'mail' => $q->getEmail(),
                    'type' => $q->getType(),
                    'action' => $q->getAction(),
                    'recipientname' => $recipientname,
                    'token' => $q->getToken(),
                    'confirmed' => $q->getConfirm()
                );

            }
        }
        $subscriptions = $this->em->getRepository('models\NotificationList')->findBy(array('is_approved' => '0', 'is_enabled' => '1'));
        $isAdmin = $this->jauth->isAdministrator();
        if ($isAdmin) {

            foreach ($subscriptions as $s) {
                $result['s'][] = array(
                    'subscriber' => $s->getSubscriber()->getUsername(),
                    'subscriber_email' => $s->getSubscriber()->getEmail(),
                    'type' => lang($s->getType()),
                    'url' => base_url('notifications/subscriber/mysubscriptions/' . base64url_encode($s->getSubscriber()->getUsername()))
                );

            }
        }
        $this->j_ncache->saveUserQList($this->session->userdata('user_id'), $result);

        return $result;
    }

    private function detailProvider(models\Queue $qObject) {
        $objAction = $qObject->getAction();
        $objRecipientType = $qObject->getRecipientType();
        if (strcasecmp($objAction, 'Create') == 0) {
            $objData = new models\Provider;
            $objData->importFromArray($qObject->getData());
            /* build table with details */
            $result = array(
                'data' => array(
                    'provider' => $this->j_queue->displayRegisterProvider($qObject),
                    'obj' => $objData,
                    'error_message' => $this->error_message,
                    'content_view' => 'reports/awaiting_provider_register_view'
                ),
                'content_view' => 'reports/awaiting_provider_register_view'
            );

            return $result;
        }
        if (strcasecmp($objAction, 'Join') == 0 && strcasecmp($objRecipientType, 'federation') == 0) {
            $recipientWriteAccess = $this->zacl->check_acl('f_' . $qObject->getRecipient(), 'write', 'federation', '');
            $requestorViewAccess = (strcasecmp($qObject->getCreator()->getUsername(), $this->jauth->getLoggedinUsername()) == 0);
            if ($requestorViewAccess || $recipientWriteAccess) {

                $result = $this->j_queue->displayInviteFederation($qObject, $recipientWriteAccess);
                if (!empty($result)) {
                    $data['result'] = $result;
                } else {
                    $data['error_message'] = "Couldn't load request details";
                }
            } else {
                $data['error_message'] = lang('rerror_noperm_viewqueuerequest');
            }
            $data['content_view'] = 'reports/awaiting_invite_federation_view';
            $r['data'] = $data;

            return $r;
        }

        return null;
    }

    public function detail($token) {
        if (!ctype_alnum($token)) {
            show_error('Wrong token provided', 404);
        }
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        /**
         * @var $queueObject models\Queue
         */
        try {
            $this->load->library(array('zacl', 'j_queue', 'providertoxml'));
            $queueObject = $this->em->getRepository("models\Queue")->findOneBy(array('token' => $token));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }

        $breadcrumbs = array(
            array('url' => base_url('reports/awaiting'), 'name' => '' . lang('rr_listawaiting') . ''),
            array('url' => '#', 'name' => lang('rr_requestawaiting'), 'type' => 'current'),
        );

        if ($queueObject === null) {
            return $this->load->view(MY_Controller::$page, array(
                'content_view' => 'error_message',
                'error_message' => lang('rerror_qid_noexist')
            ));
        }

        if (!$this->jqueueaccess->hasQAccess($queueObject)) {
            return $this->load->view(MY_Controller::$page, array(
                'content_view' => 'nopermission',
                'error' => lang('rr_nopermission')
            ));
        }
        $approveaccess = $this->jqueueaccess->hasApproveAccess($queueObject);

        $buttons = $this->j_queue->displayFormsButtons($queueObject->getId(), !$approveaccess);

        $objType = $queueObject->getObjType();
        $objAction = $queueObject->getAction();
        $recipientType = $queueObject->getRecipientType();

        if (strcasecmp($objType, 'Provider') == 0) {
            $result = $this->detailProvider($queueObject);
            $result['data']['provider'][]['2cols'] = $this->j_queue->queueRegProviderButtons($queueObject->getId(), !$approveaccess);

        } elseif (strcasecmp($objType, 'Federation') == 0) {
            $result = $this->j_queue->detailFederation($queueObject);
        } elseif (strcasecmp($objType, 'User') == 0 && strcasecmp($objAction, 'Create') == 0) {
            $result['data'] = array(
                'requestdata' => $this->j_queue->displayRegisterUser($queueObject),
                'content_view' => 'reports/awaiting_detail_view',
                'error_message' => $this->error_message
            );
            $result['data']['requestdata'][]['2cols'] = $buttons;

        } elseif (strcasecmp($objType, 'n') == 0) {
            if (strcasecmp($objAction, 'apply') == 0 && strcasecmp($recipientType, 'entitycategory') == 0) { // apply for entity category
                $result['data'] = array(
                    'requestdata' => $this->j_queue->displayApplyForEntityCategory($queueObject),
                    'content_view' => 'reports/awaiting_detail_view',

                );
                $result['data']['requestdata'][]['2cols'] = $buttons;

            } elseif (strcasecmp($objAction, 'apply') == 0 && strcasecmp($recipientType, 'regpolicy') == 0) { // apply for entity category
                $result['data'] = array(
                    'requestdata' => $this->j_queue->displayApplyForRegistrationPolicy($queueObject),
                    'content_view' => 'reports/awaiting_detail_view'
                );
                $result['data']['requestdata'][]['2cols'] = $buttons;

            } elseif (strcasecmp($objAction, 'UPDATE') == 0 && strcasecmp($recipientType, 'provider') == 0) {
                $result['data'] = array(
                    'requestdata' => $this->j_queue->displayUpdateProvider($queueObject),
                    'content_view' => 'reports/awaiting_detail_view');
            }
        } else {
            return $this->load->view(MY_Controller::$page, array(
                'content_view' => 'nopermission',
                'error' => 'no permission or unkown error',
            ));
        }

        $result['data']['breadcrumbs'] = $breadcrumbs;

        $this->load->view(MY_Controller::$page, $result['data']);
    }

    private function deleteFederation(\models\Queue $q) {
        $qFed = new models\Federation;
        $qFed->importFromArray($q->getData());
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $qFed->getName()));
        if (empty($federation)) {
            $this->error_message = 'Federation not found';

            return false;
        }
        $isActive = $federation->getActive();
        if ($isActive) {
            $this->error_message = 'Federation is active , cannot delete';

            return false;
        }
        $this->load->library('FederationRemover');
        $sbj = 'Federation has been removed';
        $body = 'Dear user,' . PHP_EOL . 'Federation : ' . $federation->getName() . ' has been removed from the system';
        $this->emailsender->addToMailQueue(array(), null, $sbj, $body, array(), false);
        $this->federationremover->removeFederation($federation);
        $this->em->remove($q);
        $this->tracker->save_track('sys', null, null, 'approved - remove fed: ' . $federation->getName() . '', false);
        try {
            $this->em->flush();
            log_message('info', 'JAGGER: ' . __METHOD__ . ' ' . $this->session->userdata('username') . ' : approved - remove fed:' . $federation->getName() . '');

            return true;
        } catch (Exception $e) {
            $this->error_message = 'Error ocurrred during Federation removal from database';
            log_message('error', __METHOD__ . ' ' . $e);

            return false;
        }
    }

    private function createProvider(\models\Queue $q, $accessLevel = null) {
        /**
         * @var $attrs models\Attribute[]
         */
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        foreach ($attrs as $a) {
            $attributesByName['' . $a->getOid() . ''] = $a;
        }
        $d = $q->getData();
        if (!isset($d['metadata'])) {
            $entity = new models\Provider;
            $entity->importFromArray($d);
        } else {
            $this->load->library(array('xmlvalidator', 'metadata2array'));
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = false;
            $metadataDOM->WarningChecking = false;
            $metadataDOM->loadXML(base64_decode($d['metadata']));
            $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, false, false);
            if (!$isValid) {
                $this->error_message = 'Invalid metadata';

                return false;
            }
            $xpath = new DomXPath($metadataDOM);
            $namespaces = h_metadataNamespaces();
            foreach ($namespaces as $key => $value) {
                $xpath->registerNamespace($key, $value);
            }
            $domlist = $metadataDOM->getElementsByTagName('EntityDescriptor');
            if (count($domlist) != 1) {
                $this->error_message = 'Invalid metadata. None or more than one EntityDescriptor found in the raw xml';

                return false;
            }
            foreach ($domlist as $l) {
                $entarray = $this->metadata2array->entityDOMToArray($l, true);
            }
            $entity = new models\Provider;
            $entity->setProviderFromArray(current($entarray), true);
            $entity->setReqAttrsFromArray(current($entarray), $attributesByName);
            $entity->setActive(true);
            $entity->setStatic(false);
            if (isset($d['federations'])) {
                $fe = $entity->getFederations();
                if ($fe->count() == 0) {
                    foreach ($d['federations'] as $g) {
                        /**
                         * @var $gg models\Federation
                         */
                        $gg = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $g['sysname']));
                        if (!empty($gg)) {
                            if ($gg->isJoinAllowedForNew()) {
                                $membership = new models\FederationMembers;
                                $membership->setJoinState('1');
                                $membership->setProvider($entity);
                                $membership->setFederation($gg);
                                $entity->getMembership()->add($membership);
                                $this->j_ncache->cleanFederationMembers($gg->getId());
                            }
                        }
                    }
                }
            }
        }
        $entityExists = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entity->getEntityId()));
        if (!empty($entityExists)) {
            $this->error_message = "Provider " . $entity->getName() . " (" . $entity->getEntityId() . ") already exists";

            return false;
        }
        $entity->setAsLocal();
        $fed = $entity->getFederations()->get(0);
        if (!empty($fed)) {
            $fed2 = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
            $entity->removeFederation($fed);
            $this->j_ncache->cleanFederationMembers($fed->getId());
        }
        foreach ($entity->getCertificates() as $o) {
            $o->setCertdata(reformatPEM($o->getCertdata()));
            $o->setCertType('x509');
        }
        if (!empty($fed2) && $fed instanceOf models\Federation) {
            $membership = new models\FederationMembers;
            $membership->setJoinState('1');
            $membership->setProvider($entity);
            $membership->setFederation($fed2);
            $this->em->persist($membership);
        }
        $dateNow = new \DateTime("now");
        $entity->setRegistrationDate($dateNow);
        $this->em->persist($entity);

        $rEntityID = $entity->getEntityId();
        $rUsername = null;

        $creator = $q->getCreator();
        $this->em->remove($q);
        $requester_recipient = null;
        if (!empty($creator) && ($creator instanceOf models\User)) {
            $requester_recipient = $creator->getEmail();
            $rUsername = $creator->getUsername();
        }
        if (empty($requester_recipient)) {
            $requester_recipient = $q->getEmail();
        }
        $sbj = 'Identity/Service Provider has been approved';
        $body = 'Dear user,' . PHP_EOL . 'Registration request: ' . $entity->getName() . ' (' . $entity->getEntityId() . ')' . PHP_EOL;
        $body .= 'Requested by: ' . $requester_recipient . '' . PHP_EOL . 'Request has been just approved by ' . $this->jauth->getLoggedinUsername() . ' and added to the system' . PHP_EOL;
        $body .= 'It can be reviewed on ' . base_url() . ' ' . PHP_EOL;
        $additionalReceipents = array();
        $toNotifyRequester = $this->config->item('notify_requester_if_queue_accepted');
        if (!empty($toNotifyRequester)) {
            $additionalReceipents[] = $requester_recipient;
        }
        $this->emailsender->addToMailQueue(array('greqisterreq', 'gidpregisterreq'), null, $sbj, $body, $additionalReceipents, false);
        $this->tracker->save_track('sys', null, null, 'approved - provider reg req: ' . $entity->getEntityId() . '', false);
        $this->load->library('j_ncache');
        try {
            $this->em->flush();
            $this->j_ncache->cleanProvidersList('idp');
            $this->j_ncache->cleanProvidersList('sp');


        } catch (Exception $e) {
            $this->error_message = 'Error occured during storing data in database';
            log_message('error', __METHOD__ . ' ' . $e);

            return false;
        }

        /**
         * @var models\Provider $rEntity
         */
        $rEntity = $this->em->getRepository('models\Provider')->findOneBy(array('entityid' => $rEntityID));
        if ($rEntity !== null && $rUsername !== null && $accessLevel !== null) {
            $this->zacl->initiateAcls();
            if ($accessLevel === 'write') {
                $this->zacl->add_access_toUser($rEntity->getId(), 'write', $rUsername, 'entity', null);
                $this->zacl->add_access_toUser($rEntity->getId(), 'read', $rUsername, 'entity', null);
            } elseif ($accessLevel === 'manage') {
                $this->zacl->add_access_toUser($rEntity->getId(), 'manage', $rUsername, 'entity', null);
                $this->zacl->add_access_toUser($rEntity->getId(), 'write', $rUsername, 'entity', null);
                $this->zacl->add_access_toUser($rEntity->getId(), 'read', $rUsername, 'entity', null);
            }
            $this->em->flush();
        }

        return true;
    }

    public function approve() {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $isAdministrator = $this->jauth->isAdministrator();
        $this->load->library(array('zacl', 'j_queue'));
        $message = "";
        $error_message = null;
        $qaction = trim($this->input->post('qaction'));
        $qaccessLevel = $this->input->post('accesslevel');
        $qid = trim($this->input->post('qid'));
        if (empty($qaction) || strcmp($qaction, 'approve') != 0) {
            log_message('debug', $message);
            $this->session->set_flashdata('message', $message);
            $this->session->set_flashdata('error_message', $error_message);
            redirect(base_url() . "reports/awaiting", 'location');

            return;
        }
        /**
         * @var $queueObj models\Queue
         */
        if (!empty($qid) || ctype_digit($qid)) {
            $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $qid));
        }
        if (empty($queueObj)) {
            $message = $this->input->server('REQUEST_URI');
            $message .= ' id=' . $this->input->post('qid') . ' doesnt exist in queue';
            log_message('debug', $message);
            $dataview = array(
                'error_message' => 'It cannot be approved because it does not exist',
                'content_view' => 'error_message'
            );

            return $this->load->view(MY_Controller::$page, $dataview);
        }
        $queueAction = $queueObj->getAction();
        $queueObjType = $queueObj->getType();
        $recipient = $queueObj->getRecipient();
        $recipienttype = $queueObj->getRecipientType();
        $allowedActionsAndTypes['Create']['User'] = array(
            'access' => $this->jqueueaccess->hasApproveAccess($queueObj),
            'fnameAction' => 'createUserFromQueue',
        );
        $allowedActionsAndTypes['Create']['IDP'] = array(
            'access' => $this->zacl->check_acl('idp', 'create', 'entity', ''),
        );
        $allowedActionsAndTypes['Create']['SP'] = array();
        $allowedActionsAndTypes['Delete']['Federation'] = array();
        $allowedActionsAndTypes['Create']['Federation'] = array();
        $allowedActionsAndTypes['Join'] = array();
        $allowedActionsAndTypes['apply'] = array();

        if (strcasecmp($queueAction, 'Create') == 0 && strcasecmp($queueObjType, 'User') == 0) {
            $approve_allowed = $this->jqueueaccess->hasApproveAccess($queueObj);
            if (!$approve_allowed) {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';

                return $this->load->view(MY_Controller::$page, $data);

            }

            $r = $this->j_queue->createUserFromQueue($queueObj);
            if ($r) {
                try {
                    $this->em->remove($queueObj);
                    $this->em->flush();
                    $success_message = "User  has been added.";
                    $data['content_view'] = 'reports/awaiting_approved_view';
                    $data['success_message'] = $success_message;
                    $this->load->view(MY_Controller::$page, $data);
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . ' ' . $e);
                    show_error("server internal error", 500);
                }

                return;
            } else {

                $data['error_message'] = implode('<br />', $this->globalerrors);
                $data['content_view'] = 'error_message';
                $this->load->view(MY_Controller::$page, $data);

                return;
            }
        } elseif ($queueAction === 'Create' && in_array($queueObjType, array('IDP', 'SP', 'BOTH'), true)) {
            $approve_allowed = $this->zacl->check_acl(strtolower($queueObjType), 'create', 'entity', '') || $this->jqueueaccess->hasApproveByFedadmin($queueObj);
            if ($approve_allowed) {
                $storedEntity = $this->createProvider($queueObj, $qaccessLevel);
                if ($storedEntity) {
                    $data = array(
                        'success_message' => 'entity approved',
                        'content_view' => 'reports/awaiting_approved_view'
                    );

                    return $this->load->view(MY_Controller::$page, $data);

                } else {
                    /**
                     * @todo change error message
                     */
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';

                    return $this->load->view(MY_Controller::$page, $data);
                }
            } else {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';

                return $this->load->view(MY_Controller::$page, $data);
            }

        } elseif (strcasecmp($queueAction, 'Delete') == 0 && strcasecmp($queueObj->getType(), 'Federation') == 0) {
            if (!$isAdministrator) {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';

                return $this->load->view(MY_Controller::$page, $data);

            }

            $r = $this->deleteFederation($queueObj);
            if (!$r) {
                $data['error_message'] = $this->error_message;
                $data['content_view'] = 'error_message';

                return $this->load->view(MY_Controller::$page, $data);

            }
            $data['success_message'] = 'Federation has been removed from the system';
            $data['content_view'] = 'reports/awaiting_approved_view';

            return $this->load->view(MY_Controller::$page, $data);

        } elseif (strcasecmp($queueAction, 'Create') == 0 && strcasecmp($queueObj->getType(), 'Federation') == 0) {
            $approve_allowed = $this->zacl->check_acl('federation', 'create', 'default', '');
            if ($approve_allowed) {
                $fed = new models\Federation;

                $fed->importFromArray($queueObj->getData());
                $fedsysname = $fed->getSysname();
                if (empty($fedsysname)) {
                    $fedsysname = base64url_encode($fed->getName());
                    $fed->setSysname($fedsysname);
                }


                $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
                if (empty($fed_check)) {
                    $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('urn' => $fed->getUrn()));
                }
                if (empty($fed_check)) {
                    $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $fed->getSysname()));
                }

                if ($fed_check) {
                    $error_message = lang('error_fedexists') . '( ' . lang('rr_fed_sysname') . ',' . lang('rr_fed_name') . ',' . lang('fednameinmeta') . ')';
                    $data['error_message'] = $error_message;
                    $data['content_view'] = 'error_message';

                    return $this->load->view(MY_Controller::$page, $data);

                } else {
                    $fedname = $queueObj->getName();
                    $this->em->persist($fed);
                    $this->em->remove($queueObj);
                    $this->em->flush();
                    $acl_res = new models\AclResource;
                    $acl_res->setResource('f_' . $fed->getId());
                    $acl_res->setType('federation');
                    $acl_res->setDefaultValue('read');


                    $parent_res = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => 'federation'));
                    $acl_res->setParent($parent_res);
                    $this->em->persist($acl_res);
                    try {
                        $this->em->flush();

                        $message = lang('rr_federation') . ' ' . $fedname . ' ID:' . $fed->getId() . ' ' . lang('hasbeenadded');

                        log_message('debug', "Federation " . $fedname . "witch ID:" . $fed->getId() . " has been added");

                        return $this->load->view(MY_Controller::$page, array('content_view' => 'reports/awaiting_approved_view', 'success_message' => $message));
                    } catch (Exception $e) {
                        log_message('error', __METHOD__ . ' ' . $e);
                        show_error('Internal server error', 500);
                    }
                }

                return;
            } else {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';

                return $this->load->view(MY_Controller::$page, $data);
            }
        } /**
         *          JOIN - accept request (by provider) sent by federation to provider
         */
        elseif (strcasecmp($queueAction, 'Join') == 0) {
            $type = $queueObj->getType();
            if (!empty($recipienttype) && !empty($recipient) && strcasecmp($recipienttype, 'provider') == 0) {
                $providers_tmp = new models\Providers;
                $provider = $providers_tmp->getOneById($recipient);
                if (empty($provider)) {
                    show_error('Provider not found', 404);
                }
                $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
                if (!$has_write_access) {
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    $this->load->view(MY_Controller::$page, $data);
                } elseif ($type == 'Federation') {
                    $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $queueObj->getName()));
                    if (empty($federation)) {
                        show_error('Federation not found', 404);
                    }
                    $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $provider->getId(), 'federation' => $federation->getId()));
                    if (!empty($membership)) {
                        $membership->setJoinState('1');
                    } else {
                        $membership = new models\FederationMembers;
                        $membership->setJoinState('1');
                        $membership->setProvider($provider);
                        $membership->setFederation($federation);
                    }
                    $this->em->persist($membership);
                    $this->em->persist($provider);
                    $this->em->persist($federation);;
                    $mail_recipients[] = $queueObj->getCreator()->getEmail();
                    $sbj = $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                    $body = $this->jauth->getLoggedinUsername() . " just approved request.\r\n";
                    $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                    $this->em->remove($queueObj);
                    $this->emailsender->addToMailQueue(array('grequeststoproviders'), null, $sbj, $body, array(), $sync = false);
                    try {
                        $this->em->flush();
                        $data['success_message'] = 'Request has been approved';
                        $data['content_view'] = 'reports/awaiting_approved_view';

                        return $this->load->view(MY_Controller::$page, $data);
                    } catch (Exception $e) {
                        log_message('error', __METHOD__ . ' ' . $e);
                        $data['error_message'] = 'Error occured';
                        $data['content_view'] = 'error_message';

                        return $this->load->view(MY_Controller::$page, $data);
                    }
                } else {
                    log_message('error', __METHOD__ . ' line ' . __LINE__ . ' unknown request');
                    show_error('Unknown request', 404);
                }

                return;
            } elseif (!empty($recipienttype) && !empty($recipient) && strcasecmp($recipienttype, 'federation') == 0) {
                $federations_tmp = new models\Federations;
                $federation = $federations_tmp->getOneFederationById($recipient);
                if (empty($federation)) {
                    show_error('Federation not found', 404);
                }
                $has_write_access = $this->zacl->check_acl('f_' . $federation->getId(), 'write', 'federation', '') || $this->zacl->check_acl('f_' . $federation->getId(), 'approve', 'federation', '');
                if (!$has_write_access) {
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';

                    return $this->load->view(MY_Controller::$page, $data);
                } elseif ($type == 'Provider') {
                    $d = $queueObj->getData();
                    $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $d['id'], 'entityid' => $d['entityid']));
                    if (empty($provider)) {
                        show_error('Provider not found', 404);
                    }
                    $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $provider->getId(), 'federation' => $federation->getId()));
                    if (!empty($membership)) {
                        $membership->setJoinState('1');
                    } else {
                        $membership = new models\FederationMembers;
                        $membership->setJoinState('1');
                        $membership->setProvider($provider);
                        $membership->setFederation($federation);
                    }
                    $this->em->persist($membership);

                    $this->tracker->save_track(strtolower($provider->getType()), 'request', $provider->getEntityId(), 'request to join federation: ' . $federation->getName() . ' :: accepted ', false);


                    /**
                     * @todo add more recipient like fedowner or fedadmins
                     */
                    $additionalReceipients = array();
                    if ($this->config->item('notify_requester_if_queue_accepted') === true) {
                        $additionalReceipients[] = $queueObj->getCreator()->getEmail();
                    }
                    $sbj = "Approved:" . $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                    $body = $this->jauth->getLoggedinUsername() . " just approved request.\r\n";
                    $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                    $this->em->persist($provider);
                    $this->em->remove($queueObj);
                    $this->emailsender->addToMailQueue(array('gjoinfedreq', 'joinfedreq'), $federation, $sbj, $body, $additionalReceipients, false);
                    $this->em->flush();
                    $data['success_message'] = 'Request has been approved';
                    $data['content_view'] = 'reports/awaiting_approved_view';

                    return $this->load->view(MY_Controller::$page, $data);
                }
            } else {
                show_error('Something went wrong', 500);
            }
        } elseif (strcasecmp($queueAction, 'apply') == 0) {
            $allowedRecipientTypes = array('entitycategory', 'regpolicy');
            $recipientTypesStrs = array('entitycategory' => lang('req_entcatapply'), 'regpolicy' => lang('req_reqpolapply'));
            $type = $queueObj->getType();
            $name = $queueObj->getName();
            if (in_array($recipienttype, $allowedRecipientTypes) && strcasecmp($type, 'Provider') == 0 && !empty($name)) {
                /**
                 * @var models\Provider $provider ;
                 * @var models\Coc $coc ;
                 */
                $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
                if (empty($provider)) {
                    log_message('error', __METHOD__ . ' could not approve request as provider with entityid ' . $name . ' does not exists');
                    show_error('Provider does not exist', 404);
                }
                if (strcasecmp($recipienttype, 'entitycategory') == 0) {
                    $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $recipient, 'type' => 'entcat'));
                    if (empty($coc)) {
                        log_message('error', __METHOD__ . ' could not approve request as EntityCategory with id ' . $recipient . '  does not exists');
                        show_error('Entity category', 404);
                    }
                } else {
                    $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $recipient, 'type' => 'regpol'));
                    if (empty($coc)) {
                        log_message('error', __METHOD__ . ' could not approve request as RegistrationPolicy with id ' . $recipient . '  does not exists');
                        show_error('RegistrationPolicy does not exist', 404);
                    }
                }
                if (!$isAdministrator) {
                    show_error('no permission', 403);
                }
                $coc->setProvider($provider);
                $provider->setCoc($coc);
                $this->em->persist($provider);
                $this->em->persist($coc);

                $m_creator = $queueObj->getCreator();
                if (!empty($m_creator)) {
                    $requestedBy = $m_creator->getEmail();
                } else {
                    $requestedBy = $queueObj->getEmail();
                }

                $additionalReciepients[] = $requestedBy;
                $subject = 'Request has been approved';
                $body = 'Hi,' . PHP_EOL;
                $body .= 'The request applied by ' . html_escape($requestedBy) . ' and  placed on ' . base_url() . PHP_EOL;
                $body .= 'has been approved' . PHP_EOL;
                $body .= 'Provider: ' . $provider->getEntityId() . PHP_EOL;
                $body .= '' . $recipientTypesStrs['' . $recipienttype . ''] . ' ' . $coc->getUrl() . PHP_EOL;

                $this->emailsender->addToMailQueue(array(), null, $subject, $body, $additionalReciepients, false);
                $this->em->remove($queueObj);
                try {
                    $this->em->flush();
                    $data = array('content_view' => 'reports/awaiting_approved_view', 'success_message' => 'Request has been approved');

                    return $this->load->view(MY_Controller::$page, $data);
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . ' ' . $e);
                    $data = array('content_view' => 'error_message', 'error_message' => 'Problem occured');

                    return $this->load->view(MY_Controller::$page, $data);
                }

            } else {
                log_message('error', __METHOD__ . ' line:' . __LINE__ . ' unkown request');
                show_error('unknown request', 404);
            }
        } elseif (strcasecmp($queueAction, 'update') == 0 && $recipienttype === 'provider') {
            // update scope
            /**
             * @var models\Provider $providerToUpdate
             */
            $providerToUpdate = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $recipient));
            if ($providerToUpdate === null) {
                show_error('Provider not found', 404);
            }
            $providerType = $providerToUpdate->getType();
            $objData = $queueObj->getData();
            $objType = $queueObj->getObjType();
            if ($objType === 'n' && isset($objData['scope']['orig']) && isset($objData['scope']['new']) && ($providerType === 'IDP' || $providerType === 'BOTH')) {
                $changes['scope idpsso'] = array('before' => implode(',', $providerToUpdate->getScope('idpsso')), 'after' => implode(',', $objData['scope']['new']['idpsso']));
                $changes['scope aa'] = array('before' => implode(',', $providerToUpdate->getScope('aa')), 'after' => implode(',', $objData['scope']['new']['aa']));
                $providerToUpdate->setScope('idpsso', $objData['scope']['new']['idpsso']);
                $providerToUpdate->setScope('aa', $objData['scope']['new']['aa']);
                $this->em->persist($providerToUpdate);

                $mailRcpts[] = $queueObj->getCreator()->getEmail();
                $sbj = 'Request approved';
                $body = 'Hi,' . PHP_EOL . html_escape($this->jauth->getLoggedinUsername()) . ' just approved request sent by ' . html_escape($queueObj->getCreator()->getEmail()) . PHP_EOL;
                $body .= ' about  update scope(s) for entityID: ' . html_escape($providerToUpdate->getEntityId()) . PHP_EOL;
                $this->emailsender->addToMailQueue(array('grequeststoproviders', 'providermodified'), $providerToUpdate, $sbj, $body, $mailRcpts, false);
                $this->load->library('tracker');
                $this->tracker->save_track('ent', 'modification', $providerToUpdate->getEntityId(), serialize($changes), false);


                $this->em->remove($queueObj);

                $this->em->flush();
                $data = array('content_view' => 'reports/awaiting_approved_view', 'success_message' => 'Request has been approved');

                return $this->load->view(MY_Controller::$page, $data);
            }

        } else {
            echo $recipienttype . ' ' . $queueObjType;
            log_message('error', __METHOD__ . ' line:' . __LINE__ . ' unkown request');
            show_error('unknown request', 404);
        }
    }

}
