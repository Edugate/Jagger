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
        $this->load->library('table');
        $this->title = lang('title_approval');
    }

    function alist()
    {
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin)
        {
            $this->load->library('zacl');
            $this->load->library('j_queue');
        }
        else
        {
            redirect('auth/login', 'location');
        }

        $data['content_view'] = 'reports/awaiting_view';
        $data['message'] = $this->alert;
        $data['error_message'] = $this->error_message;
        $this->load->view('page', $data);
    }

    function ajaxrefresh()
    {
        if (!$this->input->is_ajax_request())
        {
            show_error('Permission denied', 403);
        }

        if (!$this->j_auth->logged_in())
        {
            set_status_header(403);
            echo "not authenticated";
            return;
        }
        $this->load->library('zacl');
        $this->load->library('j_queue');
        $queuelist = $this->getQueueList();
        $data = array(
            'list' => $queuelist,
            'error_message' => $this->session->flashdata('error_message'),
            'content_view' => 'reports/awaiting_list_view'
        );
        $this->load->view('reports/awaiting_list_view', $data);
    }

    private function hasQAccess($q)
    {
        $result = false;
        $isAdministrator = $this->j_auth->isAdministrator();
        if ($isAdministrator)
        {
            return true;
        }
        $currentUser = $this->j_auth->current_user();
        $creator = $q->getCreator();
        if (!empty($creator))
        {
            $name = $creator->getUsername();
            if (strcasecmp($name, $currentUser) == 0)
            {
                return true;
            }
        }
        $action = $q->getAction();
        $recipient = $q->getRecipient();
        $recipientType = $q->getRecipientType();

        if (strcasecmp($action, 'Join') == 0)
        {
            if (!empty($recipientType) && strcasecmp($recipientType, 'federation') == 0 && !empty($recipient))
            {
                $hasWrite = $this->zacl->check_acl('f_' . $recipient . '', 'write', 'federation', '');
                return $hasWrite;
            }
        }
        elseif (strcasecmp($action, 'apply') == 0 && strcasecmp($recipientType, 'entitycategory') == 0)
        {
            /**
             * @todo decide who can approve entity category request
             */
        }
        elseif (strcasecmp($action, 'apply') == 0 && strcasecmp($recipientType, 'regpolicy') == 0)
        {
            /**
             * @todo decide who can approve registration policy request
             */
        }
        return $result;
    }

    private function hasApproveAccess($q)
    {
        $result = false;
        $isAdministrator = $this->j_auth->isAdministrator();
        if ($isAdministrator)
        {
            return true;
        }
        $action = $q->getAction();
        $recipient = $q->getRecipient();
        $recipientType = $q->getRecipientType();

        if (strcasecmp($action, 'Join') == 0 && !empty($recipientType))
        {
            if (strcasecmp($recipientType, 'federation') == 0 && !empty($recipient))
            {
                $hasAccess = $this->zacl->check_acl('f_' . $recipient . '', 'write', 'federation', '');
                return $hasAccess;
            }
            elseif (strcasecmp($recipientType, 'provider') == 0 && !empty($recipient))
            {
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
        $queueArray = $this->em->getRepository("models\Queue")->findAll();
        $result = array();
        $kid = 0;
        foreach ($queueArray as $q)
        {
            $c_creator = 'anonymous';
            $creator = $q->getCreator();
            $access = $this->hasQAccess($q);
            if (!$access)
            {
                continue;
            }
            if (!empty($creator))
            {
                $c_creator = $creator->getUsername();
            }
            $recipientid = $q->getRecipient();
            $recipenttype = $q->getRecipientType();
            $recipientname = '';
            if (strcasecmp($recipenttype, 'provider') == 0)
            {
                $p = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $recipientid));
                if (!empty($p))
                {
                    $recipientname = $p->getName();
                }
            }
            if ($access)
            {
                $result[$kid] = array(
                    'requester' => $c_creator,
                    'idate' => $q->getCreatedAt(),
                    'datei' => $q->getCreatedAt(),
                    'iname' => $q->getName(),
                    'qid' => $q->getId(),
                    'mail' => $q->getEmail(),
                    'type' => $q->getType(),
                    'action' => $q->getAction(),
                    'recipientname' => $recipientname,
                    'token' => $q->getToken(),
                    'confirmed' => $q->getConfirm()
                );
                $kid++;
            }
        }
        return $result;
    }

    public function dashajaxrefresh()
    {
        if (!$this->input->is_ajax_request())
        {
            show_error('Permission denied', 403);
        }
        if (!$this->j_auth->logged_in())
        {
            set_status_header(403);
            echo "not authenticated";
            return;
        }
        $this->load->library('zacl');
        $this->load->library('j_queue');
        $queuelist = $this->getQueueList();
        $data = array(
            'list' => $queuelist,
            'content_view' => 'reports/dashawaiting_list_view'
        );
        $this->load->view('reports/dashawaiting_list_view', $data);
    }

    public function counterqueue()
    {
        if (!$this->input->is_ajax_request())
        {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        if (!$this->j_auth->logged_in())
        {
            set_status_header(403);
            echo "not authenticated";
            return;
        }
        $this->load->library('zacl');
        $this->load->library('j_queue');
        $queuelist = $this->getQueueList();
        $c = count($queuelist);
        set_status_header(200);
        echo $c;
        return;
    }

    private function idpDetails($queueList)
    {
        $data = $queueList->getData();
        $objData = new models\Provider;
        $objData->importFromArray($data);
    }

    private function detailFederation(models\Queue $qObject)
    {
        $objAction = $qObject->getAction();
        $recipientType = $qObject->getRecipientType();
        if (strcasecmp($objAction, 'Create') == 0)
        {
            $fedrows = $this->j_queue->displayRegisterFederation($qObject);
            $fedrows[]['2cols'] = $this->j_queue->displayFormsButtons($qObject->getId());
            $data['fedrows'] = $fedrows;
            $data['content_view'] = 'reports/awaiting_federation_register_view';
            $r['data'] = $data;
            return $r;
        }
        if (strcasecmp($objAction, 'Join') == 0 && strcasecmp($recipientType, 'provider') == 0)
        {
            $recipient_write_access = $this->zacl->check_acl($qObject->getRecipient(), 'write', 'entity', '');
            $requestor_view_access = (strcasecmp($qObject->getCreator()->getUsername(), $this->j_auth->current_user()) == 0);
            if ($requestor_view_access || $recipient_write_access)
            {
                $result = $this->j_queue->displayInviteProvider($qObject);
                if (!empty($result))
                {
                    $data['result'] = $result;
                }
                else
                {
                    $data['error_message'] = "Couldn't load request details";
                }
            }
            else
            {
                $data['error_message'] = lang('rerror_noperm_viewqueuerequest');
            }

            $data['content_view'] = 'reports/awaiting_invite_provider_view';
            $r['data'] = $data;
            return $r;
        }
        if (strcasecmp($objAction, 'Delete') == 0)
        {
            $fedrows = $this->j_queue->displayDeleteFederation($qObject);
            $fedrows[]['2cols'] = $this->j_queue->displayFormsButtons($qObject->getId());
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
        if (strcasecmp($objAction, 'Create') == 0)
        {
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
        if (strcasecmp($objAction, 'Join') == 0 && strcasecmp($objRecipientType, 'federation') == 0)
        {
            $recipientWriteAccess = $this->zacl->check_acl('f_' . $qObject->getRecipient(), 'write', 'federation', '');
            $requestorViewAccess = (strcasecmp($qObject->getCreator()->getUsername(), $this->j_auth->current_user()) == 0);
            if ($requestorViewAccess || $recipientWriteAccess)
            {

                $result = $this->j_queue->displayInviteFederation($qObject, $recipientWriteAccess);
                if (!empty($result))
                {
                    $data['result'] = $result;
                }
                else
                {
                    $data['error_message'] = "Couldn't load request details";
                }
            }
            else
            {
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
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $this->load->library(array('zacl', 'j_queue'));

        $qObject = $this->em->getRepository("models\Queue")->findOneBy(array('token' => $token));
        if (empty($qObject))
        {
            $dataview = array(
                'content_view' => 'error_message',
                'error_message' => lang('rerror_qid_noexist')
            );
            $this->load->view('page', $dataview);
        }
        $objType = $qObject->getObjType();
        $objAction = $qObject->getAction();
        $recipientType = $qObject->getRecipientType();
        if (strcasecmp($objType, 'Provider') == 0)
        {
            $r = $this->detailProvider($qObject);
            if (!empty($r))
            {
                $this->load->view('page', $r['data']);
            }
            else
            {
                show_error('Unknown error', 500);
            }
            return;
        }
        elseif (strcasecmp($objType, 'Federation') == 0)
        {
            $r = $this->detailFederation($qObject);
            if (!empty($r))
            {
                $this->load->view('page', $r['data']);
            }
            else
            {
                show_error('Unknown error', 500);
            }
            return;
        }
        elseif (strcasecmp($objType, 'User') == 0 && strcasecmp($objAction, 'Create') == 0)
        {
            if ($this->hasQAccess($qObject))
            {
                $buttons = $this->j_queue->displayFormsButtons($qObject->getId());
                $dataview = array(
                    'userdata' => $this->j_queue->displayRegisterUser($qObject),
                    'content_view' => 'reports/awaiting_user_register_view',
                    'error_message' => $this->error_message
                );
                $dataview['userdata'][]['2cols'] = $buttons;
            }
            else
            {
                $dataview = array(
                    'content_view' => 'nopermission',
                    'error' => lang('rr_nopermission')
                );
            }
        }
        elseif (strcasecmp($objType, 'n') == 0 && strcasecmp($objAction, 'apply') == 0 && strcasecmp($recipientType, 'entitycategory') == 0) // apply for entity category
        {
            if ($this->hasQAccess($qObject))
            {
                $approveaccess = $this->hasApproveAccess($qObject);
                $buttons = $this->j_queue->displayFormsButtons($qObject->getId(), !$approveaccess);
                $dataview = array(
                    'requestdata' => $this->j_queue->displayApplyForEntityCategory($qObject),
                    'content_view' => 'reports/awaiting_applyforentcat_view'
                );
                $dataview['requestdata'][]['2cols'] = $buttons;
            }
            else
            {
                $dataview = array(
                    'content_view' => 'nopermission',
                    'error' => lang('rr_nopermission')
                );
            }
        }
        elseif (strcasecmp($objType, 'n') == 0 && strcasecmp($objAction, 'apply') == 0 && strcasecmp($recipientType, 'regpolicy') == 0) // apply for entity category
        {
            if ($this->hasQAccess($qObject))
            {

                $approveaccess = (boolean) $this->hasApproveAccess($qObject);

                $buttons = $this->j_queue->displayFormsButtons($qObject->getId(), !$approveaccess);

                $dataview = array(
                    'requestdata' => $this->j_queue->displayApplyForRegistrationPolicy($qObject),
                    'content_view' => 'reports/awaiting_applyforentcat_view'
                );
                $dataview['requestdata'][]['2cols'] = $buttons;
            }
            else
            {
                $dataview = array(
                    'content_view' => 'nopermission',
                    'error' => lang('rr_nopermission')
                );
            }
        }
        else
        {
            $dataview = array(
                'error' => 'Unknown type',
                'content_view' => 'nopermission'
            );
        }
        $this->load->view('page', $dataview);
    }

    private function createProvider(\models\Queue $q)
    {
        $d = $q->getData();
        if (!isset($d['metadata']))
        {
            $entity = new models\Provider;
            $entity->importFromArray($d);
        }
        else
        {
            $this->load->library('xmlvalidator');
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = FALSE;
            $metadataDOM->WarningChecking = FALSE;
            $metadataDOM->loadXML(base64_decode($d['metadata']));
            $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
            if (!$isValid)
            {
                $this->error_message = 'Invalid metadata';
                return false;
            }
            $this->load->library('metadata2array');
            $xpath = new DomXPath($metadataDOM);
            $namespaces = h_metadataNamespaces();
            foreach ($namespaces as $key => $value)
            {
                $xpath->registerNamespace($key, $value);
            }
            $domlist = $metadataDOM->getElementsByTagName('EntityDescriptor');
            if (count($domlist) != 1)
            {
                $this->error_message = 'Invalid metadata. None or more than one EntityDescriptor found in the raw xml';
                return false;
            }
            foreach ($domlist as $l)
            {
                $entarray = $this->metadata2array->entityDOMToArray($l, TRUE);
            }
            $entity = new models\Provider;
            $entity->setProviderFromArray(current($entarray), TRUE);
            $entity->setActive(TRUE);
            $entity->setStatic(FALSE);
            if (isset($d['federations']))
            {
                $fe = $entity->getFederations();
                if ($fe->count() == 0)
                {
                    foreach ($d['federations'] as $g)
                    {
                        $gg = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $g['sysname']));
                        if (!empty($gg))
                        {
                            $ispublic = $gg->getPublic();
                            $isactive = $gg->getActive();
                            if ($ispublic && $isactive)
                            {
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
        if (!empty($entityExists))
        {
            $this->error_message = "Provider " . $entity->getName() . " (" . $entity->getEntityId() . ") already exists";
            return false;
        }
        $entity->setAsLocal();
        $fed = $entity->getFederations()->get(0);
        if (!empty($fed))
        {
            $fed2 = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
            $entity->removeFederation($fed);
        }
        foreach ($entity->getCertificates() as $o)
        {
            $o->setCertdata(reformatPEM($o->getCertdata()));
            $o->setCertType('x509');
        }
        if (!empty($fed2) && $fed instanceOf models\Federation)
        {
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
        if (!empty($creator) && ($creator instanceOf models\User))
        {
            $requester_recipient = $creator->getEmail();
        }
        if (empty($requester_recipient))
        {
            $requester_recipient = $q->getEmail();
        }
        $sbj = 'Identity/Service Provider has been approved';
        $body = 'Dear user,' . PHP_EOL;
        $body .= 'Registration request: ' . $entity->getName() . ' (' . $entity->getEntityId() . ')' . PHP_EOL;
        $body .= 'Requested by: ' . $requester_recipient . '' . PHP_EOL;
        $body .= 'Request has been just approved by ' . $this->j_auth->current_user() . ' and added to the system' . PHP_EOL;
        $body .= 'It can be reviewed on ' . base_url() . ' ' . PHP_EOL;
        $additionalReceipents = array();
        $toNotifyRequester = $this->config->item('notify_requester_if_queue_accepted');
        if (!empty($toNotifyRequester))
        {
            $additionalReceipents[] = $requester_recipient;
        }
        $this->email_sender->addToMailQueue(array('greqisterreq', 'gidpregisterreq'), null, $sbj, $body, $additionalReceipents, FALSE);
        try
        {
            $this->em->flush();
            return true;
        }
        catch (Exception $e)
        {
            $this->error_message = 'Error occured during storing data in database';
            log_message('error', __METHOD__ . ' ' . $e);
            return false;
        }
    }

    function approve()
    {
        log_message('info', __METHOD__ . ' run');
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->load->library('j_queue');
        $message = "";
        $error_message = null;
        $qaction = trim($this->input->post('qaction'));
        $qid = trim($this->input->post('qid'));
        if (empty($qaction) || strcmp($qaction, 'approve') != 0)
        {
            log_message('debug', $message);
            $this->session->set_flashdata('message', $message);
            $this->session->set_flashdata('error_message', $error_message);
            redirect(base_url() . "reports/awaiting", 'location');
            return;
        }
        if (!empty($qid) || ctype_digit($qid))
        {
            $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $qid));
        }
        if (empty($queueObj))
        {
            $message = $_SERVER['REQUEST_URI'];
            $message .= ' id=' . $this->input->post('qid') . ' doesnt exist in queue';
            log_message('debug', $message);
            $data['error_message'] = 'Can\'t approve it because this request dosn\'t exist';
            $data['content_view'] = 'error_message';
            $this->load->view('page', $data);
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

        if (strcasecmp($queueAction, 'Create') == 0 && strcasecmp($queueObjType, 'User') == 0)
        {
            $approve_allowed = $this->hasApproveAccess($queueObj);
            if (!$approve_allowed)
            {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }

            $r = $this->j_queue->createUserFromQueue($queueObj);
            if ($r)
            {
                try
                {
                    $this->em->remove($queueObj);
                    $this->em->flush();
                    $success_message = "User  has been added.";
                    $data['content_view'] = 'reports/awaiting_approved_view';
                    $data['success_message'] = $success_message;
                    $this->load->view('page', $data);
                }
                catch (Exception $e)
                {
                    log_message('error', __METHOD__ . ' ' . $e);
                    show_error("server internal error", 500);
                }
            }
            else
            {

                $data['error_message'] = implode('<br />', $this->globalerrors);
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }
        }
        elseif (strcasecmp($queueAction, 'Create') == 0 && (strcasecmp($queueObjType, 'IDP') == 0 || strcasecmp($queueObjType, 'SP') == 0))
        {
            $approve_allowed = $this->zacl->check_acl(strtolower($queueObjType), 'create', 'entity', '');
            if ($approve_allowed)
            {
                $storedEntity = $this->createProvider($queueObj);
                if ($storedEntity)
                {
                    $data = array(
                        'success_message' => 'entity approved',
                        'content_view' => 'reports/awaiting_approved_view'
                    );
                    $this->load->view('page', $data);
                    return;
                }
                else
                {
                    /**
                     * @todo change error message 
                     */
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
                }
            }
            else
            {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
            }
        }
        elseif (strcasecmp($queueAction, 'Delete') == 0 && strcasecmp($queueObj->getType(), 'Federation') == 0)
        {
            $isAdministrator = $this->j_auth->isAdministrator();
            if (!$isAdministrator)
            {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }
            $fed = new models\Federation;
            $fed->importFromArray($queueObj->getData());
            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
            if (empty($federation))
            {
                $data['error_message'] = 'Federation not found';
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }
            $isActive = $federation->getActive();
            if ($isActive)
            {
                $data['error_message'] = 'Federation is active , cannot delete';
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
                return;
            }
            $fed = null;
            $this->load->library('FederationRemover');
            $sbj = 'Federation has been removed';
            $body = 'Dear user,' . PHP_EOL;
            $body .= 'Federation : ' . $federation->getName() . ' has been removed from the system';
            $this->email_sender->addToMailQueue(array(), null, $sbj, $body, array(), $sync = false);
            $this->federationremover->removeFederation($federation);
            $this->em->remove($queueObj);
            $this->em->flush();
        }
        elseif (strcasecmp($queueAction, 'Create') == 0 && strcasecmp($queueObj->getType(), 'Federation') == 0)
        {
            $approve_allowed = $this->zacl->check_acl('federation', 'create', 'default', '');
            if ($approve_allowed)
            {
                $fed = new models\Federation;

                $fed->importFromArray($queueObj->getData());
                $fedsysname = $fed->getSysname();
                if (empty($fedsysname))
                {
                    $fedsysname = base64url_encode($fed->getName());
                    $fed->setSysname($fedsysname);
                }



                $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
                if (empty($fed_check))
                {
                    $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('urn' => $fed->getUrn()));
                }
                if (empty($fed_check))
                {
                    $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $fed->getSysname()));
                }

                if ($fed_check)
                {
                    $error_message = lang('error_fedexists') . '( ' . lang('rr_fed_sysname') . ',' . lang('rr_fed_name') . ',' . lang('fednameinmeta') . ')';
                    $data['error_message'] = $error_message;
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
                    return;
                }
                else
                {
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
                    $this->em->flush();

                    $message = lang('rr_federation') . ' ' . $fedname . ' ID:' . $fed->getId() . ' ' . lang('hasbeenadded');
                    log_message('debug', "Federation " . $fedname . "witch ID:" . $fed->getId() . " has been added");
                }
            }
            else
            {
                $data['error_message'] = lang('rerror_noperm_approve');
                $data['content_view'] = 'error_message';
                $this->load->view('page', $data);
            }
        }
        /**
         *          JOIN - accept request (by provider) sent by federation to provider
         */
        elseif (strcasecmp($queueAction, 'Join') == 0)
        {
            $recipient = $queueObj->getRecipient();
            $recipienttype = $queueObj->getRecipientType();
            $type = $queueObj->getType();
            if (!empty($recipienttype) && !empty($recipient) && $recipienttype == 'provider')
            {
                $providers_tmp = new models\Providers;
                $provider = $providers_tmp->getOneById($recipient);
                if (empty($provider))
                {
                    show_error('Provider not found', 404);
                }
                $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
                if (!$has_write_access)
                {
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
                }
                elseif ($type == 'Federation')
                {
                    $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $queueObj->getName()));
                    if (empty($federation))
                    {
                        show_error('Federation not found', 404);
                        return;
                    }
                    $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $provider->getId(), 'federation' => $federation->getId()));
                    if (!empty($membership))
                    {
                        $membership->setJoinState('1');
                    }
                    else
                    {
                        $membership = new models\FederationMembers;
                        $membership->setJoinState('1');
                        $membership->setProvider($provider);
                        $membership->setFederation($federation);
                    }
                    $this->em->persist($membership);
                    $this->em->persist($provider);
                    $this->em->persist($federation);
                    $contacts = $provider->getContacts();
                    $mail_recipients = array();
                    $mail_recipients[] = $queueObj->getCreator()->getEmail();
                    $sbj = $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                    $body = $this->j_auth->current_user() . " just approved request.\r\n";
                    $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                    $this->em->remove($queueObj);
                    $this->email_sender->addToMailQueue(array('grequeststoproviders'), null, $sbj, $body, array(), $sync = false);
                    $this->em->flush();
                    $data['content_view'] = 'reports/awaiting_invite_provider_view';
                    $this->load->view('page', $data);
                }
            }
            elseif (!empty($recipienttype) && !empty($recipient) && $recipienttype == 'federation')
            {
                $federations_tmp = new models\Federations;
                $federation = $federations_tmp->getOneFederationById($recipient);
                if (empty($federation))
                {
                    show_error('Federation not found', 404);
                }
                $has_write_access = $this->zacl->check_acl('f_' . $federation->getId(), 'write', 'federation', '');
                if (!$has_write_access)
                {
                    $data['error_message'] = lang('rerror_noperm_approve');
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
                }
                elseif ($type == 'Provider')
                {
                    $d = $queueObj->getData();
                    $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $d['id'], 'entityid' => $d['entityid']));
                    if (empty($provider))
                    {
                        show_error('Provider not found', 404);
                    }
                    $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $provider->getId(), 'federation' => $federation->getId()));
                    if (!empty($membership))
                    {
                        $membership->setJoinState('1');
                    }
                    else
                    {
                        $membership = new models\FederationMembers;
                        $membership->setJoinState('1');
                        $membership->setProvider($provider);
                        $membership->setFederation($federation);
                    }
                    $this->em->persist($membership);
                    $this->load->library('tracker');
                    $this->tracker->save_track(strtolower($provider->getType()), 'request', $provider->getEntityId(), 'request to join federation: ' . $federation->getName() . ' :: accepted ', false);



                    /**
                     * @todo add more recipient like fedowner or fedadmins
                     */
                    $additionalReceipients = array();
                    if ($this->config->item('notify_requester_if_queue_accepted') === TRUE)
                    {
                        $additionalReceipients[] = $queueObj->getCreator()->getEmail();
                    }
                    $sbj = "Approved:" . $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                    $body = $this->j_auth->current_user() . " just approved request.\r\n";
                    $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                    $this->em->persist($provider);
                    $this->em->remove($queueObj);
                    $this->email_sender->addToMailQueue(array('gjoinfedreq', 'joinfedreq'), $federation, $sbj, $body, $additionalReceipients, false);
                    $this->em->flush();
                    $data['content_view'] = 'reports/awaiting_invite_federation_view';
                    $this->load->view('page', $data);
                }
            }
            else
            {
                show_error('Something went wrong', 500);
            }
        }
        elseif (strcasecmp($queueAction, 'apply') == 0)
        {
            $recipient = $queueObj->getRecipient();
            $recipienttype = $queueObj->getRecipientType();
            $type = $queueObj->getType();
            $name = $queueObj->getName();
            if (strcasecmp($recipienttype, 'entitycategory') == 0 && strcasecmp($type, 'Provider') == 0 && !empty($name))
            {
                $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $recipient, 'type' => 'entcat'));
                $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
                if (empty($coc) || empty($provider))
                {
                    log_message('error', __METHOD__ . ' couldn approve request as EntityCategory with id ' . $recipient . ' or provider with entityid ' . $name . ' does not exists');
                    show_error('Entity category or provider does not exist', 404);
                    return; /// @todo finish
                }
                $isAdmin = $this->j_auth->isAdministrator();
                if (!$isAdmin)
                {
                    show_error('no permission', 403);
                    return;
                }


                $coc->setProvider($provider);
                $provider->setCoc($coc);
                $this->em->persist($provider);
                $this->em->persist($coc);
                $this->em->remove($queueObj);
                $this->em->flush();
            }
            elseif (strcasecmp($recipienttype, 'regpolicy') == 0 && strcasecmp($type, 'Provider') == 0 && !empty($name))
            {
                $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $recipient, 'type' => 'regpol'));
                $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
                if (empty($coc) || empty($provider))
                {
                    log_message('error', __METHOD__ . ' couldn approve request as RegistrationPolicy with id ' . $recipient . ' or provider with entityid ' . $name . ' does not exists');
                    show_error('Entity category or provider does not exist', 404);
                    return; /// @todo finish
                }
                $isAdmin = $this->j_auth->isAdministrator();
                if (!$isAdmin)
                {
                    show_error('no permission', 403);
                    return;
                }


                $coc->setProvider($provider);
                $provider->setCoc($coc);
                $this->em->persist($provider);
                $this->em->persist($coc);
                $this->em->remove($queueObj);
                $this->em->flush();
            }
        }
        else
        {
            
        }
    }

    function reject()
    {
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin)
        {
            $this->load->library('zacl');
            $this->load->library('j_queue');
        }
        else
        {
            redirect('auth/login', 'location');
        }

        if (strcasecmp($this->input->post('qaction'), 'reject') == 0)
        {
            $notification = $this->config->item('notify_if_queue_rejected');
            $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $this->input->post('qid')));
            $reject_access = FALSE;

            if (!empty($queueObj))
            {
                $queueAction = $queueObj->getAction();
                $creator = $queueObj->getCreator();
                $reject_access = $this->hasQAccess($queueObj);
                $recipienttype = $queueObj->getRecipientType();
                if (!empty($creator))
                {
                    $reject_access = (strcasecmp($creator->getUsername(), $this->j_auth->current_user()) == 0);
                }
                if ($reject_access === FALSE)
                {
                    if (strcasecmp($queueAction, 'Create') == 0)
                    {
                        if (strcasecmp($queueObj->getType(), 'IDP') == 0)
                        {
                            $reject_access = $this->zacl->check_acl('idp', 'create', 'entity', '');
                        }
                        elseif (strcasecmp($queueObj->getType(), 'SP') == 0)
                        {
                            $reject_access = $this->zacl->check_acl('sp', 'create', 'entity', '');
                        }
                        elseif (strcasecmp($queueObj->getType(), 'Federation') == 0)
                        {
                            $reject_access = $this->zacl->check_acl('federation', 'create', 'default', '');
                        }
                    }
                    elseif (strcasecmp($queueAction, 'Join') == 0)
                    {
                        $recipient = $queueObj->getRecipient();
                        $type = $queueObj->getType();
                        if (!empty($recipienttype) && !empty($recipient))
                        {
                            if ($recipienttype == 'provider')
                            {
                                $reject_access = $this->zacl->check_acl($recipient, 'write', 'entity', '');
                            }
                            elseif ($recipienttype == 'federation')
                            {
                                $reject_access = $this->zacl->check_acl('f_' . $recipient, 'write', 'federation', '');
                            }
                        }
                    }
                    elseif (strcasecmp($queueAction, 'Delete') == 0)
                    {
                        $type = $queueObj->getType();
                        if (strcasecmp($type, 'Federation') == 0)
                        {
                            $isAdmin = $this->j_auth->isAdministrator();
                            if ($isAdmin)
                            {
                                $reject_access = TRUE;
                            }
                        }
                    }
                    elseif (strcasecmp($queueAction, 'apply') == 0 && strcasecmp($recipienttype, 'entitycategory') == 0)
                    {
                        $isAdmin = $this->j_auth->isAdministrator();
                        if ($isAdmin)
                        {
                            $reject_access = TRUE;
                        }
                    }
                    elseif (strcasecmp($queueAction, 'apply') == 0 && strcasecmp($recipienttype, 'regpolicy') == 0)
                    {
                        $isAdmin = $this->j_auth->isAdministrator();
                        if ($isAdmin)
                        {
                            $reject_access = TRUE;
                        }
                    }
                }
                $p = $queueObj->getName();
                $qtoken = $queueObj->getToken();
                if ($reject_access === TRUE)
                {
                    $additionalReciepients = array();
                    $m_creator = $queueObj->getCreator();
                    if (!empty($m_creator))
                    {
                        $additionalReciepients[] = $m_creator->getEmail();
                    }
                    else
                    {
                        $additionalReciepients[] = $queueObj->getEmail();
                    }

                    $subject = 'Request has been canceled/rejected';
                    $body = 'Hi,' . PHP_EOL;
                    $body .= 'The request placed on ' . base_url() . PHP_EOL;
                    $body .= 'Request with tokenID: ' . $queueObj->getToken() . ' has been canceled/rejected' . PHP_EOL;
                    $body .= "";
                    log_message('debug', 'Queue with token:' . $queueObj->getToken() . ' has been canceled/rejected by ' . $this->j_auth->current_user());
                    $this->em->remove($queueObj);
                    if ($notification === TRUE)
                    {
                        $this->email_sender->addToMailQueue(array(), null, $subject, $body, $additionalReciepients, FALSE);
                    }
                    $this->em->flush();
                    $this->error_message = 'ID: ' . $p . 'with tokenID ' . $qtoken . ' has been removed from queue';
                    $data['error_message'] = $this->error_message;
                    log_message('debug', $this->error_message);
                    $data['content_view'] = 'reports/awaiting_rejected_view';
                    $this->load->view('page', $data);
                }
                else
                {
                    $data['error_message'] = lang('rerror_noperm_reject');
                    $data['content_view'] = 'error_message';
                    $this->load->view('page', $data);
                }
            }
            else
            {
                $message = $_SERVER['REQUEST_URI'];
                $message .= ' id=' . $this->input->post('qid') . ' doesnt exist in queue';
                log_message('debug', $message);
                $this->error_message = 'ID: ' . $this->input->post('qid') . ' doesnt exist in queue';
                $data['error_message'] = $this->error_message;
                $data['content_view'] = 'reports/awaiting_rejected_view';
                $this->load->view('page', $data);
            }
        }
        else
        {
            $this->error_message = 'something went wrong';
            $data['error_message'] = $this->error_message;
            $data['content_view'] = 'reports/awaiting_rejected_view';
            $this->load->view('page', $data);
        }
    }

}
