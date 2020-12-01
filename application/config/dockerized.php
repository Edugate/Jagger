<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 
 */

if(empty($config['base_url'])){
 $vhost =  getenv('VIRTUAL_HOST')?:getenv('HOSTNAME');
 $config['base_url'] = 'https://'.$vhost.getenv('JAGGER_URI').'/';
}

$config['rabbitmq'] = array(
    'enabled' => true,
    'vhost' => getenv('RABBITMQ_VHOST')?:'/',
    'host'=>getenv('RABBITMQ_HOST')?:'rabbitmq',
    'port'=> 5672,
    'user'=>getenv('RABBITMQ_USER')?:'admin',
    'password'=>getenv('RABBITMQ_PASS')?:'password'
);

$JAGGERSETUP = getenv('JAGGER_SETUP');
if($JAGGERSETUP === true || $JAGGERSETUP === 'true')
{
   $config['rr_setup_allowed'] = true;
}

$config['syncpass'] = getenv('JAGGER_SYNC_PASS')?:'';
$config['log_path'] = getenv('JAGGER_LOGS')?: 'application/logs/';

$config['cookie_path'] = getenv('JAGGER_URI');
$config['sess_driver'] = 'memcached';
$MEMCACHED_HOST = getenv('MEMCACHE_HOST')?: 'localhost';
$config['sess_save_path'] = ''.$MEMCACHED_HOST.':11211';
$jaggerlogo = getenv('JAGGER_LOGO');
if($jaggerlogo) {
	   $config['site_logo_url'] = $jaggerlogo;
}
if(getenv('BEHIND_PROXY') === '1')
{
	   $config['proxy_ips'] = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"]  
		           : '';
}
$JAGGERMDQ = getenv('JAGGER_MDQ');
if($JAGGERMDQ === 1 || $JAGGERMDQ === '1')
{
   $config['mdq'] = true;
}
if(empty($config['internalprefixurl'])){

   $config['internalprefixurl'] = getenv('JAGGERINTERNALURL');
}
$config['feduserapplyform'] = false;
$JAGGERFEDUSERAPPLYFORM = getenv('JAGGER_FEDUSER_APPLY_FORM');
if($JAGGERFEDUSERAPPLYFORM === 1 || $JAGGERFEDUSERAPPLYFORM === '1'){
  $config['feduserapplyform'] = true
}

