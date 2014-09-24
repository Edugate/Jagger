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
        $this->form_validation->set_rules('publicvisible', 'public visible', 'max_length[1]');
        $this->form_validation->set_rules('validuntiltime', 'time until', 'trim|valid_time_hhmm');
        $this->form_validation->set_rules('validfromtime', 'Valid from time', 'trim|valid_time_hhmm');
        $this->form_validation->set_rules('validfromdate', 'Valid from date', 'trim|valid_date');
        $this->form_validation->set_rules('validuntildate', 'Valid until date', 'trim|valid_date');
        return $this->form_validation->run();
    }

    

    public function regpolicies($id)
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
        $type = $this->entity->getType();
        if($type === 'IDP')
        {
           $data['titlepage'] = lang('identityprovider').':';
        }
        elseif($type === 'SP')
        {
           $data['titlepage'] = lang('serviceprovider').':';
        }
        else
        {
           $data['titlepage'] = '';
        }
        $lang = MY_Controller::getLang();
        $isLocked = $this->entity->getLocked();
        $titlename = $this->entity->getNameToWebInLang($lang, $this->entity->getType());
        $data['titlepage'] .= ' <a href="' . base_url() . 'providers/detail/show/' . $this->entity->getId() . '">' . $titlename . '</a>';
        $data['subtitlepage'] = lang('title_regpols');
        $data['providerid'] = $this->entity->getId();
        $has_write_access = $this->zacl->check_acl($this->entity->getId(), 'write', 'entity', '');
        if (!$has_write_access)
        {
            show_error('No sufficient permision to edit entity', 403);
            return;
        }
        elseif ($isLocked)
        {
            show_error('entity id locked', 403);
            return;
        }

        $isAdmin = $this->j_auth->isAdministrator();
        
        if (!$_POST)
        {
            $data['r'] = $this->form_element->NgenerateRegistrationPolicies($this->entity);
            $data['content_view'] = 'manage/entityedit_regpolicies';
            $this->load->view('page', $data);
        }
        else
        {
            $p = $this->input->post('entregpolform');
            if(!empty($p) && strcmp($p,$this->entity->getId())==0)
            {
                $this->load->library('providerupdater');
                $process['regpol'] = array();
                $input = $this->input->post('f');
                if (!empty($input) && isset($input['regpol']))
                {
                   foreach($input['regpol'] as $p => $v)
                   {
                       foreach($v as $k=>$l)
                       {
                            $process['regpol'][] = $l;
                       }
                   }
                }
                $this->load->library('approval');
                $this->providerupdater->updateRegPolicies($this->entity, $process,$isAdmin);
                try
                {
                  $this->em->flush();
                  $data['content_view'] = 'manage/entityedit_regpolicies_success';
                  if($isAdmin)
                  {
                     $this->globalnotices[] = lang('updated');
                  }
                  elseif(count($this->globalnotices) == 0)
                  {
                     $this->globalnotices[]  = lang('requestsentforapproval');
                  }
                  $this->load->view('page',$data);
                  return;
                }
                catch(Exception $e)
                {
                   log_message('error',__METHOD__.' '.$e);
                   show_error('Internal server error',500);
                   return;
                }

                $data['r'] = $this->form_element->NgenerateRegistrationPolicies($this->entity);
                $data['content_view'] = 'manage/entityedit_regpolicies';
                $this->load->view('page', $data);
            }
        }
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
        $type = $this->entity->getType();
        if (strcasecmp($type, 'SP') == 0)
        {
            $titleprefix = lang('serviceprovider');
        }
        elseif (strcasecmp($type, 'IDP') == 0)
        {
            $titleprefix = lang('identityprovider');
        }
        else
        {
            $titleprefix = '';
        }
        $lang = MY_Controller::getLang();
        $titlename = $this->entity->getNameToWebInLang($lang, $type);

        $data['titlepage'] = $titleprefix . ': <a href="' . base_url() . 'providers/detail/show/' . $this->entity->getId() . '">' . $titlename . '</a>';
        $data['subtitlepage'] = lang('rr_status_mngmt');
        $data['entid'] = $id;
        $data['current_locked'] = $this->entity->getLocked();
        $data['current_active'] = $this->entity->getActive();
        $data['current_extint'] = $this->entity->getLocal();
        $data['current_publicvisible'] = (int) $this->entity->getPublicVisible();
        $validfrom = $this->entity->getValidFrom();
        if (!empty($validfrom))
        {
            $validfromdate = date('Y-m-d', $validfrom->format('U'));
            $validfromtime = date('H:i', $validfrom->format('U'));
        }
        else
        {
            $validfromdate = '';
            $validfromtime = '';
        }
        $validuntil = $this->entity->getValidTo();
        if (!empty($validuntil))
        {
            $validuntildate = date('Y-m-d', $validuntil->format('U'));
            $validuntiltime = date('H:i', $validuntil->format('U'));
        }
        else
        {
            $validuntildate = '';
            $validuntiltime = '';
        }
        $data['current_validuntildate'] = $validuntildate;
        $data['current_validuntiltime'] = $validuntiltime;
        $data['current_validfromdate'] = $validfromdate;
        $data['current_validfromtime'] = $validfromtime;
        $has_manage_access = $this->zacl->check_acl($this->entity->getId(), 'manage', 'entity', '');
        if (!$has_manage_access)
        {
            show_error('No sufficient permision to manage entity', 403);
            return;
        }


        if ($this->_submit_validate() === TRUE)
        {
            $locked = $this->input->post('elock');
            $active = $this->input->post('eactive');
            $extint = $this->input->post('extint');
            $publicvisible = $this->input->post('publicvisible');
            $validfromdate = $this->input->post('validfromdate');
            $validfromtime = $this->input->post('validfromtime');
            $validuntildate = $this->input->post('validuntildate');
            $validuntiltime = $this->input->post('validuntiltime');

            $changed = false;
            $differ = array();
            if (isset($locked))
            {
                if ($data['current_locked'] != $locked)
                {

                    if ($locked == '1')
                    {
                        $differ['Lock'] = array('before' => 'unlocked', 'after' => 'locked');
                        $this->entity->Lock();
                    }
                    elseif ($locked == '0')
                    {
                        $this->entity->Unlock();
                        $differ['Lock'] = array('before' => 'locked', 'after' => 'unlocked');
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
                        $differ['Active'] = array('before' => 'disabled', 'after' => 'enabled');
                    }
                    elseif ($active == '0')
                    {
                        $this->entity->Disactivate();
                        $differ['Active'] = array('before' => 'enabled', 'after' => 'disabled');
                    }
                    $changed = true;
                }
            }
            if (isset($publicvisible))
            {
                if ($data['current_publicvisible'] != $publicvisible)
                {
                    if ($publicvisible == '1')
                    {
                        $this->entity->setVisiblePublic();
                        $differ['PublicVisible'] = array('before' => 'disabled', 'after' => 'enabled');
                    }
                    elseif ($publicvisible == '0')
                    {
                        $this->entity->setHidePublic();
                        $differ['PublicVisible'] = array('before' => 'enabled', 'after' => 'disabled');
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
                        $differ['Local/External'] = array('before' => 'external', 'after' => 'local');
                    }
                    elseif ($extint == '0')
                    {
                        $this->entity->setAsExternal();
                        $differ['Local/External'] = array('before' => 'local', 'after' => 'external');
                    }
                    $changed = true;
                }
            }
            if (!empty($validuntildate) && !empty($validuntiltime))
            {
                $validuntil = new DateTime($validuntildate . 'T' . $validuntiltime);
                $this->entity->setValidTo($validuntil);
            }
            else
            {
                $this->entity->setValidTo(null);
            }
            if (!empty($validfromdate) && !empty($validfromtime))
            {
                $validfrom = new DateTime($validfromdate . 'T' . $validfromtime);
                $this->entity->setValidFrom($validfrom);
            }
            else
            {
                $this->entity->setValidFrom(null);
            }
            if (count($differ) > 0)
            {
                $this->tracker->save_track('idp', 'modification', $this->entity->getEntityId(), serialize($differ), false);
            }
            $this->em->persist($this->entity);
            try
            {
                $this->em->flush();
                $data['success_message'] = lang('rr_entstate_updated');
            }
            catch (Exception $e)
            {
                $data['error'] = 'Unkwown error occured during saving changes';
                log_message('error', __METHOD__ . ' ' . $e);
            }
        }
        $data['current_locked'] = $this->entity->getLocked();
        $data['current_active'] = $this->entity->getActive();
        $data['current_extint'] = $this->entity->getLocal();
        $data['current_publicvisible'] = (int) $this->entity->getPublicVisible();
        $data['entityid'] = $this->entity->getEntityId();
        $data['name'] = $this->entity->getName();
        $data['id'] = $this->entity->getId();
        $data['type'] = strtolower($this->entity->getType());
        $validfrom = $this->entity->getValidFrom();
        if (!empty($validfrom))
        {
            $validfromdate = date('Y-m-d', $validfrom->format('U') + j_auth::$timeOffset);
            $validfromtime = date('H:i', $validfrom->format('U') + j_auth::$timeOffset);
        }
        else
        {
            $validfromdate = '';
            $validfromtime = '';
        }
        $validuntil = $this->entity->getValidTo();
        if (!empty($validuntil))
        {
            $validuntildate = date('Y-m-d', $validuntil->format('U'));
            $validuntiltime = date('H:i', $validuntil->format('U'));
        }
        else
        {
            $validuntildate = '';
            $validuntiltime = '';
        }
        $data['current_validuntildate'] = $validuntildate;
        $data['current_validuntiltime'] = $validuntiltime;
        $data['current_validfromdate'] = $validfromdate;
        $data['current_validfromtime'] = $validfromtime;



        $data['content_view'] = 'manage/entitystate_form_view';
        $this->load->view('page', $data);
    }

}
