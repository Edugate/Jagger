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

    //put your code here
    //put your code here
    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->session->set_userdata(array('currentMenu' => 'sp'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->helper(array('url', 'cert', 'url_encoder'));
        $this->load->library('table');
        $this->load->library('zacl');

    }

    function show()
    {
        $resource = 'sp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource,$action,$group,'');
        if(!$has_read_access)
        {
                      $data['content_view'] = 'nopermission';
                      $data['error'] = "No access to list all sps";
                      $this->load->view('page',$data);
                      return;

        }
        $sprows = array();
		$tmp_providers = new models\Providers;
        $sps = $tmp_providers->getSps_inNative();
        $data['sps_count'] = count($sps);
        foreach ($sps as $i)
        {
            $i_link = base_url() . "providers/provider_detail/sp/" . $i->getId();
            $is_available=$i->getAvailable();
            if($is_available)
            {
            #$sprows[] = array(anchor($i_link, $i->getDisplayName(50)."",'title="'.$i->getDisplayName().'"')."<span class=\"additions\">".$i->getEntityId()."</span>", auto_link($i->getHelpdeskUrl(),'url'));
            $sprows[] = array(anchor($i_link, $i->getDisplayName(50)."",'title="'.$i->getDisplayName().'"')."<span class=\"additions\">".$i->getEntityId()."</span>", '<a href="'.$i->getHelpdeskUrl().'" title="'.$i->getHelpdeskUrl().'">'.substr($i->getHelpdeskUrl(), 0, 30).'...</a>');

            }
            else
            {
            $sprows[] = array("<div class=\"alert\" title=\"inactive\">".anchor($i_link, $i->getDisplayName(50)."",'title="'.$i->getDisplayName().'"')."</div><span class=\"additions\">".$i->getEntityId()."</span>", '<a href="'.$i->getHelpdeskUrl().'" title="'.$i->getHelpdeskUrl().'">'.substr($i->getHelpdeskUrl(), 0, 30).'...</a>');
            }
        }
        $data['sprows'] = $sprows;
        $data['content_view'] = 'providers/sp_list_view';
        $this->load->view('page', $data);
    }

}

