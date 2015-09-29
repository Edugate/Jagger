<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


/**
 * ResourceRegistry3
 *
 * @package   RR3
 * @copyright Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Detail extends MY_Controller
{
    protected $tmpAttributes;
    public static $alerts;
    public $isGearman;

    public function __construct()
    {
        parent::__construct();
        $this->tmpAttributes = new models\Attributes;
        $this->tmpAttributes->getAttributes();
        self::$alerts = array();
        $this->isGearman = $this->config->item('gearman');
        if ($this->isGearman !== true) {
            $this->isGearman = false;
        }
    }

    public function refreshentity($providerID)
    {
        if (!$this->input->is_ajax_request() || !$this->jauth->logged_in()) {
            return $this->output->set_status_header(403)->set_output('Denied - no session or invalid request');
        }
        if (!ctype_digit($providerID)) {
            return $this->output->set_status_header(403)->set_output('Denied - received incorrect params');
        }
        $this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));
        $hasWriteAccess = $this->zacl->check_acl($providerID, 'write', 'entity', '');
        if ($hasWriteAccess === true) {
            log_message('debug', 'TEST access ' . $hasWriteAccess);
            $providerID = trim($providerID);
            $keyPrefix = getCachePrefix();
            $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
            $cache1 = 'mcircle_' . $providerID;
            $this->cache->delete($cache1);
            $cache2 = 'arp_' . $providerID;
            $this->cache->delete($cache2);
            $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($providerID), -1);
            return $this->output->set_status_header(200)->set_output('OK');
        }

        return $this->output->set_status_header(403)->set_output('Access denied');

    }

    public function status($providerID = null, $refresh = null)
    {
        if (!$this->input->is_ajax_request() || !ctype_digit($providerID) || !$this->jauth->logged_in()) {
            return $this->output->set_status_header(403)->set_output('Accss Denied');
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerID . ''));
        if ($provider === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl', 'providertoxml');
        $hasReadAccess = $this->zacl->check_acl($providerID, 'read', 'entity', '');
        if (!$hasReadAccess) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }


        $this->load->library('providerdetails',array('ent'=>$provider));
        $keyPrefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
        $cacheId = 'mstatus_' . $providerID;


        if ($refresh === '1') {
            $result = $this->providerdetails->generateAlertsDetails();
            $this->cache->save($cacheId, $result, 3600);
        } else {
            $resultCache = $this->cache->get($cacheId);
            if (!is_array($resultCache)) {
                log_message('debug', __METHOD__ . ' cache empty refreshing');
                $result = $this->providerdetails->generateAlertsDetails();
                $this->cache->save($cacheId, $result, 3600);
            } else {
                $result = $resultCache;
            }
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));


    }

    public function showlogs($providerID)
    {
        if (!$this->input->is_ajax_request() || !$this->jauth->logged_in()) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        $this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerID));
        if ($ent === null) {
            return $this->load->view('providers/showlogs_view', array('d' => array()));
        }

        $hasWriteAccess = $this->zacl->check_acl($providerID, 'write', 'entity', '');
        if ($hasWriteAccess !== TRUE) {
            return $this->load->view('providers/showlogs_view', array('d' => array()));
        }

        $isstats = $this->config->item('statistics');
        if ($this->isGearman && $isstats === true) {
            $rows[] = array('name' => '' . anchor(base_url() . 'manage/statdefs/show/' . $ent->getId() . '', lang('statsmngmt')) . '',
                'value' => '' . anchor(base_url() . 'manage/statdefs/show/' . $ent->getId() . '', '<i class="fi-graph-bar"></i>') . '');
        }
        $rows[] = array(
            'header' => lang('rr_logs'),
        );
        $rows[] = array(
            'name' => lang('rr_variousreq'),
            'value' => $this->show_element->generateRequestsList($ent, 10)
        );
        $rows[] = array(
            'name' => lang('rr_modifications'),
            'value' => $this->show_element->generateModificationsList($ent, 10)
        );

        if ((strcasecmp($ent->getType(), 'SP') !== 0)) {
            $tmpLogs = new models\Trackers;
            /**
             * @var $arpLogs models\Tracker[]
             */
            $arpLogs = $tmpLogs->getArpDownloaded($ent);
            $loggHtml = '<ul class="no-bullet">';
            foreach ($arpLogs as $l) {
                $loggHtml .= '<li><b>' . date('Y-m-d H:i:s', $l->getCreated()->format('U') + jauth::$timeOffset) . '</b> - ' . $l->getIp() . ' <small><i>(' . $l->getAgent() . ')</i></small></li>';
            }
            $loggHtml .= '</ul>';
            $rows[] = array('name' => '' . lang('rr_recentarpdownload') . '', 'value' => '' . $loggHtml . '');
        }
        $this->load->view('providers/showlogs_view', array('d' => $rows));

    }

    public function show($providerID)
    {
        if (!ctype_digit($providerID)) {
            show_error(lang('error404'), 404);
        }
        if (!$this->jauth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));

        $tmpProviders = new models\Providers();
        /**
         * @var $ent models\Provider
         */
        $ent = $tmpProviders->getOneById($providerID);
        if ($ent === null) {
            show_error(lang('error404'), 404);
        }
        if($ent instanceof models\Provider) {
            $this->load->library('providerdetails', array('ent'=>$ent));
        }
        else
        {
            show_error('ddddddddd',500);
        }
        $hasReadAccess = $this->zacl->check_acl($providerID, 'read', 'entity', '');
        if (!$hasReadAccess) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_nospaccess');
            return $this->load->view('page', $data);
        }

        $data = $this->providerdetails->generateForControllerProvidersDetail();
        if (empty($data['bookmarked'])) {
            $data['sideicons'][] = '<a href="' . base_url() . 'ajax/bookmarkentity/' . $data['entid'] . '" class="updatebookmark bookentity"  data-jagger-bookmark="add" title="Add to dashboard"><i class="fi-bookmark" style="color: white"></i></a>';

        }
        /**
         * @todo finish show alert block if some warnings realted to entity
         */
        $data['alerts'] = self::$alerts;

        $data['titlepage'] = $data['presubtitle'] . ': ' . $data['name'];
        $this->title = &$data['titlepage'];
        $data['content_view'] = 'providers/detail_view.php';
        $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        if (strcasecmp($ent->getType(), 'SP') === 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        }
        $data['breadcrumbs'] = array(
            $plist,
            array('url' => '#', 'name' => '' . $data['name'] . '', 'type' => 'current'),

        );
        $this->load->view('page', $data);
    }

    public function showmembers($providerid)
    {
        if (!$this->input->is_ajax_request() || !ctype_digit($providerid) || !$this->jauth->logged_in()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $myLang = MY_Controller::getLang();
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('' . lang('error404') . '');
        }
        $this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));
        $hasReadAccess = $this->zacl->check_acl($providerid, 'read', 'entity', '');
        if (!$hasReadAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $result = array();
        $tmpProviders = new models\Providers;
        /**
         * @var $members models\Provider[]
         */
        $members = $tmpProviders->getTrustedServicesWithFeds($ent);
        if (count($members) === 0) {
            $result[] = array('entityid' => '' . lang('nomembers') . '', 'name' => '', 'url' => '');
        }
        $preurl = base_url() . 'providers/detail/show/';
        foreach ($members as $m) {
            $feds = array();
            $name = $m->getNameToWebInLang($myLang);
            $y = $m->getFederations();
            foreach ($y as $yv) {
                $feds[] = $yv->getName();
            }
            $result[] = array('entityid' => $m->getEntityId(), 'name' => $name, 'url' => $preurl . $m->getId(), 'feds' => $feds);
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

}
