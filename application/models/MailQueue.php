<?php
namespace models;

use \Doctrine\Common\Collections\ArrayCollection;

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
 * @Table(name="mailqueue")
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
     * list of mail addresses seperated by comma
     * @Column(type="string", length=256,nullable=false)
     */
    protected $rcptto;
  
    /**
     * @Column(type="string", length=40, nullable=false)
    */
    protected $msubject;
 

    /**
     * @Column(type="text")
     */
    protected $mbody;

    /**
     * 1,H,D,W
     * @Column(type="string", length=5, nullable=false)
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
        $this->issent = false;
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

}
