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

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('cert', 'form'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element', 'table', 'rrpreference'));
    }

    private function modifySubmitValidate()
    {
        $this->form_validation->set_rules('oldpassword', '' . lang('rr_oldpassword') . '', 'min_length[5]|max_length[50]');
        $this->form_validation->set_rules('password', '' . lang('rr_password') . '', 'required|min_length[5]|max_length[50]|matches[passwordconf]');
        $this->form_validation->set_rules('passwordconf', '' . lang('rr_passwordconf') . '', 'required|min_length[5]|max_length[50]');
        return $this->form_validation->run();
    }

    private function addSubmitValidate()
    {
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
    private function ajaxplusadmin()
    {
        return $this->input->is_ajax_request() && $this->j_auth->logged_in() && $this->j_auth->isAdministrator();
    }

    /**
     * @param $ecodedUsername
     * @return bool
     */
    private function isOwner($ecodedUsername)
    {
        $result = false;
        $decodedUser = base64url_decode(trim($ecodedUsername));
        $sessionUsername = $this->session->userdata('username');
        if (!empty($sessionUsername) && strlen(trim($sessionUsername)) > 0 && strcasecmp($decodedUser, $sessionUsername) == 0) {
            $result = true;
        }
        return $result;
    }

    private function ajaxplusowner($encodedUsername)
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            return false;
        }
        return $this->isOwner($encodedUsername);
    }

    public function currentRoles($encodeduser)
    {
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
        //$result = $this->getRolenamesToJson($user);
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
    private function findUserOrExit($encodedusername)
    {
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

    public function currentSroles($encodeduser)
    {
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

    public function updateSecondFactor($encodeduser)
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }


        $this->load->library('zacl');

        $user2fset = $this->rrpreference->getStatusByName('user2fset');
        $isOwner = $this->isOwner($encodeduser);

        $userAllowed = $user2fset && $isOwner;

        $isAdmin = $this->j_auth->isAdministrator();

        if (!$isAdmin && !$userAllowed) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $user = $this->findUserOrExit($encodeduser);

        $secondfactor = $this->input->post('secondfactor');
        $allowed2ef = $this->config->item('2fengines');
        if (empty($allowed2ef) || !is_array($allowed2ef)) {
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

    public function updateRole($encodeduser)
    {
        if (!$this->ajaxplusadmin()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $username = base64url_decode(trim($encodeduser));
        $loggedUsername = $this->j_auth->current_user();

        $user = $this->findUserOrExit($encodeduser);
        $inputroles = $this->input->post('checkrole[]');
        $currentRoles = $user->getRoles();
        foreach ($currentRoles as $resultInJson) {
            $currentRolename = $resultInJson->getName();
            $roleType = $resultInJson->getType();
            if (($roleType === 'system') && !in_array($currentRolename, $inputroles, true)) {
                if (strcasecmp($loggedUsername, $username) == 0 && strcasecmp($currentRolename, 'administrator') == 0) {
                    return $this->output->set_status_header(403)->set_output('You are not allowed to remove Administrator role from your own account');
                }
                $user->unsetRole($resultInJson);
            }
        }
        /**
         * @var $sysroles models\AclRole[]
         */
        $sysroles = $this->em->getRepository("models\AclRole")->findBy(array('type' => 'system'));
        foreach ($sysroles as $newRole) {
            $newRolename = $newRole->getName();
            if (in_array($newRolename, $inputroles, true)) {
                $user->setRole($newRole);
            }
        }
        $this->em->persist($user);
        $this->em->flush();
        $resultInJson = json_encode($user->getRoleNames());
        $this->output->set_content_type('application/json')->set_output($resultInJson);
    }

    public function add()
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in() || !$this->j_auth->isAdministrator()) {
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

    private function getBookmarks(models\User $user)
    {
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

    public function show($encodedUsername)
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $encodedUsername = trim($encodedUsername);
        $limitAuthnRows = 15;
        $user = $this->findUserOrExit($encodedUsername);
        $loggedUsername = $this->j_auth->current_user();
        $isOwner = (strcasecmp($loggedUsername, $user->getUsername()) == 0);
        $isAdmin = $this->j_auth->isAdministrator();
        $hasReadAccess = $this->zacl->check_acl('u_' . $user->getId(), 'read', 'user', '');
        $hasWriteAccess = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
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
        $passEditRow = array('key' => lang('rr_password'), 'val' => '<i class="fi-lock"></i>');
        if ($hasWriteAccess) {
            $passEditRow['val'] = '<a href="' . base_url('manage/users/passedit/' . $encodedUsername . '') . '" title="edit" ><i class="fi-pencil"></i></a>';
        }




        $localAccess = $user->getLocal();
        $federatedAccess = $user->getFederated();

        $systemTwoFactorAuthn = $this->config->item('twofactorauthn');
        $secondFactor = $user->getSecondFactor();
        $accessTypeStr = array();
        if ($localAccess) {
            $accessTypeStr[] = lang('rr_local_authn');
        }
        if ($federatedAccess) {
            $accessTypeStr[] = lang('federated_access');
        }

        $manageBtn = '';
        if ($isAdmin) {
            $manageBtn = $this->manageRoleBtn($encodedUsername);
        }
        $twoFactorLabel = '<span data-tooltip aria-haspopup="true" class="has-tip" title="' . lang('rr_twofactorauthn') . '">' . lang('rr_twofactorauthn') . '</span>';
        $allowed2fglobal = $this->rrpreference->getStatusByName('user2fset');
        $bb = '';
        if ($isAdmin || ($isOwner && $allowed2fglobal)) {
            $bb = $this->manage2fBtn($encodedUsername);
        }

        $tab1 = array(
            array('key' => lang('rr_username'), 'val' => html_escape($user->getUsername())),
            $passEditRow,
            array('key' => '' . lang('rr_userfullname') . '', 'val' => html_escape($user->getFullname())),
            array('key' => '' . lang('rr_uemail') . '', 'val' => html_escape($user->getEmail())),
            array('key' => '' . lang('rr_typeaccess') . '', 'val' => implode(', ', $accessTypeStr)),
            array('key' => '' . lang('rr_assignedroles') . '', 'val' => '<span id="currentroles">' . implode(', ', $user->getRoleNames()) . '</span> ' . $manageBtn),
            array('key' => '' . lang('rrnotifications') . '', 'val' => anchor(base_url() . 'notifications/subscriber/mysubscriptions/' . $encodedUsername . '', lang('rrmynotifications')))
        );

        if ($secondFactor) {
            $secondFactortext = '<span id="val2f" data-tooltip aria-haspopup="true" class="has-tip" title="' . $secondFactor . ' ">' . $secondFactor . '</span>';
            if ($systemTwoFactorAuthn) {
                $tab1[] = array('key' => '' . $twoFactorLabel . '', 'val' => '' . $secondFactortext . '' . $bb);
            } else {
                $tab1[] = array('key' => '' . $twoFactorLabel . '', 'val' => '' . $secondFactortext . ' <span class="label alert">Disabled</span>' . $bb);
            }
        } elseif ($systemTwoFactorAuthn) {
            $secondFactortext = '<span id="val2f" data-tooltip aria-haspopup="true" class="has-tip" title="none">none</span>';
            $tab1[] = array('key' => '' . $twoFactorLabel . '', 'val' => '' . $secondFactortext . $bb);
        }

        $bookmarks = $this->getBookmarks($user);
        $tab2[] = array('key' => lang('rr_bookmarked'), 'val' => implode('', $bookmarks));


        $tab3[] = array('data' => array('data' => lang('authnlogs') . ' - ' . lang('rr_lastrecent') . ' ' . $limitAuthnRows, 'class' => 'highlight', 'colspan' => 2));

        /**
         * @var $authnLogs models\Tracker[]
         */
        $authnLogs = $this->em->getRepository("models\Tracker")->findBy(array('resourcename' => $user->getUsername()), array('createdAt' => 'DESC'), $limitAuthnRows);
        foreach ($authnLogs as $ath) {
            $tab3[] = array(
                'key' => $ath->getCreated()->modify('+ ' . j_auth::$timeOffset . ' seconds')->format('Y-m-d H:i:s'),
                'val' => $ath->getDetail() . '<br /><small><i>' . $ath->getAgent() . '</i></small>'
            );
        }




        $data['actionlogs'] = $this->em->getRepository("models\Tracker")->findBy(array('user' => $user->getUsername()), array('createdAt' => 'DESC'));
;

        $data['tabs'] = array(
            array(
                'tabid' => 'tab1',
                'tabtitle' => lang('rr_profile'),
                'tabdata' => $tab1,
            ),
            array(
                'tabid' => 'tab2',
                'tabtitle' => lang('dashboard'),
                'tabdata' => $tab2,
            ),
            array(
                'tabid' => 'tab3',
                'tabtitle' => lang('authnlogs'),
                'tabdata' => $tab3,
            ),
        );
        $data['breadcrumbs'] = $breadcrumbs;
        $data['titlepage'] = lang('rr_detforuser') . ': ' . html_escape($user->getUsername()) ;
        $data['content_view'] = 'manage/userdetail_view';
        $this->load->view('page', $data);
    }

    private function manage2fBtn($encodeduser)
    {
        $formTarget = base_url() . 'manage/users/updatesecondfactor/' . $encodeduser;
        $allowed2f = $this->config->item('2fengines');
        if (!is_array($allowed2f)) {
            $allowed2f = array();
        }
        $result = '<button data-reveal-id="m2f" class="tiny" name="m2fbtn" value="' . base_url() . 'manage/users/currentSroles/' . $encodeduser . '"> ' . lang('btnupdate') . '</button>';
        $result .= '<div id="m2f" class="reveal-modal tiny" data-reveal><h3>' . lang('2fupdatetitle') . '</h3>' . form_open($formTarget);
        if (count($allowed2f) > 0) {
            $allowed2f[] = 'none';

            $dropdown = array();
            foreach ($allowed2f as $v) {
                $dropdown['' . $v . ''] = $v;
            }
            $result .= '<div data-alert class="alert-box alert hidden" ></div><div class="small-12 column"><div class="large-6 column end">' . form_dropdown('secondfactor', $dropdown) . '</div></div>';
            $result .= '<div class="small-12 column right"><button type="button" name="update2f" class="button small right">' . lang('btnupdate') . '</button></div>';
        }
        $result .= form_close() . '<a class="close-reveal-modal">&#215;</a></div>';
        return $result;
    }

    private function manageRoleBtn($encodeuser)
    {
        $formTarget = base_url() . 'manage/users/updaterole/' . $encodeuser;
        /**
         * @var $roles models\AclRole[]
         */
        $roles = $this->em->getRepository("models\AclRole")->findBy(array('type' => 'system'));
        $result = '<button data-reveal-id="mroles" class="tiny" name="mrolebtn" value="' . base_url() . 'manage/users/currentSroles/' . $encodeuser . '">' . lang('btnmanageroles') . '</button>';
        $result .= '<div id="mroles" class="reveal-modal tiny" data-reveal><h3>' . lang('rr_manageroles') . '</h3>';
        $result .= form_open($formTarget);
        $result .= '<div class="msg hidden alert-box" data-alert></div>';
        foreach ($roles as $v) {
            $result .= '<div class="small-12 column"><div class="small-6 column">' . $v->getName() . '</div><div class="small-6 column"><input type="checkbox" name="checkrole[]" value="' . $v->getName() . '"  /></div></div>';
        }
        $result .= '<button type="button" name="updaterole" class="button small">' . lang('btnupdate') . '</button>';
        $result .= form_close() . '<a class="close-reveal-modal">&#215;</a></div>';
        return $result;
    }

    public function showlist()
    {
        if (!$this->j_auth->logged_in()) {
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

        /**
         * @var $users models\User[]
         */
        $users = $this->em->getRepository("models\User")->findAll();
        $usersList = array();
        $showlink = base_url('manage/users/show');

        foreach ($users as $u) {
            $encodedUsername = base64url_encode($u->getUsername());
            $roles = $u->getRoleNames();
            if (in_array('Administrator', $roles, true)) {
                $action = '';
            } else {
                $action = '<a href="#" class="rmusericon" data-jagger-username="' . html_escape($u->getUsername()) . '" data-jagger-encodeduser="' . $encodedUsername . '"><i class="fi-trash"></i><a>';
            }
            $last = $u->getLastlogin();
            $lastlogin = '';
            if (!empty($last)) {
                $lastlogin = $last->modify('+ ' . j_auth::$timeOffset . ' seconds')->format('Y-m-d H:i:s');
            }
            $usersList[] = array('user' => anchor($showlink . '/' . $encodedUsername, html_escape($u->getUsername())), 'fullname' => html_escape($u->getFullname()), 'email' => safe_mailto($u->getEmail()), 'last' => $lastlogin, 'ip' => $u->getIp(), $action);
        }
        $data = array(
            'breadcrumbs' => array(
                array('url' => base_url('#'), 'name' => lang('rr_userslist'), 'type' => 'current')
            ),
            'titlepage' => lang('rr_userslist'),
            'userlist' => $usersList,
            'content_view' => 'manage/userlist_view'
        );
        $this->load->view('page', $data);
    }

    private function removeSubmitValidate()
    {
        log_message('debug', '(remove user) validating form initialized');
        $this->form_validation->set_rules('username', lang('rr_username'), 'required|trim|max_length[128]|user_username_exists[username]');
        $this->form_validation->set_rules('encodedusr', 'ff');
        return $this->form_validation->run();
    }


    public function remove()
    {
        if (!$this->j_auth->logged_in() || !$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }
        $this->load->library('zacl');
        $access = $this->zacl->check_acl('user', 'remove', 'default', '');
        if (!$access) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }
        if (!$this->removeSubmitValidate()) {
            set_status_header(403);

            echo validation_errors('<div>', '</div>');
            return;

        } else {
            $this->load->library('jusermanage');
            /**
             * @var $user models\User
             */
            $inputUsername = trim($this->input->post('username'));
            $hiddenEcondedUser = trim($this->input->post('encodedusr'));
            if (empty($inputUsername) || strcmp(base64url_encode($inputUsername), $hiddenEcondedUser) != 0) {
                return $this->output->set_status_header(403)->set_output('Entered username doesnt match');
            }

            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->input->post('username')));
            if (!empty($user)) {
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
                    set_status_header(403);
                    echo lang('error_cannotrmyouself');
                }
            } else {
                set_status_header(403);
                echo lang('error_usernotexist');
            }

        }

    }


    public function passedit($encodedUsername)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $username = base64url_decode($encodedUsername);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error('User not found', 404);
        }
        $this->load->library('zacl');
        $hasManageAccess = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        $hasWriteAccess = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$hasWriteAccess && !$hasManageAccess) {
            $data['error'] = 'You have no access';
            $data['content_view'] = 'nopermission';
            return $this->load->view('page', $data);
        }
        $accessListUsers = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$accessListUsers) {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist'), 'type' => 'unavailable'),
                array('url' => base_url('manage/users/show/' . $encodedUsername . ''), 'name' => html_escape($user->getUsername())),
                array('url' => base_url('#'), 'name' => lang('rr_changepass'), 'type' => 'current')
            );
        } else {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist')),
                array('url' => base_url('manage/users/show/' . $encodedUsername . ''), 'name' => html_escape($user->getUsername()),),
                array('url' => base_url('#'), 'name' => lang('rr_changepass'), 'type' => 'current')
            );
        }
        $data = array(
            'breadcrumbs' => $breadcrumbs,
            'encoded_username' => $encodedUsername,
            'manage_access' => $hasManageAccess,
            'write_access' => $hasWriteAccess,
        );
        if (!$this->modifySubmitValidate()) {
            $data['titlepage'] = lang('rr_changepass') . ': ' . html_escape($user->getUsername());
            $data['content_view'] = 'manage/password_change_view';
            $this->load->view('page', $data);
        } else {
            $password = $this->input->post('password');
            if ($hasManageAccess) {
                $user->setPassword($password);
                $user->setLocalEnabled();
                $this->em->persist($user);
                $this->em->flush();
                $data['message'] = '' . lang('rr_passchangedsucces') . ': ' . html_escape($user->getUsername());
                $data['content_view'] = 'manage/password_change_view';
                $this->load->view('page', $data);
            }
        }
    }

}
