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
 * Current_User Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Current_User {

    private static $user;

    public function __construct() {
        $this->_CI = & get_instance();
    }

    public static function user() {
        if (!isset(self::$user)) {
            $user_id = $this->CI->session->userdata('user_id');
            if (!$user_id) {
                return FALSE;
            }
            $u = $this->em->getRepository("models\User")->findOneById($user_id);
            if (!$u) {
                return FALSE;
            }
            self::$user = $u;
        }
        return self::$user;
    }

}
