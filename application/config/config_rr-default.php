<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


$config['rr_setup_allowed'] = FALSE;
$config['site_logo'] = 'logo-default.png';

$config['syncpass'] = 'verystrongpasss';

$config['support_mailto'] = 'support@example.com';

/**
 * Federation Registration
**/
$config['fedname_min_length'] = 5;


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
 * optional path for nonpublic data used for reports, stats
 * if enabled pls create reports and stats directories inside specified below path with apache write access
 */
// $config['datastorage_path'] = '/opt/rr3data';



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
 * Defines the minimum allowed length of a username
 */
$config['username_min_length'] = 5;
/** 
 * make sure that all Shib_required are mapped
 * 
 */
$config['Shib_required'] = array('Shib_mail','Shib_username');
$config['Shib_username'] = 'eppn';
$config['Shib_mail'] = 'mail';
$config['Shib_groups'] = 'isMemberOf';
/**
 * optional firstname and surname
 */
$config['Shib_fname'] = 'givenName';
$config['Shib_sname'] = 'sn';
/**
 * shibb_updatefullname - if TRUE then every time when user is logging in and shib receives fname and sname then user records is updated
 */
$config['shibb_updatefullname'] = TRUE;

$config['Shibboleth']['loginapp_uri'] = 'auth/fedauth';
$config['Shibboleth']['logout_uri'] = '/Shibboleth.sso/Logout';
$config['Shibboleth']['enabled'] = TRUE;

/**
 * to enable simplesamlphp support 
 * notice: remember both jagger and simplesamlphp must have the same name for cookie session 
 */
/**

$config['simplesamlphp'] = array(
      'enabled'=>true,
      'location'=>'/opt/www-sites/simplesamlphp-1.11.0/www/_include.php',
      'authsourceid'=>'default-sp',
      'attributes'=> array('username'=>'eduPersonPrincipalName','mail'=>'mail'),
);
*/
$config['nameids'] = array(
	'urn:mace:shibboleth:1.0:nameIdentifier' => 'urn:mace:shibboleth:1.0:nameIdentifier',
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
	'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'=>'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
	'urn:oasis:names:tc:SAML:2.0:nameid-format:transient' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
	'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
		);

$config['metadata_validuntil_days'] = '7';


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
 * translator access
 * example: $config['translator_access']['pl'] = 'USERNAME_WITH_ACCESS';
 */


$config['curl_timeout'] = 90;
/**
 * optional -example if you want to limit list of langs in dropdown list in edit forms
 */
//$config['langselectlimit'] = array('en','pl');

/**
 * optional what lang should be selected by default 
 */
//$config['langselectdefault'] = 'pl';




$config['translator_access']['pl'] = null;
$config['translator_access']['pt'] = null;
$config['translator_access']['it'] = null;


/**
 * datastorage_path
 * it is used for generated stats/report files. it must be outsite application
 * value must end with forward slash
 * inside this location you need to create folders : stats , reports 

 */
/**
 * $config['datastorage_path'] = '/opt/rr3data/'
 */


$config['mq'] = 'rabbitmq';


/**
 * gearman
 */
$config['gearman'] = FALSE;
$config['gearmanconf']['jobserver'] = array(array('ip'=>'127.0.0.1','port'=>'4730'));
$config['statistics'] = FALSE;



/**
 * rabbitmq
 */

$config['rabbitmq'] = array(
    'enabled' => true,
    'vhost' => '/',
    'host'=>'127.0.0.1',
    'port'=> 5672,
    'user'=>'guest',
    'password'=>'guest'
);



/**
 * enable statistics collection gearman also has to be enabled
 */
$config['statistics'] = FALSE;


$config['disable_extcirclemeta'] = TRUE;


$config['fedmetadataidprefix'] = 'prefix-';
$config['fedexportmetadataidprefix'] = 'prefixexport-';
$config['circlemetadataidprefix'] = 'prefixcircle-';

//  optional
//$config['colortheme'] = 'orange';


/**
 *  you may create own gearman worker for collecting stats which can be called if below is enabled.
 *  below few examples.
 */
//$config['predefinedstats']['raptor1'] = array('worker'=>'heanetraptor','desc'=>'predefined stat defitnition');
//$config['predefinedstats']['thisiskey'] = array('worker'=>'otherwokername','desc'=>'predefined stat defitnition 2');

/**
 * disable generating circle metadata for providers who are not managed locally
 */
$config['disable_extcirclemeta'] = TRUE;


/**
 * optional add prefix to ID in EntitiesDescriptor
 */
//$config['fedmetadataidprefix'] = 'edugate-';
//$config['fedexportmetadataidprefix'] = 'edugateexport-';
//$config['circlemetadataidprefix'] = 'edugatecircle-';

// set if you want to disable change entityid and/or scope for no Admins
$config['entpartschangesdisallowed'] = array('entityid','scope');

$config['featenable']['tasks'] = FALSE;

