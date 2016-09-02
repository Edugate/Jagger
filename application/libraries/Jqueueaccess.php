<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Jqueueaccess
{
    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function hasQAccess(\models\Queue $queue) {
        $result = false;
        if ($this->ci->jauth->isAdministrator()) {
            return true;
        }
        $currentUser = $this->ci->jauth->getLoggedinUsername();
        /**
         * @var $creator models\User
         */
        $creator = $queue->getCreator();
        if (!empty($creator) && strcasecmp($creator->getUsername(), $currentUser) == 0) {
            return true;
        }
        $action = $queue->getAction();
        $recipient = $queue->getRecipient();
        $recipientType = $queue->getRecipientType();
        $objType = $queue->getObjType();

        if (strcasecmp($action, 'Join') == 0) {
            if (!empty($recipientType) && strcasecmp($recipientType, 'federation') == 0 && !empty($recipient)) {
                $hasWrite = $this->ci->zacl->check_acl('f_' . $recipient . '', 'write', 'federation', '') || $this->ci->zacl->check_acl('f_' . $recipient . '', 'approve', 'federation', '');

                return $hasWrite;
            }
        } elseif (strcasecmp($action, 'Create') == 0 && strcasecmp($objType, 'Provider') == 0) {


            $objData = $queue->getData();
            $providerData = new models\Provider();
            $providerData->importFromArray($objData);

            $feds = $providerData->getFederations();
            $nofeds = $feds->count();
            if ($nofeds === 1) {

                $firstFed = $feds->first();
                $fedindb = $this->em->getRepository('models\Federation')->findOneBy(array('sysname' => '' . $firstFed->getSysname() . ''));
                if ($fedindb !== null) {
                    return $this->ci->zacl->check_acl('f_' . $fedindb->getId() . '', 'approve', 'federation');
                }
            }
            $result = $this->hasApproveByFedadmin($queue);
        } elseif (strcasecmp($action, 'apply') == 0 && strcasecmp($recipientType, 'entitycategory') == 0) {
            /**
             * @todo decide who can approve entity category request
             */
        } elseif (strcasecmp($action, 'apply') == 0 && strcasecmp($recipientType, 'regpolicy') == 0) {
            /**
             * @todo decide who can approve registration policy request
             */
        }

        return $result;
    }

    public function hasApproveByFedadmin(\models\Queue $queue) {
        if ($this->ci->jauth->isAdministrator()) {
            return true;
        }
        $objData = $queue->getData();
        $providerData = new models\Provider();
        $providerData->importFromArray($objData);
        $feds = $providerData->getFederations();
        $nofeds = $feds->count();
        if ($nofeds === 1) {
            $firstFed = $feds->first();
            $fedindb = $this->em->getRepository('models\Federation')->findOneBy(array('sysname' => '' . $firstFed->getSysname() . ''));
            if ($fedindb !== null) {
                return $this->ci->zacl->check_acl('f_' . $fedindb->getId() . '', 'approve', 'federation');
            }
        }

        return false;
    }

    public function hasApproveAccess(\models\Queue $q) {
        $result = false;
        if ($this->ci->jauth->isAdministrator()) {
            return true;
        }
        $action = $q->getAction();
        $recipient = $q->getRecipient();
        $recipientType = $q->getRecipientType();

        if (strcasecmp($action, 'Join') == 0 && !empty($recipientType)) {
            if (strcasecmp($recipientType, 'federation') == 0 && !empty($recipient)) {
                $result = $this->ci->zacl->check_acl('f_' . $recipient . '', 'write', 'federation', '');
            } elseif (strcasecmp($recipientType, 'provider') == 0 && !empty($recipient)) {
                $result = $this->ci->zacl->check_acl($recipient, 'write', 'provider', '');
            }
        }

        return $result;
    }
}
