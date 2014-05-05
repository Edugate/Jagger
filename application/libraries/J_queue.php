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
    function displayFormsButtons($qid)
    {
        /* add approve form */
        $approve_hidden_attributes = array('qaction' => 'approve', 'qid' => $qid, 'setfederation' => 'yes');
        $approve_attrid = array('id' => 'approvequeue');
        $approve_form = form_open('reports/awaiting/approve', $approve_attrid, $approve_hidden_attributes);
        $approve_form .= '<button type="submit" name="mysubmit" value="Accept request!" class="savebutton saveicon">' . lang("rr_submitapprove") . '</button>';
        $approve_form .= form_close();
        /* add reject form */
        $reject_hidden_attributes = array('qaction' => 'reject', 'qid' => $qid);
        $reject_attrid = array('id' => 'rejectqueue');
        $reject_form = form_open('reports/awaiting/reject', $reject_attrid, $reject_hidden_attributes);
        $reject_form .= '<button type="submit" name="mysubmit" value="Reject request!" class="resetbutton reseticon">' . lang("rr_submitreject") . '</button>';
        $reject_form .= form_close();


        $result = '<div class="buttons" >' . $reject_form . '&nbsp;' . $approve_form . '</div>';
        return $result;
    }


    function createUserFromQueue(models\Queue $q)
    {
         $objdata = $q->getData();
         if(!is_array($objdata))
         {
            log_message('error',__METHOD__.' data not in array');
            return false;
         }
         if(!isset($objdata['username']) || !isset($objdata['email']) || !isset($objdata['type']))
         {
            log_message('error',__METHOD__.' data doesnt contain information about username/email');
            return false;
         }
         $checkuser = $this->em->createQuery("SELECT u FROM models\User u WHERE u.username = '{$objdata['username']}' OR u.email = '{$objdata['email']}'")->getResult();
         
 
         if($checkuser)
         {
             $this->ci->globalerrors[] = lang('useralredyregistered');
             $this->ci->globalerrors[] = lang('queremoved');
             log_message('error',__METHOD__. ' User '.$objdata['username'].' already exists, remove request from the queue with id: '.$q->getId());
             $this->em->remove($q);
             $this->em->flush();
             return false;
         }
         $u = new models\User;
         $u->setUsername($objdata['username']);
         $u->setEmail($objdata['email']);
         $type = $objdata['type'];
         if($type === 'federated')
         {
            $u->setFederatedEnabled();
         }
         else
         {
            if ($type === 'local')
            {
               $u->setLocalEnabled();
            }
            elseif($type === 'both')
            {
               $u->setFederatedEnabled();
               $u->setLocalEnabled();
            }
         }
         $u->setAccepted();

         if(!empty($objdata['fname']))
         {
            $u->setGivenname($objdata['fname']);
         }
         if(!empty($objdata['sname']))
         {
            $u->setSurname($objdata['sname']);
         }
         $u->setEnabled();         
         $u->setSalt();
         if(!empty($objdata['pass']))
         {
            $u->setPassword($objdata['pass']);
         }
         else
         {
            $u->setRandomPassword();
         }
       
         $u->setValid();
         $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Member'));
         if (!empty($member)) {
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
         $m_body = 'Dear user,'.PHP_EOL;
         $m_body .= 'User registration request to use the service '.base_url().' has been accepted'.PHP_EOL;
         $m_body .= 'Details:'.PHP_EOL;
         $m_body .= 'Username: '.$u->getUsername().PHP_EOL;
         $m_body .= 'E-mail: '.$u->getEmail().PHP_EOL;
         $reciepient[] = $u->getEmail();
         $this->ci->email_sender->addToMailQueue(array(), null, $m_subj, $m_body, $reciepient, $sync = false);
         return true;
      // $this->em->flush();

         

    }

    function displayRegisterUser(models\Queue $q)
    {
       $objdata = $q->getData();
       $r = array();
       $r[] = array('header'=>lang('request'));
       $r[] = array('name'=>lang('type'), 'value'=>'user registration');
       $creator = $q->getCreator();
       if ($creator) {
           $r[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
       }
       else {
           $r[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
       }
       $r[] = array('name' => lang('rr_regdate'), 'value' => $q->getCreatedAt());
       $r[] = array('name' => lang('rr_username'), 'value' => $q->getName());
       $r[] = array('name' => lang('rr_uemail'), 'value' => $objdata['email']);
       $r[] = array('name' => lang('rr_fname'), 'value' => $objdata['fname']);
       $r[] = array('name' => lang('rr_lname'), 'value' => $objdata['sname']);
       if(isset($objdata['ip']))
       {
          $r[] = array('name' => 'IP', 'value' => $objdata['ip']);
       }
       if(isset($objdata['type']))
       {
          if($objdata['type'] === 'federated')
          {
            $r[] = array('name' => 'Type of account', 'value' => ''.lang('rr_onlyfedauth').'');
          }
          elseif($objdata['type'] === 'local')
          {
            $r[] = array('name' => 'Type of account', 'value' => ''.lang('rr_onlylocalauthn').'');

          }
          elseif($objdata['type'] === 'both')
          {
            $r[] = array('name' => 'Type of account', 'value' => ''.lang('rr_bothauth').'');
          }
          else
          {
            $r[] = array('name' => 'Type of account', 'value' => '<span class="alert">'.lang('unknown').'</span>');

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
        if ($creator) {
            $fedrows[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
            $objData->setOwner($creator->getUsername());
        }
        else {
            $fedrows[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }

        $fedrows[] = array('name' => lang('rr_regdate'), 'value' => $q->getCreatedAt());
        $fedrows[] = array('header' => lang('rr_basicinformation'));
        $fedrows[] = array('name' => lang('rr_fed_name'), 'value' => $objData->getName());
        $fedrows[] = array('name' => lang('rr_fed_urn'), 'value' => $objData->getUrn());
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
        if ($creator) {
            $fedrows[] = array('name' => lang('requestor'), 'value' => $creator->getUsername());
            $objData->setOwner($creator->getUsername());
        }
        else {
            $fedrows[] = array('name' => lang('requestor'), 'value' => lang('unknown'));
        }

        $fedrows[] = array('name' => lang('rr_requestdate'), 'value' => $q->getCreatedAt());
        $fedrows[] = array('header' => lang('rr_basicinformation'));
        $fedrows[] = array('name' => lang('rr_fed_name'), 'value' => $objData->getName());
        $fedrows[] = array('name' => lang('rr_fed_urn'), 'value' => $objData->getUrn());

        return $fedrows;
    }

    function displayRegisterProvider(models\Queue $q)
    {
        $objData = null;
        $data = $q->getData();
        $objType = $q->getObjType();
        $objData = new models\Provider;
        $objData->importFromArray($data);
        $i = 0;
        $provider[$i++]['header'] = lang('rr_basicinformation');
        $provider[$i]['name'] = lang('rr_homeorganisationname');
        $provider[$i++]['value'] = $objData->getName();

        $provider[$i]['name'] = 'entityID';

        $provider[$i++]['value'] = $objData->getEntityId();
        $type = $objData->getType();
        if ($type === 'IDP') {
            $provider[$i]['name'] = lang('type');
            $provider[$i++]['value'] = lang('identityprovider');

            $provider[$i]['name'] = lang('rr_scope').' <br /><small>IDPSSODescriptor</small>';
            $provider[$i++]['value'] = implode(';',$objData->getScope('idpsso'));
        }
        elseif ($type === 'SP') {
            $provider[$i]['name'] = lang('type');
            $provider[$i++]['value'] = lang('serviceprovider');
        }

        $provider[$i]['name'] = lang('rr_helpdeskurl');
        $provider[$i++]['value'] = $objData->getHelpdeskUrl();

        foreach ($objData->getFederations() as $fed) {
            $provider[$i]['name'] = lang('rr_federation');
            $provider[$i]['value'] = $fed->getName();
            $i++;
        }
        $provider[$i++]['header'] = lang('rr_servicelocations');
        foreach ($objData->getServiceLocations() as $service) {
            $provider[$i]['name'] = $service->getType();
            $provider[$i]['value'] = "" . $service->getUrl() . "<br /><small>" . $service->getBindingName() . " ; index: ".$service->getOrder()."</small><br />";
            $i++;
        }
        $provider[$i++]['header'] = lang('rr_supportednameids');
        $provider[$i]['name'] = lang('nameid');
        if($type === 'IDP')
        {
          $provider[$i++]['value'] = implode(', ', $objData->getNameIds('idpsso'));
        }
        elseif($type ==='SP')
        {
            $provider[$i++]['value'] = implode(', ', $objData->getNameIds('spsso'));
        }



        $provider[$i++]['header'] = lang('rr_certificates');
        foreach ($objData->getCertificates() as $cert) {
            $provider[$i]['name'] = "Certificate (" . $cert->getCertUse() . ")";
            $certdatacell = reformatPEM($cert->getCertdata());


            $provider[$i]['value'] = "<span class=\"span-10\"><code>" . $certdatacell . "</code></span>";
            $i++;
        }

        $provider[$i++]['header'] = lang('rr_contacts');
        foreach ($objData->getContacts() as $contact) {
            $provider[$i]['name'] = lang('rr_contact') . ' (' . $contact->getType() . ')';
            $provider[$i]['value'] = $contact->getFullName() . " &lt;" . $contact->getEmail() . "&gt;";
            $i++;
        }
        return $provider;
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

    function displayInviteFederation(models\Queue $queue)
    {

        $this->ci->load->library('table');
        if ($queue->getRecipientType() == 'federation') {
            $federation = $this->tmp_federations->getOneFederationById($queue->getRecipient());
        }
        if (empty($federation)) {
            \log_message('error',__METHOD__.' Federation ('.$queue->getRecipient().') does not exist anymore');
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
        foreach($validators as $v)
        {
            $g = $v->getEnabled();
            if($g)
            {
                $fedValidator = $v;
                break;
            }
        }
        if($fedValidator)
        {
            $nname = $fedValidator->getName();
        }
        else
        {
            $nname = '';
        }
        $cell = array(lang('rr_federation'), $federation->getName() . ' '.$nname);
        $this->ci->table->add_row($cell);
        $data = $queue->getData();
        $cell = array(lang('rr_provider'), $data['name']);
        $this->ci->table->add_row($cell);
        $cell = array(lang('request'), lang('acceptprovtofed'));
        $this->ci->table->add_row($cell);

        if(isset($data['message']))
        {
            $cell = array(lang('rr_message'), $data['message']);
            $this->ci->table->add_row($cell);
        }
        $cell = array('data' => $this->displayFormsButtons($queue->getId()), 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $result = '';
        $result .= $this->ci->table->generate();
        $result .= '';
        $this->ci->table->clear();
        return $result;
    }

}
