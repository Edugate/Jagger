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
 * Gearmanw Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Gearmanw {

    function __construct() {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
    }
    
    public function externalstatcollection($args)
    { 
       print_r($args);
       return true;
    }
    static public function fn_externalstatcollection($job)
    {
        $args = unserialize($job->workload());
       
        $y = new Gearmanhelp();
        echo get_class($y);
        $m = $y->externalstatcollection($args);
        return;



        
        
        
    }

    private function registerExtStatCollectorWorker()
    {
       $gm=new GearmanWorker();
       $gm->addServer('127.0.0.1',4730);
       $gm->addFunction('externalstatcollection','Gearmanw::fn_externalstatcollection');
       while($gm->work());

    }
 
    public function worker()
    {
        $this->registerExtStatCollectorWorker();
    }

}
