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
 * RR3 Helpers
 *
 * @package     RR3
 * @subpackage  Helpers
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


function showHelp($string)
{
     $h = '&nbsp;<span title="'. htmlentities($string).'"><img src="'.base_url().'images/icons/question.png"></span>';
     return $h;
}
function genIcon($type, $title=null)
{
    $preurl = base_url().'images/icons/';
    
    $icons = array(
          'locked' => 'lock.png',
          'disabled' => 'minus-button.png',
          'expired' => 'calendar--minus.png',
          'mstatic' => 'ui-toolbar--arrow.png',
          'external'=> 'tag-cloud.png',
          'noeditperm' => 'pencil-prohibition.png',
          'edit' => 'pencil-field.png',
          'bookmarkadd' => 'star--plus.png',
          );
    if(array_key_exists($type,$icons))
    {
         $result = '<img src="'.$preurl.$icons[$type].'" title="'.$title.'"/>';
         return $result;
    }
    else
    {
        return null;
    }

}
