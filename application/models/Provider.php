<?php
namespace models;


    /**
     * @package   Jagger
     * @author    Middleware Team HEAnet
     * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
     * @license   MIT http://www.opensource.org/licenses/mit-license.php
     * This model for Identity and Service Providers definitions
     */

/**
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
     * used for WantAssertionsSigned in SPSSODescriptor
     * @Column(type="boolean", nullable=true)
     */
    protected $wantassertsigned;

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
     * @OneToMany(targetEntity="MetadataRevision", mappedBy="provider", cascade={"persist", "remove"})
     */
    protected $metadatadumps;

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
    protected $createdAt;

    /**
     * @Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    public function __construct() {
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
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->is_approved = true;
        $this->hidepublic = false;
        $this->is_locked = false;
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function __toString() {
        return $this->entityid;
    }

    /**
     * @prePersist
     */
    public function created() {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!isset($this->hidepublic)) {
            $this->hidepublic = false;
        }
        if (empty($this->nameidformat)) {
            $this->setNameId();
        }
        if (empty($this->displayname)) {
            $this->displayname = $this->getName();
        }
    }

    /**
     * @PostPersist
     */
    public function createAclResource() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $is_local = $this->is_local;
        if ($is_local) {
            $rescheck = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $this->id));
            if (!empty($rescheck)) {
                return true;
            }
            $parent = array();

            $parents = $this->em->getRepository("models\AclResource")->findBy(array('resource' => array('idp', 'sp', 'entity')));
            foreach ($parents as $p) {
                $parent[$p->getResource()] = $p;
            }
            $stype = $this->type;
            if ($stype === 'BOTH') {
                $types = array('entity');
            } elseif ($stype === 'IDP') {
                $types = array('idp');
            } else {
                $types = array('sp');
            }
            foreach ($types as $key) {
                $r = new AclResource;
                $resource_name = $this->id;
                $r->setResource($resource_name);
                $r->setDefaultValue('view');
                $r->setType('entity');
                if (array_key_exists($key, $parent)) {
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
    public function unsetOwner() {

    }

    /**
     * @PostRemove
     */
    public function removeRequester() {
        log_message('debug', 'Provider removed, not its time to remove all entries with that requester');
    }

    /**
     * @PreUpdate
     */
    public function updated() {
        \log_message('debug', 'GG update providers updated time for:' . $this->entityid);
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @PostLoad
     */
    public function setAddionals() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    /**
     * @PostLoad
     */
    public function createEmptyFedColl() {
        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setName($name = null) {
        $this->name = $name;

        return $this;
    }

    public function setLocalName(array $name = null) {
        if ($name !== null) {
            foreach ($name as $k => $v) {
                if (empty($v)) {
                    unset($name['' . $k . '']);
                }
            }
            $this->lname = serialize($name);
        } else {
            $this->lname = null;
        }
    }

    public function setDisplayName($name = null) {
        $this->displayname = $name;

        return $this;
    }

    public function setLocalDisplayName($name = null) {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                if (empty($v)) {
                    unset($name['' . $k . '']);
                }
            }
            $this->ldisplayname = serialize($name);
        } else {
            $this->ldisplayname = serialize(array());
        }
    }

    public function setRegistrationPolicyFromArray($regarray, $reset = false) {

        if ($reset === true) {
            $this->regpolicy = serialize($regarray);
        } else {
            $s = $this->getRegistrationPolicy();
            $n = array_merge($s, $regarray);
            $this->regpolicy = serialize($n);
        }

        return $this;
    }

    public function setRegistrationPolicy($jlang, $url) {
        $s = $this->getRegistrationPolicy();
        $s['' . $jlang . ''] = $url;
        $this->regpolicy = serialize($s);

        return $this;
    }

    public function resetRegistrationPolicy() {
        $this->regpolicy = serialize(array());

        return $this;
    }


    public function setWantAssertionSigned($b) {
        if ($b === true) {
            $this->wantassertsigned = true;
        } else {
            $this->wantassertsigned = false;
        }
        return $this;
    }


    /**
     * type : idpsso, aa
     * $scope: array();
     */
    public function setScope($type, $scope) {
        $ex = @unserialize($this->scope);
        if ($ex === 'b:0;' || $ex !== false) {
            $ex['' . $type . ''] = $scope;
        } else {
            $ex = array();
            $ex['' . $type . ''] = $scope;
        }
        $this->scope = serialize($ex);

        return $this;
    }

    private function overwriteScopeFull(Provider $provider) {
        $pScope = $provider->getScopeFull();
        if (!isset($pScope['idpsso'])) {
            $pScope['idpsso'] = array();
        }
        if (!isset($pScope['aa'])) {
            $pScope['aa'] = array();
        }
        foreach ($pScope as $k => $v) {
            if ($k === 'idpsso' || $k === 'aa') {
                $this->setScope($k, $v);
            }
        }

        return $this;
    }

    public function overwriteScope($n, Provider $provider) {
        $this->setScope($n, $provider->getScope($n));

        return $this;
    }

    public function setEntityId($entity) {
        $entity = trim($entity);
        if (!empty($entity)) {
            $this->entityid = $entity;

            return $this;
        } else {
            return false;
        }
    }

    public function setDigest($a = null) {
        $this->digest = $a;

        return $this;
    }

    public function setCountry($country = null) {
        if (!empty($country)) {
            $this->country = $country;
        }
    }

    /**
     * obsolete
     */
    public function resetNameId() {
        $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();

        return $this;
    }

    /**
     * obsolete
     */
    public function setNameId($nameid = null) {
        if (empty($nameid)) {
            $nameid = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
        }
        if (empty($this->nameidformat)) {
            $this->nameidformat = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->nameidformat->add($nameid);

        return $this;
    }

    /**
     * new
     */
    public function setNameIds($n, $data) {
        $t = $this->getNameIds();
        $t['' . $n . ''] = $data;
        $this->nameids = serialize($t);

        return $this;
    }

    public function setVisiblePublic() {
        $this->hidepublic = false;

        return $this;
    }

    public function getMembership() {
        return $this->membership;
    }

    public function addMembership(FederationMembers $membership) {
        if (!$this->membership->contains($membership)) {
            $this->membership->add($membership);
            $membership->setProvider($this);
        }

        return $this;
    }

    public function setHidePublic() {
        $this->hidepublic = true;

        return $this;
    }

    public function resetProtocol() {
        $this->protocol = new \Doctrine\Common\Collections\ArrayCollection();

        return $this;
    }

    public function setProtocol($protocol = null) {
        if (empty($protocol)) {
            $protocol = 'urn:oasis:names:tc:SAML:2.0:protocol';
        }
        if (empty($this->protocol)) {
            $this->protocol = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $this->protocol->add($protocol);

        return $this;
    }

    public function setProtocolSupport($n, $data) {
        $allowed = array('aa', 'idpsso', 'spsso');
        if (is_array($data) && in_array($n, $allowed)) {
            foreach ($data as $k => $v) {
                $i = trim($v);
                if (empty($i)) {
                    unset($data['' . $k . '']);
                }
            }
            $r = $this->getProtocolSupport();
            $r['' . $n . ''] = $data;
            $this->protocolsupport = serialize($r);
        }

        return $this;
    }

    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * setting entity as SP
     */
    public function setSP() {
        $this->type = 'SP';

        return $this;
    }

    public function setAsSP() {
        $this->type = 'SP';

        return $this;
    }

    /**
     * setting entity as IDP
     */
    public function setIDP() {
        $this->type = 'IDP';

        return $this;
    }

    public function setAsIDP() {
        $this->type = 'IDP';

        return $this;
    }

    public function setAsBoth() {
        $this->type = 'BOTH';

        return $this;
    }

    public function setHelpdeskUrl($url = null) {
        $this->helpdeskurl = $url;

        return $this;
    }

    public function setLocalHelpdeskUrl($urls = null) {
        if (!empty($urls) && is_array($urls)) {
            foreach ($urls as $k => $v) {
                if (empty($v)) {
                    unset($urls['' . $k . '']);
                }
            }
            $this->lhelpdeskurl = serialize($urls);
        } else {
            $this->lhelpdeskurl = null;
        }

        return $this;
    }

    public function setPrivacyUrl($url = null) {
        $this->privacyurl = $url;

        return $this;
    }

    public function setLocalPrivacyUrl(array $url = null) {
        if (!empty($url)) {
            $this->lprivacyurl = serialize($url);
        } else {
            $this->lprivacyurl = null;
        }

        return $this;
    }

    public function setRegistrationAuthority($reg = null) {
        $this->registrar = $reg;
        if ($reg !== null && trim($reg) === '') {
            $this->registrar = null;
        }

        return $this;
    }

    public function setRegistrationDate(\DateTime $date = null) {
        if ($date === null) {
            $this->registerdate = null;
        } else {
            $this->registerdate = $date->setTimezone(new \DateTimeZone('UTC'));
        }

        return $this;
    }

    /**
     * set time entity is valid to, if null then current time
     */
    public function setValidTo(\DateTime $date = null) {
        if ($date === null) {
            $this->validto = null;
        } else {
            $this->validto = $date->setTimezone(new \DateTimeZone('UTC'));
        }

        return $this;
    }

    /**
     * @param \DateTime|NULL $date
     * @return $this
     */
    public function setValidFrom(\DateTime $date = null) {
        if ($date === null) {
            $this->validfrom = null;
        } else {
            $this->validfrom = $date->setTimezone(new \DateTimeZone('UTC'));
        }

        return $this;
    }

    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * updateLocalizedMdui1 for elements: Description, DisplayName, PrivacyURL, InformationURL
     */
    public function updateLocalizedMdui1($elementName, $descriptions, $type) {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $ex = $this->getExtendMetadata();
        $parent = null;
        foreach ($ex as $e) {
            if (!empty($parent)) {
                break;
            } else {
                if (empty($p) && $e->getType() === $type && $e->getNameSpace() === 'mdui' && $e->getElement() === 'UIInfo') {
                    $parent = $e;
                }
            }
        }
        foreach ($ex as $e) {
            $origElementName = $e->getElement();
            $origNameSpace = $e->getNameSpace();
            if ($origElementName === $elementName && $e->getType() === $type && $origNameSpace === 'mdui') {
                $t = $e->getAttributes();
                $lvalue = $t['xml:lang'];
                if (array_key_exists($lvalue, $descriptions) && !empty($descriptions[$lvalue])) {
                    $e->setValue($descriptions[$lvalue]);
                } else {
                    $ex->removeElement($e);
                    $this->em->remove($e);
                }
                unset($descriptions[$lvalue]);
            }
        }

        foreach ($descriptions as $k => $v) {
            $nelement = new ExtendMetadata();
            $nelement->setType($type);
            $nelement->setNameSpace('mdui');
            $nelement->setElement($elementName);
            $nelement->setValue($v);
            $attr = array('xml:lang' => $k);
            $nelement->setAttributes($attr);
            if ($parent === null) {
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

    public function setWayfList($wayflist = null) {
        if (is_array($wayflist)) {
            $this->wayflist = serialize($wayflist);
        }
    }

    public function setExcarps($excarps = null) {
        if (is_array($excarps) && count($excarps) > 0) {
            $this->excarps = serialize($excarps);
        } else {
            $this->excarps = null;
        }

        return $this;
    }

    public function setDefaultState() {
        $this->is_approved = 1;
        $this->is_active = 1;
        $this->is_locked = 0;
        $this->is_static = 0;
        $this->is_local = 1;
        $this->validfrom = null;
        $this->validto = null;

        return $this;
    }

    public function setLocal($isLocal) {
        if ($isLocal === true) {
            $this->is_local = true;
        } else {
            $this->is_local = false;
        }

        return $this;
    }

    public function setAsLocal() {
        $this->is_local = 1;

        return $this;
    }

    public function setAsExternal() {
        $this->is_local = 0;

        return $this;
    }

    public function setActive($val = null) {
        if (!empty($val)) {
            $this->is_active = 1;
        } else {
            $this->is_active = 0;
        }

        return $this;
    }

    public function Disactivate() {
        $this->is_active = 0;
    }

    public function Activate() {
        $this->is_active = 1;
    }

    public function Lock() {
        $this->is_locked = 1;
    }

    public function Unlock() {
        $this->is_locked = 0;
    }

    public function setApproved($val = null) {
        if (!empty($val)) {
            $this->is_approved = 1;
        } else {
            $this->is_approved = 0;
        }

        return $this;
    }

    public function setFederation(Federation $federation) {
        $doFilter['federation_id'] = array('' . $federation->getId() . '');
        $membership = $this->getMembership()->filter(
            function (FederationMembers $entry) use ($doFilter) {
                return (in_array($entry->getFederation()->getId(), $doFilter['federation_id']));
            }
        );


        if ($membership->count() == 0) {
            $newMembership = new FederationMembers();
            $federation->addMembership($newMembership);
            $this->addMembership($newMembership);
        }

        return $this->getFederations();
    }

    public function removeFederation(Federation $federation) {

        $doFilter['federation_id'] = array('' . $federation->getId() . '');
        $membership = $this->getMembership()->filter(
            function (FederationMembers $entry) use ($doFilter) {
                return (in_array($entry->getFederation()->getId(), $doFilter['federation_id']));
            }
        );


        foreach ($membership as $m) {
            $this->removeMembership($m);
        }

        return $this->getFederations();
    }

    public function removeMembership(FederationMembers $membership) {
        if ($this->membership->contains($membership)) {
            $this->membership->removeElement($membership);
        }

        return $this;
    }

    public function setServiceLocation(ServiceLocation $service) {
        $this->getServiceLocations()->add($service);
        $service->setProvider($this);

        return $this->serviceLocations;
    }

    public function setExtendMetadata(ExtendMetadata $ext) {
        $this->getExtendMetadata()->add($ext);
        $ext->setProvider($this);

        return $this->extend;
    }

    public function removeServiceLocation(ServiceLocation $service) {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->getServiceLocations()->removeElement($service);
        $this->em->remove($service);

        return $this->serviceLocations;
    }

    public function removeCoc(Coc $coc) {
        $this->getCoc()->removeElement($coc);
        $coc->getProviders()->removeElement($this);

        return $this;
    }

    public function setCoc(Coc $coc) {
        $this->getCoc()->add($coc);
        $coc->getProviders()->add($this);

        return $this;
    }

    public function setStatic($static) {
        if ($static === true) {
            $this->is_static = true;
        } else {
            $this->is_static = false;
        }

        return $this;
    }

    public function setStaticMetadata(StaticMetadata $metadata) {
        $this->metadata = $metadata;
        $metadata->setProvider($this);

        return $this;
    }

    public function overwriteStaticMetadata(StaticMetadata $metadata = null) {
        $m = $this->getStaticMetadata();
        if (!empty($m)) {
            $m->setMetadata($metadata->getMetadata());
        } else {
            $this->setStaticMetadata($metadata);
        }

        return $this;
    }

    public function setAttributesRequirement(AttributeRequirement $attribute) {
        $this->getAttributesRequirement()->add($attribute);

        return $this;
    }

    public function setContact(Contact $contact) {
        $this->getContacts()->add($contact);
        $contact->setProvider($this);

        return $this->contacts;
    }

    public function removeCertificate(Certificate $certificate) {
        $this->getCertificates()->removeElement($certificate);
        $certificate->unsetProvider($this);

        return $this->certificates;
    }

    public function removeContact(Contact $contact) {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->getContacts()->removeElement($contact);
        $this->em->remove($contact);

        return $this->contacts;
    }

    public function removeAllContacts() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $contacts = $this->getContacts();
        foreach ($contacts->getValues() as $contact) {
            $contacts->removeElement($contact);
            $this->em->remove($contact);
        }

        return $this;
    }

    public function setCertificate(Certificate $certificate) {
        $this->getCertificates()->add($certificate);
        $certificate->setProvider($this);

        return $this->certificates;
    }

    public function setStatDefinition(ProviderStatsDef $p) {
        $this->getStatDefinitions()->add($p);
        $p->setProvider($this);

        return $this;
    }

    /**
     * this object state will be overwriten by $provider object
     */
    public function overwriteByProvider(Provider $provider) {
        $this->setName($provider->getName());
        $this->setLocalName($provider->getLocalName());
        $this->setDisplayName($provider->getDisplayName());
        $this->setLocalDisplayName($provider->getLocalDisplayName());
        $this->overwriteScopeFull($provider);
        $this->setEntityId($provider->getEntityId());
        $this->setRegistrationAuthority($provider->getRegistrationAuthority());


        $r1 = $this->getRegistrationDateInFormat('YmdHis');
        $r2 = $provider->getRegistrationDateInFormat('YmdHis');
        if ($r1 !== $r2) {
            $this->setRegistrationDate($provider->getRegistrationDate());
        }

        $this->overwriteWithNameid($provider);
        foreach (array('idpsso', 'aa', 'spsso') as $a) {
            $this->setProtocolSupport($a, $provider->getProtocolSupport($a));
        }
        $this->setType($provider->getType());
        $this->setWantAssertionSigned($provider->getWantAssertionSigned());
        $this->setHelpdeskUrl($provider->getHelpdeskUrl());
        $this->setValidFrom($provider->getValidFrom());
        $this->setValidTo($provider->getValidTo());
        $smetadata = $provider->getStaticMetadata();
        if (!empty($smetadata)) {
            $this->overwriteStaticMetadata($smetadata);
        }


        /**
         * @var $s ServiceLocation
         * @var $nsrv ServiceLocation
         */
        $counterIdx = 0;
        foreach ($this->getServiceLocations() as $s) {
            if ($provider->getServiceLocations()->containsKey($counterIdx)) {
                $nsrv = $provider->getServiceLocations()->get($counterIdx);
                $s->setInFull($nsrv->getType(), $nsrv->getBindingName(), $nsrv->getUrl(), $nsrv->getOrderToInt());
                $s->setDefault($nsrv->getDefault());
                $provider->getServiceLocations()->remove($counterIdx);
            } else {
                $this->removeServiceLocation($s);
            }
            $counterIdx++;

        }


        foreach ($provider->getServiceLocations() as $r) {
            $this->setServiceLocation($r);
            $order = $r->getOrder();
            if (is_null($order)) {
                $r->setOrder(1);
            }
        }


        /**
         * @var $ctmp Certificate
         * @var $nctmp Certificate
         */
        $counterIdx = 0;
        foreach ($this->getCertificates() as $ctmp) {
            if ($provider->getCertificates()->containsKey($counterIdx)) {
                $nctmp = $provider->getCertificates()->get($counterIdx);
                $ctmp->setType($nctmp->getType());
                $cdata1 = $nctmp->getFingerprint();
                $cdata2 = $ctmp->getFingerprint();
                if ($cdata1 !== $cdata2) {
                    $ctmp->setCertdata($nctmp->getCertData());
                }

                $ctmp->setCertType($nctmp->getCertType());
                $ctmp->setCertUse($nctmp->getCertUse());
                $ctmp->setKeyname($nctmp->getKeyname());
                $ctmp->setEncryptMethods($nctmp->getEncryptMethods());

                $provider->getCertificates()->remove($counterIdx);

            } else {
                $this->removeCertificate($ctmp);
            }

            $counterIdx++;
        }

        foreach ($provider->getCertificates() as $ncrt) {
            $this->setCertificate($ncrt);
        }


        $counterIdx = 0;
        /**
         * @var $cn Contact
         * @var $nctn Contact
         */
        foreach ($this->getContacts() as $cn) {
            if ($provider->getContacts()->containsKey($counterIdx)) {
                $nctn = $provider->getContacts()->get($counterIdx);
                $cn->setEmail($nctn->getEmail());
                $cn->setType($nctn->getType());
                $cn->setGivenName($nctn->getGivenName());
                $cn->setSurName($nctn->getSurName());
                $provider->getContacts()->remove($counterIdx);
            } else {
                $this->removeContact($cn);
            }
            $counterIdx++;
        }


        foreach ($provider->getContacts() as $cn2) {
            $this->setContact($cn2);
        }


        foreach ($this->getExtendMetadata() as $f) {
            $this->removeExtendWithChildren($f);
        }
        foreach ($provider->getExtendMetadata() as $gg) {
            $this->setExtendMetadata($gg);
        }

        return $this;
    }

    private function removeExtendWithChildren(ExtendMetadata $e) {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;

        $children = $e->getChildren();
        if (!empty($children) && $children->count() > 0) {

            foreach ($children->getValues() as $c) {

                $this->removeExtendWithChildren($c);
            }
        }
        $this->getExtendMetadata()->removeElement($e);
        $this->em->remove($e);
    }

    public function getId() {
        return $this->id;
    }

    public function getRegistrationAuthority() {
        return $this->registrar;
    }

    /**
     * @return null|\DateTime
     */
    public function getRegistrationDate() {
        return $this->registerdate;
    }

    /**
     * @param $format
     * @return bool|null|string
     */
    public function getRegistrationDateInFormat($format, $offset = 0) {
        if (!empty($this->registerdate)) {
            if ($offset < 0) {
                return \date($format, $this->registerdate->format('U') - abs($offset));
            }
            return \date($format, $this->registerdate->format('U') + $offset);
        }

        return null;
    }

    public function getWantAssertionSigned(){
        return (bool) $this->wantassertsigned;
    }

    /**
     * get collection of contacts which are used in metada
     */
    public function getContacts() {
        return $this->contacts;
    }

    public function getCertificates() {
        return $this->certificates;
    }

    public function getStatDefinitions() {
        return $this->statsdef;
    }

    /**
     * obsolete
     */
    public function getNameIdToArray() {
        return $this->getNameId()->toArray();
    }

    /**
     * obsolete
     */
    public function getNameId() {
        return $this->nameidformat;
    }

    /**
     * new replacing getNameId()
     * $n one of : idpsso,spsso,aa
     */
    public function getNameIds($n = null) {
        if (empty($n)) {
            if (!empty($this->nameids)) {
                return unserialize($this->nameids);
            } else {
                return array();
            }
        }
        $default = array();
        if (!empty($this->nameids)) {
            $r = unserialize($this->nameids);
            if (isset($r['' . $n . ''])) {
                return $r['' . $n . ''];
            }
        }

        return $default;
    }

    public function getNotifications() {
        return $this->notifications;
    }

    public function addNotification(NotificationList $notification) {
        $isin = $this->getNotifications()->contains($notification);
        if (empty($isin)) {
            $this->getNotifications()->add($notification);
        }

        return $this;
    }

    public function getActive() {
        return $this->is_active;
    }

    public function getCoc() {
        return $this->coc;
    }

    public function getProtocol() {
        $col = new \Doctrine\Common\Collections\ArrayCollection();
        $tmp = $this->protocol;
        if (!empty($tmp)) {
            return $this->protocol;
        } else {
            return $col;
        }
    }

    public function getProtocolSupport($n = null) {
        if (empty($n)) {
            $t = $this->protocolsupport;
            if (empty($t)) {
                return array();
            } else {
                return unserialize($t);
            }
        }
        $default = array('urn:oasis:names:tc:SAML:2.0:protocol');
        $t = $this->protocolsupport;
        if (!empty($t)) {
            $r = unserialize($t);
            if (isset($r[$n])) {
                return $r[$n];
            }
        }

        return $default;
    }

    public function getRegistrationPolicy() {
        $s = @unserialize($this->regpolicy);
        if (empty($s)) {
            return array();
        }

        return $s;
    }

    public function getScope($n) {
        $s = @unserialize($this->scope);
        if (isset($s[$n])) {
            return $s[$n];
        } else {
            return array();
        }
    }

    public function getScopeFull() {
        $s = @unserialize($this->scope);
        if (!empty($s)) {
            return $s;
        } else {
            return array('aa' => array(), 'idpsso' => array());
        }
    }

    /**
     * used for convert strings to array
     */
    public function convertScope() {
        $s = $this->scope;
        if (!empty($s)) {
            $s2 = @unserialize($s);
            if (empty($s2)) {
                $y = explode(',', $this->scope);
                $z = array('idpsso' => $y, 'aa' => $y);
                $this->scope = (serialize($z));

                return $this;
            }
        }
    }

    public function getAttributeReleasePolicies() {
        return $this->attributeReleaseIDP;
    }

    public function getServiceLocations() {
        return $this->serviceLocations;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttributesRequirement() {
        return $this->attributeRequirement;
    }

    public function getFederations() {
        $mem = $this->membership;
        $federations = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($mem as $m) {
            $joinstate = $m->getJoinState();
            if ($joinstate != 2) {
                $federations->add($m->getFederation());;
            }
        }

        return $federations;
    }

    public function getActiveFederations() {
        $mem = $this->membership;
        $federations = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($mem as $m) {
            if ($m->isFinalMembership()) {
                $federations->add($m->getFederation());;
            }
        }

        return $federations;
    }

    public function getFederationNames() {
        $feders = array();
        foreach ($this->membership as $entry) {
            $feders[] = $entry->getFederation();
        }

        return $feders;
    }

    public function getName() {
        return $this->name;
    }

    public function getLocalName() {
        $p = unserialize($this->lname);
        if (empty($p)) {
            return array();
        } else {
            return $p;
        }
    }

    public function getMergedLocalName() {
        $r = $this->getLocalName();
        if (!isset($r['en']) && !empty($this->name)) {
            $r['en'] = $this->name;
        }

        return $r;
    }

    public function getNameToWebInLang($jlang, $type = null) {
        $result = null;
        $backupname = null;
        if ($type === null) {
            $type = $this->type;
        }
        $doFilter = array('DisplayName');
        if (!empty($this->extend)) {
            $e = $this->getExtendMetadata()->filter(
                function (ExtendMetadata $entry) use ($doFilter) {
                    return in_array($entry->getElement(), $doFilter);
                });
            if (!empty($e)) {
                foreach ($e as $p) {
                    $t = $p->getType();
                    $a = $p->getAttributes();
                    if (strcasecmp($t, $type) == 0 && isset($a['xml:lang'])) {
                        if (strcasecmp($a['xml:lang'], $jlang) == 0) {
                            $result = $p->getEvalue();
                            break;
                        } elseif ($backupname === null) {
                            $backupname = $p->getEvalue();
                        }
                    }
                }
            }
        }
        if ($result === null) {
            if ($backupname !== null) {
                $result = $backupname;
            } else {
                $result = $this->getDisplayNameInLang($jlang);
                if (empty($result)) {
                    $result = $this->getNameInLang($jlang);
                }
            }
        }
        if (empty($result)) {
            $result = $this->entityid;
        }

        return trim($result);
    }

    public function getNameLocalized() {
        $t['en'] = $this->name;
        $p = unserialize($this->lname);
        if (is_array($p)) {
            if (!array_key_exists('en', $p)) {
                $p['en'] = $t['en'];
            }
        } else {
            $p = $t;
        }

        return $p;
    }

    public function getDisplayNameInLang($jlang) {
        $r = $this->getDisplayNameLocalized();
        if (isset($r['' . $jlang . ''])) {
            return $r['' . $jlang . ''];
        } else {
            return $r['en'];
        }
    }

    public function getNameInLang($jlang) {
        $r = $this->getNameLocalized();
        if (isset($r['' . $jlang . ''])) {
            return $r['' . $jlang . ''];
        } else {
            return $r['en'];
        }
    }

    public function getDisplayName($length = null) {
        if (empty($length) || !is_integer($length) || strlen($this->displayname) <= $length) {
            return $this->displayname;
        } else {
            return substr($this->displayname, 0, $length) . '...';
        }
    }

    public function getLocalDisplayName() {
        if (!empty($this->ldisplayname)) {
            return unserialize($this->ldisplayname);
        }

        return array();
    }

    public function getMergedLocalDisplayName() {
        $r = $this->getLocalDisplayName();
        if (!isset($r['en']) && !empty($this->displayname)) {
            $r['en'] = $this->displayname;
        }

        return $r;
    }

    public function getLocalDisplayNamesToArray($type) {
        $result = array();
        $ex = $this->getExtendMetadata();
        foreach ($ex as $v) {
            if ($v->getType() === $type && $v->getNameSpace() === 'mdui' && $v->getElement() === 'DisplayName') {
                $l = $v->getAttributes();
                $result[$l['xml:lang']] = $v->getElementValue();
            }
        }

        return $result;
    }

    public function getDisplayNameLocalized() {
        if (!empty($this->ldisplayname)) {
            $p = unserialize($this->ldisplayname);
            if (!array_key_exists('en', $p)) {
                $p['en'] = $this->displayname;
            }

            return $p;
        } else {
            return array('en' => $this->displayname);
        }
    }

    public function getDigest() {
        return $this->digest;
    }

    /**
     * @return null|\Datetime
     */
    public function getValidTo() {
        return $this->validto;
    }

    /**
     * @return null|\Datetime
     */
    public function getValidFrom() {
        return $this->validfrom;
    }

    /**
     * @return bool
     */
    public function isValidFromTo() {
        /**
         * @todo fix broken time for the momemnt reurns true
         */
        $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $validAfter = true;
        $validBefore = true;
        if (!empty($this->validfrom) && ($currentTime < $this->validfrom)) {
            $validBefore = false;
        }
        if (!empty($this->validto) && ($currentTime > $this->validto)) {
            $validAfter = false;

        }

        return ($validAfter && $validBefore);
    }

    public function getEntityId() {
        return $this->entityid;
    }

    public function getType() {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getTypesToArray() {
        $result = array('idp' => false, 'sp' => false);
        if (strcasecmp($this->type, 'BOTH') == 0) {
            $result = array('idp' => true, 'sp' => true);
        } elseif (strcasecmp($this->type, 'IDP') == 0) {
            $result['idp'] = true;
        } else {
            $result['sp'] = true;
        }

        return $result;
    }

    public function getCountry() {
        return $this->country;
    }

    /*
     * return boolean if want to use static metadata
     */

    public function getStatic() {
        return $this->is_static;
    }

    /*
     * return static metadata body
     */

    public function getStaticMetadata() {
        return $this->metadata;
    }

    public function getExtendMetadata() {
        return $this->extend;
    }

    public function isStaticMetadata() {
        $c = $this->getStatic();
        $d = $this->getStaticMetadata();
        if ($c && !empty($d)) {
            return true;
        } else {
            return false;
        }
    }

    public function getHelpdeskUrl() {
        return $this->helpdeskurl;
    }

    public function getLocalHelpdeskUrl() {
        if (!empty($this->lhelpdeskurl)) {
            return unserialize($this->lhelpdeskurl);
        } else {
            return array();
        }
    }

    public function getHelpdeskUrlLocalized() {
        $t['en'] = $this->helpdeskurl;
        $p = unserialize($this->lhelpdeskurl);
        if (is_array($p)) {
            if (!array_key_exists('en', $p) && !empty($t['en'])) {
                $p['en'] = $t['en'];
            }
        } else {
            $p = $t;
        }

        return array_filter($p);
    }

    public function getPrivacyUrl() {
        return $this->privacyurl;
    }

    public function getLocalPrivacyUrl() {
        return unserialize($this->lprivacyurl);
    }

    public function getLocalPrivacyStatementsToArray($type) {
        $result = array();
        $ex = $this->getExtendMetadata();
        foreach ($ex as $v) {
            if ($v->getType() === $type && $v->getNameSpace() === 'mdui' && $v->getElement() === 'PrivacyStatementURL') {
                $l = $v->getAttributes();
                $result[$l['xml:lang']] = $v->getElementValue();
            }
        }

        return $result;
    }

    public function getPrivacyUrlLocalized() {
        $t['en'] = $this->privacyurl;
        $p = unserialize($this->lprivacyurl);
        if (is_array($p)) {
            if (!array_key_exists('en', $p)) {
                $p['en'] = $t['en'];
            }
        } else {
            $p = $t;
        }

        return $p;
    }

    public function getApproved() {
        return $this->is_approved;
    }

    public function getLocked() {

        return $this->is_locked;
    }

    public function getPublicVisible() {
        return !($this->hidepublic);
    }

    public function getAvailable() {

        return ($this->is_active && $this->is_approved && $this->isValidFromTo());
    }

    public function getLocal() {
        return $this->is_local;
    }

    public function getLocalAvailable() {
        return ($this->is_local && $this->is_active && $this->is_approved && $this->isValidFromTo());
    }

    public function getDescription() {
        return $this->description;
    }

    public function getLocalDescriptionsToArray($type) {
        $result = array();
        $ex = $this->getExtendMetadata();
        foreach ($ex as $v) {
            $t = $v->getType();
            $u = $v->getNameSpace();
            $e = $v->getElement();
            if ($t === $type && $u === 'mdui' && $e === 'Description') {
                $l = $v->getAttributes();
                $result[$l['xml:lang']] = $v->getElementValue();
            }
        }

        return $result;
    }

    public function getWayfList() {
        $w = $this->wayflist;
        if (!empty($w)) {
            return unserialize($w);
        }

        return null;

    }

    public function getExcarps() {
        $w = $this->excarps;
        if (!empty($w)) {
            return unserialize($w);
        }

        return array();

    }

    public function getLastModified() {
        if (empty($this->updatedAt)) {
            return $this->createdAt;
        }

        return $this->updatedAt;

    }

    public function overwriteWithNameid(Provider $provider) {
        $this->nameids = serialize($provider->getNameIds());
    }

    public function convertToArray($addmeta = false) {
        $r = array(
            'id' => $this->id,
            'entityid' => $this->entityid,
            'type' => $this->type,
            'validfrom' => $this->getValidFrom(),
            'validto' => $this->getValidTo(),
            'is_local' => $this->getLocal(),
            'is_approved' => $this->getApproved(),
            'is_active' => $this->getActive(),
            'is_locked' => $this->getLocked(),
            'is_static' => $this->getStatic(),
            'name' => $this->getName(),
            'displayname' => $this->getDisplayname(),
            'nameid' => array(),
            'protocol' => array(),
            'scope' => $this->getScope('idpsso'),
            'aascope' => $this->getScope('aa'),
            'helpdeskurl' => $this->getHelpdeskUrl(),
            'privacyurl' => $this->getPrivacyUrl(),
            'contacts' => array(),
            'services' => array(),
            'certificates' => array()
        );


        $nameids = $this->getNameIds();
        if (!empty($nameids)) {
            $r['nameid'] = $nameids;
        }


        $protocols = $this->getProtocol()->getValues();
        if (!empty($protocols)) {
            $r['protocol'] = $protocols;
        }

        $contacts = $this->getContacts();
        if (!empty($contacts)) {
            foreach ($contacts->getValues() as $c) {
                $r['contacts'][] = $c->convertToArray();
            }
        }


        $certs = $this->getCertificates();
        if (!empty($certs)) {
            foreach ($certs->getValues() as $crt) {
                $r['certificates'][] = $crt->convertToArray();
            }
        }
        $services = $this->getServiceLocations();

        if (!empty($services)) {
            foreach ($services->getValues() as $s) {
                $r['services'][] = $s->convertToArray();
            }
        }


        $membership = $this->getMembership();
        if (!empty($membership) && $membership->count() > 0) {
            foreach ($membership as $f) {
                $state = $f->getJoinState();
                if ($state != 2) {
                    $r['federations'][] = $f->getFederation()->convertToArray();
                }
            }
        }

        return $r;
    }

    public function importFromArray(array $r) {
        $etype = strtoupper($r['type']);
        $this->setName($r['name']);
        if (!empty($r['displayname'])) {
            $this->setDisplayname($r['displayname']);
        } else {
            $this->setDisplayname($r['name']);
        }
        $this->setEntityid($r['entityid']);
        if (is_array($r['nameid']) && count($r['nameid']) > 0) {
            foreach ($r['nameid'] as $k => $n) {
                $this->setNameids($k, $n);
            }
        }

        if (is_array($r['protocol']) && count($r['protocol']) > 0) {
            foreach ($r['protocol'] as $p) {
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
        if (count($r['contacts']) > 0) {
            foreach ($r['contacts'] as $v) {
                $c = new Contact;
                $c->importFromArray($v);
                $this->setContact($c);
                $c->setProvider($this);
            }
        }
        if (count($r['certificates']) > 0) {
            foreach ($r['certificates'] as $v) {
                if (is_array($v)) {
                    $c = new Certificate;
                    $c->importFromArray($v);
                    $this->setCertificate($c);
                    $c->setProvider($this);
                }
            }
        }
        if ($etype !== 'IDP') {
            if (isset($r['details']['spssodescriptor']['extensions']['idpdisc']) && is_array($r['details']['spssodescriptor']['extensions']['idpdisc'])) {
                foreach ($r['details']['spssodescriptor']['extensions']['idpdisc'] as $idpdisc) {
                    $c = new ServiceLocation;
                    $c->setDiscoveryResponse($idpdisc['url'], $idpdisc['order']);
                    $c->setProvider($this);
                }
            }
            if (isset($r['details']['spssodescriptor']['extensions']['init']) && is_array($r['details']['spssodescriptor']['extensions']['init'])) {
                foreach ($r['details']['spssodescriptor']['extensions']['init'] as $initreq) {
                    $c = new ServiceLocation;
                    $c->setRequestInitiator($initreq['url'], $initreq['binding']);
                    $c->setProvider($this);
                }
            }
        }
        if (count($r['services']) > 0) {
            foreach ($r['services'] as $v) {
                $c = new ServiceLocation;
                $c->importFromArray($v);
                $this->setServiceLocation($c);
                $c->setProvider($this);
            }
        }
        if (array_key_exists('federations', $r) && count($r['federations']) > 0) {
            foreach ($r['federations'] as $f) {
                $c = new Federation;
                $c->importFromArray($f);
                $m = new FederationMembers;
                $m->setFederation($c);
                $m->setProvider($this);
                $this->addMembership($m);
            }
        }
    }

    /**
     *
     * extensions inside IDPSSODEscriptor (idp) or SPSODescriptor (sp)
     */
    private function ssoDescriptorExtensionsFromArray($ext, $type) {
        $parentUIInfo = new ExtendMetadata;
        $parentUIInfo->setNamespace('mdui');
        $parentUIInfo->setElement('UIInfo');
        $parentUIInfo->setAttributes(array());
        $parentUIInfo->setType($type);
        $parentUIInfo->setProvider($this);
        $this->setExtendMetadata($parentUIInfo);
        if ($type === 'idp') {
            $parentDiscoHints = new ExtendMetadata;
            $parentDiscoHints->setNamespace('mdui');
            $parentDiscoHints->setElement('DiscoHints');
            $parentDiscoHints->setAttributes(array());
            $parentDiscoHints->setType($type);
            $parentDiscoHints->setProvider($this);
            $this->setExtendMetadata($parentDiscoHints);
            if (isset($ext['geo']) && is_array($ext['geo'])) {
                foreach ($ext['geo'] as $g) {
                    $geo = new ExtendMetadata;
                    $geo->setGeoLocation('' . $g[0] . ',' . $g[1] . '', $this, $parentDiscoHints, $type);
                    $geo->setProvider($this);
                    $this->setExtendMetadata($geo);
                }
            }
            foreach (array('iphint', 'domainhint') as $v) {
                if (isset($ext['' . $v . ''])) {
                    foreach ($ext['' . $v . ''] as $w) {
                        $we = new ExtendMetadata;
                        $we->setProvider($this);
                        $we->setType('idp');
                        $we->setNamespace('mdui');
                        $we->setValue($w);
                        $we->setParent($parentDiscoHints);
                        if ($v === 'iphint') {
                            $we->setElement('IPHint');
                        } else {
                            $we->setElement('DomainHint');
                        }
                        $this->setExtendMetadata($we);
                    }
                }
            }
        }
        if (array_key_exists('scope', $ext)) {
            $this->setScope('idpsso', $ext['scope']);
        }
        if (array_key_exists('aascope', $ext)) {
            $this->setScope('aa', $ext['aascope']);
        }

        $otherExtends = array(
            'desc' => 'Description',
            'keywords' => 'Keywords',
            'displayname' => 'DisplayName',
            'privacyurl' => 'PrivacyStatementURL',
            'informationurl' => 'InformationURL'
        );

        foreach ($otherExtends as $k => $v) {
            $langDuplicates = array();
            if (array_key_exists($k, $ext) && is_array($ext['' . $k . ''])) {
                foreach ($ext['' . $k . ''] as $p2) {
                    if (!in_array($p2['lang'], $langDuplicates)) {
                        $extdesc = new ExtendMetadata;
                        $extdesc->setNamespace('mdui');
                        $extdesc->setType($type);
                        $extdesc->setElement('' . $v . '');
                        $extdesc->setValue($p2['val']);
                        $extdesc->setAttributes(array('xml:lang' => $p2['lang']));
                        $extdesc->setProvider($this);
                        $this->setExtendMetadata($extdesc);
                        $extdesc->setParent($parentUIInfo);
                        $langDuplicates[] = $p2['lang'];
                    }
                }
            }

        }

        if (array_key_exists('logo', $ext) && is_array($ext['logo'])) {
            foreach ($ext['logo'] as $k => $p) {
                $extdesc = new ExtendMetadata;
                $extdesc->setLogo($p['val'], $this, $parentUIInfo, array('width' => $p['width'], 'height' => $p['height'], 'xml:lang' => $p['xml:lang']), $type);
                $this->setExtendMetadata($extdesc);
            }
        }
        if ($type == 'sp') {
            if (array_key_exists('idpdisc', $ext) && is_array($ext['idpdisc'])) {
                foreach ($ext['idpdisc'] as $idpdiscs) {
                    $disc = new ServiceLocation;
                    $disc->setDiscoveryResponse($idpdiscs['url'], @$idpdiscs['order']);
                    $disc->setProvider($this);
                    $this->setServiceLocation($disc);
                }
            }
            if (array_key_exists('init', $ext) && is_array($ext['init'])) {
                foreach ($ext['init'] as $inits) {
                    $rinit = new ServiceLocation;
                    $rinit->setRequestInitiator($inits['url']);
                    $rinit->setProvider($this);
                    $this->setServiceLocation($rinit);
                }
            }
        }
    }

    private function aaDescriptorFromArray($b) {
        if (array_key_exists('protocols', $b)) {
            $this->setProtocolSupport('aa', $b['protocols']);
        }
        if (array_key_exists('extensions', $b)) {
            $this->ssoDescriptorExtensionsFromArray($b['extensions'], 'aa');
        }
        if (array_key_exists('nameid', $b) && is_array($b['nameid'])) {
            $this->setNameIds('aa', $b['nameid']);
        }
        if (array_key_exists('attributeservice', $b)) {
            foreach ($b['attributeservice'] as $aval) {
                $aa = new ServiceLocation;
                $aa->setType('IDPAttributeService');
                $aa->setBindingName($aval['binding']);
                $aa->setUrl($aval['location']);
                $aa->setProvider($this);
                $this->setServiceLocation($aa);
            }
        }
        if (array_key_exists('certificate', $b)) {
            foreach ($b['certificate'] as $c) {
                $cert = new Certificate();
                if (array_key_exists('x509data', $c)) {
                    $cert->setCertType('x509');
                    if (array_key_exists('x509certificate', $c['x509data'])) {
                        $cert->setCertdata($c['x509data']['x509certificate']);
                    }
                }
                if (array_key_exists('encmethods', $c) && count($c['encmethods']) > 0) {
                    $cert->setEncryptMethods($c['encmethods']);
                }


                $cert->setType('aa');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname'])) {
                    if (is_array($c['keyname'])) {
                        $cert->setKeyname(implode(',', $c['keyname']));
                    } else {
                        $cert->setKeyname($c['keyname']);
                    }
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }
    }

    private function idpSSODescriptorFromArray($b) {
        if (array_key_exists('extensions', $b)) {
            $this->ssoDescriptorExtensionsFromArray($b['extensions'], 'idp');
        }

        if (array_key_exists('nameid', $b) && is_array($b['nameid'])) {
            $this->setNameIds('idpsso', $b['nameid']);
        }
        if (array_key_exists('servicelocations', $b)) {
            $tmpsrcl = array('singlesignonservice' => 'SingleSignOnService', 'singlelogout' => 'IDPSingleLogoutService', 'artifactresolutionservice' => 'IDPArtifactResolutionService');
            foreach ($tmpsrcl as $kc => $vc) {
                if (isset($b['servicelocations']['' . $kc . '']) && is_array($b['servicelocations']['' . $kc . ''])) {
                    foreach ($b['servicelocations']['' . $kc . ''] as $s) {
                        $sso = new ServiceLocation;
                        $order = null;
                        if ($vc === 'IDPArtifactResolutionService') {
                            $order = $s['order'];
                        }
                        $sso->setInFull($vc, $s['binding'], $s['location'], $order);
                        $sso->setProvider($this);
                        $this->setServiceLocation($sso);
                    }
                }
            }
        }
        $this->setProtocolSupport('idpsso', $b['protocols']);
        if (array_key_exists('certificate', $b) && count($b['certificate']) > 0) {

            foreach ($b['certificate'] as $c) {
                $cert = new Certificate();
                if (array_key_exists('x509data', $c)) {
                    $cert->setCertType('x509');
                    if (array_key_exists('x509certificate', $c['x509data'])) {
                        $cert->setCertdata($c['x509data']['x509certificate']);
                    }
                }
                if (array_key_exists('encmethods', $c) && count($c['encmethods']) > 0) {
                    $cert->setEncryptMethods($c['encmethods']);
                }

                $cert->setType('idpsso');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname'])) {
                    if (is_array($c['keyname'])) {
                        $cert->setKeyname(implode(',', $c['keyname']));
                    } else {
                        $cert->setKeyname($c['keyname']);
                    }
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }

        return $this;
    }

    private function spSSODescriptorFromArray($b) {
        if (array_key_exists('extensions', $b)) {
            $this->ssoDescriptorExtensionsFromArray($b['extensions'], 'sp');
        }
        $this->setWantAssertionSigned(false);
        if (array_key_exists('wantassertsigned', $b) && $b['wantassertsigned'] === true){
            $this->setWantAssertionSigned(true);
        }
        if (array_key_exists('nameid', $b) && is_array($b['nameid'])) {
            $this->setNameIds('spsso', $b['nameid']);
        }
        if (array_key_exists('protocols', $b)) {
            $this->setProtocolSupport('spsso', $b['protocols']);
        }
        if (isset($b['servicelocations']['assertionconsumerservice']) && is_array($b['servicelocations']['assertionconsumerservice'])) {

            foreach ($b['servicelocations']['assertionconsumerservice'] as $s) {
                $sso = new ServiceLocation;
                $sso->setType('AssertionConsumerService');
                $sso->setBindingName($s['binding']);
                $sso->setUrl($s['location']);
                if (isset($s['order'])) {
                    $sso->setOrder($s['order']);
                }
                if (array_key_exists('isdefault',$s) && $s['isdefault'] === 'true') {
                    $sso->setDefault(true);
                }
                $sso->setProvider($this);
                $this->setServiceLocation($sso);
            }
        }
        if (isset($b['servicelocations']['artifactresolutionservice']) && is_array($b['servicelocations']['artifactresolutionservice'])) {

            foreach ($b['servicelocations']['artifactresolutionservice'] as $s) {
                $sso = new ServiceLocation;
                $sso->setType('SPArtifactResolutionService');
                $sso->setBindingName($s['binding']);
                $sso->setUrl($s['location']);
                if (isset($s['order'])) {
                    $sso->setOrder($s['order']);
                }
                if (!empty($s['isdefault'])) {
                    $sso->setDefault(true);
                }
                $sso->setProvider($this);
                $this->setServiceLocation($sso);
            }
        }

        if (isset($b['servicelocations']['singlelogout']) && is_array($b['servicelocations']['singlelogout'])) {

            foreach ($b['servicelocations']['singlelogout'] as $s) {
                $slo = new ServiceLocation;
                $slo->setType('SPSingleLogoutService');
                $slo->setBindingName($s['binding']);
                $slo->setUrl($s['location']);
                $slo->setProvider($this);
                $this->setServiceLocation($slo);
            }
        }
        if (array_key_exists('certificate', $b) && is_array($b['certificate'])) {

            foreach ($b['certificate'] as $c) {
                $cert = new Certificate();
                if (array_key_exists('x509data', $c)) {
                    $cert->setCertType('x509');
                    if (array_key_exists('x509certificate', $c['x509data'])) {
                        $cert->setCertdata($c['x509data']['x509certificate']);
                    }
                }
                if (array_key_exists('encmethods', $c) && count($c['encmethods']) > 0) {
                    $cert->setEncryptMethods($c['encmethods']);
                }

                $cert->setType('spsso');
                $cert->setCertUse($c['use']);
                if (!empty($c['keyname'])) {
                    if (is_array($c['keyname'])) {
                        $cert->setKeyname(implode(',', $c['keyname']));
                    } else {
                        $cert->setKeyname($c['keyname']);
                    }
                }
                $cert->setProvider($this);
                $this->setCertificate($cert);
            }
        }

        return $this;
    }

    public function setReqAttrsFromArray($ent, $attributesByName) {
        if (isset($ent['details']['reqattrs'])) {
            \log_message('info', 'DI1');
            $attrsset = array();
            foreach ($ent['details']['reqattrs'] as $r) {
                if (array_key_exists($r['name'], $attributesByName)) {
                    if (!in_array($r['name'], $attrsset)) {
                        $reqattr = new AttributeRequirement;
                        $reqattr->setAttribute($attributesByName['' . $r['name'] . '']);
                        $reqattr->setType('SP');
                        $reqattr->setSP($this);
                        if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0) {
                            $reqattr->setStatus('required');
                        } else {
                            $reqattr->setStatus('desired');
                        }
                        $reqattr->setReason('');
                        $this->setAttributesRequirement($reqattr);
                        $attrsset[] = $r['name'];
                    }
                } else {
                    log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
                }
            }
        }

        return $this;
    }

    public function setProviderFromArray($a, $full = false) {
        if (!is_array($a)) {
            return null;
        }
        $this->setType($a['type']);
        $this->setEntityId($a['entityid']);
        if (!empty($a['coc'])) {
            /**
             * @todo set CodeOfConduct
             */
        }
        if (!empty($a['validuntil'])) {
            $p = explode('T', $a['validuntil']);
            $this->setValidTo(\DateTime::createFromFormat('Y-m-d', $p[0]));
        }
        if (!empty($a['registrar'])) {
            $this->setRegistrationAuthority($a['registrar']);
            if (!empty($a['regdate'])) {
                $p = explode('T', $a['regdate']);
                if (array_key_exists('1', $p)) {
                    $ptime = str_replace('Z', '', $p['1']);
                } else {
                    $ptime = '00:00:00';
                }
                $pdate = \DateTime::createFromFormat('Y-m-d H:i:s', $p[0] . ' ' . substr($ptime, 0, 8));
                if ($pdate instanceOf \DateTime) {
                    $this->setRegistrationDate($pdate);
                } else {
                    \log_message('error', __METHOD__ . ' couldnt create \DateTime object from string for entity:' . $this->entityid);
                }
            }
        }
        if ($full & !empty($a['regpol'])) {
            foreach ($a['regpol'] as $v) {
                $b = $this->em->getRepository("models\Coc")->findOneBy(array('type' => 'regpol', 'is_enabled' => true, 'lang' => $v['lang'], 'url' => $v['url']));
                if (!empty($b)) {
                    $this->setCoc($b);
                }
            }
        }
        if ($full && !empty($a['algs'])) {

            foreach ($a['algs'] as $alg) {
                $e = new ExtendMetadata();
                $e->setElement($alg['name']);
                $e->setNamespace('alg');
                $e->setValue($alg['algorithm']);
                $e->setType('ent');
                if (!empty($alg['minkeysize'])) {
                    $algattr['MinKeySize'] = $alg['minkeysize'];
                }
                if (!empty($alg['maxkeysize'])) {
                    $algattr['MaxKeySize'] = $alg['maxkeysize'];
                }
                if (!empty($algattr)) {
                    $e->setAttributes($algattr);
                }
                $e->setProvider($this);
                $this->setExtendMetadata($e);
            }
        }
        if (!empty($a['metadata'])) {
            $m = new StaticMetadata;
            $m->setMetadata($a['metadata']);
            $this->setStaticMetadata($m);
        }
        if (array_key_exists('details', $a)) {
            foreach ($a['details']['org'] as $k => $o) {
                if ($k === 'OrganizationName') {
                    $lorgname = array();
                    foreach ($o as $k1 => $v1) {
                        if ($k1 === 'en') {
                            $this->setName($v1);
                        } else {
                            $lorgname['' . $k1 . ''] = $v1;
                        }
                    }
                    $this->setLocalName($lorgname);
                } elseif ($k === 'OrganizationDisplayName') {
                    $lorgname = array();
                    foreach ($o as $k1 => $v1) {
                        if ($k1 === 'en') {
                            $this->setDisplayName($v1);
                        } else {
                            $lorgname['' . $k1 . ''] = $v1;
                        }
                    }
                    $this->setLocalDisplayName($lorgname);
                } elseif ($k === 'OrganizationURL') {
                    $lorgname = array();
                    foreach ($o as $k1 => $v1) {
                        if ($k1 === 'en') {
                            $this->setHelpdeskUrl($v1);
                        } else {
                            $lorgname[$k1] = $v1;
                        }
                    }
                    $this->setLocalHelpdeskUrl($lorgname);
                }
            }


            foreach ($a['details']['contacts'] as $c) {
                $tc = new Contact;
                $tc->setType($c['type']);
                $tc->setEmail($c['email']);
                $tc->setSurName($c['surname']);
                $tc->setGivenName($c['givenname']);
                $tc->setProvider($this);
                $this->setContact($tc);
            }
            if ($a['type'] !== 'SP') {
                if (array_key_exists('idpssodescriptor', $a['details'])) {

                    $this->idpSSODescriptorFromArray($a['details']['idpssodescriptor']);
                }
                if (array_key_exists('aadescriptor', $a['details'])) {

                    $this->aaDescriptorFromArray($a['details']['aadescriptor']);
                }
            }
            if ($a['type'] !== 'IDP' && array_key_exists('spssodescriptor', $a['details'])) {
                $this->spSSODescriptorFromArray($a['details']['spssodescriptor']);
            }
        }

        return $this;
    }

}
