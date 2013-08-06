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
 * Idp_edit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Entitystate extends MY_Controller {

    protected $id;
    protected $tmp_providers;
    protected $entity;

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
        $this->tmp_providers = new models\Providers;
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->load->library('metadata_validator');
        $this->load->library('zacl');
        $this->tmp_providers = new models\Providers();
        $this->entity = null;
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('elock', lang('rr_lock_entity'), 'max_length[1]');
        $this->form_validation->set_rules('eactive', lang('rr_entityactive'), 'max_length[1]');
        $this->form_validation->set_rules('extint', lang('rr_entitylocalext'), 'max_length[1]');
        return $this->form_validation->run();
    }

    public function modify($id)
    {
        if (!is_numeric($id))
        {
            show_error('Incorrect entity id provided', 404);
        }
        else
        {
            $this->entity = $this->tmp_providers->getOneById($id);
        }
        if (!isset($this->entity))
        {
            show_error('Provider not found', 404);
        }
        $data['entid'] = $id;
        $data['current_locked'] = $this->entity->getLocked();
        $data['current_active'] = $this->entity->getActive();
        $data['current_extint'] = $this->entity->getLocal();
        $has_manage_access = $this->zacl->check_acl($this->entity->getId(), 'manage', 'entity', '');
        if (!$has_manage_access)
        {
            show_error('No sufficient permision to manage entity', 403);
        }


        if ($this->_submit_validate() === TRUE)
        {
            $locked = $this->input->post('elock');
            $active = $this->input->post('eactive');
            $extint = $this->input->post('extint');
            $changed = false;
            $differ = array();
            if (isset($locked))
            {
                if ($data['current_locked'] != $locked)
                {

                    if ($locked == '1')
                    {
                        $differ['Lock'] = array('before'=>'unlocked','after'=>'locked');
                        $this->entity->Lock();
                    }
                    elseif ($locked == '0')
                    {
                        $this->entity->Unlock();
                        $differ['Lock'] = array('before'=>'locked','after'=>'unlocked');
                    }
                    $changed = true;
                }
            }
            if (isset($active))
            {
                if ($data['current_active'] != $active)
                {
                    if ($active == '1')
                    {
                        $this->entity->Activate();
                        $differ['Active'] = array('before'=>'disabled','after'=>'enabled');
                    }
                    elseif ($active == '0')
                    {
                        $this->entity->Disactivate();
                        $differ['Active'] = array('before'=>'enabled','after'=>'disabled');
                    }
                    $changed = true;
                }
            }
            if (isset($extint))
            {
                if ($data['current_extint'] != $extint)
                {
                    if ($extint == '1')
                    {
                        $this->entity->setAsLocal();
                        $this->entity->createAclResource();
                        $differ['Local/External'] = array('before'=>'external','after'=>'local');
                    }
                    elseif ($extint == '0')
                    {
                        $this->entity->setAsExternal();
                        $differ['Local/External'] = array('before'=>'local','after'=>'external');
                    }
                    $changed = true;
                }
            }
            if (count($differ) > 0)
            {
                $this->tracker->save_track('idp', 'modification', $this->entity->getEntityId(), serialize($differ), false);
            }
            $this->em->persist($this->entity);
            $this->em->flush();
            if($changed)
            {
                $data['success_message'] = lang('rr_entstate_updated');
            }
        }
        $data['current_locked'] = $this->entity->getLocked();
        $data['current_active'] = $this->entity->getActive();
        $data['current_extint'] = $this->entity->getLocal();
        $data['entityid'] = $this->entity->getEntityId();
        $data['name'] = $this->entity->getName();
        $data['id'] = $this->entity->getId();
        $data['type'] = strtolower($this->entity->getType());



        $data['content_view'] = 'manage/entitystate_form_view';
        $this->load->view('page', $data);
    }

}
