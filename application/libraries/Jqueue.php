<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * the class is going to replace J_queue class
 */

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Jqueue
{
    private $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function getAccessableQueues(){
        /**
         * @var  models\Queue[] $queues
         */
        $queues = $this->em->getRepository('models\Queue')->findAll();
        $result = array();
        if(!is_array($queues)){
            return $result;
        }

        foreach ($queues as $q){
            $access = $this->ci->jqueueaccess->hasQAccess($q);
            if($access !== true){
                continue;
            }
            $result[] = $q;
        }
        return $result;

    }

}
