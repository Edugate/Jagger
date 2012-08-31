<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * User_manage Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class User_manage {

    function __construct() {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
    }

    public function remove(models\User $user)
    {
        $this->em->remove($user);
        $personal_role = $this->em->getRepository("models\AclRole")->findOneBy(array('type'=>'user','name'=>$user->getUsername()));
        if(!empty($personal_role))
        {
           $this->em->remove($personal_role);
        }

        $this->em->flush();
    
    }

}
