<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

use \Doctrine\ORM\Query\ResultSetMapping;
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
 * Zacl Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class Zacl {

    // Set the instance variable
    var $ci;
    protected $mid;

    function __construct() {
        // Get the instance
        $this->ci = & get_instance();
        $this->mid = $this->ci->mid;
        $this->em = $this->ci->doctrine->em;

        // Set the include path and require the needed files
        set_include_path(get_include_path() . PATH_SEPARATOR . FCPATH . "application/libraries");
        require_once(APPPATH . '/libraries/Zend/Acl.php');
        require_once(APPPATH . '/libraries/Zend/Acl/Role.php');
        require_once(APPPATH . '/libraries/Zend/Acl/Resource.php');
        $this->acl = new Zend_Acl();
        $this->acl->addRole(new Zend_Acl_Role('default_role'));

        /**
         * get  roles
         */
        //$defined_roles = $this->em->getRepository("models\AclRole")->findAll();
        
        if (ENVIRONMENT == 'production') 
        {
            $query = $this->em->createQuery('SELECT u FROM models\AclRole u');
            $query->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache());
            $query->useResultCache(true)
                  ->setResultCacheLifeTime($seconds = 60);
            $query->setResultCacheId('aclroles');
            $defined_roles = $query->getResult();
        }
        else
        {
             $defined_roles = $this->em->getRepository("models\AclRole")->findAll();
        }
        
        /**
         * end get roles
         */

        $roleArray = array();
        foreach ($defined_roles as $r) {
            $role = new Zend_Acl_Role($r->getName());
            $parent = $r->getParent();
            if ($parent !== null) {
                $this->acl->addRole($role, $r->getParent()->getName());
            } else {
                $this->acl->addRole($role, 'default_role');
            }
            $roleArray[$r->getId()] = $role;
        }
        $user_roles = null;


        if (!empty($_SESSION['username'])) {
            $current = $this->em->getRepository("models\User")->findOneBy(array('username' => $_SESSION['username']));
        }
        if (!empty($current) && $current instanceof models\User) {
            $user_roles = $current->getRoles();
        }
        $my_roles_array = array('system' => array(), 'group' => array(), 'user' => array());
        if (!empty($user_roles)) {
            foreach ($user_roles as $p) {
                $type = $p->getType();
                $my_roles_array[$type][] = $p->getName();
            }
        }
        $parents = array();
        foreach ($my_roles_array['user'] as $k) {
            $parents[] = $k;
        }
        foreach ($my_roles_array['group'] as $k) {
            $parents[] = $k;
        }
        foreach ($my_roles_array['system'] as $k) {
            $parents[] = $k;
        }
        $n = count($parents);
        if ($n > 0) {
            $this->acl->addRole('current_user', $parents);
        } else {
            $this->acl->addRole('current_user', 'default_role');
        }
        //$this->acl->allow('Member', null, 'view');
        //$this->acl->allow('Guest', null, 'view');

        $this->acl->addResource(new Zend_Acl_Resource('root_resource'));
        //$this->acl->allow('Member', null, 'view');

        $defined_resources = $this->em->getRepository("models\AclResource")->findAll();
        foreach ($defined_resources as $res) {
            $resource = new Zend_Acl_Resource($res->getResource());
            $r_parent = $res->getParent();
            if ($r_parent !== null) {
                $this->acl->addResource($resource, $r_parent->getResource());
            } else {
                $this->acl->addResource($resource, 'root_resource');
            }
            $default_access = $res->getDefaultValue();
            if ($default_access == "none" or $default_access == "0") {
                //$this->acl->deny(null,$resource,null);
            } else {
                //$this->acl->allow(null,$resource,$res->getDefaultValue());
            }
        }
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
#        echo "<pre>";
#        print_r($this->acl);
#        echo "</pre>";
    }

    function check_acl($resource, $action, $group = '', $role = '') {
        if (empty($role)) {
            $role = 'current_user';
        }
        if (empty($group)) {
            $group = 'default_resource';
        }
        if (!$this->acl->has($resource)) {
            if (!$this->acl->has($group)) {
                return false;
            }
            $resource = $group;
        }

        $this->acl->allow('Administrator', $resource, $action);
        return $this->acl->isAllowed($role, $resource, $action);
    }
    public function check_acl_for_user($resource, $action, $user, $group)
    {
        $access = $this->check_user_acl($resource, $action, $user, $group);
        $role_exists = $this->acl->hasRole('selected_user');
        if($role_exists)
        {
                $this->acl->removeRole('selected_user');
        }
        return $access;

    }
    private function check_user_acl($resource, $action, $user, $group) {
        $s_user = new models\User;
        if (!$user instanceof models\User) {
            $s_user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user));
        } else {
            $s_user = &$user;
        }
        if (!empty($s_user)) {
            $user_roles = $s_user->getRoles();
        }
        $my_roles_array = array('system' => array(), 'group' => array(), 'user' => array());
        if (!empty($user_roles)) {
            foreach ($user_roles as $p) {
                $type = $p->getType();
                $my_roles_array[$type][] = $p->getName();
            }
        }
        $parents = array();
        foreach ($my_roles_array['user'] as $k) {
            $parents[] = $k;
        }
        foreach ($my_roles_array['group'] as $k) {
            $parents[] = $k;
        }
        foreach ($my_roles_array['system'] as $k) {
            $parents[] = $k;
        }
        $n = count($parents);
        if ($n > 0) {
            $this->acl->addRole('selected_user', $parents);
        } else {
            $this->acl->addRole('selected_user', 'default_role');
        }

        if (empty($group)) {
            $group = 'default_resource';
        }
        if (!$this->acl->has($resource)) {
            if (!$this->acl->has($group)) {
                return false;
            }
            $resource = $group;
        }

        $this->acl->allow('Administrator', $resource, $action);
        $is_allowed = $this->acl->isAllowed('selected_user', $resource, $action);


        log_message('debug', $this->mid . $s_user->getUsername() . " is_allowed to " . $action . ' to resource ' . $resource . ' :: ' . (string) $is_allowed);
        $role_exists = $this->acl->hasRole('selected_user');
        if($role_exists)
        {
                $this->acl->removeRole('selected_user');
        }
        return $is_allowed;
    }

    public function add_access_toUser($resource, $action, $user, $group,$resource_type=null) {
        $role_exists = $this->acl->hasRole('selected_user');
        if($role_exists)
        {
                $this->acl->removeRole('selected_user');
        }
        if (!$user instanceof models\User) {
            $s_user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user));
            log_message('debug', $this->mid . 's_user not instance of  models\User search by username=' . $user);
        } else {
            $s_user = &$user;
        }
        $can_manage = $this->acl->isAllowed('current_user', $resource, 'manage');
        if (!$can_manage) {
            log_message('debug', $this->mid . 'user has no rights to mamage permission');
            return false;
        } else {

            log_message('debug', $this->mid . 'user can manage permissions');
        }
        $already_has_access = $this->check_user_acl($resource, $action, $s_user, $group);

        if (!$already_has_access) {
            log_message('debug', $this->mid . 'no access - creating....');
            $acl_role = $this->em->getRepository("models\AclRole")->findOneBy(array('type' => 'user', 'name' => $s_user->getUsername()));
            if (empty($acl_role)) {
                log_message('debug',$this->mid.'no acl_role creating new one...');
                $acl_role = new models\AclRole;
                $acl_role->setName($s_user->getUsername());
                $acl_role->setType('user');
                $acl_role->setDescription('individual role for user ' . $s_user->getUsername());
                $s_user->setRole($acl_role);
            }
            else
            {
                $s_user->setRole($acl_role);
            }
                $this->em->persist($acl_role);

            $acl_group = $this->em->getRepository("models\AclResource")->findOneBy(array('resource' => $group));
            if (empty($acl_group)) {
                log_message('debug',$this->mid.'no acl_group called: '.$group.' creating one...');
                $acl_group = new models\AclResource;
                $acl_group->setResource($group);
                $acl_group->setDefaultValue('read');
                $this->em->persist($acl_group);
            }
            else
            {
                log_message('debug',$this->mid.'found acl_group called: '.$group);   
            }
            $acl_children = $acl_group->getChildren();
            foreach ($acl_children as $c) {
                $r = $c->getResource();
                if ($r == $resource) {
                    $acl_resource = $c;
                    break;
                }
            }
            if (empty($acl_resource)) {
                log_message('debug',$this->mid.'not found acl_resource ('.$resource.')in group');
                $acl_resource = new models\AclResource;
                $acl_resource->setResource($resource);
                $acl_resource->setParent($acl_group);
                $acl_resource->setDefaultValue('read');
                $this->em->persist($acl_resource);
            }
            else
            {
                log_message('debug',$this->mid.'z found acl_resource ('.$resource.')in group');
                
            }

            $acl = new models\Acl;
            $acl->setResource($acl_resource);
            $acl->setRole($acl_role);
            $acl->setAction($action);
            $acl->setAccess(true);
            $this->em->persist($acl);
            $this->em->flush();
            return true;
        } else {
            return true;
        }
    }


    public function deny_access_fromUser($resource,$action,$user,$group,$resource_type=null)
    {
        
        if (!$user instanceof models\User) {
            $s_user = $this->em->getRepository("models\User")->findOneBy(array('username' => $user));
            log_message('debug', $this->mid . 's_user not instance of  models\User search by username=' . $user);
        } else {
            $s_user = &$user;
        }
        $can_manage = $this->acl->isAllowed('current_user', $resource, 'manage');
        if (!$can_manage) {
            log_message('debug', $this->mid . 'user has no rights to mamage permission');
            return false;
        } else {

            log_message('debug', $this->mid . 'user can manage permissions');
        }
        $already_has_access = $this->check_user_acl($resource, $action, $s_user, $group);
        if (!$already_has_access) {
                log_message('debug', $this->mid . 'user already has no access to resource, we dont have to deny it');
                return true;
        }

        $roles = $s_user->getRoles();
        $no_roles = count($roles);
        if($no_roles > 0)
        {
             log_message('debug',$this->mid.'number of roles is: '.$no_roles);
             foreach($roles as $r)
             {
                $type=$r->getType();
                $role_name = $r->getName();
                log_message('debug',$this->mid.'check role: '.$role_name);
                if($type == 'user' && $role_name == $s_user->getUsername())
                {
                   $role = $r;
                   break;
                }
             }
             /* check if user has personal role */
             log_message('debug',$this->mid.'check if user has personal role');
             if(empty($role))
             {
                log_message('debug',$this->mid.'user has no personal role set');
                $tmp_role = $this->em->getRepository("models\AclRole")->findOneBy(array('name'=>$s_user->getUsername(),'type'=>'user'));
                if(!empty($tmp_role))
                {
                        log_message('error',$this->mid.'acl role: '.$tmp_role->getName().' with type \'user\' exists but not linked1 to the user' );
                        show_error($this->mid.'Internal error',500);
                        
                }
                else
                {
                        log_message('debug',$this->mid.'no acl_role creating new one...');
                        $acl_role = new models\AclRole;
                        $acl_role->setName($s_user->getUsername());
                        $acl_role->setType('user');
                        $acl_role->setDescription('individual role for user ' . $s_user->getUsername());
                        $s_user->setRole($acl_role);

                
                
                }

             }
             else
             {
                        $acl_role = $role; 
             }
             if(! $acl_role instanceof models\AclRole)
             {
                log_message('error',$this->mid.'\$role to be expected instance of models\AclRole');
                show_error($this->mid.'Internal server error',500);
                
             }


        }
        else
        {
                /* user has no roles , we need to set one */
                log_message('debug',$this->mid.'user '.$s_user->getUsername().' has  no acl_role creating new personal one...');
                $acl_role = models\AclRole;
                $acl_role->setName($s_user->getUsername());
                $acl_role->setType('user');
                $acl_role->setDescription('individual role for user ' . $s_user->getUsername());
                $s_user->setRole($acl_role);

        }

        /* resource and group */
        $res = $this->em->getRepository("models\AclResource")->findOneBy(array('resource'=>$resource,'type'=>$resource_type));
        if(empty($res))
        {
                log_message('error','reousrce not found');
                show_error('internal server error',500);
        }

        $acl = $this->em->getRepository("models\Acl")->findOneBy(array('resource'=>$res->getId(),'role'=>$acl_role->getId(),'action'=>$action,'access'=>true));

        if(!empty($acl))
        {
                log_message('debug',$this->mid.'found acl , setting access to deny');
                $acl->setAccess(false);
        }
        else
        {
                $acl = new models\Acl;
                $acl->setResource($res);
                $acl->setRole($acl_role);
                $acl->setAction($action);
                $acl->setAccess(false);
                log_message('debug',$this->mid.'not found acl , creating new acl with  deny access ');
        }
        $this->em->persist($acl);
        $this->em->persist($acl_role);
        $this->em->persist($res);
        $this->em->flush();
        return true;









    }

}
