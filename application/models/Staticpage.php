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
 * Staticpage Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Staticpage Model
 *
 * This model for static articles
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="staticpage")
 * @author janusz
 */
class Staticpage {

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @Column(type="string", length=25, nullable=false, unique=true)
     */
    protected $pcode;
   
    /**
     * @Column(type="string", length=25, nullable=true)
     */
    protected $pcategory;

    /**
     * @Column(type="string", length=128, nullable=true)
     */
    protected $ptitle;
    
    /**
     * @Column(type="text",nullable=true)
     */
    protected $ptext;

    /**
     * @Column(name="ispublic", type="boolean")
     */
    protected $ispublic;

    /**
     * @Column(name="enabled", type="boolean")
     */
    protected $enabled;
   
    /**
     * @Column(name="created_at", type="datetime")
     */
    private $createdAt;
    /**
     * @Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function __construct()
    {

        $this->updatedAt = new \DateTime("now");
    }


    public function getId()
    {
       return $this->id;
    }
    public function getName()
    {
       return $this->pcode;
    }
    public function getTitle()
    {
       return $this->ptitle;
    }
    public function getCategory()
    {
       return $this->pcategory;
    }
    public function getContent()
    {
       return $this->ptext;
    }
    public function getEnabled()
    {
       return $this->enabled;
    }
    public function getPublic()
    {
       return $this->ispublic;
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
        \log_message('debug', 'GG update providers updated time');
        $this->updatedAt = new \DateTime("now");
    }
}
