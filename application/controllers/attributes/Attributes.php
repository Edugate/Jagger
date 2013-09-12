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
 * Attributes Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Attributes extends MY_Controller
{

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
        $this->session->set_userdata(array('currentMenu' => 'general'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');

    }

    public function show()
    {
        $this->title = "Attribute List";
		$attributes_tmp = new models\Attributes();
		$attributes = $attributes_tmp->getAttributes();
        $a_ar = array();
        $excluded = '<span class="lbl lbl-alert" title="'.lang('rr_attronlyinarpdet').'">'.lang('rr_attronlyinarp').'</span>';

        foreach ($attributes as $a)
        {
            $notice = '';
            $i = $a->showInMetadata();
            if($i === FALSE)
            {
                $notice = '<br />'.$excluded;
            }
            $a_ar[] = array(showBubbleHelp($a->getDescription()) . ' '. $a->getName().$notice, $a->getFullname(), $a->getOid(),$a->getUrn());
        }
        $data['attributes'] = $a_ar;
        $data['content_view'] = 'attribute_list_view';
        $this->load->view('page', $data);
    }

}
