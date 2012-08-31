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
$config['mail_footer'] = "\r\n \r\n
YOUR FOOTER \r\n
-- \r\n
COMPANY\r\n
Phone: xxxxxxxxx\r\n
email:xxxxxxxxxxxx\r\n";
