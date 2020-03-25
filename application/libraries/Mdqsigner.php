<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @author    Middleware Team HEAnet <support@edugate.ie>
 * @copyright 2018 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @link      https://github.com/Edugate/Jagger
 */
class Mdqsigner
{
    protected $ci;
    protected $em;
    protected $metaPaths;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->metaPaths = $this->getMdqStoragePaths();
    }

    public function getMdqStoragePaths() {


        $prefix = dirname(APPPATH);
        return array(
            'circle'     => $prefix . '/signedmetadata/mdq/circle/',
            'entity'     => $prefix . '/signedmetadata/mdq/entity/',
            'federation' => $prefix . '/signedmetadata/mdq/federation/'
        );
    }


    public function getStoredMetadata($entityInSha) {
        $tmpStorageDir = $this->metaPaths['entity'];
        $filePath = $tmpStorageDir . '' . $entityInSha . '/metadata.xml';
        $result = array();
        if (is_file($filePath)) {

            $fileSize = (int) filesize($filePath);
            if($fileSize == 0){
                log_message('error', __METHOD__.' : empty metadata file :'.$filePath);
                return null;
            }
            $result['modified'] = date("U", filemtime($filePath));
            $result['metadata'] = file_get_contents($filePath);

            return $result;
        }
        return null;
    }

    public function storeMetadada($entityInSha, $xml) {
        $tmpStorageDir = $this->metaPaths['entity'];
        $fullDirPath = $tmpStorageDir . '' . $entityInSha;

        if (!is_dir($fullDirPath)) {
            if (!mkdir($fullDirPath, 0777, true) && !is_dir($fullDirPath)) {
                throw new Exception('hhhh');
            }
        }

        file_put_contents($fullDirPath . '/metadata.xml', $xml);
    }


    public function getEntity($entityid) {
        /**
         * @var $entity \models\Provider
         */
        $entity = null;
        $maxAttempts = 10;
        $attempt = 1;

        while ($attempt < $maxAttempts) {
            try {
                $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));
                $attempt = $maxAttempts;
            } catch (Exception $e) {
                log_message('error',__CLASS__.'::'.__METHOD__.': '.$e);
                $this->em->getConnection()->close();
                $this->em->getConnection()->connect();
                $attempt++;
                sleep(2);
            }

        }

        return $entity;
    }


    private function regenerateStatic(models\Provider $entity) {

        $standardNS = h_metadataNamespaces();

        $xmlOut = $this->ci->providertoxml->createXMLDocument();
        $this->ci->providertoxml->entityStaticConvert($xmlOut, $entity);
        $xmlOut->endDocument();


        $outPut = $xmlOut->outputMemory();
        $domXML = new DOMDocument();

        $domXML->loadXML($outPut, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($domXML);
        $xpath->registerNamespace('', 'urn:oasis:names:tc:SAML:2.0:metadata');
        foreach ($standardNS as $key => $val) {
            $xpath->registerNamespace('' . $key . '', '' . $val . '');
        }
        /**
         * @var \DOMElement $element
         */
        $element = $domXML->getElementsByTagName('EntityDescriptor')->item(0);
        if ($element === null) {
            $element = $domXML->getElementsByTagName('md:EntityDescriptor')->item(0);
        }
        if ($element !== null) {
            $element->setAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
            foreach ($standardNS as $key => $val) {
                $element->setAttribute('xmlns:' . $key . '', '' . $val . '');
            }
        }

        return $domXML->saveXML();
    }

    /**
     * @param \models\Provider $entity
     */
    private function genMetadata(\models\Provider $entity) {
        if(null === $entity){
            throw new Exception(__CLASS__.' '.__METHOD__." MDQ : entity object is null");
        }
        $entityInSha = sha1($entity->getEntityId());
        log_message('debug',__CLASS__.'MDQ:: entityInSha '.$entityInSha);
        $this->ci->load->library(array('providertoxml'));
        $options['attrs'] = 1;
        $isStatic = $entity->isStaticMetadata();
        $unsignedMetadata = null;
        /**
         * @var \XMLWriter $xmlOut
         */
        if ($isStatic) {
            $unsignedMetadata = $this->regenerateStatic($entity);
        } else {
            $xmlOut = $this->ci->providertoxml->entityConvertNewDocument($entity, $options);
            if (!empty($xmlOut)) {
                $unsignedMetadata = $xmlOut->outputMemory();
            } else {
                throw new Exception("Empty xmlout");
            }
        }

        if ($unsignedMetadata !== null) {
            try {
                $signeMetadata = $this->signXML($unsignedMetadata, null);
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
                throw new Exception($e);
            }
            try {
                $this->storeMetadada($entityInSha, $signeMetadata);
            } catch (Exception $e) {
                log_message('ERROR', __METHOD__ . ' ' . $e);
            }

            return $signeMetadata;
        }
        throw new Exception("empty metadata");
    }


    public function sign($entityid) {
        $entity = $this->getEntity($entityid);
        try {
            $unsignedMetadata = $this->genMetadata($entity);
            $this->signXML($unsignedMetadata);
            return true;
        }
        catch (Exception $e){
            log_message('error',$e);
        }

        return false;


    }


    /**
     * @param $xml
     * @param null|array $signKey
     * @return string
     * @throws Exception
     */
    public function signXML($xml, $signKey = null) {
        $privKeyPassword = null;
        $keyStorage = $this->ci->config->item('keystorage');
        if ($keyStorage === null) {
            throw new Exception('Key storage (\"keystorage\") is not defined in configuration');
        }
        if ($signKey === null) {
            $keyStorageSeg1 = 'default';
            $certFile = $keyStorage . '/' . $keyStorageSeg1 . '/public.crt';
            $privKeyFile = $keyStorage . '/' . $keyStorageSeg1 . '/priv.key';
            if (file_exists($keyStorage . '/' . $keyStorageSeg1 . '/priv.pass')) {
                $privKeyPassword = trim(file_get_contents($keyStorage . '/' . $keyStorageSeg1 . '/priv.pass'));
            }

        } else {
            if (!isset($signKey['public'], $signKey['private'])) {
                throw new Exception('public/private key not provided');
            }
            if (!isset($signKey['dir'])) {
                throw new Exception('subdir for key not provided');
            }

            $certFile = $keyStorage . '/' . $signKey['dir'] . '/' . $signKey['public'];
            $privKeyFile = $keyStorage . '/' . $signKey['dir'] . '/' . $signKey['private'];

            if (isset($signKey['password'])) {
                $privKeyPassword = $signKey['password'];
            } elseif (isset($signKey['passwordfile'])) {
                $privKeyPassword = trim(file_get_contents($keyStorage . '/' . $signKey['dir'] . '/' . $signKey['passwordfile']));
            }

        }

        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $mdqValidDays = $this->ci->config->item('mdq_validuntil_days') ?: 5;

        $validfor = new \DateTime('now', new \DateTimezone('UTC'));
        $validfor->modify('+' . $mdqValidDays . ' day');
        $doc->documentElement->setAttribute('validUntil', $validfor->format('Y-m-d\TH:i:s\Z'));
        $objDSig = new XMLSecurityDSig();

        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        // Sign using SHA-256
        $objDSig->addReference(
            $doc->documentElement,
            XMLSecurityDSig::SHA256,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'), array('force_uri' => false, 'id_name' => 'ID')
        );


        // Create a new (private) Security key
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        if ($privKeyPassword !== null) {
            $objKey->passphrase = $privKeyPassword;
        }

        $objKey->loadKey($privKeyFile, true);

        $objDSig->sign($objKey);
        // Add the associated public key to the signature
        $objDSig->add509Cert(file_get_contents($certFile));

        // Append the signature to the XML
        // $objDSig->insertSignature($doc->documentElement);
        $objDSig->appendSignature($doc->documentElement);

        // Save the signed XML
        return $doc->saveXML();
        //return false;
    }

}
