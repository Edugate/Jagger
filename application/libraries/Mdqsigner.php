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
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @link      https://github.com/Edugate/Jagger
 */


class Mdqsigner
{
    protected $ci;
    protected $em;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }





    public function storeMetadada($entityInSha, $xml){
        $tmpStorageDir = '/opt/Jagger/signedmetadata/mdq';
        $fullDirPath = $tmpStorageDir.'/'.$entityInSha;
        if(!is_dir($fullDirPath) && !mkdir($fullDirPath) && !is_dir($fullDirPath)){
            throw new Exception('hhhh');
        }

        file_put_contents($fullDirPath.'/metadata.xml',$xml);
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
                $privKeyPassword = file_get_contents($keyStorage . '/' . $keyStorageSeg1 . '/priv.pass');
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
                $privKeyPassword = file_get_contents($keyStorage . '/' . $signKey['dir'] . '/' . $signKey['passwordfile']);
            }

        }


        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $objDSig = new XMLSecurityDSig();

        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        // Sign using SHA-256
        $objDSig->addReference(
            $doc->documentElement,
            XMLSecurityDSig::SHA256,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'), array('force_uri' => true,)
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
