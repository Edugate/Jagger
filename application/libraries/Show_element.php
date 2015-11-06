<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * ResourceRegistry3
 *
 * @package   RR3
 * @author    Middleware Team HEAnet
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Show_element Class
 *
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Show_element
{

    protected $ci;
    protected $em;
    protected $tmp_policies;
    protected $tmp_providers;
    protected $entitiesmaps;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->tmp_policies = new models\AttributeReleasePolicies;
        $this->tmp_providers = new models\Providers;
        $this->entitiesmaps = array();
    }


    public function generateRequestsList(models\Provider $idp, $count = null)
    {
        if (empty($count) || !is_numeric($count) || $count < 1) {
            $count = 5;
        }

        $tmp_tracks = new models\Trackers;
        $tracks = $tmp_tracks->getProviderRequests($idp, $count);
        if (empty($tracks)) {
            return null;
        }
        $mcounter = 0;
        $result = '<dl class="accordion" data-accordion="requestsList">';
        foreach ($tracks as $t) {
            $det = $t->getDetail();
            $this->ci->table->set_heading('Request');
            $this->ci->table->add_row($det);
            $y = $this->ci->table->generate();
            $user = $t->getUser();
            if (empty($user)) {
                $user = lang('unknown');
            }
            $result .= '<dd class="accordion-navigation">';
            $result .= '<a href="#rmod' . $mcounter . '">' .jaggerDisplayDateTimeByOffset($t->getCreated(),jauth::$timeOffset). ' ' . lang('made_by') . ' <b>' . $user . '</b> ' . lang('from') . ' ' . $t->getIp() . '</a><div id="rmod' . $mcounter . '" class="content">' . $y . '</div>';
            $result .= '</dd>';
            $mcounter++;
            $this->ci->table->clear();
        }
        $result .= '</dl>';
        return $result;
    }

    public function generateModificationsList(models\Provider $idp, $count = null)
    {
        if (empty($count) || !is_numeric($count) || $count < 1) {
            $count = 5;
        }

        $tmp_tracks = new models\Trackers;
        $tracks = $tmp_tracks->getProviderModifications($idp, $count);
        if (empty($tracks)) {
            return null;
        }


        $result = '<dl class="accordion" data-accordion="modificationsList">';
        $mcounter = 0;
        foreach ($tracks as $t) {
            $modArray = unserialize($t->getDetail());
            $chng = array();
            foreach ($modArray as $ckey => $cvalue) {
                $chng[$ckey] = array(
                    0 => $ckey,
                    1 => $modArray[$ckey]['before'],
                    2 => $modArray[$ckey]['after']
                );
            }
            $this->ci->table->set_heading('Name', 'Before', 'After');
            $y = $this->ci->table->generate($chng);
            $user = $t->getUser();
            if (empty($user)) {
                $user = lang('unknown');
            }
            $result .= '<dd class="accordion-navigation">';
            $result .= '<a href="#mod' . $mcounter . '" class="accordion-icon">' .jaggerDisplayDateTimeByOffset($t->getCreated(),jauth::$timeOffset).  ' ' . lang('chng_made_by') . ' <b>' . $user . '</b> ' . lang('from') . ' ' . $t->getIp() . '</a><div id="mod' . $mcounter . '" class="content">' . $y . '</div>';
            $result .= '</dd>';
            $mcounter++;
        }
        $result .= '</dl>';
        return $result;
    }

}
