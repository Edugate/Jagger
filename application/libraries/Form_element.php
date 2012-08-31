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
 * Form_element Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Form_element {

    protected $ci;
    protected $em;

    function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('form');
        log_message('debug', $this->ci->mid . 'lib/Form_element initialized');
    }

    /**
     * by default we just get all federations
     * @todo add more if conditions are set in array
     */
    public function getFederation($conditions = null) {
        $result = array();
        $feds = new models\Federations;
        $fedCollection = $feds->getFederations();
        if (!empty($fedCollection)) {
            $result[''] = "Please select";
            foreach ($fedCollection as $key) {
                $value = "";
                $is_activ = $key->getActive();
                if (!($is_activ)) {
                    $value .="inactive";
                }

                if (!empty($value)) {
                    $value = "(" . $value . ")";
                }
                $result[$key->getName()] = $key->getName() . " " . $value;
            }
        } else {
            $result[''] = "no federation found";
        }
        return $result;
    }

    /**
     * make dropdown list of type of entities
     */
    public function buildTypeOfEntities() {
        $types = array('' => 'Please select', 'idp' => 'Identity Providers', 'sp' => 'Service Providers', 'all' => 'All Entities');
        return $types;
    }

    public function generateFederationsElement($federations) {
        $result = "";
        $list = array();
        foreach ($federations as $f) {
            $list[$f->getId()] = $f->getName();
        }
        $result .= form_dropdown('fedid', $list);
        return $result;
    }

    private function generateServiceLocationsSpForm(models\Provider $provider, $action = null) {
        log_message('debug', $this->ci->mid . 'Form_element::generateServiceLocationsSpForm method started');
        $locations = array();
        foreach ($provider->getServiceLocations()->getValues() as $srv) {
            $locations[$srv->getType()][] = array(
                'id' => $srv->getId(),
                'type' => $srv->getType(),
                'binding' => $srv->getBindingName(),
                'url' => $srv->getUrl(),
                'index_number' => $srv->getOrder(),
                'is_default' => $srv->getDefault()
            );
        }
        /**
         * ad one field for new ACS service
         */
        $locations['AssertionConsumerService'][] = array(
            'id' => 'n',
            'type' => 'AssertionConsumerService',
            'binding' => 'none',
            'url' => '',
            'index_number' => '',
            'is_default' => null,);

        if (array_key_exists('AssertionConsumerService', $locations)) {
            log_message('debug', $this->ci->mid . "found ACS for sp: " . $provider->getEntityId());
            $i = 0;
            $s_input = "";
            $s_input .=form_fieldset('AssertionConsumerService');


            foreach ($locations['AssertionConsumerService'] as $acs) {
                $name = 'srv_' . $acs['id'];
                $srvid = $acs['id'];

                $select_label = form_label(lang('rr_bindingname'), 'acs_bind[' . $srvid . ']');

                $select_binding = form_dropdown('acs_bind[' . $srvid . ']', $this->ci->config->item('acs_binding'), $acs['binding']);

                $s_row = "" . $select_label . $select_binding . "<br />";

                $url_data = array(
                    'name' => 'acs_url[' . $srvid . ']',
                    'id' => 'acs_url[' . $srvid . ']',
                    'value' => set_value('acs_url', $acs['url']),
                    'class'=>'acsurl',
                );
                $url_label = form_label('URL', 'acs_url[' . $srvid . ']');

                $url_input = form_input($url_data);

                $s_row .="" . $url_label . $url_input ;

                $order_data = array(
                    'name' => 'acs_index[' . $srvid . ']',
                    'id' => 'acs_index[' . $srvid . ']',
                    'size' => 2,
                    'maxlength' => 2,
                    'class' => 'acsindex',
                    'value' => set_value('acs_index', $acs['index_number']),
                );
                //$index_label = form_label('index', 'acs_index');

                $index_input = form_input($order_data);
                $indexrow = "index " . $index_input;

                $is_default_data = array(
                    'name' => 'acs_default',
                    'id' => 'acs_default',
                    'value' => $acs['id'],
                    'checked' => set_value('acs_default', $acs['is_default'])
                );

                $is_default_label = form_label('Is default?', $name . '_default');
                $is_default_checkbox = form_radio($is_default_data);
                $isdefaulrow = " is default? " . $is_default_checkbox;
                $s_row .= "<span style=\"white-space: nowrap;\">" . $indexrow . $isdefaulrow . "</span><br />";
                if ($srvid == 'n') {
                    $s_input .= "<li>" . form_fieldset(lang('rr_addnewacs')) . $s_row . form_fieldset_close() . " </li>";
                } else {
                    $s_input .= "<li> " . $s_row . " </li>";
                }
            }
            $s_input .= form_fieldset_close();
        }

        $srvform = form_fieldset('Service Locations');
        $srvform = "<fieldset><legend class=\"accordionButton\">".lang('rr_servicelocations')."</legend>";
        $srvform .="<ol class=\"accordionContent\">";
        $srvform .= $s_input;

        $srvform .="</ol>";
        $srvform .=form_fieldset_close();
        return $srvform;
    }

    private function generateServiceLocationsIdpForm(models\Provider $provider, $action = null) {
        $ssotmpl = $this->ci->config->item('ssohandler_saml2');
        $ssotmpl = array_merge($ssotmpl, $this->ci->config->item('ssohandler_saml1'));
        $locations = array();
        $locations['SingleSignOnService'] = array();
        $slocations = $provider->getServiceLocations();
        $i = $provider->getServiceLocations()->getValues();
        if (!empty($slocations)) {
            /**
             * mapping collection into array
             */
            foreach ($slocations->getValues() as $s) {
                $s_id = $s->getId();
                $s_type = $s->getType();
                $s_bindingname = $s->getBindingName();
                $s_url = $s->getUrl();
                $s_order = $s->getOrder();
                $s_default = $s->getDefault();


                $locations[$s_type][$s_bindingname] = array(
                    'url' => $s_url,
                    'default' => $s_default,
                    'order' => $s_order,
                    'id' => $s_id);
            }
        }
        /**
         * generate inputs and fill with values
         */
        $s_input = "";
        $s_input .=form_fieldset('SingleSignOn');
        //    $s_input .='<fieldset><legen class="accordionButton">SingleSignOnService</legend>';

        $i = 0;
        foreach ($ssotmpl as $m) {

            /**
             * if locations is set
             */
            if (array_key_exists($m, $locations['SingleSignOnService'])) {
                $name = 'srvsso_' . $locations['SingleSignOnService'][$m]['id'] . '_url';
                $url = $locations['SingleSignOnService'][$m]['url'];
                $labelname = $m;
                $s_input .="<li>";
                $s_input .= form_label($labelname, $name) . "\n";
                $s_input .= form_input(array(
                    'name' => $name,
                    'id' => $name,
                    'value' => set_value($name, $url)));
                $s_input .="</li>\n";
            } else {
                $i++;
                $name = 'srvsso_' . $i . 'n_url';
                $hiddenname = 'srvsso_' . $i . 'n_type';
                $labelname = $m;
                $s_input .="<li>";
                $s_input .= form_label($labelname, $name) . "\n";
                $s_input .= "<div style=\"display:none\">";
                $s_input .= form_input(array(
                    'name' => $hiddenname,
                    'type' => 'hidden',
                    'value' => $m));
                $s_input .= "</div>";
                $s_input .= form_input(array(
                    'name' => $name,
                    'id' => $name,
                    'value' => set_value($name)));
                $s_input .="</li>\n";
            }
        }
        $s_input .= form_input(array(
            'name' => 'nosrvs',
            'type' => 'hidden',
            'value' => $i
                ));

        $s_input .= form_fieldset_close();



        $srvform = form_fieldset('Service Locations');
        $srvform = '<fieldset><legend class="accordionButton">'.lang('rr_servicelocations').'</legend>';
        $srvform .='<ol class="accordionContent">';
        $srvform .= $s_input;
        $srvform .='</ol>';
        $srvform .=form_fieldset_close();
        return $srvform;
    }

    private function generateServiceLocationsForm(models\Provider $provider, $action = null) {
        $type = $provider->getType();
        $s = null;
        if ($type == 'IDP') {
            $s = $this->generateServiceLocationsIdpForm($provider);
        } elseif ($type == 'SP') {
            $s = $this->generateServiceLocationsSpForm($provider);
        }

        $t = $s;
        return $t;
    }

    private function generateContactsForm(models\Provider $provider, $action = null, $template = null) {
        //$cntform = form_fieldset('Contacts');
        $cntform = '<fieldset><legend class="accordionButton">'.lang('rr_contacts').'</legend>';
        $cntform .='<ol class="accordionContent">';
        $formtypes = array(
            'administrative' => 'Administrative',
            'technical' => 'Technical',
            'support' => 'Support',
            'billing' => 'Billing',
            'other' => 'Other'
        );
        $no_contacts = 0;

        $cntcollection = $provider->getContacts();
        $no_contacts = $cntcollection->count();
        if (!empty($cntcollection)) {
            foreach ($cntcollection->getValues() as $cnt) {

                $cntform .= form_fieldset('Contacts');
                //  $cntform .= "<ol>";
                $cntform .= "<li>\n";
                $cntform .= form_label(lang('rr_contacttype'), 'contact_' . $cnt->getId() . '_type');
                $cntform .= form_dropdown('contact_' . $cnt->getId() . '_type', $formtypes, set_value('contact_' . $cnt->getId() . '_type', $cnt->getType()));
                $cntform .= "</li>\n";
                $cntform .= "<li>\n";
                $cntform .=form_label(lang('rr_contactfirstname'), 'contact_' . $cnt->getId() . '_fname');
                $cntform .= form_input(array('name' => 'contact_' . $cnt->getId() . '_fname', 'id' => 'contact_' . $cnt->getId() . '_fname',
                    'value' => set_value('contact_' . $cnt->getId() . '_fname', htmlentities($cnt->getGivenname()))));
                $cntform .= "</li>\n";
                $cntform .= "<li>\n";
                $cntform .=form_label(lang('rr_contactlastname'), 'contact_' . $cnt->getId() . '_sname');
                $sur = htmlspecialchars_decode($cnt->getSurname());
                $cntform .= form_input(array('name' => 'contact_' . $cnt->getId() . '_sname', 'id' => 'contact_' . $cnt->getId() . '_sname',
                    'value' => set_value('contact_' . $cnt->getId() . '_sname', $sur)));
                $cntform .= "</li>";
                $cntform .= "<li>";
                $cntform .=form_label(lang('rr_contactemail'), 'contact_' . $cnt->getId() . '_email');
                $cntform .= form_input(array('name' => 'contact_' . $cnt->getId() . '_email', 'id' => 'contact_' . $cnt->getId() . '_email',
                    'value' => set_value('contact_' . $cnt->getId() . '_email', $cnt->getEmail())));
                $cntform .= "</li>";
                //$cntform .= "</ol>";
                $cntform .= form_fieldset_close();
            }
            $no_contacts++;
            
            $cntform .='<fieldset class="newcontact"><legend>'.lang('rr_newcontact').'</legend>';
            $cntform .= "<li>";
            $cntform .=form_label('Contact Type', 'contact_0n_type');
            $cntform .= form_dropdown('contact_0n_type', $formtypes, set_value('contact_0n_type'));
            $cntform .= "<div style=\"display:none\">";
            $cntform .= form_input(array('name' => 'no_contacts', 'type' => 'hidden', 'value' => $no_contacts));
            $cntform .= "</div>";
            $cntform .= "</li>";
            $cntform .= "<li>";
            $cntform .=form_label('Contact first name', 'contact_0n_fname');
            $cntform .= form_input(array('name' => 'contact_0n_fname', 'id' => 'contact_0n_fname', 'value' => set_value('contact_0n_fname')));
            $cntform .= "</li>";
            $cntform .= "<li>";
            $cntform .=form_label('Contact last name', 'contact_0n_sname');
            $cntform .= form_input(array('name' => 'contact_0n_sname', 'id' => 'contact_0n_sname', 'value' => set_value('contact_0n_sname')));
            $cntform .= "</li>";
            $cntform .= "<li>";
            $cntform .=form_label('Contact Email', 'contact_0n_email');
            $cntform .= form_input(array('name' => 'contact_0n_email', 'id' => 'contact_0n_email', 'value' => set_value('contact_0n_email')));
            $cntform .= "</li>";
            $cntform .= form_fieldset_close();
        }
        $cntform .="</ol>";
        $cntform .=form_fieldset_close();
        return $cntform;
    }

    private function generateCertificatesForm(models\Provider $provider, $action = null) {
        $crtform = form_fieldset('Certificates');
        $crtform = '<fieldset><legend class="accordionButton">'.lang('rr_certificates').'</legend>';
        $crtform .='<ol class="accordionContent">';

        $crtcollection = $provider->getCertificates();
        $no_certs = count($crtcollection);
        if ($no_certs > 0) {
            foreach ($crtcollection->getValues() as $crt) {
                $i = $crt->getId();
                $crtform .="<li>\n";
                $crtform .=form_label(lang('rr_pleaseremove'), 'cert_' . $i . '_remove');
                $crtform .=form_dropdown('cert_' . $i . '_remove', array('none' => 'Keep it', 'yes' => 'Yes, remove it'));
                $crtform .="</li>\n";
                $crtform .="<li>\n";
                $crtform .=form_label(lang('rr_certificatetype'), 'cert_' . $i . '_type');
                $crtform .=form_dropdown('cert_' . $i . '_type', array('x509' => 'x509'), set_value('cert_' . $i . '_type', 'x509'));
                $crtform .="</li>\n";
                $crtform .="<li>\n";
                $crtform .=form_label(lang('rr_certificateuse'), 'cert_' . $i . '_use[]');
                $m = array('signing' => 'signing', 'encryption' => 'encryption');
                $mselected = $crt->getCertUse();
                if (empty($mselected)) {
                    $n = $m;
                } else {
                    $n = array($mselected = $mselected);
                }
                $crtform .=form_multiselect('cert_' . $i . '_use[]', $m, $n);
                $crtform .="</li>\n";
                $crtform .="<li>\n";
                $crtform .=form_label(lang('rr_keyname'), 'cert_' . $i . '_keyname');
                $crtform .=form_input(array('name' => 'cert_' . $i . '_keyname', 'id' => 'cert_' . $i . '_keyname', 'value' => set_value('cert_' . $i . '_keyname', $crt->getKeyName())));

                $crtform .="</li>\n";

                $crtform .="<li>";
                $crtform .=form_label(lang('rr_certificate').showHelp(lang('rhelp_cert')), 'cert_' . $i . '_data');
                $crtform .=form_textarea(array(
                    'name' => 'cert_' . $i . '_data', 'id' => 'cert_' . $i . '_data',
                    'value' => set_value('cert_' . $i . '_data', $crt->getPEM($crt->getCertData())), 'cols' => 65, 'rows' => 30
                        ));
                $crtform .="</li>\n";
            }
        }
        $crtform .= '<div class="ncert">';
        $crtform .='<li><b>'.lang('rr_newcertificate').'</b><small>('.lang('rr_optional').')</small></li>';
        $crtform .="<li>\n";
        $crtform .=form_label(lang('rr_certificatetype'), 'cert_0n_type');
        $crtform .=form_dropdown('cert_0n_type', array('x509' => 'x509'), set_value('cert_0n_type', 'x509'));
        $crtform .="</li>\n";
        $crtform .="<li>\n";
        $crtform .=form_label(lang('rr_certificateuse'), 'cert_0n_use[]');
        $m = array(
            'signing' => 'signing',
            'encryption' => 'encryption'
        );
        $crtform .=form_multiselect('cert_0n_use[]', $m, $m);
        $crtform .="</li>\n";
        $crtform .="<li>\n";
        $crtform .=form_label(lang('rr_keyname'), 'cert_0n_keyname');
        $crtform .=form_input(array('name' => 'cert_0n_keyname', 'id' => 'cert_0n_keyname', 'value' => set_value('cert_0n_keyname')));
        $crtform .="</li>\n";
        $crtform .="<li>\n";
        $crtform .=form_label(lang('rr_certificate').showHelp(lang('rhelp_cert')), 'cert_0n_data');
        $crtform .=form_textarea(array(
            'name' => 'cert_0n_data',
            'id' => 'cert_0n_data',
            'value' => set_value('cert_0n_data'),
            'cols' => 65,
                ));
        $crtform .="</li>\n";
        $crtform .="</div>";
        $crtform .="</ol>";
        $crtform .=form_fieldset_close();
        return $crtform;
    }

    /**
     * return form elements:
     * select box if you want to use static metadata
     * textarea for metadata
     */

    /**
     * @todo add to main function generating form
     */
    private function staticMetadata(models\Provider $provider) {
        $is_static = $provider->getStatic();
        $static_mid = $provider->getStaticMetadata();
        $static_metadata = '';
        if ($static_mid) {
            $static_metadata = $static_mid->getMetadataToDecoded();
        }

        $tform = '<fieldset><legend class="accordionButton">'.lang('rr_staticmetadata').'</legend>';
        $tform .='<ol class="accordionContent">';
        $tform .="<li>";
        $tform .= form_label(lang('rr_usestaticmetadata'), 'usestatic');
        $tform .= form_checkbox(array(
            'name' => 'usestatic',
            'id' => 'usestatic',
            'value' => 'accept',
            'checked' => set_value('usestatic', $is_static)
                ));
        $tform .="</li>";
        $tform .="<li>";
        $tform .= form_label(lang('rr_staticmetadataxml'), 'staticmetadatabody');
        $tform .= form_textarea(array(
            'name' => 'staticmetadatabody',
            'id' => 'staticmetadatabody',
            'value' => set_value('staticmetadatabody', $static_metadata)
                ));
        $tform .="</li>\n";
        $tform .="</ol>\n";
        $tform .= form_fieldset_close();
        return $tform;
    }

    private function supportedProtocols(models\Provider $provider, $action = null) {
        $tform = '';
        $t_protocols = $provider->getProtocol();
        $selected_options = array();
        foreach ($t_protocols as $p) {
            $selected_options[$p] = $p;
        }
        // $tform .= form_fieldset('Protocols');
        $tform .= '<fieldset><legend class="accordionButton">'.lang('rr_protocols').'</legend>';
        $tform .= "<ol class=\"accordionContent\">";
        $tform .="<li>";
        $tform .= form_label(lang('rr_supportedprotocols').showHelp(lang('rhelp_supportedprotocols')), 'protocols[]');

        $options = array(
            'urn:oasis:names:tc:SAML:2.0:protocol' => 'urn:oasis:names:tc:SAML:2.0:protocol',
            'urn:oasis:names:tc:SAML:1.1:protocol' => 'urn:oasis:names:tc:SAML:1.1:protocol'
        );
        $tform .= form_multiselect('protocols[]', $options, $selected_options);

        $tform .="</li>";
        $tform .= $this->supportedNameIds($provider);
        $tform .="</ol>";
        $tform .= form_fieldset_close();

        return $tform;
    }

    /**
     * @todo add javascript ordering
     */
    private function supportedNameIds(models\Provider $provider) {
        $tform = '';
        $supported_nameids = array();
        $tmpl_nameids = $this->ci->config->item('nameids');


        $s_nameids = $provider->getNameId();
        foreach ($s_nameids->getValues() as $n) {
            $supported_nameids[$n] = $n;
            $chb[] = array(
                'name' => 'nameids[]',
                'id' => 'nameids[]',
                'value' => $n,
                'checked' => TRUE);
        }
        foreach ($tmpl_nameids as $t) {
            if (!array_key_exists($t, $supported_nameids)) {
                $chb[] = array(
                    'name' => 'nameids[]',
                    'id' => 'nameids[]',
                    'value' => $t,
                    'checked' => FALSE);
            }
        }

        $tform .='<li>';
        
        $tform .= form_label(lang('rr_supportednameids'), 'nameids[]');
        $tform .="<div id=\"sortable\">";
        foreach ($chb as $n) {
            $tform .= "<span>" . form_checkbox($n) . $n['value'] . "</span>";
        }
        
        $tform .= "</div>";
        $tform .="</li>";



        return $tform;
    }

    private function generateSpForm(models\Provider $provider, $action = null, $template = null) {
        log_message('debug', $this->ci->mid . 'Form_element::generateSpForm method started');
        $tmp = "<div id=\"mojtest\">";
        $tmp .="<div id=\"accordion\">";
        $tmp .='<fieldset><legend class="accordionButton">' . lang('rr_generalinformation') . '</legend>';

        $tmp .= "<ol class=\"accordionContent\">";
        $tmp .= '<li>';
        $tmp .= form_label(lang('rr_entityid').showHelp(lang('rhelp_entityid')).'<br /><small><span class="notice">'.lang('rr_noticechangearp').'</span></small>', 'entityid');
        $f_en = array('id' => 'entityid', 'name' => 'entityid', 'required' => 'required', 'value' => $provider->getEntityid());
        $tmp .= form_input($f_en);

        $tmp .= '</li>';
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_resource').showHelp(lang('rhelp_resourcename')), 'homeorgname');
        $tmp .= form_input('homeorgname', set_value('homeorgname', $provider->getName()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_displayname'), 'displayname');
        $tmp .= form_input('displayname', set_value('displayname', $provider->getDisplayName()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_resourceurl'), 'homeurl');
        $tmp .= form_input('homeurl', set_value('homeurl', $provider->getHomeUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_helpdeskurl').showHelp(lang('rhelp_helpdeskurl')), 'helpdeskeurl');
        $tmp .= form_input('helpdeskurl', set_value('helpdeskurl', $provider->getHelpdeskUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_privacystatement'), 'privacyurl');
        $tmp .= form_input('privacyurl', set_value('privacyurl', $provider->getPrivacyUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_validfrom'), 'validfrom');
        $ptm = $provider->getValidFrom();
        if (!empty($ptm)) {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom', $provider->getValidFrom()->format('Y-m-d'))
                    ));
        } else {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom')
                    ));
        }

        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_validto'), 'validto');
        $vtm = $provider->getValidTo();
        if (!empty($vtm)) {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto', $provider->getValidTo()->format('Y-m-d'))
                    ));
        } else {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto')
                    ));
        }
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Description', 'description');
        $tmp .= form_textarea('description', set_value('description', $provider->getDescription()));
        $tmp .= "</li>";
        $tmp .= "</ol>";

        $tmp .= form_fieldset_close();
        $tmp .="</div>";
        $tmp .= $this->staticMetadata($provider);
        $tmp .= $this->supportedProtocols($provider);
        $tmp .= $this->generateCertificatesForm($provider);
        /**
         * @todo add  service locations for sp
         */
        $tmp .= $this->generateServiceLocationsForm($provider);
        $tmp .= $this->generateContactsForm($provider);
        $tmp .= "<fieldset>
			<legend class=\"accordionButton\">
			<a href=\"" . base_url() . "manage/attribute_requirement/sp/" . $provider->getId() . "\">".lang('rr_requiredattributes')."</a>
			</legend>
			</fieldset>";
        $tmp .= "<fieldset>
			<legend class=\"accordionButton\">
			<a href=\"" . base_url() . "manage/logos/provider/sp/" . $provider->getId() . "\">".lang('rr_logo')."</a>
			</legend>
			</fieldset>";
        $tmp .= "<fieldset>
			<legend class=\"accordionButton\">
			<a href=\"" . base_url() . "geolocation/show/".$provider->getId()."/sp\">" .lang('rr_geolocation')."</a>
			</legend>
			</fieldset>";
        $tmp .="</div>";
        return $tmp;
    }

    private function generateIdpForm(models\Provider $provider, $action = null, $template = null) {
        $tmp = "";
        $tmp .='<div id="accordion">';

        $tmp .='<fieldset><legend class="accordionButton"  >'.lang('rr_generalinformation').'</legend>';


        $tmp .= '<ol class="accordionContent">';
        $tmp .= '<li>';
        $tmp .= form_label(lang('rr_entityid').showHelp(lang('rhelp_entityid')).'<br /><small><span class="notice">'.lang('rr_noticechangearp').'</span></small>', 'entityid');
        $f_en = array('id' => 'entityid', 'name' => 'entityid', 'required' => 'required', 'value' => $provider->getEntityid());
        $tmp .= form_input($f_en);

        $tmp .= '</li>';
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_homeorganisationname'), 'homeorgname');
        $in = array('id' => 'homeorgname', 'name' => 'homeorgname', 'required' => 'required', 'value' => set_value('homeorgname', $provider->getName()));
        $tmp .= form_input($in);
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_displayname'), 'displayname');
        $in = array('id' => 'displayname', 'name' => 'displayname', 'required' => 'required', 'value' => set_value('displayname', $provider->getDisplayName()));
        $tmp .= form_input($in);
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_homeorganisationurl'), 'homeurl');
        $tmp .= form_input('homeurl', set_value('homeurl', $provider->getHomeUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_helpdeskurl').showHelp(lang('rhelp_helpdeskurl')), 'helpdeskeurl');
        $in = array(
            'name' => 'helpdeskurl',
            'id' => 'helpdeskurl',
            'required' => 'required',
            'value' => set_value('helpdeskurl', $provider->getHelpdeskUrl()),
        );
        $tmp .= form_input($in);
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_privacystatement'), 'privacyurl');
        $tmp .= form_input('privacyurl', set_value('privacyurl', $provider->getPrivacyUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_validfrom'), 'validfrom');
        $ptm = $provider->getValidFrom();
        if (!empty($ptm)) {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom', $provider->getValidFrom()->format('Y-m-d'))
                    ));
        } else {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom')
                    ));
        }

        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_validto'), 'validto');
        $vtm = $provider->getValidTo();
        if (!empty($vtm)) {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto', $provider->getValidTo()->format('Y-m-d'))
                    ));
        } else {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto')
                    ));
        }
        $tmp .= "</li>";
        $tmp .="<li>";
        $tmp .= form_label(lang('rr_scope'), 'scope');
        $tmp .= form_input('scope', set_value('scope', $provider->getScope()));
        $tmp .="</li>";
        $tmp .= "<li>";
        $tmp .= form_label(lang('rr_description'), 'description');
        $tmp .= form_textarea('description', set_value('description', $provider->getDescription()));
        $tmp .= "</li>";
        $tmp .= "</ol>";

        $tmp .= form_fieldset_close();
        $tmp .="</div>";

        $tmp .= $this->staticMetadata($provider);
        $tmp .= $this->supportedProtocols($provider);

        /**
         * certificates section
         */
        $tmp .= $this->generateCertificatesForm($provider);
        /**
         * servicelocations section
         */
        $tmp .= $this->generateServiceLocationsForm($provider);
        /**
         * contacts section
         */
        $tmp .= $this->generateContactsForm($provider);
        $tmp .= "<fieldset>
			<legend class=\"accordionButton\">
			<a href=\"" . base_url() . "manage/attribute_policy/globals/" . $provider->getId() . "\">".lang('rr_attributes')."</a>
			</legend>
			</fieldset>";
        $tmp .= "<fieldset>
			<legend class=\"accordionButton\">
			<a href=\"" . base_url() . "manage/logos/provider/idp/" . $provider->getId() . "\">".lang('rr_logo')."</a>
			</legend>
			</fieldset>";
        $tmp .= "<fieldset>
			<legend class=\"accordionButton\">
			<a href=\"" . base_url() . "geolocation/show/".$provider->getId()."/idp\">".lang('rr_geolocation')."</a>
			</legend>
			</fieldset>";
        return $tmp;
    }

    public function generateEntityForm(models\Provider $provider, $action = null) {
        log_message('debug', $this->ci->mid . 'Form_element::generateEntityForm method started');
        $tform = null;
        $p_type = $provider->getType();
        if ($p_type == 'IDP') {
            $tform = $this->generateIdpForm($provider, $action);
        } elseif ($p_type == 'SP') {

            $tform = $this->generateSpForm($provider, $action);
        } else {
            /**
             * @todo display form if type is BOTH
             */
            // $tform = $this->generateBothForm($provider, $action);
        }

        return $tform;
    }

    /**
     * function return html of form elements from attributes like:
     * homeorgname,displayname,homeurl,helpdeskurl,validfrom,validto
     */
    public function generateIdpBasicForm($provider) {
        if (!$provider instanceof models\Provider) {
            return false;
        }
        $tmp = form_fieldset('Baisc Information');
        $tmp .= "<ol>";
        $tmp .= "<li>";
        $tmp .= form_label('HomeOrg Name', 'homeorgname');
        $in = array('id' => 'homeorgname', 'name' => 'homeorgname', 'required' => 'required', 'value' => set_value('homeorgname', $provider->getName()));
        //$tmp .= form_input('homeorgname', set_value('homeorgname', $provider->getName()));
        $tmp .= form_input($in);
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Display Name', 'displayname');
        $tmp .= form_input('displayname', set_value('displayname', $provider->getDisplayName()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Home Organization URL', 'homeurl');
        $tmp .= form_input('homeurl', set_value('homeurl', $provider->getHomeUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Helpdesk URL', 'helpdeskeurl');
        $tmp .= form_input('helpdeskurl', set_value('helpdeskurl', $provider->getHelpdeskUrl()));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Valid from', 'validfrom');
        $tmp .= form_input(array('name' => 'validfrom', 'id' => 'validfrom', 'value' => set_value('validfrom', $provider->getValidFrom()->format('Y-m-d'))));
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Valid to', 'validto');
        $vtm = $provider->getValidTo();
        if (!empty($vtm)) {
            $tmp .= form_input(array('name' => 'validto', 'id' => 'validto', 'value' => set_value('validto', $provider->getValidTo()->format('Y-m-d'))));
        } else {
            $tmp .= form_input(array('name' => 'validto', 'id' => 'validto', 'value' => set_value('validto')));
        }
        $tmp .= "</li>";
        $tmp .= "<li>";
        $tmp .= form_label('Description', 'description');
        $tmp .= form_textarea('description', set_value('description', $provider->getDescription()));
        $tmp .= "</li>";
        $tmp .= "</ol>";
        $tmp .= form_fieldset_close();

        return $tmp;
    }

    public function supportedAttributesForm(models\Provider $idp) {
        $tmp = new models\Attributes();
        $attributes_defs = $tmp->getAttributes();
        if (empty($attributes_defs)) {
            log_message('error', 'There is no attributes definitions');
            return null;
        }
        $tmp1 = new models\AttributeReleasePolicies();
        $supported = $tmp1->getSupportedAttributes($idp);
        $data = array();
        foreach ($attributes_defs as $a) {
            $data[$a->getId()] = array('s' => 0, 'name' => $a->getName(), 'attrid' => $a->getId());
        }
        if (!empty($supported)) {

            foreach ($supported as $s) {
                $data[$s->getAttribute()->getId()]['s'] = 1;
            }
        }

        $result_top = "";
        $result_bottom = "";
        $result = "<table id=\"details\">\n";
        $result .="<caption>Supported Attributes</caption>";
        $result .="<thead><tr><th>Attribute name</th><th>supported</th></tr></thead>\n";
      //  $result .="<tbody>";
        foreach ($data as $d => $value) {
            if ($value['s'] == 1) {
                $f = form_checkbox('attr[' . $value['attrid'] . ']', '1', true);
                $result_top .= "<tr><td>" . $value['name'] . "</td><td>" . $f . "</td></tr>\n";
            } else {
                $f = form_checkbox('attr[' . $value['attrid'] . ']', '1', false);
                $result_bottom .="<tr><td>" . $value['name'] . "</td><td>" . $f . "</td></tr>\n";
            }
        }
        if(!empty($result_top))
        {
            $result_top = "<tbody class=\"attr_supported\">".$result_top ."</tbody>";     
        }
        if(!empty($result_bottom))
        {
            $result_bottom = "<tbody>". $result_bottom . "</tbody>";
        }
        $result .= $result_top . $result_bottom ."</table>";
        return $result;
    }

    public function generateEditPolicyForm(models\AttributeReleasePolicy $arp, $action = null, $submit_type = null) {
        $result = "";
        $attributes = array('id' => 'formver2');
        $type = $arp->getType();
        $hidden = array('idpid' => $arp->getProvider()->getId(), 'attribute' => $arp->getAttribute()->getId(), 'requester' => $arp->getRequester());
        if ($type == 'fed') {
            $hidden['fedid'] = $arp->getRequester();
        }
        if (empty($action)) {
            $action = base_url() . 'manage/attribute_policy/submit';
        }
        $result .= form_open($action, $attributes, $hidden);
        $result .= $this->generateEditPolicyFormElement($arp);
        //$result .= form_fieldset('');
        $result .='<div class="buttons">';
        if (!empty($submit_type) && $submit_type == 'create') {
            $cancel_value = 'cancel';
            $save_value = 'create';
        } else {
            $save_value = 'modify';
            $cancel_value = 'delete';
        }
        $result .= '<button name="submit" type="submit" value="'.$cancel_value.'" class="btn negative"><span class="cancel">'.$cancel_value.'</span></button>';
        $result .= '<button name="submit" type="submit" value="'.$save_value.'" class="btn positive"><span class="save">'.$save_value.'</span></button>';
        $result .='</div>';

        //$result .= form_fieldset_close();

        $result .=form_close();
        return $result;
    }

    public function generateEditPolicyFormElement(models\AttributeReleasePolicy $arp) {
        $result = "";
        $result .= form_fieldset('Attribute name: ' . $arp->getAttribute()->getFullName() . ' (' . $arp->getAttribute()->getName() . ')');
        $result .= "<ol>";
        $result .= "<li>\n";
        $result .= form_label('Set policy', 'policy');
        $result .= form_dropdown('policy', $this->ci->config->item('policy_dropdown'), $arp->getPolicy());
        $result .= "</li>\n";
        $result .= "</ol>\n";
        $result .= form_fieldset_close();
        return $result;
    }

}

