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
     * AttributeRequirement Class
     *
     * @package     RR3
     * @subpackage  Models
     * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
     */


/**
 * AttributeRequirement Model
 *
 *
 * @Entity
 * @Table(name="attribute_requirement",indexes={@Index(name="type_idx", columns={"type"})})
 * @author janusz
 */
class AttributeRequirement
{

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Attribute")
     * @JoinColumn(name="attribute_id", referencedColumnName="id",nullable=false)
     */
    protected $attribute_id;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="attributeRequirement")
     * @JoinColumn(name="sp_id", referencedColumnName="id")
     */
    protected $sp_id;

    /**
     * @ManyToOne(targetEntity="Federation",inversedBy="attributeRequirement")
     * @JoinColumn(name="fed_id", referencedColumnName="id")
     */
    protected $fed_id;

    /**
     * types: SP,FED
     * @Column(type="string", length=5)
     */
    protected $type;

    /**
     * default values are desired or required
     * @Column(type="string", length=10)
     */
    protected $status;

    /**
     * @Column(type="string")
     */
    protected $reason;




    function __construct()
    {
        $this->reason = '';
    }
    public function setAttributeId($id)
    {
        $this->attribute_id = $id;
        return $this;

    }

    public function setAttribute(Attribute $attribute)
    {
        $this->attribute_id = $attribute;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setSP(Provider $provider)
    {
        $this->sp_id = $provider;
        return $this;
    }

    public function setFed(Federation $federation)
    {
        $this->fed_id = $federation;
        return $this;
    }

    /**
     * set required or desired
     * todo: validate $status if equals required or desired
     */
    public function setStatus($status)
    {

        $this->status = $status;
        return $this;
    }

    public function setReason($reason = null)
    {
        if(is_null($reason))
        {
            $reason = '';
        }
        $this->reason = $reason;
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

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute_id;
    }

    /**
     * @return Federation|null
     */
    public function getFederation()
    {
        return $this->fed_id;
    }

    /**
     * @return Provider|null
     */
    public function getSP()
    {
        return $this->sp_id;
    }

    public function getFed()
    {
        return $this->fed_id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getStatusToInt()
    {
        if(strcmp($this->status[0],'r')==0 )
        {
            return 1;
        }
        return 2;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function isRequiredToStr()
    {
        if(strcmp($this->status[0],'r')==0 )
        {
            return 'true';
        }
        return 'false';
    }

}
