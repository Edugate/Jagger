<?php


class Fedactions extends MY_Controller
{
    private $tmp_providers;

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('cert', 'form'));
        MY_Controller::$menuactive = 'fed';
        $this->load->library('j_ncache');
        $this->tmp_providers = new models\Providers;
    }


    function changestatus()
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'access denied';
            return;
        }
        $status = trim($this->input->post('status'));
        $fedname = trim($this->input->post('fedname'));
        if (empty($status) || empty($fedname)) {
            set_status_header(403);
            echo 'missing arguments';
            return;
        }
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => '' . htmlspecialchars(base64url_decode($fedname)) . ''));
        if (empty($federation)) {
            set_status_header(404);
            echo 'Federarion not found';
            return;
        }
        $this->load->library('zacl');
        $has_manage_access = $this->zacl->check_acl('f_' . $federation->getId(), 'manage', 'federation', '');
        if (!$has_manage_access) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $currentStatus = $federation->getActive();
        if ($currentStatus && strcmp($status, 'disablefed') == 0) {
            $federation->setAsDisactive();
            $this->em->persist($federation);
            $this->em->flush();
            echo "deactivated";
            return;
        } elseif (!$currentStatus && strcmp($status, 'enablefed') == 0) {
            $federation->setAsActive();
            $this->em->persist($federation);
            $this->em->flush();
            echo "activated";
            return;
        } elseif (!$currentStatus && strcmp($status, 'delfed') == 0) {
            /**
             * @todo finish
             */
            $this->load->library('Approval');
            $q = $this->approval->removeFederation($federation);
            $this->em->persist($q);
            $this->em->flush();
            echo "todelete";
            return;
        }
        set_status_header(403);
        echo "incorrect params sent";
        return;
    }

    function addbulk($fed_name, $type, $message = null)
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library(array('zacl', 'show_element'));
        $form_elements = array();
        if (strcasecmp($type, 'idp') == 0 || strcasecmp($type, 'sp') == 0) {
            /**
             * @var $federation models\Federation
             */
            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($fed_name)));
            if (empty($federation)) {
                show_error(lang('error_fednotfound'), 404);
            }

            $hasAddbulkAccess = $this->zacl->check_acl('f_'.$federation->getId(), 'addbulk', 'federation', '');
            if (!$hasAddbulkAccess) {
                $data['content_view'] = 'nopermission';
                $data['error'] = lang('rr_noperm');
                return $this->load->view('page', $data);
            }
            $data['federation_name'] = $federation->getName();
            $data['federation_urn'] = $federation->getUrn();
            $data['federation_desc'] = $federation->getDescription();
            $data['federation_is_active'] = $federation->getActive();
            $federationMembers = $federation->getMembers();
            if (strcasecmp($type, 'idp') == 0) {
                $providers = $this->tmp_providers->getIdps();
                $data['subtitlepage'] = lang('rr_addnewidpsnoinv');
            } else {
                $providers = $this->tmp_providers->getSps();
                $data['subtitlepage'] = lang('rr_addnewspsnoinv');
            }
            $data['memberstype'] = strtolower($type);
        } else {
            log_message('error', 'type is expected to be sp or idp but ' . $type . 'given');
            show_error('wrong type', 404);
        }
        foreach ($providers as $i) {
            if (!$federationMembers->contains($i)) {
                $checkbox = array(
                    'id' => 'member[' . $i->getId() . ']',
                    'name' => 'member[' . $i->getId() . ']',
                    'value' => 1,);
                $form_elements[] = array(
                    'name' => $i->getName() . ' (' . $i->getEntityId() . ')',
                    'box' => form_checkbox($checkbox),
                );
            }
        }

        $data['content_view'] = 'federation/bulkadd_view';
        $data['form_elements'] = $form_elements;
        $data['fed_encoded'] = $fed_name;
        $data['message'] = $message;
        $data['titlepage'] = lang('rr_federation') . ': <a href="' . base_url() . 'federations/manage/show/' . $data['fed_encoded'] . '">' . html_escape($federation->getName()) . '</a>';
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . $data['fed_encoded'] . ''), 'name' => html_escape($federation->getName())),
            array('url' => base_url('#'), 'name' => $data['subtitlepage'], 'type' => 'current'),
        );
        $this->load->view('page', $data);
    }

    public function bulkaddsubmit()
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $message = null;
        $encodedFedName = $this->input->post('fed');
        $memberstype = $this->input->post('memberstype');
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($encodedFedName)));
        if (empty($federation)) {
            show_error('federation not found', 404);
        }
        $hasAddbulkAccess = $this->zacl->check_acl('f_'.$federation->getId(), 'addbulk', 'federation', '');
        if (!$hasAddbulkAccess) {
            $data = array('content_view'>'nopermission','error'=>lang('rr_noperm'));
            return $this->load->view('page', $data);
        }
        $existingMembers = $federation->getMembershipProviders();
        $m = $this->input->post('member');
        if (!empty($m) && is_array($m) && count($m) > 0) {
            $mKeys = array_keys($m);
            if (strcasecmp($memberstype, 'idp') == 0 || strcasecmp($memberstype, 'sp') == 0) {
                $newMembersList = $this->em->getRepository("models\Provider")->findBy(array('type' => array('' . strtoupper($memberstype) . '', 'BOTH'), 'id' => $mKeys));
            } else {
                log_message('error', 'missed or wrong membertype while adding new members to federation');
                show_error('Missed members type', 503);
            }
            $newMembersArray = array();
            foreach ($newMembersList as $nmember) {
                if (!$existingMembers->contains($nmember)) {
                    $newMembersArray[] = $nmember->getEntityId();
                    $newMembership = new models\FederationMembers();
                    $newMembership->setProvider($nmember);
                    $newMembership->setFederation($federation);
                    if ($nmember->getLocal()) {
                        $newMembership->setJoinState('1');
                    }
                    $this->em->persist($newMembership);
                } else {
                    $doFilter = array('' . $federation->getId() . '');
                    $m1 = $nmember->getMembership()->filter(
                        function (models\FederationMembers $entry) use ($doFilter) {
                            return (in_array($entry->getFederation()->getId(), $doFilter));
                        }
                    );
                    if (!empty($m1)) {
                        foreach ($m1 as $v1) {
                            if ($nmember->getLocal()) {
                                $v1->setJoinState('1');
                            } else {
                                $v1->setJoinState('0');
                            }
                            $this->em->persist($v1);
                            $newMembersArray[] = $nmember->getEntityId();
                        }
                    }
                }
            }
            if (count($newMembersArray) > 0) {
                $subject = 'Members of Federations changed';
                $body = 'Dear user' . PHP_EOL . 'Federation ' . $federation->getName() . ' has new members:' . PHP_EOL . implode(';' . PHP_EOL, $newMembersArray);
                $this->email_sender->addToMailQueue(array('gfedmemberschanged', 'fedmemberschanged'), $federation, $subject, $body, array(), false);
            }
            $this->em->flush();
            $this->j_ncache->cleanFederationMembers($federation->getId());
            $message = '<div data-alert class="alert-box success">' . lang('rr_fedmembersadded') . '</div>';
        } else {
            $message = '<div data-alert class="alert-box alert">' . sprintf(lang('rr_nomemtype_selected'), $memberstype) . '</div>';
        }

        return $this->addbulk($encodedFedName, $memberstype, $message);
    }

}