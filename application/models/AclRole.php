<?php
namespace models;

use \Doctrine\Common\Collections\ArrayCollection;

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
 * Aclrole Class
 *
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Aclrole Model
 *
 * This model for Identity and Service Providers definitions
 *
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="acl_role")
 * @author janusz
 */
class AclRole
{
    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string",length=255)
     */
    protected $name;

    /**
     * @Column(type="string",length=10)
     */
    protected $type;
    /**
     * @OneToMany(targetEntity="Acl",mappedBy="role",cascade={"persist","remove"})
     */
    protected $acls;

    /**
     * @Column(type="string",length=128)
     */
    protected $description;

    /**
     * @ManyToOne(targetEntity="AclRole",inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @OneToMany(targetEntity="AclRole", mappedBy="parent")
     */
    protected $children;

    /**
     * @ManyToMany(targetEntity="User", mappedBy="roles", indexBy="username")
     * @JoinTable(name="aclrole_members" )
     * @OrderBy({"username"="ASC"})
     */
    protected $members;


    public function __construct() {

        $this->acls = new \Doctrine\Common\Collections\ArrayCollection();
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    /**
     * @return AclRole|null
     */
    public function getParent() {
        return $this->parent;
    }

    public function getChildren() {
        return $this->children;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getMembers() {
        return $this->members;
    }

    public function getAcls() {
        return $this->acls;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setMember(User $member) {
        $member->setRole($this);
    }

    /**
     * type only may be: group,user
     */
    public function setType($type) {
        $this->type = $type;
    }

    public function setParent(AclRole $parent = null) {
        $this->parent = $parent;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setAcl($acl) {
        $this->getAcls()->add($acl);
    }
}
