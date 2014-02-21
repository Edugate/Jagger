<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Gworkers
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Gworkers extends MY_Controller {

    
    function __construct() {
        parent::__construct();
    }

     


    function worker()
    {
        if($this->input->is_cli_request())
        {
             $this->load->library('gearmanw');
             $this->gearmanw->worker();
        }
        else
        {

           show_error('denied',403);
        }
    }
    
    function mailqueuesender()
    {
       if(!$this->input->is_cli_request())
       {

           show_error('denied',403);
           return;

       }
       
       log_message('info','MAILQUEUE STARTED : daemon needs to be restarted after any changes in configs');
       $this->load->library('doctrine');
       $em = $this->doctrine->em;
       $sending_enabled = $this->config->item('mail_sending_active');
       $mailfrom = $this->config->item('mail_from');
       $subjsuffix = $this->config->item('mail_subject_suffix');
       if(empty($subjsuffix))
       {
          $subjsuffix ='';
       }
       $mailfooter = $this->config->item('mail_footer');
       if(empty($mailfooter))
       {
          log_message('warning','MAILQUEUE ::  it is recommended to  set default footer (mail_footer) for mails in email.php config file');
          $mailfooter = '';
       }
       $attempt = 0;
       $maxattempts = 10;
       while(TRUE)
       {
           if(empty($sending_enabled))
           {
              log_message('warning', 'MAILQUEUE :: sending mails is disabled - check config "mail_sending_active" ');
           }
           else
           {
              log_message('debug','MAILQUEUE :: checks for mails to be sent');
              try
              {
                 $mails = $em->getRepository("models\MailQueue")->findBy(array('deliverytype'=>'mail','frequence'=>'1','issent'=>false));
                  
                 foreach($mails as $m)
                 {
                     log_message('debug','MAILQUEUE sending '.$m->getId());
                     $maildata = $m->getMailToArray();
                     $this->email->clear();
                     $this->email->from($mailfrom);
                     $this->email->to($maildata['to']);
                     $this->email->subject($maildata['subject'].' '.$subjsuffix);
                     $this->email->message($maildata['data'].PHP_EOL.''.$mailfooter.PHP_EOL);
                     if($this->email->send())
                     {
                        $m->setMailSent();
                        $em->persist($m);
                     }
                     else
                     {
                        log_message('error','MAILQUEUE couldnt sent mail to '.$maildata['to']);
                        log_message('error','MAILQUEUE ::'.$this->email->print_debugger());
                     }
                 }
                 $em->flush();
                 $em->clear();
              }
              catch(Exception $e)
              {
                 log_message('error','MAIL QUEUE ::'.__METHOD__.' lost connection to database trying to reconnect');
                 $em->getConnection()->close();
                 sleep(10);
                 $em->getConnection()->connect();
              }
           }
           sleep(60);
       }
    }




}
