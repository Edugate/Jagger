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
 * Awaiting Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Awaiting extends MY_Controller
{
    private $alert;
    private $error_message;

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'cert'));
        $this->load->library(array('table', 'tracker'));
        $this->title = lang('title_approval');
    }

    function alist()
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');

        }
        try {
            $this->load->library(array('zacl', 'j_queue'));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            echo 'Internal server error';
            return;
        }
        $this->title = lang('rr_listawaiting');
        $data = array(
            'titlepage' => lang('rr_listawaiting'),
            'content_view' => 'reports/awaiting_view',
            'message' => $this->alert,
            'error_message' => $this->error_message
        );
        $this->load->view('page', $data);
    }

    function ajaxrefresh()
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'Permission denied';
            return;
        }
        $this->load->library(array('zacl', 'j_queue'));
        $data = array(
            'list' => $this->getQueueList(),
            'error_message' => $this->session->flashdata('error_message'),
            'content_view' => 'reports/awaiting_list_view'
        );
        $this->load->view('reports/awaiting_list_view', $data);
    }

    private function hasQAccess(\models\Queue $q)
    {
        $result = false;
        $isAdministrator = $this->j_auth->isAdministrator();
        if ($isAdministrator) {
            return true;
        }
        $currentUser = $this->j_auth->current_user();
        $creator = $q->getCreator();
        if (!empty($creator)) {
            $name = $creator->getUsername();
            if (strcasecmp($name, $currentUser) == 0) {
                return true;
            }
        }
        $action = $q->getAction();
        $recipient = $q->getRecipient();
        $recipientType = $q->getRecipientType();

        if (strcasecmp($action, 'Join') == 0) {
            if (!empty($recipientType) && strcasecmp($recipientType, 'federation') == 0 && !empty($recipient)) {
                $hasWrite = $this->zacl->check_acl('f_' . $recipient . '', 'write', 'federation', '');
                return $hasWrite;
            }
        } elseif (strcasecmp($action, 'apply') == 0 && strcasecmp($recipientType, 'entitycategory') == 0) {
            /**
             * @todo decide who can approve entity category request
             */
        } elseif (strcasecmp($action, 'apply') == 0 && strcasecmp($recipientType, 'regpolicy') == 0) {
            /**
             * @todo decide who can approve registration policy request
             */
        }
        return $result;
    }

    private function hasApproveAccess(\models\Queue $q)
    {
        $result = false;
        $isAdministrator = $this->j_auth->isAdministrator();
        if ($isAdministrator) {
            return true;
        }
        $action = $q->getAction();
        $recipient = $q->getRecipient();
        $recipientType = $q->getRecipientType();

        if (strcasecmp($action, 'Join') == 0 && !empty($recipientType)) {
            if (strcasecmp($recipientType, 'federation') == 0 && !empty($recipient)) {
                $hasAccess = $this->zacl->check_acl('f_' . $recipient . '', 'write', 'federation', '');
                return $hasAccess;
            } elseif (strcasecmp($recipientType, 'provider') == 0 && !empty($recipient)) {
                $hasAccess = $this->zacl->check_acl($recipient, 'write', 'provider', '');
                return $hasAccess;
            }
        }
        return $result;
    }

    private function getQueueList()
    {
        $this->load->library('zacl');
        $this->load->library('j_queue');
        /**
         * @var $queueArray models\Queue[]
         */
        $queueArray = $this->em->getRepository("models\Queue")->findAll();
        $result = array('q' => array(), 's' => array());

        $kid = 0;
        foreach ($queueArray as $q) {
            $c_creator = 'anonymous';
            $c_creatorCN = 'Anonymous';
            $creator = $q->getCreator();
            $access = $this->hasQAccess($q);
            if (!$access) {
                continue;
            }
            if (!empty($creator)) {
                $c_creator = $creator->getUsername();
                $c_creatorCN = $creator->getFullname();
            }
            $recipientid = $q->getRecipient();
            $recipenttype = $q->getRecipientType();
            $recipientname = '';
            if (strcasecmp($recipenttype, 'provider') == 0) {
                $p = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $recipientid));
                if (!empty($p)) {
                    $recipientname = $p->getName();
                }
            }
            if ($access) {
                $result['q'][$kid++] = array(
                    'issubscription' => 0,
                    'requester' => $c_creator,
                    'requesterCN' => $c_creatorCN,
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
        $isAdmin = $this->j_auth->isAdministrator();
        if ($isAdmin) {

            foreach ($subscriptions as $s) {
                $result['s'][$kid++] = array(
                    'subscriber' => $s->getSubscriber()->getUsername(),
                    'type' => lang($s->getType()),
                );

            }
        }
        return $result;
    }

    public function dashajaxrefresh()
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo "Permission denied";
            return;
        }
        $this->load->library(array('zacl', 'j_queue'));
        $data = array(
            'list' => $this->getQueueList(),
            'content_view' => 'reports/dashawaiting_list_view'
        );
        $this->load->view('reports/dashawaiting_list_view', $data);
    }

    public function counterqueue()
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo "Permission denied";
            return;
        }
        $this->load->library(array('zacl', 'j_queue'));
        $queuelist = $this->getQueueList();
        $c = count($queuelist['q']) + count($queuelist['s']);
        echo $c;
        return;
    }


    private function detailFederation(models\Queue $qObject)
    {
        $objAction = $qObject->getAction();
        $recipientType = $qObject->getRecipientType();
        if (strcasecmp($objAction, 'Create') == 0) {
            $fedrows = $this->j_queue->displayRegisterFederation($qObject);
            $fedrows[]['2cols'] = $this->j_queue->displayFormsButtons($qObject->getId());
            $data['fedrows'] = $fedrows;
            $data['content_view'] = 'reports/awaiting_federation_register_view';
            $r['data'] = $data;
            return $r;
        }
        if (strcasecmp($objAction, 'Join') == 0 && strcasecmp($recipientType, 'provider') == 0) {
            $recipient_write_access = $this->zacl->check_acl($qObject->getRecipient(), 'write', 'entity', '');
            $requestor_view_access = (strcasecmp($qObject->getCreator()->getUsername(), $this->j_auth->current_user()) == 0);
            if ($requestor_view_access || $recipient_write_access) {
                $result = $this->j_queue->displayInviteProvider($qObject);
                if (!empty($result)) {
                    $data['result'] = $result;
                } else {
                    $data['error_message'] = "Couldn't load request details";
                }
            } else {
                $data['error_message'] = lang('rerror_noperm_viewqueuerequest');
            }

            $data['content_view'] = 'reports/awaiting_invite_provider_view';
            $r['data'] = $data;
            return $r;
        }
        if (strcasecmp($objAction, 'Delete') == 0) {
            $fedrows = $this->j_queue->displayDeleteFederation($qObject);
            $fedrows[]['2cols'] = $this->j_queue->displayFormsButtons($qObject->getId(), !$this->j_auth->isAdministrator());
            $data['fedrows'] = $fedrows;
            $data['content_view'] = 'reports/awaiting_federation_register_view';
            $r['data'] = $data;
            return $r;
        }
        return null;
    }

    private function detailProvider(models\Queue $qObject)
    {
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
            $result['data']['provider'][]['2cols'] = $this->j_queue->displayFormsButtons($qObject->getId());
            return $result;
        }
        if (strcasecmp($objAction, 'Join') == 0 && strcasecmp($objRecipientType, 'federation') == 0) {
            $recipientWriteAccess = $this->zacl->check_acl('f_' . $qObject->getRecipient(), 'write', 'federation', '');
            $requestorViewAccess = (strcasecmp($qObject->getCreator()->getUsername(), $this->j_auth->current_user()) == 0);
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

    function detail($token)
    {
        if (!ctype_alnum($token)) {
            show_error('Wrong token provided', 404);
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        try {
            $this->load->library(array('zacl', 'j_queue', 'providertoxml'));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }
        /**
         * @var $queueObject models\Queue
         */
        try {
            $queueObject = $this->em->getRepository("models\Queue")->findOneBy(array('token' => $token));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }


        $breadcrumbs = array(
            array('url' => base_url('reports/awaiting'), 'name' => '' . lang('rr_listawaiting') . ''),
            array('url' => '#', 'name' => lang('rr_requestawaiting'), 'type' => 'current'),
        );

        if (empty($queueObject)) {
            $data = array(
                'content_view' => 'error_message',
                'error_message' => lang('rerror_qid_noexist')
            );
            $this->load->view('page', $data);
            return;
        }
        $objType = $queueObject->getObjType();
        $objAction = $queueObject->getAction();
        $recipientType = $queueObject->getRecipientType();
        if (strcasecmp($objType, 'Provider') == 0) {
            $result = $this->detailProvider($queueObject);
        } elseif (strcasecmp($objType, 'Federation') == 0) {
            $result = $this->detailFederation($queueObject);
        } elseif (strcasecmp($objType, 'User') == 0 && strcasecmp($objAction, 'Create') == 0) {
            if ($this->hasQAccess($queueObject)) {
                $buttons = $this->j_queue->displayFormsButtons($queueObject->getId());
                $result['data'] = array(
                    'requestdata' => $this->j_queue->displayRegisterUser($queueObject),
                    'content_view' => 'reports/awaiting_detail_view',
                    'error_message' => $this->error_message
                );
                $result['data']['userdata'][]['2cols'] = $buttons;
            } else {
                $result['data'] = array(
                    'content_view' => 'nopermission',
                    'error' => lang('rr_nopermission')
                );
            }
        }
        elseif (strcasecmp($objType, 'n') == 0 && strcasecmp($objAction, 'apply') == 0 && strcasecmp($recipientType, 'entitycategory') == 0) // apply for entity category
        {
            if ($this->hasQAccess($queueObject)) {
                $approveaccess = $this->hasApproveAccess($queueObject);
                $buttons = $this->j_queue->displayFormsButtons($queueObject->getId(), !$approveaccess);
                $result['data'] = array(
                    'requestdata' => $this->j_queue->displayApplyForEntityCategory($queueObject),
                    'content_view' => 'reports/awaiting_detail_view'
                );
                $result['data']['requestdata'][]['2cols'] = $buttons;
            } else {
                $result['data'] = array(
                    'content_view' => 'nopermission',
                    'error' => lang('rr_nopermission')
                );
            }
        }
        elseif (strcasecmp($objType, 'n') == 0 && strcasecmp($objAction, 'apply') == 0 && strcasecmp($recipientType, 'regpolicy') == 0) // apply for entity category
        {
            if ($this->hasQAccess($queueObject)) {

                $approveaccess = (boolean)$this->hasApproveAccess($queueObject);

                $buttons = $this->j_queue->displayFormsButtons($queueObject->getId(), !$approveaccess);

                $result['data'] = array(
                    'requestdata' => $this->j_queue->displayApplyForRegistrationPolicy($queueObject),
                    'content_view' => 'reports/awaiting_detail_view'
                );
                $result['data']['requestdata'][]['2cols'] = $buttons;
            } else {
                $result['data'] = array(
                    'content_view' => 'nopermission',
                    'error' => lang('rr_nopermission')
                );
            }
        }

        if (!empty($result)) {
            $result['data']['breadcrumbs'] = $breadcrumbs;
        } else {
            $result['data']= array(
                'content_view' =>  'nopermission',
                'error'=>'no permission or unkown error',
            );
        }
        $this->load->view('page', $result['data']);
    }

    private function deleteFederation(\models\Queue $q)
    {
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
        $body = 'Dear user,' . PHP_EOL.'Federation : ' . $federation->getName() . ' has been removed from the system';
        $this->email_sender->addToMailQueue(array(), null, $sbj, $body, array(), false);
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

    private function createProvider(\models\Queue $q)
    {
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        foreach ($attrs as $a) {
            $attributesByName['' . $a->getOid() . ''] = $a;
        }
        $d = $q->getData();
        if (!isset($d['metadata'])) {
            $entity = new models\Provider;
            $entity->importFromArray($d);
        } else {
            $this->load->library(array('xmlvalidator','metadata2array'));
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = FALSE;
            $metadataDOM->WarningChecking = FALSE;
            $metadataDOM->loadXML(base64_decode($d['metadata']));
            $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
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
                $entarray = $this->metadata2array->entityDOMToArray($l, TRUE);
            }
            $entity = new models\Provider;
            $entity->setProviderFromArray(current($entarray), TRUE);
            $entity->setReqAttrsFromArray(current($entarray), $attributesByName);
            $entity->setActive(TRUE);
            $entity->setStatic(FALSE);
            if (isset($d['federations'])) {
                $fe = $entity->getFederations();
                if ($fe->count() == 0) {
                    foreach ($d['federations'] as $g) {
                        $gg = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $g['sysname']));
                        if (!empty($gg)) {
                            if ($gg->isJoinAllowedForNew()) {
                                $membership = new models\FederationMembers;
                                $membership->setJoinState('1');
                                $membership->setProvider($entity);
                                $membership->setFederation($gg);
                                $entity->getMembership()->add($membership);
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
        $creator = $q->getCreator();
        $this->em->remove($q);
        $requester_recipient = null;
        if (!empty($creator) && ($creator instanceOf models\User)) {
            $requester_recipient = $creator->getEmail();
        }
        if (empty($requester_recipient)) {
            $requester_recipient = $q->getEmail();
        }
        $sbj = 'Identity/Service Provider has been approved';
        $body = 'Dear user,' . PHP_EOL.'Registration request: ' . $entity->getName() . ' (' . $entity->getEntityId() . ')' . PHP_EOL;
        $body .= 'Requested by: '.$requester_recipient.''. PHP_EOL.'Request has been just approved by ' . $this->j_auth->current_user() . ' and added to the system' . PHP_EOL;
        $body .= 'It can be reviewed on ' . base_url() . ' ' . PHP_EOL;
        $additionalReceipents = array();
        $toNotifyRequester = $this->config->item('notify_requester_if_queue_accepted');
        if (!empty($toNotifyRequester)) {
            $additionalReceipents[] = $requester_recipient;
        }
        $this->email_sender->addToMailQueue(array('greqisterreq', 'gidpregisterreq'), null, $sbj, $body, $additionalReceipents, FALSE);
        $this->tracker->save_track('sys', null, null, 'approved - provider reg req: ' . $entity->getEntityId() . '', false);
        $this->load->library('j_ncache');
        try {
            $this->em->flush();
            $this->j_ncache->cleanProvidersList('idp');
            $this->j_ncache->cleanProvidersList('sp');
            return true;
        } catch (Exception $e) {
            $this->error_message = 'Error occured during storing data in database';
            log_message('error', __METHOD__ . ' ' . $e);
            return false;
        }
    }

    function approve()
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $isAdministrator = $this->j_auth->isAdministrator();
        $this->load->library(array('zacl','j_queue'));
        $message = "";
        $error_message = null;
        $qaction = trim($this->input->post('qaction'));
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
            $message = $_SERVER['REQUEST_URI'];
            $message .= ' id=' . $this->input->post('qid') . ' doesnt exist in queue';
            log_message('debug', $message);
            $dataview = array(
                'error_message' => 'It cannot be approved because it does not exist',
                'content_view' => 'error_message'
            );
            $this->load->view('page', $dataview);
            return;
        }
        $queueAction = $queueObj->getAction();
        $queueObjType = $queueObj->getType();
        $allowedActionsAndTypes['Create']['User'] = array(
            'access' => $this->hasApproveAccess($queueObj),
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
            $approve_allowed = $this->hasApproveAccess($queueObj);
            if (!$approve_allowed) {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }

            $r = $this->j_queue->createUserFromQueue($queueObj);
            if ($r) {
                try {
                    $this->em->remove($queueObj);
                    $this->em->flush();
                    $success_message = "User  has been added.";
                    $data['content_view'] = 'reports/awaiting_approved_view';
                    $data['success_message'] = $success_message;
                    $this->load->view('page', $data);
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . ' ' . $e);
                    show_error("server internal error", 500);
                }
                return;
            } else {

                $data['error_message'] = implode('<br />', $this->globalerrors);
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }
        } elseif (strcasecmp($queueAction, 'Create') == 0 && (strcasecmp($queueObjType, 'IDP') == 0 || strcasecmp($queueObjType, 'SP') == 0)) {
            $approve_allowed = $this->zacl->check_acl(strtolower($queueObjType), 'create', 'entity', '');
            if ($approve_allowed) {
                $storedEntity = $this->createProvider($queueObj);
                if ($storedEntity) {
                    $data = array(
                        'success_message' => 'entity approved',
                        'content_view' => 'reports/awaiting_approved_view'
                    );
                    return $this->load->view('page', $data);

                } else {
                    /**
                     * @todo change error message
                     */
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    return $this->load->view('page', $data);
                }
            } else {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                return $this->load->view('page', $data);
            }

        } elseif (strcasecmp($queueAction, 'Delete') == 0 && strcasecmp($queueObj->getType(), 'Federation') == 0) {
            if (!$isAdministrator) {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                return $this->load->view('page', $data);

            }

            $r = $this->deleteFederation($queueObj);
            if (!$r) {
                $data['error_message'] = $this->error_message;
                $data['content_view'] = 'error_message';
                return $this->load->view('page', $data);

            }
            $data['success_message'] = 'Federation has been removed from the system';
            $data['content_view'] = 'reports/awaiting_approved_view';
            return $this->load->view('page', $data);

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
                    return $this->load->view('page', $data);

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
                       return  $this->load->view('page', array('content_view' => 'reports/awaiting_approved_view', 'success_message' => $message));
                    } catch (Exception $e) {
                        log_message('error', __METHOD__ . ' ' . $e);
                        show_error('Internal server error', 500);
                    }
                }
                return;
            } else {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                return $this->load->view('page', $data);
            }
        } /**
         *          JOIN - accept request (by provider) sent by federation to provider
         */
        elseif (strcasecmp($queueAction, 'Join') == 0) {
            $recipient = $queueObj->getRecipient();
            $recipienttype = $queueObj->getRecipientType();
            $type = $queueObj->getType();
            if (!empty($recipienttype) && !empty($recipient) && $recipienttype == 'provider') {
                $providers_tmp = new models\Providers;
                $provider = $providers_tmp->getOneById($recipient);
                if (empty($provider)) {
                    show_error('Provider not found', 404);
                }
                $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
                if (!$has_write_access) {
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
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
                    $mail_recipients = array();
                    $mail_recipients[] = $queueObj->getCreator()->getEmail();
                    $sbj = $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                    $body = $this->j_auth->current_user() . " just approved request.\r\n";
                    $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                    $this->em->remove($queueObj);
                    $this->email_sender->addToMailQueue(array('grequeststoproviders'), null, $sbj, $body, array(), $sync = false);
                    try {
                        $this->em->flush();
                        $data['success_message'] = 'Request has been approved';
                        $data['content_view'] = 'reports/awaiting_approved_view';
                        return $this->load->view('page', $data);
                    } catch (Exception $e) {
                        log_message('error', __METHOD__ . ' ' . $e);
                        $data['error_message'] = 'Error occured';
                        $data['content_view'] = 'error_message';
                        return $this->load->view('page', $data);
                    }
                } else {
                    log_message('error', __METHOD__ . ' line ' . __LINE__ . ' unknown request');
                    show_error('Unknown request', 404);
                }
                return;
            } elseif (!empty($recipienttype) && !empty($recipient) && $recipienttype == 'federation') {
                $federations_tmp = new models\Federations;
                $federation = $federations_tmp->getOneFederationById($recipient);
                if (empty($federation)) {
                    show_error('Federation not found', 404);
                }
                $has_write_access = $this->zacl->check_acl('f_' . $federation->getId(), 'write', 'federation', '');
                if (!$has_write_access) {
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    return $this->load->view('page', $data);
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
                    if ($this->config->item('notify_requester_if_queue_accepted') === TRUE) {
                        $additionalReceipients[] = $queueObj->getCreator()->getEmail();
                    }
                    $sbj = "Approved:" . $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                    $body = $this->j_auth->current_user() . " just approved request.\r\n";
                    $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                    $this->em->persist($provider);
                    $this->em->remove($queueObj);
                    $this->email_sender->addToMailQueue(array('gjoinfedreq', 'joinfedreq'), $federation, $sbj, $body, $additionalReceipients, false);
                    $this->em->flush();
                    $data['success_message'] = 'Request has been approved';
                    $data['content_view'] = 'reports/awaiting_approved_view';
                    return $this->load->view('page', $data);
                }
            } else {
                show_error('Something went wrong', 500);
            }
        } elseif (strcasecmp($queueAction, 'apply') == 0) {
            $recipient = $queueObj->getRecipient();
            $recipienttype = $queueObj->getRecipientType();
            $allowedRecipientTypes = array('entitycategory', 'regpolicy');
            $type = $queueObj->getType();
            $name = $queueObj->getName();
            if (in_array($recipienttype, $allowedRecipientTypes) && strcasecmp($type, 'Provider') == 0 && !empty($name)) {
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
                $this->em->remove($queueObj);
                try {
                    $this->em->flush();
                    $data = array('content_view' => 'reports/awaiting_approved_view', 'success_message' => 'Request has been approved');
                    return $this->load->view('page', $data);
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . ' ' . $e);
                    $data = array('content_view' => 'error_message', 'error_message' => 'Problem occured');
                    return $this->load->view('page', $data);
                }

            } else {
                log_message('error', __METHOD__ . ' line:' . __LINE__ . ' unkown request');
                show_error('unknown request', 404);
            }
        } else {
            log_message('error', __METHOD__ . ' line:' . __LINE__ . ' unkown request');
            show_error('unknown request', 404);
        }
    }

    function reject()
    {
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin) {
            $this->load->library('zacl');
            $this->load->library('j_queue');
        } else {
            redirect('auth/login', 'location');
        }

        if (strcasecmp($this->input->post('qaction'), 'reject') == 0) {
            $notification = $this->config->item('notify_if_queue_rejected');
            $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $this->input->post('qid')));

            if (!empty($queueObj)) {
                $queueAction = $queueObj->getAction();
                $creator = $queueObj->getCreator();
                $reject_access = $this->hasQAccess($queueObj);
                $recipienttype = $queueObj->getRecipientType();
                if (!empty($creator)) {
                    $reject_access = (strcasecmp($creator->getUsername(), $this->j_auth->current_user()) == 0);
                }
                if ($reject_access === FALSE) {
                    if (strcasecmp($queueAction, 'Create') == 0) {
                        if (strcasecmp($queueObj->getType(), 'IDP') == 0) {
                            $reject_access = $this->zacl->check_acl('idp', 'create', 'entity', '');
                        } elseif (strcasecmp($queueObj->getType(), 'SP') == 0) {
                            $reject_access = $this->zacl->check_acl('sp', 'create', 'entity', '');
                        } elseif (strcasecmp($queueObj->getType(), 'Federation') == 0) {
                            $reject_access = $this->zacl->check_acl('federation', 'create', 'default', '');
                        }
                    } elseif (strcasecmp($queueAction, 'Join') == 0) {
                        $recipient = $queueObj->getRecipient();
                        if (!empty($recipienttype) && !empty($recipient)) {
                            if ($recipienttype == 'provider') {
                                $reject_access = $this->zacl->check_acl($recipient, 'write', 'entity', '');
                            } elseif ($recipienttype == 'federation') {
                                $reject_access = $this->zacl->check_acl('f_' . $recipient, 'write', 'federation', '');
                            }
                        }
                    } elseif (strcasecmp($queueAction, 'Delete') == 0) {
                        $type = $queueObj->getType();
                        if (strcasecmp($type, 'Federation') == 0) {
                            $isAdmin = $this->j_auth->isAdministrator();
                            if ($isAdmin) {
                                $reject_access = TRUE;
                            }
                        }
                    } elseif (strcasecmp($queueAction, 'apply') == 0 && strcasecmp($recipienttype, 'entitycategory') == 0) {
                        $isAdmin = $this->j_auth->isAdministrator();
                        if ($isAdmin) {
                            $reject_access = TRUE;
                        }
                    } elseif (strcasecmp($queueAction, 'apply') == 0 && strcasecmp($recipienttype, 'regpolicy') == 0) {
                        $isAdmin = $this->j_auth->isAdministrator();
                        if ($isAdmin) {
                            $reject_access = TRUE;
                        }
                    }
                }
                $p = $queueObj->getName();
                $qtoken = $queueObj->getToken();
                if ($reject_access === TRUE) {
                    $additionalReciepients = array();
                    $m_creator = $queueObj->getCreator();
                    if (!empty($m_creator)) {
                        $additionalReciepients[] = $m_creator->getEmail();
                    } else {
                        $additionalReciepients[] = $queueObj->getEmail();
                    }

                    $subject = 'Request has been canceled/rejected';
                    $body = 'Hi,' . PHP_EOL;
                    $body .= 'The request placed on ' . base_url() . PHP_EOL;
                    $body .= 'Request with tokenID: ' . $queueObj->getToken() . ' has been canceled/rejected' . PHP_EOL;
                    $body .= "";
                    log_message('info', 'JAGGER: Queue with token:' . $queueObj->getToken() . ' has been canceled/rejected by ' . $this->j_auth->current_user());
                    $this->em->remove($queueObj);
                    if ($notification === TRUE) {
                        $this->email_sender->addToMailQueue(array(), null, $subject, $body, $additionalReciepients, FALSE);
                    }
                    $this->em->flush();
                    $this->error_message = 'ID: ' . $p . 'with tokenID ' . $qtoken . ' has been removed from queue';
                    $data['error_message'] = $this->error_message;
                    log_message('debug', $this->error_message);
                    $data['content_view'] = 'reports/awaiting_rejected_view';
                    $this->load->view('page', $data);
                } else {
                    $data['error_message'] = lang('rerror_noperm_reject');
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
                }
            } else {
                $message = $_SERVER['REQUEST_URI'];
                $message .= ' id=' . $this->input->post('qid') . ' doesnt exist in queue';
                log_message('debug', $message);
                $this->error_message = 'ID: ' . $this->input->post('qid') . ' doesnt exist in queue';
                $data['error_message'] = $this->error_message;
                $data['content_view'] = 'reports/awaiting_rejected_view';
                $this->load->view('page', $data);
            }
        } else {
            $this->error_message = 'something went wrong';
            $data['error_message'] = $this->error_message;
            $data['content_view'] = 'reports/awaiting_rejected_view';
            $this->load->view('page', $data);
        }
    }

}
