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
 * Partner Class
 * 
 *  not used yet
 *
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Partner Model
 *
 * This is a sample model to demonstrate how to use the AnnotationDriver
 *
 * @Entity
 * @Table(name="partner")
 * @author janusz
 */
class Partner
{
	/**
	 * @Id
	 * @Column(type="integer", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @Column(type="string", unique=true, nullable=false)
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 */
	protected $contact;

	/**
	 * @Column(type="string")
	 */
	protected $phone;

	/**
	 * @Column(type="string", unique=true, nullable=false)
	 */
	protected $homeurl;

	/**
	 * @Column(type="text")
	 */
	protected $description;

         /**
          * it can be member of many federations
          * @ManyToMany(targetEntity="Federation", inversedBy="partners")
          * @JoinTable(name="federation_partners" )
          */
         protected $pfederations;



        public function setName($name)
        {
                $this->name=$name;
                return $this;
        }
        /**
         * main contact
         */ 
        public function setContact($contact)
        {
                $this->contact=$contact;
                return $this;
        }

        public function setPhone($phone)
        {
                $this->phone=$phone;
                return $phone;
        }
        public function setHomeUrl($url)
        {
                $this->homeurl=$url;
                return $this;
        }
        public function setDescription($desc)
        {
                $this->description=$desc;
                return $this;
        }

        public function getName()
        {
                return $this->name;
        }
        public function getContact()
        {
                return $this->contact;
        }
        public function getPhone()
        {
                return $this->phone;
        }
        public function getHomeUrl()
        {
                return $this->homeurl;
        }
        public function getDescription()
        {
                return $this->description;
        }





}
