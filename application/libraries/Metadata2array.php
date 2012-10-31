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
 * Metadata2array Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadata2array {

    private $i;
    private $occurance;
    private $metaArray;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->i = 0;
        $this->occurance = array();
        $this->metaArray = array();
    }

    function rootConvert($xml, $full = false)
    {
        $result = array('IDP' => array(), 'SP' => array());
        $this->doc = new \DOMDocument();
        $this->xpath = new \DomXPath($this->doc);
        $this->doc->loadXML($xml);
        $namespaces = h_metadataNamespaces();
        foreach ($namespaces as $key => $value)
        {
            $this->xpath->registerNamespace($key, $value);
        }
        foreach ($this->doc->childNodes as $child)
        {
            $this->entitiesConvert($child, $full);
        }
        return $this->metaArray;
    }

    function entitiesConvert($doc, $full = false)
    {
        if ($doc instanceof DOMElement)
        {
            if ($doc->nodeName == "EntityDescriptor" OR $doc->nodeName == "md:EntityDescriptor")
            {
                $this->entityConvert($doc, $full);
            } elseif ($doc->nodeName == "EntitiesDescriptor" OR $doc->nodeName == "md:EntitiesDescriptor")
            {
                foreach ($doc->childNodes as $child)
                {
                    $this->entitiesConvert($child, $full);
                }
            } else
            {
                return;
            }
        }
    }

    private function entityConvert(\DOMElement $node, $full = false)
    {
        $entity = array();
        $entity['metadata'] = null;
        $entity['details'] = null;
        $entity['entityid'] = $node->getAttribute('entityID');
        $entity['validuntil'] = null;
        $entity['rigistrar'] = null;
        $entity['regdate'] = null;
        $validuntil = $node->getAttribute('validUntil');
        if (!empty($validuntil))
        {
            $entity['validuntil'] = $validuntil;
        }
        $is_idp = false;
        $is_sp = false;
        $entity['details']['organization'] = null;
        $entity['details']['contacts'] = array();
        $entity['details']['regpolicy'] = array();
        foreach ($node->childNodes as $gnode)
        {
            if ($gnode->nodeName == 'IDPSSODescriptor' OR $gnode->nodeName == 'md:IDPSSODescriptor')
            {
                $is_idp = true;
                $entity['type'] = 'IDP';
                if (!empty($full))
                {

                    $entity['details']['idpssodescriptor'] = $this->IDPSSODescriptorConvert($gnode);
                }
            }
            if ($gnode->nodeName == 'SPSSODescriptor' OR $gnode->nodeName == 'md:SPSSODescriptor')
            {
                $is_sp = true;
                $entity['type'] = 'SP';
                if (!empty($full))
                {

                    $entity['details']['spssodescriptor'] = $this->SPSSODescriptorConvert($gnode);
                }
            }
            if ($gnode->nodeName == 'Extensions' OR $gnode->nodeName == 'md:Extensions')
            {
                if($gnode->hasChildNodes())
                {
                    foreach ($gnode->childNodes as $enode)
                    {
                        if($enode->nodeName == 'mdrpi:RegistrationInfo' && $enode->hasAttributes())
                        {
                            $entity['registrar'] = $enode->getAttribute('registrationAuthority');
                            $entity['regdate'] = $enode->getAttribute('registrationInstant');
                        }
                    }
                }
          //      $entity['details']['extensions'] = $this->ExtensionsToArray($gnode);
            } elseif ($gnode->nodeName == 'ContactPerson' OR $gnode->nodeName == 'md:ContactPerson')
            {
                $entity['details']['contacts'][] = $this->ContactPersonConvert($gnode);
            } elseif ($gnode->nodeName == 'Organization' OR $gnode->nodeName == 'md:Organization')
            {
                $entity['details']['organization'] = $this->OrganizationConvert($gnode);
            }
        }
        if ($is_idp && $is_sp)
        {
            $entity['type'] = 'BOTH';
        }

        /**
         * @todo decide when add also static metadata 
         */
        //      if (empty($full))
        //     {
        $entity['metadata'] = $this->doc->saveXML($node);
        //    } 
        //echo "<pre>";
        //print_r($entity);
        //echo "</pre>";

        $this->metaArray[$entity['entityid']] = $entity;
    }

    private function IDPSSODescriptorConvert(\DOMElement $node)
    {
        $profiles = $node->getAttribute('protocolSupportEnumeration');
        $profiles = explode(" ", $profiles);
        $result['protocols'] = $profiles;
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName == "Extensions" OR $child->nodeName == "md:Extensions")
            {
                $result['extensions'] = $this->ExtensionsToArray($child);
            } elseif ($child->nodeName == "NameIDFormat" OR $child->nodeName == "md:NameIDFormat")
            {
                $result['nameid'][] = $child->nodeValue;
            } elseif ($child->nodeName == "SingleSignOnService" OR $child->nodeName == "md:SingleSignOnService")
            {
                $result['servicelocations']['singlesignonservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            } elseif ($child->nodeName == "KeyDescriptor" OR $child->nodeName == "md:KeyDescriptor")
            {
                $result['certificate'][] = $this->KeyDescriptorConvert($child);
            }
        }
        return $result;
    }

    private function SPSSODescriptorConvert($node)
    {
        $profiles = $node->getAttribute('protocolSupportEnumeration');
        $profiles = explode(" ", $profiles);
        $result['protocols'] = $profiles;
        $result['servicelocations']['assertionconsumerservice'] = array();
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName == "md:Extensions" OR $child->nodeName == "Extensions")
            {
                $result['extensions'] = $this->ExtensionsToArray($child);
            } elseif ($child->nodeName == "md:NameIDFormat" OR $child->nodeName == "NameIDFormat")
            {
                $result['nameid'][] = $child->nodeValue;
            } elseif ($child->nodeName == "md:AssertionConsumerService" OR $child->nodeName == "AssertionConsumerService")
            {
                $result['servicelocations']['assertionconsumerservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
            } elseif ($child->nodeName == "SingleLogoutService" OR $child->nodeName == "md:SingleLogoutService")
            {
                $result['servicelocations']['singlelogoutservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            } elseif ($child->nodeName == "ManageNameIDService" OR $child->nodeName == "md:ManageNameIDService")
            {
                $result['servicelocations']['managenameidservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            } elseif ($child->nodeName == "KeyDescriptor" OR $child->nodeName == "md:KeyDescriptor")
            {
                $result['certificate'][] = $this->KeyDescriptorConvert($child);
            }
        }

        return $result;
    }

    private function KeyDescriptorConvert($node)
    {
        $cert = array();
        $usecase = $node->getAttribute('use');
        $cert['use'] = $usecase;
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName == "KeyInfo" OR $child->nodeName == "ds:KeyInfo")
            {
                foreach ($child->childNodes as $gchild)
                {
                    if ($gchild->nodeName == "KeyName" OR $gchild->nodeName == "ds:KeyName")
                    {
                        $cert['keyname'] = $gchild->nodeValue;
                    } elseif ($gchild->nodeName == "ds:X509Data" OR $gchild->nodeName == "X509Data")
                    {
                        foreach ($gchild->childNodes as $enode)
                        {
                            if ($enode->nodeName == "ds:X509Certificate" OR $enode->nodeName == "X509Certificate")
                            {
                                if (!empty($enode->nodeValue))
                                {
                                    $cert['x509data']['x509certificate'] = reformatPEM($enode->nodeValue);
                                } else
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

    private function ExtensionsToArray($node)
    {
        foreach ($node->childNodes as $enode)
        {
            if ($enode->nodeName == "shibmd:Scope" OR $enode->nodeName == "Scope")
            {
                $ext['scope'][] = $enode->nodeValue;
            } elseif ($enode->nodeName == "mdui:DiscoHints" && $enode->hasChildNodes())
            {
                foreach ($enode->childNodes as $gnode)
                {
                    $geovalue = array();
                    if ($gnode->nodeName == "mdui:GeolocationHint")
                    {
                        $geovalue = explode(",", str_ireplace("geo:", "", $gnode->nodeValue));
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

    private function OrganizationConvert($node)
    {
        $org = array();
        foreach ($node->childNodes as $child)
        {
            $org['' . str_replace('md:', '', strtolower($child->nodeName)) . ''] = $child->nodeValue;
        }
        return $org;
    }

    private function ContactPersonConvert($node)
    {
        $cnt = array();
        $cnt['type'] = $node->getAttribute('contactType');
        $cnt['surname'] = null;
        $cnt['givenname'] = null;
        $cnt['email'] = null;
        foreach ($node->childNodes as $cnode)
        {
            if ($cnode->nodeName == "SurName" OR $cnode->nodeName == "md:SurName")
            {
                $cnt['surname'] = $cnode->nodeValue;
            }
            if ($cnode->nodeName == "GivenName" OR $cnode->nodeName == "md:GivenName")
            {
                $cnt['givenname'] = $cnode->nodeValue;
            }
            if ($cnode->nodeName == "EmailAddress" OR $cnode->nodeName == "md:EmailAddress")
            {
                $cnt['email'] = $cnode->nodeValue;
            }

//			$cnt[$cnode->nodeName] = $cnode->nodeValue;
        }
        return $cnt;
    }

}
