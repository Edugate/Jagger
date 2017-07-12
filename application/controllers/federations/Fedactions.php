<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Fedactions extends MY_Controller
{
    protected $tmpProviders;

    public function __construct() {
        parent::__construct();
        MY_Controller::$menuactive = 'fed';
        $this->tmpProviders = new models\Providers;
    }


    public function changestatus() {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Access denied');
        }
        $status = trim($this->input->post('status'));
        $fedname = trim($this->input->post('fedname'));
        if ($status === '' || $fedname === '') {
            return $this->output->set_status_header(403)->set_output('Missing params in post');
        }
        /**
         * @var models\Federation $federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => '' . htmlspecialchars(base64url_decode($fedname)) . ''));
        if ($federation === null) {
            return $this->output->set_status_header(404)->set_output('Federation not found');
        }
        $this->load->library('zacl');
        $hasManageAccess = $this->zacl->check_acl('f_' . $federation->getId(), 'manage', 'federation', '');
        if (!$hasManageAccess) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $currentStatus = $federation->getActive();
        if ($currentStatus && strcmp($status, 'disablefed') == 0) {
            $federation->setAsDisactive();
            $this->em->persist($federation);
            $this->em->flush();

            return $this->output->set_status_header(200)->set_output('deactivated');
        }
        if (!$currentStatus && strcmp($status, 'enablefed') == 0) {
            $federation->setAsActive();
            $this->em->persist($federation);
            $this->em->flush();

            return $this->output->set_status_header(200)->set_output('activated');
        }
        if (!$currentStatus && strcmp($status, 'delfed') == 0) {
            /**
             * @todo finish
             */
            $this->load->library('approval');
            $q = $this->approval->removeFederation($federation);
            $this->em->persist($q);
            $this->em->flush();

            return $this->output->set_status_header(200)->set_output('todelete');
        }

        return $this->output->set_status_header(403)->set_output('incorrect params sent');


    }

    /**
     * @param $federationName
     * @param $type
     * @param null $message
     */
    public function addbulk($federationName, $type, $message = null) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library(array('zacl'));
        $formElements = array();
        /**
         * @var models\Provider[] $providers
         */
        if (strcasecmp($type, 'idp') != 0 && strcasecmp($type, 'sp') != 0) {
            log_message('error', 'type is expected to be sp or idp but ' . $type . 'given');
            show_error('wrong type', 404);
        }

        /**
         * @var models\Federation $federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($federationName)));
        if ($federation === null) {
            show_error(lang('error_fednotfound'), 404);
        }

        $hasAddbulkAccess = $this->zacl->check_acl('f_' . $federation->getId(), 'addbulk', 'federation', '');
        if (!$hasAddbulkAccess) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noperm');

            return $this->load->view(MY_Controller::$page, $data);
        }
        $data['federation_name'] = $federation->getName();
        $data['federation_urn'] = $federation->getUrn();
        $data['federation_desc'] = $federation->getDescription();
        $data['federation_is_active'] = $federation->getActive();
        $federationMembers = $federation->getMembers();

        if (strcasecmp($type, 'idp') == 0) {
            $providers = $this->tmpProviders->getIdps();
            $data['subtitlepage'] = lang('rr_addnewidpsnoinv');
        } else {
            $providers = $this->tmpProviders->getSps();
            $data['subtitlepage'] = lang('rr_addnewspsnoinv');
        }
        $data['memberstype'] = strtolower($type);

        foreach ($providers as $i) {
            if (!$federationMembers->contains($i)) {
                $checkbox = array(
                    'id'    => 'member[' . $i->getId() . ']',
                    'name'  => 'member[' . $i->getId() . ']',
                    'value' => 1,);
                $formElements[] = array(
                    'name' => $i->getName() . ' (' . $i->getEntityId() . ')',
                    'box'  => form_checkbox($checkbox),
                );
            }
        }

        $data['content_view'] = 'federation/bulkadd_view';
        $data['form_elements'] = $formElements;
        $data['fed_encoded'] = $federationName;
        $data['message'] = $message;
        $data['titlepage'] = lang('rr_federation') . ': <a href="' . base_url() . 'federations/manage/show/' . $data['fed_encoded'] . '">' . html_escape($federation->getName()) . '</a>';
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . $data['fed_encoded'] . ''), 'name' => html_escape($federation->getName())),
            array('url' => base_url('#'), 'name' => $data['subtitlepage'], 'type' => 'current'),
        );
        $this->load->view(MY_Controller::$page, $data);
    }

    public function bulkaddsubmit() {
        if (!$this->jauth->isLoggedIn()) {
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
        if (null === $federation) {
            show_error('federation not found', 404);
        }
        $hasAddbulkAccess = $this->zacl->check_acl('f_' . $federation->getId(), 'addbulk', 'federation', '');
        if (!$hasAddbulkAccess) {
            $data = array('content_view' => 'nopermission', 'error' => lang('rr_noperm'));

            return $this->load->view(MY_Controller::$page, $data);
        }
        /**
         * @var models\Provider[] $existingMembers
         * @var models\Provider[] $newMembersList
         */
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
            $newMembersArray = $this->addMembersToCollection($existingMembers, $newMembersList, $federation);
            if (count($newMembersArray) > 0) {
                $subject = 'Members of Federations changed';
                $body = 'Dear user' . PHP_EOL . 'Federation ' . $federation->getName() . ' has new members:' . PHP_EOL . implode(';' . PHP_EOL, $newMembersArray);
                $this->emailsender->addToMailQueue(array('gfedmemberschanged', 'fedmemberschanged'), $federation, $subject, $body, array(), false);
            }
            $this->em->flush();
            $this->j_ncache->cleanFederationMembers($federation->getId());
            return $this->addbulk($encodedFedName, $memberstype, '<div data-alert class="alert-box success">' . lang('rr_fedmembersadded') . '</div>');
        }
        return $this->addbulk($encodedFedName, $memberstype, '<div data-alert class="alert-box alert">' . sprintf(lang('rr_nomemtype_selected'), $memberstype) . '</div>');
    }
    private function addMembersToCollection($existingMembers,$newMembersList, $federation ){
        $newMembersArray = array();
        foreach ($newMembersList as $nmember) {
            if (!$existingMembers->contains($nmember)) {
                $newMembersArray[] = $nmember->getEntityId();
                $newMembership = new models\FederationMembers();
                $newMembership->setProvider($nmember);
                $newMembership->setFederation($federation);
                if ($nmember->getLocal()) {
                    $newMembership->setJoinstate('1');
                }
                $this->em->persist($newMembership);
            } else {
                $doFilter = array('' . $federation->getId() . '');
                /**
                 * @var models\FederationMembers[] $m1
                 */
                $m1 = $nmember->getMembership()->filter(
                    function (models\FederationMembers $entry) use ($doFilter) {
                        return in_array($entry->getFederation()->getId(), $doFilter);
                    }
                );
                if (!empty($m1)) {
                    foreach ($m1 as $v1) {
                        $v1->setJoinstate(''.(int) $nmember->getLocal().'');
                        $this->em->persist($v1);
                        $newMembersArray[] = $nmember->getEntityId();
                    }
                }
            }
        }
        return $newMembersArray;
    }

}
