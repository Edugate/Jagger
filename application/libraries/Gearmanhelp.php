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
 * Gearmanhelp Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Gearmanhelp {

    function __construct() {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->library('curl');
        $this->curl= $this->ci->curl;
    }
    
    public function externalstatcollection($args)
    { 
        if(!array_key_exists('entityid',$args) or empty($args['entityid']) or !array_key_exists('defid',$args) or empty($args['defid']))
        {
            log_message('error','gearman worker didnt receive entityid or/and statdefid');
            return false;
        }
        $statdef = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id'=>''.$args['defid'].''));
        if(empty($statdef))
        {
           log_message('error','gworker: statdef not exists');
           return false;
        }
        $datastorage = $this->ci->config->item('datastorage_path');
        if(empty($datastorage))
        {
            log_message('error', 'Missing datastorage_path in config');
            return false;
        } 
        $statstorage = $datastorage.'stats/';
        if(!is_dir($statstorage))
        {
            log_message('error','directory '. $statstorage .'not exist');
            return false;
        }
      
        $gg = $this->curl->simple_get($args['url']); 


    }
}
