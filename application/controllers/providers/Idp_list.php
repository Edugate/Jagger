<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
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
 * Idp_list Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Idp_list extends MY_Controller {

    //put your code here
    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'idp'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->library('table');
        $this->load->library('zacl');
    }

    // deprecated to be removed soon
    private function show($limit=null)
    {
        MY_Controller::$menuactive = 'idps';
        $this->title = lang('title_idplist');
        $this->load->helper('iconhelp');
        $lockicon =  '<span class="lbl lbl-locked">'.lang('rr_locked').'</span>';
        $disabledicon = '<span class="lbl lbl-disabled">'.lang('rr_disabled').'</span>';
        $expiredicon ='<span class="lbl lbl-disabled">'.lang('rr_expired').'</span>';
        $staticon = '<span class="lbl lbl-static">'.lang('rr_static').'</span>';
        $exticon = '<span class="lbl lbl-external">'.lang('rr_external').'</span>';
        $hiddenicon = '<span class="lbl lbl-disabled">'.lang('lbl_publichidden').'</span>';
        $resource = 'idp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_read_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistidps');
            $this->load->view('page', $data);
            return;
        }
        $idprows = array();
        $col = new models\Providers();
        if(empty($limit))
        {
            $data['typeidps'] = 'local';
            $idps = $col->getIdpsLightLocal();
        }
        elseif($limit === 'ext')
        {
           $data['typeidps'] = 'external';
           $idps = $col->getIdpsLightExternal();
        }
        else
        {
            $data['typeidps'] = 'all';
            $idps = $col->getIdpsLight();
        }
        $data['idps_count'] = count($idps);
        $linktitlediexp = lang('rr_disexp_link_title');
        $lang = MY_Controller::getLang();
        foreach ($idps as $i)
        {
            $i_link = base_url() . "providers/detail/show/" . $i->getId();
            $iconsblock = '';
            
            if($i->getLocked())
            {
               $iconsblock .= $lockicon .' '; 
            }
            if(!($i->getActive()))
            {
               $iconsblock .= $disabledicon .' ';
            }
            if(!($i->getIsValidFromTo()))
            {
               $iconsblock .= $expiredicon .' ';
            }
            if(!($i->getLocal()))
            {
              $iconsblock .= $exticon .' ';
            }
            if($i->getStatic())
            {
               $iconsblock .= $staticon .' ';
            }
            if(!$i->getPublicVisible())
            {
               $iconsblock .= $hiddenicon .' ';
            }
            $displayname = $i->getNameToWebInLang($lang,'idp');
            if(empty($displayname))
            {
                $displayname = $i->getEntityId();
            }
            
            if ($i->getAvailable())
            {
                $col1 = anchor($i_link, $displayname) . '<div class="s2">' . $i->getEntityId() . '</div>';
            }
            else
            {
                $col1 = '<span class="additions"><span class="alert" title="'.$linktitlediexp.'">'.anchor($i_link, $displayname).'</span><div class="s2">'. $i->getEntityId().'</div></span>';
            }
            $regdate = $i->getRegistrationDate();
            if(isset($regdate))
            {
                $col2 = date('Y-m-d',$regdate->format('U')+j_auth::$timeOffset);
            }
            else
            {
                $col2 = '';
            }
            $help_url = $i->getHelpdeskUrl();
            if (!empty($help_url))
            {
                $col3 = auto_link($help_url, 'url');
            }
            else
            {
                $col3 = '';
            }
            $idprows[] = array('data' => array('data' =>  $col1 ),$iconsblock, $col2,'<div class="squiz s2">'.$col3.'</div>');
        }
        if($data['typeidps'] === 'local')
        {
            $t = lang('rr_tbltitle_listlocalidps');
        }
        elseif($data['typeidps'] === 'external')
        {
            $t = lang('rr_tbltitle_listextidps');
        }
        else
        {
            $t = lang('rr_tbltitle_listidps');
        }
        $data['titlepage'] = $t.' ('. lang('rr_found') .' '.$data['idps_count'].')';

        $data['idprows'] = $idprows;
        $data['content_view'] = 'providers/idp_list_view';
        $this->load->view('page', $data);
    }

    function showlist()
    {

        MY_Controller::$menuactive = 'idps';
        $this->title = lang('title_idplist');
        $this->load->helper('iconhelp');
        $resource = 'idp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$has_read_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rerror_nopermtolistidps');
            $this->load->view('page', $data);
            return;
        }

        $data['entitytype'] = 'idp';
        $data['titlepage'] = lang('rr_tbltitle_listidps');
        $data['subtitlepage'] = ' ';

        $data['content_view'] = 'providers/providers_list_view';
        $this->load->view('page',$data);

    }

}

