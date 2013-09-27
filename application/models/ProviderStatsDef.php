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
     * title for stats
     * @Column(type="string", length=128, nullable=false)
     */
     protected $titlename;

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
     * allowed values: ext,sys
     * @Column(type="string", length=20, nullable=false)
     */
    protected $type;

    /**
     * used  if type==sys 
     * @Column(type="string", length=50, nullable=true)
     */
    protected $predefinedcol;

    /**
     * http methods for external source GET/POST , default GET
     * @Column(type="string", length=5, nullable=true)
     */
     protected $method;

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
     * @Column(type="string", length=20, nullable=true)
     */
    protected $accesstype;

    /**
     * username for authn
     * @Column(type="string", length=20, nullable=true)
     */
    protected $authuser;

    /**
     * pass for authn
     * @Column(type="string", length=50, nullable=true)
     */
    protected $authpass;

    /**
     * @Column(type="text" , nullable=true)
     */
    protected $displayoptions;

    /**
     * @Column(type="text" , nullable=true)
     */
    protected $postoptions;
    

    /**
     * @Column(type="text", nullable=false)
     */
    protected $description;

    /**
     * @Column(type="boolean", nullable=true)
     */
     protected $overwrite;


    /**
     * @Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    public function getId()
    {
       return $this->id;
    }

    public function  getName()
    {
       return $this->shortname;
    }
    
    public function getTitle()
    {
       return $this->titlename;
    }

    public function getProvider()
    {
       return $this->provider;
    }
  
    public function getSysDef()
    {
      return $this->predefinedcol;
    }

    public function getStatistics()
    {
       return $this->statistic;
    }
    public function getType()
    {
       return $this->type;
    }
    public function getHttpMethod()
    {
       return $this->method;
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
    public function getPostOptions()
    {
       $result = $this->postoptions;
       if(!empty($result))
       {
          $result = unserialize($result);
       }
       return $result;
    }


    public function getDisplayOptions()
    {
        $result = $this->displayoptions;
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

    public function getOverwrite()
    {
       return $this->overwrite;
    }
   

   
    public function setName($name)
    {
       $this->shortname = $name;
       return $this;
    } 
 
    public function setTitle($title)
    {
       $this->titlename = $title;
       return $this;
       
    }
    public function setType($t)
    {
       $this->type = $t;
       return $this;
    }
    public function setDescription($desc)
    {
       $this->description = $desc;
       return $this;
    }
    public function setHttpMethod($method)
    {
        $this->method = $method;
        return $this;
    }
    public function setUrl($url)
    {
      $this->sourceurl = $url;
      return $this;
    }
    public function setFormatType($t)
    {
       $this->formattype = $t;
       return $this;
    }
     
    public function setAccess($type)
    {
       $this->accesstype= $type;
       return $this;
    }
    public function setAuthuser($u)
    {
       $this->authuser = $u;
       return $this;
    }
    public function setAuthpass($p)
    {
       $this->authpass = $p;
       return $this;
    }

    public function setPostOptions($arr=null)
    {
       if(!empty($arr))
       {
           $this->postoptions = serialize($arr);
       }
       else
       {
          $this->postoptions = null;
       }
       return $this;
    }

    public function  setProvider(Provider $provider)
    {
         $this->provider = $provider;
         return $this;   
    }

    public function setDisplayOptions($opt=null)
    {
        if(!empty($opt))
        {
           if(is_array($opt))
           {
               $this->displayoptions = serialize($opt);
           }
           else
           {
              log_message('error','array expected');
           }
        }
        else
        {
            $this->displayoptions = null;
        }
        
    }

    /**
     * @prePersist 
     */
    public function created()
    {
         $this->createdAt = new \DateTime("now");
         $this->updatedAt = new \DateTime("now");
    }

    /**
     * @PreUpdate
     */
    public function updated()
    {
        $this->updatedAt = new \DateTime("now");
    }



}
