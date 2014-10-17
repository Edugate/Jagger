<?php

namespace models;

use \Doctrine\Common\Collections\ArrayCollection;
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
 * StaticMetadata Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * StaticMetadata Model
 *
 * This model for Identity and Service Providers definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="provider_metadata")
 * @author janusz
 */
class StaticMetadata
{

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * metadata encoded with base64_encode 
     * @Column(type="text")
     */
    protected $metadata;

    /**
     * @OneToOne(targetEntity="Provider",inversedBy="metadata")
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     */
    protected $provider;

    public function setMetadata($metadata)
    {
        $this->metadata = base64_encode($metadata);
        return $this;
    }

    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
        return $this;
    }

	/**
	 * $addNS add NS definitions before and after
	 */
    public function getMetadata($addNS=null)
    {
        $mresult = base64_decode($this->metadata);
        
$mresult = preg_replace('/\<\?xml(.*)\>/', '', $mresult);

		if($addNS)
		{
			$top = '<EntitiesDescriptor 
                                xmlns="urn:oasis:names:tc:SAML:2.0:metadata"
				xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" 
			 xmlns:ds="http://www.w3.org/2000/09/xmldsig#" 
                         xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
			 xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" 
			 xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" 
                         xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" 
			 xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi" 
			 xmlns:idpdisc="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" 
			 xmlns:wayf="http://sdss.ac.uk/2006/06/WAYF" 
			 xmlns:elab="http://eduserv.org.uk/labels" 
			 xmlns:ukfedlabel="http://ukfederation.org.uk/2006/11/label" 
			 xmlns:init="urn:oasis:names:tc:SAML:profiles:SSO:request-init" 
			 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                         xmlns:xi="http://www.w3.org/2001/XInclude"
			 ID="static" 
			 Name="static"> ';
		 	$down = '</EntitiesDescriptor>';
			$mresult = $top . $mresult .$down;

		}
		return $mresult;
    }


	public function getMetadataToDecoded()
	{
		$result = $this->getMetadata();
		return htmlspecialchars_decode($result);
	}

    public function getProvider()
    {
        return $this->provider;
    }

    public function getMetadataToXML()
    {
		error_reporting(0);

                $staticMetadata = new \DOMDocument;
        	$xpath = new \DomXPath($staticMetadata);
       		$xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');    
        	$xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        	$xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        	$xpath->registerNamespace('shibmd', 'urn:mace:shibboleth:metadata:1.0');
       		$xpath->registerNamespace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');    
       		$xpath->registerNamespace('mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');    
       		$xpath->registerNamespace('mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');    
       		$xpath->registerNamespace('idpdisc', 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol');    
                $xpath->registerNamespace('wayf', 'http://sdss.ac.uk/2006/06/WAYF');
                $xpath->registerNamespace('elab', 'http://eduserv.org.uk/labels');
                $xpath->registerNamespace('ukfedlabel', 'http://ukfederation.org.uk/2006/11/label');
       		$xpath->registerNamespace('init', 'urn:oasis:names:tc:SAML:profiles:SSO:request-init');    
        	$xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $xpath->registerNamespace('xi','http://www.w3.org/2001/XInclude');
		$add = true;
		if( $staticMetadata->loadXML($this->getMetadata($add)))
		{
        	$node = $staticMetadata->getElementsByTagName("EntityDescriptor")->item(0);
		}
		else
		{
			$node= false;
		}
        
        return $node;
    }

}
