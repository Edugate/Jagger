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
 * ProviderStatsCollection Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * ProviderStatsCollection Model
 *
 * This model for Statitstics  collection for IdPs and SPs
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="providerstatscollection")
 * @author janusz
 */
class ProviderStatsCollection {


    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="statistic")
     */
    protected $provider;

    /**
     * @ManyToOne(targetEntity="ProviderStatsDef",inversedBy="statistic")
     */
    protected $statdefinition;

    /**
     * @Column(type="string",length=15,nullable=false);
     */
     protected $format;

    /**
     * @Column(type="string",length=50, nullable=false)
     */
    protected $statfilename;

    /**
     * @Column(name="created_at", type="datetime")
     */
    protected $createdAt;


    public function __construct()
    {

    }

    public function getId()
    {
        return $this->id;
    }
    public function getFilename()
    {
        return $this->statfilename;
    }
    public function getFormat()
    {
        return $this->format;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getStatDefinition()
    {
        return $this->statdefinition;
    }
   
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setProvider($p)
    {
        $this->provider = $p;
        return $this;
    }
  
    public function setStatDefinition($s)
    {
        $this->statdefinition = $s;
        return $this;
    }

    public function setFormat($f)
    {
        $this->format = $f;
        return $this;
     }
    public function setFilename($f)
    {

        $this->statfilename = $f;
        return $this;
    }
      
    public function updateDate()
    {
        $this->createdAt = new \DateTime("now", new \DateTimeZone('UTC'));
    }

    /**
     * @prePersist 
     */
    public function created()
    {
         $this->createdAt = new \DateTime("now",new \DateTimeZone('UTC'));
    }


    


}

