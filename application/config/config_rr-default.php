<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


/**
 * page title prefix
 */
$config['pageTitlePref'] = 'RR :: ';
/**
 * text displayed in footer
 */
$config['pageFooter'] = 'Resource Registry';

$config['rr_setup_allowed'] = FALSE;
$config['site_logo'] = 'logo-default.png';

$config['rr_display_memory_usage'] = TRUE;

$config['syncpass'] = 'verystrongpasss';

$config['support_mailto'] = 'support@example.com';

$config['mainfedname'] = 'Edugate';

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
 * Logos upload
 */
$config['rr_logoupload'] = false;
/**
 * rr_logoupload_relpath must be under your installation path
 */
$config['rr_logoupload_relpath'] = 'logos/';

$config['rr_logo_maxwidth'] = 300;
$config['rr_logo_maxheight'] = 300;
$config['rr_logo_types'] = 'png';
$config['rr_logo_maxsize'] = 2000;

/**
 * rr_load_gmap_js to TRUE , if you you want to load googlemap api, then you need valid googlemap key https://code.google.com/apis/console
 */
$config['rr_load_gmap_js'] = TRUE;

/**
 * autoregister_federated: if true then user authenticated with shibboleth is created in db 
 */
$config['autoregister_federated'] = false;
/**
 * set default Role for autoregistered user: Guest or Member
 * Guest has lowest level of permission, he can access only some pages
 * Member has read access to most pages
 */
$config['register_defaultrole'] = 'Guest';
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
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
	'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'=>'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
	'urn:oasis:names:tc:SAML:2.0:nameid-format:transient' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
	'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
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

/**
 * define if attributes requirement should be add to metadata
 */
$config['metadata_attrreqinc'] = false;

$config['policy_dropdown'] = array('0' => 'never', '1' => 'permit only if required', '2' => 'permit if required or desired');

/**
 * default registrationAuthority for example http://www.heanet.ie
 */
$config['registrationAutority'] = null;
/**
 * (boolean) load default registrationAuthority to localy managed entities if not  set. It will be visible in generated metadata 
 */
$config['load_registrationAutority'] = false;

/**
 * caching in seconds
 */

$config['arp_cache_time'] = 1200;
$config['metadata_cache_time'] = 120;

/**
 * map defaul center
 */
$config['geocenterpoint']  = array('-6.247856140071235','53.34961629053703');

/**
 * acls
 */


/**
 * styles
 */
/**
 * memcache
 */
$config['memcached'] = array(
            'hostname'        => 'localhost',
            'port'            => '11211',
            'weight'        => '1'        
    );
$config['cacheprefix'] = 'rr3_';
/**
 * translator access
 * example: $config['translator_access']['pl'] = 'USERNAME_WITH_ACCESS';
 */






$config['translator_access']['pl'] = null;
$config['translator_access']['pt'] = null;
$config['translator_access']['it'] = null;


/**
 * gearman
 */
$config['gearman'] = FALSE;
$config['gearmanconf']['jobserver'] = array(array('ip'=>'127.0.0.1','port'=>'4730'));

/**
 * disable generating circle metadata for providers who are not managed locally
 */
$config['disable_extcirclemeta'] = TRUE;



