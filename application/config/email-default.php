<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


$config['protocol'] = 'smtp';
$config['smtp_host'] = "SMTP_HOST";
$config['smtp_port'] = 25;
$config['charset'] = 'utf-8';
$config['crlf'] = "\r\n";
$config['newline'] = "\r\n";
$config['wordwrap'] = TRUE;
$config['useragent']='ResourceRegistr3';
$config['smtp_user'] = 'USER';
$config['smtp_pass'] = 'PASS';
$config['smtp_crypto'] = 'tls';


/**
 * default 
 */
$config['mail_sending_active'] = FALSE; 
$config['notify_if_provider_rm_from_fed'] = TRUE;
$config['notify_if_queue_rejected'] = TRUE;
$config['notify_admins_if_queue_accepted'] = TRUE;
$config['notify_requester_if_queue_accepted'] = TRUE;
$config['mail_from'] = 'MAIL FROM';
$config['fake_mail_from'] = 'FAKEMAIL';
$config['reply_to'] = 'REPLYTO';
$config['mail_subject_suffix'] = '[SUBJECT_SUFFIX]';
$config['mail_header'] = "Dear technical contact person\r\n";


/** 
 * overwrite builtin messages and localized
 */

/**
 * $config['defaultmail']['joinfed'] 
 * overwrites builtin mailbody of message sent to Adminisrtators
 * about provider request to join federation. You need to keep %s in proper order as they will be replaced with values of:
 * providername,entityid,federationname,url,additionalmessage
 */
$config['defaultmail']['joinfed'] = "Hi,\r\nJust few moments ago Administator of Provider %s (%s) \r\n
sent request to Administrators of Federation: %s \r\n
to access  him as new federation member.\r\n
To accept or reject this request please go to Resource Registry\r\n %s \r\n
\r\n\r\n======= additional message attached by requestor ===========\r\n
%s \r\n=============================================================\r\n
";

/**
 * $config['localizedmail']['joinfed'] 
 * creates localized mailbody of message sent to Adminisrtators
 * about provider request to join federation. You need to keep %s in proper order as they will be replaced with values of:
 * providername,entityid,federationname,url,additionalmessage
 */
$config['localizedmail']['joinfed'] = NULL;

/**
 *  if you set $config['localizedmail']['joinfed'] then mail will contain text from $config['localizedmail']['joinfed'] first 
 *  and then built-in/$config['defaultmail']['joinfed'] on the bottom. ex. in you local language and english
 *  if you want to use only you local language then set $config['defaultmail']['joinfed'] but not $config['localizedmail']['joinfed']
 */




