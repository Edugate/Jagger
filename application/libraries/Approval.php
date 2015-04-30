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
 * Approval Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class Approval {

    protected $ci;
    protected $em;
    protected $user;

    function __construct() {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->ci->session->userdata('username')));

    }

    /**
     * @param $obj
     * @param $action
     * @return \models\Queue
     */
    public function addToQueue($obj, $action) {
        log_message('debug',__METHOD__.': obj: '.get_class($obj).' , action: '.$action);
        $queue = new models\Queue();
        if ($obj instanceof models\Federation) {
            $queue->addFederation($obj->convertToArray());
            /**
             * @todo decide if to verify action value 
             */
            $queue->setAction($action);
            $queue->setName($obj->getName());
            $queue->setEmail($this->user->getEmail());
            $queue->setCreator($this->user);
            $queue->setToken();
        }
        return $queue;
    }

    /**
     * @param \models\Coc $coc
     * @param \models\Provider $provider
     * @return \models\Queue
     */
    public function applyForEntityCategory(models\Coc $coc, models\Provider $provider)
    {
          $q = new models\Queue();
          $q->setRecipient($coc->getId());
          $q->setRecipientType('entitycategory');
          $q->setCreator($this->user);
          $q->setName($provider->getEntityId());
          $q->setEmail($this->user->getEmail());
          $q->setConfirm(TRUE);
          $q->setAction('APPLY');
          $q->setType('Provider');
          $q->setObjectType('n');
          $q->setObject(array());
          $q->setToken();
          $this->em->persist($q);
          return $q;
    }

    /**
     * @param \models\Coc $coc
     * @param \models\Provider $provider
     * @return \models\Queue
     */
    public function applyForRegistrationPolicy(models\Coc $coc, models\Provider $provider)
    {
          $q = new models\Queue();
          $q->setRecipient($coc->getId());
          $q->setRecipientType('regpolicy');
          $q->setCreator($this->user);
          $q->setName($provider->getEntityId());
          $q->setEmail($this->user->getEmail());
          $q->setConfirm(TRUE);
          $q->setAction('APPLY');
          $q->setType('Provider');
          $q->setObjectType('n');
          $q->setObject(array());
          $q->setToken();
          $this->em->persist($q);
          return $q;
    }

    /**
     * @param \models\Federation $federation
     * @param \models\Provider $obj
     * @param $action
     * @return \models\Queue
     */
    public function invitationProviderToQueue(models\Federation $federation ,models\Provider $obj,$action)
    {
           $queue = new models\Queue();
           $queue->setRecipient($obj->getId());
           $queue->setRecipientType('provider');
	   $queue->setCreator($this->user);
           $queue->setName($federation->getName());
           $queue->setEmail($this->user->getEmail());
           $fed = array('id'=>$federation->getId(), 'name'=>$federation->getName(), 'urn'=>$federation->getUrn());
           if($action == 'Join')
           {
                $queue->inviteProvider($fed);
           }
           $queue->setToken();
           $this->em->persist($queue);
           $this->em->flush();
           return $queue;
    }

    /**
     * @param \models\Federation $federation
     * @param \models\Provider $obj
     * @param $action
     * @return \models\Queue
     */
    public function removeProviderToQueue(models\Federation $federation ,models\Provider $obj,$action)
    {
           $queue = new models\Queue();
           $queue->setRecipient($obj->getId());
           $queue->setRecipientType('provider');
	   $queue->setCreator($this->user);
           $queue->setName($federation->getName());
           $queue->setEmail($this->user->getEmail());
           $fed = array('id'=>$federation->getId(), 'name'=>$federation->getName(), 'urn'=>$federation->getUrn());
           if($action == 'Leave')
           {
                $queue->leaveProvider($fed);
           }
           $queue->setToken();
           $this->em->persist($queue);
           $this->em->flush();
           return $queue;
    }

    /**
     * @param \models\Provider $provider
     * @param \models\Federation $obj
     * @param $action
     * @param null $message
     * @return \models\Queue
     */
    public function invitationFederationToQueue(models\Provider $provider ,models\Federation $obj,$action,$message = null)
    {

          $queue = new models\Queue();
          $queue->setRecipient($obj->getId());
          $queue->setRecipientType('federation');
          $queue->setCreator($this->user);
          $providername = $provider->getName();
          if(empty($providername))
          {
             $providername = $provider->getEntityId();
          }
          $queue->setName($providername);
          $queue->setEmail($this->user->getEmail());
          $prov = array('id'=>$provider->getId(), 'name'=>$providername, 'entityid'=>$provider->getEntityId(),'message'=>''.$message.'');
          if($action == 'Join')
          {
             $queue->inviteFederation($prov);
          }
          $queue->setToken();
          $this->em->persist($queue);
          $this->em->flush();
          return $queue;
    }

    /**
     * @param \models\Federation $federation
     * @return \models\Queue
     */
    public function removeFederation(models\Federation $federation)
    {
         $queue = new models\Queue();
         $queue->setCreator($this->user);
         $queue->setName($federation->getName());
         $queue->setEmail($this->user->getEmail());
         $queue->setAction('Delete');
         $queue->addFederation($federation->convertToArray());
         $queue->setToken();
         return $queue;
    }

}

