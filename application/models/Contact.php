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
     * @Column(type="string", length=24, nullable=true)
     */
    protected $phone;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="contacts")
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     * @JoinTable(name="provider_contacts" )
     */
    protected $provider;

    // Begin generic set/get methods
    public function setFullName($name)
    {
        $this->setSurName($name);
        return $this;
    }

    public function setSurName($name)
    {
        $this->surname = $name;
        return $this;
    }

    public function setGivenName($name)
    {
        $this->givenname = $name;
        return $this;
    }

    public function setEmail($mail)
    {
        $mail = str_replace('mailto:','',$mail);
        $this->email = $mail;
        return $this;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    public function setType($type)
    {
        if (($type == 'technical') OR ($type == 'administrative') OR ($type == 'support') OR ($type == 'billing') OR ($type == 'other'))
        {
            $this->type = $type;
            return $this;
        }
        else
        {
            return false;
        }
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
        $this->givenname = $contact->getGivenname();
        $this->surname = $contact->getSurname();
        $this->type = $contact->getType();
        $this->phone = $contact->getPhone();
        $this->email = $this->getEmail();
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

    public function importFromArray(array $c)
    {
        $this->setGivenName($c['givenname']);
        $this->setSurName($c['surname']);
        $this->setType($c['type']);
        $this->setPhone($c['phone']);
        $this->setEmail($c['email']);
    }

    public function convertToArray()
    {
        $c = array();
        $c['fullname'] = $this->getFullname();
        $c['givenname'] = $this->getGivenname();
        $c['surname'] = $this->getSurname();
        $c['type'] = $this->getType();
        $c['phone'] = $this->getPhone();
        $c['email'] = $this->getEmail();
        return $c;
    }

    public function getContactToXML(\DOMElement $parent)
    {
        $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ContactPerson');
        $e->setAttribute('contactType', $this->type);
        $sn = trim($this->surname);
        $fn = trim($this->givenname);
        if (empty($fn))
        {
            if (!empty($sn))
            {
                $sn_array = explode(" ", $sn);
                $fn = $sn_array[0];
                if (count($sn_array) > 1)
                {
                    unset($sn_array[0]);
                    $sn = implode(" ", $sn_array);
                }
            }
            else
            {
                $sn="unknown";
                $fn="unknown";
            }
        }
        if(empty($sn))
        {
              $sn = "unknown";
        }
 
        $Contact_GivenName_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:GivenName', str_replace("@","(at)",$fn));
        $e->appendChild($Contact_GivenName_Node);
 
        $Contact_Surname_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:SurName', str_replace("@","(at)",$sn));
        $e->appendChild($Contact_Surname_Node);

        $Contact_Email_Node = $e->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EmailAddress', $this->email);
        $e->appendChild($Contact_Email_Node);
        return $e;
    }

    public function temp_getContactToXML(\DOMElement $parent)
    {
        $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:ContactPerson');
        return $e;
    }

}

