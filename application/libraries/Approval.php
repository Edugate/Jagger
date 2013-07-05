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

    function __construct() {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
    }

    public function addToQueue($obj, $action) {
        $queue = new models\Queue();
        if ($obj instanceof models\Federation) {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $_SESSION['username']));
            $queue->addFederation($obj->convertToArray());
            /**
             * @todo decide if to verify action value 
             */
            $queue->setAction($action);
            $queue->setName($obj->getName());
            $queue->setEmail($user->getEmail());
            $queue->setCreator($user);
            $queue->setToken();
        }
        return $queue;
    }
    /**
     * 
     */
    public function invitationProviderToQueue(models\Federation $federation ,models\Provider $obj,$action)
    {
           $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $_SESSION['username']));
           $queue = new models\Queue();
           $queue->setRecipient($obj->getId());
           $queue->setRecipientType('provider');
	   $queue->setCreator($user);
           $queue->setName($federation->getName());
           $queue->setEmail($user->getEmail());
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
    public function removeProviderToQueue(models\Federation $federation ,models\Provider $obj,$action)
    {
           $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $_SESSION['username']));
           $queue = new models\Queue();
           $queue->setRecipient($obj->getId());
           $queue->setRecipientType('provider');
	   $queue->setCreator($user);
           $queue->setName($federation->getName());
           $queue->setEmail($user->getEmail());
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
    public function invitationFederationToQueue(models\Provider $provider ,models\Federation $obj,$action)
    {
          $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $_SESSION['username']));
          $queue = new models\Queue();
          $queue->setRecipient($obj->getId());
          $queue->setRecipientType('federation');
          $queue->setCreator($user);
          $providername = $provider->getName();
          if(empty($providername))
          {
             $providername = $provider->getEntityId();
          }
          $queue->setName($providername);
          $queue->setEmail($user->getEmail());
          $prov = array('id'=>$provider->getId(), 'name'=>$providername, 'entityid'=>$provider->getEntityId());
          if($action == 'Join')
          {
             $queue->inviteFederation($prov);
          }
          $queue->setToken();
          $this->em->persist($queue);
          $this->em->flush();
          return $queue;
    }

}

