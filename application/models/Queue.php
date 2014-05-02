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
 * Queue Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Queue Model
 *
 *
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="queue",indexes={@index(name="search_idx", columns={"creator", "token"})})
 * @author janusz
 */
class Queue {

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * @Column(type="string")
     */
    protected $action;

     /**
      * ir can be value of: userid,provider id,federation id,contact id
      * @Column(type="string", length=255, nullable=true)
      */
     protected $recipient;

     /**
      * it can be provider,federation,contact,user
      * @Column(type="string", length=20, nullable=true)
      */
     protected $recipienttype;

    /**
     * it defines many types of requirest:
     * SP - for ServiceProvider object
     * IDP - for IdentityProvider object
     * **********************************
     * @Column(type="string")
     */
    protected $type;

    /**
     * @Column(type="text")
     */
    protected $objdata;

    /**
     * @Column(type="string", length=20)
     */
    protected $objtype;

    /**
     * @ManyToOne(targetEntity="User",inversedBy="in_queue")
     * @JoinColumn(name="creator", referencedColumnName="id")
     */
    protected $creator;

    /**
     * @Column(type="string", length=255)
     */
    protected $email;

    /**
     * @Column(type="string", length=32)
     */
    protected $token;

    /**
     * @Column(name="is_confirmed",type="boolean")
     */
    private $is_confirmed;

    /**
     * @Column(name="created_at", type="string", length=255)
     */
    private $createdAt;

    public function __construct() {
        $this->is_confirmed = false;
    }

    /**
     * @PrePersist 
     */
    public function doStuffOnPrePersist() {
        $this->createdAt = date('Y-m-d H:m:s');
    }

    public function inviteProvider($obj)
    {
         $this->action = 'Join';
         $this->type = 'Federation';
         $this->objdata = serialize($obj);
         $this->objtype = 'Federation';
    }
    public function leaveProvider($obj)
    {
         $this->action = 'Leave';
         $this->type = 'Federation';
         $this->objdata = serialize($obj);
         $this->objtype = 'Federation';
    }
    
    public function inviteFederation($obj)
    {
         $this->action = 'Join';
         $this->type = 'Provider';
         $this->objdata = serialize($obj);
         $this->objtype = 'Provider';
    }

    public function addSP($obj) {
        $this->objdata = serialize($obj);
        $this->type = "SP";
        $this->objtype = "Provider";
    }

    public function addUser($obj)
    {
        $this->objdata = serialize($obj);
        $this->type = "User";
        $this->objtype = "User";
    }

    public function addIDP($obj) {
        $this->objdata = serialize($obj);
        $this->type = "IDP";
        $this->objtype = "Provider";
    }

    public function addFederation($obj) {
        $this->objdata = serialize($obj);
        $this->type = 'Federation';
        $this->objtype = "Federation";
        return $this;
    }

    public function setCreator(User $username) {
        $this->creator = $username;
        return $this;
    }

    /**
     * action should be one of:
     * Create, Modify
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }
    public function setObjectType($t)
    {
        $this->objtype = $t;
        return $this;
    }

    public function setType($t)
    {
        $this->type = $t;
        return $this;

     }
    
    public function setObject(array $obj)
    {
        $this->objdata = serialize($obj);
        return $this;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        
    }
    public function setRecipientType($type)
    {
       $this->recipienttype = $type;
    }

    public function setEmail($mail) {
        $this->email = $mail;
        return $this;
    }

    public function setToken() {
        $token = $this->makeToken();
        $this->token = $token;
        return $this;
    }

    public function setConfirm($confirmation) {
        $this->is_confirmed = $confirmation;
        return $this;
    }

    private function makeToken() {
        $length = 31;

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($p = 0; $p < $length; $p++) {

            $string .= $characters[mt_rand(0, (strlen($characters)) - 1)];
        }

        return $string;
    }

    public function getToken() {
        return $this->token;
    }

    public function getID() {
        return $this->id;
    }

    public function getCreator() {
        return $this->creator;
    }
    public function getRecipient()
    {
        return $this->recipient;
    }
    public function getRecipientType()
    {
        return $this->recipienttype;
    }

    public function getData() {
        return unserialize($this->objdata);
    }

    public function getName() {
        return $this->name;
    }

    public function getObjType() {
        return $this->objtype;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getType() {
        return $this->type;
    }

    public function getAction() {
        return $this->action;
    }

    public function getConfirm() {
        return $this->is_confirmed;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

}
