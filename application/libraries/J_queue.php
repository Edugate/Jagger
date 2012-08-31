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
 * J_queue Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class J_queue {

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
        $approve_form .= '<div class="buttons" style="float:right"><button type="submit" name="mysubmit" value="Accept request!" class="btn positive"><span class="save">' . lang("rr_submitapprove") . '</span></button></div>';
        $approve_form .= form_close();
        /* add reject form */
        $reject_hidden_attributes = array('qaction' => 'reject', 'qid' => $qid);
        $reject_attrid = array('id' => 'rejectqueue');
        $reject_form = form_open('reports/awaiting/reject', $reject_attrid, $reject_hidden_attributes);
        $reject_form .= '<div class="buttons prepend-12" style="float: left"><button type="submit" name="mysubmit" value="Reject request!" class="btn negative"><span class="cancel">' . lang("rr_submitreject") . '</span></button></div>';
        $reject_form .= form_close();


        $result = '<div class="span-24" style="display:inline"><span style="float:left">' . $reject_form . '</span><span>' . $approve_form . '</span></div>';
        return $result;
    }

    function displayRegisterFederation(models\Queue $q)
    {
        $objData = new models\Federation;
        
        $objData->importFromArray($q->getData());
       


        $fedrows = array();
        $fedrows[] = array('header' => 'Request');
        $fedrows[] = array('name' => 'Type', 'value' => 'Register new federation');

        $creator = $q->getCreator();
        if ($creator)
        {
            $fedrows[] = array('name' => 'Requestor', 'value' => $creator->getUsername());
            $objData->setOwner($creator->getUsername());
        }
        else
        {
            $fedrows[] = array('name' => 'Requestor', 'value' => 'unknown');
        }

        $fedrows[] = array('name' => 'Registration date', 'value' => $q->getCreatedAt());
        $fedrows[] = array('header' => 'Basic Information');
        $fedrows[] = array('name' => 'Federation name', 'value' => $objData->getName());
        $fedrows[] = array('name' => 'Federation Urn', 'value' => $objData->getUrn());
        $fedrows[] = array('name' => 'Desrciption', 'value' => $objData->getDescription());
        $fedrows[] = array('name' => 'Terms Of Use', 'value' => $objData->getTou());
        
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
        $provider[$i++]['header'] = 'Basic Information';
        $provider[$i]['name'] = 'HomeOrg Name';
        $provider[$i++]['value'] = $objData->getName();

        $provider[$i]['name'] = 'entityID';
        $provider[$i++]['value'] = $objData->getEntityId();

        $provider[$i]['name'] = 'Home URL';
        $provider[$i++]['value'] = $objData->getHomeUrl();

        $provider[$i]['name'] = 'Helpdesk URL';
        $provider[$i++]['value'] = $objData->getHelpdeskUrl();

        foreach ($objData->getFederations() as $fed)
        {
            $provider[$i]['name'] = 'Federation';
            $provider[$i]['value'] = $fed->getName();
            $i++;
        }
        $provider[$i++]['header'] = 'Service Locations';
        foreach ($objData->getServiceLocations() as $service)
        {
            $provider[$i]['name'] = $service->getType();
            $provider[$i]['value'] = "" . $service->getUrl() . "<br /><small>" . $service->getBindingName() . "</small><br />";
            $i++;
        }

        $provider[$i++]['header'] = 'Certificates';
        foreach ($objData->getCertificates() as $cert)
        {
            $provider[$i]['name'] = "Certificate (" . $cert->getCertUse() . ")";
            //$provider[$i]['value'] = "<code>" . PEMtoHTML($cert->getCertData()) . "</code>";
                  $certdatacell = $cert->getCertdata();
                  $g = explode("\n",$cert->getCertdata());
                  $c = count($g);
                  if($c < 2)
                  {
                      $pem = chunk_split($cert->getCertdata(), 64, "<br />");
                      $certdatacell = $pem;
                  }



            $provider[$i]['value'] = "<span class=\"span-10\"><code>" . $certdatacell . "</code></span>";
            $i++;
        }

        $provider[$i++]['header'] = 'Contacts';
        foreach ($objData->getContacts() as $contact)
        {
            $provider[$i]['name'] = "Contact (" . $contact->getType() . ")";
            $provider[$i]['value'] = $contact->getFullName() . " &lt;" . $contact->getEmail() . "&gt;";
            $i++;
        }
        return $provider;
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
        $this->ci->table->set_caption('Request wating for approval');


        $text = '<span style="white-space: normal">Admin of federation: ' . $queue->getName() . ' invited your Provider: (' . $provider->getEntityId() . ')';
        $text .= "</span>";
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => 'Details', 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('Requester', $queue->getCreator()->getUsername() . ' (' . $queue->getCreator()->getFullname() . ') : email: ' . $queue->getCreator()->getEmail());
        $this->ci->table->add_row($cell);
        $cell = array('Federation', $queue->getName());
        $this->ci->table->add_row($cell);
        $cell = array('Provider', $provider->getName());
        $this->ci->table->add_row($cell);
        $cell = array('Request', 'Join Federation');
        $this->ci->table->add_row($cell);
        $cell = array('data'=>$this->displayFormsButtons($queue->getId()), 'colspan'=>2);
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
        if ($queue->getRecipientType() == 'federation')
        {
            $federation = $this->tmp_federations->getOneFederationById($queue->getRecipient());
        }
        if (empty($federation))
        {
            return false;
        }
        $tmpl = array('table_open' => '<table id="details" class="zebra">');
        $this->ci->table->set_template($tmpl);
        $this->ci->table->set_caption('Request wating for approval');


        $text = '<span style="white-space: normal">Admin of provider: ' . $queue->getName() . ' asked your Federation: (' . $federation->getName() . ')';
        $text .= "</span>";
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => 'Details', 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('Requester', $queue->getCreator()->getUsername() . ' (' . $queue->getCreator()->getFullname() . ') : email: ' . $queue->getCreator()->getEmail());
        $this->ci->table->add_row($cell);
        $cell = array('Federation', $federation->getName());
        $this->ci->table->add_row($cell);
        $data = $queue->getData();
        $cell = array('Provider', $data['name']);
        $this->ci->table->add_row($cell);
        $cell = array('Request', 'Accept provider as member of this federation');
        $this->ci->table->add_row($cell);
        $cell = array('data'=>$this->displayFormsButtons($queue->getId()), 'colspan'=>2);
        $this->ci->table->add_row($cell);
        $result = '';
        $result .= $this->ci->table->generate();
        $result .= '';
        $this->ci->table->clear();
        return $result;
    }

}
