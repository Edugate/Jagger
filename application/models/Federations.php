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
 * Federations Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Federations
{

    protected $federations;
    protected $em;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;

        $this->federations = new ArrayCollection();
    }

    public function getFederations()
    {
        $this->federations = $this->em->getRepository("models\Federation")->findBy(array(), array('name' => 'ASC'));
        return $this->federations;
    }

    public function getAllIdNames()
    {

          $query = $this->em->createQuery("
              SELECT p.id, p.name from models\Federation as p ORDER BY p.name ASC
          ");
          $result = $query->getResult();
          return $result;
    }



    public function getOneByName($name)
    {
        $this->federations = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $name));
        return $this->federations;
    }
    public function getOneByUrn($name)
    {
        $this->federations = $this->em->getRepository("models\Federation")->findOneBy(array('urn' => $name));
        return $this->federations;
    }

    public function getOneFederationById($id)
    {
        $this->federations = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $id));
        return $this->federations;
    }

    public function getFederationsByIds($id_pool)
    {

        $this->federations = $this->em->getRepository("models\Federation")->findBy(array('id' => $id_pool));
        return $this->federations;
    }

    public function getPublicFederations()
    {
        $this->federations = $this->em->getRepository("models\Federation")->findBy(array('is_public' => true));
    }

}
