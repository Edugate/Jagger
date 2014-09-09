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
 * Settings Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class Settings extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        $loggedin = $this->j_auth->logged_in();

        $this->current_site = current_url();


        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }

        $this->load->helper('form');
        $this->load->library('zacl');
        
    }

    public function reset_sp()
    {
        $s_a = array('current_sp' => null, 'current_sp_name' => null);
        $this->session->set_userdata($s_a);
    }

    public function sp()
    {
        $data['target'] = "";
        $data['splist_dropdown'] = array();
        $data['error'] = "";
        $this->title = 'select sp';
        $sent_sp = $this->input->post('service_provider');
        $sent_target = $this->input->post('target');
        if (!empty($sent_sp) && is_numeric($sent_sp))
        {
            $s_a['current_sp'] = $sent_sp;
            $spObj = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $sent_sp));
            $s_a['current_sp_name'] = $spObj->getDisplayname();
            $this->session->set_userdata($s_a);
            if (!empty($sent_target))
            {
                redirect($sent_target, 'location');
            } else
            {
                $data['message'] = "Service Provider has been set";
                $splist = $this->em->getRepository("models\Provider")->findBy(array('type' => 'SP'));
                $splist_dropdown = array();
                foreach ($splist as $s)
                {
                    $splist_dropdown[$s->getId()] = $s->getDisplayName();
                }
                if (count($splist_dropdown) < 1)
                {
                    $data['error'] = "No service providers found";
                }
                $data['splist_dropdown'] = $splist_dropdown;
            }
        } else
        {

            $splist = $this->em->getRepository("models\Provider")->findBy(array('type' => 'SP'));
            $splist_dropdown = array();
            foreach ($splist as $s)
            {
                $splist_dropdown[$s->getId()] = $s->getDisplayName();
            }
            if (count($splist_dropdown) < 1)
            {
                $data['error'] = "No service providers found";
            }
            $data['splist_dropdown'] = $splist_dropdown;
            if (empty($sent_target))
            {
                $data['target'] = $this->session->flashdata('target');
            } else
            {
                $data['target'] = $sent_target;
            }
        }
        $data['content_view'] = 'manage/settings_sp_view';
        $this->load->view('page', $data);
    }

    public function idp()
    {
        $data['target'] = "";
        $data['idplist_dropdown'] = array();
        $data['error'] = "";
        $this->title = 'select sp';
        $sent_idp = $this->input->post('identity_provider');
        $sent_target = $this->input->post('target');
        if (!empty($sent_idp) && is_numeric($sent_idp))
        {
            $s_a['current_idp'] = $sent_idp;
			$tmp = new models\Providers;
			$idpObj=$tmp->getOneIdPById($sent_idp);

            $s_a['current_idp_name'] = $idpObj->getDisplayname();
            $this->session->set_userdata($s_a);
            if (!empty($sent_target))
            {
                redirect($sent_target, 'location');
            } else
            {
                $data['message'] = "Identity Provider has been set";
				log_message('debug',"Identity Provider has been set");
                $idplist = $this->em->getRepository("models\Provider")->findBy(array('type' => 'IDP'));
                $idplist_dropdown = array();
                foreach ($idplist as $s)
                {
                    $idplist_dropdown[$s->getId()] = $s->getDisplayName();
                    //$splist_dropdown[$s->getId()] = $s->getDisplayName()." (".$s->getEntityId().")";
                }
                if (count($idplist_dropdown) < 1)
                {
                    $data['error'] = "No service providers found";
                }
                $data['idplist_dropdown'] = $idplist_dropdown;
            }
        } else
        {

            $idplist = $this->em->getRepository("models\Provider")->findBy(array('type' => 'IDP'));
            $idplist_dropdown = array();
            foreach ($idplist as $s)
            {
                $idplist_dropdown[$s->getId()] = $s->getDisplayName();
            }
            if (count($idplist_dropdown) < 1)
            {
                $data['error'] = "No service providers found";
            }
            $data['idplist_dropdown'] = $idplist_dropdown;
            if (empty($sent_target))
            {
                $data['target'] = $this->session->flashdata('target');
            } else
            {
                $data['target'] = $sent_target;
            }
        }
        $data['content_view'] = 'manage/settings_idp_view';
        $this->load->view('page', $data);
    }

    /**
     * @todo finish
     */
    public function federation()
    {

        $data['content_view'] = 'manage/settings_federation_view';
        $this->load->view('page', $data);
    }

}
