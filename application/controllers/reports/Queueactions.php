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
class Queueactions extends MY_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
    }
    
    /**
     * @return bool
     */
    private function submitValidate() {
        $this->form_validation->set_rules('qaction', 'Action', 'trim|required');
        $this->form_validation->set_rules('qid', 'Queue id', 'trim|required|numeric');
        return $this->form_validation->run();
    }

    public function reject() {
        $loggedin = $this->jauth->isLoggedIn();
        $isAjax = $this->input->is_ajax_request();
        if ($loggedin && $isAjax) {
            $this->load->library(array('zacl', 'j_queue', 'jqueueaccess'));
        } else {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        if ($this->submitValidate() !== true) {
            return $this->output->set_status_header(403)->set_output('Invalid post');
        }

        $isAdmin = $this->jauth->isAdministrator();
        $qaction = $this->input->post('qaction');
        $qid = $this->input->post('qid');
        if ($qaction !== 'reject') {
            return $this->output->set_status_header(403)->set_output('Invalid action');
        }

        /**
         * @var models\Queue $queueObj
         */
        $queueObj = $this->em->getRepository("models\Queue")->findOneBy(array('id' => $qid));

        $notification = $this->config->item('notify_if_queue_rejected');

        if ($queueObj === null) {
            $error_message = 'ID: ' . $qid . ' doesnt exist in queue';
            return $this->output->set_status_header(403)->set_output($error_message);
        }

        $queueAction = $queueObj->getAction();
        $creator = $queueObj->getCreator();
        $hasRejectAccess = $this->jqueueaccess->hasQAccess($queueObj);
        $recipienttype = $queueObj->getRecipientType();
        $queueObjType = strtolower($queueObj->getType());
        if (!empty($creator)) {
            $hasRejectAccess = (strcasecmp($creator->getUsername(), $this->jauth->getLoggedinUsername()) == 0);
        }
        if($isAdmin){
            $hasRejectAccess = true;
        }
        if ($hasRejectAccess === false) {
            if (strcasecmp($queueAction, 'Create') == 0) {
                if (strcasecmp($queueObjType, 'idp') == 0 || strcasecmp($queueObjType, 'sp') == 0) {
                    $hasRejectAccess = $this->jqueueaccess->hasApproveByFedadmin($queueObj) || $this->zacl->check_acl($queueObjType, 'create', 'entity', '');
                } elseif (strcasecmp($queueObj->getType(), 'Federation') == 0) {
                    $hasRejectAccess = $this->zacl->check_acl('federation', 'create', 'default', '');
                }
            } elseif (strcasecmp($queueAction, 'Join') == 0) {
                $recipient = $queueObj->getRecipient();
                if (!empty($recipienttype) && !empty($recipient)) {
                    if ($recipienttype === 'provider') {
                        $hasRejectAccess = $this->zacl->check_acl($recipient, 'write', 'entity', '');
                    } elseif ($recipienttype == 'federation') {
                        $hasRejectAccess = $this->zacl->check_acl('f_' . $recipient, 'write', 'federation', '') || $this->zacl->check_acl('f_' . $recipient, 'approve', 'federation', '');
                    }
                }
            } elseif (strcasecmp($queueAction, 'Delete') == 0) {
                $type = $queueObj->getType();
                if (strcasecmp($type, 'Federation') == 0) {
                    if ($isAdmin) {
                        $hasRejectAccess = true;
                    }
                }
            } elseif (strcasecmp($queueAction, 'apply') == 0 && strcasecmp($recipienttype, 'entitycategory') == 0) {
                if ($isAdmin) {
                    $hasRejectAccess = true;
                }
            } elseif (strcasecmp($queueAction, 'apply') == 0 && strcasecmp($recipienttype, 'regpolicy') == 0) {
                if ($isAdmin) {
                    $hasRejectAccess = true;
                }
            }
        }
        $qtoken = $queueObj->getToken();
        if ($hasRejectAccess !== true) {
            return $this->output->set_status_header(403)->set_output(lang('rerror_noperm_reject'));
        }

        $additionalRcpts = array();
        $mCreator = $queueObj->getCreator();
        if (!empty($mCreator)) {
            $additionalRcpts[] = $mCreator->getEmail();
        } else {
            $additionalRcpts[] = $queueObj->getEmail();
        }

        $subject = 'Request has been canceled/rejected';
        $body = 'Hi,' . PHP_EOL .
            'The request placed on ' . base_url() . PHP_EOL .
            'Request with tokenID: ' . $queueObj->getToken() . ' has been canceled/rejected' . PHP_EOL;
        log_message('info', 'JAGGER: Queue with token:' . $queueObj->getToken() . ' has been canceled/rejected by ' . $this->jauth->getLoggedinUsername());
        $this->em->remove($queueObj);
        if ($notification === true) {
            $this->emailsender->addToMailQueue(array(), null, $subject, $body, $additionalRcpts, false);
        }
        try {
            $this->em->flush();

        } catch (\Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
        return $this->output->set_status_header(200)->set_content_type('application/json')->set_output(json_encode(array('status' => 'OK', 'message' => 'Request with tokenID ' . $qtoken . ' has been removed from queue')));
    }

}
