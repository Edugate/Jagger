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
 * AttributeRequirements Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class AttributeRequirements
{

    protected $em;

    public function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;

    }

	public function  getRequirementsBySP(Provider $sp)
	{
		$req = $this->em->getRepository("models\AttributeRequirement")->findBy(array('type'=>'SP','sp_id'=>$sp->getId()));
		return $req;
	}

	public function  getRequirementsBySPs(array $sps)
	{
                $result = array();
                if(count($sps)>0)
                {
		   $req = $this->em->getRepository("models\AttributeRequirement")->findBy(array('type'=>'SP','sp_id'=>$sps));
                   foreach($req as $r)
                   {
                      $result[$r->getSP()->getId()][]=$r;
                   }
                }
		return $result;
	}

	public function  getRequirementsByFed(Federation $fed)
	{
		$req = $this->em->getRepository("models\AttributeRequirement")->findBy(array('type'=>'FED','fed_id'=>$fed->getId()));
		return $req;
	}
	public function getAllRequirements()
	{
		$req = $this->em->getRepository("models\AttributeRequirement")->findAll();

		return $req;
		
	}
}
