<?php
namespace models;

/**
 * MailLocalization Model
 * 
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="maillocalization")
 * @author janusz
 */
class MailLocalization {

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @Column(type="string", length=20, nullable=false)
     */
    protected $mgroup;
    
    /**
     * @Column(type="string", length=6, nullable=false)
     */
    protected $lang;
    
    /**
     * @Column(type="text", nullable=false)
     */
    protected $msgbody;
    
    /**
     * @Column(type="string",length=50, nullable=false)
     */
    protected $msgsubject;
    
    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $isdefault;
    
    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $isenabled;
    
    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $alwaysattach;
    
    function __construct()
    {
        $this->alwaysattach = FALSE;
        $this->isenabled = TRUE;
        $this->isdefault = FALSE;
    }
    
    
    public function getId()
    {
        return $this->id;
    }
    public function getGroup()
    {
        return $this->mgroup;
    }
    public function getLanguage()
    {
        return $this->lang;
    }
    public function getSubject()
    {
        return $this->msgsubject;
    }
    public function getBody()
    {
        return $this->msgbody;
    }
    public function isDefault()
    {
        return $this->isdefault;
    }
    public function isEnabled()
    {
        return $this->isenabled;
    }
    public function isAlwaysAttached()
    {
        return $this->alwaysattach;
    }
    
    
    public function setGroup($a)
    {
        $this->mgroup = trim($a);
        return $this;            
    }
    public  function setLanguage($a)
    {
        $this->lang = trim($a);
        return $this;
    }
    public function setSubject($a)
    {
        $this->msgsubject = trim($a);
        return $this;
    }
    public function setBody($a)
    {
        $this->msgbody = trim($a);
        return $this;
    }
    public function setEnabled($bool)
    {
        $this->isenabled = $bool;
        return $this;
    }
    public function setDefault($bool)
    {
        $this->isdefault = $bool;
        return $this;
    }
    public function setAlwaysAttach($bool)
    {
        $this->alwaysattach = $bool;
        return $this;
    }
    

}
