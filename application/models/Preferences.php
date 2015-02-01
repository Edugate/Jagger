<?php

namespace models;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
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
 * Preference Model
 *
 * This model for Identity and Service Providers definitions
 *
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="preferences")
 * @author janusz
 */
class Preferences
{

	/**
	 * @Id
	 * @Column(type="bigint", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @Column(type="string", length=30, unique=true)
	 */
	protected $name;

	/**
	 * allowed values are: multicheck, singlecheck,text,settings,bool
	 * @Column(type="string",length=12,nullable=false)
	 */
	protected $stype;

	/**
	 * @Column(type="string",length=10, nullable = false)
	 */
	protected $scategory;

	/**
	 * @Column(type="string", length=50, unique=false)
	 */
	protected $descname;

	/**
	 * @Column(type="text", nullable=true)
	 */
	protected $pvalue;

	/**
	 * @Column(type="text", nullable=true)
	 */
	protected $serializedvalue;

	/**
	 * @Column(type="boolean")
	 */
	protected $is_enabled;

	/**
	 * @Column(type="text")
	 */
	protected $description;

	public function __construct()
	{
		$this->scategory = 'default';
	}

        public function getId()
        {
                return $this->id;
        }

	public function getName()
	{
		return $this->name;
	}

	public function getType()
	{
		return $this->stype;
	}

	public function getCategory()
	{
		return $this->scategory;
	}

	public function getDescname()
	{
		return $this->descname;
	}

	public function getEnabled()
	{
		return $this->is_enabled;
	}

	public function getValue()
	{
		return $this->pvalue;
	}

	public function getSerializedValue()
	{
		$p = $this->serializedvalue;
		if(!is_null($p))
		{
			return unserialize($p);
		}
		return null;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function setType($type)
	{
		$ptype = trim($type);
		$allowed = array('multicheck','singlecheck','text','settings','bool');
		if(!in_array($ptype,$allowed))
		{
			\log_message('error',__METHOD__.' value:'.$ptype.' not allwed');
			return false;
		}
		$this->stype = $ptype;
		return $this;
	}

	public function setCategory($v)
	{
		$this->scategory = $v;
		return $this;
	}

	public function setDescname($d)
	{
		$this->descname = $d;
		return $this;
	}

	public function setValue($v = null)
	{
		$this->pvalue = $v;
		return $this;
	}

	public function setSerializedValue($v)
	{
		if(is_array($v))
		{
			$this->serializedvalue = serialize($v);
			return $this;
		}
		\log_message('error',__METHOD__.' incorrect value provided - expected array');
		return false;
	}

	public function setEnabled()
	{
		$this->is_enabled = TRUE;
		return $this;
	}

	public function setDisabled()
	{
		$this->is_enabled = FALSE;
		return $this;
	}

	public function setDescription($d)
	{
		$this->description = $d;
		return $this;
	}

}
