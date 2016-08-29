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
     * Certificate Class
     *
     * @package     RR3
     * @subpackage  Models
     * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
     */


/**
 * Certificate Model
 *
 * This model for attributes definitions
 *
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="certificate")
 * @author janusz
 */
class Certificate
{

    /**
     * @Id
     * @Column(type="bigint", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string",length=12)
     * types like idpsso,spsso,idpaa etc
     */
    protected $type;

    /**
     * @Column(type="string", length=12, nullable=true)
     * KeyDescriptor use: null, signing, encryption
     */
    protected $certusage;

    /**
     * @Column(type="string", length=26, nullable=true)
     * certtype like x509 (X509Certificate)
     */
    protected $certtype;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $certdata;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $encmethods;


    /**
     * @todo add automatic generate subject
     */

    /**
     * @Column(type="string",nullable=true,length=128)
     */
    protected $subject;

    /**
     * @ManyToOne(targetEntity="Provider",inversedBy="certificates")
     * @JoinColumn(name="provider_id", referencedColumnName="id")
     */
    protected $provider;

    /**
     * @Column(type="boolean")
     */
    protected $is_default;

    /**
     * @Column(type="string", nullable=true,length=512)
     */
    protected $keyname;

    public function __construct() {
        $this->is_default = true;
    }

    // Begin generic set/get methods
    public function setCertdata($certdata = null) {

        $this->certdata = trim($certdata);

        return $this;
    }

    public function setKeyname($keyname = null) {
        $this->keyname = str_replace(' ', '', $keyname);

        return $this;
    }

    public function setProvider(Provider $provider) {
        $this->provider = $provider;

        return $this;
    }

    public function unsetProvider(Provider $provider) {
        $this->provider = null;

        return $this;
    }

    /**
     * only null,signing,encryption may be set
     */
    public function setCertUse($use = null) {
        $this->certusage = null;
        if ($use === 'signing' || $use === 'encryption') {
            $this->certusage = $use;
        }

        return $this;
    }


    /**
     * setAsSSO() or setAsAA()
     */
    public function setType($type = null) {
        if (empty($type)) {
            $type = 'sso';
        }
        $this->type = $type;

        return $this;
    }

    public function setAsSPSSO() {
        $this->setType('spsso');

        return $this;
    }

    public function setAsIDPSSO() {
        $this->setType('idpsso');

        return $this;
    }

    public function setCertType($type = null) {
        if (empty($type) || $type === 'x509') {
            $type = 'X509Certificate';
        }
        $this->certtype = $type;

        return $this;
    }

    public function setSubject($sub) {
        $this->subject = $sub;

        return $this;
    }

    /**
     * it's private because preferred calling functions
     * setAsDefault() or setAsNonDefault()
     */
    private function setDefault($bool) {
        $this->is_default = $bool;

        return $this;
    }

    /**
     * set default as true
     */
    public function setAsDefault() {
        $this->setDefault(true);

        return $this;
    }

    public function setEncryptMethods($enc = null) {

        $this->encmethods = null;
        if (!empty($enc) && is_array($enc)) {
            $this->encmethods = serialize(array_unique($enc));
        }

        return $this;

    }

    public function addEncryptionMethod($str) {
        $encmethods = $this->getEncryptMethods();
        $encmethods[] = trim($str);
        $this->setEncryptMethods($encmethods);
        return $this;
    }

    /**
     * set default as false
     */
    public function setAsNonDefault() {
        $bool = false;
        $this->setDefault($bool);

        return $this;
    }


    public function getId() {
        return $this->id;
    }

    public function getType() {
        return $this->type;
    }

    public function getCertUse() {
        return $this->certusage;
    }

    public function getCertUseInStr() {
        if (empty($this->certusage)) {
            return 'both';
        }

        return $this->certusage;
    }

    /**
     * return certificate without BEGIN/END Certificate
     */
    public function getCertDataNoHeaders() {
        $cert = $this->certdata;
        $output = null;
        if (!empty($cert)) {
            if ($this->getCertType() === 'X509Certificate') {
                $output = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
                $output = str_replace('-----END CERTIFICATE-----', '', $output);
            }
        }

        return $output;
    }

    public function getCertDataWithHeaders() {
        $cert = trim($this->certdata);
        $output = null;
        if (!empty($cert)) {
            if ($this->getCertType() === 'X509Certificate') {
                $output = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
                $output = str_replace('-----END CERTIFICATE-----', '', $output);
            }
        }
        $output2 = '-----BEGIN CERTIFICATE-----';
        $output2 .= $output;
        $output2 .= '-----END CERTIFICATE-----';

        return $output2;
    }

    public function getCertData() {
        return $this->certdata;
    }

    public function isDefault() {
        return $this->is_default;
    }

    public function getEncryptMethods() {
        $result = $this->encmethods;
        if ($result !== null) {
            return unserialize($result);
        } else {
            return array();
        }
    }

    public function getKeyname() {
        return $this->keyname;
    }

    public function getCertType() {
        return $this->certtype;
    }

    public function getFingerprint($alg = null) {
        if (empty($alg)) {
            $alg = 'md5';
        }
        $certdata = $this->getCertDataNoHeaders();
        if (empty($certdata)) {
            return null;
        }
        $certdata = trim($certdata);
        $certdata = str_replace(array("\n\r", "\n", "\r"), '', $certdata);
        $bin = base64_decode($certdata);

        return $alg($bin);
    }

    public function getTimeValidTo() {
        $cert = $this->getPEM($this->getCertData());
        if (empty($cert)) {
            return null;
        }
        if ($this->getCertType() === 'X509Certificate') {
            $parsed = openssl_x509_parse($cert);

            return date('Y-m-d H:i:s', $parsed['validTo_time_t']);
        }

        return null;

    }

    /**
     * decide if you want just boolean return or number of days
     */
    public function getTimeValid($type = null) {
        $cert = $this->getPEM($this->getCertData());
        if (empty($cert)) {
            return null;
        }
        if ($this->getCertType() === 'X509Certificate') {
            $parsed = openssl_x509_parse($cert);
            $period = $parsed['validTo_time_t'] - time();
            $days = (int)$period / 60 / 60 / 24;
            $boolreturn = false;
            if ($period > 0) {
                $boolreturn = true;
            }
            if (empty($type)) {
                return $boolreturn;
            } else {
                return $days;
            }
        } else {
            /**
             * @todo check if can be used different encryption methods
             */
            return null;
        }
    }

    /**
     * @prePersist
     */
    public function fixCert() {
        if ($this->certtype === 'X509Certificate') {
            if (!empty($this->certdata)) {
                $i = explode("\n", $this->certdata);
                $c = count($i);
                if ($c < 2) {
                    $pem = chunk_split($this->certdata, 64, "\n");
                    $this->certdata = $pem;
                }
            }
        }

    }

    /**
     * @PreUpdate
     */
    public function updated() {
        if ($this->certtype === 'X509Certificate') {
            if (!empty($this->certdata)) {
                $this->certdata = self::reformatPEM($this->certdata);
            }
        }
    }


    public function importFromArray(array $cdata) {
        $this->setType($cdata['type']);
        $this->setCertUse($cdata['usage']);
        $this->setCertdata($cdata['certdata']);
        $this->setKeyname($cdata['keyname']);
        $this->setCertType($cdata['certtype']);
    }

    public function convertToArray() {
        return array(
            'type'     => $this->getType(),
            'usage'    => $this->getCertUse(),
            'certdata' => $this->getCertData(),
            'keyname'  => $this->getKeyname(),
            'certtype' => $this->getCertType()
        );
    }

    public function getPEM($value, $raw = false) {
        $cleaned_value = preg_replace('#(\\\n)#', "\n", $value);

        $cleaned_value = trim($cleaned_value);

        // Add or remove BEGIN/END lines
        if ($raw) {
            $cleaned_value = preg_replace('-----BEGIN CERTIFICATE-----', '', $cleaned_value);
            $cleaned_value = preg_replace('-----END CERTIFICATE-----', '', $cleaned_value);
            $cleaned_value = trim($cleaned_value);
        } else {
            if (!empty($cleaned_value) && !preg_match('/-----BEGIN CERTIFICATE-----/', $cleaned_value)) {
                $cleaned_value = "-----BEGIN CERTIFICATE-----\n" . $cleaned_value;
            }
            if (!empty($cleaned_value) && !preg_match('/-----END CERTIFICATE-----/', $cleaned_value)) {
                $cleaned_value .= "\n-----END CERTIFICATE-----";
            }
        }

        return $cleaned_value;
    }

    public static function reformatPEM($value) {
        if (!empty($value)) {
            $pattern = array(
                '0' => '/(.*)-----BEGIN CERTIFICATE-----/s',
                '1' => '/-----END CERTIFICATE-----(.*)/s'
            );
            $cleaner = array(
                '0' => '',
                '1' => ''
            );
            $cleaned_value = preg_replace($pattern, $cleaner, $value);
            $cleaned_value = preg_replace("/\r\n/", '', $cleaned_value);
            $cleaned_value = preg_replace("/\n+/", '', $cleaned_value);
            $cleaned_value = preg_replace('/\s\s+/', '', $cleaned_value);
            $cleaned_value = preg_replace('/\s*/', '', $cleaned_value);
            $cleaned_value = trim($cleaned_value);
            $pem = chunk_split($cleaned_value, 64, PHP_EOL);
            $pem = '-----BEGIN CERTIFICATE-----' . PHP_EOL . $pem . '-----END CERTIFICATE-----';

            return $pem;
        }

        return $value;
    }

}

