<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
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
 * Access_manage Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Access_manage extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $this->tmp_providers = new models\Providers;
        $this->load->helper('form');
    }

    private function displayFormChng($access, $user, $action)
    {

        $form = '<div class="permset">' . form_open() . form_hidden('user', $user) . form_hidden('action', $action);
        if ($access === 'allow')
        {
            $form .='<button type="submit" name="change_access"  value="' . $access . '" class="addbutton addicon button tiny">' . lang('btn_allow') . '</button>';
        }
        else
        {
            $form .='<button  type="submit" name="change_access"  value="' . $access . '"  class="resetbutton deleteicon button tiny alert">' . lang('btn_deny') . '</button>';
        }
        $form .= form_close() . '</div>';
        return $form;
    }

    public function entity($id)
    {

        /**
         * @var $ent models\Provider
         */
        $ent = $this->tmp_providers->getOneById($id);
        if ($ent === null)
        {
            show_error(lang('rerror_providernotexist'), 404);
        }
        $group = strtolower($ent->getType());
        if ($group === 'both')
        {
            $group = 'entity';
        }
        $isLocal = $ent->getLocal();
        if ($isLocal)
        {
            /**
             * @var $isResourceAcl models\AclResource
             * @var $parent models\AclResource
             */
            $isResourceAcl = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $ent->getId()));
            if ($isResourceAcl === null)
            {
                $parent = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => 'entity'));
                $aclResource = new models\AclResource;
                $resourceName = $ent->getId();
                $aclResource->setResource($resourceName);
                $aclResource->setDefaultValue('view');
                $aclResource->setType('entity');
                $aclResource->setParent($parent);
                $this->em->persist($aclResource);
                $this->em->flush();
            }
        }

        $this->load->library('zacl');

        $hasManageAccess = $this->zacl->check_acl($ent->getId(), 'manage', $group, '');
        if (!$hasManageAccess)
        {
            show_error(lang('rr_noperm'), 403);
        }

        if (!$isLocal)
        {
            show_error(lang('rr_externalentity'), 403);
        }



        $submited = $this->input->post('change_access');
        if (!empty($submited))
        {


            $resource = $ent->getId();
            log_message('debug', __METHOD__ . 'change access level submited: ' . $resource);

            $action = $this->input->post('action');

            if ($submited === 'deny')
            {


                $user = $this->input->post('user');
                $resource_type = 'entity';
                if ($action === 'read')
                {
                    $this->zacl->deny_access_fromUser($resource, $action, $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($resource, 'write', $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($resource, 'manage', $user, $group, $resource_type);
                }
                elseif ($action === 'write')
                {
                    $this->zacl->deny_access_fromUser($resource, $action, $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($resource, 'manage', $user, $group, $resource_type);
                }
                elseif ($action === 'manage')
                {
                    $this->zacl->deny_access_fromUser($resource, $action, $user, $group, $resource_type);
                }
                $this->em->flush();
            }
            elseif ($submited === 'allow')
            {


                $user = $this->input->post('user');
                $resource_type = 'entity';
                if ($action === 'manage')
                {
                    $this->zacl->add_access_toUser($resource, $action, $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($resource, 'write', $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($resource, 'read', $user, $group, $resource_type);
                }
                elseif ($action === 'write')
                {
                    $this->zacl->add_access_toUser($resource, $action, $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($resource, 'read', $user, $group, $resource_type);
                }
                elseif ($action === 'read')
                {
                    $this->zacl->add_access_toUser($resource, $action, $user, $group, $resource_type);
                }
                $this->em->flush();
            }
            else
            {
                log_message('error', 'access_manage: incorrect submit:' . $submited);
            }
        }
        else
        {
            log_message('debug', 'no change access submited');
        }
        $this->em->flush();
        $this->zacl = new Zacl();

        /**
         * @var models\User[] $tmpUsers
         * @var models\AclRole $adminRole
         */
        $tmpUsers = $this->em->getRepository("models\User")->findAll();
        $adminRole = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
        $admins = $adminRole->getMembers();
        $users_array = array();
        $users_objects = array();
        $actions = array('read', 'write', 'manage');
        $id_of_entity = $ent->getId();
        foreach ($tmpUsers as $u)
        {
            $users_objects[$u->getUsername()] = $u;
            foreach ($actions as $a)
            {
                $users_array[$u->getUsername()][$a] = $this->zacl->check_acl_for_user($id_of_entity, $a, $u->getUsername(), $group);
            }
        }


        $row = array();
        $i = 0;
        $currentUser = $this->jauth->getLoggedinUsername();
        foreach ($users_array as $key => $value)
        {
            $is_me = '';
            $isitme = false;
            if ($currentUser === $key)
            {
                $is_me = '<span class="alert">' . lang('rr_you') . '</span>';
                $isitme = true;
            }
            $u = $admins->contains($users_objects[$key]);
            if ($u)
            {
                $row[$i] = array($key . ' (Administrator)' . $is_me, '' . lang('rr_hasaccess') . '', '' . lang('rr_hasaccess') . '', '' . lang('rr_hasaccess') . '');
            }
            else
            {
                $row[$i][] = $key . ' (' . $users_objects[$key]->getFullname() . ')' . $is_me;
                $hasAccess = lang('rr_hasaccess');
                $hasNoAccess = lang('rr_hasnoaccess');
                foreach ($value as $ackey => $acvalue)
                {
                    if ($acvalue)
                    {
                        if (!$isitme)
                        {
                            $row[$i][] = $hasAccess . $this->displayFormChng('deny', $key, $ackey);
                        }
                        else
                        {
                            $row[$i][] = $hasAccess;
                        }
                    }
                    else
                    {
                        if (!$isitme)
                        {
                            $row[$i][] = $hasNoAccess . $this->displayFormChng('allow', $key, $ackey);
                        }
                        else
                        {
                            $row[$i][] = $hasNoAccess;
                        }
                    }
                }
            }
            $i++;
        }
        $entity_link = anchor(base_url() . 'providers/detail/show/' . $id_of_entity, '<i class="fi-arrow-right"></i>');
        $data['resource_name'] = $ent->getName() . ' (' . $ent->getEntityId() . ')' . $entity_link;
        $lang = MY_Controller::getLang();
        $data['resourcename'] = $ent->getNameToWebInLang($lang, $ent->getType());
        $data['entityid'] = $ent->getEntityId();
        $data['resourceid'] = $id_of_entity;
        $data['row'] = $row;
        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $ent->getId() . '">' . $data['resourcename'] . '</a>';
        $data['subtitlepage'] = lang('rr_accessmngmt');
        $data['content_view'] = 'manage/access_manage_view';
        if(strcasecmp($ent->getType(),'SP')==0)
        {
            $plist = array('url'=>base_url('providers/sp_list/showlist'),'name'=>lang('serviceproviders'));
        }
        else
        {
            $plist = array('url'=>base_url('providers/idp_list/showlist'),'name'=>lang('identityproviders'));
        }
	    $data['breadcrumbs'] = array(
            $plist,
		    array('url'=>base_url('providers/detail/show/'.$ent->getId().''),'name'=>''.html_escape($data['resourcename']).''),
		    array('url'=>'#','name'=>lang('rr_accessmngmt'),'type'=>'current'),
	    );
        $this->load->view('page', $data);
    }

    public function federation($id)
    {
        $this->load->library('zacl');
        /**
         * @var models\Federation $fed
         */
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $id));
        if ($fed === null)
        {
            show_error(lang('error_fednotfound'), 404);
        }
        $fedurl= base64url_encode($fed->getName());
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/'.$fedurl.''), 'name' => '' . $fed->getName() . ''),
            array('url'=>'#','type'=>'current','name'=>lang('rr_accessmngmt'))

        );

        $group = 'federation';
        $has_manage_access = $this->zacl->check_acl('f_' . $fed->getId(), 'manage', $group, '');
        if (!$has_manage_access)
        {
            show_error(lang('rerror_noperm_mngperm'), 403);
        }
        $submited = $this->input->post('change_access');
        if (!empty($submited))
        {
            log_message('debug', 'change access submited');
            if ($submited === 'deny')
            {
                $fresource = 'f_' . $fed->getId();
                $action = $this->input->post('action');
                $user = $this->input->post('user');
                $resource_type = 'federation';
                if ($action === 'read')
                {
                    $this->zacl->deny_access_fromUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($fresource, 'write', $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($fresource, 'manage', $user, $group, $resource_type);
                }
                elseif ($action === 'write')
                {
                    $this->zacl->deny_access_fromUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($fresource, 'manage', $user, $group, $resource_type);
                }
                elseif ($action === 'manage')
                {
                    $this->zacl->deny_access_fromUser($fresource, $action, $user, $group, $resource_type);
                }
                $this->em->flush();
            }
            elseif ($submited === 'allow')
            {
                $fresource = 'f_' . $fed->getId();
                $action = $this->input->post('action');
                $user = $this->input->post('user');
                $resource_type = 'federation';
                if ($action === 'manage')
                {
                    $this->zacl->add_access_toUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($fresource, 'write', $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($fresource, 'read', $user, $group, $resource_type);
                }
                elseif ($action === 'write')
                {
                    $this->zacl->add_access_toUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($fresource, 'read', $user, $group, $resource_type);
                }
                elseif ($action === 'read')
                {
                    $this->zacl->add_access_toUser($fresource, $action, $user, $group, $resource_type);
                }
                $this->em->flush();
            }
            else
            {
                log_message('error', 'access_manage: incorrect submit:' . $submited);
            }
        }
        else
        {
            log_message('debug', 'no change access submited');
        }
        $this->em->flush();
        $this->zacl = new Zacl();
        $tmp_users = $this->em->getRepository("models\User")->findAll();
        $admin_role = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
        $admins = $admin_role->getMembers();
        $users_array = array();
        $users_objects = array();
        $actions = array('read', 'write', 'manage');
        $id_of_fed = 'f_' . $fed->getId();
        foreach ($tmp_users as $u)
        {
            $users_objects[$u->getUsername()] = $u;
            foreach ($actions as $a)
            {
                $users_array[$u->getUsername()][$a] = $this->zacl->check_acl_for_user($id_of_fed, $a, $u->getUsername(), $group);
            }
        }
        $row = array();
        $i = 0;
        $sessionUser = $this->jauth->getLoggedinUsername();

        foreach ($users_array as $key => $value)
        {
            $is_me = '';
            $isitme = false;
            if ($sessionUser == $key)
            {
                $is_me = '<span class="alert">' . lang('rr_you') . '</span>';
                $isitme = true;
            }
            $u = $admins->contains($users_objects[$key]);
            if ($u)
            {
                $k = 'admin';
            }
            else
            {
                $k = '';
            }
            if ($k)
            {
                $row[$i] = array('' . $is_me . ' ' . $key . ' (Administrator' . showBubbleHelp('' . lang('rhelp_admfullright') . '') . ')', '' . lang('rr_hasaccess') . '', '' . lang('rr_hasaccess') . '', '' . lang('rr_hasaccess') . '');
            }
            else
            {
                $row[$i][] = $is_me . ' ' . $key . ' ';
                $hasAccess = lang('rr_hasaccess');
                $hasNoAccess = lang('rr_hasnoaccess');
                foreach ($value as $ackey => $acvalue)
                {
                    if ($acvalue)
                    {
                        if (!$isitme)
                        {
                            $row[$i][] = $hasAccess . $this->displayFormChng('deny', $key, $ackey);
                        }
                        else
                        {
                            $row[$i][] = $hasAccess;
                        }
                    }
                    else
                    {
                        if (!$isitme)
                        {
                            $row[$i][] = $hasNoAccess . $this->displayFormChng('allow', $key, $ackey);
                        }
                        else
                        {
                            $row[$i][] = $hasNoAccess;
                        }
                    }
                }
            }
            $i++;
        }
        $data['fedlink'] = base_url() . 'federations/manage/show/' . base64url_encode($fed->getName());
        $data['resourcename'] = $fed->getName();
        $data['row'] = $row;
        $data['titlepage'] = lang('rr_federation') . ' ' . lang('rr_accessmngmt') . ': ' . anchor($data['fedlink'], $data['resourcename']);
        $data['readlegend'] = lang('fedaclreadinfo');
        $data['content_view'] = 'manage/fedaccess_manage_view';
        $this->load->view('page', $data);
    }

}
