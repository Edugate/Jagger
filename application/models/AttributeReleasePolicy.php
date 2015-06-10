<?php
namespace models;
/**
 * Jagger
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2015, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * AttributeReleasePolicy Class
 * 
 * @package     Jagger
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
/**
 * AttributeReleasePolicy Model
 *
 *
 * @Entity
 * @Table(name="attribute_release_policy", indexes={@Index(name="requester_idx", columns={"requester"})})
 * @author janusz
 */
class AttributeReleasePolicy {

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string",nullable=false, length=10)
     */
    protected $type;

    /**
     * @ManyToOne(targetEntity="Attribute")
     * @JoinColumn(name="attribute_id", referencedColumnName="id")
     */
    protected $attribute;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="attributeReleaseIDP")
     * @JoinColumn(name="idp_id", referencedColumnName="id")
     */
    protected $idp;

    /**
     * null/fed_id/sp_id/entcat_id
     * @Column(type="integer",nullable=true)
     */
    protected $requester;

    /**
     * 0=never release;
     * 1=only if required;
     * 2=required or desired
     *
     * @Column(type="integer",nullable=true)
     */
    protected $policy;

    /**
     * contains serialized array. array may look like:
     *  array('permit'=>array('joe@heanet.ie','other permit str'),'deny'=>array('denied_example_str'))
     * @Column(type="text", nullable=true)
     */
    protected $rawdata;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getProvider()
    {
        return $this->idp;
    }

    public function getPolicy()
    {
        return $this->policy;
    }

    public function getRequester()
    {
        return $this->requester;
    }


    public function getRelease()
    {
        $result = "";
        $p = $this->getPolicy();
        if ($p == 0)
        {
            $result = "never permit";
        }
        elseif ($p == 1)
        {
            $result = "permit only if required";
        }
        elseif ($p == 2)
        {
            $result = "permit when required or desired";
        }
        return $result;
    }

    public function getRawdata()
    {
        return unserialize($this->rawdata);
    }

    /**
     * types: supported,global
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    public function setProvider(Provider $provider)
    {
        $this->idp = $provider;
        return $this;
    }

    public function setRequester($requester = null)
    {
        $this->requester = $requester;
        return $this;
    }

    public function setPolicy($policy = null)
    {
        $this->policy = $policy;
        return $this;
    }

    public function setPermitOnlyIfRequired()
    {
        $this->setPolicy(1);
        return $this;
    }

    public function setPermitNever()
    {
        $this->setPolicy(0);
        return $this;
    }

    public function setPermitAnyRequest()
    {
        $this->setPolicy(2);
        return $this;
    }

    public function setSupportedAttribute(Provider $idp, Attribute $attribute)
    {
        $this->setAttribute($attribute);
        $this->setProvider($idp);
        $this->setRequester(null);
        $this->setPolicy(null);
        $this->setType('supported');
    }

    /**
     * set global policy for Provider - Attribute
     */
    public function setGlobalPolicy(Provider $idp, Attribute $attribute, $policy)
    {
        $this->setAttribute($attribute);
        $this->setProvider($idp);
        $this->setRequester(null);
        $this->setType('global');
        $this->setPolicy($policy);
        return $this;
    }
  /**
     * set global policy for Provider - Attribute
     */
    public function setFedPolicy(Provider $idp, Attribute $attribute, Federation $federation, $policy)
    {
        $this->setAttribute($attribute);
        $this->setProvider($idp);
        $this->setRequester($federation->getId());
        $this->setType('fed');
        $this->setPolicy($policy);
        return $this;
    }
    public function setSpecificPolicy(Provider $idp, Attribute $attribute, $requester, $policy)
    {
        $this->setAttribute($attribute);
        $this->setProvider($idp);
        $this->setRequester($requester);
        $this->setType('sp');
        $this->setPolicy($policy);
        return $this;
    }

    public function setEntCategoryPolicy(Provider $idp, Attribute $attribute, $requester, $policy)
    {
        $this->setAttribute($attribute);
        $this->setProvider($idp);
        $this->setRequester($requester);
        $this->setType('entcat');
        $this->setPolicy($policy);
        return $this;
    }

    public function setRawdata($rawdata)
    {
        $this->rawdata = serialize($rawdata);
        return $this;
    }

}
