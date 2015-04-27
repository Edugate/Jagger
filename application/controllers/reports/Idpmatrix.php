<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Idpmatrix Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Idpmatrix extends MY_Controller
{

	private $tmp_providers;

	function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('table');
		$this->load->library('arp_generator');
		$this->tmp_providers = new models\Providers;
		$this->logo_basepath = $this->config->item('rr_logouriprefix');
		$this->logo_baseurl = $this->config->item('rr_logobaseurl');
		if (empty($this->logo_baseurl)) {
			$this->logo_baseurl = base_url();
		}
		$this->logo_url = $this->logo_baseurl . $this->logo_basepath;
	}

	public function getArpData($idpid)
	{
		if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
			set_status_header(403);
			echo 'Access denied';
			return false;
		}
		$this->load->library('zacl');
        /**
         * @var $idp models\Provider
         */
		$idp = $this->tmp_providers->getOneIdpById($idpid);
		if (empty($idp)) {
			set_status_header(404);
			echo 'IdP not found';
			return false;
		}
		$has_read_access = $this->zacl->check_acl($idpid, 'read', 'entity', '');
		if (!$has_read_access) {
			set_status_header(403);
			echo 'no perms';
			return false;
		}
        /**
         * @var $attrs models\Attribute[]
         */
		$attrs = $this->em->getRepository("models\Attribute")->findAll();
		foreach ($attrs as $a) {
			$attrdefs[$a->getName()] = $a->getId();
			$attrlist[$a->getName()] = 0;
		}
		$attrdedsCopy = $attrdefs;
		$returnArray = TRUE;
		$arparray['policies'] = $this->arp_generator->arpToXML($idp, $returnArray);
		if (is_null($arparray['policies'])) {
			$arparray['policies'] = array();
		}
        else {
            foreach ($arparray['policies'] as $p) {
                foreach ($p['attributes'] as $k => $v) {
                    unset($attrdedsCopy['' . $k . '']);
                }
                foreach ($p['req'] as $k => $v) {
                    unset($attrdedsCopy['' . $k . '']);
                }
            }
        }

		$attrdefsLeft = array_diff_key($attrdefs, $attrdedsCopy);
		ksort($attrdefsLeft);
		ksort($attrlist);
		$arparray['total'] = count($arparray['policies']);
		$arparray['attributes'] = $attrdefsLeft;
		$arparray['attrlist'] = $attrlist;
		if ($arparray['total'] == 0) {
			$arparray['message'] = lang('errormatrixnoattrsormembers');
		}
		$this->output->set_content_type('application/json')->set_output(json_encode($arparray));
	}

	public function show($idpid)
	{
		if (empty($idpid) || !ctype_digit($idpid)) {
			show_error('Wrong or empty id', 404);
		}
		if ($this->j_auth->logged_in()) {
			$this->session->set_userdata(array('currentMenu' => 'awaiting'));
			$this->load->library('zacl');
		} else {
			redirect('auth/login', 'location');
		}
		$idp = $this->tmp_providers->getOneIdpById($idpid);
		if (empty($idp)) {
			show_error('Identity Provider not found', 404);
		}
        $myLang = MY_Controller::getLang();

		$has_read_access = $this->zacl->check_acl($idpid, 'read', 'entity', '');
		$has_write_access = $this->zacl->check_acl($idpid, 'write', 'entity', '');
		if (!$has_read_access) {
			$data['content_view'] = 'nopermission';
			$data['error'] = lang('rr_noidpaccess');
			$this->load->view('page', $data);
			return;
		}
        $data = array(
            'has_write_access' => $has_write_access,
            'excluded' => $idp->getExcarps(),
            'idpname' => $idp->getNameToWebInLang($myLang, 'IDP'),
            'idpid' => $idp->getId(),
            'entityid' => $idp->getEntityId(),
        );

		$extends = $idp->getExtendMetadata();
		if (count($extends) > 0) {
			foreach ($extends as $ex) {
				$el = $ex->getElement();
				if ($el === 'Logo') {
					$data['providerlogourl'] = $ex->getLogoValue();
					break;
				}
			}
		}
		$data['titlepage'] = lang('identityprovider') . ': ' . anchor('' . base_url() . 'providers/detail/show/' . $data['idpid'], $data['idpname']) . '<br />' . $data['entityid'];
		$data['subtitlepage'] = lang('rr_arpoverview');
		$data['breadcrumbs'] = array(
            array('url'=>base_url('providers/idp_list/showlist'),'name'=>lang('identityproviders')),
			array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . html_escape($data['idpname']) . ''),
			array('url' => '#', 'name' => lang('rr_arpoverview'), 'type' => 'current'),


		);
		$data['content_view'] = 'reports/idpmatrix_show_view';
		$this->load->view('page', $data);
	}

}
