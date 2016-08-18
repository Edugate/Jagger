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
 * Contact Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * Contact Model
 *
 * This model for attributes definitions
 * 
 * @Entity
 * @Table(name="contact")
 * @author janusz
 */
class Contact {

    /**
     * @Id
     * @HasLifecycleCallbacks
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $givenname;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $surname;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @Column(type="string", length=64, nullable=false)
     */
    protected $type;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $issirfty;

    /**
     * @Column(type="string", length=24, nullable=true)
     */
    protected $phone;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="contacts")
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     * @JoinTable(name="provider_contacts" )
     */
    protected $provider;


    public function __construct() {
        $this->issirfty = false;
    }

    // Begin generic set/get methods
    public function setFullName($name)
    {
        $this->setSurName($name);
        return $this;
    }

    public function setSurName($name)
    {
        $this->surname = trim($name);
        return $this;
    }

    public function setGivenName($name)
    {
        $this->givenname = trim($name);
        return $this;
    }

    public function setEmail($mail)
    {
        $mail = str_replace('mailto:','',$mail);
        $this->email = trim($mail);
        return $this;
    }

    public function setPhone($phone)
    {
        $this->phone = trim($phone);
        return $this;
    }

    public function setType($type)
    {
        if (($type == 'technical') || ($type == 'administrative') || ($type == 'support') || ($type == 'billing') || ($type == 'other'))
        {
            $this->type = $type;
            return $this;
        }
        else
        {
            return false;
        }
    }

    /**
     * @param $arg
     * @return $this
     */
    public function setSirfti($arg){
        $this->issirfty = $arg;
        return $this;
    }

    public function setProvider(Provider $provider = null)
    {
        $this->provider = $provider;
    }

    public function unsetProvider()
    {
        $this->provider = null;
    }

    /**
     * this object is overwrittent by other contact object
     */
    public function overwriteWith(Contact $contact)
    {
        $this->givenname = $contact->getGivenName();
        $this->surname = $contact->getSurname();
        $this->type = $contact->getType();
        $this->phone = $contact->getPhone();
        $this->email = $contact->getEmail();
        $this->issirfty = $contact->isSirfti();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFullName()
    {
        return $this->givenname . " " . $this->surname;
    }

    public function getGivenName()
    {
        return $this->givenname;
    }

    public function getSurName()
    {
        return $this->surname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTypeToForm(){
        if($this->issirfty){
            return 'other-sirfti';
        }
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isSirfti(){
        return $this->issirfty;
    }

    public function setAllInfoNoProvider($fname,$sname,$type,$mail)
    {
        $this->email = $mail;
        $this->givenname = $fname;

         if($type === 'other-sirfti') {
            $this->type = 'other';
            $this->issirfty = true;
        }
        else {
            $this->type = $type;
        }
        $this->surname = $sname;
        return $this;
    }
    public function setAllInfo($fname,$sname,$type,$mail,Provider $provider)
    {
        $this->email = $mail;
        $this->givenname = $fname;
        if($type === 'other-sirfti') {
            $this->type = 'other';
            $this->issirfty = true;
        }
        else {
            $this->type = $type;
        }
        $this->surname = $sname;
        $this->setProvider($provider);
        $provider->setContact($this);
        return $this;
    }
    public function importFromArray(array $c)
    {
        $this->setGivenName($c['givenname']);
        $this->setSurName($c['surname']);
        $this->setType($c['type']);
        $this->setPhone($c['phone']);
        $this->setEmail($c['email']);
        if(array_key_exists('issirfti')){
            $sirfti = (bool) $c['issirfti'];
            $this->setEmail($sirfti);
        }
        return $this;
    }

    public function convertToArray()
    {
        return array(
            'fullname' => $this->getFullname(),
            'givenname' => $this->getGivenname(),
            'surname' => $this->getSurname(),
            'type' => $this->getType(),
            'phone' =>  $this->getPhone(),
            'email'=> $this->getEmail(),
            'issirfti' => $this->isSirfti()
        );
    }


    /**
     * @PostPersist
    */
    public function verifySirfty(){
        if($this->issirfty){
            $this->type = 'other';
        }
        return $this;
    }

}

