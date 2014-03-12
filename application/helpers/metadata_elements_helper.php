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

function h_metadataNamespaces()
{
    $t = array();
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
          );
     return $t;

}

function h_metadataComment($comment)
{
     $result = str_replace('--', '-' . chr(194) . chr(173) . '-', $comment);
     return $result;
}
