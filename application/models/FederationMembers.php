<?php
namespace models;
use \Doctrine\Common\Collections\ArrayCollection;



/**
 * Federation Model
 *
 * This model for federations definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="federation_members", uniqueConstraints={@UniqueConstraint(name="memberspair_idx", columns={"provider_id", "federation_id"})})
 * @author janusz
 */
class FederationMembers
{
 
    /**
     * @Id
     * @Column(type="integer", nullable=false, unique=true)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

   /**
    * @ManyToOne(targetEntity="Provider",inversedBy="membership",fetch="EAGER")
    * @JoinColumn(name="provider_id", referencedColumnName="id", nullable=false)
    */
   protected $provider;

   /**
    * @ManyToOne(targetEntity="Federation",inversedBy="membership",fetch="EAGER" )
    * @JoinColumn(name="federation_id", referencedColumnName="id",nullable=false)
    */
   protected $federation;


   /**
    * default: 0, 1 => local entity joined fed, 2 => local entity left fed, 3 => entity joined during sync 
    * @Column(name="joinstate",type="integer", nullable=false)
    */
   protected $joinstate;

   /**
    * @Column(name="isdisabled", type="boolean", nullable=false)
    */
   protected $isDisabled;

   /**
    * @Column(name="isbanned", type="boolean", nullable=false)
    */
   protected $isBanned;
   


   function __construct()
   {
       $this->joinstate = 0;
       $this->isDisabled = 0;
       $this->isBanned = 0;
   }
   public function getId()
   {
       return $this->id;
   }
   public function getProvider()
   {
      return $this->provider;
   }

   public function getFederation()
   {
      return $this->federation;
   }

   public function getJoinState()
   {
      return $this->joinstate;
   }

   public function isFinalMembership()
   {
      return !($this->isDisabled || $this->isBanned || ($this->joinstate == 2));
 
   }
   public function isDisabled()
   {
      return $this->isDisabled;
   }
   public function isBanned()
   {
      return $this->isBanned;
   }

   public function setProvider($provider)
   {
       $this->provider = $provider;
       return $this;
   }
   public function setFederation($federation)
   {
       $this->federation = $federation;
       return $this;
   }

   public function setJoinstate($state)
   {
       $this->joinstate = $state;
       return $this;
   }
   public function setBanned($bool)
   {
       $this->isBanned = $bool;
       return $this;
   }
   public function setDisabled($bool)
   {
       $this->isDisabled = $bool;
       return $this;
   }
}
