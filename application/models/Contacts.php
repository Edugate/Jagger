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
 * Contacts Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Contacts
{

    protected $cnt;
    protected $em;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;

        $this->cnt = new ArrayCollection();
    
        
    }
    
    public function getContacts()
    {
         $this->cnt = $this->em->getRepository("models\Contact")->findBy(array(), array('id' => 'ASC'));
         return $this->cnt;
    }
    public function getContactsByProvidersIds(array $ids)
    {
         $this->cnt = $this->em->getRepository("models\Contact")->findBy(array( 'provider'=>$ids ), array('id' => 'ASC'));
         return $this->cnt;
    }
}
