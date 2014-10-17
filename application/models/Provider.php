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
 * @Table(name="provider",indexes={@Index(name="type_idx", columns={"type"}),@Index(name="pname_idx", columns={"name"}),@Index(name="islocal_idx", columns={"is_local"})})
 * @author janusz
 */
class Provider
{

    protected $em;
    protected $logo_url;
    protected $ci;
    protected $federations;

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255, nullable=true, unique=false)
     * it got from OrganizationName
     */
    protected $name;

    /**
     * @Column(type="text", nullable=true, unique=false)
     * it got from OrganizationName localized, serialized
     */
    protected $lname;

    /**
     * @Column(type="string", length=255,nullable=true, unique=false)
     * it got from OrganizationDisplayName
     */
    protected $displayname;

    /**
     * @Column(type="text",nullable=true, unique=false)
     * it got from OrganizationDisplayName localized
     */
    protected $ldisplayname;

    /**
     * @Column(type="string", length=128, nullable=false, unique=true)
     */
    protected $entityid;

    /**
     * obsolete - will be removed in the future
     * @Column(type="array",nullable=true)
     */
    protected $nameidformat;

    /**
     * new - replacing nameidformat
     * @Column(type="text",nullable=true)
     */
    protected $nameids;

    /**
     * obsolete
     * array of all values from protocolSupportEnumeration in IDP/SP SSODescription
     * @Column(type="array",nullable=true)
     */
    protected $protocol;

    /**
     * new of all values from protocolSupportEnumeration in idpsso,spsso,aa 
     * @Column(type="text",nullable=true)
     */
    protected $protocolsupport;

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
     * @Column(type="string",length=10, nullable=true)
     */
    protected $digest;

    /**
     * helpdeskurl is used in metadata, it can be http(s) or mailto
     * @Column(type="string", length=255, nullable=true)
     */
    protected $helpdeskurl;

    /**
     * licalized lhelpdeskurl is used in metadata, it can be http(s) or mailto
     * @Column(type="text", nullable=true)
     */
    protected $lhelpdeskurl;

    /**
     * privacyurl is used in metadata as mdui:PrivacyStatementURL
     * @Column(type="string", length=255, nullable=true)
     */
    protected $privacyurl;

    /**
     * lprivacyurl is used in metadata as mdui:PrivacyStatementURL - localized
     * @Column(type="text", nullable=true)
     */
    protected $lprivacyurl;

    /**
     * @ManyToMany(targetEntity="Coc",inversedBy="provider", cascade={"persist","detach"})
     * @JoinTable(name="Provider_Coc",
     *      joinColumns={@JoinColumn(name="provider_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="coc_id", referencedColumnName="id")}
     *      )
     */
    protected $coc;

    /**
     * @OneToMany(targetEntity="ProviderStatsDef",mappedBy="provider",cascade={"persist","remove"})
     */
    protected $statsdef;

    /**
     * @OneToMany(targetEntity="ProviderStatsCollection",mappedBy="provider",cascade={"persist","remove"})
     */
    protected $statistic;

    /**
     * registrar is used in metadata for registrationAuthority in mdrpi:RegistrationInfo
     * @Column(type="string", length=255, nullable=true)
     */
    protected $registrar;

    /**
     * registerdate is used in metadata for registrationInstant
     * @Column(type="datetime",nullable=true)
     */
    protected $registerdate;

    /**
     * regpolicy is used in metadata for RegistrationPolicy
     * DEPRECATED - to be removed in v 2.x
     * @Column(type="text",nullable=true)
     */
    protected $regpolicy;

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
     * serialized array containing entities to be escluded from ARP
     * @Column(type="text",nullable=true)
     */
    protected $excarps;

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
     * if true then it is not listed on public lists
     * @Column(type="boolean")
     */
    protected $hidepublic;


    /**
     * it can be member of many federations
     * ManyToMany(targetEntity="Federation", inversedBy="members")
     * JoinTable(name="federation_members" )
     */
    //protected $federations;

    /**
     * @OneToMany(targetEntity="FederationMembers", mappedBy="provider", cascade={"persist", "remove","detach"})
     */
    protected $membership;

    /**
     * @OneToMany(targetEntity="NotificationList", mappedBy="provider", cascade={"persist", "remove"})
     */
    protected $notifications;

    /**
     * @OneToMany(targetEntity="Contact", mappedBy="provider", cascade={"all"})
     */
    protected $contacts;

    /**
     * it can be member of many federations
     *
     * @OneToMany(targetEntity="Certificate", mappedBy="provider", cascade={"all"})
     */
    protected $certificates;

    /**
     * @OneToOne(targetEntity="Provider")
     * @JoinColumn(name="owner_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @OneToMany(targetEntity="ServiceLocation", mappedBy="provider", cascade={"all"})
     */
    protected $serviceLocations;

    /**
     * @OneToMany(targetEntity="AttributeReleasePolicy", mappedBy="idp", cascade={"persist", "remove"})
     */
    protected $attributeReleaseIDP;

    /**
     * @OneToMany(targetEntity="AttributeRequirement", mappedBy="sp_id",cascade={"all"})
     */
    protected $attributeRequirement;

    /**
     * @OneToOne(targetEntity="StaticMetadata", mappedBy="provider", fetch="EXTRA_LAZY", cascade={"all"})
     */
    protected $metadata;

    /**
     * @OneToMany(targetEntity="ExtendMetadata", mappedBy="provider",cascade={"all"})
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
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->certificates = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        $this->protocol = new \Doctrine\Common\Collections\ArrayCollection();
        $this->membership = new \Doctrine\Common\Collections\ArrayCollection();
        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->extend = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributeRequirement = new \Doctrine\Common\Collections\ArrayCollection();
        $this->coc = new \Doctrine\Common\Collections\ArrayCollection();
        $this->updatedAt = new \DateTime("now", new \DateTimeZone('UTC'));
        $this->is_approved = TRUE;
        $this->hidepublic = FALSE;
        $this->is_locked = FALSE;
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function diffProviderToArray(Provider $provider)
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
        if ($provider->getHelpdeskUrl() != $this->getHelpdeskUrl())
        {
            $differ['Helpdesk URL']['before'] = $provider->getHelpdeskUrl();
            $differ['Helpdesk URL']['after'] = $this->getHelpdeskUrl();
        }
        if ($provider->getPrivacyUrl() != $this->getPrivacyUrl())
        {
            $differ['PrivacyStatement URL']['before'] = $provider->getPrivacyUrl();
            $differ['ProvideStatement URL']['after'] = $this->getPrivacyUrl();
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
            }
            else
            {
                $differ['Registration Date']['before'] = null;
            }
            $rgafter = $this->getRegistrationDate();
            if (!empty($rgafter))
            {
                $rgafter = $this->getRegistrationDate();
                $differ['Registration Date']['after'] = $rgafter->format('Y-m-d');
            }
            else
            {
                $differ['Registration Date']['after'] = null;
            }
        }

        if ($provider->getEntityId() != $this->getEntityId())
        {
            $differ['EntityID']['before'] = $provider->getEntityId();
            $differ['EntityID']['after'] = $this->getEntityId();
        }
        if (serialize($provider->getScope('idpsso')) != serialize($this->getScope('idpsso')))
        {
            $differ['Scope']['before'] = implode(',', $provider->getScope('idpsso'));
            $differ['Scope']['after'] = implode(',', $this->getScope('idpsso'));
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
        }
        else
        {
            $validto_before = '';
        }
        if (!empty($tmp_this_validto))
        {
            $validto_after = $this->getValidTo()->format('Y-m-d');
        }
        else
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
        }
        else
        {
            $validfrom_before = '';
        }
        if (!empty($tmp_this_validfrom))
        {
            $validfrom_after = $this->getValidFrom()->format('Y-m-d');
        }
        else
        {
            $validfrom_after = '';
        }
        if ($validfrom_before != $validfrom_after)
        {
            $differ['ValidFrom']['before'] = $validfrom_before;
            $differ['ValidFrom']['after'] = $validfrom_after;
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
        if ($provider->getLocked() != $this->getLocked())
        {
            $differ['Locked']['before'] = $provider->getLocked();
            $differ['Locked']['after'] = $this->getLocked();
        }
        /**
         *  compare localized names
         */
        $ldisplayname_before = $provider->getLocalDisplayName();
        $ldisplayname_after = $this->getLocalDisplayName();

        $ldisplayname_diff1 = array_diff_assoc($ldisplayname_before, $ldisplayname_after);
        $ldisplayname_diff2 = array_diff_assoc($ldisplayname_after, $ldisplayname_before);
        if (count($ldisplayname_diff1) > 0 || count($ldisplayname_diff2) > 0)
        {
            $tmpstr = '';
            foreach ($ldisplayname_diff1 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['DisplayNameLocalized']['before'] = $tmpstr;
            $tmpstr = '';
            foreach ($ldisplayname_diff2 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['DisplayNameLocalized']['after'] = $tmpstr;
        }







        $lname_before = $provider->getLocalName();
        if ($lname_before == NULL)
        {
            $lname_before = array();
        }
        $lname_after = $this->getLocalName();
        if ($lname_after == NULL)
        {
            $lname_after = array();
        }
        $lname_diff1 = array_diff_assoc($lname_before, $lname_after);
        $lname_diff2 = array_diff_assoc($lname_after, $lname_before);
        if (count($lname_diff1) > 0 || count($lname_diff2) > 0)
        {
            $tmpstr = '';
            foreach ($lname_diff1 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['NameLocalized']['before'] = $tmpstr;
            $tmpstr = '';
            foreach ($lname_diff2 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['NameLocalized']['after'] = $tmpstr;
        }




        $lname_before = $provider->getLocalHelpdeskUrl();
        if ($lname_before == NULL)
        {
            $lname_before = array();
        }
        $lname_after = $this->getLocalHelpdeskUrl();
        if ($lname_after == NULL)
        {
            $lname_after = array();
        }
        $lname_diff1 = array_diff_assoc($lname_before, $lname_after);
        $lname_diff2 = array_diff_assoc($lname_after, $lname_before);
        if (count($lname_diff1) > 0 || count($lname_diff2) > 0)
        {
            $tmpstr = '';
            foreach ($lname_diff1 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['HelpdeskURLLocalized']['before'] = $tmpstr;
            $tmpstr = '';
            foreach ($lname_diff2 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['HelpdeskURLLocalized']['after'] = $tmpstr;
        }


        $lname_before = $provider->getLocalPrivacyUrl();
        if ($lname_before == NULL)
        {
            $lname_before = array();
        }
        $lname_after = $this->getLocalPrivacyUrl();
        if ($lname_after == NULL)
        {
            $lname_after = array();
        }
        $lname_diff1 = array_diff_assoc($lname_before, $lname_after);
        $lname_diff2 = array_diff_assoc($lname_after, $lname_before);
        if (count($lname_diff1) > 0 || count($lname_diff2) > 0)
        {
            $tmpstr = '';
            foreach ($lname_diff1 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['PrivacyStatementURLLocalized']['before'] = $tmpstr;
            $tmpstr = '';
            foreach ($lname_diff2 as $k => $v)
            {
                $tmpstr .= $k . ':' . htmlentities($v) . '<br />';
            }
            $differ['PrivacyStatementURLLocalized']['after'] = $tmpstr;
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
        $this->createdAt = new \DateTime("now", new \DateTimeZone('UTC'));
        if (!isset($this->hidepublic))
        {
            $this->hidepublic = false;
        }
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
            if (!empty($rescheck))
            {
                return true;
            }
            $parent = array();

            $parents = $this->em->getRepository("models\AclResource")->findBy(array('resource' => array('idp', 'sp', 'entity')));
            foreach ($parents as $p)
            {
                $parent[$p->getResource()] = $p;
            }
            $stype = $this->type;
            if ($stype === 'BOTH')
            {
                $types = array('entity');
            }
            elseif ($stype === 'IDP')
            {
                $types = array('idp');
            }
            else
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
        \log_message('debug', 'GG update providers updated time for:' . $this->entityid);
        $this->updatedAt = new \DateTime("now", new \DateTimeZone('UTC'));
    }

    /**
     * @PostLoad
     */
    public function setAddionals()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    /**
     * @PostLoad
     */
    public function createEmptyFedColl()
    {
        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setName($name = null)
    {
        $this->name = $name;
        return $this;
    }

    public function setLocalName(array $name = NULL)
    {
        if (!empty($name))
        {
            foreach ($name as $k => $v)
            {
                if (empty($v))
                {
                    unset($name['' . $k . '']);
                }
            }
            $this->lname = serialize($name);
        }
        else
        {
            $this->lname = NULL;
        }
    }

    public function setDisplayName($name = null)
    {
        $this->displayname = $name;
        return $this;
    }

    public function setLocalDisplayName($name = NULL)
    {
        if (!empty($name) && is_array($name))
        {
            foreach ($name as $k => $v)
            {
                if (empty($v))
                {
                    unset($name['' . $k . '']);
                }
            }
            $this->ldisplayname = serialize($name);
        }
        else
        {
            $this->ldisplayname = serialize(array());
        }
    }

    public function setRegistrationPolicyFromArray($regarray, $reset = FALSE)
    {

        if ($reset === TRUE)
        {
            $this->regpolicy = serialize($regarray);
        }
        else
        {
            $s = $this->getRegistrationPolicy();
            $n = array_merge($s, $regarray);
            $this->regpolicy = serialize($n);
        }
        return $this;
    }

    public function setRegistrationPolicy($lang, $url)
    {
        $s = $this->getRegistrationPolicy();
        $s['' . $lang . ''] = $url;
        $this->regpolicy = serialize($s);
        return $this;
    }

    public function resetRegistrationPolicy()
    {
        $this->regpolicy = serialize(array());
        return $this;
    }

    /**
      public function setScope($scope)
      {
      $this->scope = $scope;
      return $this;
      }
     */

    /**
     * type : idpsso, aa
     * $scope: array();
     */
    public function setScope($type, $scope)
    {
        $ex = @unserialize($this->scope);
        if ($ex === 'b:0;' || $ex !== false)
        {
            $ex['' . $type . ''] = $scope;
        }
        else
        {
            $ex = array();
            $ex['' . $type . ''] = $scope;
        }
        $this->scope = serialize($ex);
        return $this;
    }

    private function overwriteScopeFull(Provider $provider)
    {
        $pScope = $provider->getScopeFull();
        if (!isset($pScope['idpsso']))
        {
            $pScope['idpsso'] = array();
        }
        if (!isset($pScope['aa']))
        {
            $pScope['aa'] = array();
        }
        foreach ($pScope as $k => $v)
        {
            if ($k === 'idpsso' || $k === 'aa')
            {
                $this->setScope($k, $v);
            }
        }
        return $this;
    }

    public function overwriteScope($n, Provider $provider)
    {
        $this->setScope($n, $provider->getScope($n));
        return $this;
    }

    public function setEntityId($entity)
    {
        $entity = trim($entity);
        if (!empty($entity))
        {
            $this->entityid = $entity;
            return $this;
        }
        else
        {
            return false;
        }
    }

    public function setDigest($a = null)
    {
        $this->digest = $a;
        return $this;
    }

    public function setCountry($country = null)
    {
        if (!empty($country))
        {
            $this->country = $country;
        }
    }

    /**
     * obsolete
     */
    public function resetNameId()
    {
        $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        return $this;
    }

    /**
     * obsolete
     */
    public function setNameId($nameid = NULL)
    {
        if (empty($nameid))
        {
            $nameid = "urn:oasis:names:tc:SAML:2.0:nameid-format:transient";
        }
        if (empty($this->nameidformat))
        {
            $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->nameidformat->add($nameid);
        return $this;
    }

    /**
     * new
     */
    public function setNameIds($n, $data)
    {
        $t = $this->getNameIds();
        $t['' . $n . ''] = $data;
        $this->nameids = serialize($t);
        return $this;
    }

    public function setVisiblePublic()
    {
        $this->hidepublic = false;
        return $this;
    }

    public function getMembership()
    {
        return $this->membership;
    }

    public function addMembership(FederationMembers $membership)
    {
        if (!$this->membership->contains($membership))
        {
            $this->membership->add($membership);
            $membership->setProvider($this);
        }

        return $this;
    }

    public function setHidePublic()
    {
        $this->hidepublic = true;
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
        $this->protocol->add($protocol);
        return $this;
    }

    public function setProtocolSupport($n, $data)
    {
        $allowed = array('aa', 'idpsso', 'spsso');
        if (in_array($n, $allowed) && is_array($data))
        {
            foreach ($data as $k => $v)
            {
                $i = trim($v);
                if (empty($i))
                {
                    unset($data['' . $k . '']);
                }
            }
            $r = $this->getProtocolSupport();
            $r['' . $n . ''] = $data;
            $this->protocolsupport = serialize($r);
        }
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

    public function setHelpdeskUrl($url = null)
    {
        $this->helpdeskurl = $url;
        return $this;
    }

    public function setLocalHelpdeskUrl($urls = NULL)
    {
        if (!empty($urls) && is_array($urls))
        {
            foreach ($urls as $k => $v)
            {
                if (empty($v))
                {
                    unset($urls['' . $k . '']);
                }
            }
            $this->lhelpdeskurl = serialize($urls);
        }
        else
        {
            $this->lhelpdeskurl = NULL;
        }
    }

    public function setPrivacyUrl($url = null)
    {
        $this->privacyurl = $url;
    }

    public function setLocalPrivacyUrl(array $url = null)
    {
        if (!empty($url))
        {
            $this->lprivacyurl = serialize($url);
        }
        else
        {
            $this->lprivacyurl = NULL;
        }
    }

    public function setRegistrationAuthority($reg = null)
    {
        $this->registrar = $reg;
        return $this;
    }

    public function setRegistrationDate($date = null)
    {
        if (empty($date))
        {
            $this->registerdate = NULL;
        }
        else
        {
            $this->registerdate = $date->setTimezone(new \DateTimeZone('UTC'));
        }
        return $this;
    }

    /**
     * set time entity is valid to, if null then current time
     */
    public function setValidTo(\DateTime $date = NULL)
    {
        if (empty($date))
        {
            $this->validto = NULL;
        }
        else
        {
            $this->validto = $date->setTimezone(new \DateTimeZone('UTC'));
        }
        return $this;
    }

    public function setValidFrom(\DateTime $date = NULL)
    {
        if (empty($date))
        {
            $this->validfrom = NULL;
        }
        else
        {
            $this->validfrom = $date->setTimezone(new \DateTimeZone('UTC'));
        }
        return $this;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * updateLocalizedMdui1 for elements: Description, DisplayName, PrivacyURL, InformationURL
     */
    public function updateLocalizedMdui1($elementName, $descriptions, $type)
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $ex = $this->getExtendMetadata();
        $parent = null;
        foreach ($ex as $e)
        {
            if (!empty($parent))
            {
                break;
            }
            else
            {
                if (empty($p) && $e->getType() === $type && $e->getNameSpace() === 'mdui' && $e->getElement() === 'UIInfo')
                {
                    $parent = $e;
                }
            }
        }
        foreach ($ex as $e)
        {
            $origElementName = $e->getElement();
            $origType = $e->getType();
            $origNameSpace = $e->getNameSpace();
            if ($origElementName === $elementName && $origType === $type && $origNameSpace === 'mdui')
            {
                $t = $e->getAttributes();
                $lvalue = $t['xml:lang'];
                if (array_key_exists($lvalue, $descriptions))
                {
                    if (!empty($descriptions[$lvalue]))
                    {
                        $e->setValue($descriptions[$lvalue]);
                    }
                    else
                    {
                        $ex->removeElement($e);
                        $this->em->remove($e);
                    }
                    unset($descriptions[$lvalue]);
                }
                else
                {
                    $ex->removeElement($e);
                    $this->em->remove($e);
                }
            }
        }
        if (count($descriptions) > 0)
        {
            foreach ($descriptions as $k => $v)
            {
                $nelement = new ExtendMetadata();
                $nelement->setType($type);
                $nelement->setNameSpace('mdui');
                $nelement->setElement($elementName);
                $nelement->setValue($v);
                $attr = array('xml:lang' => $k);
                $nelement->setAttributes($attr);
                if (empty($parent))
                {
                    $parent = new ExtendMetadata();
                    $parent->setType($type);
                    $parent->setNameSpace('mdui');
                    $parent->setElement('UIInfo');
                    $ex->add($parent);
                    $parent->setProvider($this);
                    $this->em->persist($parent);
                }
                $nelement->setParent($parent);
                $ex->add($nelement);
                $nelement->setProvider($this);
                $this->em->persist($nelement);
            }
        }
    }

    public function setWayfList($wayflist = null)
    {
        if (!empty($wayflist) && is_array($wayflist))
        {
            $this->wayflist = serialize($wayflist);
        }
    }

    public function setExcarps($excarps = null)
    {
        if (!empty($excarps) && is_array($excarps) && count($excarps) > 0)
        {
            $this->excarps = serialize($excarps);
        }
        else
        {
            $this->excarps = null;
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
        }
        else
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
        }
        else
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
        }
        else
        {
            $this->is_approved = 0;
        }
        return $this;
    }

    public function setFederation(Federation $federation)
    {
        $doFilter['federation_id'] = array('' . $federation->getId() . '');
        $membership = $this->getMembership()->filter(
                function($entry) use($doFilter) {
            return (in_array($entry->getFederation()->getId(), $doFilter['federation_id']));
        }
        );


        if ($membership->count() == 0)
        {
            $newMembership = new FederationMembers();
            $federation->addMembership($newMembership);
            $this->addMembership($newMembership);
        }
        return $this->getFederations();
    }

    public function removeFederation(Federation $federation)
    {

        $doFilter['federation_id'] = array('' . $federation->getId() . '');
        $membership = $this->getMembership()->filter(
                function($entry) use($doFilter) {
            return (in_array($entry->getFederation()->getId(), $doFilter['federation_id']));
        }
        );


        foreach ($membership as $m)
        {
            $this->removeMembership($m);
        }
        return $this->getFederations();
    }

    public function removeMembership(FederationMembers $membership)
    {
        if ($this->membership->contains($membership))
        {
            $this->membership->removeElement($membership);
        }

        return $this;
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
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->getServiceLocations()->removeElement($service);
        $this->em->remove($service);
        return $this->serviceLocations;
    }

    public function removeCoc(Coc $coc)
    {
        $this->getCoc()->removeElement($coc);
        $coc->getProviders()->removeElement($this);
        return $this;
    }

    public function setCoc(Coc $coc)
    {
        $this->getCoc()->add($coc);
        $coc->getProviders()->add($this);
        return $this;
    }

    public function setStatic($static)
    {
        if ($static === true)
        {
            $this->is_static = true;
        }
        else
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
        }
        else
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
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->getContacts()->removeElement($contact);
        $this->em->remove($contact);
        return $this->contacts;
    }

    public function removeAllContacts()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $contacts = $this->getContacts();
        foreach ($contacts->getValues() as $contact)
        {
            $contacts->removeElement($contact);
            $this->em->remove($contact);
        }
        return $this;
    }

    public function setCertificate(Certificate $certificate)
    {
        $this->getCertificates()->add($certificate);
        $certificate->setProvider($this);
        return $this->certificates;
    }

    public function setStatDefinition(ProviderStatsDef $p)
    {
        $this->getStatDefinitions()->add($p);
        $p->setProvider($this);
        return $this;
    }

    /**
     * this object state will be overwriten by $provider object
     */
    public function overwriteByProvider(Provider $provider)
    {
        $this->setName($provider->getName());
        $this->setLocalName($provider->getLocalName());
        $this->setDisplayName($provider->getDisplayName());
        $this->setLocalDisplayName($provider->getLocalDisplayName());
        $this->overwriteScopeFull($provider);
        $this->setEntityId($provider->getEntityId());
        $this->setRegistrationAuthority($provider->getRegistrationAuthority());
        $this->setRegistrationDate($provider->getRegistrationDate());
        $this->overwriteWithNameid($provider);
        $prototypes = array('idpsso', 'aa', 'spsso');
        foreach ($prototypes as $a)
        {
            $this->setProtocolSupport($a, $provider->getProtocolSupport($a));
        }
        $this->setType($provider->getType());
        $this->setHelpdeskUrl($provider->getHelpdeskUrl());
        $this->setValidFrom($provider->getValidFrom());
        $this->setValidTo($provider->getValidTo());
        $smetadata = $provider->getStaticMetadata();
        if (!empty($smetadata))
        {
            $this->overwriteStaticMetadata($smetadata);
        }
        foreach ($this->getServiceLocations() as $s)
        {
            $this->removeServiceLocation($s);
        }
        foreach ($provider->getServiceLocations() as $r)
        {
            $this->setServiceLocation($r);
            if (!$r->getOrder())
            {
                $r->setOrder(1);
            }
        }
        foreach ($this->getCertificates() as $c)
        {
            $this->removeCertificate($c);
        }
        foreach ($provider->getCertificates() as $r)
        {
            $this->setCertificate($r);
        }

        foreach ($this->getContacts() as $cn)
        {
            $this->removeContact($cn);
        }
        foreach ($provider->getContacts() as $cn2)
        {
            $this->setContact($cn2);
        }
        foreach ($this->getExtendMetadata() as $f)
        {
            if (!empty($f))
            {
                $this->removeExtendWithChildren($f);
            }
        }
        foreach ($provider->getExtendMetadata() as $gg)
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

    public function getStatDefinitions()
    {
        return $this->statsdef;
    }

    /**
     * obsolete
     */
    public function getNameIdToArray()
    {
        return $this->getNameId()->toArray();
    }

    /**
     * obsolete
     */
    public function getNameId()
    {
        return $this->nameidformat;
    }

    /**
     * new replacing getNameId()
     * $n one of : idpsso,spsso,aa
     */
    public function getNameIds($n = null)
    {
        if (empty($n))
        {
            if (!empty($this->nameids))
            {
                return unserialize($this->nameids);
            }
            else
            {
                return array();
            }
        }
        $default = array();
        if (!empty($this->nameids))
        {
            $r = unserialize($this->nameids);
            if (isset($r['' . $n . '']))
            {
                return $r['' . $n . ''];
            }
        }
        return $default;
    }

    public function getNotifications()
    {
        return $this->notifications;
    }

    public function addNotification(NotificationList $notification)
    {
        $isin = $this->getNotifications()->contains($notification);
        if (empty($isin))
        {
            $this->getNotifications()->add($notification);
        }
        return $this;
    }

    public function getActive()
    {
        return $this->is_active;
    }

    public function getCoc()
    {
        return $this->coc;
    }

    public function getProtocol()
    {
        $col = new \Doctrine\Common\Collections\ArrayCollection();
        $tmp = $this->protocol;
        if (!empty($tmp))
        {
            return $this->protocol;
        }
        else
        {
            return $col;
        }
    }

    public function getProtocolSupport($n = null)
    {
        if (empty($n))
        {
            $t = $this->protocolsupport;
            if (empty($t))
            {
                return array();
            }
            else
            {
                return unserialize($t);
            }
        }
        $default = array('urn:oasis:names:tc:SAML:2.0:protocol');
        $t = $this->protocolsupport;
        if (!empty($t))
        {
            $r = unserialize($t);
            if (isset($r[$n]))
            {
                return $r[$n];
            }
        }
        return $default;
    }

    public function getRegistrationPolicy()
    {
        $s = @unserialize($this->regpolicy);
        if (empty($s))
        {
            return array();
        }
        return $s;
    }

    public function getScope($n)
    {
        $s = @unserialize($this->scope);
        if (isset($s[$n]))
        {
            return $s[$n];
        }
        else
        {
            return array();
        }
    }

    public function getScopeFull()
    {
        $s = @unserialize($this->scope);
        if (!empty($s))
        {
            return $s;
        }
        else
        {
            return array('aa' => array(), 'idpsso' => array());
        }
    }

    /**
     * used for convert strings to array
     */
    public function convertScope()
    {
        $s = $this->scope;
        if (!empty($s))
        {
            $s2 = @unserialize($s);
            if (empty($s2))
            {
                $y = explode(',', $this->scope);
                $z = array('idpsso' => $y, 'aa' => $y);
                $this->scope = (serialize($z));
                return $this;
            }
        }
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

    public function getFederations()
    {
        $mem = $this->membership;
        $federations = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($mem as $m)
        {
            $joinstate = $m->getJoinState();
            if ($joinstate != 2)
            {
                $federations->add($m->getFederation());
                ;
            }
        }
        return $federations;
    }

    public function getActiveFederations()
    {
        $mem = $this->membership;
        $federations = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($mem as $m)
        {
            if ($m->isFinalMembership())
            {
                $federations->add($m->getFederation());
                ;
            }
        }
        return $federations;
    }

    public function getFederationNames()
    {
        $feders = array();
        foreach ($this->membership as $entry)
        {
            $feders[] = $entry->getFederation();
        }
        return $feders;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLocalName()
    {
        $p = unserialize($this->lname);
        if (empty($p))
        {
            return array();
        }
        else
        {
            return $p;
        }
    }

    public function getMergedLocalName()
    {
        $r = $this->getLocalName();
        if (!isset($r['en']) && !empty($this->name))
        {
            $r['en'] = $this->name;
        }
        return $r;
    }

    public function getNameToWebInLang($lang, $type = null)
    {
        $result = null;
        $backupname = null;
        if (empty($type))
        {
            $type = $this->type;
        }
        $e = $this->getExtendMetadata();
        if (!empty($e))
        {
            foreach ($e as $p)
            {
                $k = $p->getElement();
                $t = $p->getType();
                $a = $p->getAttributes();
                if (strcmp($k, 'DisplayName') == 0 && strcasecmp($t, $type) == 0 && isset($a['xml:lang']))
                {
                    if (strcasecmp($a['xml:lang'], $lang) == 0)
                    {
                        $result = $p->getEvalue();
                        break;
                    }
                    elseif ($backupname === null)
                    {
                        $backupname = $p->getEvalue();
                    }
                }
            }
        }
        if ($result === null)
        {
            if ($backupname !== null)
            {
                $result = $backupname;
            }
            else
            {
                $result = $this->getDisplayNameInLang($lang);
                if (empty($result))
                {
                    $result = $this->getNameInLang($lang);
                }
            }
        }
        if (empty($result))
        {
            $result = $this->entityid;
        }
        return trim($result);
    }

    public function getNameLocalized()
    {
        $t['en'] = $this->name;
        $p = unserialize($this->lname);
        if (is_array($p))
        {
            if (!array_key_exists('en', $p))
            {
                $p['en'] = $t['en'];
            }
        }
        else
        {
            $p = $t;
        }
        return $p;
    }

    public function getDisplayNameInLang($lang)
    {
        $r = $this->getDisplayNameLocalized();
        if (isset($r['' . $lang . '']))
        {
            return $r['' . $lang . ''];
        }
        else
        {
            return $r['en'];
        }
    }

    public function getNameInLang($lang)
    {
        $r = $this->getNameLocalized();
        if (isset($r['' . $lang . '']))
        {
            return $r['' . $lang . ''];
        }
        else
        {
            return $r['en'];
        }
    }

    public function getDisplayName($length = null)
    {
        if (empty($length) || !is_integer($length) || strlen($this->displayname) <= $length)
        {
            return $this->displayname;
        }
        else
        {
            return substr($this->displayname, 0, $length) . "...";
        }
    }

    public function getLocalDisplayName()
    {
        if (!empty($this->ldisplayname))
        {
            return unserialize($this->ldisplayname);
        }
        return array();
    }

    public function getMergedLocalDisplayName()
    {
        $r = $this->getLocalDisplayName();
        if (!isset($r['en']) && !empty($this->displayname))
        {
            $r['en'] = $this->displayname;
        }
        return $r;
    }

    public function getLocalDisplayNamesToArray($type)
    {
        $result = array();
        $ex = $this->getExtendMetadata();
        foreach ($ex as $v)
        {
            if ($v->getType() == $type && $v->getNameSpace() == 'mdui' && $v->getElement() == 'DisplayName')
            {
                $l = $v->getAttributes();
                $result[$l['xml:lang']] = $v->getElementValue();
            }
        }
        return $result;
    }

    public function getDisplayNameLocalized()
    {
        if (!empty($this->ldisplayname))
        {
            $p = unserialize($this->ldisplayname);
            if (!array_key_exists('en', $p))
            {
                $p['en'] = $this->displayname;
            }
            return $p;
        }
        else
        {
            return array('en' => $this->displayname);
        }
    }

    private function getServicePartsToArray($name)
    {
        $result = array();
        $ext = $this->getExtendMetadata();
        foreach ($ext as $e)
        {
            $t = $e->getType();
            if ($t !== 'sp')
            {
                continue;
            }
            $n = $e->getElement();
            $ns = $e->getNameSpace();
            if ($n === $name && $ns === 'mdui')
            {
                $l = $e->getAttributes();
                $v = $e->getEvalue();
                if (isset($l['xml:lang']) && !empty($v))
                {
                    $result['' . $l['xml:lang'] . ''] = $v;
                }
            }
        }
        if (count($result) == 0)
        {
            if ($name === 'DisplayName')
            {
                $m = $this->getDisplayName();
                if (empty($m))
                {
                    $m = $this->getName();
                }
                if (empty($m))
                {
                    $m = $this->getEntityId();
                }
                $result['en'] = $m;
            }
            elseif ($name === 'Description')
            {
                if (!empty($desc))
                {
                    $result['en'] = 'no description';
                }
            }
        }
        return $result;
    }

    public function getDigest()
    {
        return $this->digest;
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
    public function isValidFromTo()
    {
        /**
         * @todo fix broken time for the momemnt reurns true
         */
        $currentTime = new \DateTime("now", new \DateTimeZone('UTC'));
        $validAfter = TRUE;
        $validBefore = TRUE;
        if (!empty($this->validfrom))
        {

            if ($currentTime < $this->validfrom)
            {
                $validBefore = FALSE;
            }
        }
        if (!empty($this->validto))
        {
            if ($currentTime > $this->validto)
            {
                $validAfter = FALSE;
            }
        }

        return ($validAfter && $validBefore);
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

    public function isStaticMetadata()
    {
        $c = $this->getStatic();
        $d = $this->getStaticMetadata();
        if ($c && !empty($d))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getHelpdeskUrl()
    {
        return $this->helpdeskurl;
    }

    public function getLocalHelpdeskUrl()
    {
        if (!empty($this->lhelpdeskurl))
        {
            return unserialize($this->lhelpdeskurl);
        }
        else
        {
            return array();
        }
    }

    public function getHelpdeskUrlLocalized()
    {
        $t['en'] = $this->helpdeskurl;
        $p = unserialize($this->lhelpdeskurl);
        if (is_array($p))
        {
            if (!array_key_exists('en', $p) && !empty($t['en']))
            {
                $p['en'] = $t['en'];
            }
        }
        else
        {
            $p = $t;
        }
        return array_filter($p);
    }

    public function getPrivacyUrl()
    {
        return $this->privacyurl;
    }

    public function getLocalPrivacyUrl()
    {
        return unserialize($this->lprivacyurl);
    }

    public function getLocalPrivacyStatementsToArray($type)
    {
        $result = array();
        $ex = $this->getExtendMetadata();
        foreach ($ex as $v)
        {
            if ($v->getType() == $type && $v->getNameSpace() == 'mdui' && $v->getElement() == 'PrivacyStatementURL')
            {
                $l = $v->getAttributes();
                $result[$l['xml:lang']] = $v->getElementValue();
            }
        }
        return $result;
    }

    public function getPrivacyUrlLocalized()
    {
        $t['en'] = $this->privacyurl;
        $p = unserialize($this->lprivacyurl);
        if (is_array($p))
        {
            if (!array_key_exists('en', $p))
            {
                $p['en'] = $t['en'];
            }
        }
        else
        {
            $p = $t;
        }
        return $p;
    }

    public function getApproved()
    {
        return $this->is_approved;
    }

    public function getLocked()
    {

        return $this->is_locked;
    }

    public function getPublicVisible()
    {
        return !($this->hidepublic);
    }

    public function getAvailable()
    {

        return ($this->is_active && $this->is_approved && $this->isValidFromTo());
    }

    public function getLocal()
    {
        return $this->is_local;
    }

    public function getLocalAvailable()
    {
        return ( $this->is_local && $this->is_active && $this->is_approved && $this->isValidFromTo());
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getLocalDescriptionsToArray($type)
    {
        $result = array();
        $ex = $this->getExtendMetadata();
        foreach ($ex as $v)
        {
            $t = $v->getType();
            $u = $v->getNameSpace();
            $e = $v->getElement();
            if ($t === $type && $u === 'mdui' && $e === 'Description')
            {
                $l = $v->getAttributes();
                $result[$l['xml:lang']] = $v->getElementValue();
            }
        }
        return $result;
    }

    public function getMduiDiscoHintToXML(\DOMElement $parent, $type = NULL)
    {
        if (empty($type))
        {
            $type = $this->type;
        }
        $ext = $this->getExtendMetadata();
        $extarray = array();
        foreach ($ext as $v)
        {
            if ((strcasecmp($v->getType(), $type) == 0) && ($v->getNamespace() === 'mdui') && ($v->getElement() === 'GeolocationHint'))
            {
                $extarray[] = $v;
            }
        }
        if (count($extarray) > 0)
        {
            $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:DiscoHints');
            foreach ($extarray as $dm)
            {
                $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:GeolocationHint');
                $dnode->appendChild($e->ownerDocument->createTextNode($dm->getElementValue()));
                $e->appendChild($dnode);
            }
            return $e;
        }
        else
        {
            return NULL;
        }
    }

    /**
     * $type should be sp or idp
     */
    public function getMduiToXML(\DOMElement $parent, $type = NULL)
    {
        if (empty($type))
        {
            $type = $this->type;
        }

        $ext = $this->getExtendMetadata();
        /**
         * leave only elements matching criteria
         */
        $extarray = array('DisplayName' => array(), 'Description' => array(), 'Logo' => array(), 'InformationURL' => array(), 'PrivacyStatementURL' => array());
        foreach ($ext as $v)
        {
            if ((strcasecmp($v->getType(), $type) == 0) && ($v->getNamespace() === 'mdui'))
            {
                $extarray['' . $v->getElement() . ''][] = $v;
            }
        }
        if (isset($extarray['Logo']) || array_key_exists('Logo', $extarray))
        {
            $this->logo_basepath = $this->ci->config->item('rr_logouriprefix');
            $this->logo_baseurl = $this->ci->config->item('rr_logobaseurl');

            if (empty($this->logo_baseurl))
            {
                $this->logo_baseurl = base_url();
            }
            $this->logo_url = $this->logo_baseurl . $this->logo_basepath;
        }

        $en_displayname = FALSE;
        $en_informationurl = FALSE;
        $en_privacyurl = FALSE;
        $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:UIInfo');

        foreach ($extarray as $key => $value)
        {
            if ($key === 'DisplayName')
            {
                foreach ($value as $dm)
                {
                    $lang = $dm->getAttributes();
                    if (isset($lang['xml:lang']))
                    {
                        $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:DisplayName');
                        $dnode->setAttribute('xml:lang', '' . $lang['xml:lang'] . '');
                        $dnode->appendChild($e->ownerDocument->createTextNode($dm->getElementValue()));
                        if ($lang['xml:lang'] == 'en')
                        {
                            $en_displayname = TRUE;
                        }
                        $e->appendChild($dnode);
                    }
                }
                if ($en_displayname !== TRUE)
                {
                    $gd = $this->getDisplayName();
                    if (empty($gd))
                    {
                        $gd = $this->getName();
                    }
                    if (empty($gd))
                    {
                        $gd = $this->getEntityId();
                    }
                    $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:DisplayName');
                    $dnode->setAttribute('xml:lang', 'en');
                    $dnode->appendChild($e->ownerDocument->createTextNode($gd));
                    $e->appendChild($dnode);
                }
            }
            elseif ($key === 'Description')
            {
                foreach ($value as $dm)
                {
                    $lang = $dm->getAttributes();
                    if (isset($lang['xml:lang']))
                    {
                        $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:Description');
                        $dnode->setAttribute('xml:lang', '' . $lang['xml:lang'] . '');
                        $dnode->appendChild($e->ownerDocument->createTextNode($dm->getElementValue()));
                        $e->appendChild($dnode);
                    }
                }
            }
            elseif ($key === 'PrivacyStatementURL')
            {
                foreach ($value as $dm)
                {
                    $lang = $dm->getAttributes();
                    if (isset($lang['xml:lang']))
                    {
                        $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:PrivacyStatementURL');
                        $dnode->setAttribute('xml:lang', '' . $lang['xml:lang'] . '');
                        $dnode->appendChild($e->ownerDocument->createTextNode($dm->getElementValue()));
                        if ($lang['xml:lang'] === 'en')
                        {
                            $en_privacyurl = TRUE;
                        }
                        $e->appendChild($dnode);
                    }
                }
                if ($en_privacyurl !== TRUE)
                {
                    $gd = $this->getPrivacyUrl();
                    if (!empty($gd))
                    {
                        $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:PrivacyStatementURL');
                        $dnode->setAttribute('xml:lang', 'en');
                        $dnode->appendChild($e->ownerDocument->createTextNode($gd));
                        $e->appendChild($dnode);
                    }
                }
            }
            elseif ($key === 'InformationURL')
            {
                foreach ($value as $dm)
                {
                    $lang = $dm->getAttributes();
                    if (isset($lang['xml:lang']))
                    {
                        $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:InformationURL');
                        $dnode->appendChild($e->ownerDocument->createTextNode($dm->getElementValue()));
                        $dnode->setAttribute('xml:lang', '' . $lang['xml:lang'] . '');
                        if ($lang['xml:lang'] === 'en')
                        {
                            $en_informationurl = TRUE;
                        }
                        $e->appendChild($dnode);
                    }
                }
                if ($en_informationurl !== TRUE)
                {
                    $gd = $this->getHelpdeskURL();
                    if (!empty($gd))
                    {
                        $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:InformationURL');
                        $dnode->setAttribute('xml:lang', 'en');
                        $dnode->appendChild($e->ownerDocument->createTextNode($gd));
                        $e->appendChild($dnode);
                    }
                }
            }
            elseif ($key === 'Logo')
            {
                foreach ($value as $dm)
                {
                    if (!(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $dm->getElementValue(), $matches)))
                    {
                        $ElementValue = $this->logo_url . $dm->getElementValue();
                    }
                    else
                    {
                        $ElementValue = $dm->getElementValue();
                    }
                    $dnode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:Logo');
                    $dnode->appendChild($e->ownerDocument->createTextNode($ElementValue));
                    $attrs = $dm->getAttributes();
                    if (!empty($attrs))
                    {

                        foreach ($attrs as $akey => $avalue)
                        {
                            if (!empty($avalue))
                            {
                                $dnode->setAttribute($akey, $avalue);
                            }
                        }
                    }

                    $e->appendChild($dnode);
                }
            }
        }


        return $e;
    }

    public function getWayfList()
    {
        $w = $this->wayflist;
        if (!empty($w))
        {
            return unserialize($w);
        }
        else
        {
            return null;
        }
    }

    public function getExcarps()
    {
        $w = $this->excarps;
        if (!empty($w))
        {
            return unserialize($w);
        }
        else
        {
            return array();
        }
    }

    public function getLastModified()
    {
        if (empty($this->updatedAt))
        {
            return $this->createdAt;
        }
        else
        {
            return $this->updatedAt;
        }
    }

    public function overwriteWithNameid(Provider $provider)
    {
        $this->nameids = serialize($provider->getNameIds());
    }

    public function convertToArray($addmeta = FALSE)
    {
        $r = array();
        $r['id'] = $this->getId();
        if ($addmeta === TRUE)
        {
            $m = $this->getProviderToXML()->saveXML();
            if (!empty($m))
            {
                $r['metadata'] = base64_encode($m);
            }
        }
        $r['name'] = $this->getName();
        $r['displayname'] = $this->getDisplayname();
        $r['entityid'] = $this->getEntityId();

        $r['nameid'] = array();
        $nameids = $this->getNameIds();
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
        $r['scope'] = $this->getScope('idpsso');
        $r['aascope'] = $this->getScope('aa');
        $r['helpdeskurl'] = $this->getHelpdeskUrl();
        $r['privacyurl'] = $this->getPrivacyUrl();
        $r['validfrom'] = $this->getValidFrom();
        $r['validto'] = $this->getValidTo();
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
        $membership = $this->getMembership();
        if (!empty($membership) && $membership->count() > 0)
        {
            \log_message('debug', 'GKS found membership');
            foreach ($membership as $f)
            {
                $state = $f->getJoinState();
                if ($state != 2)
                {
                    $r['federations'][] = $f->getFederation()->convertToArray();
                }
            }
        }

        return $r;
    }

    public function importFromArray(array $r)
    {
        $etype = strtoupper($r['type']);
        $this->setName($r['name']);
        if (!empty($r['displayname']))
        {
            $this->setDisplayname($r['displayname']);
        }
        else
        {
            $this->setDisplayname($r['name']);
        }
        $this->setEntityid($r['entityid']);
        if (is_array($r['nameid']) && count($r['nameid'] > 0))
        {
            foreach ($r['nameid'] as $k => $n)
            {
                $this->setNameids($k, $n);
            }
        }

        if (is_array($r['protocol']) && count($r['protocol']) > 0)
        {
            foreach ($r['protocol'] as $p)
            {
                $this->setProtocol($p);
            }
        }

        $this->setType($r['type']);
        $this->setScope('idpsso', $r['scope']);
        $this->setScope('aa', $r['aascope']);
        $this->setHelpdeskUrl($r['helpdeskurl']);
        $this->setPrivacyUrl($r['privacyurl']);
        $this->setValidFrom($r['validfrom']);
        $this->setValidTo($r['validto']);
        $this->setApproved($r['is_approved']);
        $this->setActive($r['is_active']);
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
        if ($etype !== 'IDP')
        {
            if (isset($r['details']['spssodescriptor']['extensions']['idpdisc']) && is_array($r['details']['spssodescriptor']['extensions']['idpdisc']))
            {
                foreach ($r['details']['spssodescriptor']['extensions']['idpdisc'] as $idpdisc)
                {
                    $c = new ServiceLocation;
                    $c->setDiscoveryResponse($idpdisc['url'], $idpdisc['order']);
                    $c->setProvider($this);
                }
            }
            if (isset($r['details']['spssodescriptor']['extensions']['init']) && is_array($r['details']['spssodescriptor']['extensions']['init']))
            {
                foreach ($r['details']['spssodescriptor']['extensions']['init'] as $initreq)
                {
                    $c = new ServiceLocation;
                    $c->setRequestInitiator($initreq['url'], $initreq['binding']);
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
                $m = new FederationMembers;
                $m->setFederation($c);
                $m->setProvider($this);
                $this->addMembership($m);
            }
        }
    }

    public function getOrganizationToXML(\DOMElement $parent)
    {
        $ns_md = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $e = $parent->ownerDocument->createElementNS($ns_md, 'md:Organization');

        $lorgnames = $this->getMergedLocalName();
        $ldorgnames = $this->getMergedLocalDisplayName();
        $lurls = $this->getHelpdeskUrlLocalized();
        if (count($lurls) == 0 || count($lorgnames) == 0 || count($ldorgnames) == 0)
        {
            \log_message('warning', 'Missing one of Organization elements , not generating Organization for entity: ' . $this->entityid);
            return null;
        }
        foreach ($lorgnames as $k => $v)
        {
            if (!empty($v))
            {
                $organizationNameNode = $e->ownerDocument->createElementNS($ns_md, 'md:OrganizationName');
                $organizationNameNode->setAttribute('xml:lang', '' . $k . '');
                $organizationNameNode->appendChild($e->ownerDocument->createTextNode($v));
                $e->appendChild($organizationNameNode);
            }
        }
        foreach ($ldorgnames as $k => $v)
        {
            if (!empty($v))
            {
                $organizationDisplaynameNode = $e->ownerDocument->createElementNS($ns_md, 'md:OrganizationDisplayName');
                $organizationDisplaynameNode->setAttribute('xml:lang', '' . $k . '');
                $organizationDisplaynameNode->appendChild($e->ownerDocument->createTextNode($v));
                $e->appendChild($organizationDisplaynameNode);
            }
        }
        foreach ($lurls as $k => $v)
        {
            if (!empty($v))
            {
                $organizationUrlNode = $e->ownerDocument->createElementNS($ns_md, 'md:OrganizationURL');
                $organizationUrlNode->setAttribute('xml:lang', '' . $k . '');
                $organizationUrlNode->appendChild($e->ownerDocument->createTextNode($v));
                $e->appendChild($organizationUrlNode);
            }
        }

        return $e;
    }

    private function getIDPAADescriptorToXML(\DOMElement $parent)
    {
        $this->ci = & get_instance();
        $doFilter = array('IDPAttributeService');
        $services = $this->getServiceLocations()->filter(
                function($entry) use ($doFilter) {
            return in_array($entry->getType(), $doFilter);
        });
        $doCertFilter = array('aa');
        $certs = $this->getCertificates()->filter(
                function($entry) use ($doCertFilter) {
            return in_array($entry->getType(), $doCertFilter);
        });


        /**
         * do dont generate <AttributeAuthoritydescriptor if no service found
         */
        $noservices = $services->count();
        if ($noservices < 1)
        {
            return null;
        }

        $ns_md = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $e = $parent->ownerDocument->createElementNS($ns_md, 'md:AttributeAuthorityDescriptor');
        $protocol = $this->getProtocolSupport('aa');
        $protocols = implode(' ', $protocol);
        $e->setAttribute('protocolSupportEnumeration', $protocols);

        $scs = $this->getScope('aa');
        if (is_array($scs) && count($scs) > 0)
        {
            $Extensions_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
            foreach ($scs as $sc)
            {
                $Scope_Node = $Extensions_Node->ownerDocument->createElementNS('urn:mace:shibboleth:metadata:1.0', 'shibmd:Scope', trim($sc));
                $Scope_Node->setAttribute('regexp', 'false');
                $Extensions_Node->appendChild($Scope_Node);
            }
            $e->appendChild($Extensions_Node);
        }
        $certs = $this->getCertificates();
        log_message('debug', __METHOD__ . ' ' . gettype($certs));
        if (!empty($certs))
        {
            $ncerts = $certs->count();
        }
        else
        {
            $ncerts = 0;
        }
        if ($ncerts === 0)
        {
            log_message('debug', 'Provider ' . $this->id . ': no certificates found for AA ');
            return NULL;
        }
        else
        {
            foreach ($certs as $cert)
            {
                $type = $cert->getType();
                if ($type === 'aa')
                {
                    $KeyDescriptor_Node = $cert->getCertificateToXML($e);
                    if ($KeyDescriptor_Node !== NULL)
                    {
                        $e->appendChild($KeyDescriptor_Node);
                    }
                }
            }
        }
        foreach ($services as $srv)
        {
            $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AttributeService');
            $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
            $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
            $e->appendChild($ServiceLocation_Node);
        }
        $nameid = $this->getNameIds('aa');
        foreach ($nameid as $key)
        {
            $NameIDFormat_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:NameIDFormat', $key);
            $e->appendChild($NameIDFormat_Node);
        }




        return $e;
    }

    private function getIDPSSODescriptorToXML(\DOMElement $parent)
    {
        $services = $this->getServiceLocations();
        if ($services->count() == 0)
        {
            log_message('error', __METHOD__ . 'no serviceLocations found for entityID:' . $this->entityid);
            return null;
        }
        $this->logo_basepath = $this->ci->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->ci->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;

        $ns_md = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $e = $parent->ownerDocument->createElementNS($ns_md, 'md:IDPSSODescriptor');
        $protocol = $this->getProtocolSupport('idpsso');
        $protocols = implode(" ", $protocol);
        if (empty($protocols))
        {
            $protocols = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        $e->setAttribute('protocolSupportEnumeration', $protocols);
        $Extensions_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
        $scs = $this->getScope('idpsso');
        if (is_array($scs))
        {
            foreach ($scs as $sc)
            {
                $sc = trim($sc);
                if (!empty($sc))
                {
                    $Scope_Node = $Extensions_Node->ownerDocument->createElementNS('urn:mace:shibboleth:metadata:1.0', 'shibmd:Scope', $sc);
                    $Scope_Node->setAttribute('regexp', 'false');
                    $Extensions_Node->appendChild($Scope_Node);
                }
            }
        }

        /* UIInfo */
        $UIInfo_Node = $this->getMduiToXML($Extensions_Node, 'idp');
        if (!empty($UIInfo_Node))
        {
            $Extensions_Node->appendChild($UIInfo_Node);
        }
        $DiscoHints_Node = $this->getMduiDiscoHintToXML($Extensions_Node, 'idp');
        if (!empty($DiscoHints_Node))
        {
            $Extensions_Node->appendChild($DiscoHints_Node);
        }



        $e->appendChild($Extensions_Node);
        $certs = $this->getCertificates();
        log_message('debug', __LINE__ . ' ' . __METHOD__ . ': ' . $certs->count());
        log_message('debug', __LINE__ . ' ' . __METHOD__ . ': ' . gettype($certs));
        if (!empty($certs))
        {
            $ncerts = $certs->count();
        }
        else
        {
            $ncerts = 0;
            log_message('debug', "Provider model: no local certificates may cause problems");
            return NULL;
        }

        if ($ncerts > 0)
        {
            $idpssocerts = 0;
            foreach ($certs as $cert)
            {
                $type = $cert->getType();
                if ($type === 'idpsso')
                {
                    $KeyDescriptor_Node = $cert->getCertificateToXML($e);
                    if ($KeyDescriptor_Node !== NULL)
                    {
                        $e->appendChild($KeyDescriptor_Node);
                        ++$idpssocerts;
                    }
                }
            }
            if ($idpssocerts == 0)
            {
                log_message('error', 'line ' . __LINE__ . ' ' . __METHOD__ . ' ' . $this->entityid . ' no idpsso certs ');
                return NULL;
            }
        }
        $tmpserorder = array('logout' => array(), 'sso' => array(), 'artifact' => array());




        foreach ($services as $srv)
        {
            $srv_type = $srv->getType();
            if (strcmp($srv_type, 'SingleSignOnService') == 0)
            {
                $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SingleSignOnService');
                $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
                $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
                $tmpserorder['sso'][] = $ServiceLocation_Node;
            }
            elseif ($srv_type === 'IDPSingleLogoutService')
            {
                $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SingleLogoutService');
                $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
                $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
                $tmpserorder['logout'][] = $ServiceLocation_Node;
            }
            elseif ($srv_type === 'IDPArtifactResolutionService')
            {
                $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ArtifactResolutionService');
                $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
                $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
                $ServiceLocation_Node->setAttribute("index", $srv->getOrder());
                $tmpserorder['artifact'][] = $ServiceLocation_Node;
            }
        }
        foreach ($tmpserorder['artifact'] as $p)
        {
            $e->appendChild($p);
        }

        foreach ($tmpserorder['logout'] as $p)
        {
            $e->appendChild($p);
        }
        $nameid = $this->getNameIds('idpsso');
        foreach ($nameid as $key)
        {
            $NameIDFormat_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:NameIDFormat', $key);
            $e->appendChild($NameIDFormat_Node);
        }
        foreach ($tmpserorder['sso'] as $p)
        {
            $e->appendChild($p);
        }

        return $e;
    }

    public function getSPSSODescriptorToXML(\DOMElement $parent, $options = null)
    {


        $services = $this->getServiceLocations();
        if ($services->count() == 0)
        {
            log_message('error', __METHOD__ . ' no service location found for entityID:' . $this->entityid);
            return null;
        }

        $this->logo_basepath = $this->ci->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->ci->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;

        $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SPSSODescriptor');
        $protocols = implode(" ", $this->getProtocolSupport('spsso'));
        if (empty($protocols))
        {
            $protocols = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        $e->setAttribute('protocolSupportEnumeration', $protocols);

        $Extensions_Node = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
        $e->appendChild($Extensions_Node);

        /* DiscoveryResponse */

        $discrespindex = array('-1');
        foreach ($services as $t)
        {
            $loc_type = $t->getType();
            if ($loc_type === 'RequestInitiator')
            {
                $discNode = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:profiles:SSO:request-init', 'init:RequestInitiator');
                $discNode->setAttribute('Binding', $t->getBindingName());
                $discNode->setAttribute('Location', $t->getUrl());
                $Extensions_Node->appendChild($discNode);
            }
            elseif ($loc_type === 'DiscoveryResponse')
            {
                $discNode = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol', 'idpdisc:DiscoveryResponse');
                $discNode->setAttribute('Binding', $t->getBindingName());
                $discNode->setAttribute('Location', $t->getUrl());
                $discorder = $t->getOrder();
                if (is_null($discorder) || in_array($discorder, $discrespindex))
                {
                    $discorder = max($discrespindex) + 1;
                    $discrespindex[] = $discorder;
                }
                else
                {
                    $discrespindex[] = $discorder;
                }
                $discNode->setAttribute('index', $discorder);
                $Extensions_Node->appendChild($discNode);
            }
        }
        /* UIInfo */
        $UIInfo_Node = $this->getMduiToXML($Extensions_Node, 'sp');
        if (!empty($UIInfo_Node))
        {
            $Extensions_Node->appendChild($UIInfo_Node);
        }
      //  $DiscoHints_Node = $this->getMduiDiscoHintToXML($Extensions_Node, 'sp');
      //  if (!empty($DiscoHints_Node))
      //  {
      //      $Extensions_Node->appendChild($DiscoHints_Node);
      //  }



        foreach ($this->getCertificates() as $cert)
        {
            if ($cert->getType() === 'spsso')
            {

                $KeyDescriptor_Node = $cert->getCertificateToXML($e);
                if ($KeyDescriptor_Node !== NULL)
                {
                    $e->appendChild($KeyDescriptor_Node);
                }
            }
        }

        $tmpserorder = array('logout' => array(), 'assert' => array(), 'art' => array());
        foreach ($services as $srv)
        {
            $stype = $srv->getType();
            if ($stype === 'AssertionConsumerService')
            {
                $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AssertionConsumerService');
                $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
                $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
                $ServiceLocation_Node->setAttribute("index", $srv->getOrder());
                $is_defaultsrc = $srv->getDefault();
                if (!empty($is_defaultsrc))
                {
                    $ServiceLocation_Node->setAttribute("isDefault", 'true');
                }
                $tmpserorder['assert'][] = $ServiceLocation_Node;
            }
            elseif ($stype === 'SPSingleLogoutService')
            {
                $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SingleLogoutService');
                $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
                $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
                $tmpserorder['logout'][] = $ServiceLocation_Node;
            }
            elseif ($stype === 'SPArtifactResolutionService')
            {
                $ServiceLocation_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ArtifactResolutionService');
                $ServiceLocation_Node->setAttribute("Binding", $srv->getBindingName());
                $ServiceLocation_Node->setAttribute("Location", $srv->getUrl());
                $ServiceLocation_Node->setAttribute("index", $srv->getOrder());
                $tmpserorder['art'][] = $ServiceLocation_Node;
            }
        }
        foreach ($tmpserorder['art'] as $p)
        {
            $e->appendChild($p);
        }
        foreach ($tmpserorder['logout'] as $p)
        {
            $e->appendChild($p);
        }
        foreach ($this->getNameIds('spsso') as $v)
        {
            $NameIDFormat_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:NameIDFormat', $v);
            $e->appendChild($NameIDFormat_Node);
        }

        foreach ($tmpserorder['assert'] as $p)
        {
            $e->appendChild($p);
        }
        $tmpserorder = null;
        if (!empty($options) and is_array($options) and array_key_exists('attrs', $options) and ! empty($options['attrs']))
        {
            $sp_reqattrs = $this->getAttributesRequirement();
            $sp_reqattrs_count = $sp_reqattrs->count();
            if ($sp_reqattrs_count > 0)
            {
                foreach ($sp_reqattrs->getValues() as $v)
                {
                    $reqattr = $v->getAttribute();
                    if(!empty($reqattr))
                    {
                       $in = $reqattr->showInMetadata();
                       if ($in === FALSE)
                       {

                        $sp_reqattrs->removeElement($v);
                      }
                    }
                }
            }
            $sp_reqattrs_count = $sp_reqattrs->count();
            if ($sp_reqattrs_count > 0)
            {
                $attrConsumingServiceNode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AttributeConsumingService');
                $attrConsumingServiceNode->setAttribute('index', '0');
                $e->appendChild($attrConsumingServiceNode);
                /**
                 * set servicename, servicedesc based on in order mdui, md
                 */
                $sericenames = $this->getServicePartsToArray('DisplayName');
                foreach ($sericenames as $k => $v)
                {
                    $srvnameNode = $attrConsumingServiceNode->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceName');
                    $srvnameNode->setAttribute('xml:lang', '' . $k . '');
                    $srvnameNode->appendChild($attrConsumingServiceNode->ownerDocument->createTextNode($v));
                    $attrConsumingServiceNode->appendChild($srvnameNode);
                }

                $servicenamesDesc = $this->getServicePartsToArray('Description');
                foreach ($servicenamesDesc as $k => $v)
                {
                    $srvdisplay_node = $attrConsumingServiceNode->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceDescription');
                    $srvdisplay_node->setAttribute('xml:lang', '' . $k . '');
                    $srvdisplay_node->appendChild($attrConsumingServiceNode->ownerDocument->createTextNode($v));
                    $attrConsumingServiceNode->appendChild($srvdisplay_node);
                }

                foreach ($sp_reqattrs->getValues() as $v)
                {
                    $attrNode = $attrConsumingServiceNode->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:RequestedAttribute');
                    $attrNode->setAttribute('FriendlyName', $v->getAttribute()->getName());
                    $attrNode->setAttribute('Name', $v->getAttribute()->getOid());
                    $attrNode->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                    if ($v->getStatus() == 'required')
                    {
                        $attrNode->setAttribute('isRequired', 'true');
                    }
                    else
                    {
                        $attrNode->setAttribute('isRequired', 'false');
                    }
                    $attrConsumingServiceNode->appendChild($attrNode);
                }
            }
            else
            {

                if (array_key_exists('fedreqattrs', $options) && is_array($options['fedreqattrs']))
                {
                    $attrConsumingServiceNode = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AttributeConsumingService');
                    $attrConsumingServiceNode->setAttribute('index', '0');
                    $e->appendChild($attrConsumingServiceNode);
                    $t_name = $this->getName();
                    if (empty($t_name))
                    {
                        $t_name = $this->getEntityId();
                    }
                    $srvnameNode = $attrConsumingServiceNode->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceName', $t_name);
                    $srvnameNode->setAttribute('xml:lang', 'en');
                    $attrConsumingServiceNode->appendChild($srvnameNode);
                    $tDisplayname = $this->getDisplayName();
                    if (!empty($tDisplayname))
                    {
                        $srvdisplay_node = $attrConsumingServiceNode->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ServiceDescription', $tDisplayname);
                        $srvdisplay_node->setAttribute('xml:lang', 'en');
                        $attrConsumingServiceNode->appendChild($srvdisplay_node);
                    }
                    foreach ($options['fedreqattrs'] as $v)
                    {
                        $attrNode = $attrConsumingServiceNode->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:RequestedAttribute');
                        $attrNode->setAttribute('FriendlyName', $v->getAttribute()->getName());
                        $attrNode->setAttribute('Name', $v->getAttribute()->getOid());
                        $attrNode->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                        if ($v->getStatus() == 'required')
                        {
                            $attrNode->setAttribute('isRequired', 'true');
                        }
                        else
                        {
                            $attrNode->setAttribute('isRequired', 'false');
                        }
                        $attrConsumingServiceNode->appendChild($attrNode);
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
        log_message('debug', __METHOD__ . ' start:  ' . $this->entityid);
        $comment = "\"" . $this->getEntityId() . "\" \n";
        $s_metadata = null;
        $valid_until = null;
        $p_validUntil = $this->getValidTo();
        if (!empty($p_validUntil))
        {
            $valid_until = $p_validUntil->format('Y-m-d\TH:i:s\Z');
        }
        if ($this->is_static)
        {
            $static_meta = $this->getStaticMetadata();
            if (!empty($static_meta))
            {
                $s_metadata = $this->getStaticMetadata()->getMetadata();
                $comment .= "static meta\n";
            }
            else
            {
                log_message('error', __METHOD__ . ': Static metadata is enabled but empty for entity (id: ' . $this->id . '):' . $this->entityid);
                return null;
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
                $node = $static_meta->getMetadataToXML();
                if (!empty($node))
                {
                    $node = $docXML->importNode($node, true);
                    $docXML->appendChild($node);
                }
                else
                {
                    \log_message('error', __METHOD__ . ' ' . $this->entityid . ' : static is enabled but cant import into domnode');
                    return null;
                }
                return $docXML;
            }

            $EntityDesc_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntityDescriptor');
            if (!empty($valid_until))
            {
                $EntityDesc_Node->setAttribute('validUntil', $valid_until);
            }
            $docXML->appendChild($EntityDesc_Node);
        }
        else
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
                }
                else
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
        if ($this->is_local && function_exists('customGenerateEntityDescriptorID'))
        {
            $cid = customGenerateEntityDescriptorID(array('id' => '' . $this->getId() . '', 'entityid' => '' . $this->getEntityId() . ''));
            if (!empty($cid))
            {

                $EntityDesc_Node->setAttribute('ID', $cid);
            }
        }
        $ci = & get_instance();
        if (!empty($this->registrar))
        {
            \log_message('debug', 'GKS not empty registrar');
            $EntExtension_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
            $EntityDesc_Node->appendChild($EntExtension_Node);
            $RegistrationInfo_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:RegistrationInfo');
            $RegistrationInfo_Node->setAttribute('registrationAuthority', htmlspecialchars($this->registrar));
            if (!empty($this->registerdate))
            {
                $RegistrationInfo_Node->setAttribute('registrationInstant', $this->registerdate->format('Y-m-d') . 'T' . $this->registerdate->format('H:i:s') . 'Z');
            }
            $EntExtension_Node->appendChild($RegistrationInfo_Node);
        }
        elseif ($this->is_local === TRUE)
        {
            $configRegistrar = $ci->config->item('registrationAutority');
            $configRegistrationPolicy = $ci->config->item('registrationPolicy');
            $configRegistrarLoad = $ci->config->item('load_registrationAutority');
            if (!empty($configRegistrarLoad) && !empty($configRegistrar))
            {
                $EntExtension_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
                $EntityDesc_Node->appendChild($EntExtension_Node);
                $RegistrationInfo_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:RegistrationInfo');
                $RegistrationInfo_Node->setAttribute('registrationAuthority', $configRegistrar);
                if (!empty($this->registerdate))
                {
                    $RegistrationInfo_Node->setAttribute('registrationInstant', $this->registerdate->format('Y-m-d') . 'T' . $this->registerdate->format('H:i:s') . 'Z');
                }
                $EntExtension_Node->appendChild($RegistrationInfo_Node);
            }
        }


        $cocs = $this->getCoc();
        $entityCategories = array();
        $registrationPolicies = array();
        foreach ($cocs as $k => $v)
        {
            \log_message('debug', 'GKS provider model coc');
            $cocsenabled = $v->getAvailable();
            $cocstype = $v->getType();
            $cocsubtype = $v->getSubtype();
            if ($cocsenabled === TRUE)
            {
                if ($cocstype === 'entcat' && !empty($cocsubtype))
                {
                    $entityCategories['' . $cocsubtype . ''][] = $v;
                }
                elseif ($cocstype === 'regpol')
                {
                    $registrationPolicies[] = $v;
                    \log_message('debug', 'GKS provider model entcat: ' . $v->getUrl());
                }
            }
        }

        if (!empty($RegistrationInfo_Node))
        {
            if (count($registrationPolicies) > 0)
            {
                \log_message('debug', 'GKS provider generating XML for entcat');
                $langsset = array();
                foreach ($registrationPolicies as $v)
                {
                    $vlang = $v->getLang();
                    if (in_array($vlang, $langsset))
                    {
                        \log_message('error', __METHOD__ . ' multiple registration policies are set for lang:' . $vlang . ' for entityId: ' . $this->entityid);
                        continue;
                    }
                    $langsset[] = $vlang;
                    $RegPolicyNode = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:RegistrationPolicy');
                    $RegPolicyNode->setAttribute('xml:lang', $v->getLang());
                    $RegPolicyNode->appendChild($RegistrationInfo_Node->ownerDocument->createTextNode($v->getUrl()));
                    \log_message('debug', 'GKS .. add entvcat to xml: ' . $v->getLang() . ': ' . $v->getUrl());
                    $RegistrationInfo_Node->appendChild($RegPolicyNode);
                }
                unset($langsset);
            }
            elseif ($this->is_local === TRUE && empty($this->registrar) && !empty($configRegistrationPolicy) && !empty($configRegistrarLoad))
            {
                $RegPolicyNode = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:RegistrationPolicy');
                $RegPolicyNode->setAttribute('xml:lang', 'en');
                $RegPolicyNode->appendChild($EntityDesc_Node->ownerDocument->createTextNode($configRegistrationPolicy));
                $RegistrationInfo_Node->appendChild($RegPolicyNode);
            }
        }

        if (count($entityCategories) > 0)
        {
            $AttributesGroup_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:metadata:attribute', 'mdattr:EntityAttributes');
            $EntExtension_Node->appendChild($AttributesGroup_Node);
            foreach ($entityCategories as $key => $value)
            {
                $Attribute_Node = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Attribute');
                $Attribute_Node->setAttribute('Name', '' . $key . '');
                $Attribute_Node->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri');
                $AttributesGroup_Node->appendChild($Attribute_Node);
                foreach ($value as $v)
                {
                    $Attribute_Value = $EntityDesc_Node->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AttributeValue');
                    $Attribute_Value->appendChild($Attribute_Node->ownerDocument->createTextNode($v->getUrl()));
                    $Attribute_Node->appendChild($Attribute_Value);
                }
            }
        }

        if ($this->type !== 'SP')
        {
            $SSODesc_Node = $this->getIDPSSODescriptorToXML($EntityDesc_Node);
            if (!empty($SSODesc_Node))
            {
                $EntityDesc_Node->appendChild($SSODesc_Node);
            }
            else
            {
                \log_message('error', __FILE__ . ' line ' . __LINE__ . ' ' . __METHOD__ . "Provider model: IDP/BOTH type but IDPSSODescriptor is null. Metadata for " . $this->getEntityId() . " couldnt be generated");
                return null;
            }
            $AA_Node = $this->getIDPAADescriptorToXML($EntityDesc_Node);
            if (!empty($AA_Node))
            {
                $EntityDesc_Node->appendChild($AA_Node);
            }
        }
        if ($this->type !== 'IDP')
        {

            $SSODesc_Node = $this->getSPSSODescriptorToXML($EntityDesc_Node, $options);
            if (!empty($SSODesc_Node))
            {
                $EntityDesc_Node->appendChild($SSODesc_Node);
            }
            else
            {
                \log_message('error', ' line ' . __LINE__ . ' ' . __METHOD__ . ' SP/BOTH type but SPSSODescriptor is null cant genereate metadata for: ' . $this->entityid);
                return null;
            }
        }

        $Organization_Node = $this->getOrganizationToXML($EntityDesc_Node);
        if ($Organization_Node !== null && $Organization_Node->hasChildNodes())
        {
            $EntityDesc_Node->appendChild($Organization_Node);
        }
        $contacts = $this->getContacts();

        foreach ($contacts as $v)
        {
            $Contact_Node = $v->getContactToXML($EntityDesc_Node);
            $EntityDesc_Node->appendChild($Contact_Node);
        }

        if ($parent === NULL)
        {
            return $docXML;
        }
        else
        {
            $parent->appendChild($EntityDesc_Node);
            return $EntityDesc_Node;
        }
    }

    /**
     *
     * extensions inside IDPSSODEscriptor (idp) or SPSODescriptor (sp)
     */
    private function ssoDescriptorExtensionsFromArray($ext, $type)
    {
        $parentUIInfo = new ExtendMetadata;
        $parentUIInfo->setNamespace('mdui');
        $parentUIInfo->setElement('UIInfo');
        $parentUIInfo->setAttributes(array());
        $parentUIInfo->setType($type);
        $parentUIInfo->setProvider($this);
        $this->setExtendMetadata($parentUIInfo);


        if (array_key_exists('scope', $ext))
        {
            $this->setScope('idpsso', $ext['scope']);
        }
        if (array_key_exists('aascope', $ext))
        {
            $this->setScope('aa', $ext['aascope']);
        }
        if (array_key_exists('geo', $ext) && is_array($ext['geo']))
        {
            \log_message('debug', __METHOD__ . ': geo');
            $parentgeo = new ExtendMetadata;
            $parentgeo->setNamespace('mdui');
            $parentgeo->setElement('DiscoHints');
            $parentgeo->setAttributes(array());
            $parentgeo->setType($type);
            $parentgeo->setProvider($this);
            $this->setExtendMetadata($parentgeo);
            foreach ($ext['geo'] as $g)
            {
                $geo = new ExtendMetadata;
                $geo->setGeoLocation('' . $g[0] . ',' . $g[1] . '', $this, $parentgeo, $type);
                $geo->setProvider($this);
                $this->setExtendMetadata($geo);
            }
        }
        if (array_key_exists('desc', $ext) && is_array($ext['desc']))
        {
            foreach ($ext['desc'] as $k => $p)
            {
                $extdesc = new ExtendMetadata;
                $extdesc->setNamespace('mdui');
                $extdesc->setType($type);
                $extdesc->setElement('Description');
                $extdesc->setValue($p['val']);
                $extdesc->setAttributes(array('xml:lang' => $p['lang']));
                $extdesc->setProvider($this);
                $this->setExtendMetadata($extdesc);
                $extdesc->setParent($parentUIInfo);
            }
        }
        if (array_key_exists('displayname', $ext) && is_array($ext['displayname']))
        {
            foreach ($ext['displayname'] as $k => $p)
            {
                $extdesc = new ExtendMetadata;
                $extdesc->setNamespace('mdui');
                $extdesc->setType($type);
                $extdesc->setElement('DisplayName');
                $extdesc->setValue($p['val']);
                $extdesc->setAttributes(array('xml:lang' => $p['lang']));
                $extdesc->setProvider($this);
                $this->setExtendMetadata($extdesc);
                $extdesc->setParent($parentUIInfo);
            }
        }
        if (array_key_exists('privacyurl', $ext) && is_array($ext['privacyurl']))
        {
            foreach ($ext['privacyurl'] as $k => $p)
            {
                $extdesc = new ExtendMetadata;
                $extdesc->setNamespace('mdui');
                $extdesc->setType($type);
                $extdesc->setElement('PrivacyStatementURL');
                $extdesc->setValue($p['val']);
                $extdesc->setAttributes(array('xml:lang' => $p['lang']));
                $extdesc->setProvider($this);
                $this->setExtendMetadata($extdesc);
                $extdesc->setParent($parentUIInfo);
            }
        }
        if (array_key_exists('informationurl', $ext) && is_array($ext['informationurl']))
        {
            foreach ($ext['informationurl'] as $k => $p)
            {
                $extdesc = new ExtendMetadata;
                $extdesc->setNamespace('mdui');
                $extdesc->setType($type);
                $extdesc->setElement('InformationURL');
                $extdesc->setValue($p['val']);
                $extdesc->setAttributes(array('xml:lang' => $p['lang']));
                $extdesc->setProvider($this);
                $this->setExtendMetadata($extdesc);
                $extdesc->setParent($parentUIInfo);
            }
        }
        if (array_key_exists('logo', $ext) && is_array($ext['logo']))
        {
            \log_message('debug', 'GK logo provider');
            foreach ($ext['logo'] as $k => $p)
            {
                $extdesc = new ExtendMetadata;
                $extdesc->setLogo($p['val'], $this, $parentUIInfo, array('width' => $p['width'], 'height' => $p['height'], 'xml:lang' => $p['xml:lang']), $type);
                $this->setExtendMetadata($extdesc);
            }
        }
        if ($type == 'sp')
        {
            if (array_key_exists('idpdisc', $ext) && is_array($ext['idpdisc']))
            {
                foreach ($ext['idpdisc'] as $idpdiscs)
                {
                    $disc = new ServiceLocation;
                    $disc->setDiscoveryResponse($idpdiscs['url'], @$idpdiscs['order']);
                    $disc->setProvider($this);
                    $this->setServiceLocation($disc);
                }
            }
            if (array_key_exists('init', $ext) && is_array($ext['init']))
            {
                foreach ($ext['init'] as $inits)
                {
                    $rinit = new ServiceLocation;
                    $rinit->setRequestInitiator($inits['url']);
                    $rinit->setProvider($this);
                    $this->setServiceLocation($rinit);
                }
            }
        }
    }

    private function aaDescriptorFromArray($b)
    {
        if (array_key_exists('protocols', $b))
        {
            $this->setProtocolSupport('aa', $b['protocols']);
        }
        if (array_key_exists('extensions', $b))
        {
            $this->ssoDescriptorExtensionsFromArray($b['extensions'], 'aa');
        }
        if (array_key_exists('nameid', $b) && is_array($b['nameid']))
        {
            $this->setNameIds('aa', $b['nameid']);
        }
        if (array_key_exists('attributeservice', $b))
        {
            foreach ($b['attributeservice'] as $aval)
            {
                $aa = new ServiceLocation;
                $aa->setType('IDPAttributeService');
                $aa->setBindingName($aval['binding']);
                $aa->setUrl($aval['location']);
                $aa->setProvider($this);
                $this->setServiceLocation($aa);
            }
        }
        if (array_key_exists('certificate', $b))
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

                $cert->setType('aa');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname']))
                {
                    if (is_array($c['keyname']))
                    {
                        $cert->setKeyname(implode(',', $c['keyname']));
                    }
                    else
                    {
                        $cert->setKeyname($c['keyname']);
                    }
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }
    }

    private function idpSSODescriptorFromArray($b)
    {
        if (array_key_exists('extensions', $b))
        {
            $this->ssoDescriptorExtensionsFromArray($b['extensions'], 'idp');
        }

        if (array_key_exists('nameid', $b) && is_array($b['nameid']))
        {
            $this->setNameIds('idpsso', $b['nameid']);
        }
        if (array_key_exists('servicelocations', $b))
        {
            $tmpsrcl = array('singlesignonservice' => 'SingleSignOnService', 'singlelogout' => 'IDPSingleLogoutService', 'artifactresolutionservice' => 'IDPArtifactResolutionService');
            foreach ($tmpsrcl as $kc => $vc)
            {
                if (isset($b['servicelocations']['' . $kc . '']) && is_array($b['servicelocations']['' . $kc . '']))
                {
                    foreach ($b['servicelocations']['' . $kc . ''] as $s)
                    {
                        $sso = new ServiceLocation;
                        $sso->setType($vc);
                        $sso->setBindingName($s['binding']);
                        $sso->setUrl($s['location']);
                        if ($vc === 'IDPArtifactResolutionService')
                        {
                            $sso->setOrder($s['order']);
                        }
                        $sso->setProvider($this);
                        $this->setServiceLocation($sso);
                    }
                }
            }
        }
        $this->setProtocolSupport('idpsso', $b['protocols']);
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

                $cert->setType('idpsso');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname']))
                {
                    if (is_array($c['keyname']))
                    {
                        $cert->setKeyname(implode(',', $c['keyname']));
                    }
                    else
                    {
                        $cert->setKeyname($c['keyname']);
                    }
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }
        return $this;
    }

    private function spSSODescriptorFromArray($b)
    {
        if (array_key_exists('extensions', $b))
        {
            $this->ssoDescriptorExtensionsFromArray($b['extensions'], 'sp');
        }
        if (array_key_exists('nameid', $b) && is_array($b['nameid']))
        {
            $this->setNameIds('spsso', $b['nameid']);
        }
        if (array_key_exists('protocols', $b))
        {
            $this->setProtocolSupport('spsso', $b['protocols']);
        }
        if (isset($b['servicelocations']['assertionconsumerservice']) && is_array($b['servicelocations']['assertionconsumerservice']))
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
        if (isset($b['servicelocations']['artifactresolutionservice']) && is_array($b['servicelocations']['artifactresolutionservice']))
        {

            foreach ($b['servicelocations']['artifactresolutionservice'] as $s)
            {
                $sso = new ServiceLocation;
                $sso->setType('SPArtifactResolutionService');
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

        if (isset($b['servicelocations']['singlelogout']) && is_array($b['servicelocations']['singlelogout']))
        {

            foreach ($b['servicelocations']['singlelogout'] as $s)
            {
                $slo = new ServiceLocation;
                $slo->setType('SPSingleLogoutService');
                $slo->setBindingName($s['binding']);
                $slo->setUrl($s['location']);
                $slo->setProvider($this);
                $this->setServiceLocation($slo);
            }
        }
        if (array_key_exists('certificate', $b) && is_array($b['certificate']))
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
                $cert->setType('spsso');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname']))
                {
                    if (is_array($c['keyname']))
                    {
                        $cert->setKeyname(implode(',', $c['keyname']));
                    }
                    else
                    {
                        $cert->setKeyname($c['keyname']);
                    }
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }
        return $this;
    }

    public function setReqAttrsFromArray($ent, $attributesByName)
    {
        if (isset($ent['details']['reqattrs']))
        {
            \log_message('info','DI1');
            $attrsset = array();
            foreach ($ent['details']['reqattrs'] as $r)
            {
                if (array_key_exists($r['name'], $attributesByName))
                {
                    if (!in_array($r['name'], $attrsset))
                    {
                        $reqattr = new AttributeRequirement;
                        $reqattr->setAttribute($attributesByName['' . $r['name'] . '']);
                        $reqattr->setType('SP');
                        $reqattr->setSP($this);
                        if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0)
                        {
                            $reqattr->setStatus('required');
                        }
                        else
                        {
                            $reqattr->setStatus('desired');
                        }
                        $reqattr->setReason('');
                        $this->setAttributesRequirement($reqattr);
                       // $this->em->persist($reqattr);
                        $attrsset[] = $r['name'];
                    }
                }
                else
                {
                    log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
                }
            }
        }
        return $this;
    }

    public function setProviderFromArray($a, $full = FALSE)
    {
        if (!is_array($a))
        {
            return null;
        }
        $this->setType($a['type']);
        $this->setEntityId($a['entityid']);
        if (!empty($a['coc']))
        {
            /**
             * @todo set CodeOfConduct
             */
        }
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
                $this->setRegistrationDate(\DateTime::createFromFormat('Y-m-d H:i:s', $p[0] . ' ' . $ptime));
            }
        }
        if ($full & !empty($a['regpol']))
        {
            foreach ($a['regpol'] as $v)
            {
                \log_message('debug', 'GKS SS regpollll');
                $b = $this->em->getRepository("models\Coc")->findOneBy(array('type' => 'regpol', 'is_enabled' => true, 'lang' => $v['lang'], 'url' => $v['url']));
                if (!empty($b))
                {
                    $this->setCoc($b);
                }
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
            foreach ($a['details']['org'] as $k => $o)
            {
                if ($k === 'OrganizationName')
                {
                    $lorgname = array();
                    foreach ($o as $k1 => $v1)
                    {
                        if ($k1 === 'en')
                        {
                            $this->setName($v1);
                        }
                        else
                        {
                            $lorgname['' . $k1 . ''] = $v1;
                        }
                    }
                    $this->setLocalName($lorgname);
                }
                elseif ($k === 'OrganizationDisplayName')
                {
                    $lorgname = array();
                    foreach ($o as $k1 => $v1)
                    {
                        if ($k1 === 'en')
                        {
                            $this->setDisplayName($v1);
                        }
                        else
                        {
                            $lorgname['' . $k1 . ''] = $v1;
                        }
                    }
                    $this->setLocalDisplayName($lorgname);
                }
                elseif ($k === 'OrganizationURL')
                {
                    $lorgname = array();
                    foreach ($o as $k1 => $v1)
                    {
                        if ($k1 === 'en')
                        {
                            $this->setHelpdeskUrl($v1);
                        }
                        else
                        {
                            $lorgname[$k1] = $v1;
                        }
                    }
                    $this->setLocalHelpdeskUrl($lorgname);
                }
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
            if ($a['type'] !== 'SP')
            {
                if (array_key_exists('idpssodescriptor', $a['details']))
                {

                    $this->idpSSODescriptorFromArray($a['details']['idpssodescriptor']);
                }
                if (array_key_exists('aadescriptor', $a['details']))
                {
                    \log_message('debug', 'GKL import aa');

                    $this->aaDescriptorFromArray($a['details']['aadescriptor']);
                }
            }
            if ($a['type'] !== 'IDP')
            {
                if (array_key_exists('spssodescriptor', $a['details']))
                {
                    $this->spSSODescriptorFromArray($a['details']['spssodescriptor']);
                }
            }
        }
        return $this;
    }

}
