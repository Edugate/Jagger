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
 * FederationValidator Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * FederationValidator Model
 *
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="fedvalidator")
 * @author janusz
 */
class FederationValidator {


   
    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * name
     * @Column(type="string", length=20, nullable=false)
     */
    protected $name;

    /**
     * @ManyToOne(targetEntity="Federation",inversedBy="fvalidator")
     */
    protected $federation;

    /**
     * @Column(name="is_enabled",type="boolean", nullable=false)
     */
    protected $isEnabled;

    /**
     * @Column(type="string",length=256, nullable=false)
     */
    protected $url;

    /**
     * @Column(type="string", length=4,nullable=false)
     */
    protected $method;

    /**
     * arg name that should be used to pass url of entity metadata
     * @Column(name="entityparam",type="string", length=32, nullable=false)
     */
    protected $entityParam;

    /**
     * additional args sent in request
     * @Column(type="text", nullable=true)
     */
     protected $optargs;

    /**
     * args seprator
     * @Column(type="string", length=10, nullable=true)
     */
     protected $argseparator;

     /**
      * @Column(name="documenttype",type="string",length=20,nullable=false)
      */
     protected $documentType;

    /**
     * @Column(type="text", nullable=false)
     */
    protected $description;

    /**
     * @Column(type="string", length=256, nullable=false)
     */
    protected $returncodeelement;
    /**
     * @Column(type="string", length=512, nullable=false)
     */
    protected $returncodevalue;
    
    /**
     * @Column(type="string", length=256, nullable=false)
     */
    protected $messagecodeelement;




    /**
     * @Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;


  

    public function __construct()
    {
        $this->isEnabled = false;
        $this->documentType = 'xml';
    }

    public function getId()
    {
       return $this->id;
    }
    public function getName()
    {
       return $this->name;
    }

    public function getFederation()
    {
       return $this->federation;
    }
    public function getEnabled()
    {
       return  $this->isEnabled;
    }
    public function getMethod()
    {
       return $this->method;
    }
    public function getUrl()
    {
       return $this->url;
    }
    public function getEntityParam()
    {
       return $this->entityParam;
    } 
    public function getOptargs()
    {
      return unserialize($this->optargs);
    }
    public function getOptargsToInputStr()
    {
        $result ='';
        $first = true;
        $o = $this->getOptargs();
        foreach($o as $k=>$v)
        {
           if(!$first)
           {
              $result .= '$$'.$k;
           }
           else
           {
              $first=false;
              $result .= $k;
           }
           if(isset($v))
           {
              $result .= '$:$'.$v;
           }
           

        }
        return $result;
    }
    public function getSeparator()
    {
      return $this->argseparator;
    }
    public function getDocutmentType()
    {
      return $this->documentType;
    }
    public function getDescription()
    {
      return $this->description;
    }
    public function getReturnCodeElement()
    {
      if(!empty($this->returncodeelement))
      {
         return unserialize($this->returncodeelement);
      }
      else
      {
         return array();
      }
    }
    public function getReturnCodeValues()
    {
        if(!empty($this->returncodevalue))
        {
           return unserialize($this->returncodevalue);
        }
        else{
           return array();
       }
    }

    public function getMessageCodeElements()
    {
         return unserialize($this->messagecodeelement);
    }


    public function  setName($name)
    {
        $this->name = trim($name);
        return $this;
    }
    public function setFederation(Federation $federation)
    {
        $this->federation = $federation;
        return $this;
    }
    public function setEnabled($arg)
    {
        $this->isEnabled = $arg;
        return $this;
    }
    public function setUrl($url)
    {
        $this->url = trim($url);
        return $this;
    }
    public function setMethod($method)
    {
        if(strcasecmp($method,'GET') == 0 || strcasecmp($method,'POST') == 0)
        {
           $this->method = strtoupper($method);
           return $this;
        }
        else
        {
           \log_message('error',__METHOD__.' received incorrect method value');
        }
    }
   
    public function setEntityParam($param)
    {
        $this->entityParam = $param;
    } 

    public function  setSeparator($separator)
    {
        $this->argseparator = trim($separator);
    }
    public function  setOptargs(array $args=null)
    {
        if(!empty($args))
        {
           $this->optargs = serialize($args);
        }
        else
        {
           $this->optargs = serialize(array());
        }
    }
    public function addOptarg($arg, $value=null)
    {
        $tmpargs = $this->getOptargs();
        $tmpargs[] = array(''.trim($arg).''=>trim($value));
        $this->setOptargs($tmpargs);
        return $this;
    }
    public function  delOptarg($arg)
    {
        $tmpargs = $this->getOptargs();
        foreach($tmpargs as $k=>$v)
        {
            if(array_key_exists($arg,$v))
            {
                 unset($tmpargs[$k]);
            }
        }
        $this->setOptargs($tmpargs);
        return $this;
    }
    public function setDocumentType($type)
    {
        if(strcasecmp($type,'xml')==0)
        {
            $this->documentType = strtolower($type);
            return $this;
        }
    }
    public function setDescription($desc)
    {
        $this->description = $desc;
        return $this;
    }
    public function setReturnCodeElement(array $element)
    {
        $this->returncodeelement = serialize($element);
        return $this;
    }
    /**
     * @todo finish 
     */
    public function setReturnCodeValue(array $values)
    {
       if(empty($values))
       {
          $values=array();
       }
        $this->returncodevalue = serialize($values);
        return $this;
    }
    public function setMessageElement(array $msgs)
    {
        if(empty($msgs))
        {
            $msgs = array();
        }
        $this->messagecodeelement = serialize($msgs);
        return $this;

    }

    /**
     * @prePersist 
     */
    public function created()
    {
        $this->createdAt = new \DateTime("now",new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime("now",new \DateTimeZone('UTC'));
        if(empty($this->optargs))
        {
           $this->optargs = serialize(array());
        }
    }

     /**
     * @PreUpdate
     */
    public function updated()
    {
        $this->updatedAt = new \DateTime("now",new \DateTimeZone('UTC'));
    }


}
