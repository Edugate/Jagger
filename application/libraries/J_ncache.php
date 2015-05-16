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
        $this->ci = &get_instance();
        $this->keyprefix = getCachePrefix();
        $this->ci->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $this->keyprefix));
    }

    public function cleanProvidersList($type)
    {
        $guilangs = MY_Controller::guiLangs();
        $langs = array_keys($guilangs);
        $cachePrefix = $type . '_l_';
        foreach ($langs as $v) {
            $this->ci->cache->delete($cachePrefix . $v);
        }
        return true;
    }

    public function cleanFederationMembers($fedId)
    {
        $guilangs = MY_Controller::guiLangs();
        $langs = array_keys($guilangs);
        $cachePrefix = 'fedmbrs_' . $fedId . '_';
        foreach ($langs as $v) {
            $this->ci->cache->delete($cachePrefix . $v);
        }
        return true;
    }

    public function getFederationMembers($fedId, $lang)
    {
        $cachedid = 'fedmbrs_' . $fedId . '_' . $lang;
        return $this->ci->cache->get($cachedid);
    }

    public function saveFederationMembers($fedId, $lang, $data)
    {
        $cachedid = 'fedmbrs_' . $fedId . '_' . $lang;
        $this->ci->cache->save($cachedid, $data, 720);
        return true;
    }

    public function getPreregisterMetadata($tmpid)
    {
        $cacheid = 'preregmeta_' . $tmpid;
        return $this->ci->cache->get($cacheid);
    }

    public function cleanPreregisterMetadata($tmpid)
    {
        $cacheid = 'preregmeta_' . $tmpid;
        $this->ci->cache->delete($cacheid);
        return true;
    }

    public function savePreregisterMetadata($tmpid, $data)
    {
        $cacheid = 'preregmeta_' . $tmpid;
        $this->ci->cache->save($cacheid, $data, 720);
        return true;

    }


    public function getMcircleMeta($providerId)
    {
        $cacheid = 'mcircle_' . $providerId;
        return $this->ci->cache->get($cacheid);
    }

    public function cleanMcirclceMeta($providerId)
    {
        $this->ci->cache->delete('mcricle_' . $providerId . '');
        return true;
    }

    public function saveMcircleMeta($providerId, $data)
    {
        $cacheid = 'mcircle_' . $providerId;
        $this->ci->cache->save($cacheid, $data, 600);
        return true;
    }

    public function cleanEntityStatus($providerId)
    {
        $this->ci->cache->delete('mstatus_' . $providerId . '');
        return true;
    }


}

