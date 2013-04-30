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
    private $coclist;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->i = 0;
        $this->occurance = array();
        $this->metaArray = array();
        $this->coclist = array();
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
        if(count($this->coclist)> 0)
        {
           $redusedlist = $this->coclist;
           foreach($redusedlist as $r)
           {
               $existing = $this->em->getRepository("models\Coc")->findOneBy(array('url'=>$r));
               if(empty($existing))
               {
                   $nconduct = new models\Coc;
                   $nconduct->setUrl($r);
                   $nconduct->setName($r);
                   $nconduct->setDescription($r);
                   $nconduct->setAvailable(FALSE);
                   $this->em->persist($nconduct);
               }
           }
           $this->em->flush();
        }
        return $this->metaArray;
    }

    function entitiesConvert($doc, $full = false)
    {
        if ($doc instanceof DOMElement)
        {
            if ($doc->nodeName === "md:EntityDescriptor" OR $doc->nodeName === "EntityDescriptor")
            {
                $this->entityConvert($doc, $full);
            }
            elseif ($doc->nodeName == "EntitiesDescriptor" OR $doc->nodeName == "md:EntitiesDescriptor")
            {
                foreach ($doc->childNodes as $child)
                {
                    $this->entitiesConvert($child, $full);
                }
            }
            else
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
        $entity['coc'] = null;
        $entity['validuntil'] = $node->getAttribute('validUntil');
        $is_idp = false;
        $is_sp = false;
        $entity['details']['org'] = array('OrganizationName'=>array(), 'OrganizationDisplayName'=>array(), 'OrganizationURL'=>array());
        $entity['details']['contacts'] = array();
        $entity['details']['regpolicy'] = array();
        foreach ($node->childNodes as $gnode)
        {
            if ($gnode->nodeName === 'md:IDPSSODescriptor' OR $gnode->nodeName === 'IDPSSODescriptor')
            {
                $is_idp = true;
                $entity['type'] = 'IDP';
                if (!empty($full))
                {

                    $entity['details']['idpssodescriptor'] = $this->IDPSSODescriptorConvert($gnode);
                }
            }
            if ($gnode->nodeName === 'SPSSODescriptor' OR $gnode->nodeName === 'md:SPSSODescriptor')
            {
                $is_sp = true;
                $entity['type'] = 'SP';
                if (!empty($full))
                {

                    $entity['details']['spssodescriptor'] = $this->SPSSODescriptorConvert($gnode);
                }
            }
            if ($gnode->nodeName === 'md:AttributeAuthorityDescriptor' OR $gnode->nodeName === 'AttributeAuthorityDescriptor')
            {
                $entity['details']['aadescriptor'] = $this->AADescriptorConvert($gnode);
            }
            if ($gnode->nodeName === 'Extensions' OR $gnode->nodeName === 'md:Extensions')
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
                                        $chlang = $ch->getAttribute('xml:lang');
                                        $chvalue = $ch->nodeValue;
                                        if (!empty($chlang) && !empty($chvalue))
                                        {
                                            $entity['details']['regpolicy'][''.$chlang.''] =  $chvalue;
                                        }
                                    }
                                }
                            }
                        }
                        elseif($enode->nodeName === 'mdattr:EntityAttributes' && $enode->hasChildNodes())
                        {
                            foreach( $enode->getElementsByTagNameNS( 'urn:oasis:names:tc:SAML:2.0:assertion','Attribute') as $enode2)
                            {
                                if($enode2->hasAttributes() && $enode2->getAttribute('Name') === 'http://macedir.org/entity-category' && $enode2->hasChildNodes())
                                {
                                      foreach($enode2->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'AttributeValue') as $enode3)
                                      {
                                          $entity['coc'] = $enode3->nodeValue;
                                          $this->coclist[] = $enode3->nodeValue;
                                      }

                                }
                            }
                        }
                    }
                }
                //      $entity['details']['extensions'] = $this->ExtensionsToArray($gnode);
            }
            elseif ($gnode->nodeName === 'md:ContactPerson' OR $gnode->nodeName === 'ContactPerson' )
            {
                $entity['details']['contacts'][] = $this->ContactPersonConvert($gnode);
            }
            elseif ($gnode->nodeName === 'md:Organization' OR $gnode->nodeName === 'Organization')
            {
                $entity['details']['org'] = $this->OrganizationConvert($gnode);
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

    private function AADescriptorConvert(\DOMElement $node)
    {
       $result = array();
       $result['protocols'] = array_filter(explode(' ',$node->getAttribute('protocolSupportEnumeration')),'strlen');
       foreach ($node->childNodes as $child)
       {
          if ($child->nodeName === 'md:Extensions' OR $child->nodeName === 'Extensions' ) 
          {
             $result['extensions'] = $this->AAExtensionsToArray($child);
          }
           elseif ($child->nodeName === 'md:NameIDFormat' OR $child->nodeName === 'NameIDFormat' )
          {
             $result['nameid'][] = $child->nodeValue;
          }
          elseif ($child->nodeName === 'AttributeService' OR $child->nodeName === 'md:AttributeService')
          {
             $result['attributeservice'][] = array('binding'=>$child->getAttribute('Binding'),'location'=>$child->getAttribute('Location'));
          }
          elseif ($child->nodeName == "KeyDescriptor" OR $child->nodeName == "md:KeyDescriptor")
          {
              $result['certificate'][] = $this->KeyDescriptorConvert($child);
          }
       }
       return $result;
       
    }

    private function IDPSSODescriptorConvert(\DOMElement $node)
    {
        $result = array();
        $result['protocols'] = array_filter(explode(' ',$node->getAttribute('protocolSupportEnumeration')),'strlen');
        //log_message('debug','GGGG profiles '.serialize($result['protocols']));
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName === 'md:Extensions' OR $child->nodeName === 'Extensions' )
            {
                $result['extensions'] = $this->ExtensionsToArray($child);
            }
            elseif ($child->nodeName === 'md:NameIDFormat' OR $child->nodeName === 'NameIDFormat' )
            {
                $result['nameid'][] = $child->nodeValue;
            }
            elseif ($child->nodeName === 'SingleSignOnService' OR $child->nodeName === 'md:SingleSignOnService')
            {
                $result['servicelocations']['singlesignonservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
            elseif ($child->nodeName === 'md:SingleLogoutService' OR $child->nodeName === 'SingleLogoutService')
            {
                $result['servicelocations']['singlelogout'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
                 
            }
            elseif ($child->nodeName === 'md:ArtifactResolutionService' OR $child->nodeName === 'ArtifactResolutionService')
            {
                $result['servicelocations']['artifactresolutionservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
                
            }
            elseif ($child->nodeName == "KeyDescriptor" OR $child->nodeName == "md:KeyDescriptor")
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
        $result['servicelocations'] = array('assertionconsumerservice'=> array(), 'singlelogout'=>array());
        $result['extensions']['idpdisc'] = array();
        $result['extensions']['init'] = array();
        $result['extensions']['desc'] = array();
      
        foreach ($node->childNodes as $child)
        {
            if ($child->nodeName === 'md:Extensions' OR $child->nodeName === 'Extensions')
            {
                $result['extensions'] = $this->ExtensionsToArray($child);
            }
            elseif ($child->nodeName === 'md:NameIDFormat' OR $child->nodeName === 'NameIDFormat')
            {
                $result['nameid'][] = $child->nodeValue;
            }
            elseif ($child->nodeName === 'md:AssertionConsumerService' OR $child->nodeName === 'AssertionConsumerService')
            {
                $result['servicelocations']['assertionconsumerservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
            }
            elseif ($child->nodeName === 'md:ArtifactResolutionService' OR $child->nodeName === 'ArtifactResolutionService')
            {
                $result['servicelocations']['artifactresolutionservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
            }
            elseif ($child->nodeName === 'md:SingleLogoutService' OR $child->nodeName === 'SingleLogoutService')
            {
                $result['servicelocations']['singlelogout'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
            elseif ($child->nodeName === 'md:ManageNameIDService' OR $child->nodeName === 'ManageNameIDService')
            {
                $result['servicelocations']['managenameidservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
            }
          
            elseif ($child->nodeName === 'KeyDescriptor' OR $child->nodeName === 'md:KeyDescriptor')
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
                        $cert['keyname'][] = $gchild->nodeValue;
                    }
                    elseif ($gchild->nodeName == "ds:X509Data" OR $gchild->nodeName == "X509Data")
                    {
                        foreach ($gchild->childNodes as $enode)
                        {
                            if ($enode->nodeName == "ds:X509Certificate" OR $enode->nodeName == "X509Certificate")
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

    private function AAExtensionsToArray($node)
    {
       $result = array();
       foreach ($node->childNodes as $enode)
       {
           if ($enode->nodeName === 'shibmd:Scope' OR $enode->nodeName === 'Scope' OR $enode->nodeName === 'saml1md:Scope')
           {
                $result['aascope'][] = $enode->nodeValue;

           }

       }      
       return $result;

    }
    private function ExtensionsToArray($node)
    {
        foreach ($node->childNodes as $enode)
        {
            if ($enode->nodeName === 'shibmd:Scope' OR $enode->nodeName === 'Scope' OR $enode->nodeName === 'saml1md:Scope')
            {
                $ext['scope'][] = $enode->nodeValue;
            }
            elseif($enode->nodeName == 'idpdisc:DiscoveryResponse' OR $enode->nodeName == 'DiscoveryResponse')
            {
                $ext['idpdisc'][] = array('binding'=>$enode->getAttribute('Binding'),'url'=>$enode->getAttribute('Location'),'order'=>$enode->getAttribute('index'));
            }
            elseif($enode->nodeName == 'init:RequestInitiator' OR $enode->nodeName == 'RequestInitiator')
            {
                $ext['init'][] = array('binding'=>$enode->getAttribute('Binding'),'url'=>$enode->getAttribute('Location'));
            }
            elseif ($enode->nodeName == 'mdui:UIInfo' && $enode->hasChildNodes())
            {
                foreach($enode->childNodes as $gnode)
                {
                    /**
                     * @todo finish  
                     */
                    if($gnode->nodeName == 'mdui:Description' OR $gnode->nodeName == 'Description')
                    {
                       $ext['desc'][] = array('lang'=>$gnode->getAttribute('xml:lang'),'val'=>$gnode->nodeValue);
                    }
                    elseif($gnode->nodeName == 'mdui:DisplayName' OR $gnode->nodeName == 'DisplayName')
                    {
                       $ext['displayname'][] = array('lang'=>$gnode->getAttribute('xml:lang'),'val'=>$gnode->nodeValue);
                    }
                    elseif($gnode->nodeName == 'mdui:PrivacyStatementURL' OR $gnode->nodeName == 'PrivacyStatementURL')
                    {
                       $ext['privacyurl'][] = array('lang'=>$gnode->getAttribute('xml:lang'),'val'=>$gnode->nodeValue);
                    }
                    elseif($gnode->nodeName == 'mdui:InformationURL' OR $gnode->nodeName == 'InformationURL')
                    {
                       $ext['informationurl'][] = array('lang'=>$gnode->getAttribute('xml:lang'),'val'=>$gnode->nodeValue);
                    }
                    elseif($gnode->nodeName === 'mdui:Logo')
                    {
                       $logoval = $gnode->nodeValue;
                       if(substr( $logoval, 0, 4 ) === "http")
                       {
                         $ext['logo'][] = array('height'=>$gnode->getAttribute('height'),'width'=>$gnode->getAttribute('width'),'xml:lang'=>$gnode->getAttribute('xml:lang'),'val'=>$logoval);
                       }
                    }
                }
                
            }
            elseif($enode->nodeName === 'mdui:DiscoHints' && $enode->hasChildNodes())
            {
                log_message('debug','GK : DiscoHints found');
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

    private function OrganizationConvert($node)
    {
        $org = array('OrganizationName'=>array(), 'OrganizationDisplayName'=>array(), 'OrganizationURL'=>array());
        if($node->hasChildNodes())
        {
           foreach ($node->childNodes as $child)
           {
               log_message('debug', 'GGGG chiold: ' .$child->nodeName);
                if(! $child instanceOf DOMText)
                {
                      $org[''.str_replace('md:', '', $child->nodeName).''][''.$child->getAttribute('xml:lang').''] = trim($child->nodeValue);
                }
           }
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
