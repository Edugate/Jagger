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
    private $em;
    private $isGenIdFnExist;
    private $useGlobalRegistrar;
    private $globalRegistrar;
    private $globalRegpolicy;
    private $logoPrefixUrl;
    private $srvMap;

    function __construct()
    {
        $this->ci = & get_instance();

        $this->srvMap = array(
            'SingleSignOnService' => array(
                'name' => 'SingleSignOnService',
                'isorder' => 0
            ),
            'IDPSingleLogoutService' => array(
                'name' => 'SingleLogoutService',
                'isorder' => 0
            ),
            'IDPArtifactResolutionService' => array(
                'name' => 'ArtifactResolutionService',
                'isorder' => 1
            ),
        );
        if (function_exists('customGenerateEntityDescriptorID'))
        {
            $this->isGenIdFnExist = true;
        }
        else
        {
            $this->isGenIdFnExist = false;
        }
        if (!empty($this->ci->config->item('registrationAutority')) && !empty($this->ci->config('load_registrationAutority')))
        {
            $this->useGlobalRegistrar = true;
            $this->globalRegistrar = $this->ci->config->item('registrationAutority');
            $this->globalRegpolicy = $this->ci->config->item('registrationPolicy');
        }
        else
        {
            $this->useGlobalRegistrar = false;
        }
        $logoPrefixURI = $this->ci->config->item('rr_logouriprefix');
        $logoBaseUrl = $this->ci->config->item('rr_logobaseurl');
        if (empty($logoBaseUrl))
        {
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

    private function createXMLDocucument()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString(' ');
        $xml->startDocument('1.0', 'UTF-8');
        return $xml;
    }

    private function createEntityExtensions(\XMLWriter $xml, \models\Provider $ent)
    {
        $registrar = $ent->getRegistrationAuthority();
        if (empty($registrar) && $ent->getLocal() && $this->useGlobalRegistrar)
        {
            $registrar = $this->globalRegistrar;
        }
        $doFilter = array(TRUE);
        $cocs = $ent->getCoc()->filter(
                function(models\Coc $entry) use ($doFilter) {
            return in_array($entry->getAvailable(), $doFilter);
        }
        );

        $cocsByGroups = array('entcat' => array(), 'regpol' => array());
        foreach ($cocs as $v)
        {
            $cocsSubtype = $v->getSubtype();
            if (!empty($cocsSubtype))
            {
                $cocsByGroups['' . $v->getType() . ''][$cocsSubtype][] = $v;
            }
            else
            {
                $cocsByGroups['' . $v->getType() . ''][] = $v;
            }
        }

        if (!empty($registrar) || count($cocs) > 0)
        {

            $xml->startElementNs('md', 'Extensions', null);
            if (!empty($registrar))
            {
                $xml->startElementNs('mdrpi', 'RegistrationInfo', null);
                $xml->writeAttribute('registrationAuthority', $registrar);
                $registerDate = $ent->getRegistrationDate();
                if (!empty($registerDate))
                {
                    $xml->writeAttribute('registrationInstant', $registerDate->format('Y-m-d') . 'T' . $registerDate->format('H:i:s') . 'Z');
                    if (count($cocsByGroups['regpol']) > 0)
                    {

                        $langsset = array();
                        foreach ($cocsByGroups['regpol'] as $v)
                        {
                            $vlang = $v->getLang();
                            if (in_array($vlang, $langsset))
                            {
                                \log_message('error', __METHOD__ . ' multiple registration policies are set for lang:' . $vlang . ' for entityId: ' . $ent->getEntityId());
                                continue;
                            }
                            $langsset[] = $vlang;
                            $xml->startElementNs('mdrpi', 'RegistrationPolicy', null);
                            $xml->writeAttribute('xml:lang', $vlang);
                            $xml->text($v->getUrl());
                            $xml->endElement();
                        }
                    }
                    elseif (!empty($this->globalRegpolicy))
                    {
                        $xml->startElementNs('mdrpi', 'RegistrationPolicy', null);
                        $xml->writeAttribute('xml:lang', 'en');
                        $xml->text($this->globalRegpolicy);
                        $xml->endElement();
                    }
                }
                $xml->endElement();
            }

            if (count($cocsByGroups['entcat']) > 0)
            {
                $xml->startElementNs('mdattr', 'EntityAttributes', null);
                foreach ($cocsByGroups['entcat'] as $k => $v)
                {

                    $xml->startElementNs('saml', 'Attribute', null);
                    $xml->writeAttribute('Name', $k);
                    $xml->writeAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                    foreach ($v as $k1 => $v1)
                    {

                        $xml->startElementNs('saml', 'AttributeValue', null);
                        $xml->text($v1->getUrl());
                        $xml->endElement();
                    }
                    $xml->endElement();
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
        foreach ($contacts as $c)
        {
            $givenName = $c->getGivenName();
            $surName = $c->getSurname();
            $email = $c->getEmail();
            $xml->startElementNs('md', 'ContactPerson', null);
            $xml->writeAttribute('contactType', $c->getType());
            if (empty($givenName))
            {
                $xml->startElementNs('md', 'GivenName', null);
                $xml->text($givenName);
                $xml->endElement();
            }
            if (!empty($surName))
            {
                $xml->startElementNs('md', 'SurName', null);
                $xml->text($surName);
                $xml->endElement();
            }
            $xml->startElementNs('md', 'EmailAddress', null);
            $xml->text($email);
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
        foreach ($extendMeta as $v)
        {
            if ((strcasecmp($v->getType(), $role) == 0) && ($v->getNamespace() === 'mdui'))
            {
                $extarray['' . $v->getElement() . ''][] = $v;
            }
        }
        foreach ($extarray as $e)
        {
            if (count($e) > 0)
            {
                $toGenerate = TRUE;
                break;
            }
        }
        if ($toGenerate === FALSE)
        {
            return $xml;
        }
        $xml->startElementNs('mdui', 'UIInfo', null);

        $enLang = array();
        foreach (array('DisplayName', 'Description', 'InformationURL', 'PrivacyStatementURL') as $mduiElement)
        {
            $enLang['' . $mduiElement . ''] = FALSE;


            foreach ($extarray['' . $mduiElement . ''] as $value)
            {
                $lang = $value->getAttributes();
                if (isset($lang['xml:lang']))
                {
                    if (strcmp($lang['xml:lang'], 'en') == 0)
                    {
                        $enLang['' . $mduiElement . ''] = TRUE;
                    }
                    $xml->startElementNs('mdui', '' . $mduiElement . '', null);
                    $xml->writeAttribute('xml:lang', $lang['xml:lang']);
                    $xml->text($value->getElementValue());
                    $xml->endElement();
                }
            }

            if (!$enLang['' . $mduiElement . ''])
            {
                if (strcmp('PrivacyStatementURL', $mduiElement) == 0)
                {
                    $t = $ent->getPrivacyUrl();
                }
                elseif (strcmp('InformationURL', $mduiElement) == 0)
                {
                    $t = $ent->getHelpdeskURL();
                }

                if (!empty($t))
                {
                    $xml->startElementNs('mdui', '' . $mduiElement . '', null);
                    $xml->writeAttribute('xml:lang', 'en');
                    $xml->text($t);
                    $xml->endElement();
                }
            }
        }
        foreach ($extarray['Logo'] as $l)
        {
            $xml->startElementNs('mdui', 'Logo', null);
            $logoAttrs = array_filter($l->getAttributes());
            foreach($logoAttrs as $lk=>$lv)
            {
                $xml->writeAttribute($lk, $lv);
            }
            if (!filter_var($l->getElementValue(), FILTER_VALIDATE_URL))
            {
                $xml->text($this->logoPrefixUrl.$l->getElementValue());
            }
            else
            {
                $xml->text($l->getElementValue());
            }
            $xml->endElement();
        }

        $xml->endElement();
        return $xml;
    }

    private function createDiscoHints(\XMLWriter $xml, \models\Provider $ent, $role)
    {
        //$doFilter1 = array('GeolocationHint');
        // @todo filtering collection
        $extMetada = $ent->getExtendMetadata();
       
        return $xml;
    }

    private function createServiceLocations(\XMLWriter $xml, $serviceCollection)
    {
        foreach ($serviceCollection as $srv)
        {
            $srvType = $srv->getType();
            $xml->startElementNs('md', '' . $this->srvMap['' . $srvType . '']['name'] . '', null);
            $xml->writeAttribute('Binding', $srv->getBindingName());
            $xml->writeAttribute('Location', $srv->getUrl());
            if ($this->srvMap['' . $srvType . '']['isorder'])
            {
                $xml->writeAttribute('index', $srv->getOrder());
            }
            $xml->endElement();
        }
        return $xml;
    }

    private function createCerts(\XMLWriter $xml, $certCollection)
    {
        foreach ($certCollection as $cert)
        {
            $certBody = $cert->getCertDataNoHeaders();
            $keyName = $cert->getKeyname();
            $certUse = $cert->getCertUse();
            if (empty($certBody) && empty($keyName))
            {
                continue;
            }

            $xml->startElementNs('md', 'KeyDescriptor', null);
            if (!empty($certUse))
            {
                $xml->writeAttribute('use', $certUse);
            }
            if (!empty($keyName))
            {
                $keynames = explode(',', $keyName);
                foreach ($keynames as $vk)
                {
                    $xml->startElementNs('ds', 'KeyName', null);
                    $xml->text($vk);
                    $xml->endElement();
                }
            }
            if (!empty($certBody))
            {
                $xml->startElementNs('ds', 'X509Data', null);
                $xml->startElementNs('ds', 'X509Certificate', null);
                $xml->text($certBody);
                $xml->endElement();
                $xml->endElement();
            }

            $xml->endElement(); //KeyDescriptor
        }

        return $xml;
    }

    private function createIDPSSODescriptor(\XMLWriter $xml, \models\Provider $ent)
    {
        $protocol = $ent->getProtocolSupport('idpsso');
        $protocolEnum = implode(" ", $protocol);



        $doFilter = array('SingleSignOnService', 'IDPSingleLogoutService', 'IDPArtifactResolutionService');
        $serviceLocations = $ent->getServiceLocations()->filter(
                function(models\ServiceLocation $entry) use ($doFilter) {
            return in_array($entry->getType(), $doFilter);
        }
        );
        $srvsByType = array('SingleSignOnService' => array(), 'IDPSingleLogoutService' => array(), 'IDPArtifactResolutionService' => array());
        foreach ($serviceLocations as $s)
        {
            $srvsByType['' . $s->getType() . ''][] = $s;
        }
        if (empty($protocolEnum))
        {
            $protocolEnum = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        /**
         * @todo check if no services found
         */
        $doCertFilter = array('idpsso');
        $certificates = $ent->getCertificates()->filter(
                function(models\Certificate $entry) use ($doCertFilter) {
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

        $xml->startElementNs('md', 'Extensions', null);
        foreach ($scopes as $scope)
        {
            $xml->startElementNs('shibmd', 'Scope', null);
            $xml->writeAttribute('regexp', 'false');
            $xml->text($scope);
            $xml->endElement();
        }
        $this->createUIIInfo($xml, $ent, 'idp');
        $this->createDiscoHints($xml, $ent, 'idp');
        $xml->endElement(); // end md:Extensions


        $this->createCerts($xml, $certificates);

        $this->createServiceLocations($xml, $srvsByType['IDPArtifactResolutionService']);
        $this->createServiceLocations($xml, $srvsByType['IDPSingleLogoutService']);
        $nameids = $ent->getNameIds('idpsso');
        foreach ($nameids as $nameid)
        {
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
        return $xml;
    }

    private function createSPSSODescriptor(\XMLWriter $xml, \models\Provider $ent)
    {
        return $xml;
    }

    private function createOrganization(\XMLWriter $xml, \models\Provider $ent)
    {
        $lorgnames = array_filter($ent->getMergedLocalName());
        $ldorgnames = array_filter($ent->getMergedLocalDisplayName());
        $lurls = array_filter($ent->getHelpdeskUrlLocalized());
        if (count($lurls) == 0 || count($lorgnames) == 0 || count($ldorgnames) == 0)
        {
            return $xml;
        }
        $xml->startElementNs('md', 'Organization', null);
        foreach ($lorgnames as $k => $v)
        {
            $xml->startElementNs('md', 'OrganizationName', null);
            $xml->writeAttribute('xml:lang', $k);
            $xml->text($v);
            $xml->endElement();
        }
        foreach ($ldorgnames as $k => $v)
        {
            $xml->startElementNs('md', 'OrganizationDisplayName', null);
            $xml->writeAttribute('xml:lang', $k);
            $xml->text($v);
            $xml->endElement();
        }
        foreach ($lurls as $k => $v)
        {
            $xml->startElementNs('md', 'OrganizationURL', null);
            $xml->writeAttribute('xml:lang', $k);
            $xml->text($v);
            $xml->endElement();
        }
        $xml->endElement();
        return $xml;
    }

    public function entityConvertNewDocument(\models\Provider $ent)
    {
        $xml = $this->createXMLDocucument();
        /**
         * @todo finish
         */
        return $xml;
    }

    public function entityConvert(\XMLWriter $xml, \models\Provider $ent)
    {

        $type = $ent->getType();
        if (strcasecmp($type, 'IDP') == 0)
        {
            $rolesFns = array('createIDPSSODescriptor', 'createAttributeAuthorityDescriptor');
        }
        elseif (strcasecmp($type, 'SP') == 0)
        {
            $rolesFns = array('createSPSSODescriptor');
        }
        else
        {
            $rolesFns = array('createIDPSSODescriptor', 'createAttributeAuthorityDescriptor', 'createSPSSODescriptor');
        }
        $islocal = $ent->getLocal();
        $valiUntil = $ent->getValidTo();

        $xml->startElementNs('md', 'EntityDescriptor', null);
        if ($islocal && $this->isGenIdFnExist)
        {
            $genId = customGenerateEntityDescriptorID(array('id' => '' . $ent->getId() . '', 'entityid' => '' . $ent->getEntityId() . ''));
            if (!empty($genId))
            {

                $xml->writeAttribute('ID', $genId);
            }
        }
        $xml->writeAttribute('entityID', $ent->getEntityId());
        if (!empty($valiUntil))
        {
            $xml->writeAttribute('validUntil', $valiUntil->format('Y-m-d\TH:i:s\Z'));
        }
// entity exitension start
        $this->createEntityExtensions($xml, $ent);
// entity ext end

        foreach ($rolesFns as $fn)
        {
            $this->$fn($xml, $ent);
        }

        $this->createOrganization($xml, $ent);
        $this->createContacts($xml, $ent);

        $xml->endElement();
        return $xml;
    }

    public function entityStaticConvert(\XMLWriter $xml, \models\Provider $ent)
    {
        $s = $ent->getStaticMetadata();
        if (!empty($s))
        {
            $meta = $s->getMetadata();
            $xml->writeRaw($meta);
        }
        return $xml;
    }

}
