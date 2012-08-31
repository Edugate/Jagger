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
 * Attribute Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Attribute Model
 *
 * This model for attributes definitions
 * 
 * @Entity
 * @Table(name="attribute")
 * @author janusz
 */
class Attribute
{
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
          * @Column(type="string", length=255, nullable=false)
          */
         protected $fullname;

         /**
          * @Column(type="string", length=255)
          */
         protected $oid;

         /**
          * @Column(type="string", length=255)
          */
         protected $urn;

         /**
          * @Column(type="text", nullable=true)
          */
         protected $description;

		  public function __toString()
		  {
		          return $this->name;
		     }

         // Begin generic set/get methods
         public function setName($name)
         {
               $this->name = \trim($name);
               return $this;
         }
         public function setFullname($fullname)
         {
               $this->fullname = \trim($fullname);
               return $this;
         }
         public function setUrn($urn)
         {
               $this->urn = \trim($urn);
               return $this;
         }
         public function setOid($oid)
         {
               $this->oid = \trim($oid);
               return $this;
         }
         public function setDescription($description)
         {
               $this->description = $description;
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
		 public function getFullname()
		 {
		 	return $this->fullname;
		 }
		 public function getUrn()
		 {
		 	return $this->urn;
		 }
		 public function getOid()
		 {
		 	return $this->oid;
		 }
		 public function getDescription()
		 {
		 	return $this->description;
		 }
}

