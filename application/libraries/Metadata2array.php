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
 * Metadata2array Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadata2array
{

    private $i;
    private $occurance;
    private $metaArray;
    private $coclist;
    private $regpollist;
    private $nameidsattrs = array();
    private $newNameSpaces = array();

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->i = 0;
        $this->occurance = array();
        $this->metaArray = array();
        $this->coclist = array();
        $this->regpollist = array();
        $tmpnemaids = $this->em->getRepository("models\Attribute")->findBy(array('name' => array('persistentId', 'transientId')));
        foreach ($tmpnemaids as $p)
        {
            $this->nameidsattrs['' . $p->getName() . ''] = $p->getOid();
        }
    }

    function rootConvert($xml, $full = false)
    {
        if (!$xml instanceOf \DOMDocument)
        {
            $this->doc = new \DOMDocument();
            $this->xpath = new \DomXPath($this->doc);
            $this->doc->loadXML($xml);
        }
        else
        {
            $this->doc = $xml;
            $this->xpath = new \DomXPath($this->doc);
        }
        $namespaces = h_metadataNamespaces();
        foreach ($namespaces as $key => $value)
        {
            $this->xpath->registerNamespace($key, $value);
        }
        foreach ($this->doc->childNodes as $child)
        {
            if ($child instanceof \DOMElement)
            {
                $this->entitiesConvert($child, $full);
            }
        }

        foreach ($this->coclist as $attrname => $attrvalues)
        {
            $reducedList = array_unique($attrvalues);
            foreach ($reducedList as $r)
            {
                $existing = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $r, 'type' => 'entcat', 'subtype' => $attrname));
                if (empty($existing))
                {
                    $nconduct = new models\Coc;
                    $nconduct->setEntityCategory($r, $r, $attrname, '' . $attrname . ': ' . $r . '', FALSE);
                    $this->em->persist($nconduct);
                }
            }
        }
        foreach ($this->regpollist as $k => $v)
        {
            $reducedList = array_unique($v);
            foreach ($reducedList as $c)
            {
                $existing = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $c, 'type' => 'regpol', 'lang' => $k));
                if (empty($existing))
                {
                    $nregpol = new models\Coc;
                    $nregpol->setUrl($c);
                    $nregpol->setName($c);
                    $nregpol->setType('regpol');
                    $nregpol->setLang($k);
                    $nregpol->setDescription($c);
                    $nregpol->setAvailable(FALSE);
                    $this->em->persist($nregpol);
                }
            }
        }
        $this->em->flush();
        return $this->metaArray;
    }

    function entitiesConvert(\DOMElement $doc, $full = false)
    {
        if ($doc->nodeName === "md:EntityDescriptor" || $doc->nodeName === "EntityDescriptor")
        {
            $this->entityConvert($doc, $full);
        }
        elseif ($doc->nodeName === "EntitiesDescriptor" || $doc->nodeName === "md:EntitiesDescriptor")
        {
            $lxpath = new \DomXPath($this->doc);
            foreach ($lxpath->query('namespace::*', $doc) as $pnode)
            {
                $prefix = $pnode->prefix;
                $val = $pnode->nodeValue;
                if (!empty($prefix) && (strcmp($prefix, 'xml') != 0))
                {
                    $this->newNameSpaces['' . $prefix . ''] = $val;
                }
            }
            $namespaces = h_metadataNamespaces();
            $this->newNameSpaces = array_diff_assoc($this->newNameSpaces, $namespaces);

            if (count($this->newNameSpaces))
            {
                log_message('warning', __METHOD__ . ' Found additional xmlns not known by system ' . serialize($this->newNameSpaces));
                foreach ($this->newNameSpaces as $k => $v)
                {
                    $this->xpath->registerNamespace($k, $v);
                }
            }
            if ($doc->hasChildNodes())
            {
                foreach ($doc->childNodes as $child)
                {
                    if ($child instanceof \DOMElement)
                    {
                        $this->entitiesConvert($child, $full);
                    }
                }
            }
        }
        else
        {
            return;
        }
    }

    public function entityDOMToArray(\DOMElement $node, $full = false)
    {
        /**
         * @todo fi ix
         */
        $this->doc = new \DOMDocument();


        $this->entityConvert($node, $full);
        return $this->metaArray;
    }

    private function entityConvert(\DOMElement $node, $full = false)
    {
        $isIdp = false;
        $isSp = false;
        $entity = array(
            'metadata' => null,
            'details' => null,
            'entityid' => $node->getAttribute('entityID'),
            'validuntil' => $node->getAttribute('validUntil'),
            'rigistrar' => null,
            'regdate' => null,
            'coc' => array(),
            'regpol' => array(),
            'details' => array(
                'org' => array('OrganizationName' => array(), 'OrganizationDisplayName' => array(), 'OrganizationURL' => array()),
                'contacts' => array(),
                'reqattrs' => array(),
            ),
        );
        $allowedEntcats = attrsEntCategoryList();
        foreach ($node->childNodes as $gnode)
        {
            if ($gnode->nodeName === 'md:IDPSSODescriptor' || $gnode->nodeName === 'IDPSSODescriptor')
            {
                $isIdp = true;
                $entity['type'] = 'IDP';
                if (!empty($full))
                {

                    $entity['details']['idpssodescriptor'] = $this->idpSSODescriptorConvert($gnode);
                }
            }
            elseif ($gnode->nodeName === 'md:SPSSODescriptor' || $gnode->nodeName === 'SPSSODescriptor')
            {
                $isSp = true;
                $entity['type'] = 'SP';
                if (!empty($full))
                {

                    $entity['details']['spssodescriptor'] = $this->spSSODescriptorConvert($gnode);
                }
                foreach ($gnode->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'RequestedAttribute') as $reqattr)
                {
                    if (strcasecmp($reqattr->getAttribute('NameFormat'), 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri') == 0)
                    {
                        $entity['details']['reqattrs'][] = array('name' => '' . $reqattr->getAttribute('Name') . '',
                            'req' => $reqattr->getAttribute('isRequired'));
                    }
                }
            }
            elseif ($gnode->nodeName === 'md:AttributeAuthorityDescriptor' || $gnode->nodeName === 'AttributeAuthorityDescriptor')
            {
                $entity['details']['aadescriptor'] = $this->attributeAuthorityDescriptorConvert($gnode);
            }
            elseif ($gnode->nodeName === 'Extensions' || $gnode->nodeName === 'md:Extensions')
            {
                if ($gnode->hasChildNodes())
                {
                    foreach ($gnode->childNodes as $enode)
                    {
                        if ($enode->nodeName == 'mdrpi:RegistrationInfo' && $enode->hasAttributes())
                        {
                            $entity['registrar'] = $enode->getAttribute('registrationAuthority');
                            $entity['regdate'] = $enode->getAttribute('registrationInstant');
                            if ($enode->hasChildNodes())
                            {
                                foreach ($enode->childNodes as $ch)
                                {
                                    if ($ch->nodeName == 'mdrpi:RegistrationPolicy')
                                    {
                                        $chlang = strtolower($ch->getAttribute('xml:lang'));
                                        $chvalue = $ch->nodeValue;
                                        if (!empty($chlang) && !empty($chvalue))
                                        {
                                            $entity['regpol'][] = array('lang' => $chlang, 'url' => $chvalue);
                                            $this->regpollist['' . $chlang . ''][] = $chvalue;
                                        }
                                    }
                                }
                            }
                        }
                        elseif ($enode->nodeName === 'mdattr:EntityAttributes' && $enode->hasChildNodes())
                        {
                            foreach ($enode->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Attribute') as $enode2)
                            {
                                if ($enode2->hasAttributes() && in_array($enode2->getAttribute('Name'), $allowedEntcats) && $enode2->hasChildNodes())
                                {
                                    foreach ($enode2->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'AttributeValue') as $enode3)
                                    {
                                        $entity['coc']['' . $enode2->getAttribute('Name') . ''][] = $enode3->nodeValue;
                                        $this->coclist['' . $enode2->getAttribute('Name') . ''][] = $enode3->nodeValue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            elseif ($gnode->nodeName === 'md:ContactPerson' || $gnode->nodeName === 'ContactPerson')
            {
                $entity['details']['contacts'][] = $this->contactPersonConvert($gnode);
            }
            elseif ($gnode->nodeName === 'md:Organization' || $gnode->nodeName === 'Organization')
            {
                $entity['details']['org'] = $this->organizationConvert($gnode);
            }
        }
        if ($isIdp && $isSp)
        {
            $entity['type'] = 'BOTH';
        }

        if ($isSp && isset($entity['details']['spssodescriptor']['nameid']) && is_array($entity['details']['spssodescriptor']['nameid']) && count($entity['details']['spssodescriptor']['nameid']) > 0)
        {
            if (in_array('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $entity['details']['spssodescriptor']['nameid']) && array_key_exists('persistentId', $this->nameidsattrs))
            {
                $entity['details']['reqattrs'][] = array('name' => $this->nameidsattrs['persistentId'], 'req' => 'True');
            }
            elseif (in_array('urn:oasis:names:tc:SAML:2.0:nameid-format:transient', $entity['details']['spssodescriptor']['nameid']) && array_key_exists('transientId', $this->nameidsattrs))
            {
                $entity['details']['reqattrs'][] = array('name' => $this->nameidsattrs['transientId'], 'req' => 'True');
            }
        }
        elseif ($isSp && array_key_exists('transientId', $this->nameidsattrs))
        {
            $entity['details']['reqattrs'][] = array('name' => $this->nameidsattrs['transientId'], 'req' => 'True');
        }
        /**
         * check for duplicates
         */
        if (isset($entity['details']['reqattrs']) && is_array($entity['details']['reqattrs']))
        {
            $attrssets = array();
            foreach ($entity['details']['reqattrs'] as $k => $v)
            {
                if (in_array($v['name'], $attrssets))
                {
                    unset($entity['details']['reqattrs']['' . $k . '']);
                }
                else
                {
                    $attrssets[] = $v['name'];
                }
            }
        }

        try
        {
            $entity['metadata'] = $this->doc->saveXML($node);
        }
        catch (Exception $e)
        {
            log_message('warning', 'Couldn store xml: ' . $e);
        }
        $this->metaArray[$entity['entityid']] = $entity;
    }

    private function attributeAuthorityDescriptorConvert(\DOMElement $node)
    {
        $result = array();
        $result['protocols'] = array_filter(explode(' ', $node->getAttribute('protocolSupportEnumeration')), 'strlen');
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName === 'md:Extensions' || $child->nodeName === 'Extensions')
            {
                $result['extensions'] = $this->aaExtensionsToArray($child);
            }
            elseif ($child->nodeName === 'md:NameIDFormat' || $child->nodeName === 'NameIDFormat')
            {
                $result['nameid'][] = $child->nodeValue;
            }
            elseif ($child->nodeName === 'AttributeService' || $child->nodeName === 'md:AttributeService')
            {
                $result['attributeservice'][] = array('binding' => $child->getAttribute('Binding'), 'location' => $child->getAttribute('Location'));
            }
            elseif ($child->nodeName === "KeyDescriptor" || $child->nodeName === "md:KeyDescriptor")
            {
                $result['certificate'][] = $this->keyDescriptorConvert($child);
            }
        }
        return $result;
    }

    private function idpSSODescriptorConvert(\DOMElement $node)
    {
        $result = array();
        $result['protocols'] = array_filter(explode(' ', $node->getAttribute('protocolSupportEnumeration')), 'strlen');
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName === 'md:Extensions' || $child->nodeName === 'Extensions')
            {
                $result['extensions'] = $this->extensionsToArray($child);
            }
            elseif ($child->nodeName === 'md:NameIDFormat' || $child->nodeName === 'NameIDFormat')
            {
                $result['nameid'][] = $child->nodeValue;
            }
            elseif ($child->nodeName === 'SingleSignOnService' || $child->nodeName === 'md:SingleSignOnService')
            {
                $result['servicelocations']['singlesignonservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
            elseif ($child->nodeName === 'md:SingleLogoutService' || $child->nodeName === 'SingleLogoutService')
            {
                $result['servicelocations']['singlelogout'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
            elseif ($child->nodeName === 'md:ArtifactResolutionService' || $child->nodeName === 'ArtifactResolutionService')
            {
                $result['servicelocations']['artifactresolutionservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
            }
            elseif ($child->nodeName == "KeyDescriptor" || $child->nodeName == "md:KeyDescriptor")
            {
                $result['certificate'][] = $this->keyDescriptorConvert($child);
            }
        }
        return $result;
    }

    private function spSSODescriptorConvert($node)
    {
        $profilesTmp = $node->getAttribute('protocolSupportEnumeration');
        $profiles = explode(" ", $profilesTmp);
        $result = array(
            'protocols' => $profiles,
            'servicelocations' => array('assertionconsumerservice' => array(), 'singlelogout' => array()),
            'extensions' => array(
                'idpdisc' => array(),
                'init' => array(),
                'desc' => array()
            ),
        );
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName === 'md:Extensions' || $child->nodeName === 'Extensions')
            {
                $result['extensions'] = $this->extensionsToArray($child);
            }
            elseif ($child->nodeName === 'md:NameIDFormat' || $child->nodeName === 'NameIDFormat')
            {
                $result['nameid'][] = $child->nodeValue;
            }
            elseif ($child->nodeName === 'md:AssertionConsumerService' || $child->nodeName === 'AssertionConsumerService')
            {
                $result['servicelocations']['assertionconsumerservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
            }
            elseif ($child->nodeName === 'md:ArtifactResolutionService' || $child->nodeName === 'ArtifactResolutionService')
            {
                $result['servicelocations']['artifactresolutionservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
            }
            elseif ($child->nodeName === 'md:SingleLogoutService' || $child->nodeName === 'SingleLogoutService')
            {
                $result['servicelocations']['singlelogout'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
            elseif ($child->nodeName === 'md:ManageNameIDService' || $child->nodeName === 'ManageNameIDService')
            {
                $result['servicelocations']['managenameidservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
            elseif ($child->nodeName === 'KeyDescriptor' || $child->nodeName === 'md:KeyDescriptor')
            {
                $result['certificate'][] = $this->keyDescriptorConvert($child);
            }
        }

        return $result;
    }

    private function keyDescriptorConvert($node)
    {
        $cert = array();
        $usecase = $node->getAttribute('use');
        $cert['use'] = $usecase;
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName == "KeyInfo" || $child->nodeName == "ds:KeyInfo")
            {
                foreach ($child->childNodes as $gchild)
                {
                    if ($gchild->nodeName == "KeyName" || $gchild->nodeName == "ds:KeyName")
                    {
                        $cert['keyname'][] = $gchild->nodeValue;
                    }
                    elseif ($gchild->nodeName == "ds:X509Data" || $gchild->nodeName == "X509Data")
                    {
                        foreach ($gchild->childNodes as $enode)
                        {
                            if ($enode->nodeName == "ds:X509Certificate" || $enode->nodeName == "X509Certificate")
                            {
                                if (!empty($enode->nodeValue))
                                {
                                    $cert['x509data']['x509certificate'] = reformatPEM($enode->nodeValue);
                                }
                                else
                                {
                                    $cert['x509data']['x509certificate'] = null;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $cert;
    }

    private function aaExtensionsToArray($node)
    {
        $result = array();
        foreach ($node->childNodes as $enode)
        {
            if ($enode->nodeName === 'shibmd:Scope' || $enode->nodeName === 'Scope' || $enode->nodeName === 'saml1md:Scope')
            {
                $result['aascope'][] = $enode->nodeValue;
            }
        }
        return $result;
    }

    private function extensionsToArray($node)
    {
        foreach ($node->childNodes as $enode)
        {
            if ($enode->nodeName === 'shibmd:Scope' || $enode->nodeName === 'Scope' || $enode->nodeName === 'saml1md:Scope')
            {
                $ext['scope'][] = $enode->nodeValue;
            }
            elseif ($enode->nodeName == 'idpdisc:DiscoveryResponse' || $enode->nodeName == 'DiscoveryResponse')
            {
                $ext['idpdisc'][] = array('binding' => $enode->getAttribute('Binding'), 'url' => $enode->getAttribute('Location'), 'order' => $enode->getAttribute('index'));
            }
            elseif ($enode->nodeName == 'init:RequestInitiator' || $enode->nodeName == 'RequestInitiator')
            {
                $ext['init'][] = array('binding' => $enode->getAttribute('Binding'), 'url' => $enode->getAttribute('Location'));
            }
            elseif ($enode->nodeName == 'mdui:UIInfo' && $enode->hasChildNodes())
            {
                foreach ($enode->childNodes as $gnode)
                {
                    /**
                     * @todo finish  
                     */
                    if ($gnode->nodeName == 'mdui:Description' || $gnode->nodeName == 'Description')
                    {
                        $ext['desc'][] = array('lang' => $gnode->getAttribute('xml:lang'), 'val' => $gnode->nodeValue);
                    }
                    elseif ($gnode->nodeName == 'mdui:DisplayName' || $gnode->nodeName == 'DisplayName')
                    {
                        $ext['displayname'][] = array('lang' => $gnode->getAttribute('xml:lang'), 'val' => $gnode->nodeValue);
                    }
                    elseif ($gnode->nodeName == 'mdui:PrivacyStatementURL' || $gnode->nodeName == 'PrivacyStatementURL')
                    {
                        $ext['privacyurl'][] = array('lang' => $gnode->getAttribute('xml:lang'), 'val' => $gnode->nodeValue);
                    }
                    elseif ($gnode->nodeName == 'mdui:InformationURL' || $gnode->nodeName == 'InformationURL')
                    {
                        $ext['informationurl'][] = array('lang' => $gnode->getAttribute('xml:lang'), 'val' => $gnode->nodeValue);
                    }
                    elseif ($gnode->nodeName === 'mdui:Logo')
                    {
                        $logoval = $gnode->nodeValue;
                        if (substr($logoval, 0, 4) === "http")
                        {
                            $ext['logo'][] = array('height' => $gnode->getAttribute('height'), 'width' => $gnode->getAttribute('width'), 'xml:lang' => $gnode->getAttribute('xml:lang'), 'val' => $logoval);
                        }
                    }
                }
            }
            elseif ($enode->nodeName === 'mdui:DiscoHints' && $enode->hasChildNodes())
            {
                log_message('debug', 'GK : DiscoHints found');
                foreach ($enode->childNodes as $agnode)
                {
                    $geovalue = array();
                    if ($agnode->nodeName == 'mdui:GeolocationHint')
                    {
                        $geovalue = explode(',', str_ireplace('geo:', '', $agnode->nodeValue));
                        if (count($geovalue) == 2)
                        {
                            $numericvalues = true;
                            foreach ($geovalue as $g)
                            {
                                if (!is_numeric($g))
                                {
                                    $numericvalues = false;
                                }
                            }
                            if ($numericvalues === TRUE)
                            {
                                $ext['geo'][] = array_values($geovalue);
                            }
                        }
                    }
                }
            }
        }
        if (empty($ext))
        {
            $ext = array();
        }
        return $ext;
    }

    private function organizationConvert($node)
    {
        $org = array('OrganizationName' => array(), 'OrganizationDisplayName' => array(), 'OrganizationURL' => array());
        if ($node->hasChildNodes())
        {
            foreach ($node->childNodes as $child)
            {
                if (!$child instanceOf DOMText)
                {
                    $org['' . str_replace('md:', '', $child->nodeName) . '']['' . $child->getAttribute('xml:lang') . ''] = trim($child->nodeValue);
                }
            }
        }
        return $org;
    }

    private function contactPersonConvert($node)
    {
        $cnt = array();
        $cnt['type'] = $node->getAttribute('contactType');
        $cnt['surname'] = null;
        $cnt['givenname'] = null;
        $cnt['email'] = null;
        foreach ($node->childNodes as $cnode)
        {
            if ($cnode->nodeName == "SurName" || $cnode->nodeName == "md:SurName")
            {
                $cnt['surname'] = $cnode->nodeValue;
            }
            if ($cnode->nodeName == "GivenName" || $cnode->nodeName == "md:GivenName")
            {
                $cnt['givenname'] = $cnode->nodeValue;
            }
            if ($cnode->nodeName == "EmailAddress" || $cnode->nodeName == "md:EmailAddress")
            {
                $cnt['email'] = $cnode->nodeValue;
            }
        }
        return $cnt;
    }

}
