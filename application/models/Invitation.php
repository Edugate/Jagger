<?php

namespace models;
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
 * Invitation Class
 *
 *  Is not used yet
 *
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Invitation Model
 * @Entity
 * @Table(name="invitation")
 * @HasLifecycleCallbacks
 * @author janusz
 */
class Invitation
{

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=32)
     */
    protected $token;

    /**
     * @Column(type="string", length=32)
     */
    protected $validationkey;

    /**
     * @Column(type="string",length=255,unique=false,nullable=false)
     */
    protected $mailfrom;

    /**
     * @Column(type="string",length=255,unique=false,nullable=false)
     */
    protected $mailto;

    /**
     * @Column(name="created_at", type="string", length=255)
     */
    protected $createdAt;

    /**
     * epoch
     * @Column(name="validto", type="string", length=255)
     */
    protected $validto;

    /**
     * @Column(name="is_valid", type="boolean")
     */
    protected $isValid;

    /**
     * @Column(name="targettype",type="string",length=32)
     */
    protected $targettype;

    /**
     * @Column(name="targetid",type="string",length=32)
     */
    protected $targetid;

    /**
     * @Column(name="actiontype",type="string",length=32)
     */
    protected $actiontype;

    /**
     * @Column(name="actionvalue", type="string",length=32)
     */
    protected $actionvalue;


    public function __construct()
    {
        log_message('debug', 'Invitation model initiated');
        $this->isValid = false;
        $this->validto = null;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getValidationKey(){
        return $this->validationkey;
    }

    public function getTargetType()
    {
        return $this->targettype;
    }

    public function getTargetId()
    {
        return $this->targetid;
    }

    public function getActionType()
    {
        return $this->actiontype;
    }

    public function getActionValue()
    {
        return $this->actionvalue;
    }


    public function isInvitationValid($token, $validationKey)
    {
        $tokenKeyMatch = ($token === $this->token && $validationKey === $this->validationkey);
        $isValid = $this->isValid;
        $isTimeValid = (time() < (int)$this->validto);

        return ($tokenKeyMatch && $isValid && $isTimeValid);
    }

    private function makeToken()
    {
        $length = 31;

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($p = 0; $p < $length; $p++) {

            $string .= $characters[mt_rand(0, (strlen($characters)) - 1)];
        }

        return $string;
    }

    public function setToken()
    {
        $token = $this->makeToken();
        $this->token = $token;

        return $this;
    }

    public function setValidationKey()
    {
        $validkey = $this->makeToken();
        $this->validationkey = $validkey;

        return $this;
    }

    public function setTargetId($target)
    {
        $this->targetid = $target;

        return $this;
    }

    public function setTargetType($type)
    {
        $this->targettype = $type;

        return $this;
    }

    public function setActionValue($action)
    {
        $this->actionvalue = $action;

        return $this;
    }

    public function setActionType($type)
    {
        $this->actiontype = $type;

        return $this;

    }

    public function setValidTo($epoch)
    {
        $this->validto = $epoch;
        return $this;
    }
    public function setInvalid(){
        $this->isValid = false;
        return $this;
    }

    public function setValid()
    {
        $this->isValid = true;
        return $this;
    }

    public function setMailFrom($mail)
    {
        $this->mailfrom = $mail;
        return $this;
    }

    public function setMailTo($mail)
    {
        $this->mailto = $mail;
        return $this;
    }


    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist()
    {
        $this->createdAt = date('Y-m-d H:m:s');
        if ($this->validto === null) {
            $oneDay = time() + (24 * 60 * 60);
            $this->validto = $oneDay;
        }
    }


}
