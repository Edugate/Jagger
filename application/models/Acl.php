<?php

namespace models;

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Acl Model
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="acl")
 * @author janusz
 */
class Acl
{

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @ManyToOne(targetEntity="AclResource",inversedBy="acls")
     * @JoinColumn(name="resource_id",referencedColumnName="id")
     */
    protected $resource;


    /**
     * @ManyToOne(targetEntity="AclRole",inversedBy="acls")
     * @JoinColumn(name="role_id",referencedColumnName="id")
     */
    protected $role;

    /**
     * @Column(type="string",length=10)
     */
    protected $action;
    /**
     * @Column(type="boolean")
     */
    protected $access;


    public function getId() {
        return $this->id;
    }

    public function getAction() {
        return $this->action;
    }

    /**
     * @return AclRole
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * @return AclResource
     */
    public function getResource() {
        return $this->resource;
    }

    public function getAccess() {
        return $this->access;
    }


    public function setResource($resource) {
        $this->resource = $resource;
    }

    public function setRole($role) {
        $this->role = $role;
    }

    public function setAccess($access) {
        $this->access = $access;
    }

    public function setAction($action) {
        $this->action = $action;
    }


}
