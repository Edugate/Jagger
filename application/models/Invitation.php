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
	log_message('debug','Invitation model initiated');
    }

    private function makeToken()
    {
        $length = 31;

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($p = 0; $p < $length; $p++)
        {

            $string .= $characters[mt_rand(0, (strlen($characters))-1)];
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


    public function getToken()
    {
        return $this->token;
    }



    /**
     * @PrePersist 
     */
    public function doStuffOnPrePersist()
    {
        $this->createdAt = date('Y-m-d H:m:s');
    }


}
