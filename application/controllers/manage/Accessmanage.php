<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
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
            } else {
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
        if ($extractedData[1] === 'approve' && !$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(403)->set_output('Not sufficient right to change access');
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
        } elseif ($action === 'approve') {
            $this->zacl->add_access_toUser($resource, 'read', $username, $group, $resourcetype);
            $this->zacl->add_access_toUser($resource, 'approve', $username, $group, $resourcetype);
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
        } elseif ($action === 'approve') {

            $this->zacl->deny_access_fromUser($resource, 'approve', $username, $group, $resourcetype);
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

        $result = array('definitions' => array('admin' => $this->jauth->isAdministrator(), 'actions' => $resourceData['actions'], 'dictionary' => array('allow' => 'allow', 'deny' => 'deny', 'hasaccess' => lang('rr_hasaccess'), 'hasnoaccess' => lang('rr_hasnoaccess'), 'username' => lang('rr_username'))));

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
            $adminUsername = $admin->getUsername();
            if (array_key_exists($adminUsername, $result['data'])) {
                $result['data']['' . $adminUsername . '']['isadmin'] = true;
            }

        }
        $result['data']['' . $this->jauth->getLoggedinUsername() . '']['isyou'] = true;


        return $this->output->set_content_type('application/json')->set_output(json_encode($result));


    }


    public function federation($id) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        /**
         * @var models\Federation $federation
         */
        $federation = $this->em->getRepository('models\Federation')->findOneBy(array('id' => $id));
        if ($federation === null) {
            show_error('Federation not found', 404);
        }
        $fedurl = base64url_encode($federation->getName());
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . $fedurl . ''), 'name' => '' . $federation->getName() . ''),
            array('url' => '#', 'type' => 'current', 'name' => lang('rr_accessmngmt'))

        );
        $data['resourceid'] = $id;
        $data['resourcename'] = $federation->getName();
        $data['resourcetype'] = 'federation';
        $data['content_view'] = 'manage/accessmanage_view';
        $data['fedlink'] = base_url() . 'federations/manage/show/' . base64url_encode($federation->getName());
        $data['titlepage'] = lang('rr_federation') . ' ' . lang('rr_accessmngmt') . ': ' . anchor($data['fedlink'], $data['resourcename']);
        $this->load->view('page', $data);
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

}
