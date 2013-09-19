<?php

namespace models;


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
 * ProviderStatsDef Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * ProviderStatsDef Model
 *
 * This model for Statitstics  definitions for IdPs and SPs
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="providerstatsdef")
 * @author janusz
 */
class ProviderStatsDef {

   
    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * shortname def
     * @Column(type="string", length=20, nullable=false)
     */
    protected $shortname;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="statsdef")
     */
    protected $provider;

     /**
     * @OneToMany(targetEntity="ProviderStatsCollection",mappedBy="statdefinition")
     */
    protected $statistic;


    /**
     * definition typ like: system or external source
     * @Column(type="string", length=20, nullable=false)
     */
    protected $type;

    /**
     * httpprotocol for external source , usualy GET
     * @Column(type="string", length=5, nullable=true)
     */
     protected $httpprotocol;

    /**
     * when external source definition of collected stats format like image, rrd etc
     * @Column(type="string", length=20, nullable=true)
     */
    protected $formattype;

    /**
     * when external source is defined then sourceulr is where to collect stats from, it can be http(s)
     * @Column(type="string", length=512, nullable=true)
     */
    protected $sourceurl;

    /**
     * for externa source to define access type like anon, basicauth
     * Column(type="string", length=20, nullable=true)
     */
    protected $accesstype;

    /**
     * username for authn
     * Column(type="string", length=20, nullable=true)
     */
    protected $authuser;

    /**
     * pass for authn
     * Column(type="string", length=50, nullable=true)
     */
    protected $authpass;
    
    /**
     * additional options to be sent in GET/POST
     * serialized
     * Column(type="string", length=512,nullable=true)
     */
    protected $additionaloptions;

    /**
     * Column(type="text", nullable=false)
     */
    protected $description;


    /**
     * @Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;


    public function  getName()
    {
       return $this->shortname;
    }

    public function getProvider()
    {
       return $this->provider;
    }

    public function getStatistics()
    {
       return $this->statistic;
    }
    public function getType()
    {
       return $this->type;
    }
    public function getHttpProtocol()
    {
       return $this->httpprotocol;
    }

    public function getFormatType()
    {
       return $this->formattype;
    }
   
    public function getSourceUrl()
    {
       return $this->sourceurl;
    } 

    public function getAccessType()
    {
       return $this->accesstype;
    }
    public function getAuthUser()
    {
       return $this->authuser;
    }
    public function getAuthPass()
    {
       return $this->authpass;
    }
    public function getOptions()
    {
       $result = $this->additionaloptions;
       if(!empty($result))
       {
          $result = unserialize($result);
       }
       return $result;
    }
    
    public function getDescription()
    {
       return $this->description;
    } 
   

   
    public function setName($name)
    {
       $this->shortname = $name;
       return $this;
    } 


    /**
     * @prePersist 
     */
    public function created()
    {
         $this->createdAt = new \DateTime("now");
    }

    /**
     * @PreUpdate
     */
    public function updated()
    {
        $this->updatedAt = new \DateTime("now");
    }



}
