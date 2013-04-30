<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Rrpreference Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Rrpreference {


    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }
    public function getPreferences($name=null)
    {
        $res = $this->ci->j_cache->library('rrpreference', 'prefToArray', array('global'), '600');
        if(!empty($name))
        {
           if(isset($res[''.$name.'']))
           {
               return $res[''.$name.''];
           }
           else
           {
               return array();
           }
        }
        else
        {
           return $res;
        }
     
    }
    public function prefToArray($type)
    {
        $result = array();
        if($type == 'global')
        {
             $y = $this->em->getRepository("models\Preferences")->findAll();
             foreach($y as $r)
             {

               $result[''.$r->getName().''] = array('descname'=>$r->getDescname(), 'value'=>$r->getValue(), 'status'=>$r->getEnabled());
             }
     
        }
        return $result;
   
    }

}
