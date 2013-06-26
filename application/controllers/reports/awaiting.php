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
        $this->title = "Approval";


        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'awaiting'));
            $this->load->library('zacl');
            $this->load->library('j_queue');
            return;
        }
        else
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
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
                                 $idp->setFederation($fed2);
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
                            $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
                            $a_members = $a->getMembers();
                            foreach ($a_members as $m)
                            {
                                $admin_recipients[] = $m->getEmail();
                            }
                            $admin_recipients = array_unique($admin_recipients);
                            $mail_sbj = "Identity Provider has been approved";
                            $mail_body ="Hi,\r\n";
                            $mail_body .= $idp->getEntityId()." has been just approved and added to Resource Registry\r\n";
                            $mail_body .= "on ".base_url()."\r\n";

 
                            $this->em->flush();
                            $this->load->library('email_sender');
                            if($this->config->item('notify_requester_if_queue_accepted') === TRUE)
                            {
                                 $this->email_sender->send($requester_recipient,$mail_sbj,$mail_body);
                            }
                            if($this->config->item('notify_admins_if_queue_accepted') === TRUE)
                            {         
                                 $this->email_sender->send($admin_recipients,$mail_sbj,$mail_body);
                            }

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
                            $sp->setNameId();
                            $sp->setAsLocal();
                            $tfeds = $sp->getFederations();
                            if(!empty($tfeds) && $tfeds->count() > 0)
                            {
                               $fed = $sp->getFederations()->get(0);
                               $fed2 = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $fed->getName()));
                               $sp->removeFederation($fed);
                               $sp->setFederation($fed2);
                            }
                            foreach ($sp->getCertificates()->getValues() as $o)
                            {
                                $o->setCertType('x509');
                                $o->generateFingerprint();
                            }
                            $creator = $queueObj->getCreator();
                            $this->em->persist($sp);
                            $this->em->remove($queueObj);
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
                            $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
                            $a_members = $a->getMembers();
                            foreach ($a_members as $m)
                            {
                                $admin_recipients[] = $m->getEmail();
                            }
                            $admin_recipients = array_unique($admin_recipients);
                            $mail_sbj = "Service Provider has been approved";
                            $mail_body ="Hi,\r\n";
                            $mail_body .= $sp->getEntityId()." has been just approved and added to Resource Registry\r\n";
                            $mail_body .= "on ".base_url()."\r\n";
                            
                            $this->em->flush();
                            $this->load->library('email_sender');
                            if($this->config->item('notify_requester_if_queue_accepted') === TRUE)
                            {
                                 $this->email_sender->send($requester_recipient,$mail_sbj,$mail_body);
                            }
                            if($this->config->item('notify_admins_if_queue_accepted') === TRUE)
                            {         
                                 $this->email_sender->send($admin_recipients,$mail_sbj,$mail_body);
                            }
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
                                exit();
                            }
                            $provider->setFederation($federation);
                            $contacts = $provider->getContacts();
                            $mail_recipients = array();
                            $mail_recipients[] = $queueObj->getCreator()->getEmail();
                            $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
                            $a_members = $a->getMembers();
                            foreach ($a_members as $m)
                            {
                                $mail_recipients[] = $m->getEmail();
                            }
                            foreach ($contacts as $cnt)
                            {
                                $mail_recipients[] = $cnt->getEmail();
                            }
                            $mail_recipients = array_unique($mail_recipients);
                            $sbj = $provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                            $body = $this->j_auth->current_user() . " just approved request.\r\n";
                            $body .= 'Since now Provider: ' . $provider->getName() . 'becomes a member of ' . $federation->getName() . '\r\n';
                            $this->em->persist($provider);
                            $this->em->remove($queueObj);
                            if ($this->em->flush())
                            {
                                $this->load->library('email_sender');
                                $this->email_sender->send($mail_recipients, $sbj, $body);
                                $data['content_view'] = 'reports/awaiting_invite_provider_view';
                                $this->load->view('page', $data);
                            }
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
                            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $d['id'],'name'=>$d['name'],'entityid'=>$d['entityid']));
                            if(empty($provider)) 
                            {
                                show_error('Provider not found', 404);
                            }
                            $provider->setFederation($federation);
                            /**
                             * @todo add more recipient like fedowner or fedadmins
                             */
                            $mail_recipients = array();
                            if($this->config->item('notify_requester_if_queue_accepted') === TRUE)
                            {
			         $mail_recipients[] = $queueObj->getCreator()->getEmail();
                            }
                            if($this->config->item('notify_admins_if_queue_accepted') === TRUE)
                            {
                                 $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
                                 $a_members = $a->getMembers();
                                 foreach ($a_members as $m)
                                 {
                                      $mail_recipients[] = $m->getEmail();
                                 }
                            }
                            $mail_recipients = array_unique($mail_recipients);
                            $sbj = "Approved:".$provider->getName() . ' joins federation: "' . $federation->getName() . '"';
                            $body = $this->j_auth->current_user() . " just approved request.\r\n";
                            $body .= 'Since now Provider: ' . $provider->getName() . 'becomes a member of ' . $federation->getName() . '\r\n';
                            $this->em->persist($provider);
                            $this->em->remove($queueObj);
                            if($this->em->flush())
                            {
                                 $this->load->library('email_sender');
                                 if(count($mail_recipients) > 0)
                                 {
                                    $this->email_sender->send($mail_recipients, $sbj, $body);
                                 }
                                 $data['content_view'] = 'reports/awaiting_invite_federation_view';
                                 $this->load->view('page', $data);

                            }
                           

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
         
                }
                $p = $queueObj->getName();
                $qtoken = $queueObj->getToken();
                if($reject_access === TRUE)
                {
                    $this->load->library('email_sender');
                    $m_creator = $queueObj->getCreator();
                    if(!empty($m_creator))
                    {
                       $mail_reciepient = $m_creator->getEmail();
                    }
                    else
                    {
                       $mail_reciepient = $queueObj->getEmail();
                    }
                    $mail_sbj = 'Your request has been rejected';
                    $mail_body = "Hi,\r\n";
                    $mail_body .= "Your request placed on ".base_url()."\r\n";
                    $mail_body .= "Unfortunately your request with tokenID: ".$queueObj->getToken()." has been rejected\r\n";
                    $mail_body .= "";
                    log_message('debug','Queue with token:'.$queueObj->getToken().' has been rejected by '.$this->j_auth->current_user());
                    $this->em->remove($queueObj);
                    $this->em->flush();
                    if($notification === TRUE)
                    {
                        $this->email_sender->send($mail_reciepient,$mail_sbj,$mail_body);
                    }
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
