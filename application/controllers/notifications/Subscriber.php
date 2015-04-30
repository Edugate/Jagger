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
class Subscriber extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper('shortcodes');
    }

    private function getSubscriptionsToJson(models\User $subscriptionOwner)
    {
        /**
         * @var $userSubscriptions models\NotificationList[]
         */
        $userSubscriptions = $subscriptionOwner->getSubscriptions();
        $result = array();

        foreach ($userSubscriptions as $subscription) {
            $prov = $subscription->getProvider();
            if (empty($prov)) {
                $provid = null;
                $provname = null;
            } else {
                $provid = $prov->getId();
                $provname = $prov->getEntityId();
            }
            $fed = $subscription->getFederation();
            if (empty($fed)) {
                $federationId = null;
                $federationName = null;
            } else {
                $federationId = $fed->getId();
                $federationName = $fed->getName();
            }
            $isApproved = $subscription->getApproved();
            $isEnabled = $subscription->getEnabled();
            $status = '';
            if ($isEnabled && $isApproved) {
                $status = lang('subscisactive');
            } else {

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
                'providerid' => $provid,
                'providername' => $provname,
                'federationid' => $federationId,
                'federationname' => $federationName,
                'rcptto' => $subscription->getRcpt(),
                'email' => '' . $subscription->getAltEmail() . '',
                'enabled' => '' . $subscription->getEnabled() . '',
                'approved' => '' . $subscription->getApproved() . '',
                'updated' => '' . date('Y-m-d H:i:s', $subscription->getUpdatedAt()->format('U') + j_auth::$timeOffset) . '',
                'langstatus' => $status,
                'langprovider' => lang('rr_provider'),
                'langfederation' => lang('rr_federation'),
                'langany' => lang('rr_any')
            );
        }
        return json_encode($result);
    }

    public function mysubscriptions($encodeduser = null)
    {
        if (empty($encodeduser)) {
            show_error('not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
            return;
        }
        $this->load->library('zacl');
        $decodeduser = base64url_decode($encodeduser);
        $loggeduser = $this->j_auth->current_user();
        if (empty($loggeduser)) {
            log_message('warning', 'User logged in but missing username in sesssion');
            show_error('permission denied', 403);
        }
        $isAdmin = $this->j_auth->isAdministrator();
        if (strcasecmp($decodeduser, $loggeduser) != 0 && $isAdmin !== TRUE) {
            log_message('warning', __METHOD__ . ': User ' . $loggeduser . ' tried to get access to other users subsricriptions:' . $decodeduser);
            show_error('permission denied', 403);
        }

        /**
         * @var $subscribtionOwner models\User
         */
        $subscribtionOwner = $this->em->getRepository("models\User")->findOneBy(array('username' => $decodeduser));
        if (empty($subscribtionOwner)) {
            show_error('not found', 404);
        }
        $data['encodeduser'] = $encodeduser;
        $data['subscriber']['username'] = $subscribtionOwner->getUsername();
        $data['subscriber']['fullname'] = $subscribtionOwner->getFullname();
        $data['subscriber']['email'] = $subscribtionOwner->getEmail();
        $data['titlepage'] = $data['subscriber']['username'];
        $data['subtitlepage'] = lang('title_usersubscriptions');
        $accessListUsers = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$accessListUsers)
        {
            $data['breadcrumbs'] = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist'), 'type' => 'unavailable'),
                array('url' => base_url('manage/users/show/'.$encodeduser.''), 'name' => html_escape($subscribtionOwner->getUsername())),
                array('url' => base_url('#'), 'name' => lang('title_usersubscriptions'), 'type' => 'current')
            );
        }
        else
        {
            $data['breadcrumbs'] = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist')),
                array('url' => base_url('manage/users/show/'.$encodeduser.''), 'name' => html_escape($subscribtionOwner->getUsername()),),
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
                $button = '<a href="#" value="' . $v['id'] . '" class="updatenotifactionstatus"  data-reveal-id="notificationupdatemodal"><i class="fi-pencil"></i></a>';
                $row[] = array(++$i, $type, $relatedto, $v['delivery'], $v['rcptto'], '<div class="subscrstatus">' . $v['langstatus'] . '</div>', $v['updated'], $button);
            }
        }

        $data['rows'] = $row;
        if ($isAdmin) {
            $data['statusdropdown'] = array('approve' => lang('rr_approve'), 'disapprove' => lang('rr_disapprove'), 'enable' => lang('rr_enable'), 'disable' => lang('rr_disable'), 'remove' => lang('rr_remove'));
        } else {
            $data['statusdropdown'] = array('enable' => lang('rr_enable'), 'disable' => lang('rr_disable'), 'remove' => lang('rr_remove'));
        }

        $data['content_view'] = 'notifications/usernotifications_view';
        $this->load->view('page', $data);
    }

    public function add($encodeduser = null)
    {
        if (!$this->input->is_ajax_request() || empty($encodeduser)) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo 'not loggedin';
            return;
        }
        $username = $this->j_auth->current_user();
        if (!empty($username)) {
            /**
             * @var $user models\User
             */
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $username . ''));
        }
        if (empty($user)) {
            set_status_header(403);
            echo 'error occured';
            return;
        }
        $isAdministator = $this->j_auth->isAdministrator();
        $decodeduser = base64url_decode($encodeduser);
        $requetmatchuser = (boolean)(strcmp($username, $decodeduser) == 0);
        if (!($isAdministator || $requetmatchuser)) {
            set_status_header(403);
            echo 'mismatch error occured';
            return;
        }
        if ($requetmatchuser) {
            $subscriber = &$user;
        } else {
            $subscriber = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $decodeduser . ''));
            if (empty($subscriber)) {
                set_status_header(403);
                echo 'error occured';
                return;
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
        if (strcmp($ntype, 'joinfedreq') == 0 || strcmp($ntype, 'fedmemberschanged') == 0) {
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
        } elseif (strcmp($ntype, 'requeststoproviders') == 0) {
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
        return;
    }

    public function updatestatus($id = null)
    {
        if (!$this->input->is_ajax_request() || empty($id) || !is_numeric($id) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo 'not loggedin';
            return;
        }

        $noteid = $this->input->post('noteid');
        $status = htmlentities($this->input->post('status'));
        if (empty($noteid) || !is_numeric($noteid) || strcmp($noteid, $id) != 0) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $allowedStatus = array('remove', 'approve', 'enable', 'disable', 'disapprove');
        if (!in_array($status, $allowedStatus)) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        /**
         * @var $notification models\NotificationList
         */
        $notification = $this->em->getRepository("models\NotificationList")->findOneBy(array('id' => $noteid));
        if (empty($notification)) {
            set_status_header(404);
            echo 'not found';
            return;
        }

        /**
         * @var $user models\User
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->j_auth->current_user()));
        if (empty($user)) {
            set_status_header(404);
            echo 'not found';
            return;
        }
        $isAdministrator = $this->j_auth->isAdministrator();
        $notificationOwner = $notification->getSubscriber();
        $userMatchOwner = ($notificationOwner->getId() === $user->getId());
        if (!(($userMatchOwner) || ($isAdministrator))) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $success = false;
        if ($userMatchOwner && (strcmp($status, 'remove') === 0 || strcmp($status, 'disable') === 0 || strcmp($status, 'enable') === 0)) {
            if (strcmp($status, 'remove') === 0) {
                $this->em->remove($notification);
                $this->em->flush();
                $success = true;
            } elseif (strcmp($status, 'disable') === 0) {
                $notification->setEnabled(false);
                $this->em->persist($notification);
                $this->em->flush();
                $success = true;
            } elseif (strcmp($status, 'enable') === 0) {
                $notification->setEnabled(true);
                $this->em->persist($notification);
                $this->em->flush();
                $success = true;
            }
        } elseif ($isAdministrator) {
            if (strcmp($status, 'remove') === 0) {
                $this->em->remove($notification);
                $this->em->flush();
                $success = true;
            } elseif (strcmp($status, 'disable') === 0) {
                $notification->setEnabled(false);
                $this->em->persist($notification);
                $this->em->flush();
                $success = true;
            } elseif (strcmp($status, 'enable') === 0) {
                $notification->setEnabled(true);
                $this->em->persist($notification);
                $this->em->flush();
                $success = true;
            } elseif (strcmp($status, 'approve') === 0) {
                $notification->setApproved(true);
                $this->em->persist($notification);
                $this->em->flush();
                $success = true;
            } elseif (strcmp($status, 'disapprove') === 0) {
                $notification->setApproved(false);
                $this->em->persist($notification);
                $this->em->flush();
                $success = true;
            }
        }
        if ($success) {
            $refreshed = $this->getSubscriptionsToJson($notificationOwner);
            $this->output->set_content_type('application/json');
            echo $refreshed;
        }
    }

}
