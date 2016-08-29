<?php
namespace models;


    /**
     * @package     Jagger
     * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
     * @author      Middleware Team HEAnet
     * @copyright   2016, HEAnet Limited (http://www.heanet.ie)
     * @license     MIT http://www.opensource.org/licenses/mit-license.php
     */

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="coc")
 */
class Coc
{

    /**
     * @Id
     * @Column(type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255, nullable=false, unique=false)
     */
    protected $name;

    /**
     * allowed types: entcat (entity category)
     * @Column(type="string", length=7, nullable=true)
     */
    protected $type;

    /**
     * allowed subtypes: for entcat: http://macedir.org/entity-category-support, http://macedir.org/entity-category
     * @Column(type="string", length=128, nullable=true)
     */
    protected $subtype;

    /**
     * @Column(type="string", length=512, nullable=false )
     */
    protected $url;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $cdescription;

    /**
     * @Column(type="boolean", nullable=false)
     */
    protected $is_enabled;

    /**
     * @Column(type="string",length=6,nullable=true)
     */
    protected $lang;

    /**
     * @ManyToMany(targetEntity="Provider",mappedBy="coc",fetch="EXTRA_LAZY")
     */
    protected $provider;

    /**
     * allowed values: idp, sp, both
     * @Column(type="string", length=5, nullable = true)
     */
    protected $availfor;

    public function __construct() {
        $this->is_enabled = false;
        $this->type = 'entcat';
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return trim($this->name);
    }

    public function getType() {
        return $this->type;
    }

    public function getSubtype() {
        return $this->subtype;

    }

    public function getUrl() {
        return trim($this->url);
    }

    public function getAvailable() {
        return (boolean)$this->is_enabled;
    }

    public function getDescription() {
        return $this->cdescription;
    }

    public function getLang() {
        return $this->lang;
    }

    public function getProviders() {
        return $this->provider;
    }

    public function getProvidersCount() {
        return $this->provider->count();
    }

    public function getAvailFor() {
        return $this->availfor;
    }

    /**
     * @param string $entType
     * @return bool
     */
    public function isAvailForEntType($entType) {
        $type = strtolower($entType);
        if ($type === 'both' || $this->availfor === 'both' || $this->availfor === null) {
            return true;
        }

        return $type === $this->availfor;
    }

    public function setName($name) {
        $this->name = trim($name);

        return $this;
    }

    /**
     * allowed types: entcat, regpol
     * @param string $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    public function setSubtype($subtype) {
        $this->subtype = $subtype;

        return $this;
    }

    public function setUrl($url) {
        $this->url = trim($url);

        return $this;
    }

    public function setDescription($desc) {
        $this->cdescription = trim($desc);

        return $this;
    }

    public function setLang($lang) {
        $this->lang = trim($lang);

        return $this;
    }

    public function setProvider($provider) {
        $this->getProviders()->add($provider);

        return $this;
    }

    public function setAvailable($a = null) {
        $this->is_enabled = false;
        if ($a === true) {
            $this->is_enabled = true;
        }

        return $this;
    }

    public function setAvailFor($type) {
        if (in_array($type, array('idp', 'sp', 'both'), true)) {
            $this->availfor = $type;
        }

        return $this;
    }


    public function setEntityCategory($name, $url, $subtype, $description, $isavailable) {
        $this->name = trim($name);
        $this->url = trim($url);
        $this->subtype = trim($subtype);
        $this->cdescription = trim($description);
        $this->is_enabled = $isavailable;
        $this->type = 'entcat';

        return $this;
    }

}
