<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Zacl
{

    // Set the instance variable
    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;
    protected $acl;


    public function __construct() {
        // Get the instance
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;

        $this->acl = new Zend\Permissions\Acl\Acl();
        $this->acl->addRole(new Zend\Permissions\Acl\Role\GenericRole('default_role'));
        /**
         * @var models\AclRole[] $aclRoles
         */
        $aclRoles = $this->em->getRepository("models\AclRole")->findAll();


        /**
         * end get roles
         */
        $roleArray = array();
        foreach ($aclRoles as $r) {
            $role = new Zend\Permissions\Acl\Role\GenericRole($r->getName());
            $parent = $r->getParent();
            if ($parent !== null) {
                $this->acl->addRole($role, $r->getParent()->getName());
            } else {
                $this->acl->addRole($role, 'default_role');
            }
            $roleArray[$r->getId()] = $role;
        }

        $loggedinUsername = $this->ci->jauth->getLoggedinUsername();


        /**
         * @var models\User $loggedinUser
         */
        $loggedinUser = $this->em->getRepository("models\User")->findOneBy(array('username' => $loggedinUsername));
        if ($loggedinUser === null) {
            log_message('error', __METHOD__ . ' Loggedin username: ' . $loggedinUsername . ' not found in user table');
            throw new \Exception('User not found');
        }

        /**
         * @var models\AclRole[] $loggedinUserRoles
         */
        $loggedinUserRoles = $loggedinUser->getRoles();


        $my_roles_array = array('system' => array(), 'group' => array(), 'user' => array());
        if (!empty($loggedinUserRoles)) {
            foreach ($loggedinUserRoles as $p) {
                $type = $p->getType();
                $my_roles_array[$type][] = $p->getName();
            }
        }
        $isAdmin = array_search('Administrator', $my_roles_array['system']);

        if (in_array('Administrator', $my_roles_array['system'], true)) {

            $isAdmin = true;
        }
        $parents = array();
        foreach ($my_roles_array as $groupRoles) {
            foreach ($groupRoles as $krole) {
                $parents[] = $krole;
            }
        }

        if ($isAdmin) {
            $kad = array_search('Administrator', $parents);
            unset($parents[$kad]);
            $parents[] = 'Administrator';
        }
        if (count($parents) > 0) {
            $this->acl->addRole('current_user', $parents);
        } else {
            $this->acl->addRole('current_user', 'default_role');
        }
        $this->acl->addResource(new Zend\Permissions\Acl\Resource\GenericResource('root_resource'));

        /**
         * @var models\AclResource[] $defined_resources
         */
        $defined_resources = $this->em->getRepository("models\AclResource")->findAll();
        foreach ($defined_resources as $res) {
            $resource = new Zend\Permissions\Acl\Resource\GenericResource($res->getResource());
            $r_parent = $res->getParent();

            if ($r_parent !== null) {
                $this->acl->addResource($resource, $r_parent->getResource());
            } else {
                $this->acl->addResource($resource, 'root_resource');
            }

        }
        /**
         * @var models\Acl[] $defined_acls
         */
        $defined_acls = $this->em->getRepository("models\Acl")->findAll();
        if (!empty($defined_acls)) {
            foreach ($defined_acls as $a) {
                $access = $a->getAccess();
                $action_type = $a->getAction();
                $role = null;
                $resource = null;
                $r = $a->getRole();
                if (!empty($r)) {
                    $role = $r->getName();
                }
                $s = $a->getResource();
                if (!empty($s)) {
                    $resource = $s->getResource();
                }


                if ($access) {
                    $this->acl->allow($role, $resource, $action_type);
                } else {
                    $this->acl->deny($role, $resource, $action_type);
                }
            }
        }

        $this->acl->allow('Administrator');

    }

    public function check_acl($resource, $action, $group = '', $role = '') {
        if (empty($role)) {
            $role = 'current_user';
        }
        if (empty($group)) {
            $group = 'default_resource';
        }
        if (!$this->acl->hasResource($resource)) {
            if (!$this->acl->hasResource($group)) {
                return false;
            }
            $resource = $group;
        }

        $this->acl->allow('Administrator', $resource, $action);

        return $this->acl->isAllowed($role, $resource, $action);
    }

    public function check_acl_for_user($resource, $action, $user, $group) {
        $access = $this->check_user_acl($resource, $action, $user, $group);
        $role_exists = $this->acl->hasRole('selected_user');
        if ($role_exists) {
            $this->acl->removeRole('selected_user');
        }

        return $access;
    }

    private function check_user_acl($resource, $action, $user, $group) {
        $s_user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user));
        if ($s_user !== null) {
            $user_roles = $s_user->getRoles();
        }
        $my_roles_array = array('system' => array(), 'group' => array(), 'user' => array());
        if (!empty($user_roles)) {
            foreach ($user_roles as $p) {
                $type = $p->getType();
                $my_roles_array[$type][] = $p->getName();
            }
        }
        $isAdmin = array_search('Administrator', $my_roles_array['system']);

        if (in_array('Administrator', $my_roles_array['system'], true)) {

            $isAdmin = true;
        }
        $parents = array();
        foreach ($my_roles_array as $roleGroup) {
            foreach ($roleGroup as $krole) {
                $parents[] = $krole;
            }
        }

        if ($isAdmin) {
            $kad = array_search('Administrator', $parents);
            unset($parents[$kad]);
            $parents[] = 'Administrator';
        }
        if (count($parents) > 0) {
            $this->acl->addRole('selected_user', $parents);
        } else {
            $this->acl->addRole('selected_user', 'default_role');
        }

        if (empty($group)) {
            $group = 'default_resource';
        }
        if (!$this->acl->hasResource($resource)) {
            if (!$this->acl->hasResource($group)) {
                return false;
            }
            $resource = $group;
        }

        $this->acl->allow('Administrator', $resource, $action);
        $is_allowed = $this->acl->isAllowed('selected_user', $resource, $action);
        log_message('debug', $s_user->getUsername() . ' is_allowed to ' . $action . ' to resource ' . $resource . ' :: ' . (string)$is_allowed);
        $role_exists = $this->acl->hasRole('selected_user');
        if ($role_exists) {
            $this->acl->removeRole('selected_user');
        }

        return $is_allowed;
    }

    public function add_access_toUser($resource, $action, $user, $group, $resource_type = null) {
        $roleExists = $this->acl->hasRole('selected_user');
        if ($roleExists) {
            $this->acl->removeRole('selected_user');
        }
        if (!$user instanceof models\User) {
            $s_user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user));
            log_message('debug', 's_user not instance of  models\User search by username=' . $user);
        } else {
            $s_user = $user;
        }
        $manageAccess = $this->acl->isAllowed('current_user', $resource, 'manage');
        if (!$manageAccess) {
            log_message('debug', 'user has no rights to mamage permission');

            return false;
        } else {

            log_message('debug', 'user can manage permissions');
        }

        $alreadyHasAccess = $this->check_user_acl($resource, $action, $s_user, $group);

        $resourceExist = false;
        $aclRoleExist = false;
        if (!$alreadyHasAccess) {
            $acl_role = $this->em->getRepository("models\AclRole")->findOneBy(array('type' => 'user', 'name' => $s_user->getUsername()));
            if (empty($acl_role)) {
                log_message('debug', 'no acl_role creating new one...');
                $acl_role = new models\AclRole;
                $acl_role->setName($s_user->getUsername());
                $acl_role->setType('user');
                $acl_role->setDescription('individual role for user ' . $s_user->getUsername());
                $s_user->setRole($acl_role);
            } else {
                $s_user->setRole($acl_role);
                $aclRoleExist = true;
            }
            $this->em->persist($acl_role);

            $acl_group = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $group));
            if (empty($acl_group)) {
                log_message('debug', 'no acl_group called: ' . $group . ' creating one...');
                $acl_group = new models\AclResource;
                $acl_group->setResource($group);
                $acl_group->setDefaultValue('read');
                $this->em->persist($acl_group);
            } else {
                log_message('debug', 'found acl_group called: ' . $group);
            }

            /**
             * @var models\AclResource $aclResource
             */
            $aclResource = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $resource));

            if (empty($aclResource)) {
                log_message('debug', 'not found acl_resource (' . $resource . ')in group');
                $aclResource = new models\AclResource;
                $aclResource->setResource($resource);
                $aclResource->setParent($acl_group);
                $aclResource->setDefaultValue('read');
                $this->em->persist($aclResource);
            } else {
                log_message('debug', 'z found acl_resource (' . $resource . ')in group');
                $resourceExist = true;
            }
            if (($resourceExist && $aclRoleExist) === false) {
                $acl = new models\Acl;
                $acl->setResource($aclResource);
                $acl->setRole($acl_role);
                $acl->setAction($action);
                $acl->setAccess(true);
                $this->em->persist($acl);
                $this->em->flush();

                return true;
            } else {
                /**
                 * @var models\Acl[] $acls
                 */
                $acls = $this->em->getRepository("models\Acl")->findBy(array('resource' => '' . $aclResource->getId() . '', 'role' => '' . $acl_role->getId() . '', 'action' => '' . $action . ''));

                $noAcls = count($acls);
                if ($noAcls === 0) {
                    $acl = new models\Acl;
                    $acl->setResource($aclResource);
                    $acl->setRole($acl_role);
                    $acl->setAction($action);
                    $acl->setAccess(true);
                    $this->em->persist($acl);
                    $this->em->flush();

                    return true;
                } else {
                    $aclToChange = array_pop($acls);
                    $aclToChange->setAccess('1');
                    $this->em->persist($aclToChange);
                    foreach ($acls as $a) {
                        $this->em->remove($a);
                    }
                    $this->em->flush();

                    return true;
                }
            }
        } else {
            return true;
        }
    }

    public function deny_access_fromUser($resource, $action, $user, $group, $resource_type = null) {

        if (!$user instanceof models\User) {
            $s_user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user));
            log_message('debug', 's_user not instance of  models\User search by username=' . $user);
        } else {
            $s_user = $user;
        }
        $manageAccess = $this->acl->isAllowed('current_user', $resource, 'manage');
        if (!$manageAccess) {
            log_message('debug', 'user has no rights to mamage permission');

            return false;
        } else {

            log_message('debug', 'user can manage permissions');
        }
        $alreadyHasAccess = $this->check_user_acl($resource, $action, $s_user, $group);
        if (!$alreadyHasAccess) {
            log_message('debug', 'user already has no access to resource, we dont have to deny it');

            return true;
        }

        $roles = $s_user->getRoles();
        if (count($roles) > 0) {
            log_message('debug', 'number of roles is: ' . count($roles));
            foreach ($roles as $r) {
                $type = $r->getType();
                $role_name = $r->getName();
                log_message('debug', 'check role: ' . $role_name);
                if ($type == 'user' && $role_name == $s_user->getUsername()) {
                    $role = $r;
                    break;
                }
            }
            /* check if user has personal role */
            log_message('debug', 'check if user has personal role');
            if (empty($role)) {
                log_message('debug', 'user has no personal role set');
                $tmp_role = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $s_user->getUsername(), 'type' => 'user'));
                if (!empty($tmp_role)) {
                    log_message('error', 'acl role: ' . $tmp_role->getName() . ' with type \'user\' exists but not linked1 to the user');
                    show_error('Internal error', 500);
                } else {
                    log_message('debug', 'no acl_role creating new one...');
                    $aclRole = new models\AclRole;
                    $aclRole->setName($s_user->getUsername());
                    $aclRole->setType('user');
                    $aclRole->setDescription('individual role for user ' . $s_user->getUsername());
                    $s_user->setRole($aclRole);
                }
            } else {
                $aclRole = $role;
            }
            if (!$aclRole instanceof models\AclRole) {
                log_message('error', '\$role to be expected instance of models\AclRole');
                show_error('Internal server error', 500);
            }
        } else {
            /* user has no roles , we need to set one */
            log_message('debug', 'user ' . $s_user->getUsername() . ' has  no acl_role creating new personal one...');
            $aclRole = new models\AclRole;
            $aclRole->setName($s_user->getUsername());
            $aclRole->setType('user');
            $aclRole->setDescription('individual role for user ' . $s_user->getUsername());
            $s_user->setRole($aclRole);
        }

        /* resource and group */
        /**
         * @var models\AclResource $res
         */
        $res = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $resource, 'type' => $resource_type));
        if (empty($res)) {
            log_message('error', 'reousrce not found');
            show_error('internal server error', 500);
        }

        /**
         * @var models\Acl $acl
         */
        $acl = $this->em->getRepository("models\Acl")->findOneBy(array('resource' => $res->getId(), 'role' => $aclRole->getId(), 'action' => $action));

        if (!empty($acl)) {
            log_message('debug', 'found acl , setting access to deny');
            $acl->setAccess(false);
        } else {
            $acl = new models\Acl;
            $acl->setResource($res);
            $acl->setRole($aclRole);
            $acl->setAction($action);
            $acl->setAccess(false);
            log_message('debug', 'not found acl , creating new acl with  deny access ');
        }
        $this->em->persist($acl);
        $this->em->persist($aclRole);
        $this->em->persist($res);
        $this->em->flush();

        return true;
    }

}
