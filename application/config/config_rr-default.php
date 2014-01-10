<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

//$config['defaultfedname']= 'Edugate';

/**
 * page title prefix
 */
$config['pageTitlePref'] = 'RR :: ';
/**
 * text displayed in footer - it's replaced with preferences from database
 */ 
// $config['pageFooter'] = 'Resource Registry';

$config['rr_setup_allowed'] = FALSE;
$config['site_logo'] = 'logo-default.png';

$config['syncpass'] = 'verystrongpasss';

$config['support_mailto'] = 'support@example.com';


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

/**
 * gearman
 */
$config['gearman'] = FALSE;
$config['gearmanconf']['jobserver'] = array(array('ip'=>'127.0.0.1','port'=>'4730'));
$config['statistics'] = FALSE;

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

/**
 * there are two logic ways to generate ARP files:
 * 1) old: by overwrite with exclusion - for example: there are: default policy, per federation, per SP
 *    if you have set specific policy for SP then default/perFederation are completely ignored for example:
 *    default policy is to release MAIL, EPPN if required and  you set SP policy for only MAIL . As global policy is ignored that follows EPPN is not set - it means DENY
 *    It may cause a lot of trouble with later management
 *
 * 2) by inherit: this new logic. Inherit: GLOBAL->PERFED (+ overwriting) ->SPECIFIC (+overwrite)
 *      So in above example: EPPN is set -> overwrite global policy; MAIL is not set -> inherits from FED/GLOBAL
 *
 * To keep backward compatibility as default is set old logic. If you want to use new one (by inherit) you need to set: $config['arpbyinherit'] = TRUE; 
 *    
 */
$config['arpbyinherit'] = FALSE;
