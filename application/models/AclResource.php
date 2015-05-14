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
 * AclResource Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * AclResource Model
 *
 * This model for Identity and Service Providers definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="acl_resource")
 * @author janusz
 */
 class AclResource {
 
 
    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string",length=30, unique=true)
     */
     protected $resource;
    /**
     * @Column(type="string",length=255,nullable=true)
     */
     protected $description;

     /**
      * @Column(type="string",length=255,nullable=true)
      */
      protected $type;
    /**
     * @ManyToOne(targetEntity="AclResource",inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
     protected $parent; 

     /**
      * @OneToMany(targetEntity="AclResource", mappedBy="parent", cascade={"remove"})
      */
     protected $children;





    /**
     * @Column(type="string",length=10)
     */
     protected $default_value;

     /**
      * @OneToMany(targetEntity="Acl",mappedBy="resource",cascade={"persist","remove"})
      */
     protected $acls;
      
     function __construct()
     {
        $this->default_value = 0;
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->acls = new \Doctrine\Common\Collections\ArrayCollection();
     }

     public function getId()
     {
        return $this->id;
     }
     public function getDefaultValue()
     {
        return $this->default_value;
     }
     public function getResource()
     {
        return $this->resource;
     }
     public function getParent()
     {
        return $this->parent;
     }
     public function getChildren()
     {
        return $this->children;
     }
     public function getDescription()
     {
        return $this->description;
     }
     public function getType()
     {
        return $this->type;
     }
     public function getAcls()
     {
        return $this->acls;
     }

     public function setParent($parent)
     {
        $this->parent = $parent;
     }

     public function setResource($resource)
     {
        $this->resource=$resource;
     }
     public function setDefaultValue($default)
     {
        $this->default_value = $default;
     }
     public function setDescription($description)
     {
        $this->description = $description;
     }
     public function setType($type)
     {
        $this->type = $type;
     }
     public function setAcl($acl)
     {
        $this->getAcls()->add($acl);
     }

     


 }
