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
 * Sp_list Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Sp_list extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'sp'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->library('table');
        $this->load->library('zacl');
    }

    function showlist()
    {

        MY_Controller::$menuactive = 'sps';
        $this->title = lang('title_splist');
        $this->load->helper('iconhelp');
        $resource = 'sp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_read_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistidps');
            $this->load->view('page', $data);
            return;
        }

        $data['entitytype'] = 'sp';
        $data['titlepage'] = lang('rr_tbltitle_listsps');
        $data['subtitlepage'] = ' ';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('serviceproviders'),'type'=>'current'),

        );

        $data['content_view'] = 'providers/providers_list_view';
        $this->load->view('page', $data);

    }

    // depreacted to be removed
    private function show($limit = null)
    {
        MY_Controller::$menuactive = 'sps';
        $this->title = lang('title_splist');
        $this->load->helper('iconhelp');
        $lockicon = '<span class="lbl lbl-locked">' . lang('rr_locked') . '</span>';
        $disabledicon = '<span class="lbl lbl-disabled">' . lang('rr_disabled') . '</span>';
        $expiredicon = '<span class="lbl lbl-disabled">' . lang('rr_expired') . '</span>';
        $staticon = '<span class="lbl lbl-static">' . lang('rr_static') . '</span>';
        $exticon = '<span class="lbl lbl-external">' . lang('rr_external') . '</span>';
        $hiddenicon = '<span class="lbl lbl-disabled">' . lang('lbl_publichidden') . '</span>';
        $resource = 'sp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_read_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistisps');
            $this->load->view('page', $data);
            return;
        }
        $sprows = array();
        $tmp_providers = new models\Providers;
        if (empty($limit)) {
            $data['typesps'] = 'local';
            $t = lang('rr_tbltitle_listlocalsps');
            $sps = $tmp_providers->getSpsLightLocal();
        } elseif ($limit === 'ext') {
            $t = lang('rr_tbltitle_listextsps');
            $data['typesps'] = 'external';
            $sps = $tmp_providers->getSpsLightExternal();
        } else {
            $t = lang('rr_tbltitle_listsps');
            $data['typesps'] = 'all';
            $sps = $tmp_providers->getSpsLight();
        }


        $data['sps_count'] = count($sps);
        $data['titlepage'] = $t . ' (' . lang('rr_found') . ' ' . $data['sps_count'] . ')';
        $linktitle_disexp = lang('rr_disexp_link_title');
        $lang = MY_Controller::getLang();
        foreach ($sps as $i) {
            $iconsblock = '';

            if ($i->getLocked()) {
                $iconsblock .= $lockicon . ' ';
            }
            if (!($i->getActive())) {
                $iconsblock .= $disabledicon . ' ';
            }
            if (!($i->isValidFromTo())) {
                $iconsblock .= $expiredicon . ' ';
            }
            if (!($i->getLocal())) {
                $iconsblock .= $exticon . ' ';
            }
            if ($i->getStatic()) {
                $iconsblock .= $staticon . ' ';
            }
            if (!$i->getPublicVisible()) {
                $iconsblock .= $hiddenicon . ' ';
            }
            $regdate = $i->getRegistrationDate();
            if (isset($regdate)) {
                $regcol = date('Y-m-d', $regdate->format('U') + j_auth::$timeOffset);
            } else {
                $regcol = '';
            }
            $i_link = base_url() . "providers/detail/show/" . $i->getId();
            $is_available = $i->getAvailable();
            $displayname = $i->getNameToWebInLang($lang, 'sp');
            if (empty($displayname)) {
                $displayname = $i->getEntityId();
            }
            if ($is_available) {
                $sprows[] = array(anchor($i_link, $displayname . '', 'title="' . $displayname . '"') . '<div class="additions s2">' . $i->getEntityId() . '</div>', $iconsblock, $regcol, '<div class="squiz s2"><a href="' . $i->getHelpdeskUrl() . '" title="' . $i->getHelpdeskUrl() . '">' . $i->getHelpdeskUrl() . '</a></div>');
            } else {
                $sprows[] = array('<span class="alert" title="' . $linktitle_disexp . '">' . anchor($i_link, $displayname, 'title="' . $displayname . '"') . '</span><div class="additions s2">' . $i->getEntityId() . '</div>', $iconsblock, $regcol, '<div class="squiz s2"><a href="' . $i->getHelpdeskUrl() . '" title="' . $i->getHelpdeskUrl() . '">' . $i->getHelpdeskUrl() . '</a></div>');
            }
        }
        $data['sprows'] = $sprows;
        $data['content_view'] = 'providers/sp_list_view';
        $this->load->view('page', $data);
    }

}

