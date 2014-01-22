<?php
namespace models;
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Attribute Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * NotificationList Model
 *
 * This model for attributes definitions
 * 
 * 
 * @HasLifecycleCallbacks
 * @Entity
 * @Table(name="notificationlist",indexes={@Index(name="type_idx", columns={"type"}),@Index(name="subscibe_idx", columns={"subscriber"})})
 * @author janusz
 */
class NotificationList
{
        /**
         * @Id
         * @Column(type="integer", nullable=false)
         * @GeneratedValue(strategy="AUTO")
         */
         protected $id;

        /**
         * @ManyToOne(targetEntity="User",inversedBy="subscriptions")
         * @JoinColumn(name="subscriber", referencedColumnName="id")
         */
        protected $subscriber;

        /**
         * @Column(type="string", length=15, nullable=false)
         */
        protected $type;

        /**
         * @ManyToOne(targetEntity="Provider",inversedBy="notifications")
         * @JoinColumn(name="provider", referencedColumnName="id")
         */
        protected $provider;

        /**
         * @ManyToOne(targetEntity="Federation",inversedBy="notifications")
         * @JoinColumn(name="federation", referencedColumnName="id")
         */
        protected $federation;

        /**
         * @Column(type="string", length=256, nullable=true)
         */
        protected $email;

        /**
         * @Column(name="isenabled",type="boolean")
         */
        protected $is_enabled;

        /**
         * @Column(name="isapproved",type="boolean")
         */
        protected $is_approved;
        
        /**
         * @Column(name="created", type="datetime")
         */
        protected $createdAt;

       /**
        * @Column(name="updated", type="datetime")
        */
       protected $updatedAt;

       function __construct()
       {
          $this->is_enabled = false;
          $this->is_approved = false;
          $this->updatedAt = new \DateTime("now",new \DateTimeZone('UTC'));
       }

       public function getId()
       {
           return $this->id;
       } 

       public function getSubscriber()
       {
           return $this->subscriber;
       }
       public function getType()
       {
           return $this->type;
       }
       
       public function getProvider()
       {
           return $this->provider;
       } 
       public function getFederation()
       {
           return $this->federation;
       }
       public function getEmail()
       {
           return $this->email;
       }
      
       public function getRcpt()
       {
           if(empty($this->email))
           {
              return $this->getSubcriber()->getEmail();
           }
           return $this->email;
       }
       public function getEnabled()
       {
           return $this->is_enabled;
       }

       public function getApproved()
       {
           return $this->is_approved;
       }
 
       public function getAvailable()
       {
          if($this->is_enabled && $this->is_approved)
          {
              return true; 
          }
          return false;
       }

       public function getCreatedAt()
       {
          return $this->createdAt;
       }
      
       public function getUpdatedAt()
       {
          return $this->updatedAt;
       }
     
      /**
       * setters/modifiers
       */
       public function setSubscriber(User $user)
       {
          $this->subscriber = $user;
          return $this;
       }
       public function setType($type)
       {
          $type = trim($type);
          if(!empty($type))
          {
             $this->type  = $type;
             return $this;
          }
       }
       public function  setProvider(Provider $provider)
       {
          $this->provider = $provider;
          return $this;
       }
     
       public function setFederation(Federation $federation)
       {
           $this->federation = $federation;
           return $this;
       }

       public function setEmail($email)
       {
          $email = trim($email);
          $this->email = $email;
          return $this;
       }

       public function setEnabled( $arg)
       {
          $this->is_enabled = $arg;
          return $this;
       }

       public function setApproved( $arg)
       {
          $this->is_approved = $arg;
          return $this;
       }


       
      /**
       * @prePersist 
       */
       public function created()
       {
           $this->createdAt = new \DateTime("now",new \DateTimeZone('UTC'));
       }


      /**
       * @PreUpdate
       */
       public function updated()
       {
          $this->updatedAt = new \DateTime("now",new \DateTimeZone('UTC'));
       }
      
}
