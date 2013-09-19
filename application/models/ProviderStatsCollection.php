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
     * @Column(type="string",length=50, nullable=false)
     */
    protected $statfilename;

    /**
     * @Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @prePersist 
     */
    public function created()
    {
         $this->createdAt = new \DateTime("now");
    }


}

