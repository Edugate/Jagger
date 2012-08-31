<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Metadata_validator {

//	private  $idp;
//	private  $tmp_providers;

    function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->mid = $this->ci->mid;
    }

    public function validateWithSchema($metadata = null) {
        if (empty($metadata)) {
            log_message('error', $this->mid . 'cannot validate empty metadata');
            return false;
        }
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->strictErrorChecking = FALSE;
        $doc->strictWarningChecking = FALSE;
        $doc->loadXML($metadata);
        /*
          foreach(libxml_get_errors() as $er)
          {
          echo "DD<pre>";
          print_r($er);
          echo "</pre>FF";

          }
         */

        //$doc->schemaValidate('library.xsd'); 
        $result = $doc->schemaValidate('schemas/saml-schema-metadata-2.0.xsd');
        if ($result === TRUE) {
            log_message('debug', $this->mid . 'tested metadata is valid');
        } else {
            log_message('error', $this->mid . 'tested metadata is not valid');
        }
        return $result;
    }

}
