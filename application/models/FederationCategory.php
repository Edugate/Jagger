<?php
namespace models;
use \Doctrine\Common\Collections\ArrayCollection;
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * FederationCategory Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * FederationCategory Model
 *
 * This model for federations categories definitions
 * 
 * @Entity
 * @Table(name="fedcategory")
 * @author janusz
 */
class FederationCategory
{
    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=28, nullable=false, unique=true)
     */
    protected $shortname;

    /**
     * @Column(type="string", length=56, nullable=false)
     */
    protected $descname;

    /**
     * @Column(type="string", length=512, nullable=false)
     */
    protected $description;

    /**
     * @ManyToMany(targetEntity="Federation", inversedBy="categories")
     * @JoinTable(name="fedcategory_members")
     */
     protected $federations;

    /**
     * @Column(type="boolean");
     */
     protected $isdefault;


     public function __construct()
     {
        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->isdefault = FALSE;

     }

    public function getId()
    {
        return $this->id;
    }
    public function  getName()
    {
       return $this->shortname;

    }
    public function getFullName()
    {
       return $this->descname;
    }
    public function getDescription()
    {
       return $this->description;
    }

    public function getFederations()
    {
       return $this->federations;
    }

    public function isDefault()
    {
       return $this->isdefault;
    }

    public function setName($name)
    {
       $this->shortname = $name;
       return $this;
    }
    public function setFullName($name)
    {
       $this->descname = $name;
       return $this;
    }
    public function setDescription($desc)
    {
       $this->description = $desc;
        return $this;
    }
    public function setDefault($a)
    {
        $this->isdefault = $a;
        return $this;
    }

    public function populate($name,$fullname,$description)
    {
        $this->shortname = $name;
        $this->descname = $fullname;
        $this->description = $description;
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
        $federation->getCategories()->removeElement($this);
        return $this->federations;
    }
    


     


}
