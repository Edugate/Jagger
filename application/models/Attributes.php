<?php
namespace models;

use \Doctrine\Common\Collections\ArrayCollection;


/**
 * Attributes Class
 *
 * @package    Jagger
 * @subpackage Models
 * @author     Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright  2016, HEAnet Limited (http://www.heanet.ie)
 * @license    MIT http://www.opensource.org/licenses/mit-license.php
 */
class Attributes
{

    protected $attributes;
    protected $ci;
    /**
     * @var \Doctrine\ORM\EntityManager $em
     */
    protected $em;


    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->attributes = new ArrayCollection();
    }

    public function getAttributes() {
        return $this->em->getRepository("models\Attribute")->findBy(array(), array('name' => 'ASC'));
    }

    public function getAttributeById($attrId) {
        return $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $attrId));

    }

    public function getAttributeByName($name) {
        return $this->em->getRepository("models\Attribute")->findOneBy(array('name' => $name));

    }

    public function getAttributesToArrayById() {
        /**
         * @var Attribute[] $tmp
         */
        $tmp = $this->em->getRepository("models\Attribute")->findAll();
        $result = array();
        foreach ($tmp as $attr) {
            $result['' . $attr->getId() . ''] = $attr;
        }
        return $result;
    }

    /**
     * @param $attrid
     * @return array|null
     */
    public function getAttributeUsageById($attrid){
        /**
         * @var Attribute $attribute
         */
        $attribute = $this->em->getRepository('models\Attribute')->findOneBy(array('id' => $attrid));
        if (null === $attribute) {
            return null;
        }
        /**
         * @var AttributeReleasePolicy[] $attrSupportcol
         * @var AttributeRequirement[] $attrReq
         */
        $attrSupportcol = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('attribute' => $attribute, 'type' => 'supported'));
        $attrReq = $this->em->getRepository('models\AttributeRequirement')->findBy(array('attribute_id' => $attribute));
        $col1 = array();
        $col2 = array();
        foreach ($attrSupportcol as $a) {
            $p = $a->getProvider();
            $col1[] = array(
                'entityid'  => $p->getEntityId(),
                'is_local'  => $p->getLocal(),
                'is_active' => $p->getActive(),
                'registrar' => $p->getRegistrationAuthority()
            );
        }
        foreach ($attrReq as $a) {
            $ptype = $a->getType();
            if ($ptype === 'sp') {
                $p = $a->getSP();
                $col2[] = array(
                    'entityid'  => $p->getEntityId(),
                    'type'      => 'provider',
                    'is_local'  => $p->getLocal(),
                    'is_active' => $p->getActive(),
                    'registrar' => $p->getRegistrationAuthority()
                );
            } elseif ($ptype === 'fed') {
                $f = $a->getFederation();
                $col2[] = array(
                    'name' => $f->getName(),
                    'type' => 'federation'
                );
            }

        }

        $result = array(
            'type'             => 'attribute',
            'id'               => $attribute->getId(),
            'name'             => $attribute->getName(),
            'fullname'         => $attribute->getFullname(),
            'saml2_name'       => $attribute->getOid(),
            'saml1_name'       => $attribute->getUrn(),
            'description'      => $attribute->getDescription(),
            'visible_metadata' => $attribute->showInMetadata(),
            'supported_by'     => $col1,
            'requested_by'     => $col2,

        );

        return $result;
    }

}
