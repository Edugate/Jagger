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


function j_SignatureAlgorithms()
{
	$result = array(
		'http://www.w3.org/2000/09/xmldsig#dsa-sha1',
		'http://www.w3.org/2009/xmldsig11#dsa-sha256',
		'http://www.w3.org/2001/04/xmldsig-more#rsa-md5',
		'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
		'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
		'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384',
		'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512',
		'http://www.w3.org/2001/04/xmldsig-more#rsa-ripemd160',
		'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1',
		'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224',
		'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256',
		'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384',
		'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512',
		'http://www.w3.org/2000/09/xmldsig#hmac-sha1',
		'http://www.w3.org/2001/04/xmldsig-more#hmac-sha256',
		'http://www.w3.org/2001/04/xmldsig-more#hmac-sha384',
		'http://www.w3.org/2001/04/xmldsig-more#hmac-sha512',
		'http://www.w3.org/2001/04/xmldsig-more#hmac-ripemd160'

	);

	return $result;

}

function j_DigestMethods()
{
	$result = array(
		'http://www.w3.org/2001/04/xmldsig-more#md5',
		'http://www.w3.org/2000/09/xmldsig#sha1',
		'http://www.w3.org/2001/04/xmldsig-more#sha224',
		'http://www.w3.org/2001/04/xmlenc#sha256',
		'http://www.w3.org/2001/04/xmldsig-more#sha384',
		'http://www.w3.org/2001/04/xmlenc#sha512',
		'http://www.w3.org/2001/04/xmlenc#ripemd160'

	);
	return $result;

}

/**
 * source http://www.w3.org/TR/2010/WD-xmlsec-algorithms-20100513/
 */
function j_KeyEncryptionAlgorithms()
{
	$result = array(
		'http://www.w3.org/2001/04/xmlenc#tripledes-cbc',
		'http://www.w3.org/2001/04/xmlenc#aes128-cbc',
		'http://www.w3.org/2001/04/xmlenc#aes192-cbc',
		'http://www.w3.org/2001/04/xmlenc#aes256-cbc',
                'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p',
		'http://www.w3.org/2001/04/xmldsig-more#camellia128-cb',
		'http://www.w3.org/2001/04/xmldsig-more#camellia192-cbc',
		'http://www.w3.org/2001/04/xmldsig-more#camellia256-cbc',
                'http://www.w3.org/2009/xmlenc11#aes128-gcm',
                'http://www.w3.org/2009/xmlenc11#aes192-gcm',
                'http://www.w3.org/2009/xmlenc11#aes256-gcm',
                'http://www.w3.org/2009/xmlenc11#rsa-oaep'

	);

	return $result;

}


function j_schemasMapping($prefix = null)
{
	if (is_null($prefix)) {
		$prefix = '';
	}
	$t = array(

		'http://www.w3.org/TR/2002/REC-xmldsig-core-20020212/xmldsig-core-schema.xsd' => $prefix . 'xmldsig-core-schema.xsd',
		'http://www.w3.org/TR/2002/REC-xmlenc-core-20021210/xenc-schema.xsd' => $prefix . 'xenc-schema.xsd',
		'http://www.w3.org/2001/xml.xsd' => $prefix . 'xml.xsd',

	);
	return $t;

}

function h_metadataNamespaces()
{
	$t = array(
		'md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
		'ds' => 'http://www.w3.org/2000/09/xmldsig#',
		'saml' => 'urn:oasis:names:tc:SAML:2.0:assertion',
		'shibmd' => 'urn:mace:shibboleth:metadata:1.0',
		'mdui' => 'urn:oasis:names:tc:SAML:metadata:ui',
		'mdattr' => 'urn:oasis:names:tc:SAML:metadata:attribute',
		'mdrpi' => 'urn:oasis:names:tc:SAML:metadata:rpi',
		'idpdisc' => 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol',
                'xenc' => 'http://www.w3.org/2001/04/xmlenc#',
		'init' => 'urn:oasis:names:tc:SAML:profiles:SSO:request-init',
		'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
		'xi' => 'http://www.w3.org/2001/XInclude',
                'alg' => 'urn:oasis:names:tc:SAML:metadata:algsupport',
                'algsupport' => 'urn:oasis:names:tc:SAML:metadata:algsupport',
                'remd' => 'http://refeds.org/metadata',
                'hoksso' => 'urn:oasis:names:tc:SAML:2.0:profiles:holder-of-key:SSO:browser'
	);
	return $t;

}

function h_metadataComment($comment)
{
	$result = str_replace('--', '-' . chr(194) . chr(173) . '-', $comment);
	return $result;
}


function boolToStr($b){
    if($b === true){
        return 'true';
    }
    if($b === false){
        return 'false';
    }

}
