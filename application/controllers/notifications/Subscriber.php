<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Dashboard Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Subscriber extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('j_auth');
    }

    public function index()
    {

        echo "okok2";
    }


    public function mysubscriptions($encodeduser=null)
    {
       if(empty($encodeduser))
       {
           show_error('not found',404);
       }
       $loggedin = $this->j_auth->logged_in();
       if(!$loggedin)
       {
           redirect('auth/login', 'location');
           return;
       } 
       $decodeduser = base64url_decode($encodeduser);
       $loggeduser = $this->j_auth->current_user();
       if(empty($loggeduser))
       {
           log_message('warning', 'User logged in but missing username in sesssion');
           show_error('permission denied',403);

       }
       $isAdmin = $this->j_auth->isAdministrator();
       if(strcasecmp($decodeduser,$loggeduser)!=0 && $isAdmin !== TRUE)
       {
           log_message('warning', __METHOD__.': User '.$loggeduser. ' tried to get access to other users subsricriptions:'.$decodeduser);
           show_error('permission denied',403);
       }

       $subscribtionOwner = $this->em->getRepository("models\User")->findOneBy(array('username'=>$decodeduser));
       if(empty($subscribtionOwner))
       {
           show_error('not found',404);
       }
       $data['subscriber']['username'] = $subscribtionOwner->getUsername();
       $data['subscriber']['fullname'] = $subscribtionOwner->getFullname();
       $data['subscriber']['email'] = $subscribtionOwner->getEmail();
       $n = $subscribtionOwner->getSubscriptions();
       $row[] = array('',lang('subscrtype'),lang('rr_relatedto'),lang('rr_deliverytype'),lang('subscrmail'),lang('subscrstatus'),lang('updatedat'),'');
       if(! $n->count()>0)
       {
         $data['warnmessage'] = lang('nousersubscrfound');
       }
       else
       {
         $this->load->helper('shortcodes');
         $mappedTypes = notificationCodes();
         $i = 0;
         foreach($n as $v)
         {
             $status = '';
             $isEnabled = $v->getEnabled();
             $isApproved = $v->getApproved();
             $type = $v->getType();
             if(isset($mappedTypes[''.$type.'']))
             {
                $type = lang(''.$mappedTypes[''.$type.'']['desclang'].'');
             }
             $relatedto = '';
             if($v->getProvider())
             {
                 $relatedto = lang('rr_provider').': '.$v->getProvider()->getEntitId();
             }
             elseif($v->getFederation())
             {
                 $relatedto =  lang('rr_federation').': '.$v->getFederation()->getName();
             }
             else
             {
                 $relatedto = 'Any';
             }
             $date = date('Y-m-d H:i:s', $v->getUpdatedAt()->format('U')+ j_auth::$timeOffset);
             if($isEnabled && $isApproved)
             {
                $status = lang('subscisactive');
             }
             else
             {
                
                if(!$isEnabled)
                {
                   $status .= lang('subscdisabled').'; ';
                }
                if(!$isApproved)
                {
                   $status .= lang('subscnotapproved');
                }

             }
             $button = '<button type="button" value="'.$v->getId().'" class="updatenotifactionstatus editbutton">update</button>';
             $row[] = array(++$i,$type,$relatedto,$v->getNotificationType(),$v->getRcpt(),$status,$date,$button);
         }
       }

       $data['rows'] = $row;
       if($isAdmin)
       {
           $data['statusdropdown'] = array('approve'=>lang('rr_approve'),'disapprove'=>lang('rr_disapprove'),'enable'=>lang('rr_enable'),'disable'=>lang('rr_disable'),'remove'=>lang('rr_remove'));
       }
       else
       {
           $data['statusdropdown'] = array('enable'=>lang('rr_enable'),'disable'=>lang('rr_disable'),'remove'=>lang('rr_remove'));
       }

       $data['content_view'] = 'notifications/usernotifications_view';
       $this->load->view('page',$data);

    }

    public function updatestatus($id=null)
    {
        if (!$this->input->is_ajax_request() or empty($id) or !is_numeric($id) or $_SERVER['REQUEST_METHOD'] !== 'POST')
        {
           set_status_header(403);
           echo 'denied';
           return;
        }
        $loggedin = $this->j_auth->logged_in();
        if(!$loggedin)
        {
           set_status_header(403);
           echo 'not loggedin';
           return;
        } 
       
        $noteid = $this->input->post('noteid');
        $status = htmlentities($this->input->post('status'));
        if(empty($noteid) or !is_numeric($noteid) or strcmp($noteid,$id) != 0)
        {
           set_status_header(403);
           echo 'denied';
           return;
        }
        $allowedStatus = array('remove','approve','enable','disable','disapprove');
        if(!in_array($status,$allowedStatus))
        {
           set_status_header(403);
           echo 'denied';
           return;
        }
        $notification = $this->em->getRepository("models\NotificationList")->findOneBy(array('id'=>$noteid));
        if(empty($notification))
        {
           set_status_header(404);
           echo 'not found';
           return;
        }
        
        $user = $this->em->getRepository("models\User")->findOneBy(array('username'=>$this->j_auth->current_user()));
        if(empty($user))
        {
           set_status_header(404);
           echo 'not found';
           return;
        }
        $isAdministrator=$this->j_auth->isAdministrator();
        $notificationOwner = $notification->getSubscriber();
        $userMatchOwner = ($notificationOwner->getId() === $user->getId());
        if(!(($userMatchOwner) or ($isAdministrator)))
        {
           set_status_header(403);
           echo 'denied';
           return;

        }
        if($userMatchOwner && (strcmp($status,'remove') === 0 or strcmp($status,'disable') === 0 or strcmp($status,'enable') === 0))
        {
             if(strcmp($status,'remove') === 0)
             {
                $this->em->remove($notification);
                $this->em->flush();
                echo 'R';
             }
             elseif(strcmp($status,'disable') === 0)
             {
                $notification->setEnabled(false);
                $this->em->persist($notification);
                $this->em->flush();
                echo 'D';
             }
             elseif(strcmp($status,'enable') === 0)
             {
                $notification->setEnabled(true);
                $this->em->persist($notification);
                $this->em->flush();
                echo 'E';
             }

        }
        elseif($isAdministrator)
        {
             if(strcmp($status,'remove') === 0)
             {
                $this->em->remove($notification);
                $this->em->flush();
                echo 'R';
             }
             elseif(strcmp($status,'disable') === 0)
             {
                $notification->setEnabled(false);
                $this->em->persist($notification);
                $this->em->flush();
                echo 'D';
             }
             elseif(strcmp($status,'enable') === 0)
             {
                $notification->setEnabled(true);
                $this->em->persist($notification);
                $this->em->flush();
                echo 'E';
             }
             elseif(strcmp($status,'approve') === 0)
             {
                $notification->setApproved(true);
                $this->em->persist($notification);
                $this->em->flush();
                echo 'A';
             }
             elseif(strcmp($status,'disapprove') === 0)
             {
                $notification->setApproved(false);
                $this->em->persist($notification);
                $this->em->flush();
                echo 'DA';
             }
            

        }

    }




    
}
