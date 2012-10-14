<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
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
 * Provider_detail Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Provider_detail extends MY_Controller {

    private $current_idp;
    private $current_idp_name;
    private $logo_url;

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->helper(array('url', 'cert', 'url_encoder'));
        $this->load->library('table');
        $this->load->library('geshilib');
        $this->load->library('zacl');
        $this->logo_basepath = $this->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;
    }

    function idp($id = NULL)
    {
        $params = array(
            'enable_classes' => true,
        );
        $data['alert_message'] = null;
        $this->session->set_userdata(array('currentMenu' => 'idp'));
        if (empty($id))
        {
            log_message('debug', current_url() . ': idp not set - check if default idp in session exists');
            /**
             * @todo finish action (display error) if idp not found	
             */
            if (empty($this->current_idp))
            {
                log_message('debug', current_url() . ': default idp is not set in session');
                $this->session->set_flashdata('target', $this->current_site);
                redirect('manage/settings/idp', 'refresh');
            }
            else
            {
                $id = $this->current_idp;
            }
        }
        if (!ctype_digit($id))
        {
            show_error(lang('rerror_wrongidpid'), 404);
        }
        $g = new models\Providers();
        $idp = $g->getOneIdpById($id);


        if (empty($idp))
        {
            /**
             * @todo finish action (display error) if idp not found	
             */
            log_message('debug', "Idp " . $idp . " not found");
            show_error(lang('rerror_idpnotfound'), 404);
            return;
        }
        $resource = $idp->getId();
        $data['idpid'] = $resource;
        $group = 'idp';
        $action = 'read';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_read_access)
        {
            //show_error('No access',401);
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noidpaccess');
            $this->load->view('page', $data);
            return;
        }

        $is_active = $idp->getActive();

        if (empty($is_active))
        {
            $activeString = "<span class=\"notice\"><small>" . lang('rr_idpnotenabled') . "</small></span>";
        }
        else
        {
            $activeString = lang('rr_idpactive');
        }
        $i = 1;

        $resource = $idp->getId();
        $group = 'idp';
        $action = 'write';
        $has_write_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_write_access)
        {
            $edit_link = "<span class=\"notice\">" . lang('rr_nopermission') . "</span>";
            $edit_basic = "";
            $edit_federation = "";
            $edit_technical = "";
            $edit_protocols = "";
            $edit_services = "";
            $edit_certificates = "";
            $edit_contacts = "";
            $edit_attributes = "";
            $edit_policy = "";
            $e_local = false;
            $image_link = "";
        }
        elseif (!$idp->getLocal())
        {
            $edit_link = "<span class=\"notice\">" . lang('rr_externalentity') . "</span>";
            $edit_basic = "";
            $edit_federation = "";
            $edit_technical = "";
            $edit_protocols = "";
            $edit_services = "";
            $edit_certificates = "";
            $edit_contacts = "";
            $edit_attributes = "";
            $edit_policy = "";
            $e_local = false;
            $image_link = "";
        }
        else
        {
            $image_link = "<img src=\"" . base_url() . "images/icons/pencil-field.png\"/>";
            $edit_link = "<span><a href=\"" . base_url() . "manage/idp_edit/show/" . $idp->getId() . "\" class=\"edit\" title=\"edit\" >" . $image_link . "</a></span>";
            $edit_basic = "";
            $edit_federation = "";
            $edit_technical = "";
            $edit_protocols = "";
            $edit_services = "";
            $edit_certificates = "";
            $edit_contacts = "";
            $edit_attributes = "<span><a href=\"" . base_url() . "manage/supported_attributes/idp/" . $idp->getId() . " \" class=\"edit\">" . $image_link . "</a></span>";
            $edit_policy = "<span><a href=\"" . base_url() . "manage/attribute_policy/globals/" . $idp->getId() . " \" class=\"edit\">" . $image_link . "</a></span>";
            $e_local = true;
        }

        $data['edit_link'] = $edit_link;

        $data['idp_details'][$i++]['header'] = "<a name=\"basic\"></a><b>" . lang('rr_basicinformation') . $activeString . " </b> " . $edit_basic . "";
        $data['idp_details'][$i]['name'] = 'Last modification';
        $data['idp_details'][$i++]['value'] = "<b>" . $idp->getLastModified()->format('Y-m-d H:i:s') . "</b>";

        $data['idp_details'][$i]['name'] = lang('rr_homeorganisationname');
        $data['idp_details'][$i++]['value'] = "<b>" . $idp->getName() . "</b>";
        $data['idp_details'][$i]['name'] = lang('rr_descriptivename');
        $data['idp_details'][$i++]['value'] = "<b>" . $idp->getDisplayName() . "</b>";
        $data['idp_details'][$i]['name'] = lang('rr_description');
        $data['idp_details'][$i++]['value'] = "" . htmlentities($idp->getDescription()) . "";
        $data['idp_details'][$i]['name'] = lang('rr_homeorganisationurl') . '<small> ' . lang('rr_notincludedmetadata') . '</small>';
        $homeUrl = $idp->getHomeUrl();
        if (!empty($homeUrl))
        {
            $data['idp_details'][$i++]['value'] = anchor($idp->getHomeURL());
        }
        else
        {
            $data['idp_details'][$i++]['value'] = lang("rr_notset");
        }
        $data['idp_details'][$i]['name'] = lang('rr_helpdeskurl') . '<small> ' . lang('rr_includedmetadata') . '</small>';
        $data['idp_details'][$i++]['value'] = anchor($idp->getHelpdeskURL());

        $data['idp_details'][$i]['name'] = lang('rr_privacystatement');
        $privurl = $idp->getPrivacyUrl();
        if (!empty($privurl))
        {
            $data['idp_details'][$i++]['value'] = anchor($idp->getPrivacyUrl());
        }
        else
        {
            $data['idp_details'][$i++]['value'] = lang("rr_notset");
        }

        if (!$idp->getIsValidFromTo())
        {
            $data['idp_details'][$i++]['2cols'] = "<div class=\"alert\">" . lang('rr_fromtomatch') . "</div>";
        }

        $data['idp_details'][$i]['name'] = lang('rr_validfrom');
        if ($idp->getValidFrom())
        {
            $data['idp_details'][$i++]['value'] = date_format($idp->getValidFrom(), 'Y M d');
        }
        else
        {
            $data['idp_details'][$i++]['value'] = lang('rr_unlimited');
        }
        $data['idp_details'][$i]['name'] = lang('rr_validto');
        if ($idp->getValidTo())
        {
            $data['idp_details'][$i++]['value'] = date_format($idp->getValidTo(), 'Y M d');
        }
        else
        {
            $data['idp_details'][$i++]['value'] = lang('rr_unlimited');
        }
        $data['idp_details'][$i]['name'] = lang('rr_managedlocallyexternal');
        if (!($idp->getLocal()))
        {

            $data['idp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_external') . '</span>';
        }
        else
        {

            $data['idp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_managedlocally') . '</span>';
        }
        $data['idp_details'][$i]['name'] = lang('rr_metadata');
        if (!($idp->getIsStaticMetadata()))
        {

            $data['idp_details'][$i++]['value'] = lang('rr_generatedbelow');
        }
        else
        {
            $tmp_s = $idp->getStaticMetadata();
            if (!empty($tmp_s))
            {
                $tmp = $tmp_s->getMetadataToDecoded();
            }
            else
            {
                $tmp = null;
            }
            if (!empty($tmp))
            {
                $data['idp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_staticmetadataset') . '</span><br />' . lang('rr_notgeneratedbased');
            }
            else
            {
                $data['idp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_staticxmlenabled') . '</span>' . lang('rr_metadatageneratedbased');
                $data['alert_message'][] = lang("rr_enabledempty");
            }
        }



        $data['idp_details'][$i++]['header'] = '<a name="federation"></a>' . lang('rr_federation') . $edit_federation;
        $data['idp_details'][$i]['name'] = lang('rr_memberof');
        $federationsString = "";
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $feds = $idp->getFederations();

        if (!empty($feds))
        {
            $federationsString = "<ul>";
            foreach ($feds->getValues() as $f)
            {
                $fedlink = base_url() . "federations/manage/show/" . base64url_encode($f->getName()) . "/metadata.xml";
                $metalink = base_url() . "metadata/federation/" . base64url_encode($f->getName()) . "/metadata.xml";
                $federationsString .= "<li>" . anchor($fedlink, $f->getName()) . " &nbsp&nbsp&nbsp; <span class=\"accordionButton\">metadata URL:</span><span class=\"accordionContent\"><br />" . $metalink . "</span> " . anchor_popup($metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>') . "</li>";
            }
            $federationsString .="</ul>";
            $manage_membership = '';
            if ($feds->count() > 0 && $has_write_access)
            {
                $manage_membership .= '<b>' . lang('rr_federationleave') . '</b> ' . anchor(base_url() . 'manage/leavefed/leavefederation/' . $idp->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
            }
            if ($has_write_access && ($feds->count() < count($all_federations)))
            {
                $manage_membership .= '<b>' . lang('rr_federationjoin') . '</b> ' . anchor(base_url() . 'manage/joinfed/joinfederation/' . $idp->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
            }
        }
        $data['idp_details'][$i++]['value'] = "<b>" . $federationsString . "</b>" . $manage_membership;


        $data['idp_details'][$i++]['header'] = '<a name="technical"></a>' . lang('rr_technicalinformation') . $edit_technical;
        $data['idp_details'][$i]['name'] = lang('rr_entityid');
        $data['idp_details'][$i++]['value'] = $idp->getEntityId();
        $idp_metalink = base_url() . "metadata/service/" . base64url_encode($idp->getEntityId()) . "/metadata.xml";
        $data['idp_details'][$i]['name'] = '<a name="metadata"></a>' . lang('rr_entitymetadataurl');
        $data['idp_details'][$i++]['value'] = "<span class=\"accordionButton\"><b>" . lang('rr_metadataurl') . "</b></span><span class=\"accordionContent\"><br />" . $idp_metalink . "&nbsp;&nbsp;</span>&nbsp; " . anchor_popup($idp_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>');

        $idp_circle_metalink = base_url() . "metadata/circle/" . base64url_encode($idp->getEntityId()) . "/metadata.xml";
        $data['idp_details'][$i]['name'] = lang('rr_circleoftrust');
        $data['idp_details'][$i++]['value'] = "<span class=\"accordionButton\"><b>" . lang('rr_metadataurl') . "</b></span><span class=\"accordionContent\"><br />" . $idp_circle_metalink . "&nbsp;&nbsp;</span> &nbsp;" . anchor_popup($idp_circle_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>');

        $idp_circle_metalink_signed = base_url() . "signedmetadata/provider/" . base64url_encode($idp->getEntityId()) . "/metadata.xml";
        $data['idp_details'][$i]['name'] = lang('rr_circleoftrust') . " (signed)";
        $data['idp_details'][$i++]['value'] = "<span class=\"accordionButton\"><b>" . lang('rr_metadataurl') . "</b></span><span class=\"accordionContent\"><br />" . $idp_circle_metalink_signed . "&nbsp;&nbsp;</span> &nbsp;" . anchor_popup($idp_circle_metalink_signed, '<img src="' . base_url() . 'images/icons/arrow.png"/>');

        $data['idp_details'][$i]['name'] = lang('rr_domainscope');
        $scopes = $idp->getScopeToArray();
        $scopeString = "<ul>";
        foreach ($scopes as $key => $value)
        {
            $scopeString .= "<li>" . $value . "</li>";
        }
        $scopeString .= "</ul>";
        $data['idp_details'][$i++]['value'] = $scopeString;

        $is_static = $idp->getStatic();
        $sm = $idp->getStaticMetadata();
        $static_metadata = null;
        if (!empty($sm))
        {
            $static_metadata = $idp->getStaticMetadata()->getMetadataToDecoded();
        }
        if ($is_static)
        {
            if (empty($static_metadata))
            {
                $data['idp_details'][$i]['name'] = lang('rrstaticmetadataactive');
                $data['idp_details'][$i++]['value'] = "<span class=\"error\">" . lang('rr_isempty') . "</span>";
            }
            else
            {
                $data['idp_details'][$i++]['header'] = lang('rr_staticmetadataactive');
                //  $data['idp_details'][$i++]['value'] = "";
                $data['idp_details'][$i++]['2cols'] = "<span class=\"accordionButton\"></span><code>" . $this->geshilib->highlight($static_metadata, 'xml', $params) . "</code>";
            }
        }
        else
        {
            if (empty($static_metadata))
            {
                $data['idp_details'][$i]['name'] = lang('rr_staticmetadatanotactive');
                $data['idp_details'][$i++]['value'] = "<span>" . lang('rr_isempty') . "</span>";
            }
            else
            {
                $data['idp_details'][$i++]['header'] = lang('rr_setnotactive');

                //$data['idp_details'][$i++]['2cols'] = "<pre>" . htmlentities($static_metadata) . "</pre>";

                $data['idp_details'][$i++]['2cols'] = "<span class=\"accordionButton\"></span><code class=\"accordionContent\">" . $this->geshilib->highlight($static_metadata, 'xml', $params) . "</code>";
            }
        }
        $data['idp_details'][$i++]['header'] = '<a name="arp"></a>' . lang('rr_arp');

        $encoded_entityid = base64url_encode($idp->getEntityId());
        $arp_url = base_url() . "arp/format2/" . $encoded_entityid . "/arp.xml";
        $data['idp_details'][$i]['name'] = lang('rr_individualarpurl');
        $data['idp_details'][$i++]['value'] = "<span class=\"accordionButton\">ARP URL</span><span class=\"accordionContent\"><br />" . $arp_url . "&nbsp;</span>&nbsp;" . anchor_popup($arp_url, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        //
        $tmp_logs = new models\Trackers;
        $arp_logs = $tmp_logs->getArpDownloaded($idp);

        $logg_tmp = "<ul>";
        if (!empty($arp_logs))
        {
            foreach ($arp_logs as $l)
            {
                $logg_tmp .= "<li><b>" . $l->getCreated()->format('Y-m-d H:i:s') . "</b> - " . $l->getIp() . " <small><i>(" . $l->getAgent() . ")</i></small></li>";
            }
        }
        $logg_tmp .= "</ul>";
        $data['idp_details'][$i]['name'] = lang('rr_recentarpdownload');
        $data['idp_details'][$i++]['value'] = $logg_tmp;

        //

        $data['idp_details'][$i++]['header'] = lang('rr_supportedprotocols') . $edit_protocols;
        $data['idp_details'][$i]['name'] = lang('rr_supportedprotocols');
        $protocols = "";
        $no_protocols = count($idp->getProtocol()->getValues());
        foreach ($idp->getProtocol()->getValues() as $p)
        {
            $protocols .=$p . " ";
        }
        if (($no_protocols < 1) && !$idp->getStatic())
        {
            $data['alert_message'][] = "Supported protocols is not set";
        }
        $data['idp_details'][$i++]['value'] = trim($protocols);


        $data['idp_details'][$i]['name'] = lang('rr_supportednameids');
        $nameids = "<ul>";
        foreach ($idp->getNameId()->getValues() as $r)
        {
            $nameids .= "<li>" . $r . "</li>";
        }
        $nameids .="</ul>";
        $data['idp_details'][$i++]['value'] = trim($nameids);

        $serviceLocations = $idp->getServiceLocations()->getValues();
        $no_of_serviceLocations = count($serviceLocations);
        if ($no_of_serviceLocations < 1 && !$idp->getStatic())
        {
            $data['alert_message'][] = "ServiceLocations: SingleSignOnService is not set";
        }


        $data['idp_details'][$i++]['header'] = lang('rr_servicelocations') . $edit_services;


        $ssovalues = "";

        foreach ($serviceLocations as $s)
        {
            if ($s->getType() == 'SingleSignOnService')
            {
                $def = "";
                if ($s->getDefault())
                {
                    $def = "<i>(default)</i>";
                }
                $ssovalues .= '<b>' . $def . ' ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small><br />';
            }
        }
        $data['idp_details'][$i]['name'] = 'SingleSignOnService';
        $data['idp_details'][$i++]['value'] = $ssovalues;
        /**
         * @todo check other service protocols
         */
        $certs = $idp->getCertificates()->getValues();

        $cString = "";
        $data['idp_details'][$i++]['header'] = lang('rr_certificates') . $edit_certificates;
        foreach ($certs as $c)
        {
            $c_certKeyname = $c->getKeyname();
            $c_certData = $c->getCertData();
            if (!empty($c_certKeyname))
            {
                $cString .="<b>Keyname:</b> <span>" . $c_certKeyname . "</span><br />";
            }

            $data['idp_details'][$i]['name'] = '';
            if (!empty($c_certData))
            {
                $c_certtype = $c->getCertType();
                if ($c_certtype == 'X509Certificate')
                {
                    $c_fingerprint = $c->getFingerprint();
                    $c_certValid = validateX509($c_certData);
                    if (!$c_certValid)
                    {
                        $cString .="<span class=\"error\">" . lang('rr_certificatenotvalid') . "</span>";
                    }
                }
                if (!empty($c_fingerprint))
                {
                    $cString .="<b>Fingerprint:</b> <span>" . $c_fingerprint . "</span><br />";
                }
                $cString .= "<span class=\"accordionButton\"><b>Certificate body</b><br /></span><code class=\"accordionContent\">" . trim($c_certData) . "</code>";
                $val = $c->getTimeValid('days');
                if ($val > 30)
                {
                    $data['idp_details'][$i]['name'] = '';
                }
                elseif ($val < 1)
                {

                    $data['idp_details'][$i]['name'] = '<span class="notice">' . lang('rr_expired') . '</span>';
                    $data['alert_message'][] = lang("rr_certificateexpired");
                }
                else
                {
                    $data['idp_details'][$i]['name'] = '<span class="notice">' . $val . ' days to expire</span>';
                }
            }
            $data['idp_details'][$i++]['value'] = $cString;
        }


        $data['idp_details'][$i++]['header'] = lang("rr_contacts") . $edit_contacts;


        $contacts = $idp->getContacts()->getValues();
        foreach ($contacts as $c)
        {
            $data['idp_details'][$i]['name'] = $c->getType();
            $data['idp_details'][$i++]['value'] = $c->getFullName() . " " . safe_mailto($c->getEmail());
        }

        $data['idp_details'][$i++]['header'] = '<a name="attrs"></a>' . lang('rr_supportedattributes') . ' ' . $edit_attributes;
        // array of objects
        $tmpAttrs = new models\AttributeReleasePolicies;
        $supportedAttributes = $tmpAttrs->getSupportedAttributes($idp);
        foreach ($supportedAttributes as $s)
        {
            $data['idp_details'][$i]['name'] = $s->getAttribute()->getName();
            $data['idp_details'][$i++]['value'] = $s->getAttribute()->getDescription();
        }

        $data['idp_details'][$i++]['header'] = lang('rr_defaultspecificarp') . $edit_policy;
        $this->load->library('show_element');
        $disable_caption = true;
        $r = $this->show_element->generateTableDefaultArp($idp, $disable_caption);
        $data['idp_details'][$i++]['2cols'] = $r;
        // array of objects

        $data['idp_details'][$i++]['header'] = lang('rr_logs');

        $data['idp_details'][$i]['name'] = lang('rr_modifications');
        $data['idp_details'][$i++]['value'] = $this->show_element->generateModificationsList($idp, 3);

        $data['idp_details'][$i++]['header'] = lang('rr_homeorgadmin');
        $data['idp_details'][$i]['name'] = '';
        $data['idp_details'][$i++]['value'] = lang('rr_displayaccess') . anchor(base_url() . 'manage/access_manage/entity/' . $idp->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>');


        $data['idpname'] = $idp->getName();
        $this->title = lang("rr_informationdetail") . $data['idpname'];
        $data['content_view'] = 'providers/idp_detail_view';
        /** display logo */
        $extends = $idp->getExtendMetadata();
        if (count($extends) > 0)
        {
            $is_logo = false;
            foreach ($extends as $ex)
            {
                $el = $ex->getElement();
                if ($el == 'Logo')
                {
                    $data['provider_logo_url'] = $this->logo_url . $ex->getEvalue();
                }
            }
        }

        $this->load->view('page', $data);
    }

    function sp($id = null)
    {
        $params = array(
            'enable_classes' => false,
        );
        $this->session->set_userdata(array('currentMenu' => 'sp'));
        $this->title = lang("rr_serviceproviderdetails");
        if (empty($id))
        {
            /**
             * @todo finish action (display error) if idp not found	
             */
            if (empty($this->current_sp))
            {
                $this->session->set_flashdata('target', $this->current_site);
                redirect('manage/settings/sp', 'refresh');
            }
            else
            {
                $id = $this->current_sp;
            }
        }
        if (!ctype_digit($id))
        {
            show_error(lang('rerror_wrongspid'), 404);
        }

        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('SP', 'BOTH')));
        if (empty($sp))
        {
            log_message('error', $this->mid . 'Service Provider with id:' . $id . ' not found');
            show_error($this->mid . lang("rerror_spnotfound"), 404);
        }

        $resource = $sp->getId();
        $group = 'sp';
        $action = 'read';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        $has_write_access = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$has_read_access)
        {
            //show_error('No access',401);
            $data['content_view'] = 'nopermission';
            $data['error'] = lang("rr_nospaccess");
            $this->load->view('page', $data);
            return;
        }

        $is_active = $sp->getActive();
        if (empty($is_active))
        {
            $activeString = "<span class=\"notice\"><small>" . lang('rr_spdisabled') . "</small></span>";
        }
        else
        {
            $activeString = lang("rr_idpactive");
        }
        if (!$has_write_access)
        {
            $edit_link = "<span class=\"notice\">" . lang('rr_nopermission') . "</span>";
        }
        elseif (!$sp->getLocal())
        {
            $edit_link = "<span class=\"notice\">" . lang('rr_externalentity') . "</span>";
        }
        else
        {
            $image_link = "<img src=\"" . base_url('images/icons/pencil-field.png') . "\"/>";
            $edit_link = "<span><a href=\"" . base_url("manage/sp_edit/show/" . $sp->getId()) . "\" class=\"edit\" title=\"edit\" >" . $image_link . "</a></span>";
        }
        $data['edit_link'] = $edit_link;
        $i = 1;
        $data['sp_details'][$i++]['header'] = "<span id=\"basic\"></span>" . lang('rr_basicinformation') . " <b> " . $activeString . "</b>";
        $data['sp_details'][$i]['name'] = lang('rr_lastmodification');
        $data['sp_details'][$i++]['value'] = "<b>" . $sp->getLastModified()->format('Y-m-d H:i:s') . "</b>";
        $data['sp_details'][$i]['name'] = lang('rr_resource');
        $data['sp_details'][$i++]['value'] = "<b>" . $sp->getName() . "</b>";
        $data['sp_details'][$i]['name'] = lang('rr_descriptivename');
        $data['sp_details'][$i++]['value'] = "<b>" . htmlentities($sp->getDisplayName()) . "</b>";
        $data['sp_details'][$i]['name'] = lang('rr_description');
        $data['sp_details'][$i++]['value'] = "" . htmlentities($sp->getDescription()) . "";
        $data['sp_details'][$i]['name'] = lang('rr_homeorganisationurl') . '<small>(not included in metadata)</small>';
        $homeUrl = $sp->getHomeUrl();
        if (!empty($homeUrl))
        {
            $data['sp_details'][$i++]['value'] = anchor($homeUrl);
        }
        else
        {
            $data['sp_details'][$i++]['value'] = lang("rr_notset");
        }
        $helpurl = $sp->getHelpdeskURL();

        $data['sp_details'][$i]['name'] = lang('rr_helpdeskurl') . '<small>(included in metadata)</small>';
        if (!empty($helpurl))
        {
            $data['sp_details'][$i++]['value'] = anchor($helpurl);
        }
        else
        {
            $data['sp_details'][$i++]['value'] = "<span class=\"alert\">" . lang('rr_notset') . "</span>";
        }
        $data['sp_details'][$i]['name'] = lang('rr_privacystatement');
        $privurl = $sp->getPrivacyUrl();
        if (!empty($privurl))
        {
            $data['sp_details'][$i++]['value'] = anchor($privurl);
        }
        else
        {
            $data['sp_details'][$i++]['value'] = lang("rr_notset");
        }
        if (!$sp->getIsValidFromTo())
        {
            $data['sp_details'][$i++]['2cols'] = "<div class=\"alert\">Valid From/To doesn't match current date. Your entity won't appear in metadata</div>";
        }

        $data['sp_details'][$i]['name'] = lang('rr_validfrom');
        if ($sp->getValidFrom())
        {
            $data['sp_details'][$i++]['value'] = $sp->getValidFrom()->format('Y M d');
        }
        else
        {
            $data['sp_details'][$i++]['value'] = lang('rr_unlimited');
        }
        $data['sp_details'][$i]['name'] = lang('rr_validto');
        if ($sp->getValidTo())
        {
            $data['sp_details'][$i++]['value'] = $sp->getValidTo()->format('Y M d');
        }
        else
        {
            $data['sp_details'][$i++]['value'] = lang('rr_unlimited');
        }
        $data['sp_details'][$i]['name'] = lang('rr_managedlocallyexternal');
        if (!($sp->getLocal()))
        {

            $data['sp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_external') . '</span>';
        }
        else
        {

            $data['sp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_managedlocally') . '</span>';
        }
        $data['sp_details'][$i]['name'] = lang('rr_metadata');
        if (!($sp->getIsStaticMetadata()))
        {

            $data['sp_details'][$i++]['value'] = lang('rr_generatedbelow');
        }
        else
        {
            $data['sp_details'][$i++]['value'] = '<span class="notice">' . lang('rr_setasdefault') . '</span>';
        }

        $data['sp_details'][$i++]['header'] = '<span id="federation"></span>' . lang('rr_federation');
        $data['sp_details'][$i]['name'] = 'Member of';
        $federationsString = "";
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $feds = $sp->getFederations();
        if (!empty($feds))
        {
            $federationsString = "<ul>";
            foreach ($feds->getValues() as $f)
            {
                $fedlink = base_url("federations/manage/show/" . base64url_encode($f->getName()));
                $metalink = base_url("metadata/federation/" . base64url_encode($f->getName()) . "/metadata.xml");
                $federationsString .= "<li>" . anchor($fedlink, $f->getName()) . " <span class=\"accordionButton\">metadata URL:</span><span class=\"accordionContent\"><br />" . $metalink . "&nbsp;</span> &nbsp;&nbsp;" . anchor_popup($metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>') . "</li>";
            }
            $federationsString .="</ul>";
            $manage_membership = '';
            if ($feds->count() > 0 && $has_write_access)
            {
                $manage_membership .= '<b>Manage membership (leaving)</b> ' . anchor(base_url() . 'manage/leavefed/leavefederation/' . $sp->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
            }
            if ($has_write_access && ($feds->count() < count($all_federations)))
            {
                $manage_membership .= '<b>Manage membership (joining)</b> ' . anchor(base_url() . 'manage/joinfed/joinfederation/' . $sp->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
            }
        }
        $data['sp_details'][$i++]['value'] = "<b>" . $federationsString . "</b>" . $manage_membership;

        $data['sp_details'][$i++]['header'] = '<span id="technical"></span>' . lang('rr_technicalinformation');
        $data['sp_details'][$i]['name'] = lang('rr_entityid');
        $data['sp_details'][$i++]['value'] = $sp->getEntityId();
        $sp_metalink = base_url("metadata/service/" . base64url_encode($sp->getEntityId()) . "/metadata.xml");
        $data['sp_details'][$i]['name'] = '<a name="metadata"></a>' . lang('rr_servicemetadataurl');
        $data['sp_details'][$i++]['value'] = "<span class=\"accordionButton\">" . lang('rr_metadataurl') . "</span><span class=\"accordionContent\"><br />" . $sp_metalink . "&nbsp;</span>&nbsp; " . anchor_popup($sp_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>');

        $sp_circle_metalink = base_url() . "metadata/circle/" . base64url_encode($sp->getEntityId()) . "/metadata.xml";
        $data['sp_details'][$i]['name'] = lang('rr_circleoftrust');
        $data['sp_details'][$i++]['value'] = "<span class=\"accordionButton\">" . lang('rr_metadataurl') . "</span><span class=\"accordionContent\"><br />" . $sp_circle_metalink . "&nbsp;</span>&nbsp; " . anchor_popup($sp_circle_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        $sp_circle_metalink_signed = base_url() . "signedmetadata/provider/" . base64url_encode($sp->getEntityId()) . "/metadata.xml";
        $data['sp_details'][$i]['name'] = lang('rr_circleoftrust') . "(signed)";
        $data['sp_details'][$i++]['value'] = "<span class=\"accordionButton\">" . lang('rr_metadataurl') . "</span><span class=\"accordionContent\"><br />" . $sp_circle_metalink_signed . "&nbsp;</span>&nbsp; " . anchor_popup($sp_circle_metalink_signed, '<img src="' . base_url() . 'images/icons/arrow.png"/>');




        $is_static = $sp->getStatic();

        if ($is_static)
        {
            $tmp_st = $sp->getStaticMetadata();
            if (!empty($tmp_st))
            {
                $static_metadata = $tmp_st->getMetadata();
            }
            else
            {
                $static_metadata = null;
            }
            if (empty($static_metadata))
            {
                $data['sp_details'][$i]['name'] = lang('rr_staticmetadataactive');
                $data['sp_details'][$i++]['value'] = "<span class=\"alert\">" . lang('rr_isempty') . "</span>";
            }
            else
            {
                $data['sp_details'][$i++]['header'] = lang('rr_staticmetadataactive');

                $data['sp_details'][$i++]['2cols'] = "<span class=\"accordionButton\"></span><code class=\"accordionContent\">" . $this->geshilib->highlight($static_metadata, 'xml', $params) . "</code>";
            }
        }
        $data['sp_details'][$i++]['header'] = lang('rr_supportedprotocols');
        $data['sp_details'][$i]['name'] = lang('rr_supportedprotocols');
        $protocols = "";
        foreach ($sp->getProtocol()->getValues() as $p)
        {
            $protocols .=$p . " ";
        }
        $data['sp_details'][$i++]['value'] = trim($protocols);
        $data['sp_details'][$i]['name'] = lang('rr_supportednameids');
        $nameids = "<ul>";
        foreach ($sp->getNameId()->getValues() as $r)
        {
            $nameids .= "<li>" . $r . "</li>";
        }
        $nameids .="</ul>";
        $data['sp_details'][$i++]['value'] = trim($nameids);

        $serviceLocations = $sp->getServiceLocations()->getValues();

        $data['sp_details'][$i++]['header'] = lang('rr_servicelocations');

        $acs = "";
        foreach ($serviceLocations as $s)
        {
            if ($s->getType() == 'AssertionConsumerService')
            {
                $def = "";
                if ($s->getDefault())
                {
                    $def = "<i>(default)</i>";
                }
                $acs .= '<b>' . $def . ' ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small><br />';
            }
        }
        $data['sp_details'][$i]['name'] = lang('rr_acs');
        $data['sp_details'][$i++]['value'] = $acs;
        /**
         * @todo check other service protocols
         */
        $certs = $sp->getCertificates()->getValues();


        $data['sp_details'][$i++]['header'] = lang('rr_certificates');

        foreach ($certs as $c)
        {
            $cString = "";
            $c_certUse = $c->getCertUse();
            $c_certKeyname = $c->getKeyname();
            $c_certData = $c->getCertData();
            $cString .= "<b>Usage: </b>" . $c_certUse . ": <br />";
            if (!empty($c_certKeyname))
            {
                $cString .= "<b>KeyName:</b> " . $c_certKeyname . "<br />";
            }
            if (!empty($c_certData))
            {
                $c_certtype = $c->getCertType();
                if ($c_certtype == 'X509Certificate')
                {
                    $c_fingerprint = $c->getFingerprint();
                    $c_certValid = validateX509($c_certData);
                    if (!$c_certValid)
                    {
                        $cString .="<span class=\"error\">" . lang('rr_certificatenotvalid') . "</span>";
                    }
                }
                if (!empty($c_fingerprint))
                {
                    $cString .="<b>Fingerprint:</b> <span>" . $c_fingerprint . "</span><br />";
                }

                $cString .= "<span class=\"accordionButton\"></span><code class=\"accordionContent\">" . trim($c_certData) . "</code>";
            }
            $val = $c->getTimeValid('days');
            if ($val > 30)
            {
                $data['sp_details'][$i]['name'] = '';
            }
            elseif ($val < 1)
            {

                $data['sp_details'][$i]['name'] = '<span class="notice">' . lang('rr_expired') . '</span>';
            }
            else
            {
                $data['sp_details'][$i]['name'] = '<span class="notice">' . $val . ' days to expire</span>';
            }
            $data['sp_details'][$i++]['value'] = $cString;
        }
        $data['sp_details'][$i++]['header'] = lang("rr_contacts");


        $contacts = $sp->getContacts()->getValues();
        foreach ($contacts as $c)
        {
            $data['sp_details'][$i]['name'] = $c->getType();
            $data['sp_details'][$i++]['value'] = $c->getFullName() . " " . safe_mailto($c->getEmail());
        }

        /**
         * required attributes
         */
        if ($has_write_access)
        {
            $image_link = "<img src=\"" . base_url('images/icons/pencil-field.png') . "\"/>";
            $edit_req_attrs_link = "<span><a href=\"" . base_url("manage/attribute_requirement/sp/" . $sp->getId()) . "\" class=\"edit\" title=\"edit\" >" . $image_link . "</a></span>";
        }
        else
        {
            $edit_req_attrs_link = '';
        }
        $data['sp_details'][$i++]['header'] = "<span id=\"reqattrs\"></span>" . lang("rr_requiredattributes") . $edit_req_attrs_link;
        $requiredAttributes = $sp->getAttributesRequirement();

        if ($requiredAttributes->count() == 0)
        {
            $data['sp_details'][$i]['name'] = '';
            $data['sp_details'][$i++]['value'] = '<span class="notice">No requirement specified. It may inherit requirement from federation</span>';
        }
        else
        {
            foreach ($requiredAttributes->getValues() as $key)
            {
                $data['sp_details'][$i]['name'] = $key->getAttribute()->getName();
                $data['sp_details'][$i++]['value'] = "<b>" . $key->getStatus() . "</b>: <i>(" . $key->getReason() . ")</i>";
            }
        }


///////////
        $data['sp_details'][$i++]['header'] = lang('rr_logs');

        $data['sp_details'][$i++]['header'] = lang('rr_admins');
        $data['sp_details'][$i]['name'] = '';
        $data['sp_details'][$i++]['value'] = lang('rr_displayaccess') . anchor(base_url() . 'manage/access_manage/entity/' . $sp->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        $data['spname'] = $sp->getName();
        if (empty($data['spname']))
        {
            $data['spname'] = 'unknown';
        }
        $this->title = lang("rr_informationdetail") . $data['spname'];
        $data['content_view'] = 'providers/sp_detail_view';
        /** display logo */
        $extends = $sp->getExtendMetadata();
        if (count($extends) > 0)
        {
            $is_logo = false;
            foreach ($extends as $ex)
            {
                $el = $ex->getElement();
                if ($el == 'Logo')
                {
                    //$img_logo = "<img src=\"".$this->logo_url . $ex->getEvalue()."\" style=\"float: left; max-width: 150px;\"/>";
                    $data['provider_logo_url'] = $this->logo_url . $ex->getEvalue();
                    //$is_logo = true;
                }
            }
        }
        $data['spid'] = $sp->getId();
        $this->load->view('page', $data);
    }

}

