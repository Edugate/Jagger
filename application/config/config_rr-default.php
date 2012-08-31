<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


$config['rr_setup_allowed'] = FALSE;
$config['site_logo'] = 'logo-default.png';

$config['rr_display_memory_usage'] = TRUE;

$config['syncpass'] = 'verystrongpasss';


/**
 * if TRUE feadmin may remove member from his fed without approve queue
 * don't change to FALSE as it's not finished yet
 */
$config['rr_rm_member_from_fed'] = TRUE;

/**
 * Logos
 * if rr_logobaseurl   - (with slash on the end) is null then codeigniter baseurl is used
 *    rr_logouriprefix - uri with slash on the end ex. 'app/uploaded/'
 * url od logo is $rr_baseurl.$rr_logouriprefix/$logo_file
 */
$config['rr_logobaseurl'] = null;
$config['rr_logouriprefix'] = 'logos/';

/**
 * rr_load_gmap_js to TRUE , if you you want to load googlemap api, then you need valid googlemap key https://code.google.com/apis/console
 */
$config['rr_load_gmap_js'] = TRUE;

/**
 * autoregister_federated: if true then user authenticated with shibboleth is created in db 
 */
$config['autoregister_federated'] = false;
/** 
 * make sure that all Shib_required are mapped
 * 
 */
$config['Shib_required'] = array('Shib_mail','Shib_username');
$config['Shib_username'] = 'eppn';
$config['Shib_mail'] = 'mail';

$config['Shibboleth']['loginapp_uri'] = 'auth/fedauth';
$config['Shibboleth']['logout_uri'] = '/Shibboleth.sso/Logout';
$config['Shibboleth']['enabled'] = TRUE;

$config['nameids'] = array(
	'urn:mace:shibboleth:1.0:nameIdentifier' => 'urn:mace:shibboleth:1.0:nameIdentifier',
	'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'=>'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
	'urn:oasis:names:tc:SAML:2.0:nameid-format:transient' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
	'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
		);

$config['supported_protocols'] = array(
	'urn:oasis:names:tc:SAML:2.0:protocol'=>'urn:oasis:names:tc:SAML:2.0:protocol',
	'urn:oasis:names:tc:SAML:1.1:protocol'=>'urn:oasis:names:tc:SAML:1.1:protocol',
	'urn:oasis:names:tc:SAML:1.0:protocol'=>'urn:oasis:names:tc:SAML:1.0:protocol',
	);

$config['ssohandler_saml2'] = array(
     'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
     'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
	 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
 	 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP'=>'urn:oasis:names:tc:SAML:2.0:bindings:SOAP');
$config['ssohandler_saml1'] = array('urn:mace:shibboleth:1.0:profiles:AuthnRequest'=>'urn:mace:shibboleth:1.0:profiles:AuthnRequest');

$config['acs_binding'] = array(
	'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
	'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
	'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign'=>'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
	'urn:oasis:names:tc:SAML:2.0:bindings:PAOS'=>'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
	'urn:oasis:names:tc:SAML:2.0:profiles:browser-post'=>'urn:oasis:names:tc:SAML:2.0:profiles:browser-post',
	'urn:oasis:names:tc:SAML:1.0:profiles:browser-post'=>'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
	'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01'=>'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01');


$config['metadata_validuntil_days'] = '7';

$config['policy_dropdown'] = array('0' => 'never', '1' => 'permit only if required', '2' => 'permit if required or desired');

/**
 * caching in seconds
 */

$config['arp_cache_time'] = 1200;
$config['metadata_cache_time'] = 120;


/**
 * acls
 */


/**
 * styles
 */

