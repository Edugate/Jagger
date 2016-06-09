<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

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
class Subscriber extends MY_Controller
{

    public function __construct() {
        parent::__construct();
    }

    private function getSubscriptionsToJson(models\User $subscriptionOwner) {
        /**
         * @var $userSubscriptions models\NotificationList[]
         */
        $userSubscriptions = $subscriptionOwner->getSubscriptions();
        $result = array();

        foreach ($userSubscriptions as $subscription) {
            $provider = $subscription->getProvider();
            $providerId = null;
            $providerEntityId = null;
            if (!empty($provider)) {
                $providerId = $provider->getId();
                $providerEntityId = $provider->getEntityId();
            }
            $federationId = null;
            $federationName = null;
            $federation = $subscription->getFederation();
            if (!empty($federation)) {
                $federationId = $federation->getId();
                $federationName = $federation->getName();
            }
            $isApproved = $subscription->getApproved();
            $isEnabled = $subscription->getEnabled();
            if ($isEnabled && $isApproved) {
                $status = lang('subscisactive');
            } else {
                $status = '';
                if (!$isEnabled) {
                    $status .= lang('subscdisabled') . '; ';
                }
                if (!$isApproved) {
                    $status .= lang('subscnotapproved');
                }
            }
            $result[] = array(
                'id' => $subscription->getId(),
                'delivery' => $subscription->getNotificationType(),
                'type' => $subscription->getType(),
                'langtype' => lang($subscription->getType()),
                'providerid' => $providerId,
                'providername' => $providerEntityId,
                'federationid' => $federationId,
                'federationname' => $federationName,
                'rcptto' => $subscription->getRcpt(),
                'email' => '' . $subscription->getAltEmail() . '',
                'enabled' => '' . $subscription->getEnabled() . '',
                'approved' => '' . $subscription->getApproved() . '',
                'updated' => '' . jaggerDisplayDateTimeByOffset($subscription->getUpdatedAt(),jauth::$timeOffset),
                'langstatus' => $status,
                'langprovider' => lang('rr_provider'),
                'langfederation' => lang('rr_federation'),
                'langany' => lang('rr_any')
            );
        }
        return json_encode($result);
    }

    /**
     * @param $username
     * @return \models\User
     * @throws Exception
     */
    private function getSubscriberOwner($encodedusername = null){
        if($encodedusername === null){
            throw new Exception('missing username');
        }
        $username = base64url_decode($encodedusername);
        $loggeduser = $this->jauth->getLoggedinUsername();
        $isAdmin = $this->jauth->isAdministrator();
        if ($isAdmin !== true && strcasecmp($username, $loggeduser) != 0) {
            log_message('warning', __METHOD__ . ': User ' . $loggeduser . ' tried to get access to other users subsricriptions:' . $username);
            throw new Exception('permission denied');
        }

        /**
         * @var $subscribtionOwner models\User
         */
        $subscribtionOwner = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if ($subscribtionOwner === null) {
            throw new Exception('not found');
        }
        return $subscribtionOwner;
    }

    public function mysubscriptions($encodeduser = null) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        try {
            $subscribtionOwner = $this->getSubscriberOwner($encodeduser);
        }
        catch(Exception $e){
            show_error(403, $e->getMessage());
        }
        $data = array(
            'encodeduser' => $encodeduser,
            'subscriber' => array(
                'username' => $subscribtionOwner->getUsername(),
                'fullname' => $subscribtionOwner->getFullname(),
                'email' => $subscribtionOwner->getEmail(),
            ),
            'titlepage' => $subscribtionOwner->getUsername(),
            'subtitlepage' => lang('title_usersubscriptions')
        );


        $accessListUsers = $this->zacl->check_acl('', 'read', 'user', '');
        $data['breadcrumbs'] = array(
            array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist')),
            array('url' => base_url('manage/users/show/' . $encodeduser . ''), 'name' => html_escape($subscribtionOwner->getUsername()),),
            array('url' => base_url('#'), 'name' => lang('title_usersubscriptions'), 'type' => 'current')
        );
        if (!$accessListUsers) {
            $data['breadcrumbs'] = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist'), 'type' => 'unavailable'),
                array('url' => base_url('manage/users/show/' . $encodeduser . ''), 'name' => html_escape($subscribtionOwner->getUsername())),
                array('url' => base_url('#'), 'name' => lang('title_usersubscriptions'), 'type' => 'current')
            );
        }

        $n = json_decode($this->getSubscriptionsToJson($subscribtionOwner), true);
        $row[] = array('', lang('subscrtype'), lang('rr_relatedto'), lang('rr_deliverytype'), lang('subscrmail'), lang('subscrstatus'), lang('updatedat'), '');
        if (!count($n) > 0) {
            $data['warnmessage'] = lang('nousersubscrfound');
        } else {
            $this->load->helper('shortcodes');
            $mappedTypes = notificationCodes();
            $i = 0;
            foreach ($n as $v) {
                $type = $v['type'];
                if (isset($mappedTypes['' . $type . ''])) {
                    $type = lang('' . $mappedTypes['' . $type . '']['desclang'] . '');
                }
                if ($v['providerid']) {
                    $relatedto = $v['langprovider'] . ': ' . $v['providername'];
                } elseif ($v['federationid']) {
                    $relatedto = $v['langfederation'] . ': ' . $v['federationname'];
                } else {
                    $relatedto = $v['langany'];
                }
                $button = '<a href="#" value="' . $v['id'] . '" class="updatenotifactionstatus"  data-open="notificationupdatemodal"><i class="fa fa-pencil"></i></a>';
                $row[] = array(++$i, $type, $relatedto, $v['delivery'], $v['rcptto'], '<div class="subscrstatus">' . $v['langstatus'] . '</div>', $v['updated'], $button);
            }
        }

        $data['rows'] = $row;
        if ($this->jauth->isAdministrator()) {
            $data['statusdropdown'] = array('approve' => lang('rr_approve'), 'disapprove' => lang('rr_disapprove'), 'enable' => lang('rr_enable'), 'disable' => lang('rr_disable'), 'remove' => lang('rr_remove'));
        } else {
            $data['statusdropdown'] = array('enable' => lang('rr_enable'), 'disable' => lang('rr_disable'), 'remove' => lang('rr_remove'));
        }

        $data['content_view'] = 'notifications/usernotifications_view';
        $this->load->view(MY_Controller::$page, $data);
    }

    public function add($encodeduser = null) {

        if ($encodeduser === null || !$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('denied');
        }
        $username = $this->jauth->getLoggedinUsername();
        $user = null;
        if (!empty($username)) {
            /**
             * @var $user models\User
             */
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $username . ''));
        }
        if ($user === null) {
            return $this->output->set_status_header(403)->set_output('error occured');
        }
        $isAdministator = $this->jauth->isAdministrator();
        $decodeduser = base64url_decode($encodeduser);
        $requetmatchuser = (boolean)(strcmp($username, $decodeduser) == 0);
        if (!($isAdministator || $requetmatchuser)) {
            return $this->output->set_status_header(403)->set_output('mismatch error occured');
        }
        if ($requetmatchuser) {
            $subscriber = $user;
        } else {
            $subscriber = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $decodeduser . ''));
            if (empty($subscriber)) {
                return $this->output->set_status_header(403)->set_output('error occured');
            }
        }

        $this->load->library('zacl');

        $ntype = trim(htmlentities($this->input->post('type')));
        $nprovider = trim($this->input->post('sprovider'));
        if (!empty($nprovider) && !ctype_digit($nprovider)) {
            echo '<div class="error">incorrect provider</div>';
        }
        $nfederation = trim($this->input->post('sfederation'));
        if (!empty($nfederation) && !ctype_digit($nfederation)) {
            echo '<div class="error">incorrect federation</div>';
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules('semail', '' . lang('rr_contactemail') . '', 'trim|min_length[3]|max_length[255]|valid_email');
        if ($this->form_validation->run() === FALSE) {
            echo validation_errors('<div class="error">', '</div>');
            return;
        }
        $nemail = trim($this->input->post('semail'));

        $codes = notificationCodes();
        if (!array_key_exists($ntype, $codes)) {
            echo '<div class="error">' . lang('error_wrongnotifycationtype') . '</div>';
            return;
        }
        ///////////////////////////////////////////////////////
        if (strcmp($ntype, 'joinfedreq') == 0 || strcmp($ntype, 'fedmemberschanged') == 0 || strcmp($ntype, 'fedmembersmodified') == 0) {
            /**
             * @var $federation models\Federation
             */
            if (!empty($nfederation)) {
                $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $nfederation));
            }
            if (empty($federation)) {
                echo '<div class="error">' . lang('error_fednotfound') . '</div>';
                return;
            }
            $has_write_access = $this->zacl->check_acl('f_' . $federation->getId() . '', 'write', 'federation', '');
            $notification = new models\NotificationList();
            $notification->setSubscriber($subscriber);
            $notification->setType($ntype);
            $notification->setFederation($federation);
            if (!empty($nemail)) {
                $notification->setEmail($nemail);
            }
            $notification->setEnabled(TRUE);
            if ($has_write_access) {
                $notification->setApproved(TRUE);
            }
            $this->em->persist($notification);
            $this->em->flush();
            echo "OK";
        } elseif (strcmp($ntype, 'requeststoproviders') == 0 || strcmp($ntype, 'providermodified') == 0) {
            /**
             * @var $provider models\Provider
             */
            if (!empty($nprovider)) {
                $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $nprovider));
            }
            if (empty($provider)) {
                echo '<div class="error">' . lang('rerror_providernotexist') . '</div>';
                return;
            }
            $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity');
            $notification = new models\NotificationList();
            $notification->setSubscriber($subscriber);
            $notification->setType($ntype);
            $notification->setProvider($provider);
            if (!empty($nemail)) {
                $notification->setEmail($nemail);
            }
            $notification->setEnabled(TRUE);
            if ($has_write_access) {
                $notification->setApproved(TRUE);
            }
            $this->em->persist($notification);
            $this->em->flush();
            echo "OK";
        } elseif (array_key_exists($ntype, $codes)) {
            $notification = new models\NotificationList();
            $notification->setSubscriber($subscriber);
            $notification->setType($ntype);
            if (!empty($nemail)) {
                $notification->setEmail($nemail);
            }
            $notification->setEnabled(TRUE);

            if ($isAdministator) {
                $notification->setApproved(TRUE);
            }
            $this->em->persist($notification);
            $this->em->flush();
            echo "OK";
        } else {
            echo '<div class="error">' . lang('unknownerror') . '</div>';
        }

    }

    public function updatestatus($id = null) {
        $method = $this->input->method(true);
        if ($method !== 'POST' || !ctype_digit($id) ||  !$this->input->is_ajax_request() ) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('not logged in');
        }

        $noteid = $this->input->post('noteid');
        $status = htmlentities($this->input->post('status'));
        if (empty($noteid) || !is_numeric($noteid) || strcmp($noteid, $id) != 0) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        $allowedStatus = array('remove', 'approve', 'enable', 'disable', 'disapprove');
        if (!in_array($status, $allowedStatus,true)) {
            return $this->output->set_status_header(403)->set_output('Denied');

        }
        /**
         * @var $notification models\NotificationList
         */
        $notification = $this->em->getRepository("models\NotificationList")->findOneBy(array('id' => $noteid));
        if ($notification === null) {
            return $this->output->set_status_header(404)->set_output('not found');
        }

        /**
         * @var $user models\User
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->jauth->getLoggedinUsername()));
        if ($user === null) {
            return $this->output->set_status_header(404)->set_output('not found');
        }
        $isAdministrator = $this->jauth->isAdministrator();
        $notificationOwner = $notification->getSubscriber();
        $userMatchOwner = ($notificationOwner->getId() === $user->getId());
        if (!(($userMatchOwner) || ($isAdministrator))) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        $success = false;
        if ($userMatchOwner && (strcmp($status, 'remove') == 0 || strcmp($status, 'disable') == 0 || strcmp($status, 'enable') == 0)) {
            if (strcmp($status, 'remove') == 0) {
                $this->em->remove($notification);
            } elseif (strcmp($status, 'disable') == 0) {
                $notification->setEnabled(false);
                $this->em->persist($notification);
            } elseif (strcmp($status, 'enable') == 0) {
                $notification->setEnabled(true);
                $this->em->persist($notification);
            }
            $success = true;
        } elseif ($isAdministrator && in_array($status, array('remove', 'disable', 'enable', 'approve', 'disapprove'))) {
            if (strcmp($status, 'remove') === 0) {
                $this->em->remove($notification);
            } elseif (strcmp($status, 'disable') == 0) {
                $notification->setEnabled(false);
                $this->em->persist($notification);
            } elseif (strcmp($status, 'enable') == 0) {
                $notification->setEnabled(true);
                $this->em->persist($notification);
            } elseif (strcmp($status, 'approve') == 0) {
                $notification->setApproved(true);
                $this->em->persist($notification);
            } elseif (strcmp($status, 'disapprove') == 0) {
                $notification->setApproved(false);
                $this->em->persist($notification);
            }
            $success = true;
        }
        if ($success) {
            try {
                $this->em->flush();
                $refreshed = $this->getSubscriptionsToJson($notificationOwner);
                $this->output->set_content_type('application/json');
                echo $refreshed;
            } catch (Exception $e) {
                set_status_header(500);
                echo 'Internal server error';
                log_message('error', __METHOD__ . ' ' . $e);
            }
        }
    }

}
