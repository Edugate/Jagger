<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use RobRichards\XMLSecLibs\XMLSecurityDSig;

//use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @copyright 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Xmlvalidator
{
    private $rootSchemaFile;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('metadata_elements');
        libxml_use_internal_errors(true);

        $this->rootSchemaFile = $this->ci->config->item('rootSchemaFile');
        if (empty($this->rootSchemaFile)) {
            $this->rootSchemaFile = 'saml-schema-metadata-2.0.xsd';
        }
    }

    public function validateMetadata($xml, $signed = false, $pubKey = false) {

        $result = false;
        $xmlDOM = new \DOMDocument();
        $xmlDOM->strictErrorChecking = false;
        $schemaLocation = dirname(APPPATH) . '/schemas/old/' . $this->rootSchemaFile;

        if (function_exists('libxml_set_external_entity_loader')) {
            log_message('debug', 'libxml_set_external_entity_loader supported');
            $schemasFolder = dirname(APPPATH) . '/schemas/new/';
            $mapping = j_schemasMapping($schemasFolder);
            libxml_set_external_entity_loader(
                function ($public, $system, $context) use ($mapping) {
                    if (is_file($system)) {
                        return $system;
                    }
                    if (isset($mapping[$system])) {
                        return $mapping[$system];
                    }
                    $message = 'Failed to load external entity';
                    throw new RuntimeException($message);
                }
            );
            $schemaLocation = $schemasFolder . $this->rootSchemaFile;
        }


        \log_message('debug', __METHOD__ . ' started');


        if ($xml instanceOf \DOMDocument) {
            log_message('debug', __METHOD__ . ' received DOMDocument object ' . $xml->childNodes->length);
            $xmlDOM = $xml;
        } else {
            if (!$xmlDOM->loadXML($xml)) {
                $this->ci->globalerrors[] = 'Metadata validation: couldnt load xml document';
                log_message('error', __METHOD__ . ' couldn load xml into DOMDocument');

                return false;
            }
        }
        $childNodes = $xmlDOM->childNodes->length;
        if ($childNodes === 0) {
            $this->ci->globalerrors[] = 'Metadata validation: empty document received';
            log_message('error', __METHOD__ . ' empty DOMDocument');

            return false;
        }
        if ($signed === false) {
            $result = $xmlDOM->schemaValidate($schemaLocation);
            $errors = libxml_get_errors();
            if ($result === true) {
                log_message('debug', __METHOD__ . ' metadata is with schema');
            } else {
                if (is_array($errors)) {
                    foreach ($errors as $k => $v) {
                        if ($v instanceof LibXMLError && !empty($v->message)) {
                            $this->ci->globalerrors[] = htmlentities($v->message);
                            log_message('warning', __METHOD__ . ' validation metadata : ' . html_escape($v->message));
                        }
                    }
                }
                $this->ci->globalerrors[] = 'Metada validation : not valid with schema';
                log_message('warning', __METHOD__ . ' validated metadata is not with schema');
            }

            return $result;
        }

        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->idKeys[] = 'ID';
        $signatureElement = $objXMLSecDSig->locateSignature($xmlDOM);
        if (!$signatureElement) {
            $this->ci->globalerrors[] = 'Metada validation : couldnt locate signatureElement in Metadata';
            log_message('warning', __METHOD__ . ' couldnt locate signatureElement in Metadata DOMDocument');

            return false;
        }
        log_message('debug', __METHOD__ . '  signatureElement is located in Metadata DOMDocument');

        $objXMLSecDSig->canonicalizeSignedInfo();
        log_message('debug', __METHOD__ . '  finished canonicalizeSignedInfo method');
        if (!$objXMLSecDSig->validateReference()) {
            $this->ci->globalerrors[] = 'Metada validation : digest validation failed';
            log_message('warning', __METHOD__ . ' XMLsec: digest validation failed');

            return false;
        }
        log_message('debug', __METHOD__ . ' XMLsec: digest validation success');

        $objKey = $objXMLSecDSig->locateKey();
        if (null === $objKey) {
            $this->ci->globalerrors[] = 'Metada validation : Error loading key to handle XML signature';
            log_message('warning', __METHOD__ . ' Error loading key to handle XML signature');

            return false;
        }
        if (empty($pubKey)) {
            $this->ci->globalerrors[] = 'Metada validation : Certificate not provided for metadata signature validation';
            log_message('warning', __METHOD__ . ' Certificate not provided for metadata signature validation');

            return false;
        }
        $objKey->loadKey($pubKey, false, true);
        if (!$objXMLSecDSig->verify($objKey)) {
            $this->ci->globalerrors[] = 'Metada validation : Unable to validate Signature';
            log_message('warning', __METHOD__ . ' Unable to validate Signature');
        } else {
            $result = $xmlDOM->schemaValidate($schemaLocation);
            $errors = libxml_get_errors();
            if ($result === true) {
                log_message('debug', __METHOD__ . ' metadata is valid with schema');
            } else {
                $this->ci->globalerrors[] = 'Metada validation : not valid with schema';
                log_message('warning', __METHOD__ . ' validated metadata is not with schema: ' . serialize($errors));
            }
        }
        \log_message('debug', __METHOD__ . ' end');

        return $result;
    }


}
