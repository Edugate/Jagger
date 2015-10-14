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
 * Accessmanage Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Accessmanage extends MY_Controller
{

    protected $tmpProviders;

    public function __construct() {
        parent::__construct();

        $this->tmpProviders = new models\Providers;
        $this->load->helper('form');
        $this->load->library('form_validation');
    }

    private function displayFormChng($access, $user, $action) {

        $form = '<div class="permset">' . form_open() . form_hidden('user', $user) . form_hidden('action', $action);
        if ($access === 'allow') {
            $form .= '<button type="submit" name="change_access"  value="' . $access . '" class="addbutton addicon button tiny">' . lang('btn_allow') . '</button>';
        } else {
            $form .= '<button  type="submit" name="change_access"  value="' . $access . '"  class="resetbutton deleteicon button tiny alert">' . lang('btn_deny') . '</button>';
        }
        $form .= form_close() . '</div>';

        return $form;
    }

    private function prepareResourceData($resourceType = null, $resourceId = null) {
        if (!in_array($resourceType, array('federation', 'entity'), true) || !ctype_digit($resourceId) || !$this->jauth->isLoggedIn()) {
            throw new Exception('Access denied.');
        }

        if ($resourceType === 'federation') {
            /**
             * @var models\Federation $resource
             */
            $resource = $this->em->getRepository('models\Federation')->findOneBy(array('id' => $resourceId));
            $group = 'federation';
            $prefixId = 'f_';
            $actions = array('read', 'write', 'manage', 'approve');

        } else {
            /**
             * @var models\Provider $resource
             */
            $resource = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $resourceId));
            $group = 'entity';
            $prefixId = '';
            $actions = array('read', 'write', 'manage');
        }
        if ($resource === null) {
            throw new Exception('Resource not found.');
        }
        if ($group === 'entity') {
            $isLocal = $resource->getLocal();
            if ($isLocal) {
                /**
                 * @var $isResourceAcl models\AclResource
                 * @var $parent models\AclResource
                 */
                $isResourceAcl = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $resource->getId()));
                if ($isResourceAcl === null) {
                    $parent = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => 'entity'));
                    $aclResource = new models\AclResource;
                    $resourceName = $resource->getId();
                    $aclResource->setResource($resourceName);
                    $aclResource->setDefaultValue('view');
                    $aclResource->setType('entity');
                    $aclResource->setParent($parent);
                    $this->em->persist($aclResource);
                    $this->em->flush();
                }
            }
            else{
                throw new Exception('Cannot manage access to external entity.');
            }
        }

        $this->load->library('zacl');
        $hasManageAccess = $this->zacl->check_acl('' . $prefixId . $resource->getId(), 'manage', $group, '');
        if (!$hasManageAccess) {
            throw new Exception('No access to mamange permissions.');
        }

        return array(
            'resource'  => $resource,
            'aclprefix' => $prefixId,
            'aclgroup'  => $group,
            'actions'   => $actions

        );

    }

    private function validateUpdatePost() {

        $this->form_validation->set_rules('changeaccess', 'Update access', 'trim|required|min_length[5]|max_length[500]|no_white_spaces');

        return $this->form_validation->run();

    }


    public function update($resourceType = null, $resourceId = null) {
        try {
            $resourceData = $this->prepareResourceData($resourceType, $resourceId);
        } catch (Exception $e) {
            return $this->output->set_status_header(403)->set_output(html_escape($e->getMessage()));
        }
        if ($this->validateUpdatePost() !== true) {
            return $this->output->set_status_header(403)->set_output('Error in post');
        }

        $changeaccess = $this->input->post('changeaccess');
        $extractedData = explode('$|$', $changeaccess);
        if (count($extractedData) !== 3 || !in_array($extractedData[1], $resourceData['actions'], true)) {
            return $this->output->set_status_header(403)->set_output('Invalid data in post');
        }

        /**
         * @var models\User $user
         */
        $user = $this->em->getRepository('models\User')->findOneBy(array('username' => '' . $extractedData[0] . ''));
        if ($user === null) {
            return $this->output->set_status_header(403)->set_output('User not found');
        }

        $username = $user->getUsername();
        $currentUser = $this->jauth->getLoggedinUsername();
        if ($username === $currentUser) {
            return $this->output->set_status_header(403)->set_output('Cannot change own permissions');
        }

        if ($extractedData[2] === 'allow') {
            $this->addAccess('' . $resourceData['aclprefix'] . $resourceId . '', $extractedData[1], $username, $resourceData['aclgroup'], $resourceType);
        } elseif ($extractedData[2] === 'deny') {
            $this->removeAccess('' . $resourceData['aclprefix'] . $resourceId . '', $extractedData[1], $username, $resourceData['aclgroup'], $resourceType);
        }


    }

    private function addAccess($resource, $action, $username, $group, $resourcetype) {
        if ($action === 'manage') {
            $this->zacl->add_access_toUser($resource, 'manage', $username, $group, $resourcetype);
            $this->zacl->add_access_toUser($resource, 'write', $username, $group, $resourcetype);
            $this->zacl->add_access_toUser($resource, 'read', $username, $group, $resourcetype);
        } elseif ($action === 'write') {
            $this->zacl->add_access_toUser($resource, 'write', $username, $group, $resourcetype);
            $this->zacl->add_access_toUser($resource, 'read', $username, $group, $resourcetype);
        } elseif ($action === 'read') {
            $this->zacl->add_access_toUser($resource, 'read', $username, $group, $resourcetype);
        } else {
            return false;
        }
        $this->em->flush();

        return true;
    }

    private function removeAccess($resource, $action, $username, $group, $resourcetype) {
        if ($action === 'manage') {
            $this->zacl->deny_access_fromUser($resource, 'manage', $username, $group, $resourcetype);
        } elseif ($action === 'write') {
            $this->zacl->deny_access_fromUser($resource, 'manage', $username, $group, $resourcetype);
            $this->zacl->deny_access_fromUser($resource, 'write', $username, $group, $resourcetype);
        } elseif ($action === 'read') {

            $this->zacl->deny_access_fromUser($resource, 'manage', $username, $group, $resourcetype);
            $this->zacl->deny_access_fromUser($resource, 'write', $username, $group, $resourcetype);
            $this->zacl->deny_access_fromUser($resource, 'read', $username, $group, $resourcetype);
        } else {
            return false;
        }
        $this->em->flush();

        return true;
    }

    public function getusersrights($resourceType = null, $resourceId = null) {


        try {
            $resourceData = $this->prepareResourceData($resourceType, $resourceId);
        } catch (Exception $e) {
            return $this->output->set_status_header(403)->set_output(html_escape($e->getMessage()));
        }

        $result = array('definitions' => array('actions' => $resourceData['actions'], 'dictionary' => array('allow' => 'allow', 'deny' => 'deny', 'hasaccess' => lang('rr_hasaccess'), 'hasnoaccess' => lang('rr_hasnoaccess'), 'username' => lang('rr_username'))));

        /**
         * @var models\User[] $users
         * @var models\AclRole $adminRole
         */
        $users = $this->em->getRepository('models\User')->findAll();

        foreach ($users as $user) {
            $result['data'][$user->getUsername()] = array('isadmin' => false, 'fullname' => $user->getFullname(), 'email' => $user->getEmail());
            foreach ($resourceData['actions'] as $action) {
                $result['data'][$user->getUsername()]['perms'][$action] = $this->zacl->check_acl_for_user($resourceData['aclprefix'] . $resourceId, $action, $user, $resourceData['aclgroup']);
            }
        }
        $adminRole = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
        $admins = $adminRole->getMembers();

        foreach ($admins as $admin) {
            // $result['admins'][] = $admin->getUsername();
            $adminUsername = $admin->getUsername();
            if (array_key_exists($adminUsername, $result['data'])) {
                $result['data']['' . $adminUsername . '']['isadmin'] = true;
            }

        }
        $result['data']['' . $this->jauth->getLoggedinUsername() . '']['isyou'] = true;

        return $this->output->set_content_type('application/json')->set_output(json_encode($result));


    }


    public function entity($id) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        /**
         * @var models\Provider $ent
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $id));
        if ($ent === null) {
            show_error(lang('rerror_providernotexist'), 404);
        }
        $myLang = MY_Controller::getLang();
        $data['resourcename'] = $ent->getNameToWebInLang($myLang);
        if (strcasecmp($ent->getType(), 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        } else {
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        }
        $data['breadcrumbs'] = array(
            $plist,
            array('url' => base_url('providers/detail/show/' . $ent->getId() . ''), 'name' => '' . html_escape($data['resourcename']) . ''),
            array('url' => '#', 'name' => lang('rr_accessmngmt'), 'type' => 'current'),
        );

        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $ent->getId() . '">' . $data['resourcename'] . '</a>';
        $data['subtitlepage'] = lang('rr_accessmngmt');
        $data['resourcetype'] = 'entity';
        $data['resourceid'] = $id;
        $data['content_view'] = 'manage/accessmanage_view';
        $this->load->view('page', $data);


    }

    public function federation($id) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        /**
         * @var models\Federation $federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $id));
        if ($federation === null) {
            show_error(lang('error_fednotfound'), 404);
        }
        $fedurl = base64url_encode($federation->getName());
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . $fedurl . ''), 'name' => '' . $federation->getName() . ''),
            array('url' => '#', 'type' => 'current', 'name' => lang('rr_accessmngmt'))

        );

        $group = 'federation';
        $has_manage_access = $this->zacl->check_acl('f_' . $federation->getId(), 'manage', $group, '');
        if (!$has_manage_access) {
            show_error(lang('rerror_noperm_mngperm'), 403);
        }
        $submited = $this->input->post('change_access');
        if (!empty($submited)) {
            log_message('debug', 'change access submited');
            if ($submited === 'deny') {
                $fresource = 'f_' . $federation->getId();
                $action = $this->input->post('action');
                $user = $this->input->post('user');
                $resource_type = 'federation';
                if ($action === 'read') {
                    $this->zacl->deny_access_fromUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($fresource, 'write', $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($fresource, 'manage', $user, $group, $resource_type);
                } elseif ($action === 'write') {
                    $this->zacl->deny_access_fromUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->deny_access_fromUser($fresource, 'manage', $user, $group, $resource_type);
                } elseif ($action === 'manage') {
                    $this->zacl->deny_access_fromUser($fresource, $action, $user, $group, $resource_type);
                }
                $this->em->flush();
            } elseif ($submited === 'allow') {
                $fresource = 'f_' . $federation->getId();
                $action = $this->input->post('action');
                $user = $this->input->post('user');
                $resource_type = 'federation';
                if ($action === 'manage') {
                    $this->zacl->add_access_toUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($fresource, 'write', $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($fresource, 'read', $user, $group, $resource_type);
                } elseif ($action === 'write') {
                    $this->zacl->add_access_toUser($fresource, $action, $user, $group, $resource_type);
                    $this->zacl->add_access_toUser($fresource, 'read', $user, $group, $resource_type);
                } elseif ($action === 'read') {
                    $this->zacl->add_access_toUser($fresource, $action, $user, $group, $resource_type);
                }
                $this->em->flush();
            } else {
                log_message('error', 'accessmanage: incorrect submit:' . $submited);
            }
        } else {
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
        $id_of_fed = 'f_' . $federation->getId();
        foreach ($tmp_users as $u) {
            $users_objects[$u->getUsername()] = $u;
            foreach ($actions as $a) {
                $users_array[$u->getUsername()][$a] = $this->zacl->check_acl_for_user($id_of_fed, $a, $u, $group);
            }
        }
        $row = array();
        $i = 0;
        $sessionUser = $this->jauth->getLoggedinUsername();

        foreach ($users_array as $key => $value) {
            $is_me = '';
            $isitme = false;
            if ($sessionUser == $key) {
                $is_me = '<span class="alert">' . lang('rr_you') . '</span>';
                $isitme = true;
            }
            $u = $admins->contains($users_objects[$key]);
            if ($u) {
                $k = 'admin';
            } else {
                $k = '';
            }
            if ($k) {
                $row[$i] = array('' . $is_me . ' ' . $key . ' (Administrator' . showBubbleHelp('' . lang('rhelp_admfullright') . '') . ')', '' . lang('rr_hasaccess') . '', '' . lang('rr_hasaccess') . '', '' . lang('rr_hasaccess') . '');
            } else {
                $row[$i][] = $is_me . ' ' . $key . ' ';
                $hasAccess = lang('rr_hasaccess');
                $hasNoAccess = lang('rr_hasnoaccess');
                foreach ($value as $ackey => $acvalue) {
                    if ($acvalue) {
                        if (!$isitme) {
                            $row[$i][] = $hasAccess . $this->displayFormChng('deny', $key, $ackey);
                        } else {
                            $row[$i][] = $hasAccess;
                        }
                    } else {
                        if (!$isitme) {
                            $row[$i][] = $hasNoAccess . $this->displayFormChng('allow', $key, $ackey);
                        } else {
                            $row[$i][] = $hasNoAccess;
                        }
                    }
                }
            }
            $i++;
        }
        $data['fedlink'] = base_url() . 'federations/manage/show/' . base64url_encode($federation->getName());
        $data['resourcename'] = $federation->getName();
        $data['row'] = $row;
        $data['titlepage'] = lang('rr_federation') . ' ' . lang('rr_accessmngmt') . ': ' . anchor($data['fedlink'], $data['resourcename']);
        $data['readlegend'] = lang('fedaclreadinfo');
        $data['content_view'] = 'manage/fedaccess_manage_view';
        $this->load->view('page', $data);
    }

}
