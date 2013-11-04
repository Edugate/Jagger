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
        $approve_form .= '<button type="submit" name="mysubmit" value="Accept request!" class="savebutton saveicon">' . lang("rr_submitapprove") . '</button>';
        $approve_form .= form_close();
        /* add reject form */
        $reject_hidden_attributes = array('qaction' => 'reject', 'qid' => $qid);
        $reject_attrid = array('id' => 'rejectqueue');
        $reject_form = form_open('reports/awaiting/reject', $reject_attrid, $reject_hidden_attributes);
        $reject_form .= '<button type="submit" name="mysubmit" value="Reject request!" class="resetbutton reseticon">' . lang("rr_submitreject") . '</button>';
        $reject_form .= form_close();


        $result = '<div class="buttons" >' . $reject_form .'&nbsp;'  . $approve_form . '</div>';
        return $result;
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
            $objData->setOwner($creator->getUsername());
        }
        else
        {
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

        $provider[$i]['name'] = lang('rr_homeurl');
        $provider[$i++]['value'] = $objData->getHomeUrl();

        $provider[$i]['name'] = lang('rr_helpdeskurl');
        $provider[$i++]['value'] = $objData->getHelpdeskUrl();

        foreach ($objData->getFederations() as $fed)
        {
            $provider[$i]['name'] = lang('rr_federation');
            $provider[$i]['value'] = $fed->getName();
            $i++;
        }
        $provider[$i++]['header'] = lang('rr_servicelocations');
        foreach ($objData->getServiceLocations() as $service)
        {
            $provider[$i]['name'] = $service->getType();
            $provider[$i]['value'] = "" . $service->getUrl() . "<br /><small>" . $service->getBindingName() . "</small><br />";
            $i++;
        }

        $provider[$i++]['header'] = lang('rr_certificates');
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

        $provider[$i++]['header'] = lang('rr_contacts');
        foreach ($objData->getContacts() as $contact)
        {
            $provider[$i]['name'] = lang('rr_contact').' (' . $contact->getType() . ')';
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
        $this->ci->table->set_caption(lang('rr_requestawaiting'));


        $text = '<span style="white-space: normal">'.lang('adminoffed').': ' . $queue->getName() . ' '.lang('invyourprov').': (' . $provider->getEntityId() . ')';
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
        $this->ci->table->set_caption(lang('rr_requestawaiting'));


        $text = '<span style="white-space: normal">'.lang('adminofprov').': ' . $queue->getName() . ' '.lang('askedyourfed').': (' . $federation->getName() . ')';
        $text .= "</span>";
        $cell = array('data' => $text, 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array('data' => lang('rr_details'), 'class' => 'highlight', 'colspan' => 2);
        $this->ci->table->add_row($cell);
        $cell = array(lang('requestor'), $queue->getCreator()->getUsername() . ' (' . $queue->getCreator()->getFullname() . ') : email: ' . $queue->getCreator()->getEmail());
        $this->ci->table->add_row($cell);
        $cell = array(lang('rr_federation'), $federation->getName());
        $this->ci->table->add_row($cell);
        $data = $queue->getData();
        $cell = array(lang('rr_provider'), $data['name']);
        $this->ci->table->add_row($cell);
        $cell = array(lang('request'), lang('acceptprovtofed'));
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
