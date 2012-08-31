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
class Idp_list extends MY_Controller
{

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
        $this->session->set_userdata(array('currentMenu' => 'idp'));
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
        $resource = 'idp_list';
        $action = 'read';
        $group = 'default';
        $has_read_access = $this->zacl->check_acl($resource,$action,$group,'');
        if(!$has_read_access)
        {
                      $data['content_view'] = 'nopermission';
                      $data['error'] = "No access to list all idps";
                      $this->load->view('page',$data);
                      return;

        }
        $idprows = array();
		$col = new models\Providers();
		$idps = $col->getIdps_inNative();
                 $data['idps_count'] = count($idps);
        foreach ($idps as $i)
        {
            $i_link = base_url() . "providers/provider_detail/idp/" . $i->getId();
                       if($i->getAvailable())
                       {
			$col1 = anchor($i_link, $i->getDisplayName())."<br />(".$i->getEntityId().")";
                       }
                       else
                       {
			   $col1 = "<div class=\"alert\" title=\"disabled or expired\">".anchor($i_link, $i->getDisplayName())."</div>(".$i->getEntityId().")";
                       }
                        $help_url = $i->getHelpdeskUrl();
                        if(!empty($help_url))
                        {
			    $col2 = auto_link($help_url,'url');
                        }
                        else
                        { 
                            $col2='';
                        }
            $idprows[] = array('data'=>array('data'=>$col1,'class'=>'homeorg'), $col2);
        }
        $data['idprows'] =  $idprows;
        $data['content_view'] = 'providers/idp_list_view';
        $this->load->view('page', $data);
    }

}

