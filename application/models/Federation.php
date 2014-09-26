<?php

namespace models;

use \Doctrine\Common\Collections\ArrayCollection,
    \Doctrine\ORM\Events;

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
 * Federation Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Federation Model
 *
 * This model for federations definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="federation")
 * @author janusz
 */
class Federation {

    protected $fedmembers;

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=128, nullable=false, unique=true)
     */
    protected $name;

    /**
     * @Column(type="string", length=128, nullable=true, unique=true)
     */
    protected $sysname;

    /**
     * @Column(type="string", length=255, nullable=false, unique=true)
     */
    protected $urn;

    /**
     * @Column(type="string", length=512, nullable=true, unique=false)
     */
    protected $publisher;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $is_active;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $is_protected;

    /**
     * @Column(name="is_public",type="boolean", nullable=false)
     */
    protected $is_public;

    /**
     * if true then additional metadata can be generated with local only entities
     * example usecase - export metadata for Edugain
     *
     * @Column(name="is_lexport", type="boolean", nullable=false)
     */
    protected $is_lexport = FALSE;

    /**
     * set if federation is localy created or external like edugain
     * @Column(name="is_local",type="boolean", nullable=false)
     */
    protected $is_local;

    /**
     * @Column(name="digest", type="string",length=10, nullable=true)
     */
    protected $digest;

    /**
     * @Column(name="digestexport", type="string",length=10, nullable=true)
     */
    protected $digestexport;

    /**
     * add attribute requirements into generated metadata
     * @Column(name="attrreq_inmeta", type="boolean", nullable=false)
     */
    protected $attrreq_inmeta = FALSE;

    /**
     * optional terms of use for federation it can be included in metadata as a comment
     * @Column(name="tou",type="text", nullable=true)
     */
    protected $tou;


    /**
     * @Column(name="usealtmetaurl",type="boolean", nullable=false)
     */
    protected $usealtmetaurl;
    
    /**
     * @Column(name="altmetaurl",type="string" , length=512, nullable=true)
     */
    protected $altmetaurl;

    /**
     * @OneToMany(targetEntity="AttributeRequirement",mappedBy="fed_id",cascade={"persist","remove"})
     */
    protected $attributeRequirement;

    /**
     * ManyToMany(targetEntity="Provider", mappedBy="federations", indexBy="entityid")
     * JoinTable(name="federation_members" )
     * OrderBy({"name"="ASC"})
     * var eProvider[]
     */
    //protected $members;

    /**
     * @OneToMany(targetEntity="FederationMembers", mappedBy="federation" ,  cascade={"persist", "remove"})
     */
    protected $membership;

    /**
     * @ManyToMany(targetEntity="FederationCategory", mappedBy="federations")
     * @JoinTable(name="fedcategory_members" )
     */
    protected $categories;

    /**
     * @ManyToMany(targetEntity="Partner", mappedBy="pfederations")
     * @JoinTable(name="federation_partners" )
     */
    protected $partners;

    /**
     * @OneToMany(targetEntity="FederationValidator",mappedBy="federation", cascade={"persist", "remove"})
     */
    protected $fvalidator;

    /**
     * @OneToMany(targetEntity="NotificationList", mappedBy="federation", cascade={"persist", "remove"})
     */
    protected $notifications;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $owner;

    public function __construct()
    {
        $this->membership = new \Doctrine\Common\Collections\ArrayCollection();
        $this->fedmembers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->fvalidator = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributeRequirement = new \Doctrine\Common\Collections\ArrayCollection();
        $this->is_protected = FALSE;
        $this->is_local = TRUE;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setSysname($name)
    {
        $this->sysname = trim($name);
        return $this;
    }

    public function setDigest($a = null)
    {
        $this->digest = $a;
        return $this;
    }

    public function setDigestExport($a = null)
    {
        $this->digestexport = $a;
        return $this;
    }

    public function setUrn($urn)
    {
        $this->urn = $urn;
        return $this;
    }
    
    public function setAltMetaUrl($url=null)
    {
        $this->altmetaurl = $url;
        return $this;
    }
    
    public function setAltMetaUrlEnabled($arg)
    {
        $this->usealtmetaurl = $arg;
        return $this;
    }

    public function setPublisher($publisher = null)
    {
        if (!empty($publisher))
        {
            $this->publisher = trim($publisher);
        }
        else
        {
            $this->publisher = null;
        }
        return $this;
    }

    public function setDescription($description = null)
    {
        $this->description = $description;
        return $this;
    }

    public function setLocalExport($a = FALSE)
    {
        $this->is_lexport = (boolean) $a;
    }

    public function setAsActive()
    {
        $is_active = TRUE;
        $this->setActive($is_active);
        return $this;
    }

    public function setAsDisactive()
    {
        $is_active = FALSE;
        $this->setActive($is_active);
        return $this;
    }

    public function setActive($is_active = null)
    {
        if (!empty($is_active))
        {
            $this->is_active = '1';
        }
        else
        {
            $this->is_active = '0';
        }
        return $this;
    }

    public function setPublic($is_public = null)
    {
        $this->is_public = $is_public;
        return $this;
    }

    public function publish()
    {
        $public = TRUE;
        $this->setPublic($public);
        return $this;
    }

    public function unPublish()
    {
        $public = FALSE;
        $this->setPublic($public);
        return $this;
    }

    public function setProtected($is_protected = null)
    {
        $this->is_protected = $is_protected;
        return $this;
    }

    public function setLocal($l = null)
    {
        if (!empty($l))
        {
            $this->is_local = 1;
        }
        else
        {
            $this->is_local = 0;
        }
    }

    public function setAsLocal()
    {
        $this->is_local = 1;
        return $this;
    }

    public function setAsExternal()
    {
        $this->is_local = 0;
        return $this;
    }

    public function setTou($txt = null)
    {
        $this->tou = $txt;
        return $this;
    }

    public function setOwner($username)
    {
        $this->owner = $username;
        return $this;
    }

    public function setAttributesRequirement(AttributeRequirement $attribute)
    {
        $this->getAttributesRequirement()->add($attribute);
        return $this;
    }

    public function addValidator(FederationValidator $validator)
    {
        $exist = $this->getValidators()->contains($validator);
        if (empty($exist))
        {
            $this->getValidators()->add($validator);
        }
        return $this;
    }

    public function addMember(Provider $provider)
    {
        $doFilter['provider_id'] = array('' . $provider->getId() . '');
        $membership = $this->getMembership()->filter(
                function($entry) use($doFilter)
        {
            return (in_array($doFilter['provider_id']));
        }
        );


        if ($membership->count() == 0)
        {
            $newMembership = new FederationMembers();
            $provider->addMembership($newMembership);
            $this->addMembership($newMembership);
        }
        return $this->getMembers()->toArray();
    }

    public function addCategory(FederationCategory $category)
    {
        $isin = $this->getCategories()->contains($category);
        if (empty($isin))
        {
            $this->getCategories()->add($category);
        }
        return $this;
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

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSysname()
    {
        return $this->sysname;
    }

    public function getLocal()
    {
        return $this->is_local;
    }

    public function getUrn()
    {
        return $this->urn;
    }

    public function getActive()
    {
        return $this->is_active;
    }

    public function getAttrsInmeta()
    {
        return $this->attrreq_inmeta;
    }
    
    public function getAltMetaUrlEnabled()
    {
        return  $this->usealtmetaurl;
    }
    
    public function getAltMetaUrl()
    {
        return $this->altmetaurl;
    }

    public function setAttrsInmeta($r)
    {
        if ($r === TRUE)
        {
            $this->attrreq_inmeta = true;
        }
        elseif ($r === FALSE)
        {
            $this->attrreq_inmeta = false;
        }
    }

    public function getLocalExport()
    {
        return $this->is_lexport;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getValidators()
    {
        return $this->fvalidator;
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
            $membership->setFederation($this);
        }

        return $this;
    }

    public function getMembershipProviders()
    {
        $mem = $this->membership;
        $result = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($mem as $m)
        {
            $result->set($m->getId(), $m->getProvider());
        }
        return $result;
    }

    public function getMembers()
    {
        $mem = $this->membership;
        $result = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($mem as $m)
        {
            if ($m->getJoinState() != 2)
            {
                $result->set($m->getProvider()->getEntityId(), $m->getProvider());
                ;
            }
        }
        return $result;
    }

    public function getActiveMembers()
    {
        $members = new \Doctrine\Common\Collections\ArrayCollection();
        $mem = $this->membership;
        foreach ($mem as $m)
        {
            $isOK = $m->getIsFinalMembership();
            if ($isOK)
            {
                $members->set($m->getProvider()->getEntityId(), $m->getProvider());
            }
        }
        return $members;
    }

    public function getMembersForExport()
    {
        $members = new \Doctrine\Common\Collections\ArrayCollection();
        $mem = $this->membership;
        foreach ($mem as $m)
        {
            $isOK = !(($m->getJoinState() == 3) || ($m->getJoinState() == 2)) && !($m->getIsDisabled() || $m->getIsBanned());
            if ($isOK)
            {
                $members->set($m->getProvider()->getEntityId(), $m->getProvider());
            }
        }
        return $members;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function getNotifications()
    {
        return $this->notifications;
    }

    public function getAttributesRequirement()
    {
        return $this->attributeRequirement;
    }

    public function getPublic()
    {
        return $this->is_public;
    }

    public function getProtected()
    {
        return $this->is_protected;
    }

    public function getTou()
    {
        return $this->tou;
    }

    public function getDigest()
    {
        return $this->digest;
    }

    public function getDigestExport()
    {
        return $this->digestexport;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getPublisher()
    {
        return $this->publisher;
    }

    public function importFromArray(array $r)
    {
        $this->setName($r['name']);
        $this->setSysname($r['sysname']);
        $this->setUrn($r['urn']);
        $this->setDescription($r['description']);
        $this->setActive($r['is_active']);
        $this->setPublic($r['is_public']);
        $this->setProtected($r['is_protected']);
        $this->setLocal($r['is_local']);
        $this->setTou($r['tou']);
        if (isset($r['publisher']))
        {
            $this->setPublisher($r['publisher']);
        }
        return $this;
    }

    public function convertToArray()
    {
        $r = array();
        if (!empty($this->id))
        {
            $r['id'] = $this->id;
        }
        $r['name'] = $this->getName();
        $r['sysname'] = $this->getSysname();
        $r['urn'] = $this->getUrn();
        $r['description'] = $this->getDescription();
        $r['is_active'] = $this->getActive();
        $r['is_public'] = $this->getPublic();
        $r['is_protected'] = $this->getProtected();
        $r['is_local'] = $this->getLocal();
        $r['tou'] = $this->getTou();
        return $r;
    }

    /**
     * @PostLoad
     */
    function createMembersCollection()
    {
        $this->fedmembers = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
