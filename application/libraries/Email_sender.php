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
 * Email_sender Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Email_sender {
    function __construct() {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
    }


   function addToMailQueue(array $notificationTypes,$obj=NULL,$subject,$body,$additionalReciepients,$sync=false)
   {
       $subscribers = array();
       $notificationTypes[] = 'systemnotifications';
       if(count($notificationTypes)>0)
       {
           $subscribers = $this->em->getRepository("models\NotificationList")->findBy(
                          array('type'=>$notificationTypes,'is_enabled'=>true,'is_approved'=>true));
       }
       $alreadyMailTo = array();
       foreach($subscribers as $s)
       {
            $type = $s->getType();
            if($type === 'joinfedreq')
            {
               if(empty($obj))
               {
                    continue;
               }
               if(!$obj instanceOf models\Federation)
               {
                  continue;
               }
               $objId = $obj->getId();
               $fed = $s->getFederation();
               if(empty($fed))
               {
                  continue;
               }
               $fedId = $fed->getId();
               if($fedId != $objId)
               {
                  continue;
               }
               
            }
            elseif($type === 'fedmemberschanged')
            {
               if(empty($obj))
               {
                    continue;
               }
               if(is_array($obj))
               {
                   foreach($obj as $v)
                   {
                       if($v instanceOf models\Federation)
                       {
                          $objId = $obj->getId();
                          $fed = $s->getFederation();
                          if(empty($fed))
                          {
                               continue;
                          }
                          $fedId = $fed->getId();
                          if($fedId != $objId)
                          {
                             continue;
                          }

                       }
                   }
               }
               elseif($v instanceOf models\Federation)
               {
                          $objId = $obj->getId();
                          $fed = $s->getFederation();
                          if(empty($fed))
                          {
                               continue;
                          }
                          $fedId = $fed->getId();
                          if($fedId != $objId)
                          {
                             continue;
                          }

               }
               else
               {
                  continue;
               }

            }
            elseif($type === 'requeststoproviders')
            {
                if(!(!empty($obj) && ($obj instanceOf models\Provider)))
                {
                   continue;
                }
                $objId = $obj->getId();
                $prov = $s->getProvider();
                if(empty($prov))
                {
                   continue;
                }
                $provId = $prov->getId();
                if($provId != $objId)
                {
                  continue;
                }
            }
            $mailto = $s->getRcpt();
            if(!in_array($mailto,$alreadyMailTo))
            {
               $m = new models\MailQueue();
               $m->setSubject($subject);
               $m->setBody($body);
               $m->setDeliveryType($s->getNotificationType());
               $m->setRcptto($mailto);
               $this->em->persist($m);
               $alreadyMailTo[] = $mailto;
            }
       }
       if(!empty($additionalReciepients) and is_array($additionalReciepients) && count($additionalReciepients)>0)
       {
          foreach($additionalReciepients as $v)
          {
              if(!in_array($v,$alreadyMailTo))
              {
                  $m = new models\MailQueue();
                  $m->setSubject($subject);
                  $m->setBody($body);
                  $m->setDeliveryType('mail');
                  $m->setRcptto($v);
                  $this->em->persist($m);
                  $alreadyMailTo[] = $v;
              }
          }
       }
       return true;
   }

   /**
    * $to may be single email or array of mails
    */
   function send($to,$subject,$body)
   {
      $sending_enabled = $this->ci->config->item('mail_sending_active');
      log_message('debug','Mail:: preparing');
      log_message('debug','Mail:: To: '. serialize($to));
      log_message('debug','Mail:: Subject: '. $subject);
      log_message('debug','Mail:: Body: '. $body);
      
      if(!$sending_enabled)
      {
          log_message('debug','Mail:: cannot be sent because $config[mail_sending_active] is not true');
          return false;
      }
      else
      {
          log_message('debug','Preparing to send email');
      }
      $full_subject = $subject ." " . $this->ci->config->item('mail_subject_suffix');
      $list = array();
      if(!is_array($to))
      {
         $list[] = $to;
      }
      else
      {
         $list = $to;
      }
      foreach($list as $k)
      {
          $this->ci->email->clear();
          $this->ci->email->from($this->ci->config->item('mail_from'), '');
          $this->ci->email->to($k, '');
          $this->ci->email->subject($full_subject);
          $footer = $this->ci->config->item('mail_footer');
          $message = $body . $footer;
          $this->ci->email->message($message);
          if($this->ci->email->send())
          {
             log_message('debug','email sent to '.$k);
          }
          else
          {
             log_message('error','email couldnt be sent to '.$k);
             log_message('error',$this->ci->email->print_debugger());
          }

      } 
      return true;
   } 

}

