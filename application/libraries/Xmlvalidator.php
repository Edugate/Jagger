<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Xmlvalidator {
    
    private $xmlDOM;
    private $pubKey;
    private $errors = array();

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('metadata_elements');
        libxml_use_internal_errors(true);
        $this->xmlDOM = new \DOMDocument();
        $this->xmlDOM->strictErrorChecking = FALSE;
        $this->xmlDOM->WarningChecking = FALSE;
    }

    public function validateMetadata($xml,$signed = FALSE,$pubkey = FALSE)
    {
        \log_message('debug',__METHOD__.' started');
         
        $result = FALSE;
        $this->pubKey=$pubkey;
        if($xml instanceOf \DOMDocument)
        {
            log_message('debug',__METHOD__. ' received DOMDocument object '.$xml->childNodes->length);
            $this->xmlDOM = $xml;
        }
        else
        {
           if(!$this->xmlDOM->loadXML($xml))
           {
              $this->ci->globalerrors[] = 'Metadata validation: couldnt load xml document';
              log_message('error',__METHOD__.' couldn load xml into DOMDocument');
              return FALSE;
           }
        }
        $childNodes = $this->xmlDOM->childNodes->length;
        if($childNodes === 0)
        {
            $this->ci->globalerrors[] = 'Metadata validation: empty document received';
            log_message('error',__METHOD__.' empty DOMDocument');
            return FALSE;
        }
        else
        {
            log_message('debug',__METHOD__.' not empty DOMDocument');
        }
        if($signed === FALSE)
        {
            $result = $this->xmlDOM->schemaValidate('schemas/saml-schema-metadata-2.0.xsd');
            $errors = libxml_get_errors();
            
            if($result === TRUE)
            {
                log_message('debug',__METHOD__.' metadata is with schema');
            }
            else
            {
                if(!empty($errors) && is_array($errors))
                {
                     foreach($errors as $k=>$v)
                     {
                         if($v instanceof LibXMLError && !empty($v->message))
                         {
                            $this->ci->globalerrors[] = htmlentities($v->message);
                         }
                     }
                }
      
                $this->ci->globalerrors[] = 'Metada validation : not valid with schema';
                log_message('warning', __METHOD__.' validated metadata is not with schema');
            }
            return $result;
       }
       $this->ci->load->library('xmlseclibs');
       $objXMLSecDSig = new XMLSecurityDSig();
       $objXMLSecDSig->idKeys[] = 'ID';
       $signatureElement = $objXMLSecDSig->locateSignature($this->xmlDOM);
       if(!$signatureElement)
       {
           $this->ci->globalerrors[] = 'Metada validation : couldnt locate signatureElement in Metadata';
           log_message('warning',__METHOD__.' couldnt locate signatureElement in Metadata DOMDocument');
           return FALSE;
       }
       else
       {
           log_message('debug',__METHOD__.'  signatureElement is located in Metadata DOMDocument');

       }
       $objXMLSecDSig->canonicalizeSignedInfo();
       log_message('debug',__METHOD__.'  finished canonicalizeSignedInfo method');
       if (!$objXMLSecDSig->validateReference()) 
       {
           $this->ci->globalerrors[] = 'Metada validation : digest validation failed';
           log_message('warning',__METHOD__.' XMLsec: digest validation failed');
           return FALSE;
       }
       else
       {
           log_message('debug',__METHOD__.' XMLsec: digest validation success');
       }
       $objKey = $objXMLSecDSig->locateKey();
       if (empty($objKey)) {
           $this->ci->globalerrors[] = 'Metada validation : Error loading key to handle XML signature';
           log_message('warning',__METHOD__.' Error loading key to handle XML signature');
           return FALSE;
       }
       if(empty($this->pubKey))
       {
           $this->ci->globalerrors[] = 'Metada validation : Certificate not provided for metadata signature validation';
           log_message('warning',__METHOD__.' Certificate not provided for metadata signature validation');
           return FALSE;
       }
       $objKey->loadKey($this->pubKey,FALSE,TRUE);
       if (!$objXMLSecDSig->verify($objKey)) {
           $this->ci->globalerrors[] = 'Metada validation : Unable to validate Signature';
           log_message('warning',__METHOD__.' Unable to validate Signature');
       }
       else
       {
            $result = $this->xmlDOM->schemaValidate('schemas/saml-schema-metadata-2.0.xsd');
            $errors = libxml_get_errors();
            if($result === TRUE)
            {
                log_message('debug',__METHOD__.' metadata is valid with schema');
            }
            else
            {
                $this->ci->globalerrors[] = 'Metada validation : not valid with schema';
                log_message('warning', __METHOD__.' validated metadata is not with schema');
            }
       }
       \log_message('debug',__METHOD__.' end');
       return $result; 
    }



}
