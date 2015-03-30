<?php
namespace models;

/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */
/**
 * MailQueue Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * MailQueue Model
 *
 * This model for Identity and Service Providers definitions
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="mailqueue",indexes={@Index(name="freq_idx", columns={"frequence"}),@Index(name="issent_idx", columns={"issent"})})
 * @author janusz
 */
class MailQueue {

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=10,nullable=false)
     */
    protected $deliverytype;

    /**
     * list of mail addresses seperated by comma
     * @Column(type="string", length=256,nullable=false)
     */
    protected $rcptto;
  
    /**
     * @Column(type="string", length=128, nullable=false)
    */
    protected $msubject;
 

    /**
     * @Column(type="text")
     */
    protected $mbody;

    /**
     * 1,H,D,W,Hp,Dp,Wp
     * @Column(type="string", length=2, nullable=false)
     */ 
    protected $frequence;
 
    /**
     * @Column(name="createdat", type="datetime")
     */
    protected $createdAt;

    /**
     * @Column(name="sentat", type="datetime", nullable=true)
     */
    protected $sentAt;

    /**
     * @Column(name="issent", type="boolean")
     */
    protected $issent;

    function __construct()
    {
        $this->deliverytype = 'mail';
        $this->issent = false;
        $this->frequence = '1';
        $this->createdAt = new \DateTime("now",new \DateTimeZone('UTC'));
    }

    public function getId()
    {
       return $this->id;
    }
    public function getMailToArray()
    {
        $result = array(
          'to'=>$this->rcptto,
          'subject'=>$this->msubject,
          'data'=>''.$this->mbody.''
          );
        return $result;
    }

    public function setRcptto($r)
    {
        $this->rcptto = $r;
        return $this;
    }
    public function  setSubject($s)
    {
        $this->msubject = $s;
        return $this;
    }
    public function setBody($b)
    {
        $this->mbody = $b;
        return $this;
    }

    public function setMailSent()
    {
        $this->issent = TRUE;
        $this->sentAt = new \DateTime("now",new \DateTimeZone('UTC'));
        return $this;
    }

    public function setDeliveryType($type)
    {
         $this->deliverytype = $type;
         return $this;

    }

}
