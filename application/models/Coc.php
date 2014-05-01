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
 * Coc Model
 * 
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="coc")
 * @author janusz
 */
class Coc {

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255, nullable=false, unique=true)
     */
    protected $name;

    /**
     * @Column(type="string", length=512, nullable=false )
     */
    protected $url;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $cdescription;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $is_enabled;

    /**
     * @ManyToMany(targetEntity="Provider",mappedBy="coc")
     */
    protected $provider;
 
    public function __construct()
    {
        $this->is_enabled = FALSE;
    }
     
    public function getId()
    {
        return $this->id;
    }
 
    public function getName()
    {
        return $this->name;   
    }
     
    public function getUrl()
    {
        return $this->url;
    }
    public function getAvailable()
    {
        return (boolean) $this->is_enabled;
    }
    public function getDescription()
    {
        return $this->cdescription;
    }

    public function getProviders()
    {
       return $this->provider;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    public function setDescription($desc)
    {
        $this->cdescription = $desc;
        return $this;
    }
    public function setProvider($provider)
    {
        $this->getProviders()->add($provider);
        return $this;
    }
    public function setAvailable($a=NULL)
    {
        if($a === TRUE)
        {
           $this->is_enabled  = TRUE;
        }
        else
        {
           $this->is_enabled = FALSE;
        }
        return $this;
    }

}
