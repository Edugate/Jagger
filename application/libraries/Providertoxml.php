<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Providertoxml
 *
 * @author janul
 */
class Providertoxml
{

    private $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    private $em;
    private $isGenIdFnExist;
    private $useGlobalRegistrar;
    private $globalRegistrar;
    private $globalRegpolicy;
    private $logoPrefixUrl;
    private $srvMap;

    function __construct()
    {
        $this->ci = &get_instance();

        $this->srvMap = array(
            'SingleSignOnService' => array(
                'name' => 'SingleSignOnService',
                'isorder' => 0,
                'ns' => 'md',
            ),
            'IDPSingleLogoutService' => array(
                'name' => 'SingleLogoutService',
                'isorder' => 0,
                'ns' => 'md',
            ),
            'IDPArtifactResolutionService' => array(
                'name' => 'ArtifactResolutionService',
                'isorder' => 1,
                'ns' => 'md',
            ),
            'IDPAttributeService' => array(
                'name' => 'AttributeService',
                'isorder' => 0,
                'ns' => 'md',
            ),
            'RequestInitiator' => array(
                'name' => 'RequestInitiator',
                'isorder' => 0,
                'ns' => 'init',
            ),
            'DiscoveryResponse' => array(
                'name' => 'DiscoveryResponse',
                'isorder' => 1,
                'ns' => 'idpdisc',
            ),
            'AssertionConsumerService' => array(
                'name' => 'AssertionConsumerService',
                'isorder' => 1,
                'ns' => 'md',
            ),
            'SPSingleLogoutService' => array(
                'name' => 'SingleLogoutService',
                'isorder' => 0,
                'ns' => 'md',
            ),
            'SPArtifactResolutionService' => array(
                'name' => 'ArtifactResolutionService',
                'isorder' => 1,
                'ns' => 'md',
            ),
        );
        if (function_exists('customGenerateEntityDescriptorID')) {
            $this->isGenIdFnExist = true;
        } else {
            $this->isGenIdFnExist = false;
        }
        $registrationAutority = $this->ci->config->item('registrationAutority');
        $load_registrationAutority = $this->ci->config->item('load_registrationAutority');
        if (!empty($registrationAutority) && !empty($load_registrationAutority)) {
            $this->useGlobalRegistrar = true;
            $this->globalRegistrar = $this->ci->config->item('registrationAutority');
            $this->globalRegpolicy = $this->ci->config->item('registrationPolicy');
        } else {
            $this->useGlobalRegistrar = false;
        }
        $logoPrefixURI = $this->ci->config->item('rr_logouriprefix');
        $logoBaseUrl = $this->ci->config->item('rr_logobaseurl');
        if (empty($logoBaseUrl)) {
            $logoBaseUrl = base_url();
        }
        $this->logoPrefixUrl = $logoBaseUrl . $logoPrefixURI;
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
        $this->em->getRepository("models\Coc")->findAll();
    }

    public function providerConvertToXML(\models\Provider $ent)
    {

    }

    private function createEntityExtensions(\XMLWriter $xml, \models\Provider $ent)
    {

        $registrar = $ent->getRegistrationAuthority();
        if (empty($registrar) && $ent->getLocal() && $this->useGlobalRegistrar) {
            $registrar = $this->globalRegistrar;
        }
        /**
         * @var $cocs models\Coc[]
         */
        $doFilter = array(TRUE);
        $cocs = $ent->getCoc()->filter(
            function (models\Coc $entry) use ($doFilter) {
                return in_array($entry->getAvailable(), $doFilter);
            }
        );

        $cocsByGroups = array('entcat' => array(), 'regpol' => array());
        foreach ($cocs as $v) {
            $cocsSubtype = $v->getSubtype();
            if (!empty($cocsSubtype)) {
                $cocsByGroups['' . $v->getType() . ''][$cocsSubtype][] = $v;
            } else {
                $cocsByGroups['' . $v->getType() . ''][] = $v;
            }
        }

        /**
         * @var $extendMeta models\ExtendMetadata[]
         */
        $doFilter1 = array('DigestMethod', 'SigningMethod');
        $extendMeta = $ent->getExtendMetadata()->filter(
            function (models\ExtendMetadata $entry) use ($doFilter1) {
                return in_array($entry->getElement(), $doFilter1);
            }
        );

        $algs = array();
        foreach($extendMeta as $e)
        {
            $type = $e->getType();
            if($type==='ent')
            {
                $algs[] = $e;
            }
        }

        $algsCount = count($algs);

        if (!empty($registrar) || count($cocsByGroups['entcat']) || $algsCount > 0) {
            $xml->startElementNs('md', 'Extensions', null);
            if($algsCount>0)
            {
                $xml->writeAttribute('xmlns:alg','urn:oasis:names:tc:SAML:metadata:algsupport');
            }

            if (!empty($registrar)) {
                $xml->startElementNs('mdrpi', 'RegistrationInfo', null);
                $xml->writeAttribute('registrationAuthority', $registrar);
                /**
                 * @var $registerDate DateTime
                 */
                $registerDate = $ent->getRegistrationDate();
                if (!empty($registerDate)) {
                    $xml->writeAttribute('registrationInstant', $registerDate->format('Y-m-d') . 'T' . $registerDate->format('H:i:s') . 'Z');
                    if (count($cocsByGroups['regpol']) > 0) {

                        $langsset = array();
                        foreach ($cocsByGroups['regpol'] as $v) {
                            $vlang = $v->getLang();
                            if (in_array($vlang, $langsset)) {
                                \log_message('error', __METHOD__ . ' multiple registration policies are set for lang:' . $vlang . ' for entityId: ' . $ent->getEntityId());
                                continue;
                            }
                            $langsset[] = $vlang;
                            $xml->startElementNs('mdrpi', 'RegistrationPolicy', null);
                            $xml->writeAttribute('xml:lang', $vlang);
                            $xml->text($v->getUrl());
                            $xml->endElement();
                        }
                    } elseif (!empty($this->globalRegpolicy)) {
                        $xml->startElementNs('mdrpi', 'RegistrationPolicy', null);
                        $xml->writeAttribute('xml:lang', 'en');
                        $xml->text($this->globalRegpolicy);
                        $xml->endElement();
                    }
                }
                $xml->endElement();
            }

            if (count($cocsByGroups['entcat']) > 0) {

                $xml->startElementNs('mdattr', 'EntityAttributes', null);
                foreach ($cocsByGroups['entcat'] as $k => $v) {

                    $xml->startElementNs('saml', 'Attribute', null);
                    $xml->writeAttribute('Name', $k);
                    $xml->writeAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                    foreach ($v as $k1 => $v1) {

                        $xml->startElementNs('saml', 'AttributeValue', null);
                        $xml->text($v1->getUrl());
                        $xml->endElement();
                    }
                    $xml->endElement();
                }
                $xml->endElement();
            }

            foreach($algs as $alg)
            {
                $algElement = $alg->getElement();
                $algAlgorithm = $alg->getEvalue();
                $xml->startElementNs('alg',$algElement,null);
                $xml->writeAttribute('Algorithm',$algAlgorithm);
                $algattrs = $alg->getAttributes();
	            if(is_array($algattrs)) {
		            foreach ($algattrs as $k => $a) {
			            $xml->writeAttribute($k, $a);
		            }
	            }
                $xml->endElement();
            }
            $xml->endElement(); //end extensions element
        }


        return $xml;
    }

    private function createContacts(\XMLWriter $xml, \models\Provider $ent)
    {
        $contacts = $ent->getContacts();
        foreach ($contacts as $c) {
            $givenName = $c->getGivenname();
            $surName = $c->getSurname();
            $email = $c->getEmail();
            $xml->startElementNs('md', 'ContactPerson', null);
            $xml->writeAttribute('contactType', $c->getType());
            if (!empty($givenName)) {
                $xml->startElementNs('md', 'GivenName', null);
                $xml->text($givenName);
                $xml->endElement();
            }
            if (!empty($surName)) {
                $xml->startElementNs('md', 'SurName', null);
                $xml->text($surName);
                $xml->endElement();
            }
            $xml->startElementNs('md', 'EmailAddress', null);
            $xml->text('mailto:' . $email);
            $xml->endElement();
            $xml->endElement();
        }
        return $xml;
    }

    private function createUIIInfo(\XMLWriter $xml, \models\Provider $ent, $role)
    {
        $toGenerate = FALSE;
        $extendMeta = $ent->getExtendMetadata();
        $extarray = array('DisplayName' => array(), 'Description' => array(), 'Logo' => array(), 'InformationURL' => array(), 'PrivacyStatementURL' => array());
        $extarrayKeys = array_keys($extarray);
        foreach ($extendMeta as $v) {
            if ( in_array($v->getElement(),$extarrayKeys) &&  (strcasecmp($v->getType(), $role) == 0) && ($v->getNamespace() === 'mdui')) {
                $extarray['' . $v->getElement() . ''][] = $v;
                $toGenerate = TRUE;
            }
        }
        if ($toGenerate === FALSE) {

            return $xml;
        }
        $xml->startElementNs('mdui', 'UIInfo', null);

        $enLang = array();
        foreach (array('DisplayName', 'Description', 'InformationURL', 'PrivacyStatementURL') as $mduiElement) {
            $enLang['' . $mduiElement . ''] = FALSE;


            foreach ($extarray['' . $mduiElement . ''] as $value) {
                $lang = $value->getAttributes();
                if (isset($lang['xml:lang'])) {
                    if (strcmp($lang['xml:lang'], 'en') == 0) {
                        $enLang['' . $mduiElement . ''] = TRUE;
                    }
                    $xml->startElementNs('mdui', '' . $mduiElement . '', null);
                    $xml->writeAttribute('xml:lang', $lang['xml:lang']);
                    $xml->text($value->getElementValue());
                    $xml->endElement();
                }
            }

            if (!$enLang['' . $mduiElement . '']) {
                if (strcmp('PrivacyStatementURL', $mduiElement) == 0) {
                    $t = $ent->getPrivacyUrl();
                }

                if (!empty($t)) {
                    $xml->startElementNs('mdui', '' . $mduiElement . '', null);
                    $xml->writeAttribute('xml:lang', 'en');
                    $xml->text($t);
                    $xml->endElement();
                }
            }
        }
        foreach ($extarray['Logo'] as $l) {
            $xml->startElementNs('mdui', 'Logo', null);
            $logoAttrs = array_filter($l->getAttributes());
            foreach ($logoAttrs as $lk => $lv) {
                $xml->writeAttribute($lk, $lv);
            }
            if (!filter_var($l->getElementValue(), FILTER_VALIDATE_URL)) {
                $xml->text($this->logoPrefixUrl . $l->getElementValue());
            } else {
                $xml->text($l->getElementValue());
            }
            $xml->endElement();
        }

        $xml->endElement();
        return $xml;
    }

    private function createDiscoHints(\XMLWriter $xml, \models\Provider $ent, $role)
    {

        // @todo filtering collection
        $extMetada = $ent->getExtendMetadata();
        $extarray = array();
        foreach ($extMetada as $v) {
            $extElement = $v->getElement();
            if ((($extElement === 'GeolocationHint') || ($extElement === 'IPHint') || ($extElement === 'DomainHint')) && (strcasecmp($v->getType(), $role) == 0) && ($v->getNamespace() === 'mdui')) {
                $extarray['' . $extElement . ''][] = $v;
            }
        }
        if (count($extarray) > 0) {
            $xml->startElementNs('mdui', 'DiscoHints', null);

            foreach ($extarray as $g => $groups) {
                foreach ($groups as $e) {
                    $xml->startElementNs('mdui', '' . $g . '', null);
                    $xml->text($e->getElementValue());
                    $xml->endElement();
                }
            }

            $xml->endElement();
        }
        return $xml;
    }

    private function createServiceLocations(\XMLWriter $xml, $serviceCollection)
    {
        $discrespindex = array('-1');
        foreach ($serviceCollection as $srv) {
            $srvType = $srv->getType();
            $xml->startElementNs($this->srvMap['' . $srvType . '']['ns'], '' . $this->srvMap['' . $srvType . '']['name'] . '', null);
            $xml->writeAttribute('Binding', $srv->getBindingName());
            $xml->writeAttribute('Location', $srv->getUrl());
            if ($this->srvMap['' . $srvType . '']['isorder']) {
                $discorder = $srv->getOrder();
                if (is_null($discorder) || in_array($discorder, $discrespindex)) {
                    $discorder = max($discrespindex) + 20;
                    $discrespindex[] = $discorder;
                } else {
                    $discrespindex[] = $discorder;
                }
                $xml->writeAttribute('index', $discorder);
            }
            $xml->endElement();
        }
        return $xml;
    }

    private function createCerts(\XMLWriter $xml, $certCollection)
    {
        foreach ($certCollection as $cert) {
            $certBody = $cert->getCertDataNoHeaders();
            $keyName = $cert->getKeyname();
            $certUse = $cert->getCertUse();
            if (empty($certBody) && empty($keyName)) {
                continue;
            }

            $xml->startElementNs('md', 'KeyDescriptor', null);
            if (!empty($certUse)) {
                $xml->writeAttribute('use', $certUse);
            }
            $xml->startElementNs('ds', 'KeyInfo', null);
            if (!empty($keyName)) {
                $keynames = explode(',', $keyName);
                foreach ($keynames as $vk) {
                    $xml->startElementNs('ds', 'KeyName', null);
                    $xml->text($vk);
                    $xml->endElement();
                }
            }
            if (!empty($certBody)) {
                $xml->startElementNs('ds', 'X509Data', null);
                $xml->startElementNs('ds', 'X509Certificate', null);
                $xml->text($certBody);
                $xml->endElement(); //X509Certificate
                $xml->endElement(); //X509Data
            }
            $xml->endElement(); // KeyInfo
            $encMethods = $cert->getEncryptMethods();
            foreach($encMethods as $enc)
            {
                $xml->startElementNs('md','EncryptionMethod',null);
                $xml->writeAttribute('Algorithm',$enc);
                $xml->endElement();
            }

            $xml->endElement(); //KeyDescriptor
        }

        return $xml;
    }

    private function createAttributeConsumingService(\XMLWriter $xml, \models\Provider $ent, $options)
    {

        $reqColl = $ent->getAttributesRequirement();
        $requiredAttributes = array();
        foreach ($reqColl as $reqAttr) {
            $toShow = $reqAttr->getAttribute()->showInMetadata();
            if ($toShow) {
                $requiredAttributes['' . $reqAttr->getAttribute()->getId() . ''] = $reqAttr;
            }
        }
        if (count($requiredAttributes) == 0 && (!isset($options['fedreqattrs']) || count($options['fedreqattrs']) == 0)) {
            return $xml;
        }

        $xml->startElementNs('md', 'AttributeConsumingService', null);
        $xml->writeAttribute('index', '0');
        $doFilter1 = array('DisplayName', 'Description');
        $extendMeta = $ent->getExtendMetadata()->filter(
            function (models\ExtendMetadata $entry) use ($doFilter1) {
                return in_array($entry->getElement(), $doFilter1);
            }
        );
        $extArray = array('DisplayName' => array(), 'Description' => array());
        foreach ($extendMeta as $v) {
            $l = $v->getAttributes();
            if (!empty($l['xml:lang'])) {
                $extArray['' . $v->getElement() . '']['' . $l['xml:lang'] . ''] = $v->getEvalue();
            }
        }
        // set 
        if (count($extArray['DisplayName']) == 0) {
            $ldorgnames = array_filter($ent->getMergedLocalDisplayName());
            if (count($ldorgnames) == 0) {
                $extArray['DisplayName']['en'] = $ent->getEntityId();
            } else {
                $extArray['DisplayName'] = $ldorgnames;
            }
        }
        $cArray = array(
            'ServiceName' => $extArray['DisplayName'],
            'ServiceDescription' => $extArray['Description']
        );
        foreach ($cArray as $keyElement => $element) {
            foreach ($element as $extKey => $extValue) {
                $xml->startElementNs('md', $keyElement, null);
                $xml->writeAttribute('xml:lang', $extKey);
                $xml->text($extValue);
                $xml->endElement();
            }
        }

        if (count($requiredAttributes) > 0) {
            foreach ($requiredAttributes as $attr) {


                $xml->startElementNs('md', 'RequestedAttribute', null);
                $xml->writeAttribute('FriendlyName', $attr->getAttribute()->getName());
                $xml->writeAttribute('Name', $attr->getAttribute()->getOid());
                $xml->writeAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                if (strcmp($attr->getStatus(), 'required') == 0) {
                    $xml->writeAttribute('isRequired', 'true');
                } else {
                    $xml->writeAttribute('isRequired', 'false');
                }
                $xml->endElement();
            }
        }
	    else
	    {
		    foreach ($options['fedreqattrs'] as $attr) {
			    $xml->startElementNs('md', 'RequestedAttribute', null);
			    $xml->writeAttribute('FriendlyName', $attr->getAttribute()->getName());
			    $xml->writeAttribute('Name', $attr->getAttribute()->getOid());
			    $xml->writeAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
			    if (strcmp($attr->getStatus(), 'required') == 0) {
				    $xml->writeAttribute('isRequired', 'true');
			    } else {
				    $xml->writeAttribute('isRequired', 'false');
			    }
			    $xml->endElement();
		    }
	    }

        $xml->endElement();
        return $xml;
    }

    private function createIDPSSODescriptor(\XMLWriter $xml, \models\Provider $ent)
    {
        $protocol = $ent->getProtocolSupport('idpsso');
        $protocolEnum = implode(" ", $protocol);
        $doFilter = array('SingleSignOnService', 'IDPSingleLogoutService', 'IDPArtifactResolutionService');
        $serviceLocations = $ent->getServiceLocations()->filter(
            function (models\ServiceLocation $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter);
            }
        );
        $srvsByType = array('SingleSignOnService' => array(), 'IDPSingleLogoutService' => array(), 'IDPArtifactResolutionService' => array());
        foreach ($serviceLocations as $s) {
            $srvsByType['' . $s->getType() . ''][] = $s;
        }
        if (empty($protocolEnum)) {
            $protocolEnum = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        /**
         * @todo check if no services found
         */
        $doCertFilter = array('idpsso');
        $certificates = $ent->getCertificates()->filter(
            function (models\Certificate $entry) use ($doCertFilter) {
                return in_array($entry->getType(), $doCertFilter);
            }
        );

        /**
         * @todo check if certs found
         */
        $scopes = $ent->getScope('idpsso');
        // start IDPSSODescriptor element
        $xml->startElementNs('md', 'IDPSSODescriptor', null);
        $xml->writeAttribute('protocolSupportEnumeration', $protocolEnum);

        $extXML = new XMLWriter();
        $extXML->openMemory();
        $extXML->setIndent(true);
        $extXML->setIndentString(' ');


        foreach ($scopes as $scope) {
            $extXML->startElementNs('shibmd', 'Scope', null);
            $extXML->writeAttribute('regexp', 'false');
            $extXML->text($scope);
            $extXML->endElement();
        }
        $this->createUIIInfo($extXML, $ent, 'idp');
        $this->createDiscoHints($extXML, $ent, 'idp');
        $extXMLoutput = $extXML->outputMemory();
        if (!empty($extXMLoutput)) {
            $xml->startElementNs('md', 'Extensions', null);
            $xml->writeRaw(PHP_EOL . $extXMLoutput);
            $xml->endElement(); // end md:Extensions
        }

        $this->createCerts($xml, $certificates);

        $this->createServiceLocations($xml, $srvsByType['IDPArtifactResolutionService']);
        $this->createServiceLocations($xml, $srvsByType['IDPSingleLogoutService']);
        $nameids = $ent->getNameIds('idpsso');
        foreach ($nameids as $nameid) {
            $xml->startElementNs('md', 'NameIDFormat', null);
            $xml->text($nameid);
            $xml->endElement();
        }
        $this->createServiceLocations($xml, $srvsByType['SingleSignOnService']);
        $xml->endElement(); // end IDPSSODescriptor element


        return $xml;
    }

    private function createAttributeAuthorityDescriptor(\XMLWriter $xml, \models\Provider $ent)
    {
        $doFilter = array('IDPAttributeService');
        $services = $ent->getServiceLocations()->filter(
            function (models\ServiceLocation $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter);
            });
        $doCertFilter = array('aa');
        $certificates = $ent->getCertificates()->filter(
            function (models\Certificate $entry) use ($doCertFilter) {
                return in_array($entry->getType(), $doCertFilter);
            });
        if (count($certificates) == 0 || count($services) == 0) {
            return $xml;
        }
        $protocol = $ent->getProtocolSupport('aa');
        $protocolEnum = implode(" ", $protocol);
        if (empty($protocolEnum)) {
            $protocolEnum = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        $scopes = $ent->getScope('aa');

        $xml->startElementNs('md', 'AttributeAuthorityDescriptor', null);
        $xml->writeAttribute('protocolSupportEnumeration', $protocolEnum);

        if (count($scopes) > 0) {
            $xml->startElementNs('md', 'Extensions', null);
            foreach ($scopes as $scope) {
                $xml->startElementNs('shibmd', 'Scope', null);
                $xml->writeAttribute('regexp', 'false');
                $xml->text($scope);
                $xml->endElement();
            }
            $xml->endElement(); // end md:Extensions
        }
        $this->createCerts($xml, $certificates);
        $this->createServiceLocations($xml, $services);

        $nameids = $ent->getNameIds('aa');
        foreach ($nameids as $nameid) {
            $xml->startElementNs('md', 'NameIDFormat', null);
            $xml->text($nameid);
            $xml->endElement();
        }
        $xml->endElement(); //AttributeAuthorityDescriptor


        return $xml;
    }

    private function createSPSSODescriptor(\XMLWriter $xml, \models\Provider $ent, $options)
    {
        $protocol = $ent->getProtocolSupport('spsso');
        $protocolEnum = implode(" ", $protocol);
        if (empty($protocolEnum)) {
            $protocolEnum = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }

        $srvsByType = array(
            'RequestInitiator' => array(),
            'DiscoveryResponse' => array(),
            'AssertionConsumerService' => array(),
            'SPSingleLogoutService' => array(),
            'SPArtifactResolutionService' => array());
        $doFilter = array_keys($srvsByType);
        $serviceLocations = $ent->getServiceLocations()->filter(
            function (models\ServiceLocation $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter);
            }
        );
        $doCertFilter = array('spsso');
        $certificates = $ent->getCertificates()->filter(
            function (models\Certificate $entry) use ($doCertFilter) {
                return in_array($entry->getType(), $doCertFilter);
            }
        );
        foreach ($serviceLocations as $s) {
            $srvsByType['' . $s->getType() . ''][] = $s;
        }


        $xml->startElementNs('md', 'SPSSODescriptor', null);
        $xml->writeAttribute('protocolSupportEnumeration', $protocolEnum);

        $extXML = new XMLWriter();
        $extXML->openMemory();

        foreach (array('RequestInitiator', 'DiscoveryResponse') as $srvtype) {
            $this->createServiceLocations($extXML, $srvsByType[$srvtype]);
        }

        $this->createUIIInfo($extXML, $ent, 'sp');

        $extXMLoutput = $extXML->outputMemory();
        if (!empty($extXMLoutput)) {
            $xml->startElementNs('md', 'Extensions', null);
            $xml->writeRaw($extXMLoutput);
            $xml->endElement(); //Extensions
        }
        $this->createCerts($xml, $certificates);

        $this->createServiceLocations($xml, $srvsByType['SPArtifactResolutionService']);
        $this->createServiceLocations($xml, $srvsByType['SPSingleLogoutService']);
        $nameids = $ent->getNameIds('spsso');
        foreach ($nameids as $nameid) {
            $xml->startElementNs('md', 'NameIDFormat', null);
            $xml->text($nameid);
            $xml->endElement(); //NameIDFormat
        }
        $this->createServiceLocations($xml, $srvsByType['AssertionConsumerService']);


        if ($options['attrs'] != 0) {
            $this->createAttributeConsumingService($xml, $ent, $options);
        }
        $xml->endElement(); //SPSSODescriptor
        return $xml;
    }

    private function createOrganization(\XMLWriter $xml, models\Provider $ent)
    {
        $lorgnames = array_filter($ent->getMergedLocalName());
        $ldorgnames = array_filter($ent->getMergedLocalDisplayName());
        $lurls = array_filter($ent->getHelpdeskUrlLocalized());
        if (count($lurls) == 0 || count($lorgnames) == 0 || count($ldorgnames) == 0) {
            return $xml;
        }
        $xml->startElementNs('md', 'Organization', null);
        foreach ($lorgnames as $k => $v) {
            $xml->startElementNs('md', 'OrganizationName', null);
            $xml->writeAttribute('xml:lang', $k);
            $xml->text($v);
            $xml->endElement();
        }
        foreach ($ldorgnames as $k => $v) {
            $xml->startElementNs('md', 'OrganizationDisplayName', null);
            $xml->writeAttribute('xml:lang', $k);
            $xml->text($v);
            $xml->endElement();
        }
        foreach ($lurls as $k => $v) {
            $xml->startElementNs('md', 'OrganizationURL', null);
            $xml->writeAttribute('xml:lang', $k);
            $xml->text($v);
            $xml->endElement();
        }
        $xml->endElement();
        return $xml;
    }

    private function verifySPSSO(models\Provider $ent)
    {
        $doFilter = array('AssertionConsumerService');
        $serviceLocations = $ent->getServiceLocations()->filter(
            function (models\ServiceLocation $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter);
            }
        );
        if (count($serviceLocations) == 0) {
            log_message('error', __METHOD__ . ' missing AssertionConsumerService for entity:' . $ent->getEntityId());
            return FALSE;
        }
        return TRUE;
    }

    private function verifyIDPSSO(\models\Provider $ent)
    {
        $doFilter = array('SingleSignOnService');
        $serviceLocations = $ent->getServiceLocations()->filter(
            function (models\ServiceLocation $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter);
            }
        );
        if (count($serviceLocations) == 0) {
            log_message('error', __METHOD__ . ' missing SingleSignOnService for entity:' . $ent->getEntityId());
            return FALSE;
        }
        $doCertFilter = array('idpsso');
        $certificates = $ent->getCertificates()->filter(
            function (models\Certificate $entry) use ($doCertFilter) {
                return in_array($entry->getType(), $doCertFilter);
            }
        );
        if (count($certificates) == 0) {
            log_message('error', __METHOD__ . ' missing cert for IDP : entity:' . $ent->getEntityId());
            return FALSE;
        }

        return TRUE;
    }


    private function verifyAA(\models\Provider $ent)
    {
        $doFilter = array('IDPAttributeService');
        $serviceLocations = $ent->getServiceLocations()->filter(
            function (models\ServiceLocation $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter);
            }
        );
        if (count($serviceLocations) == 0) {
            log_message('warning', __METHOD__ . ' missing  ServiceLocations (AA) for entity:' . $ent->getEntityId());
            return FALSE;
        }
        $doCertFilter = array('aa');
        $certificates = $ent->getCertificates()->filter(
            function (models\Certificate $entry) use ($doCertFilter) {
                return in_array($entry->getType(), $doCertFilter);
            }
        );
        if (count($certificates) == 0) {
            log_message('error', __METHOD__ . ' missing cert for AA : entity:' . $ent->getEntityId());
            return FALSE;
        }

        return TRUE;
    }


    // if $doCacheId is set it saves entiy in cache with key = ${systemprefix}.$doCacheId
    public function entityConvert(\XMLWriter $xmlOut, \models\Provider $ent, $options, $doCacheId = null)
    {

        if (!$doCacheId) {
            $xml = $xmlOut;
        } else {
            $xml = new XMLWriter();
            $xml->openMemory();
            $xml->setIndent(true);
            $xml->setIndentString(' ');
        }
        $type = $ent->getType();
        $hasIdpRole = FALSE;
        $hasSpRole = FALSE;
        if (strcasecmp($type, 'IDP') == 0) {
            $rolesFns = array('createIDPSSODescriptor', 'createAttributeAuthorityDescriptor');
            $hasIdpRole = TRUE;
        } elseif (strcasecmp($type, 'SP') == 0) {
            $hasSpRole = TRUE;
            $rolesFns = array('createSPSSODescriptor');
        } else {
            $hasIdpRole = TRUE;
            $hasSpRole = TRUE;
            $rolesFns = array('createIDPSSODescriptor', 'createAttributeAuthorityDescriptor', 'createSPSSODescriptor');
        }
        $islocal = $ent->getLocal();
        $valiUntil = $ent->getValidTo();
        $isValidIDPSSO = FALSE;
        $isValidAA = FALSE;
        if ($hasIdpRole) {
            $isValidIDPSSO = $this->verifyIDPSSO($ent);
            $isValidAA = $this->verifyAA($ent);
            $canProceed = ($isValidAA || $isValidIDPSSO);
            if (!$canProceed) {
                return $xml;
            }
        }
        if ($hasSpRole) {
            $canProceed = $this->verifySPSSO($ent);
            if (!$canProceed) {
                return $xml;
            }
        }

        $xml->startElementNs('md', 'EntityDescriptor', null);
        if ($islocal && $this->isGenIdFnExist) {
            $genId = customGenerateEntityDescriptorID(array('id' => '' . $ent->getId() . '', 'entityid' => '' . $ent->getEntityId() . ''));
            if (!empty($genId)) {
                $xml->writeAttribute('ID', $genId);
            }
        }
        $xml->writeAttribute('entityID', $ent->getEntityId());
        if (!empty($valiUntil)) {
            $xml->writeAttribute('validUntil', $valiUntil->format('Y-m-d\TH:i:s\Z'));
        }
        // entity exitension start
        $this->createEntityExtensions($xml, $ent);
        // entity ext end

        foreach ($rolesFns as $fn) {
            if (strcmp($fn, 'createSPSSODescriptor') == 0) {
                $this->$fn($xml, $ent, $options);
            } elseif(strcmp($fn, 'createIDPSSODescriptor') == 0  && $isValidIDPSSO) {
                $this->$fn($xml, $ent);
            } elseif(strcmp($fn, 'createAttributeAuthorityDescriptor') == 0  && $isValidAA) {
                $this->$fn($xml, $ent);
            }
        }
        
        $this->createOrganization($xml, $ent);
        $this->createContacts($xml, $ent);

        $xml->endElement();
        if (!$doCacheId) {
            return $xml;
        } else {
            $entityPart = $xml->outputMemory();

            $this->ci->cache->save($doCacheId, $entityPart, 600);
            $xmlOut->writeRaw($entityPart);
            return $xmlOut;
        }
    }

    public function createXMLDocument()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString(' ');
        $xml->startDocument('1.0', 'UTF-8');
        return $xml;
    }

    /**
     * @param \models\Provider $ent
     * @param $options
     * @param bool $outputXML
     * @return string|XMLWriter
     */
    public function entityConvertNewDocument(\models\Provider $ent, $options, $outputXML = false)
    {
        $type = $ent->getType();
        $hasIdpRole = FALSE;
        $hasSpRole = FALSE;
        if (strcasecmp($type, 'IDP') == 0) {
            $rolesFns = array('createIDPSSODescriptor', 'createAttributeAuthorityDescriptor');
            $hasIdpRole = TRUE;
        } elseif (strcasecmp($type, 'SP') == 0) {
            $hasSpRole = TRUE;
            $rolesFns = array('createSPSSODescriptor');
        } else {
            $hasIdpRole = TRUE;
            $hasSpRole = TRUE;
            $rolesFns = array('createIDPSSODescriptor', 'createAttributeAuthorityDescriptor', 'createSPSSODescriptor');
        }
        $islocal = $ent->getLocal();
        $valiUntil = $ent->getValidTo();

        $isValidIDPSSO = FALSE;
        $isValidAA = FALSE;
        if ($hasIdpRole) {
            $isValidIDPSSO = $this->verifyIDPSSO($ent);
            $isValidAA = $this->verifyAA($ent);
            $canProceed =  ($isValidIDPSSO || $isValidAA);
            if (!$canProceed) {
                return null;
            }
        }
        if ($hasSpRole) {
            $canProceed = $this->verifySPSSO($ent);
            if (!$canProceed) {
                return null;
            }
        }
        $xml = $this->createXMLDocument();
        $xml->startElementNs('md', 'EntityDescriptor', null);
        $xml->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v) {
            $xml->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }
        if ($islocal && $this->isGenIdFnExist) {
            $genId = customGenerateEntityDescriptorID(array('id' => '' . $ent->getId() . '', 'entityid' => '' . $ent->getEntityId() . ''));
            if (!empty($genId)) {

                $xml->writeAttribute('ID', $genId);
            }
        }
        $xml->writeAttribute('entityID', $ent->getEntityId());
        if (!empty($valiUntil)) {
            $xml->writeAttribute('validUntil', $valiUntil->format('Y-m-d\TH:i:s\Z'));
        }
// entity exitension start
        $this->createEntityExtensions($xml, $ent);
// entity ext end

        foreach ($rolesFns as $fn) {
            if (strcmp($fn, 'createSPSSODescriptor') == 0) {
                $this->$fn($xml, $ent, $options);
            } elseif(strcmp($fn, 'createIDPSSODescriptor') == 0  && $isValidIDPSSO) {
                $this->$fn($xml, $ent);
            } elseif(strcmp($fn, 'createAttributeAuthorityDescriptor') == 0  && $isValidAA) {
                $this->$fn($xml, $ent);
            }
        }

        $this->createOrganization($xml, $ent);
        $this->createContacts($xml, $ent);

        $xml->endElement();
        $xml->endDocument();
        if ($outputXML) {
            return $xml->outputMemory();
        } else {
            return $xml;
        }
    }

    public function entityStaticConvert(\XMLWriter $xml, \models\Provider $ent)
    {
        $s = $ent->getStaticMetadata();
        if (!empty($s)) {
            $meta = $s->getMetadata();
            $xml->writeRaw($meta);
        }
        return $xml;
    }

}
