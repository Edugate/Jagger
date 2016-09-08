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

}
