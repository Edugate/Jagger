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
 * ExtentMetadata Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * ExtentMetadata Model
 *
 * This is a model contains extended ionformation for IDP and SP like geolocation 
 * logo etc
 *
 * @Entity
 * @Table(name="extendmetadata")
 * @author janusz
 */
class ExtendMetadata {

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * type idp,sp - because some entities may be both so we need to split it
     */

    /**
     * @Column(name="etype",type="string",length=12)
     */
    protected $etype;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="extend",cascade={"persist"})
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     */
    protected $provider;

    /**
     * @Column(name="namespace",type="string",length=32)
     */
    protected $namespace;

    /**
     * @ManyToOne(targetEntity="ExtendMetadata",inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @OneToMany(targetEntity="ExtendMetadata", mappedBy="parent",cascade={"persist", "remove"})
     */
    protected $children;

    /**
     * @Column(name="element",type="string",length=32)
     */
    protected $element;

    /**
     * @Column(name="evalue",type="text",nullable=true)
     */
    protected $evalue;

    /**
     * @Column(name="attrs",type="string",length=255,nullable=true)
     */
    protected $attrs;

    function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->etype;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getElement()
    {
        return $this->element;
    }

    public function getEvalue()
    {
        return $this->evalue;
    }

    public function getLogoValue()
    {
        $this->ci = & get_instance();

        if(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $this->evalue, $matches))
        {
            return $this->evalue;
        }
        elseif ((substr($this->evalue, 0, 5)) === 'data:')
        {
            return $this->evalue;
        }
        else
        {
            $logobasepath = $this->ci->config->item('rr_logouriprefix');
            $logobaseurl = $this->ci->config->item('rr_logobaseurl');
            if (empty($logobaseurl))
            {
                $logobaseurl = base_url();
            }
            $logourl = $logobaseurl . $logobasepath;
            return $logourl . $this->evalue;
        }


    }

    public function getElementValue()
    {
        if (!empty($this->element) && $this->element == 'GeolocationHint')
        {
            $val = 'geo:' . $this->evalue;
            return $val;
        }
        else
        {
            return $this->evalue;
        }
    }

    public function getAttributes()
    {
	    if(is_null($this->attrs))
	    {
		    return array();
	    }
        return unserialize($this->attrs);
    }

    public function getValueAndLang()
    {
        $attr = $this->getAttributes();
        $lang = '';
        if(array_key_exists('xml:lang',$attr))
        {
            $lang = $attr['xml:lang'];
        }
        return $lang.':'.$this->evalue;
    }

    public function setType($type)
    {
        $this->etype = $type;
    }

    public function setProvider(Provider $provider = null)
    {
        $this->provider = $provider;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    public function setParent($parent = null)
    {
        $this->parent = $parent;
    }

    public function setElement($element)
    {
        $this->element = $element;
    }

    public function setValue($value)
    {

        $this->evalue = $value;
    }


    public function populateWithNoProvider(ExtendMetadata $parent,$type,$namespace,$value,$element,$attrs)
    {
        $this->setParent($parent);
        $this->etype = $type;
        $this->namespace = $namespace;
        $this->setValue($value);
        $this->element = $element;
        $this->setAttributes($attrs);
        return $this;
    }

    public function setLogoNoProvider($filename,ExtendMetadata $parent,array $attrs, $type)
    {
        $this->setValue($filename);
        $this->setParent($parent);
        $this->namespace = 'mdui';
        $this->element = 'Logo';
        $this->etype = $type;
        $this->setAttributes($attrs);
        return $this;
    }

    public function setLogo($filename, Provider $provider, ExtendMetadata $parent, array $attrs, $type)
    {
        $this->setValue($filename);
        $this->setProvider($provider);
        $this->setParent($parent);
        $this->setNamespace('mdui');
        $this->setElement('Logo');
        $this->setType($type);
        $this->setAttributes($attrs);
    }

    public function setGeoLocation($location, Provider $provider, ExtendMetadata $parent, $type)
    {
        $this->setNamespace('mdui');
        $this->setElement('GeolocationHint');
        $this->setValue($location);
        $this->setProvider($provider);
        $this->setParent($parent);
        $this->setType($type);
        $attrs = array();
        $this->setAttributes($attrs);
    }

    public function setAlgorithmMethod($value,$method)
    {
        $this->setType('ent');
        $this->setNamespace('alg');
        $this->setValue(trim($value));
        $this->setElement($method);
        return $this;
    }


    public function setAttributes(array $attrs)
    {

        $this->attrs = serialize($attrs);
    }

    public function addAttribute($name, $value)
    {
        $attrs = $this->getAttributes();
        $attrs[$name] = $value;
        $this->setAttributes($attrs);
    }

    public function createDiscoHintParent()
    {
        $this->setType('idp');
        $this->setNamespace('mdui');
        $this->setElement('DiscoHints');
        return $this;
    }

}
