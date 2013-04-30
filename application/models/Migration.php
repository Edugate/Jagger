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
 * Migration Model
 *
 * This model for store app migrations versions
 * 
 * @Entity
 * @Table(name="migrations")
 * @author janusz
 */
class Migration {


    /**
     * @Id
     * @Column(name="version",type="bigint", unique=true)
     */
    protected $version = 0;

    
    public function setVersion($v)
    {
        $this->version = $v;
        return $this;
    }

    public function getVersion()
    {
       return $this->version;
    }



}
