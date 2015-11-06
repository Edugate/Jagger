<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @copyright 2015  HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 *
 * Users Class
 */
class Users extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('cert', 'form'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'formelement', 'table', 'rrpreference'));
    }


    private function addSubmitValidate() {
        log_message('debug', '(add user) validating form initialized');
        $usernameMinLength = $this->config->item('username_min_length') ?: 5;
        $this->form_validation->set_rules('username', '' . lang('rr_username') . '', 'trim|required|min_length[' . $usernameMinLength . ']|max_length[128]|user_username_unique[username]');
        $this->form_validation->set_rules('email', 'E-mail', 'trim|required|min_length[5]|max_length[128]|valid_email');
        $this->form_validation->set_rules('access', 'Access type', 'trim|required');
        $accesstype = trim($this->input->post('access'));
        if ($accesstype === 'fed') {
            $this->form_validation->set_rules('password', '' . lang('rr_password') . '', 'required|min_length[5]|max_length[23]|matches[passwordconf]');
            $this->form_validation->set_rules('passwordconf', '' . lang('rr_passwordconf') . '', 'required|min_length[5]|max_length[23]');
        }
        $this->form_validation->set_rules('fname', '' . lang('rr_fname') . '', 'required|min_length[3]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('sname', '' . lang('rr_surname') . '', 'required|min_length[3]|max_length[255]|xss_clean');

        return $this->form_validation->run();
    }

    /**
     * @return bool
     */
    private function ajaxplusadmin() {
        return $this->input->is_ajax_request() && $this->jauth->isLoggedIn() && $this->jauth->isAdministrator();
    }

    /**
     * @param $encodedUsername
     * @return bool
     */
    private function isOwner($encodedUsername) {
        $result = false;
        $decodedUsername = base64url_decode(trim($encodedUsername));
        $sessionUsername = $this->session->userdata('username');
        if ($sessionUsername !== null && strcasecmp($decodedUsername, $sessionUsername) == 0 && strlen(trim($sessionUsername)) > 0) {
            $result = true;
        }

        return $result;
    }

    private function ajaxplusowner($encodedUsername) {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return false;
        }

        return $this->isOwner($encodedUsername);
    }

    public function currentRoles($encodeduser) {
        $encodeduser = strip_tags($encodeduser);
        if (!$this->ajaxplusadmin() && !$this->ajaxplusowner($encodeduser)) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $username = base64url_decode(trim($encodeduser));
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);

            return $this->output->set_status_header(500)->set_output('');
        }
        if (empty($user)) {
            return $this->output->set_status_header(404)->set_output('User not found');
        }
        $result = json_encode($user->getRoleNames());
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
    }

    /**
     * @param $encodedusername
     * @return \models\User
     */
    private function findUserOrExit($encodedusername) {
        $username = base64url_decode(trim($encodedusername));
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            $this->output->set_status_header(500)->set_output('Internal Server error')->_display();
            exit;
        }
        if (empty($user)) {
            $this->output->set_status_header(404)->set_output('User not found')->_display();
            exit;
        }

        return $user;
    }

    public function currentSroles($encodeduser) {
        if (!$this->ajaxplusadmin()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $user = $this->findUserOrExit($encodeduser);
        $resultInJsonEncoded = json_encode($user->getSystemRoleNames());

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output($resultInJsonEncoded);
    }

    public function updateSecondFactor($encodeduser) {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }


        $this->load->library('zacl');

        $user2fset = $this->rrpreference->getStatusByName('user2fset');
        $isOwner = $this->isOwner($encodeduser);

        $userAllowed = $user2fset && $isOwner;

        $isAdmin = $this->jauth->isAdministrator();

        if (!$isAdmin && !$userAllowed) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $user = $this->findUserOrExit($encodeduser);

        $secondfactor = $this->input->post('secondfactor');
        $allowed2ef = $this->config->item('2fengines');
        if (!is_array($allowed2ef)) {
            $allowed2ef = array();
        }
        if (in_array($secondfactor, $allowed2ef, true)) {
            $user->setSecondFactor($secondfactor);
        } else {
            $user->setSecondFactor(null);
        }
        $this->em->persist($user);
        try {
            $this->em->flush();
            $result = array('secondfactor' => $secondfactor);
            $this->output->set_content_type('application/json')->set_output(json_encode($result));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);

            return $this->output->set_status_header(500)->set_output('DB issue');
        }

    }


    public function add() {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn() || !$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }
        $this->load->library('zacl');
        if ($this->addSubmitValidate()) {
            $username = $this->input->post('username');
            $email = $this->input->post('email');
            $fname = $this->input->post('fname');
            $sname = $this->input->post('sname');
            $access = $this->input->post('access');
            $password = $this->input->post('password');
            if (strcasecmp($access, 'fed') == 0) {
                $password = str_generator();
            }
            $user = new models\User;
            $user->genNewValidUser($username, $password, $email, $fname, $sname, $access);
            /**
             * @var $member models\AclRole
             */
            $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Member'));
            if (!empty($member)) {
                $user->setRole($member);
            }
            $personalRole = new models\AclRole;
            $personalRole->setName($username);
            $personalRole->setType('user');
            $personalRole->setDescription('personal role for user ' . $username);
            $user->setRole($personalRole);
            $this->em->persist($personalRole);
            $this->em->persist($user);
            $this->tracker->save_track('user', 'create', $username, 'user created in the system', false);

            try {
                $this->em->flush();
                echo 'OK';
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);

                return $this->output->set_status_header(500)->set_output('Internal server error');
            }
        } else {
            $errors = validation_errors('<div>', '</div>');

            if (!empty($errors)) {
                echo $errors;
            }
        }
    }

    private function getBookmarks(models\User $user) {
        $bookmarksSections = array('idp' => lang('identityproviders'), 'sp' => lang('serviceproviders'), 'fed' => lang('federations'));
        $board = $user->getBookmarks();

        $bookmarks = array();
        foreach (array_keys($board) as $sect) {
            $bookmarks[] = '<p><b>' . $bookmarksSections[$sect] . '</b><ul class="no-bullet">';
            foreach ($board[$sect] as $key => $value) {
                if ($sect === 'fed') {
                    $bookmarks[] = '<li><a href="' . base_url() . 'federations/manage/show/' . $value['url'] . '">' . $value['name'] . '</a></li>';
                } else {
                    $bookmarks[] = '<li><a href="' . base_url('providers/detail/show/' . $key . '') . '">' . $value['name'] . '</a><br /><small>' . $value['entity'] . '</small></li>';
                }
            }
            $bookmarks[] = '</ul></p>';
        }

        return $bookmarks;
    }

    private function genBasicProfile(models\User $user){

        $systemTwoFactorAuthn = $this->config->item('twofactorauthn');
        $secondFactor = $user->getSecondFactor();
        $accessTypeStr = array();

        if ($user->getLocal()) {
            $accessTypeStr[] = lang('rr_local_authn');
        }
        if ($user->getFederated()) {
            $accessTypeStr[] = lang('federated_access');
        }

        $result = array(
            array('key' => lang('rr_username'), 'val' => html_escape($user->getUsername())),
            array('key' => '' . lang('rr_userfullname') . '', 'val' => html_escape($user->getFullname())),
            array('key' => '' . lang('rr_uemail') . '', 'val' => html_escape($user->getEmail())),
            array('key' => '' . lang('rr_typeaccess') . '', 'val' => implode(', ', $accessTypeStr)),
            array('key' => '' . lang('rr_assignedroles') . '', 'val' => '<span id="currentroles">' . implode(', ', $user->getSystemRoleNames()) . '</span> '),
            array('key' => '' . lang('rrnotifications') . '', 'val' => anchor(base_url() . 'notifications/subscriber/mysubscriptions/' . base64url_encode($user->getUsername()) . '', lang('rrmynotifications')))
        );

        $twoFactorLabel = '<span data-tooltip aria-haspopup="true" class="has-tip" title="' . lang('rr_twofactorauthn') . '">' . lang('rr_twofactorauthn') . '</span>';

        if ($secondFactor) {
            $secondFactortext = '<span id="val2f" data-tooltip aria-haspopup="true" class="has-tip" title="' . $secondFactor . ' ">' . $secondFactor . '</span>';
            if ($systemTwoFactorAuthn) {
                $result[] = array('key' => '' . $twoFactorLabel . '', 'val' => '' . $secondFactortext . '');
            } else {
                $result[] = array('key' => '' . $twoFactorLabel . '', 'val' => '' . $secondFactortext . ' <span class="label alert">Disabled</span>');
            }
        } elseif ($systemTwoFactorAuthn) {
            $secondFactortext = '<span id="val2f" data-tooltip aria-haspopup="true" class="has-tip" title="none">none</span>';
            $result[] = array('key' => '' . $twoFactorLabel . '', 'val' => '' . $secondFactortext);
        }

        return $result;

    }

    public function show($encodedUsername) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $limitAuthnRows = 15;
        $user = $this->findUserOrExit($encodedUsername);
        $loggedUsername = $this->jauth->getLoggedinUsername();
        $isOwner = ($loggedUsername === $user->getUsername());
        $isAdmin = $this->jauth->isAdministrator();
        $hasReadAccess = $this->zacl->check_acl('u_' . $user->getId(), 'read', 'user', '');
        if (!($hasReadAccess || $isOwner)) {
            return $this->load->view('page', array('error' => lang('error403'), 'content_view' => 'nopermission'));
        }
        $accessListUsers = $this->zacl->check_acl('', 'read', 'user', '');


        $breadcrumbs = array(
            array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist')),
            array('url' => base_url('#'), 'name' => html_escape($user->getUsername()), 'type' => 'current')
        );
        if (!$accessListUsers) {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist'), 'type' => 'unavailable'),
                array('url' => base_url('#'), 'name' => html_escape($user->getUsername()), 'type' => 'current')
            );
        }


        $tab1 = $this->genBasicProfile($user);



        $tab2[] = array('key' => lang('rr_bookmarked'), 'val' => implode('', $this->getBookmarks($user)));

        $tab3[] = array('data' => array('data' => lang('authnlogs') . ' - ' . lang('rr_lastrecent') . ' ' . $limitAuthnRows, 'class' => 'highlight', 'colspan' => 2));

        /**
         * @var models\Tracker[] $authnLogs
         */
        $authnLogs = $this->em->getRepository("models\Tracker")->findBy(array('resourcename' => $user->getUsername()), array('createdAt' => 'DESC'), $limitAuthnRows);
        foreach ($authnLogs as $ath) {
            $tab3[] = array(
                'key' => jaggerDisplayDateTimeByOffset($ath->getCreated(),jauth::$timeOffset),
                'val' => $ath->getDetail() . '<br /><small><i>' . $ath->getAgent() . '</i></small>'
            );
        }
        $data = array(
            'actionlogs'   => $this->em->getRepository("models\Tracker")->findBy(array('user' => $user->getUsername()), array('createdAt' => 'DESC')),
            'tabs'         => array(
                array(
                    'tabid'    => 'tab1',
                    'tabtitle' => lang('rr_profile'),
                    'tabdata'  => $tab1
                ),
                array(
                    'tabid'    => 'tab2',
                    'tabtitle' => lang('dashboard'),
                    'tabdata'  => $tab2
                ),
                array(
                    'tabid'    => 'tab3',
                    'tabtitle' => lang('authnlogs'),
                    'tabdata'  => $tab3
                )
            ),
            'breadcrumbs'  => $breadcrumbs,
            'titlepage'    => lang('rr_detforuser') . ': ' . html_escape($user->getUsername()) . '',
            'content_view' => 'manage/userdetail_view'
        );
        if ($isAdmin) {
            $data['sideicons'][] = '<a href="' . base_url('manage/userprofile/edit/' . $encodedUsername . '') . '"><i class="fi-pencil"></i></a>';
        } else {
            $data['sideicons'][] = '<a href="' . base_url('manage/userprofile/edit') . '"><i class="fi-pencil"></i></a>';
        }

        $this->load->view('page', $data);
    }


    public function showlist() {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $access = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$access) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);

            return;
        }
        $isAdmin = $this->jauth->isAdministrator();

        /**
         * @var $users models\User[]
         */
        $users = $this->em->getRepository("models\User")->findAll();
        $usersList = array();
        $showlink = base_url('manage/users/show');

        $editLinkPrefix = base_url('manage/userprofile/edit');
        foreach ($users as $u) {
            $encodedUsername = base64url_encode($u->getUsername());
            $roles = $u->getRoleNames();
            $editLink = '';
            if ($isAdmin) {
                $editLink = '<a href="' . $editLinkPrefix . '/' . $encodedUsername . '"><i class="fi-pencil"></i></a>';
            }
            if (in_array('Administrator', $roles, true)) {
                $action = '';
            } else {
                $action = '<a href="#" class="rmusericon" data-jagger-username="' . html_escape($u->getUsername()) . '" data-jagger-encodeduser="' . $encodedUsername . '"><i class="fi-trash"></i><a>';
            }
            $last = $u->getLastlogin();
            $lastlogin = '';
            if (!empty($last)) {
                $lastlogin = jaggerDisplayDateTimeByOffset($last,jauth::$timeOffset);
            }
            $usersList[] = array('user' => anchor($showlink . '/' . $encodedUsername, html_escape($u->getUsername())), 'fullname' => html_escape($u->getFullname()), 'email' => safe_mailto($u->getEmail()), 'ip' => implode(', ', $u->getSystemRoleNames()), 'last' => $lastlogin, $editLink . ' ' . $action);
        }
        $data = array(
            'breadcrumbs'  => array(
                array('url' => base_url('#'), 'name' => lang('rr_userslist'), 'type' => 'current')
            ),
            'titlepage'    => lang('rr_userslist'),
            'userlist'     => $usersList,
            'content_view' => 'manage/userlist_view'
        );
        $this->load->view('page', $data);
    }

    private function removeSubmitValidate() {
        log_message('debug', '(remove user) validating form initialized');
        $this->form_validation->set_rules('username', lang('rr_username'), 'required|trim|max_length[128]|user_username_exists[username]');
        $this->form_validation->set_rules('encodedusr', 'ff');

        return $this->form_validation->run();
    }


    public function remove() {
        if (!$this->jauth->isLoggedIn() || !$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }
        $this->load->library('zacl');
        $access = $this->zacl->check_acl('user', 'remove', 'default', '');
        if ($access !== true) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }
        if (!$this->removeSubmitValidate()) {
            return $this->output->set_status_header(403)->set_output(validation_errors('<div>', '</div>'));

        }
        $this->load->library('jusermanage');
        /**
         * @var $user models\User
         */
        $inputUsername = trim($this->input->post('username'));
        $hiddenEcondedUser = trim($this->input->post('encodedusr'));
        if (empty($inputUsername) || strcmp(base64url_encode($inputUsername), $hiddenEcondedUser) != 0) {
            return $this->output->set_status_header(403)->set_output('Entered username doesnt match');
        }

        /**
         * @var models\User $user
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->input->post('username')));
        if ($user === null) {
            return $this->output->set_status_header(403)->set_output(lang('error_usernotexist'));
        }

        $userRoles = $user->getRoleNames();
        if (in_array('Administrator', $userRoles, true)) {
            return $this->output->set_status_header(403)->set_output('You cannot remover user who has Admninitrator role set');
        }
        $selectedUsername = strtolower($user->getUsername());
        $currentUsername = strtolower($this->session->userdata('username'));
        if (strcmp($selectedUsername, $currentUsername) != 0) {
            $this->jusermanage->remove($user);
            echo 'user has been removed';
            $this->load->library('tracker');
            $this->tracker->save_track('user', 'remove', $selectedUsername, 'user removed from the system', true);
        } else {
            return $this->output->set_status_header(403)->set_output(lang('error_cannotrmyouself'));
        }


    }


}
