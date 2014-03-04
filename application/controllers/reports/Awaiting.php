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
 * Awaiting Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Awaiting extends MY_Controller {

    private $alert;
    private $error_message;

    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->helper('url');
        $this->load->helper('cert');
        $this->load->library('table');
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        $this->title = lang('title_approval');


        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'awaiting'));
            $this->load->library('zacl');
            $this->load->library('j_queue');
            return;
        }
        elseif(!$this->input->is_ajax_request())
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        else
        {
           show_error('session not valid',403);
        }
    }

    function alist()
    {
        $this->title = "Identity Provider (Home Organization) registration - success";
        $data['content_view'] = 'reports/awaiting_view';
        $data['message'] = $this->alert;
        $data['error_message'] = $this->error_message;

        $this->load->view('page', $data);
    }

    function ajaxrefresh()
    {
        $queueArray = $this->em->getRepository("models\Queue")->findAll();
        $kid = 0;
        foreach ($queueArray as $q)
        {
            $c_creator = 'anonymous';
            $creator = $q->getCreator();
            $request_type = $q->getType();
            $request_action = $q->getAction();
            $recipenttype = $q->getRecipientType();
            $recipientid = $q->getRecipient(); 
            $recipientname = '';
            if($recipenttype == 'provider')
            {
                $p = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$recipientid));
                if(!empty($p))
                {
                   $recipientname = $p->getName();
                }

            }
            
            $access = true;
            if (!empty($creator))
            {
                $c_creator = $creator->getUsername();
            }
            if ($access)
            {
                $data['list'][$kid]['requester'] = $c_creator;
                $data['list'][$kid]['idate'] = $q->getCreatedAt();
                $data['list'][$kid]['datei'] = $q->getCreatedAt();
                $data['list'][$kid]['iname'] = $q->getName();
                $data['list'][$kid]['qid'] = $q->getID();
                $data['list'][$kid]['mail'] = $q->getEmail();
                $data['list'][$kid]['recipientname'] = $recipientname;
                $data['list'][$kid]['type'] = $q->getType();
                $data['list'][$kid]['action'] = $q->getAction();
                $data['list'][$kid]['token'] = $q->getToken();
                $data['list'][$kid]['confirmed'] = $q->getConfirm();
                $kid++;
            }
        }


        $data['message'] = $this->session->flashdata('message');
        $data['error_message'] = $this->session->flashdata('error_message');
        $this->title = "Identity Provider (Home Organization) registration - success";

        $data['content_view'] = 'reports/awaiting_list_view';
        $this->load->view('reports/awaiting_list_view', $data);
    }
    function dashajaxrefresh()
    {
       if(!$this->j_auth->logged_in())
       {
          show_error('invalid session', 403);
       }
       if($this->input->is_ajax_request())
        {
        $queueArray = $this->em->getRepository("models\Queue")->findAll();
        $kid = 0;
        foreach ($queueArray as $q)
        {
            $c_creator = 'anonymous';
            $creator = $q->getCreator();
            $request_type = $q->getType();
            $request_action = $q->getAction();
            $recipientid = $q->getRecipient(); 
            $recipenttype = $q->getRecipientType();
            $recipientname = '';
            if($recipenttype == 'provider')
            {
                $p = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$recipientid));
                if(!empty($p))
                {
                   $recipientname = $p->getName();
                }

            }
            $access = true;
            if (!empty($creator))
            {
                $c_creator = $creator->getUsername();
            }
            if ($access)
            {
                $data['list'][$kid]['requester'] = $c_creator;
                $data['list'][$kid]['idate'] = $q->getCreatedAt();
                $data['list'][$kid]['datei'] = $q->getCreatedAt();
                $data['list'][$kid]['iname'] = $q->getName();
                $data['list'][$kid]['qid'] = $q->getID();
                $data['list'][$kid]['mail'] = $q->getEmail();
                $data['list'][$kid]['type'] = $q->getType();
                $data['list'][$kid]['action'] = $q->getAction();
                $data['list'][$kid]['recipientname'] = $recipientname;
                $data['list'][$kid]['token'] = $q->getToken();
                $data['list'][$kid]['confirmed'] = $q->getConfirm();
                $kid++;
            }
        }

        $data['content_view'] = 'reports/dashawaiting_list_view';
        $this->load->view('reports/dashawaiting_list_view', $data);
        }
       else
       {
            echo "lll2";
        }
        
    }

    private function idpDetails($queueList)
    {
        $objData = null;
        $data = $queueList->getData();
        $objType = $queueList->getObjType();
        $objData = new models\Provider;
        $objData->importFromArray($data);
    }

    
    
    function detail($token)
    {

        $queueList = $this->em->getRepository("models\Queue")->findOneBy(array('token' => $token));
        if (!empty($queueList))
        {
            $objData = null;
            $data = $queueList->getData();
            $objType = $queueList->getObjType();

            if ($objType == 'Provider')
            {


                if ($queueList->getAction() == 'Create')
                {
                    $objData = new models\Provider;
                    $objData->importFromArray($data);
                    /* build table with details */
                    $data['provider'] = $this->j_queue->displayRegisterProvider($queueList);
                    $i = max(array_keys( $data['provider'] ));
                              
                    $buttons = $this->j_queue->displayFormsButtons($queueList->getId());
                    $data['provider'][++$i]['2cols'] = $buttons;
                    $data['obj'] = $objData;
                   
                    $data['content_view'] = 'reports/awaiting_provider_register_view';
                    $data['error_message'] = $this->error_message;

                    $this->load->view('page', $data);
                }
                elseif($queueList->getAction() == 'Join')
                {
                    /**
                     *@todo display details when provider requestes to join federation 
                     */
                    if($queueList->getRecipientType() == 'federation')
                    {
                          $recipient_write_access = $this->zacl->check_acl('f_'.$queueList->getRecipient(), 'write', 'federation', '');
                          $requestor_view_access = (boolean) $queueList->getCreator()->getUsername() === $this->j_auth->current_user();
                          if ($requestor_view_access or $recipient_write_access)
                          {
                               
                                 $result = $this->j_queue->displayInviteFederation($queueList);
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
                          $this->load->view('page', $data);
                    } 
                }
                else
                {
                    echo "OO modify";
                }
            }
            elseif ($objType == 'Federation')
            {
                if ($queueList->getAction() == 'Create')
                {

                     
                    $fedrows = $this->j_queue->displayRegisterFederation($queueList);
                    $fedrows[]['2cols'] = $this->j_queue->displayFormsButtons($queueList->getId());
                    $data['fedrows'] = $fedrows;
                }
                elseif ($queueList->getAction() == 'Join')
                {
                    if ($queueList->getRecipientType() == 'provider')
                    {
                        $recipient_write_access = $this->zacl->check_acl($queueList->getRecipient(), 'write', 'entity', '');
                        $requestor_view_access = (boolean) $queueList->getCreator()->getUsername() === $this->j_auth->current_user();
                        if ($requestor_view_access or $recipient_write_access)
                        {
                            $result = $this->j_queue->displayInviteProvider($queueList);
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
                        $this->load->view('page', $data);
                        return;
                    }
                }
                elseif($queueList->getAction() == 'Delete')
                {
                    $fedrows = $this->j_queue->displayDeleteFederation($queueList);
                    $fedrows[]['2cols'] = $this->j_queue->displayFormsButtons($queueList->getId());
                    $data['fedrows'] = $fedrows;
                     
                }
                else
                {
                    $data['error'] = 'Unknown action';
                }
                $data['content_view'] = 'reports/awaiting_federation_register_view';
                $this->load->view('page', $data);
            }
            else
            {
                $data['error'] = 'Unknown type';
            }
        }
        else
        {
            $data['content_view'] = 'error_message';
            $data['error_message'] = lang('rerror_qid_noexist');
            $this->load->view('page',$data);
        }
    }

    private function _idpDetail($id, $action)
    {
        
    }

    private function _idpCreateApprove($obj)
    {
        
    }

    function approve()
    {
        log_message('debug', 'approve');
        $message = "";
        $error_message = null;
        if ($this->input->post('qaction') === 'approve')
        {
            $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $this->input->post('qid')));
            if (!empty($queueObj))
            {
                log_message('debug',  'queue object is not empty');
                if (($queueObj->getAction() === 'Create') && ($queueObj->getType() === 'IDP'))
                {
                    $approve_allowed = $this->zacl->check_acl('idp', 'create', 'entity', '');
                    if ($approve_allowed)
                    {
                        $idp = new models\Provider;
                        $idp->importFromArray($queueObj->getData());
  
                        //echo $idp->getName();

                        $idp_check = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $idp->getEntityId()));
                        if (!empty($idp_check))
                        {
                            $this->error_message = "Identity Provider " . $idp->getName() . " (" . $idp->getEntityId() . ") already exists";
                            return $this->detail($queueObj->getToken());
                        }
                        else
                        {
                            $idp->setNameId();
                            $idp->setAsLocal();
                            $idp->setHomeUrl($idp->getHelpdeskUrl());
                            $fed = $idp->getFederations()->get(0);
                            if(!empty($fed))
                            {
                               $fed2 = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
                               $idp->removeFederation($fed);
                            }
                            foreach ($idp->getCertificates()->getValues() as $o)
                            {
                                $o->setCertType('x509');
                                $o->generateFingerprint();
                            }
                            $creator = $queueObj->getCreator();
                            if(!empty($fed2) and $fed instanceOf models\Federation )
                            {
                                 $membership = new models\FederationMembers;
                                 $membership->setJoinState('1');
                                 $membership->setProvider($idp);
                                 $membership->setFederation($fed2);
                                 $this->em->persist($membership);
                            }
                            
                            $this->em->persist($idp);
                            $this->em->remove($queueObj);
                            $requester_recipient = null;
                            if (!empty($creator) && ($creator instanceOf models\User))
                            {
                               /*
                                $a_res = $idp->getId();
                                $a_resource_type = "entity";
                                $a_group = strtolower($idp->getType());
                                $this->zacl->add_access_toUser($a_res, 'manage', $creator, $a_group, $a_resource_type);
                                $this->zacl->add_access_toUser($a_res, 'write', $creator, $a_group, $a_resource_type);
                                $this->zacl->add_access_toUser($a_res, 'read', $creator, $a_group, $a_resource_type);
                                */
                                $requester_recipient = $creator->getEmail();
                            }
                            if(empty($requester_recipient))
                            {
                                $requester_recipient = $queueObj->getEmail();
                            }
                            $sbj = 'Identity Provider has been approved';
                            $body = 'Dear user,'.PHP_EOL;
                            $body .= 'Registration request: '.$idp->getName() .' ('.$idp->getEntityId().')'.PHP_EOL;
                            $body .= 'Requested by: '.$requester_recipient.''.PHP_EOL;
                            $body .= 'Request has been just approved by '.$this->j_auth->current_user().' and added to the system'.PHP_EOL;
                            $body .= 'It can be reviewed on '.base_url().' '.PHP_EOL;
                            $additionalReceipents = array();
                            $toNotifyRequester = $this->config->item('notify_requester_if_queue_accepted');
                            if(!empty($toNotifyRequester))
                            {
                                $additionalReceipents[] = $requester_recipient;
                            }
                            $this->email_sender->addToMailQueue(array('greqisterreq','gidpregisterreq'),null,$sbj,$body,$additionalReceipents,FALSE);
                            $this->em->flush();
                            $success_message = "Identity Provider has been added. Please set correct permissions.";
                            $data['content_view'] = 'reports/awaiting_approved_view';
                            $data['success_message'] = $success_message;
                            $this->load->view('page', $data);
                        }
                    }
                    else
                    {
                        $data['error_message'] = lang('rerror_noperm_approve');
                        $data['content_view'] = 'error_message';
                        $this->load->view('page',$data);
                    }
                }
                elseif (($queueObj->getAction() === 'Create') && ($queueObj->getType() === 'SP'))
                {

                    $approve_allowed = $this->zacl->check_acl('sp', 'create', 'entity', '');
                    if ($approve_allowed)
                    {
                        $sp = new models\Provider;
                        $sp->importFromArray($queueObj->getData());
                        $sp_check = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $sp->getEntityId()));
                        if ($sp_check)
                        {
                            $this->error_message = "Service Provider " . $sp->getName() . " (" . $sp->getEntityId() . ") already exists";
                            return $this->detail($queueObj->getToken());
                        }
                        else
                        {
                            //$sp->setNameId();
                            $sp->setAsLocal();
                            $tfeds = $sp->getFederations();
                            if(!empty($tfeds) && $tfeds->count() > 0)
                            {
                               $fed = $sp->getFederations()->get(0);
                               $fed2 = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
                               $sp->removeFederation($fed);
                               $membership = new models\FederationMembers;
                               $membership->setJoinState('1');
                               $membership->setProvider($sp);
                               $membership->setFederation($fed2);
                               $this->em->persist($membership);
                            }
                            foreach ($sp->getCertificates()->getValues() as $o)
                            {
                                $o->setCertType('x509');
                                $o->generateFingerprint();
                            }
                            $creator = $queueObj->getCreator();
                            $this->em->persist($sp);
                            $this->em->remove($queueObj);
                            $requester_recipient ='';
                            if (!empty($creator) && ($creator instanceOf models\User))
                            {
                            /*
                                $a_res = $sp->getId();
                                $a_resource_type = "entity";
                                $a_group = strtolower($sp->getType());
                                $this->zacl->add_access_toUser($a_res, 'manage', $creator, $a_group, $a_resource_type);
                                $this->zacl->add_access_toUser($a_res, 'write', $creator, $a_group, $a_resource_type);
                                $this->zacl->add_access_toUser($a_res, 'read', $creator, $a_group, $a_resource_type);
                             */
                                $requester_reciepient = $creator->getEmail();
                            }
                            if(empty($requester_recipient))
                            {
                                $requester_recipient = $queueObj->getEmail();
                            }
                            $sbj = 'Service Provider has been approved';
                            $body = 'Hi,'.PHP_EOL;
                            $body .= 'Registration request: '.$sp->getName(). '('.$sp->getEntityId().')'.PHP_EOL;
                            $body .='Requested by: '.$requester_recipient.''.PHP_EOL;
                            $body .='Request has been just approved by '.$this->j_auth->current_user().' and added to the system'.PHP_EOL;
                            $body .= 'It can be reviewed on '.base_url().' '.PHP_EOL;
                            

                            $additionalRcpts = array();

                            $toNotifyRequester = $this->config->item('notify_requester_if_queue_accepted');
                            if(!empty($toNotifyRequester))
                            {
                                $additionalRcpts[] = $requester_recipient;
                            }
                            $this->email_sender->addToMailQueue(array('greqisterreq','gspregisterreq'),null,$sbj,$body,$additionalRcpts,FALSE) ;
                            $this->em->flush();
                            $success_message = "Service Provider has been added";
                            $data['content_view'] = 'reports/awaiting_approved_view';
                            $data['success_message'] = $success_message;
                            $this->load->view('page', $data);
                        }
                    }
                    else
                    {
                        $data['error_message'] = lang('rerror_noperm_approve');
                        $data['content_view'] = 'error_message';
                        $this->load->view('page',$data);
                    }
                }
                elseif  (($queueObj->getAction() === 'Delete') && ($queueObj->getType() === 'Federation'))
                {
                    $isAdministrator = $this->j_auth->isAdministrator();
                    if(!$isAdministrator)
                    {
                        $data['error_message'] = lang('rerror_noperm_approve');
                        $data['content_view'] = 'error_message';
                        $this->load->view('page',$data);
                        return;
                    }
                    $fed = new models\Federation;
                    $fed->importFromArray($queueObj->getData());
                    $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name'=>$fed->getName()));
                    if(empty($federation))
                    {
                        $data['error_message'] = 'Federation not found';
                        $data['content_view'] = 'error_message';
                        $this->load->view('page',$data);
                        return;
                    }
                    $isActive = $federation->getActive();
                    if($isActive)
                    {
                        $data['error_message'] = 'Federation is active , cannot delete';
                        $data['content_view'] = 'error_message';
                        $this->load->view('page',$data);
                        return;
                    }
                    $fed = null;
                    $this->load->library('FederationRemover');
                    $sbj = 'Federation has been removed';
                    $body = 'Dear user,'.PHP_EOL;
                    $body .= 'Federation : '.$federation->getName() . ' has been removed from the system';
                    $this->email_sender->addToMailQueue(array(),null,$sbj,$body,array(),$sync=false);
                    $this->federationremover->removeFederation($federation);
                    $this->em->remove($queueObj);
                    $this->em->flush();

                    
                }
                elseif (($queueObj->getAction() === 'Create') && ($queueObj->getType() === 'Federation'))
                {
                    $approve_allowed = $this->zacl->check_acl('federation', 'create', 'default', '');
                    if ($approve_allowed)
                    {
                        $fed = new models\Federation;

                        $fed->importFromArray($queueObj->getData());
                        $creator = $queueObj->getCreator();
                        if (!empty($creator))
                        {
                            $fed->setOwner($creator->getUsername());
                        }

                        $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
                        if(empty($fed_check))
                        {
                              $fed_check = $this->em->getRepository("models\Federation")->findOneBy(array('urn' => $fed->getUrn()));
                        }

                        if ($fed_check)
                        {
                            $error_message = "Federation already exists with provided name or urn";
                            $data['error_message'] = $error_message;
                            $data['content_view'] = 'error_message';
                            $this->load->view('page',$data);
                            return;
                            
                        }
                        else
                        {
                            $fedname = $queueObj->getName();
                            $this->em->persist($fed);
                            $this->em->remove($queueObj);
                            $this->em->flush();
                            $acl_res = new models\AclResource;
                            $acl_res->setResource('f_'.$fed->getId());
                            $acl_res->setType('federation');
                            $acl_res->setDefaultValue('read');
                            
                            
                            $parent_res = $this->em->getRepository("models\AclResource")->findOneBy(array('resource'=>'federation'));
                            $acl_res->setParent($parent_res);
                            $this->em->persist($acl_res);
                            $this->em->flush();
                          
                            $message = "Federation " . $fedname . "witch ID:".$fed->getId()." has been added";
                            log_message('debug',"Federation " . $fedname . "witch ID:".$fed->getId()." has been added");
                           

                        }
                    }
                    else
                    {
                        $data['error_message'] = lang('rerror_noperm_approve');
                        $data['content_view'] = 'error_message';
                        $this->load->view('page',$data);
                      
                    }
                }
                /**
                 *          JOIN - accept request (by provider) sent by federation to provider
                 */
                elseif (($queueObj->getAction() === 'Join'))
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
                             $this->load->view('page',$data);
                        }
                        elseif ($type == 'Federation')
                        {
                            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $queueObj->getName()));
                            if (empty($federation))
                            {
                                show_error('Federation not found', 404);
                                return;
                            }
                            $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider'=>$provider->getId(),'federation'=>$federation->getId));
                            if(!empty($membership))
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
                            $this->email_sender->addToMailQueue(array('grequeststoproviders'),null,$sbj,$body,array(),$sync=false);
                            $this->em->flush();
                            $data['content_view'] = 'reports/awaiting_invite_provider_view';
                            $this->load->view('page', $data);
                        }
                    }
                    elseif(!empty($recipienttype) && !empty($recipient) && $recipienttype == 'federation')
                    {
                        $federations_tmp = new models\Federations; 
                        $federation = $federations_tmp->getOneFederationById($recipient);
                        if(empty($federation))
                        {
                           show_error('Federation not found', 404);
                        }
                        $has_write_access = $this->zacl->check_acl('f_'.$federation->getId(), 'write', 'federation', '');
                        if (!$has_write_access)
                        {
                             $data['error_message'] = lang('rerror_noperm_approve');
                             $data['content_view'] = 'error_message';
                             $this->load->view('page',$data);
                        }
                        elseif ($type == 'Provider')
 			{
                            $d = $queueObj->getData(); 
                            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $d['id'],'entityid'=>$d['entityid']));
                            if(empty($provider)) 
                            {
                                show_error('Provider not found', 404);
                            }
                            $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider'=>$provider->getId(),'federation'=>$federation->getId()));
                            if(!empty($membership))
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



                            /**
                             * @todo add more recipient like fedowner or fedadmins
                             */
                            $additionalReceipients = array();
                            if($this->config->item('notify_requester_if_queue_accepted') === TRUE)
                            {
			         $additionalReceipients[] = $queueObj->getCreator()->getEmail();
                            }
                            $sbj = "Approved:".$provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                            $body = $this->j_auth->current_user() . " just approved request.\r\n";
                            $body .= 'Since now Provider: ' . $provider->getName() . ' becomes a member of ' . $federation->getName() . PHP_EOL;
                            $this->em->persist($provider);
                            $this->em->remove($queueObj);
                            $this->email_sender->addToMailQueue(array('gjoinfedreq','joinfedreq'),$federation,$sbj,$body,$additionalReceipients,false);
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
                else
                {
                    
                }
            }
            else
            {
                $message = $_SERVER['REQUEST_URI'];
                $message .= ' id=' . $this->input->post('qid') . ' doesnt exist in queue';
                log_message('debug', $message);
                
                $data['error_message'] = 'Can\'t approve it because this request dosn\'t exist';
                $data['content_view'] = 'error_message';
                $this->load->view('page',$data);
            }
        }


        // $message = 'ID: ' . $p . ' has been added';
        log_message('debug', $message);
        $this->session->set_flashdata('message', $message);
        $this->session->set_flashdata('error_message', $error_message);
        redirect(base_url() . "reports/awaiting", 'location');
    }

    function reject()
    {
        if ($this->input->post('qaction') === 'reject')
        {
            $notification = $this->config->item('notify_if_queue_rejected');
            $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $this->input->post('qid')));
            $reject_access = FALSE;
            if (!empty($queueObj))
            {
                $creator = $queueObj->getCreator();
                if(!empty($creator))
                {
                    $reject_access = (bool) $creator->getUsername() === $this->j_auth->current_user();
                }
                if($reject_access === FALSE)
                {
                    if ($queueObj->getAction() === 'Create')
                    {
                         if($queueObj->getType() === 'IDP')
                         {
                              $reject_access = $this->zacl->check_acl('idp', 'create', 'entity', '');
                         }
                         elseif($queueObj->getType() === 'SP')
                         {
                              $reject_access = $this->zacl->check_acl('sp', 'create', 'entity', '');
                         }
                         elseif($queueObj->getType() === 'Federation')
                         {
                              $reject_access = $this->zacl->check_acl('federation', 'create', 'default', '');
                         }
                    }
                    elseif (($queueObj->getAction() === 'Join'))
                    {
                       $recipient = $queueObj->getRecipient();
                       $recipienttype = $queueObj->getRecipientType();
                       $type = $queueObj->getType();
                       if (!empty($recipienttype) && !empty($recipient))
                       {
                           if($recipienttype == 'provider')
                           {
                               $reject_access = $this->zacl->check_acl($recipient, 'write', 'entity', '');
                           }
                           elseif($recipienttype == 'federation')
                           {
                               $reject_access = $this->zacl->check_acl('f_'.$recipient, 'write', 'federation', '');
                           }
                       }
                    }
                    elseif(($queueObj->getAction() === 'Delete'))
                    {
                        $type = $queueObj->getType();
                        if($type === 'Federation')
                        {
                           $isAdmin = $this->j_auth->isAdministrator();
                           if($isAdmin)
                           {
                                $reject_access = TRUE;
                           }
                        }

                    }
         
                }
                $p = $queueObj->getName();
                $qtoken = $queueObj->getToken();
                if($reject_access === TRUE)
                {
                    $additionalReciepients = array();
                    $m_creator = $queueObj->getCreator();
                    if(!empty($m_creator))
                    {
                       $additionalReciepients[] = $m_creator->getEmail();
                    }
                    else
                    {
                       $additionalReciepients[] = $queueObj->getEmail();
                    }
                    
                    $subject = 'Your request has been rejected';
                    $body = "Hi,\r\n";
                    $body .= "Your request placed on ".base_url()."\r\n";
                    $body .= "Unfortunately your request with tokenID: ".$queueObj->getToken()." has been rejected\r\n";
                    $body .= "";
                    log_message('debug','Queue with token:'.$queueObj->getToken().' has been rejected by '.$this->j_auth->current_user());
                    $this->em->remove($queueObj);
                    if($notification === TRUE)
                    {
                       $this->email_sender->addToMailQueue(array(),null,$subject,$body,$additionalReciepients,FALSE);
                    }
                    $this->em->flush();
                    $this->error_message = 'ID: ' . $p . 'with tokenID '.$qtoken.' has been removed from queue';
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
