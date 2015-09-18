<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet <support@edugate.ie>
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Rrpreference
{

    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function getPreferences($name = null)
    {
        $res = $this->ci->j_cache->library('rrpreference', 'prefToArray', array('global'), '600');
        if (!empty($name)) {
            if (isset($res['' . $name . ''])) {
                return $res['' . $name . ''];
            } else {
                return array();
            }
        } else {
            return $res;
        }

    }

    /**
     * @return bool
     */
    public function cleanFromCache()
    {
        $this->ci->j_cache->library('rrpreference', 'prefToArray', array('global'), -1);
        return true;
    }
    /**
     * @param $name
     * @return null
     */
    public function getTextValueByName($name)
    {
        $r = $this->getPreferences($name);
        if (array_key_exists('value', $r) && isset($r['status']) && !empty($r['status'])) {
            return $r['value'];
        } else {
            return null;
        }
    }
    /**
     * @param $name
     * @return bool
     */
    public function getStatusByName($name)
    {
        $prefStatus = $this->getPreferences($name);
        if (array_key_exists('status', $prefStatus)) {
            return $prefStatus['status'];
        }
        return false;
    }

    /**
     * @param $type
     * @return array
     */
    public function prefToArray($type)
    {
        $result = array();
        if ($type === 'global') {
            /**
             * @var models\Preferences[] $globalPrefs
             */
            $globalPrefs = $this->em->getRepository("models\Preferences")->findAll();
            foreach ($globalPrefs as $r) {

                $result['' . $r->getName() . ''] = array('name' => $r->getName(), 'descname' => $r->getDescname(), 'value' => $r->getValue(), 'status' => $r->getEnabled(), 'type' => $r->getType(), 'servalue' => $r->getSerializedValue());
            }
        }
        return $result;
    }

}
