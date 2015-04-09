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
 * ServiceLocation Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * ServiceLocation Model
 *
 * This model for ServiceLocations definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="service_location")
 * @author janusz
 */
class ServiceLocation {

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string")
     */
    protected $type;

    /**
     * @Column(type="string",name="binding_name")
     */
    protected $bindingName;

    /**
     * @Column(type="string")
     */
    protected $url;

    /**
     * @Column(type="boolean")
     */
    protected $is_default;

    /**
     * @Column(type="integer",nullable=true)
     */
    protected $ordered_no;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="serviceLocations")
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     */
    protected $provider;

    public function __construct()
    {
        $this->is_default = FALSE;
    }

    /**
     * @PreUpdate
     */
    public function updated()
    {
        \log_message('debug', 'GG update time ');
        $p = $this->getProvider();
        if (!empty($p))
        {
            $p->updated();
        }
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setAsACS()
    {
        $this->type = 'AssertionConsumerService';
    }

    public function setBindingName($bindingname)
    {
        $this->bindingName = $bindingname;
        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setOrder($no)
    {
        $this->ordered_no = $no;
        return $this;
    }

    public function setOrderNull()
    {
        $this->ordered_no = null;
        return $this;
    }

    public function setDefault($default = NULL)
    {
        if ($default === TRUE)
        {
            $this->is_default = 1;
        }
        else
        {
            $this->is_default = 0;
        }
        return $this;
    }

    public function setProvider(Provider $provider = null)
    {
        $this->provider = $provider;
    }

    public function setRequestInitiator($url, $binding = NULL)
    {
        $this->url = $url;
        $this->type = 'RequestInitiator';
        if (empty($binding))
        {
            $this->bindingName = 'urn:oasis:names:tc:SAML:profiles:SSO:request-init';
        }
        else
        {
            $this->bindingName = $binding;
        }
        return $this;
    }

    public function setDiscoveryResponse($url, $index)
    {
        $this->type = 'DiscoveryResponse';
        $this->bindingName = 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol';
        $this->url = $url;
        $this->ordered_no = $index;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getBindingName()
    {
        return $this->bindingName;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getOrder()
    {
        return $this->ordered_no;
    }

    public function getDefault()
    {
        return $this->is_default;
    }

    public function convertToArray()
    {
        $s = array();
        $s['type'] = $this->getType();
        $s['binding'] = $this->getBindingName();
        $s['url'] = $this->getUrl();
        $s['order'] = $this->getOrder();
        $s['default'] = $this->getDefault();
        return $s;
    }

    public function importFromArray(array $s)
    {
        $this->setType($s['type']);
        $this->setBindingName($s['binding']);
        $this->setUrl($s['url']);
        $this->setOrder($s['order']);
        $this->setDefault($s['default']);
    }

    public function getServiceLocationToXML(\DOMElement $parent, $options = null)
    {
        $stype = $this->type;
        if (!empty($options))
        {
            if (($options === 'IDPSSODescriptor') && ($stype === 'SingleSignOnService'))
            {
                $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SingleSignOnService');
                $e->setAttribute("Binding", $this->bindingName);
                $e->setAttribute("Location", $this->url);
            }
            elseif (($options === 'SPSSODescriptor') && ($stype === 'AssertionConsumerService'))
            {
                $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:AssertionConsumerService');
                $e->setAttribute("Binding", $this->bindingName);
                $e->setAttribute("Location", $this->url);
                $e->setAttribute("index", $this->ordered_no);
                $is_defaultsrc = $this->getDefault();
                if (!empty($is_defaultsrc))
                {
                    $e->setAttribute("isDefault", 'true');
                }
            }
            else
            {
                $e = NULL;
            }
        }
        else
        {
            $e = NULL;
        }
        return $e;
    }

}
