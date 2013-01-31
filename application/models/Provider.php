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
 * Provider Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Provider Model
 *
 * This model for Identity and Service Providers definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="provider")
 * @author janusz
 */
class Provider {

    protected $em;
    protected $logo_url;

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255, nullable=true, unique=false)
     * it got form OrganizationName
     */
    protected $name;

    /**
     * @Column(type="string", length=255,nullable=true, unique=false)
     * it got from OrganizationDisplayName
     */
    protected $displayname;

    /**
     * @Column(type="string", length=128, nullable=false, unique=true)
     */
    protected $entityid;

    /**
     * @Column(type="array",nullable=true)
     */
    protected $nameidformat;

    /**
     * array of all values from protocolSupportEnumeration in IDP/SP SSODescription
     * @Column(type="array",nullable=true)
     */
    protected $protocol;

    /**
     * type - IDP,SP,BOTH
     * @Column(type="string", length=5, nullable=true)
     */
    protected $type;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $scope;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $homeurl;

    /**
     * helpdeskurl is used in metadata, it can be http(s) or mailto
     * @Column(type="string", length=255, nullable=true)
     */
    protected $helpdeskurl;

    /**
     * privacyurl is used in metadata as mdui:PrivacyStatementURL
     * @Column(type="string", length=255, nullable=true)
     */
    protected $privacyurl;

    /**
     * registrar is used in metadata for registrationAuthority
     * @Column(type="string", length=255, nullable=true)
     */
    protected $registrar;

    /**
     * registerdate is used in metadata for registrationInstant
     * @Column(type="datetime",nullable=true)
     */
    protected $registerdate;

    /**
     * @Column(type="datetime",nullable=true)
     */
    protected $validfrom;

    /**
     * @Column(type="datetime",nullable=true)
     */
    protected $validto;

    /**
     * @Column(type="text",nullable=true)
     */
    protected $description;

    /**
     * @Column(type="string", length=2, nullable=true)
     */
    protected $country;

    /**
     * @Column(type="text",nullable=true)
     */
    protected $wayflist;

    /**
     * not used for the moment and default true
     * @Column(type="boolean")
     */
    protected $is_approved;

    /**
     * @Column(type="boolean")
     */
    protected $is_active;

    /**
     * @Column(type="boolean")
     */
    protected $is_locked;

    /**
     * if set then use static metadata
     *
     * @Column(type="boolean")
     */
    protected $is_static;

    /**
     * if true then it's not external entity
     * @Column(type="boolean")
     */
    protected $is_local;

    /**
     * it can be member of many federations
     * @ManyToMany(targetEntity="Federation", inversedBy="members")
     * @JoinTable(name="federation_members" )
     */
    protected $federations;

    /**
     * it can be member of many federations
     * @OneToMany(targetEntity="Contact", mappedBy="provider", cascade={"persist", "remove"})
     */
    protected $contacts;

    /**
     * it can be member of many federations
     *
     * @OneToMany(targetEntity="Certificate", mappedBy="provider", cascade={"persist", "remove"})
     */
    protected $certificates;

    /**
     * @OneToOne(targetEntity="Provider")
     * @JoinColumn(name="owner_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @OneToMany(targetEntity="ServiceLocation", mappedBy="provider", cascade={"persist", "remove"})
     */
    protected $serviceLocations;

    /**
     * @OneToMany(targetEntity="AttributeReleasePolicy", mappedBy="idp", cascade={"persist", "remove"})
     */
    protected $attributeReleaseIDP;

    /**
     * @OneToMany(targetEntity="AttributeRequirement", mappedBy="sp_id",cascade={"persist", "remove"})
     */
    protected $attributeRequirement;

    /**
     * @OneToOne(targetEntity="StaticMetadata", mappedBy="provider",cascade={"persist", "remove"})
     */
    protected $metadata;

    /**
     * @OneToMany(targetEntity="ExtendMetadata", mappedBy="provider",cascade={"persist", "remove"})
     */
    protected $extend;

    /**
     * @Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function __construct()
    {

        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->certificates = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        $this->protocol = new \Doctrine\Common\Collections\ArrayCollection();
        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->extend = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributeRequirement = new \Doctrine\Common\Collections\ArrayCollection();
        $this->updatedAt = new \DateTime("now");
        $this->is_approved = TRUE;
        $this->is_locked = FALSE;

        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function diffProviderToArray($provider)
    {
        $differ = array();
        if ($provider->getName() != $this->getName())
        {
            $differ['Name']['before'] = $provider->getName();
            $differ['Name']['after'] = $this->getName();
        }
        if ($provider->getDisplayName() != $this->getDisplayName())
        {
            $differ['Displayname']['before'] = $provider->getDisplayName();
            $differ['Displayname']['after'] = $this->getDisplayName();
        }
        if ($provider->getHomeUrl() != $this->getHomeUrl())
        {
            $differ['Home URL']['before'] = $provider->getHomeUrl();
            $differ['Home URL']['after'] = $this->getHomeUrl();
        }
        if ($provider->getHelpdeskUrl() != $this->getHelpdeskUrl())
        {
            $differ['Helpdesk URL']['before'] = $provider->getHelpdeskUrl();
            $differ['Helpdesk URL']['after'] = $this->getHelpdeskUrl();
        }
        if ($provider->getPrivacyUrl() != $this->getPrivacyUrl())
        {
            $differ['Helpdesk URL']['before'] = $provider->getPrivacyUrl();
            $differ['Helpdesk URL']['after'] = $this->getPrivacyUrl();
        }
        if ($provider->getRegistrationAuthority() != $this->getRegistrationAuthority())
        {
            $differ['Registration Authority']['before'] = $provider->getRegistrationAuthority();
            $differ['Registration Authority']['after'] = $this->getRegistrationAuthority();
        }
        if ($provider->getRegistrationDate() != $this->getRegistrationDate())
        {
            $rgbefore = $provider->getRegistrationDate();
            if (!empty($rgbefore))
            {
                $differ['Registration Date']['before'] = $rgbefore->format('Y-m-d');
            } else
            {
                $differ['Registration Date']['before'] = null;
            }
            $rgafter = $this->getRegistrationDate();
            if (!empty($rgafter))
            {
                $rgafter = $this->getRegistrationDate();
                $differ['Registration Date']['after'] = $rgafter->format('Y-m-d');
            } else
            {
                $differ['Registration Date']['after'] = null;
            }
        }

        if ($provider->getEntityId() != $this->getEntityId())
        {
            $differ['EntityID']['before'] = $provider->getEntityId();
            $differ['EntityID']['after'] = $this->getEntityId();
        }
        if ($provider->getScope() != $this->getScope())
        {
            $differ['Scope']['before'] = $provider->getScope();
            $differ['Scope']['after'] = $this->getScope();
        }
        $nameids_before = $provider->getNameIdToArray();
        $nameids_after = $this->getNameIdToArray();
        if ($nameids_before != $nameids_after)
        {
            $differ['nameids']['before'] = implode(', ', $nameids_before);
            $differ['nameids']['after'] = implode(', ', $nameids_after);
        }



        if ($provider->getCountry() != $this->getCountry())
        {
            $differ['Country']['before'] = $provider->getCountry();
            $differ['Country']['after'] = $this->getCountry();
        }

        $tmp_provider_validto = $provider->getValidTo();
        $tmp_this_validto = $this->getValidTo();
        if (!empty($tmp_provider_validto))
        {
            $validto_before = $provider->getValidTo()->format('Y-m-d');
        } else
        {
            $validto_before = '';
        }
        if (!empty($tmp_this_validto))
        {
            $validto_after = $this->getValidTo()->format('Y-m-d');
        } else
        {
            $validto_after = '';
        }

        if ($validto_before != $validto_after)
        {
            $differ['ValidTo']['before'] = $validto_before;
            $differ['ValidTo']['after'] = $validto_after;
        }
        $tmp_provider_validfrom = $provider->getValidFrom();
        $tmp_this_validfrom = $this->getValidFrom();
        if (!empty($tmp_provider_validfrom))
        {
            $validfrom_before = $provider->getValidFrom()->format('Y-m-d');
        } else
        {
            $validfrom_before = '';
        }
        if (!empty($tmp_this_validfrom))
        {
            $validfrom_after = $this->getValidFrom()->format('Y-m-d');
        } else
        {
            $validfrom_after = '';
        }
        if ($validfrom_before != $validfrom_after)
        {
            $differ['ValidFrom']['before'] = $validfrom_before;
            $differ['ValidFrom']['after'] = $validfrom_after;
            ;
        }
        if ($provider->getType() != $this->getType())
        {
            $differ['Type']['before'] = $provider->getType();
            $differ['Type']['after'] = $this->getType();
        }
        if ($provider->getActive() != $this->getActive())
        {
            $differ['Active']['before'] = $provider->getActive();
            $differ['Active']['after'] = $this->getActive();
        }

        return $differ;
    }

    public function __toString()
    {
        return $this->entityid;
    }

    /**
     * @prePersist 
     */
    public function created()
    {
        $this->createdAt = new \DateTime("now");
        if (empty($this->nameidformat))
        {
            $this->setNameId();
        }
        if (empty($this->displayname))
        {
            $this->displayname = $this->getName();
        }
    }

    /**
     * @PostPersist
     */
    public function createAclResource()
    {
       $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $is_local = $this->is_local;
        if ($is_local)
        {
            $rescheck = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $this->id));
            if(!empty($rescheck))
            {
                return true;
            }
            $parent = array();

            $parents = $this->em->getRepository("models\AclResource")->findBy(array('resource' => array('idp', 'sp','entity')));
            foreach ($parents as $p)
            {
                $parent[$p->getResource()] = $p;
            }
            $stype = $this->type;
            if ($stype == 'BOTH')
            {
                $types = array('entity');
            } elseif ($stype == 'IDP')
            {
                $types = array('idp');
            } else
            {
                $types = array('sp');
            }
            foreach ($types as $key)
            {
                $r = new AclResource;
                $resource_name = $this->id;
                $r->setResource($resource_name);
                $r->setDefaultValue('view');
                $r->setType('entity');
                if (array_key_exists($key, $parent))
                {
                    $r->setParent($parent[$key]);
                }
                $this->em->persist($r);
            }
            $this->em->flush();
        }
        log_message('debug', 'entity:' . $this->id . ' ::' . $this->type);
    }

    /**
     * @preRemove 
     */
    public function unsetOwner()
    {
        
    }

    /**
     * @PostRemove
     */
    public function removeRequester()
    {
        log_message('debug', 'Provider removed, not its time to remove all entries with that requester');
    }

    /**
     * @PreUpdate
     */
    public function updated()
    {
        $this->updatedAt = new \DateTime("now");
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setDisplayName($name)
    {
        $this->displayname = $name;
        return $this;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    public function overwriteScope($provider)
    {
        $this->scope = $provider->getScope();
        return $this;
    }

    public function setEntityId($entity)
    {
        if (!empty($entity))
        {
            $this->entityid = $entity;
            return $this;
        } else
        {
            return false;
        }
    }

    public function setCountry($country = null)
    {
        if (!empty($country))
        {
            $this->country = $country;
        }
    }

    public function resetNameId()
    {
        $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        return $this;
    }

    public function setNameId($nameid = NULL)
    {
        if (empty($nameid))
        {
            $nameid = "urn:oasis:names:tc:SAML:2.0:nameid-format:transient";
        }
        //$this->nameidformat = $nameid;
        if (empty($this->nameidformat))
        {
            $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->nameidformat->add($nameid);
        return $this;
    }

    public function resetProtocol()
    {
        $this->protocol = new \Doctrine\Common\Collections\ArrayCollection();
        return $this;
    }

    public function setProtocol($protocol = NULL)
    {
        if (empty($protocol))
        {
            $protocol = "urn:oasis:names:tc:SAML:2.0:protocol";
        }
        if (empty($this->protocol))
        {
            $this->protocol = new \Doctrine\Common\Collections\ArrayCollection();
        }
        //$this->getProtocol()->add($protocol);
        $this->protocol->add($protocol);
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * setting entity as SP 
     */
    public function setSP()
    {
        $this->type = 'SP';
        return $this;
    }

    public function setAsSP()
    {
        $this->type = 'SP';
        return $this;
    }

    /**
     * setting entity as IDP
     */
    public function setIDP()
    {
        $this->type = 'IDP';
        return $this;
    }

    public function setAsIDP()
    {
        $this->type = 'IDP';
        return $this;
    }

    public function setAsBoth()
    {
        $this->type = 'BOTH';
        return $this;
    }

    public function setHelpdeskUrl($url)
    {
        $this->helpdeskurl = $url;
        return $this;
    }

    /**
     * set homeurl
     */
    public function setHomeUrl($url)
    {
        $this->homeurl = $url;
        return $this;
    }

    public function setPrivacyUrl($url = null)
    {
        $this->privacyurl = $url;
    }

    public function setRegistrationAuthority($reg)
    {
        $this->registrar = $reg;
        return $this;
    }

    public function setRegistrationDate($date = null)
    {
        $this->registerdate = $date;
        return $this;
    }

    /**
     * set time entity is valid to, if null then current time
     */
    public function setValidTo($date = NULL)
    {
        if (empty($date))
        {
            $this->validto = NULL;
        } else
        {
            // $date->setTime(23, 59, 59);
            $this->validto = $date;
        }
        return $this;
    }

    public function setValidFrom($date = NULL)
    {
        if (empty($date))
        {
            $this->validfrom = NULL;
        } else
        {
            //$date->setTime(00, 01, 00);
            $this->validfrom = $date;
        }
        return $this;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function setWayfList($wayflist = null)
    {
       if(!empty($wayflist) && is_array($wayflist))
       {
          $this->wayflist = serialize($wayflist);
       }
    }

    public function setDefaultState()
    {
        $this->is_approved = 1;
        $this->is_active = 1;
        $this->is_locked = 0;
        $this->is_static = 0;
        $this->is_local = 1;
        $this->setValidFrom();
        $this->setValidTo();
        return $this;
    }

    public function setLocal($is_local)
    {
        if ($is_local)
        {
            $this->is_local = true;
        } else
        {
            $this->is_local = false;
        }
        return $this;
    }

    public function setAsLocal()
    {
        $this->is_local = 1;
    }

    public function setAsExternal()
    {
        $this->is_local = 0;
    }

    public function setActive($val = NULL)
    {
        if (!empty($val))
        {
            $this->is_active = 1;
        } else
        {
            $this->is_active = 0;
        }
        return $this;
    }

    public function Disactivate()
    {
        $this->is_active = 0;
    }

    public function Activate()
    {
        $this->is_active = 1;
    }

    public function Lock()
    {
        $this->is_locked = 1;
    }

    public function Unlock()
    {
        $this->is_locked = 0;
    }

    public function setApproved($val = NULL)
    {
        if (!empty($val))
        {
            $this->is_approved = 1;
        } else
        {
            $this->is_approved = 0;
        }
        return $this;
    }

    public function setFederation(Federation $federation)
    {
        $already_there = $this->getFederations()->contains($federation);
        if (empty($already_there))
        {
            $this->getFederations()->add($federation);
        }
        return $this->federations;
    }

    public function removeFederation(Federation $federation)
    {
        $this->getFederations()->removeElement($federation);
        $federation->getMembers()->removeElement($this);
        return $this->federations;
    }

    public function setServiceLocation(ServiceLocation $service)
    {
        $this->getServiceLocations()->add($service);
        $service->setProvider($this);
        return $this->serviceLocations;
    }

    public function setExtendMetadata(ExtendMetadata $ext)
    {
        $this->getExtendMetadata()->add($ext);
        $ext->setProvider($this);
        return $this->extend; 
     
    }

    public function removeServiceLocation(ServiceLocation $service)
    {
        $this->getServiceLocations()->removeElement($service);
        $service->setProvider(null);
        return $this->serviceLocations;
    }

    public function setStatic($static)
    {
        if ($static === true)
        {
            $this->is_static = true;
        } else
        {
            $this->is_static = false;
        }
        return $this;
    }

    public function setStaticMetadata(StaticMetadata $metadata)
    {
        $this->metadata = $metadata;
        $metadata->setProvider($this);

        return $this;
    }

    public function overwriteStaticMetadata(StaticMetadata $metadata = null)
    {
        $m = $this->getStaticMetadata();
        if (!empty($m))
        {
            $m->setMetadata($metadata->getMetadata());
        } else
        {
            $this->setStaticMetadata($metadata);
        }
        return $this;
    }

    public function setAttributesRequirement(AttributeRequirement $attribute)
    {
        $this->getAttributesRequirement()->add($attribute);
        return $this;
    }

    public function setContact(Contact $contact)
    {
        $this->getContacts()->add($contact);
        $contact->setProvider($this);
        return $this->contacts;
    }

    public function removeCertificate(Certificate $certificate)
    {
        $this->getCertificates()->removeElement($certificate);
        $certificate->unsetProvider($this);
        return $this->certificates;
    }

    public function removeContact(Contact $contact)
    {
        $this->getContacts()->removeElement($contact);
        $contact->setProvider(null);
        return $this->contacts;
    }

    public function removeAllContacts()
    {
        $contacts = $this->getContacts();
        foreach ($contacts->getValues() as $contact)
        {
            $contacts->removeElement($contact);
            $contact->setProvider(null);
        }
        return $this;
    }

    public function setCertificate(Certificate $certificate)
    {
        $this->getCertificates()->add($certificate);
        $certificate->setProvider($this);
        return $this->certificates;
    }

    /**
     * this object state will be overwriten by $provider object
     */
    public function overwriteByProvider(Provider $provider)
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;



        $this->setName($provider->getName());
        $this->setDisplayName($provider->getDisplayName());
        $this->overwriteScope($provider);
        $this->setEntityId($provider->getEntityId());
        $this->setRegistrationAuthority($provider->getRegistrationAuthority());
        $this->setRegistrationDate($provider->getRegistrationDate());

        $this->overwriteWithNameid($provider);

        $this->resetProtocol();
        foreach ($provider->getProtocol()->getValues() as $p)
        {
            $this->setProtocol($p);
        }
        $this->setType($provider->getType());
        $this->setHelpdeskUrl($provider->getHelpdeskUrl());
        $homeurl = $provider->getHomeUrl();
        if (empty($homeurl))
        {
            $homeurl = $provider->getHelpdeskUrl();
        }
        $this->setHomeUrl($homeurl);
        $this->setValidFrom($provider->getValidFrom());
        $this->setValidTo($provider->getValidTo());
        $this->setDescription($provider->getDescription());
        $smetadata = $provider->getStaticMetadata();
        if (!empty($smetadata))
        {
            $this->overwriteStaticMetadata($smetadata);
        }
        /**
         * overwrite servicelocations
         */
        //echo $c1 = $this->getServiceLocations()->count();
        //echo $c2 = $provider->getServiceLocations()->count();
        //$c3 = $c1->$c2;
        //if($c3>0)
        //{
        foreach ($this->getServiceLocations()->getValues() as $s)
        {
            $this->removeServiceLocation($s);
        }
        foreach ($provider->getServiceLocations()->getValues() as $r)
        {
            $this->setServiceLocation($r);
            if (!$r->getOrder())
            {
                $r->setOrder(1);
            }
        }
        foreach ($this->getCertificates()->getValues() as $c)
        {
            $this->removeCertificate($c);
        }
        foreach ($provider->getCertificates()->getValues() as $r)
        {
            $this->setCertificate($r);
        }
        foreach ($this->getExtendMetadata()->getValues() as $f)
        {
            if(!empty($f))
            {
               $this->removeExtendWithChildren($f);
            }
        }
        foreach($provider->getExtendMetadata()->getValues() as $gg)
        {
            $this->setExtendMetadata($gg);
        }
        return $this;
    }

    private function removeExtendWithChildren($e)
    {  
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        
        $children = $e->getChildren();
        if (!empty($children) && $children->count() > 0)
        {
            
            foreach ($children->getValues() as $c)
            {
               
                $this->removeExtendWithChildren($c);
            }
            
        }
        $this->getExtendMetadata()->removeElement($e);
        $this->em->remove($e);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRegistrationAuthority()
    {
        return $this->registrar;
    }

    public function getRegistrationDate()
    {
        return $this->registerdate;
    }

    /**
     * get collection of contacts which are used in metada
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    public function getCertificates()
    {
        return $this->certificates;
    }

    public function getNameIdToArray()
    {
        return $this->getNameId()->toArray();
    }

    public function getNameId()
    {
        return $this->nameidformat;
    }

    public function getActive()
    {
        return $this->is_active;
    }

    public function getProtocol()
    {
        $col = new \Doctrine\Common\Collections\ArrayCollection();
        $tmp = $this->protocol;
        if (!empty($tmp))
        {
            return $this->protocol;
        } else
        {
            return $col;
        }
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getScopeToArray()
    {
        $scopes = explode(",", $this->scope);
        return $scopes;
    }

    public function getAttributeReleasePolicies()
    {
        return $this->attributeReleaseIDP;
    }

    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }

    public function getAttributesRequirement()
    {
        return $this->attributeRequirement;
    }

    public function getAttributesRequirementToArray_V1()
    {
        $result = array();
        $req = $this->getAttributesRequirement();
        foreach ($req as $r)
        {
            $result[$r->getAttribute()->getName()] = $r->getStatus();
        }
        return $result;
    }

    public function getFederations()
    {
        return $this->federations;
    }

    public function getFederationNames()
    {
        $feders = array();
        foreach ($this->federations as $entry)
        {
            $feder[] = $entry;
        }
        return $feder;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDisplayName($length = null)
    {
        if (empty($length) or !is_integer($length) or strlen($this->displayname) <= $length)
        {
            return $this->displayname;
        } else
        {
            return substr($this->displayname, 0, $length) . "...";
        }
    }

    public function findOneSPbyName($name)
    {
        return $this->_em->createQuery('SELECT u FROM Models\Provider u WHERE name = "' . $name . '"')->getResult();
    }

    public function getValidTo()
    {
        return $this->validto;
    }

    public function getValidFrom()
    {
        return $this->validfrom;
    }

    /**
     * return boolean if entity is between validfrom and validto dates
     */
    public function getIsValidFromTo()
    {
        /**
         * @todo fix broken time for the momemnt reurns true
         */
        $currentTime = new \DateTime("now");
        $validAfter = TRUE;
        $validBefore = TRUE;
        if (!empty($this->validfrom))
        {

            $timeFrom = $this->validfrom;
            if ($currentTime < $timeFrom)
            {
                $validBefore = FALSE;
            }
        }
        if (!empty($this->validto))
        {
            $timeTo = $this->validto;
            if ($currentTime > $timeTo)
            {
                $validAfter = FALSE;
            }
        }

        $result = $validAfter && $validBefore;
        return $result;
    }

    public function getEntityId()
    {
        return $this->entityid;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCountry()
    {
        return $this->country;
    }

    /*
     * return boolean if want to use static metadata
     */

    public function getStatic()
    {
        return $this->is_static;
    }

    /*
     * return static metadata body
     */

    public function getStaticMetadata()
    {
        return $this->metadata;
    }

    public function getExtendMetadata()
    {
        return $this->extend;
    }

    public function getIsStaticMetadata()
    {
        $c = $this->getStatic();
        $d = $this->getStaticMetadata();
        if ($c && !empty($d))
        {
            return true;
        } else
        {
            return false;
        }
    }

    public function getHomeUrl()
    {
        return $this->homeurl;
    }

    public function getHelpdeskUrl()
    {
        return $this->helpdeskurl;
    }

    public function getPrivacyUrl()
    {
        return $this->privacyurl;
    }

    public function getApproved()
    {
        return $this->is_approved;
    }

    public function getLocked()
    {
        return $this->is_locked;
    }

    public function getAvailable()
    {

        return ($this->is_active && $this->is_approved && $this->getIsValidFromTo());
    }

    public function getLocal()
    {
        return $this->is_local;
    }

    public function getDescription()
    {
        return $this->description;
    }
    public function getWayfList()
    {
         $w = $this->wayflist;
         if(!empty($w))
         {
             return  unserialize($w);
         }
         else
         {
            return null;
         }

    }

    public function getSupportedAttributes()
    {
        if ($this->type == 'IDP')
        {
            $finalArp = new \Doctrine\Common\Collections\ArrayCollection();

            $arp = $this->attributeReleaseIDP;
            foreach ($arp as $a)
            {
                if ($a->getIsDefault())
                {
                    $finalArp->add($a);
                }
            }
        } else
        {
            return false;
        }
        return $finalArp;
    }

    public function getLastModified()
    {
        if (empty($this->updatedAt))
        {
            return $this->createdAt;
        } else
        {
            return $this->updatedAt;
        }
    }

    public function replaceContactCollection(Provider $provider)
    {
        $existingContacts = $this->getContacts();
        $no_existingContacts = count($existing_Contacts);
        $newContacts = $provider->getContacts();
        $no_newContacts = count($newContacts);
    }

    public function overwriteWithNameid(Provider $provider)
    {
        $this->nameidformat = $provider->getNameId();
    }

    public function convertToArray()
    {
        $r = array();
        $r['id'] = $this->getId();
        $r['name'] = $this->getName();
        $r['displayname'] = $this->getDisplayname();
        $r['entityid'] = $this->getEntityid();

        $r['nameid'] = array();
        $nameids = $this->getNameid()->getValues();
        if (!empty($nameids))
        {
            $r['nameid'] = $nameids;
        }
        $r['protocol'] = array();
        $protocols = $this->getProtocol()->getValues();
        if (!empty($protocols))
        {
            $r['protocol'] = $protocols;
        }
        $r['type'] = $this->getType();
        $r['scope'] = $this->getScope();
        $r['homeurl'] = $this->getHomeUrl();
        $r['helpdeskurl'] = $this->getHelpdeskUrl();
        $r['privacyurl'] = $this->getPrivacyUrl();
        $r['validfrom'] = $this->getValidFrom();
        $r['validto'] = $this->getValidTo();
        $r['description'] = $this->getDescription();
        $r['is_approved'] = $this->getApproved();
        $r['is_active'] = $this->getActive();
        $r['is_locked'] = $this->getLocked();
        $r['is_static'] = $this->getStatic();
        $r['is_local'] = $this->getLocal();
        $r['contacts'] = array();
        $contacts = $this->getContacts();
        if (!empty($contacts))
        {
            foreach ($contacts->getValues() as $c)
            {
                $r['contacts'][] = $c->convertToArray();
            }
        }

        $r['certificates'] = array();
        $certs = $this->getCertificates();
        if (!empty($certs))
        {
            foreach ($certs->getValues() as $crt)
            {
                $r['certificates'][] = $crt->convertToArray();
            }
        }
        $services = $this->getServiceLocations();
        $r['services'] = array();
        if (!empty($services))
        {
            foreach ($services->getValues() as $s)
            {
                $r['services'][] = $s->convertToArray();
            }
        }

        $r['federations'] = array();
        $feds = $this->getFederations();
        if (!empty($feds))
        {
            foreach ($feds->getValues() as $f)
            {
                $r['federations'][] = $f->convertToArray();
            }
        }

        return $r;
    }

    public function importFromArray(array $r)
    {
        $this->setName($r['name']);
        if (!empty($r['displayname']))
        {
            $this->setDisplayname($r['displayname']);
        } else
        {
            $this->setDisplayname($r['name']);
        }
        $this->setEntityid($r['entityid']);
        if (is_array($r['nameid']) && count($r['nameid'] > 0))
        {
            foreach ($r['nameid'] as $n)
            {
                $this->setNameid($n);
            }
        }

        if (is_array($r['protocol']) && count($r['protocol']) > 0)
        {
            foreach ($r['protocol'] as $p)
            {
                $this->setProtocol($p);
            }
        }

        // $this->setProtocol($r['protocol']);
        $this->setType($r['type']);
        $this->setScope($r['scope']);
        $this->setHomeUrl($r['homeurl']);
        $this->setHelpdeskUrl($r['helpdeskurl']);
        $this->setPrivacyUrl($r['privacyurl']);
        $this->setValidFrom($r['validfrom']);
        $this->setValidTo($r['validto']);
        $this->setDescription($r['description']);
        $this->setApproved($r['is_approved']);
        $this->setActive($r['is_active']);
        //$this->setLocked($r['is_locked']);
        $this->setStatic($r['is_static']);
        $this->setLocal($r['is_local']);
        if (count($r['contacts']) > 0)
        {
            foreach ($r['contacts'] as $v)
            {
                $c = new Contact;
                $c->importFromArray($v);
                $this->setContact($c);
                $c->setProvider($this);
            }
        }
        if (count($r['certificates']) > 0)
        {
            foreach ($r['certificates'] as $v)
            {
                if (is_array($v))
                {
                    $c = new Certificate;
                    $c->importFromArray($v);
                    $this->setCertificate($c);
                    $c->setProvider($this);
                }
            }
        }
        if (count($r['services']) > 0)
        {
            foreach ($r['services'] as $v)
            {
                $c = new ServiceLocation;
                $c->importFromArray($v);
                $this->setServiceLocation($c);
                $c->setProvider($this);
            }
        }
        if (count($r['federations']) > 0)
        {
            foreach ($r['federations'] as $f)
            {
                $c = new Federation;
                $c->importFromArray($f);
                $this->setFederation($c);
                $c->addMember($this);
            }
        }
    }

    public function getOrganizationToXML(\DOMElement $parent)
    {
        $ns_md = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $e = $parent->ownerDocument->createElementNS($ns_md, 'md:Organization');
        $OrganizationName_Node = $e->ownerDocument->createElementNS($ns_md, 'md:OrganizationName', htmlspecialchars($this->getName()));
        $OrganizationName_Node->setAttribute('xml:lang', 'en');
        $OrganizationDisplayName_Node = $e->ownerDocument->createElementNS($ns_md, 'md:OrganizationDisplayName', htmlspecialchars($this->getDisplayName()));
        $OrganizationDisplayName_Node->setAttribute('xml:lang', 'en');
        /**
         * @todo for the moment using also Name as DisplayName 
         */
        //$OrganizationDisplayName_Node->appendChild($OrganizationName_Node_Text);

        $OrganizationURL_Node = $e->ownerDocument->createElementNS($ns_md, 'md:OrganizationURL', htmlspecialchars($this->getHelpdeskUrl()));
        $OrganizationURL_Node->setAttribute('xml:lang', 'en');

        $e->appendChild($OrganizationName_Node);
        $e->appendChild($OrganizationDisplayName_Node);
        $e->appendChild($OrganizationURL_Node);
        return $e;
    }

    public function getIDPSSODescriptorToXML(\DOMElement $parent, $options = null)
    {
        $this->ci = & get_instance();
        $this->ci->load->helper('url');

        $this->logo_basepath = $this->ci->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->ci->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;

        $ns_md = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $e = $parent->ownerDocument->createElementNS($ns_md, 'md:IDPSSODescriptor');
        $protocol = $this->getProtocol()->getValues();
        if (!empty($protocol))
        {
            $protocols = implode(" ", $protocol);
        } else
        {
            $protocols = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        $e->setAttribute('protocolSupportEnumeration', $protocols);
        $Extensions_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');

        /* UIInfo */
        $UIInfo_Node = $Extensions_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:UIInfo');
        $d_element = 'DisplayName';
        $d_value = htmlspecialchars($this->getDisplayName());
        $d_node = $UIInfo_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $d_element . '', $d_value);
        $d_node->setAttribute('xml:lang', 'en');
        $UIInfo_Node->appendChild($d_node);

        $d_element = 'Description';
        $d_value = trim($this->getDescription());
        if (empty($d_value))
        {
            $d_value = "description not provided";
        }
        $d_node = $UIInfo_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $d_element . '', htmlspecialchars($d_value));
        $d_node->setAttribute('xml:lang', 'en');
        $UIInfo_Node->appendChild($d_node);

        $d_element = 'PrivacyStatementURL';
        $d_value = $this->getPrivacyUrl();
        if (!empty($d_value))
        {
            $d_node = $UIInfo_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $d_element . '', $d_value);
            $d_node->setAttribute('xml:lang', 'en');
            $UIInfo_Node->appendChild($d_node);
        }


        $scs = $this->getScopeToArray();
        if (!empty($scs) and is_array($scs))
        {
            foreach ($scs as $sc)
            {
                $Scope_Node = $Extensions_Node->ownerDocument->createElementNS('urn:mace:shibboleth:metadata:1.0', 'shibmd:Scope', trim($sc));
                $Scope_Node->setAttribute('regexp', 'false');
                $Extensions_Node->appendChild($Scope_Node);
            }
        }
        $e->appendChild($Extensions_Node);

        $other_extends = $this->getExtendMetadata();
        $count_extends = count($other_extends);
        $oextends = array();
        if ($count_extends > 0)
        {
            foreach ($other_extends as $o)
            {
                $otype = $o->getType();
                $oparent = $o->getParent();
                if ($otype == 'idp' && empty($oparent))
                {
                    $oextends[] = $o;
                }
            }
        }
        if (count($oextends) > 0)
        {
            foreach ($oextends as $ex)
            {
                $namespace = $ex->getNamespace();
                if ($namespace == 'mdui')
                {
                    $element_name = $ex->getElement();
                    if ($element_name == 'UIInfo')
                    {
                        $root_extends = &$UIInfo_Node;
                    } else
                    {
                        $root_extends = $Extensions_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $ex->getElement() . '');
                    }


                    $children_extends = $ex->getChildren();
                    $is_extend_displayname = FALSE;
                    $is_extend_description = FALSE;
                    foreach ($children_extends as $child)
                    {
                        $Element = $child->getElement();
                        $ElementValue = $child->getElementValue();
                        if ($Element == 'Logo')
                        {
                            if (!(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $child->getElementValue(), $matches)))
                            {
                                $ElementValue = $this->logo_url . $child->getElementValue();
                            } else
                            {
                                $ElementValue = $child->getElementValue();
                            }
                        }
                        $ch_node = $root_extends->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $Element . '', $ElementValue);
                        $attrs = $child->getAttributes();
                        if (!empty($attrs))
                        {
                            foreach ($child->getAttributes() as $akey => $avalue)
                            {
                                $ch_node->setAttribute($akey, $avalue);
                            }
                        }
                        $root_extends->appendChild($ch_node);
                    }
                    $m_element = $ex->getElement();
                    if ($root_extends->hasChildNodes())
                    {
                        $Extensions_Node->appendChild($root_extends);
                    }
                }
            }
        }

        $Extensions_Node->appendChild($UIInfo_Node);

        $certs = $this->getCertificates();
        log_message('debug', gettype($certs));
        if (!empty($certs))
        {
            $ncerts = $certs->count();
        } else
        {
            $ncerts = 0;
        }
        log_message('debug', "Provider model: number of local certs is " . $ncerts);
        if ($ncerts == 0)
        {
            log_message('debug', "Provider model: no local certificates may cause problems");
            return NULL;
        }

        $tmp_certs = array();
        if ($ncerts > 0)
        {
            foreach ($certs->getValues() as $cert)
            {
                log_message('debug', 'generating crt to xml');
                $type = $cert->getType();
                if ($type == 'sso')
                {
                    $certusage = $cert->getCertUse();
                    if (empty($certusage))
                    {
                        log_message('debug', 'cert - all');
                        $tmp_certs['all'][] = $cert;
                    } else
                    {
                        log_message('debug', 'cert - ' . $certusage);
                        $tmp_certs[$certusage] = $cert;
                    }
                    log_message('debug', 'generating crt-sso to xml');
                    $KeyDescriptor_Node = $cert->getCertificateToXML($e);
                    $e->appendChild($KeyDescriptor_Node);
                }
            }
        }
        /**
         * @todo finish for rollover
         */
        /**
          if(array_key_exists('all', $tmp_certs) && count($tmp_certs) == 1)
          {
          if(count($tmp_certs['all']) == 1)
          {
          $KeyDescriptor_Node = $cert->getCertificateToXML($e);
          $e->appendChild($KeyDescriptor_Node);
          }
          else
          {

          }
          }
         */
        $nameid_collection = $this->getNameId();

        log_message('debug', 'nameid type:' . get_class($nameid_collection));
        $nameid = $nameid_collection->getValues();
        if (!empty($nameid))
        {
            foreach ($nameid as $key)
            {

                $NameIDFormat_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:NameIDFormat', $key);
                $e->appendChild($NameIDFormat_Node);
            }
        }

        $services = $this->getServiceLocations();
        if (empty($services))
        {
            return null;
        }
        foreach ($services as $srv)
        {
            $ServiceLocation_Node = $srv->getServiceLocationToXML($e,$options);
            /**
             * @todo check if index or default can be added
             */
            $e->appendChild($ServiceLocation_Node);
        }

        return $e;
    }

    public function getSPSSODescriptorToXML(\DOMElement $parent,$options = null)
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('url');

        $this->logo_basepath = $this->ci->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->ci->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;

        $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SPSSODescriptor');
        $protocol = $this->getProtocol()->getValues();
        if (!empty($protocol))
        {
            $protocols = "";
            /**
             * @todo verify if built correctly
             */
            foreach ($protocol as $key)
            {
                $protocols .= $key . " ";
            }
            $protocols = \trim($protocols);
        } else
        {
            $protocols = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        $e->setAttribute('protocolSupportEnumeration', $protocols);

        $Extensions_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
        $e->appendChild($Extensions_Node);
        /* UIInfo */
        $UIInfo_Node = $Extensions_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:UIInfo');
        $d_element = 'DisplayName';
        $d_value = htmlspecialchars($this->getDisplayName());
        $d_node = $UIInfo_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $d_element . '', $d_value);
        $d_node->setAttribute('xml:lang', 'en');
        $UIInfo_Node->appendChild($d_node);

        $d_element = 'Description';
        $d_value = htmlspecialchars(trim($this->getDescription()));
        if (empty($d_value))
        {
            $d_value = "description not provided";
        }
        $d_node = $UIInfo_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $d_element . '', $d_value);
        $d_node->setAttribute('xml:lang', 'en');
        $UIInfo_Node->appendChild($d_node);

        $d_element = 'PrivacyStatementURL';
        $d_value = $this->getPrivacyUrl();
        if (!empty($d_value))
        {
            $d_node = $UIInfo_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $d_element . '', $d_value);
            $d_node->setAttribute('xml:lang', 'en');
            $UIInfo_Node->appendChild($d_node);
        }
        $Extensions_Node->appendChild($UIInfo_Node);


        $other_extends = $this->getExtendMetadata();
        $count_extends = count($other_extends);
        $oextends = array();
        if ($count_extends > 0)
        {
            foreach ($other_extends as $o)
            {
                $otype = $o->getType();
                $oparent = $o->getParent();
                if ($otype == 'sp' && empty($oparent))
                {
                    $oextends[] = $o;
                }
            }
        }
        if (count($oextends) > 0)
        {
            foreach ($oextends as $ex)
            {
                $namespace = $ex->getNamespace();
                if ($namespace == 'mdui')
                {
                    $element_name = $ex->getElement();
                    if ($element_name == 'UIInfo')
                    {
                        $root_extends = &$UIInfo_Node;
                    } else
                    {
                        $root_extends = $Extensions_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $ex->getElement() . '');
                    }
                    $children_extends = $ex->getChildren();
                    $is_extend_displayname = FALSE;
                    $is_extend_description = FALSE;
                    foreach ($children_extends as $child)
                    {
                        $Element = $child->getElement();
                        $ElementValue = $child->getElementValue();

                        if ($Element == 'Logo')
                        {
                            //$ElementValue = $this->logo_url . $child->getElementValue();
                            if (!(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $child->getElementValue(), $matches)))
                            {
                                $ElementValue = $this->logo_url . $child->getElementValue();
                            } else
                            {
                                if ($Element == 'DisplayName')
                                {
                                    $is_extend_displayname = TRUE;
                                } elseif ($Element == 'Description')
                                {
                                    $is_extend_description = TRUE;
                                }
                                $ElementValue = $child->getElementValue();
                            }
                        } else
                        {
                            $ElementValue = $child->getElementValue();
                        }

                        $ch_node = $root_extends->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:' . $Element . '', $ElementValue);
                        $attrs = $child->getAttributes();
                        if (!empty($attrs))
                        {
                            foreach ($child->getAttributes() as $akey => $avalue)
                            {
                                $ch_node->setAttribute($akey, $avalue);
                            }
                        }
                        $root_extends->appendChild($ch_node);
                    }
                    $m_element = $ex->getElement();



                    if ($root_extends->hasChildNodes())
                    {
                        $Extensions_Node->appendChild($root_extends);
                    }
                }
            }
        }
        $Extensions_Node->appendChild($UIInfo_Node);


        /**
         * @todo check if certificates as rtquired fo SP 
         */
        $certs = $this->getCertificates()->getValues();

        foreach ($certs as $cert)
        {

            $KeyDescriptor_Node = $cert->getCertificateToXML($e);
            $e->appendChild($KeyDescriptor_Node);
        }

        $nameid = $this->getNameId()->getValues();
        if (!empty($nameid))
        {
            foreach ($nameid as $key)
            {

                $NameIDFormat_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:NameIDFormat', $key);
                $e->appendChild($NameIDFormat_Node);
            }
        }
        $services = $this->getServiceLocations()->getValues();
        foreach ($services as $srv)
        {

            $ServiceLocation_Node = $srv->getServiceLocationToXML($e);
            /**
             * @todo check if index or default can be added
             */
            $e->appendChild($ServiceLocation_Node);
        }
        if(!empty($options) and is_array($options) and array_key_exists('attrs',$options) and !empty($options['attrs']))
        {
           $sp_reqattrs = $this->getAttributesRequirement();
           $sp_reqattrs_count = $sp_reqattrs->count();
           if($sp_reqattrs_count > 0)
           {
               foreach($sp_reqattrs->getValues() as $v)
               {
                   $in = $v->getAttribute()->showInMetadata();
                   if($in === FALSE)
                   {
                       
                       $sp_reqattrs->removeElement($v);
                   }
               }
           }
           $sp_reqattrs_count = $sp_reqattrs->count();
           if($sp_reqattrs_count > 0)
           {
               $Attrconsumingservice_Node =  $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AttributeConsumingService');
               $Attrconsumingservice_Node->setAttribute('index','0');
               $e->appendChild($Attrconsumingservice_Node);
               $t_name = $this->getName();
               if(empty($t_name))
               {
                   $t_name = $this->getEntityId();
               }
               $srvname_node = $Attrconsumingservice_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceName',$t_name);
               $srvname_node->setAttribute('xml:lang','en');
               $Attrconsumingservice_Node->appendChild($srvname_node);
               $t_displayname = $this->getDisplayName();
               if(!empty($t_displayname))
               {
                   $srvdisplay_node = $Attrconsumingservice_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceDescription',$t_displayname);
                   $srvdisplay_node->setAttribute('xml:lang','en');
                   $Attrconsumingservice_Node->appendChild($srvdisplay_node);
               }
               foreach($sp_reqattrs->getValues() as $v)
               {
                  $attr_node = $Attrconsumingservice_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata','md:RequestedAttribute');         
                  $attr_node->setAttribute('FriendlyName',$v->getAttribute()->getName());
                  $attr_node->setAttribute('Name',$v->getAttribute()->getOid());
                  $attr_node->setAttribute('NameFormat','urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                  if($v->getStatus() == 'required')
                  {
                      $attr_node->setAttribute('isRequired','true');
                  }
                  else
                  {
                      $attr_node->setAttribute('isRequired','false');
                  }
                  $Attrconsumingservice_Node->appendChild($attr_node);
                  
               }
           
           }
           else
           {
               
               \log_message('debug','OKO: '.$this->getEntityId());
               if(array_key_exists('fedreqattrs',$options) && is_array($options['fedreqattrs']))
               {
                  $Attrconsumingservice_Node =  $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AttributeConsumingService');
                  $Attrconsumingservice_Node->setAttribute('index','0');
                  $e->appendChild($Attrconsumingservice_Node);
                  $t_name = $this->getName();
                  if(empty($t_name))
                  {
                     $t_name = $this->getEntityId();
                  }
                  $srvname_node = $Attrconsumingservice_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceName',$t_name);
                  $srvname_node->setAttribute('xml:lang','en');
                  $Attrconsumingservice_Node->appendChild($srvname_node);
                  $t_displayname = $this->getDisplayName();
                  if(!empty($t_displayname))
                  {
                     $srvdisplay_node = $Attrconsumingservice_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceDescription',$t_displayname);
                     $srvdisplay_node->setAttribute('xml:lang','en');
                     $Attrconsumingservice_Node->appendChild($srvdisplay_node);
                  }
                  foreach($options['fedreqattrs'] as $v)
                  {
                        \log_message('debug','OKO: '.$v->getAttribute()->getName());
                        $attr_node = $Attrconsumingservice_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata','md:RequestedAttribute');         
                        $attr_node->setAttribute('FriendlyName',$v->getAttribute()->getName());
                        $attr_node->setAttribute('Name',$v->getAttribute()->getOid());
                        $attr_node->setAttribute('NameFormat','urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                        if($v->getStatus() == 'required')
                        {
                           $attr_node->setAttribute('isRequired','true');
                        }
                        else
                        {
                           $attr_node->setAttribute('isRequired','false');
                        }
                        $Attrconsumingservice_Node->appendChild($attr_node);
                   }
               }
           }
     
            
        }
        

        return $e;
    }

    /**
     * $conditions as array with keys :
     *  attr_inc - add required attributes into sp
     *  only_allowed - return if entity is active/valid etc
     * @todo add attr requirements in sp if required
     * @param type $conditions
     */
    public function getProviderToXML(\DOMElement $parent = NULL, $options = NULL)
    {
        log_message('debug', "Provider model: start generating xml for " . $this->getEntityId());
        $comment = "\"" . $this->getEntityId() . "\" \n";
        $l = 1;
        /*
          foreach($this->federations as $f)
          {
          $comment .= $l++ .") ". $f->getName()."\n";
          }
         */
        /**
         * defauls values
         */
        $attrs_in_sp = FALSE;
        $only_allowed = FALSE;
        /**
         * condition when XML may be returned
         */
        if (!empty($conditions) && is_array($conditions) && count($conditions) > 0)
        {

            if (array_key_exists('attr_inc', $conditions))
            {
                $attrs_in_sp = $conditions['attr_inc'];
            }
            if (array_key_exists('only_allowed', $conditions))
            {
                $only_allowed = $conditions['only_allowed'];
            }
        }

        /**
         * do not return if active required and entity disabled
         */
        $p_active = $this->getAvailable();

        if ($only_allowed)
        {

            if (empty($p_active))
            {
                log_message('debug', "Provider model: not active - null returned");
                return \NULL;
            }
        }


        $p_entityID = $this->getEntityId();
        $p_static = $this->getStatic();
        $s_metadata = null;
        $valid_until = null;
        $p_validUntil = $this->getValidTo();
        if (!empty($p_validUntil))
        {
            $valid_until = $p_validUntil->format('Y-m-d');
            $valid_until = $valid_until . "T00:00:00Z";
        }



        if ($p_static)
        {
            $static_meta = $this->getStaticMetadata();
            if (empty($static_meta))
            {
                log_message('error', 'Static metadata is enabled but doesnt exist for entity (id: ' . $this->getId() . '):' . $this->getEntityId());
                return null;
            } else
            {
                $s_metadata = $this->getStaticMetadata()->getMetadata();
                $comment .= "static meta\n";
            }
        }
        if ($parent === NULL)
        {
            $docXML = new \DOMDocument();
            $xpath = new \DomXPath($docXML);
            $namespaces = h_metadataNamespaces();
            foreach ($namespaces as $namekey => $namevalue)
            {
                $xpath->registerNamespace($namekey, $namevalue);
            }
            $c = $docXML->createComment(str_replace('--', '-' . chr(194) . chr(173) . '-', $comment));
            $docXML->appendChild($c);
            /**
             * trying to get static 
             */
            if (!empty($s_metadata))
            {
                $node = $this->getStaticMetadata()->getMetadataToXML();
                if (!empty($node))
                {
                    $node = $docXML->importNode($node, true);
                    $docXML->appendChild($node);
                }
                return $docXML;
            }

            $EntityDesc_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntityDescriptor');
            if ($valid_until)
            {
                $EntityDesc_Node->setAttribute('validUntil', $valid_until);
            }
            $docXML->appendChild($EntityDesc_Node);
        } else
        {
            $c = $parent->ownerDocument->createComment(str_replace('--', '-' . chr(194) . chr(173) . '-', $comment));
            $parent->appendChild($c);
            if (!empty($s_metadata))
            {
                $node = $this->getStaticMetadata()->getMetadataToXML();
                if (!empty($node))
                {
                    $node = $parent->ownerDocument->importNode($node, true);
                    $parent->appendChild($node);
                    return $node;
                } else
                {
                    log_message('error', 'Provider model: ' . $this->entityid . ' static metadata active but is empty - null returned');
                    return null;
                }
            }
            $EntityDesc_Node = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntityDescriptor');
            if ($valid_until)
            {
                $EntityDesc_Node->setAttribute('validUntil', $valid_until);
            }
        }


        $EntityDesc_Node->setAttribute('entityID', $this->getEntityId());
        $ci = & get_instance();
        $configRegistrar = $ci->config->item('registrationAutority');
        $configRegistrarLoad = $ci->config->item('load_registrationAutority');
        if (!empty($this->registrar))
        {
            $EntExtension_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
            $EntityDesc_Node->appendChild($EntExtension_Node);
            $RegistrationInfo_Node = $EntExtension_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:RegistrationInfo');
            $RegistrationInfo_Node->setAttribute('registrationAuthority', htmlspecialchars($this->registrar));
            if (!empty($this->registerdate))
            {
                $RegistrationInfo_Node->setAttribute('registrationInstant', $this->registerdate->format('Y-m-d') . 'T' . $this->registerdate->format('H:i:s') . 'Z');
            }
            $EntExtension_Node->appendChild($RegistrationInfo_Node);
        } elseif (!empty($configRegistrarLoad) && !empty($configRegistrar) && $this->is_local === TRUE)
        {
            $EntExtension_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
            $EntityDesc_Node->appendChild($EntExtension_Node);
            $RegistrationInfo_Node = $EntExtension_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:RegistrationInfo');
            $RegistrationInfo_Node->setAttribute('registrationAuthority', $configRegistrar);
            if (!empty($this->registerdate))
            {
                $RegistrationInfo_Node->setAttribute('registrationInstant', $this->registerdate->format('Y-m-d') . 'T' . $this->registerdate->format('H:i:s') . 'Z');
            }
            $EntExtension_Node->appendChild($RegistrationInfo_Node);
        }

        $type = $this->getType();


        switch ($type):
            case "IDP":
                $SSODesc_Node = $this->getIDPSSODescriptorToXML($EntityDesc_Node);
                if (!empty($SSODesc_Node))
                {
                    $EntityDesc_Node->appendChild($SSODesc_Node);
                } else
                {
                    \log_message('error', "Provider model: IDP type but IDPSSODescriptor is null. Metadata for " . $this->getEntityId() . " couldnt be generated");
                    return null;
                }
                break;
            case "SP":
                $SSODesc_Node = $this->getSPSSODescriptorToXML($EntityDesc_Node,$options);
                if (!empty($SSODesc_Node))
                {
                    $EntityDesc_Node->appendChild($SSODesc_Node);
                } else
                {
                    log_message('error', "Provider model: SP type but SPSSODescriptor is null - null returned");
                    return null;
                }
                break;
            case "BOTH":
                $SSODesc_Node = $this->getIDPSSODescriptorToXML($EntityDesc_Node);
                if (empty($SSODesc_Node))
                {
                    log_message('error', "Provider model: BOTH type but IDPSSODescriptor is null - null returned");
                    return null;
                }
                $EntityDesc_Node->appendChild($SSODesc_Node);
                $SSODesc_Node = $this->getSPSSODescriptorToXML($EntityDesc_Node,$options);
                if (empty($SSODesc_Node))
                {
                    log_message('error', "Provider model: BOTH type but SPSSODescriptor is null - null returned");
                    return null;
                }
                $EntityDesc_Node->appendChild($SSODesc_Node);
        endswitch;


        $Organization_Node = $this->getOrganizationToXML($EntityDesc_Node);
        $EntityDesc_Node->appendChild($Organization_Node);

        $contact_coll = $this->getContacts();
        $contact_count = $contact_coll->count();
        //log_message('debug','no of contacts: '.$contact_count);
        $con = $contact_coll->getValues();
        for ($i = 0; $i < $contact_count; $i++)
        {
            $Contact_Node = $contact_coll->get($i)->getContactToXML($EntityDesc_Node);
            $EntityDesc_Node->appendChild($Contact_Node);
        }

        if ($parent === NULL)
        {
            return $docXML;
        } else
        {
            $parent->appendChild($EntityDesc_Node);
            return $EntityDesc_Node;
        }
    }

    /**
     *
     * extensions inside DPSSODEscriptor or IDPSSODescriptor 
     */
    private function SSODescriptorExtensionsFromArray($ext, $type = null)
    {
        if (array_key_exists('scope', $ext))
        {
            $scopeString = implode(",", $ext['scope']);
            $this->setScope($scopeString);
        }
        if (array_key_exists('geo', $ext) && is_array($ext['geo']))
        {
            $parentgeo = new ExtendMetadata;
            $parentgeo->setNamespace('mdui');
            $parentgeo->setElement('DiscoHints');
            $parentgeo->setAttributes(array());
            if (!empty($type))
            {
                $parentgeo->setType($type);
            }
            $parentgeo->setProvider($this);
            $this->setExtendMetadata($parentgeo);
            foreach ($ext['geo'] as $g)
            {
                $geo = new ExtendMetadata;
                $geo->setGeoLocation('' . $g[0] . ',' . $g[1] . '', $this, $parentgeo, $type);
                $this->setExtendMetadata($geo);
            }
        }
    }

    private function IDPSSODescriptorFromArray($b)
    {
        if (array_key_exists('extensions', $b))
        {
            $this->SSODescriptorExtensionsFromArray($b['extensions'], 'idp');
        }

        if (array_key_exists('nameid', $b))
        {
            foreach ($b['nameid'] as $n)
            {
                $this->setNameid($n);
            }
        }
        if (array_key_exists('servicelocations', $b))
        {
            foreach ($b['servicelocations']['singlesignonservice'] as $s)
            {
                $sso = new ServiceLocation;
                $sso->setType('SingleSignOnService');
                $sso->setBindingName($s['binding']);
                $sso->setUrl($s['location']);
                if (!empty($s['order']))
                {
                    $sso->setOrder($s['order']);
                }
                if (!empty($s['isdefault']))
                {
                    $sso->setDefault(true);
                }
                $sso->setProvider($this);
                $this->setServiceLocation($sso);
            }
        }
        if (array_key_exists('protocols', $b))
        {
            foreach ($b['protocols'] as $p)
            {
                $this->setProtocol($p);
            }
        }
        if (array_key_exists('certificate', $b) && count($b['certificate']) > 0)
        {

            foreach ($b['certificate'] as $c)
            {
                $cert = new Certificate();
                if (array_key_exists('x509data', $c))
                {
                    $cert->setCertType('x509');
                    if (array_key_exists('x509certificate', $c['x509data']))
                    {
                        $cert->setCertdata($c['x509data']['x509certificate']);
                    }
                }

                $cert->setType('sso');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname']))
                {
                    $cert->setKeyname($c['keyname']);
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }
        return $this;
    }

    private function SPSSODescriptorFromArray($b)
    {
        if (array_key_exists('extensions', $b))
        {
            $this->SSODescriptorExtensionsFromArray($b['extensions'], 'sp');
        }
        if (array_key_exists('nameid', $b))
        {
            foreach ($b['nameid'] as $n)
            {
                $this->setNameid($n);
            }
        }
        if (array_key_exists('protocols', $b))
        {
            foreach ($b['protocols'] as $p)
            {
                $this->setProtocol($p);
            }
        }
        if (array_key_exists('servicelocations', $b))
        {

            foreach ($b['servicelocations']['assertionconsumerservice'] as $s)
            {
                $sso = new ServiceLocation;
                $sso->setType('AssertionConsumerService');
                $sso->setBindingName($s['binding']);
                $sso->setUrl($s['location']);
                if (isset($s['order']))
                {
                    $sso->setOrder($s['order']);
                }
                if (!empty($s['isdefault']))
                {
                    $sso->setDefault(true);
                }
                $sso->setProvider($this);
                $this->setServiceLocation($sso);
            }
        }
        if (array_key_exists('certificate', $b) && count($b['certificate']) > 0)
        {

            foreach ($b['certificate'] as $c)
            {
                $cert = new Certificate();
                if (array_key_exists('x509data', $c))
                {
                    $cert->setCertType('x509');
                    if (array_key_exists('x509certificate', $c['x509data']))
                    {
                        $cert->setCertdata($c['x509data']['x509certificate']);
                    }
                }
                $cert->setType('sso');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname']))
                {
                    $cert->setKeyname($c['keyname']);
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }
        return $this;
    }

    public function setProviderFromArray($a)
    {
        if (!is_array($a))
        {
            return null;
        }
        $this->setType($a['type']);
        $this->setEntityId($a['entityid']);
        if (!empty($a['validuntil']))
        {
            $p = explode("T", $a['validuntil']);
            $this->setValidTo(\DateTime::createFromFormat('Y-m-d', $p[0]));
        }
        if (!empty($a['registrar']))
        {
            $this->setRegistrationAuthority($a['registrar']);
            if (!empty($a['regdate']))
            {
                $p = explode("T", $a['regdate']);
                $ptime = str_replace('Z', '', $p['1']);
                $this->setRegistrationDate(\DateTime::createFromFormat('Y-m-d H:i:s', $p[0].' '.$ptime));
            }
        }
        if (!empty($a['metadata']))
        {
            $m = new StaticMetadata;
            $m->setMetadata($a['metadata']);
            $this->setStaticMetadata($m);
        }
        if (array_key_exists('details', $a))
        {
            $this->setName($a['details']['organization']['organizationname']);
            $this->setDisplayName($a['details']['organization']['organizationdisplayname']);
            $this->setHelpdeskUrl($a['details']['organization']['organizationurl']);
            foreach ($a['details']['regpolicy'] as $rp)
            {
                /**
                 * extend regpolicy 
                 */
            }

            foreach ($a['details']['contacts'] as $c)
            {
                $tc = new Contact;
                $tc->setType($c['type']);
                $tc->setEmail($c['email']);
                $tc->setSurName($c['surname']);
                $tc->setGivenName($c['givenname']);
                $tc->setProvider($this);
                $this->setContact($tc);
            }
            if ($a['type'] == "IDP")
            {
                if (array_key_exists('idpssodescriptor', $a['details']))
                {

                    $this->IDPSSODescriptorFromArray($a['details']['idpssodescriptor']);
                }
            } elseif ($a['type'] == "SP")
            {
                if (array_key_exists('spssodescriptor', $a['details']))
                {
                    $this->SPSSODescriptorFromArray($a['details']['spssodescriptor']);
                }
            } elseif ($a['type'] == "BOTH")
            {
                if (array_key_exists('idpssodescriptor', $a['details']))
                {

                    $this->IDPSSODescriptorFromArray($a['details']['idpssodescriptor']);
                }
                if (array_key_exists('spssodescriptor', $a['details']))
                {
                    $this->SPSSODescriptorFromArray($a['details']['spssodescriptor']);
                }
            }
        }
        return $this;
    }

}
