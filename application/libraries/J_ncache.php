<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2015, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * J_ncache Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class J_ncache
{

    protected $ci;
    protected $keyprefix;

    public function __construct()
    {
        $this->ci = & get_instance();
        $this->keyprefix = getCachePrefix();
        $this->ci->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $this->keyprefix));
    }

    public function cleanProvidersList($type)
    {
        $guilangs = MY_Controller::guiLangs();
        $langs = array_keys($guilangs);
        foreach($langs as $v)
        {
            $cachedid = $type.'_l_'.$v;
            $this->ci->cache->delete($cachedid);
            
        }
        return true;
    }
}
