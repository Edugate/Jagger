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
 * Attributes Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class Attributes
{

    protected $attributes;
    protected $ci;
    protected $em;


    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;

        $this->attributes = new ArrayCollection();
    }

    public function getAttributes()
    {
        return $this->em->getRepository("models\Attribute")->findBy(array(), array('name' => 'ASC'));
    }

    public function getAttributeById($id)
    {
        return $this->em->getRepository("models\Attribute")->findOneBy(array('id'=>$id));
          
    }
    public function getAttributeByName($name)
    {
        return $this->em->getRepository("models\Attribute")->findOneBy(array('name'=>$name));
          
    }

    public function getAttributesToArrayById()
    {
        $tmp = $this->em->getRepository("models\Attribute")->findAll();
        $result = array();
        foreach($tmp as $attr)
        {
            $result[''.$attr->getId().'']= $attr;
        }
        return $result;
    }

}
