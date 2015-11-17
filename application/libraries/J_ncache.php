<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @author    Middleware Team HEAnet <support@edugate.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @link      https://github.com/Edugate/Jagger
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

    /**
     * @param $type
     * @return bool
     */
    public function cleanProvidersList($type)
    {
        $types = (array) $type;
        $guilangs = (array) MY_Controller::guiLangs();
        $langs = array_keys($guilangs);
        if(count($langs) === 0){
            $langs = array('en','cs','es','it','pl','pt');
        }
        foreach($types as $ptype) {
            $cachePrefix = $ptype . '_l_';
            foreach ($langs as $v) {
                $this->ci->cache->delete($cachePrefix . $v);
            }
        }
        return true;
    }

    /**
     * @param $fedId
     * @return bool
     */
    public function cleanFederationMembers($fedId)
    {
        $guilangs = (array) MY_Controller::guiLangs();
        $langs = array_keys($guilangs);
        $cachePrefix = 'fedmbrs_' . $fedId . '_';
        foreach ($langs as $v) {
            $this->ci->cache->delete($cachePrefix . $v);
        }
        return true;
    }

    /**
     * @param $fedId
     * @param $lang
     * @return mixed
     */
    public function getFederationMembers($fedId, $lang)
    {
        $cachedid = 'fedmbrs_' . $fedId . '_' . $lang;
        return $this->ci->cache->get($cachedid);
    }

    /**
     * @param $fedId
     * @param $lang
     * @param $data
     * @return bool
     */
    public function saveFederationMembers($fedId, $lang, $data)
    {
        $cachedid = 'fedmbrs_' . $fedId . '_' . $lang;
        $this->ci->cache->save($cachedid, $data, 720);
        return true;
    }

    /**
     * @param $tmpid
     * @return mixed
     */
    public function getPreregisterMetadata($tmpid)
    {
        $cacheid = 'preregmeta_' . $tmpid;
        return $this->ci->cache->get($cacheid);
    }

    /**
     * @param $tmpid
     * @return bool
     */
    public function cleanPreregisterMetadata($tmpid)
    {
        $cacheid = 'preregmeta_' . $tmpid;
        $this->ci->cache->delete($cacheid);
        return true;
    }

    /**
     * @param $tmpid
     * @param $data
     * @return bool
     */
    public function savePreregisterMetadata($tmpid, $data)
    {
        $cacheid = 'preregmeta_' . $tmpid;
        $this->ci->cache->save($cacheid, $data, 720);
        return true;

    }

    /**
     * @param null $userid
     * @return null
     */
    public function getUserQList($userid = null)
    {
        if ($userid === null) {
            return null;
        }
        $cacheid = 'userq_' . $userid;
        return $this->ci->cache->get($cacheid);
    }

    /**
     * @param $userid
     * @param $data
     * @return bool
     */
    public function saveUserQList($userid, $data)
    {
        $cacheid = 'userq_' . $userid;
        $this->ci->cache->save($cacheid, $data, 15);
        return true;
    }

    /**
     * @param $providerId
     * @return mixed
     */
    public function getMcircleMeta($providerId)
    {
        $cacheid = 'mcircle_' . $providerId;
        return $this->ci->cache->get($cacheid);
    }

    /**
     * @param $providerId
     * @return bool
     */
    public function cleanMcirclceMeta($providerId)
    {
        $this->ci->cache->delete('mcricle_' . $providerId . '');
        return true;
    }

    /**
     * @param $providerId
     * @param $data
     * @return bool
     */
    public function saveMcircleMeta($providerId, $data)
    {
        $cacheid = 'mcircle_' . $providerId;
        $this->ci->cache->save($cacheid, $data, 600);
        return true;
    }

    /**
     * @param $providerId
     * @return bool
     */
    public function cleanEntityStatus($providerId)
    {
        $this->ci->cache->delete('mstatus_' . $providerId . '');
        return true;
    }

    /**
     * @return mixed
     */
    public function getEntityCategoriesDefs()
    {
        $cacheid = 'entcatsdefs';
        return $this->ci->cache->get($cacheid);
    }

    /**
     * @param $data
     * @return bool
     */
    public function saveEntityCategoriesDefs($data)
    {
        $cacheid = 'entcatsdefs';
        $this->ci->cache->save($cacheid, $data, 600);
        return true;
    }

    /**
     * @param $providerId
     * @return mixed
     */
    public function getCircleDisco($providerId)
    {
        $cacheid = 'disco_' . $providerId;
        return $this->ci->cache->get($cacheid);
    }


    /**
     * @param $providerId
     * @param $data
     * @return bool
     */
    public function saveCircleDisco($providerId, $data)
    {
        $cacheid = 'disco_' . $providerId;
        $this->ci->cache->save($cacheid, $data, 3600);
        return true;
    }

    /**
     * @return mixed
     */
    public function getFullDisco()
    {
        $cacheid = 'discof';
        return $this->ci->cache->get($cacheid);
    }

    /**
     * @param $data
     * @return bool
     */
    public function saveFullDisco($data)
    {
        $cacheid = 'discof';
        $this->ci->cache->save($cacheid, $data, 12000);
        return true;
    }


}

