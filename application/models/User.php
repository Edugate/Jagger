<?php
namespace models;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @package     Jagger
 * @author      Middleware Team HEAnet
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright   2015 HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 */


/**
 * User Model
 * @Entity
 * @Table(name="user")
 */
class User
{

    protected $em;

    /**
     * The User currently logged in
     */
    public static $current;

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=128, unique=true, nullable=false)
     */
    protected $username;

    /**
     * if user federated then password in raw text will be stored NOPASSWORD
     * @Column(type="string", length=64, nullable=false)
     */
    protected $password;

    /**
     * @Column(type="string", length=40, nullable=true)
     */
    protected $salt;

    /**
     * @Column(type="string", length=255, unique=false, nullable=false)
     */
    protected $email;

    /**
     * @Column(type="string",length=255, unique=false, nullable=true)
     */
    protected $givenname;

    /**
     * @Column(type="string",length=255,unique=false, nullable=true)
     */
    protected $surname;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $userpref;

    /**
     * creator of all queue entries
     * @OneToMany(targetEntity="Queue",mappedBy="creator",cascade={"persist", "remove"})
     */
    protected $in_queue;

    /**
     * if local authn allowed
     * @Column(type="boolean")
     */
    protected $local;

    /**
     * if federated allowed
     * @Column(type="boolean")
     */
    protected $federated;

    /**
     * @Column(type="boolean")
     */
    protected $approved;

    /**
     * allow acces or not
     * @Column(type="boolean")
     */
    protected $enabled;

    /**
     *
     * @Column(type="boolean")
     */
    protected $validated;

    /**
     * @ManyToMany(targetEntity="AclRole", inversedBy="members")
     * @JoinTable(name="aclrole_members" )
     */
    protected $roles;

    /**
     * @OneToMany(targetEntity="NotificationList",  mappedBy="subscriber", cascade={"persist", "remove"})
     */
    protected $subscriptions;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $lastlogin;

    /**
     * @Column(type="string",length=64,nullable=true)
     */
    protected $lastip;

    public function __construct()
    {
        log_message('debug', 'User model initiated');
        $this->in_queue = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->local = false;
        $this->federated = false;
    }

    public function setRandomPassword()
    {
        $length = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, (strlen($characters)) - 1)];
        }
        $this->password = self::encryptPassword($string);
        return $this;
    }

    /**
     * Encrypt the password before we store it
     *
     * @access    public
     * @param    string $password
     * @return    void
     */
    public function setPassword($password)
    {
        $this->password = self::encryptPassword($password);
        return $this;
    }

    public function setRawPassword($password)
    {
        $this->password = $password;
        return $this;
    }


    /**
     * Encrypt a Password
     *
     * @static
     * @access    public
     * @param    string $password
     * @return    void
     */
    public function encryptPassword($password)
    {
        $salt = $this->getSalt();
        log_message('debug', 'Model User: encryptPassword: got slat:' . $salt);
        $encrypted_password = sha1($password . $salt);

        return $encrypted_password;
    }

    /**
     * @param $password
     * @return bool
     */
    public function isPasswordMatch($password)
    {
        $encPass = $this->encryptPassword($password);
        if($encPass === $this->password)
        {
            return true;
        }
        return false;
    }

    public function setSalt()
    {
        log_message('debug', 'Model User: setSalt()');
        $length = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[mt_rand(0, (strlen($characters)) - 1)];
        }
        $this->salt = $string;
        log_message('debug', 'Model User: salt:' . $this->salt);

        return $this;
    }

    public function setRole(AclRole $role)
    {
        $already_there = $this->getRoles()->contains($role);
        if (empty($already_there)) {
            $this->getRoles()->add($role);
        }

        return $this;
    }

    public function unsetRole(AclRole $role)
    {
        $alreadyThere = $this->getRoles()->contains($role);
        if ($alreadyThere) {
            $this->getRoles()->removeElement($role);
        }
        return $this;

    }

    /**
     * Authenticate this User by setting self::current to $this
     *
     * @return    User
     */
    public function authenticate()
    {
        self::$current = $this;
        return $this;
    }

    // Begin generic set/get method stubs
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function setGivenname($givenname)
    {
        $this->givenname = $givenname;
        return $this;
    }

    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }

    public function setLocalEnabled()
    {
        $this->local = true;
        return $this;
    }

    public function setLocalDisabled()
    {
        $this->local = false;
        return $this;
    }

    public function setFederatedEnabled()
    {
        $this->federated = true;
        return $this;
    }

    public function setFederatedDisabled()
    {
        $this->federated = false;
        return $this;
    }

    public function genNewValidUser($username, $password, $email, $fname, $sname, $access)
    {
        $this->setSalt();
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
        $this->setGivenname($fname);
        $this->setSurname($sname);
        $this->setAccessType($access);
        $this->setAccepted();
        $this->setEnabled();
        $this->setValid();
        return $this;

    }


    /**
     * @param $type
     * @return $this
     */
    public function setAccessType($type)
    {
        $this->federated = false;
        $this->local = false;
        if ($type === 'both') {
            $this->federated = true;
            $this->local = true;
        } elseif ($type === 'fed') {
            $this->federated = true;
        } elseif ($type === 'local') {
            $this->local = true;
        }
        return $this;
    }

    public function setAccepted()
    {
        $this->approved = true;
        return $this;
    }

    public function setRejected()
    {
        $this->approved = false;
        return $this;
    }

    public function setEnabled()
    {
        $this->enabled = true;
        return $this;
    }

    public function setDisabled()
    {
        $this->enabled = false;
        return $this;
    }

    public function setIP($ip)
    {
        $this->lastip = $ip;
        return $this;
    }

    public function setUserpref(array $pref)
    {
        log_message('debug', 'setUserpref');
        $this->userpref = serialize($pref);
    }

    public function delEntityFromBookmark($id)
    {
        $pref = $this->getUserpref();
        if (empty($pref) || !is_array($pref)) {
            $pref = array();
        } else {
            unset($pref['board']['idp'][$id]);
            unset($pref['board']['sp'][$id]);
        }
        $this->setUserpref($pref);
    }

    public function delFedFromBookmark($id)
    {
        $pref = $this->getUserpref();
        if (empty($pref) || !is_array($pref)) {
            $pref = array();
        } else {
            unset($pref['board']['fed'][$id]);
        }
        $this->setUserpref($pref);
    }

    public function setShowHelp($b)
    {
        $pref = $this->getUserpref();
        if (empty($pref) || !is_array($pref)) {
            $pref = array();
        }
        $pref['showhelp'] = $b;
        $this->setUserpref($pref);
        return $this;

    }

    public function addEntityToBookmark($entid, $entname, $enttype, $entityid)
    {
        log_message('debug', 'addEntityToBookmark');
        $pref = $this->getUserpref();
        if (empty($pref) || !is_array($pref)) {
            $pref = array();
        }
        if ($enttype == 'IDP') {
            log_message('debug', 'addEntityToBookmark : IDP');

            $pref['board']['idp'][$entid] = array('name' => $entname, 'entity' => $entityid);
        } elseif ($enttype == 'SP') {
            log_message('debug', 'addEntityToBookmark : SP');
            $pref['board']['sp'][$entid] = array('name' => $entname, 'entity' => $entityid);
        } else {
            $pref['board']['idp'][$entid] = array('name' => $entname, 'entity' => $entityid);
            $pref['board']['sp'][$entid] = array('name' => $entname, 'entity' => $entityid);
        }
        $this->setUserpref($pref);
    }

    public function addFedToBookmark($fedid, $fedname, $fedencoded)
    {
        log_message('debug', 'addFedToBookmark');
        $pref = $this->getUserpref();
        if (empty($pref) || !is_array($pref)) {
            $pref = array();
        }
        $pref['board']['fed'][$fedid] = array('name' => $fedname, 'url' => $fedencoded);
        $this->setUserpref($pref);
        return $this;
    }

    public function getSecondFactor()
    {
        $pref = $this->getUserpref();
        if (!empty($pref) && is_array($pref)) {
            if (isset($pref['2f']) && !empty($pref['2f'])) {
                return $pref['2f'];
            }
        }
        return null;

    }

    public function setSecondFactor($f = null)
    {
        if (!empty($f) && strcmp($f, 'duo') == 0) {
            $factor = $f;
        } else {
            $factor = null;
        }

        $pref = $this->getUserpref();
        if (empty($pref) || !is_array($pref)) {
            $pref = array();
        }
        $pref['2f'] = $factor;
        $this->setUserpref($pref);
        return $this;


    }

    public function setValid()
    {
        $this->validated = true;
    }

    public function setInvalid()
    {
        $this->validated = false;
    }

    /**
     * @PreUpdate
     */
    public function updated()
    {
        $this->lastlogin = new \DateTime("now", new \DateTimeZone('UTC'));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getFullname()
    {
        $fullname = $this->givenname . " " . $this->surname;
        return $fullname;
    }

    public function getGivenname()
    {
        return $this->givenname;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * getBasic is used by j_auth lib to creta sessiondata
     */
    public function getBasic()
    {
        $data = array(
            'username' => $this->getUsername(),
            'user_id' => $this->getId());
        $userpref = $this->getUserpref();
        if (array_key_exists('showhelp', $userpref) && $userpref['showhelp'] === true) {
            $data['showhelp'] = true;
        } else {
            $data['showhelp'] = false;
        }

        return $data;
    }

    public function getSalt()
    {
        log_message('debug', 'Model:User run getSalt() ');
        return $this->salt;
    }

    public function getUserpref()
    {
        if (!empty($this->userpref)) {
            return unserialize($this->userpref);
        } else {
            return array();
        }
    }

    public function getBookmarks()
    {

        $prefs = $this->getUserpref();
        $board = array('idp' => array(), 'sp' => array(), 'fed' => array());
        if (array_key_exists('board', $prefs)) {
            $board = array_merge($board, $prefs['board']);
        }
        return $board;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getFederated()
    {
        return $this->federated;
    }

    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    public function getIp()
    {
        return $this->lastip;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getRoleNames()
    {
        $result = array();
        $roles = $this->getRoles();
        foreach ($roles as $r) {
            $result[] = $r->getName();
        }
        return $result;
    }

    public function getSystemRoleNames()
    {
        $result = array();
        $roles = $this->getRoles();
        foreach ($roles as $r) {
            $rtype = $r->getType();
            if ($rtype === 'system') {
                $result[] = $r->getName();
            }
        }
        return $result;
    }

    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    public function addSubscription(NotificationList $arg)
    {
        $isin = $this->getSubscriptions()->contains($arg);
        if (empty($isin)) {
            $this->getSubscriptions()->add($arg);
            $arg->setSubscriber($this);
        }
        return $this;
    }


    // End method stubs
}
