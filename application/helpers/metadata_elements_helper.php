<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet Ltd.
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * RR3 Helpers
 *
 * @package     RR3
 * @subpackage  Helpers
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


function j_schemasMapping($prefix = null)
{
   if(is_null($prefix))
   {
      $prefix = '';
   }
   $t = array(
       
       'http://www.w3.org/TR/2002/REC-xmldsig-core-20020212/xmldsig-core-schema.xsd' => $prefix.'xmldsig-core-schema.xsd',
       'http://www.w3.org/TR/2002/REC-xmlenc-core-20021210/xenc-schema.xsd'=>$prefix.'xenc-schema.xsd',
       'http://www.w3.org/2001/xml.xsd'=>$prefix.'xml.xsd',
        
   );
   return $t;

}

function h_metadataNamespaces()
{
    $t = array(
         'md'=>'urn:oasis:names:tc:SAML:2.0:metadata',
         'ds'=>'http://www.w3.org/2000/09/xmldsig#',
         'saml'=>'urn:oasis:names:tc:SAML:2.0:assertion',
         'shibmd'=>'urn:mace:shibboleth:metadata:1.0',
         'mdui'=>'urn:oasis:names:tc:SAML:metadata:ui',
         'mdattr'=>'urn:oasis:names:tc:SAML:metadata:attribute',
         'mdrpi'=>'urn:oasis:names:tc:SAML:metadata:rpi',
         'idpdisc'=>'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol',
         'wayf'=>'http://sdss.ac.uk/2006/06/WAYF',
         'elab'=>'http://eduserv.org.uk/labels',
         'ukfedlabel'=>'http://ukfederation.org.uk/2006/11/label',
         'init'=>'urn:oasis:names:tc:SAML:profiles:SSO:request-init',
         'xsi'=>'http://www.w3.org/2001/XMLSchema-instance',
         'xi'=>'http://www.w3.org/2001/XInclude',
          );
     return $t;

}

function h_metadataComment($comment)
{
     $result = str_replace('--', '-' . chr(194) . chr(173) . '-', $comment);
     return $result;
}
