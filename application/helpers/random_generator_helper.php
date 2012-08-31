<?php if (!defined('BASEPATH')) exit('No direct script access allowed.');
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
 * RR3 Helpers
 *
 * @package     RR3
 * @subpackage  Helpers
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

function str_generator()
{
     $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
     $length = 10;
     $string = '';
     for ($p = 0; $p < $length; $p++)
     {
           $string .= $characters[mt_rand(0, (strlen($characters))-1)];
     }
     return $string;

}

	

