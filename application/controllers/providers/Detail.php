<?php

if (!defined('BASEPATH'))
	exit('Ni direct script access allowed');
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
 * Provider_detail Class
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Detail extends MY_Controller
{
	private $tmp_attributes;
	public static $alerts;

	function __construct()
	{
		parent::__construct();
		$this->current_site = current_url();
		$this->tmp_attributes = new models\Attributes;
		$this->tmp_attributes->getAttributes();
		self::$alerts = array();
	}

	function refreshentity($id)
	{
		if ($this->input->is_ajax_request()) {
			if (!$this->j_auth->logged_in()) {
				set_status_header(403);
				echo 'no user session';
				return;
			}
			if (!is_numeric($id)) {
				set_status_header(403);
				echo 'received incorrect params';
				return;
			}
			$this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));
			$has_write_access = $this->zacl->check_acl($id, 'write', 'entity', '');
			log_message('debug', 'TEST access ' . $has_write_access);
			if ($has_write_access === TRUE) {
				log_message('debug', 'TEST access ' . $has_write_access);
				$id = trim($id);
				$keyPrefix = getCachePrefix();
				$this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
				$cache1 = 'mcircle_' . $id;
				$this->cache->delete($cache1);
				$cache2 = 'arp_' . $id;
				$this->cache->delete($cache2);
				$this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($id), -1);
				echo 'OK';
				return TRUE;
			} else {
				set_status_header(403);
				echo 'access denied';
				return;
			}
		} else {
			show_error('Access denied', 403);
		}
	}

	function status($id = null, $refresh = null)
	{
		$this->output->set_content_type('application/json');
		if (!$this->input->is_ajax_request()) {
			set_status_header(403);
			echo 'request not valid';
			return;
		}

		if (empty($id) || !ctype_digit($id)) {
			set_status_header(403);
			echo 'incorrect or missing arg ';
			return;
		}
		if (!$this->j_auth->logged_in()) {
			set_status_header(403);
			echo 'no session';
			return;
		}

		$providerid = $id;


		$provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
		if (empty($provider)) {
			set_status_header(404);
			echo 'not foud';
			return;
		}
		$this->load->library('zacl', 'providertoxml');
		$hasReadAccess = $this->zacl->check_acl($providerid, 'read', 'entity', '');
		if (!$hasReadAccess) {
			set_status_header(403);
			echo 'denied';
			return;
		}


		$this->load->library('providerdetails');
		$keyPrefix = getCachePrefix();
		$this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
		$cacheId = 'mstatus_' . trim($id);


		if (!empty($refresh) && $refresh === '1') {
			$result = $this->providerdetails->generateAlertsDetails($provider);
			$this->cache->save($cacheId, $result, 3600);
		} else {
			$resultCache = $this->cache->get($cacheId);
			if (is_null($resultCache) || !is_array($resultCache)) {
				log_message('debug', __METHOD__ . ' cache empty refreshing');
				$result = $this->providerdetails->generateAlertsDetails($provider);
				$this->cache->save($cacheId, $result, 3600);
			} else {
				$result = $resultCache;
			}
		}


		echo json_encode($result);

		return;
	}

	function showlogs($id)
	{
		if ($this->input->is_ajax_request()) {
			if (!$this->j_auth->logged_in()) {
				set_status_header(403);
				echo 'no session';
				return;
			}
			$this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));
			$d = array();
			$ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
			if (empty($ent)) {
				$data['d'] = $d;
				$this->load->view('providers/showlogs_view', $data);
				return;
			}

			$has_write_access = $this->zacl->check_acl($id, 'write', 'entity', '');
			if ($has_write_access === TRUE) {
				$i = 0;
				$isactive = $ent->getActive();
				$islocal = $ent->getLocal();
				$isgearman = $this->config->item('gearman');
				$isstats = $this->config->item('statistics');
				if (($isactive === TRUE) && ($islocal === TRUE) && !empty($isgearman) && ($isgearman === TRUE) && !empty($isstats)) {
					$d[++$i] = array('name' => '' . anchor(base_url() . 'manage/statdefs/show/' . $ent->getId() . '', lang('statsmngmt')) . '',
						'value' => '' . anchor(base_url() . 'manage/statdefs/show/' . $ent->getId() . '', '<i class="fi-graph-bar"></i>') . '');
				}
				$d[++$i]['header'] = lang('rr_logs');
				$d[++$i]['name'] = lang('rr_variousreq');
				$d[$i]['value'] = $this->show_element->generateRequestsList($ent, 10);
				$d[++$i]['name'] = lang('rr_modifications');
				$d[$i]['value'] = $this->show_element->generateModificationsList($ent, 10);
				if ((strcasecmp($ent->getType(), 'IDP') == 0) || (strcasecmp($ent->getType(), 'BOTH') == 0)) {
					$tmp_logs = new models\Trackers;
					$arpLogs = $tmp_logs->getArpDownloaded($ent);
					$logg_tmp = '<ul class="no-bullet">';
					if (!empty($arpLogs)) {
						foreach ($arpLogs as $l) {
							$logg_tmp .= '<li><b>' . date('Y-m-d H:i:s', $l->getCreated()->format('U') + j_auth::$timeOffset) . '</b> - ' . $l->getIp() . ' <small><i>(' . $l->getAgent() . ')</i></small></li>';
						}
					}
					$logg_tmp .= '</ul>';
					$d[++$i] = array('name' => '' . lang('rr_recentarpdownload') . '', 'value' => '' . $logg_tmp . '');
				}
			} else {
				log_message('debug', 'no access to load logs tab');
			}

			$data['d'] = $d;
			$this->load->view('providers/showlogs_view', $data);
		} else {
			echo '';
		}
	}

	function show($id)
	{

		if (empty($id) || !ctype_digit($id)) {
			show_error(lang('error404'), 404);
			return;
		}
		if (!$this->j_auth->logged_in()) {
			redirect('auth/login', 'location');
			return;
		}
		$this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml', 'providerdetails'));

		$tmp_providers = new models\Providers();
		$ent = $tmp_providers->getOneById($id);
		if (empty($ent)) {
			show_error(lang('error404'), 404);
			return;
		}
		$hasReadAccess = $this->zacl->check_acl($id, 'read', 'entity', '');
		if (!$hasReadAccess) {
			$data['content_view'] = 'nopermission';
			$data['error'] = lang("rr_nospaccess");
			$this->load->view('page', $data);
			return;
		}

		$data = $this->providerdetails->generateForControllerProvidersDetail($ent);
		if (empty($data['bookmarked'])) {
			$data['sideicons'][] = '<a href="' . base_url() . 'ajax/bookmarkentity/' . $data['entid'] . '" class="updatebookmark bookentity"  data-jagger-bookmark="add" title="Add to dashboard"><i class="fi-plus" style="color: white"></i></a>';

		}
		/**
		 * @todo finish show alert block if some warnings realted to entity
		 */
		$data['alerts'] = self::$alerts;

		$data['titlepage'] = $data['presubtitle'] . ': ' . $data['name'];
		$this->title = &$data['titlepage'];
		$data['content_view'] = 'providers/detail_view.php';
		$data['breadcrumbs'] = array(
			array('url' => base_url('p/page/front_page'), 'name' => lang('home')),
			array('url' => base_url(), 'name' => lang('dashboard')),
			array('url' => '#', 'name' => '' . $data['name'] . '', 'type' => 'current'),

		);
		$this->load->view('page', $data);
	}

	function showmembers($providerid)
	{
		if (!$this->input->is_ajax_request()) {
			set_status_header(403);
			echo 'unsupported request';
			return;
		}
		if (!$this->j_auth->logged_in()) {

			set_status_header(403);
			echo 'no session';
			return;
		}
		$ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid));
		if (empty($ent)) {
			set_status_header(404);
			echo lang('error404');
			return;
		}
		$this->load->library(array('geshilib', 'show_element', 'zacl', 'providertoxml'));
		$has_read_access = $this->zacl->check_acl($providerid, 'read', 'entity', '');
		if (!$has_read_access) {
			set_status_header(403);
			echo 'no access';
			return;
		}

		$l = array();
		$tmp_providers = new models\Providers;
		$members = $tmp_providers->getTrustedServicesWithFeds($ent);
		if (empty($members)) {
			$l[] = array('entityid' => '' . lang('nomembers') . '', 'name' => '', 'url' => '');
		}
		$preurl = base_url() . 'providers/detail/show/';
		foreach ($members as $m) {
			$feds = array();
			$name = $m->getName();
			if (empty($name)) {
				$name = $m->getEntityId();
			}
			$y = $m->getFederations();
			foreach ($y as $yv) {
				$feds[] = $yv->getName();
			}
			$l[] = array('entityid' => $m->getEntityId(), 'name' => $name, 'url' => $preurl . $m->getId(), 'feds' => $feds);
		}
		echo json_encode($l);
	}

}
