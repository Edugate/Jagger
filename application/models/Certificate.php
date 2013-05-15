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
     * @Column(type="string",length=77,nullable=true)
     */
    protected $fingerprint;

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

    public function __construct()
    {
        $this->is_default = TRUE;
    }

    // Begin generic set/get methods
    public function setCertdata($certdata = null)
    {
        
        //      $certdata = preg_replace(array('/\s{2,}/', '/[\t\n]/'), '', $certdata);
        $this->certdata = trim($certdata);
        return $this;
    }

    public function setKeyname($keyname = null)
    {
        $this->keyname = str_replace(' ','',$keyname);
        return $this;
    }

    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
        return $this;
    }

    public function unsetProvider(Provider $provider)
    {
        $this->provider = null;
        return $this;
    }

    /**
     * only null,signing,encryption may be set
     */
    public function setCertUse($use = NULL)
    {
        if (!empty($use) && ($use == 'signing' OR $use == 'encryption'))
        {
            $this->certusage = $use;
        } else
        {
            $this->certusage = NULL;
        }
        return $this;
    }

    /**
     * setAsSSO() or setAsAA()
     */
    public function setType($type = null)
    {
        if (empty($type))
        {
            $type = 'sso';
        }
        $this->type = $type;
        return $this;
    }
    public function setAsSPSSO()
    {
       $this->setType('spsso');
       return $this;
    }
    public function setAsIDPSSO()
    {
       $this->setType('idpsso');
       return $this;
    }

    public function setAsSSO()
    {
        $this->setType('sso');
        return $this;
    }

    public function setAsAA()
    {
        $this->setType('aa');
        return $this;
    }

    public function setCertType($type=null)
    {
        if (empty($type) or $type == 'x509')
        {
            $type = 'X509Certificate';
        }
        $this->certtype = $type;
        return $this;
    }

    public function setSubject($sub)
    {
        $this->subject = $sub;
        return $this;
    }

    /**
     * it's private because preferred calling functions 
     * setAsDefault() or setAsNonDefault()
     */
    private function setDefault($bool)
    {
        $this->is_default = $bool;
        return $this;
    }

    /**
     * set default as true
     */
    public function setAsDefault()
    {
        $bool = TRUE;
        $this->setDefault($bool);
        return $this;
    }

    /**
     * set default as false
     */
    public function setAsNonDefault()
    {
        $bool = FALSE;
        $this->setDefault($bool);
        return $this;
    }

    public function setFingerprint($fingerprint)
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    public function generateFingerprint()
    {
        $cert = $this->getPEM($this->certdata);
        if (!empty($cert))
        {
            if ($this->getCertType() === 'X509Certificate')
            {
                $resource = openssl_x509_read($cert);
                $fingerprint = null;
                $output = null;
                $result = openssl_x509_export($resource, $output);
                if ($result !== false)
                {
                    $output = str_replace('-----BEGIN CERTIFICATE-----', '', $output);
                    $output = str_replace('-----END CERTIFICATE-----', '', $output);
                    $output = base64_decode($output);
                    $fingerprint = sha1($output);
                }
                $this->setFingerprint($fingerprint);
            }
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCertUse()
    {
        return $this->certusage;
    }

    /**
     * return certificate without BEGIN/END Certificate
     */
    public function getCertDataNoHeaders()
    {
        $cert = $this->certdata;
        $output = null;
        if (!empty($cert))
        {
            if ($this->getCertType() == 'X509Certificate')
            {
                $output = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
                $output = str_replace('-----END CERTIFICATE-----', '', $output);
            }
        }
        return $output;
    }

    public function getCertDataWithHeaders()
    {
        $cert = $this->certdata;
        $output = null;
        if (!empty($cert))
        {
            if ($this->getCertType() == 'X509Certificate')
            {
                $output = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
                $output = str_replace('-----END CERTIFICATE-----', '', $output);
            }
        }
        $output2 = '-----BEGIN CERTIFICATE-----';
        $output2 .= $output;
        $output2 .='-----END CERTIFICATE-----';
        return $output2;
    }

    public function getCertData()
    {
        return $this->certdata;
    }

    public function getKeyname()
    {
        return $this->keyname;
    }

    public function getCertType()
    {
        return $this->certtype;
    }

    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    public function getTimeValidTo()
    {
        $cert = $this->getPEM($this->getCertData());
        if (empty($cert))
        {
            return null;
        }
        if ($this->getCertType() == 'X509Certificate')
        {
            $parsed = openssl_x509_parse($cert);
            //$validFrom = date('Y-m-d H:i:s', $parsed['validFrom_time_t']);
            $validTo = date('Y-m-d H:i:s', $parsed['validTo_time_t']);
            return $validTo;
        } else
        {
            return null;
        }
    }

    /**
     * decide if you want just boolean return or number of days
     */
    public function getTimeValid($type = null)
    {
        $cert = $this->getPEM($this->getCertData());
        if (empty($cert))
        {
            return null;
        }
        if ($this->getCertType() == 'X509Certificate')
        {
            $parsed = openssl_x509_parse($cert);
            $validFrom = date('Y-m-d H:i:s', $parsed['validFrom_time_t']);
            $validTo = date('Y-m-d H:i:s', $parsed['validTo_time_t']);
            $now = date('Y-m-d H:i:s', time());
            $period = $parsed['validTo_time_t'] - time();
            $days = (int) $period / 60 / 60 / 24;

            if ($period > 0)
            {
                $boolreturn = true;
            } else
            {
                $boolreturn = false;
            }
            if (empty($type))
            {
                return $boolreturn;
            } else
            {
                return $days;
            }
        } else
        {
            /**
             * @todo check if can be used different encryption methods
             */
            return null;
        }
    }
    /**
     * @prePersist
     */
     public function fixCert()
     {
         log_message('debug','PERSIST CERT');
         if($this->certtype == 'X509Certificate')
         {
              if(!empty($this->certdata))
              {
                 $i = explode("\n",$this->certdata);
                 $c = count($i);
                 if($c < 2)
                 {
                      $pem = chunk_split($this->certdata, 64, "\n");
                      $this->certdata = $pem;
                 }
              }
         }

     }

    /**
     * @PreUpdate
     */
    public function updated()
    {
         log_message('debug','UPDATE CERT');
         if($this->certtype == 'X509Certificate')
         {
              if(!empty($this->certdata))
              {
                 $this->certdata = reformatPEM($this->certdata);
              }
         }
        $this->generateFingerprint();
    }

   
    public function importFromArray(array $c)
    {
         $this->setType($c['type']);
         $this->setCertUse($c['usage']);
         $this->setCertData($c['certdata']);
         $this->setKeyname($c['keyname']);
         $this->setCertType($c['certtype']);
    }

    public function convertToArray()
    {
         $c = array();
         $c['type'] = $this->getType();
         $c['usage'] = $this->getCertUse();
         $c['certdata'] = $this->getCertData();
         $c['keyname'] = $this->getKeyname();
         $c['certtype'] = $this->getCertType();
         return $c;
 
    }
    public function getCertificateToXML(\DOMElement $parent)
    {
        $e = $parent->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:KeyDescriptor');
        /**
         * usage null/singing/encrypting
         */
        //$use = $this->getCertUse();

        $certbody = $this->getCertDataNoHeaders();
         
        if(empty($this->keyname) && empty($certbody))
        {
           return null;
        }
        if (!empty($this->certusage))
        {
            $e->setAttribute('use', $this->certusage);
        }
        $KeyInfo_Node = $e->ownerDocument->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
        if (!empty($this->keyname))
        {
            $keynames = explode(',',$this->keyname);
            foreach($keynames as $v)
            {
                $KeyName_Node = $KeyInfo_Node->ownerDocument->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyName', $v);
                $KeyInfo_Node->appendChild($KeyName_Node);
            }
        }
        if ($this->getCertType() === 'X509Certificate')
        {
            if(!empty($certbody))
            {
               $CertType_Node = $parent->ownerDocument->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
               $CertBody_Node = $parent->ownerDocument->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate', $this->getCertDataNoHeaders());

               $CertType_Node->appendChild($CertBody_Node);
               $KeyInfo_Node->appendChild($CertType_Node);
            }
        }
        $e->appendChild($KeyInfo_Node);
        return $e;
    }

    function getPEM($value, $raw = false)
    {

        $cleaned_value = preg_replace('#(\\\r)#', '', $value);
        $cleaned_value = preg_replace('#(\\\n)#', "\n", $value);

        $cleaned_value = trim($cleaned_value);

        // Add or remove BEGIN/END lines
        if ($raw)
        {
            $cleaned_value = preg_replace('-----BEGIN CERTIFICATE-----', '', $cleaned_value);
            $cleaned_value = preg_replace('-----END CERTIFICATE-----', '', $cleaned_value);
            $cleaned_value = trim($cleaned_value);
        } else
        {
            if (!empty($cleaned_value) && !preg_match('/-----BEGIN CERTIFICATE-----/', $cleaned_value))
            {
                $cleaned_value = "-----BEGIN CERTIFICATE-----\n" . $cleaned_value;
            }
            if (!empty($cleaned_value) && !preg_match('/-----END CERTIFICATE-----/', $cleaned_value))
            {
                $cleaned_value .= "\n-----END CERTIFICATE-----";
            }
        }

        return $cleaned_value;
    }

}

