<?php

namespace models;


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
 * Tracker Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Tracker Model
 *
 * This is a sample model to demonstrate how to use the AnnotationDriver
 *
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="tracker",indexes={@Index(name="resourcetype_idx", columns={"resourcetype"}),@Index(name="subtype_idx", columns={"subtype"})})
 * @author janusz
 */
class Tracker {


    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /* resourcetype like idp/sp/fed/user */

    /**
     * @Column(name="resourcetype", type="string" , length=25 , nullable=true,unique=false)
     */
    protected $resourcetype;

    /* subtype of resourcetype like idp->arp_download */

    /**
     * @Column(name="subtype", type="string" , length=25 , nullable=true,unique=false)
     */
    protected $subtype;

    /**
     * @Column(name="resourcename", type="string" , length=128 , nullable=true,unique=false)
     */
    protected $resourcename;

    /**
     * @Column(name="sourceip",type="string", length=40, nullable=true)
     */
    protected $sourceip;

    /**
     * @Column(name="useragent",type="string", length=128, nullable=true)
     */
    protected $agent;

    /**
     * @Column(name="user",type="string", length=256, nullable=true)
     */
    protected $user;

    /**
     * @Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @Column(name="detail",type="text",nullable=true)
     */
    private $detail;


    public function setResourceType($type = null)
    {
        $this->resourcetype = $type;
        return $this;
    }

    public function setResourceName($name = null)
    {
        $this->resourcename = $name;
    }

    public function setType($type = null)
    {
        $this->type = $type;
        return $this;
    }

    public function setSubType($subtype = null)
    {
        $this->subtype = $subtype;
        return $this;
    }

    public function setUser($user = null)
    {
        $this->user = $user;
        return $this;
    }

    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    public function setSourceIp($ip)
    {
        $this->sourceip = $ip;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getResourceType()
    {
        return $this->resourcetype;
    }

    public function getResourceName()
    {
        return $this->resourcename;
    }

    public function getSubType()
    {
        return $this->subtype;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getDetail()
    {
        return $this->detail;
    }

    public function getIp()
    {
        return $this->sourceip;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->createdAt;
    }

    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * @PrePersist 
     */
    public function created()
    {
        $this->createdAt = new \DateTime("now", new \DateTimeZone('UTC'));
        if (isset($_SERVER['REMOTE_ADDR']))
        {
            $this->sourceip = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $this->agent = $_SERVER['HTTP_USER_AGENT'];
        }
    }

}
