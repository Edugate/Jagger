<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
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

function getBindSingleLogout()
{
   $y = array(
'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      );
return $y;   
}

function getBindSingleSignOn()
{
   $y = array(
    'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
    'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
    'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
    'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
    'urn:mace:shibboleth:1.0:profiles:AuthnRequest'
  );
return $y;   
}

function getBindACS()
{
   $y = array(
     'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
     'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
     'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
     'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
     'urn:oasis:names:tc:SAML:2.0:profiles:browser-post',
     'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
     'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
   );
   return $y;

}

function getAllowedProtocolEnum()
{
     $y = array(
      'urn:oasis:names:tc:SAML:2.0:protocol',
      'urn:oasis:names:tc:SAML:1.1:protocol',
      'urn:oasis:names:tc:SAML:1.0:protocol',
      'urn:mace:shibboleth:1.0'
      );
     return $y;

}

function getAllowedNameId()
{
     $y = array(
     'urn:mace:shibboleth:1.0:nameIdentifier',
     'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
     'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
     'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
     'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
     ); 
     return $y;
}
function getAllowedSOAPBindings()
{
     $y = array('urn:oasis:names:tc:SAML:2.0:bindings:SOAP','urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding');
     return $y;

}
function getArtifactBindings()
{
     $y = array('urn:oasis:names:tc:SAML:2.0:bindings:SOAP','urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding');
     return $y;

}
