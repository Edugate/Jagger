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
 * Users Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Users extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('cert', 'form'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element', 'table', 'zacl'));
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
        $this->form_validation->set_rules('username', '' . lang('rr_username') . '', 'required|min_length[' . $usernameMinLength . ']|max_length[128]|user_username_unique[username]|xss_clean');
        $this->form_validation->set_rules('email', 'E-mail', 'required|min_length[5]|max_length[128]|valid_email');
        $this->form_validation->set_rules('access', 'Access type', 'required|xss_clean');
        $accesstype = trim($this->input->post('access'));
        if (!strcasecmp($accesstype, 'fed') == 0) {
            $this->form_validation->set_rules('password', '' . lang('rr_password') . '', 'required|min_length[5]|max_length[23]|matches[passwordconf]');
            $this->form_validation->set_rules('passwordconf', '' . lang('rr_passwordconf') . '', 'required|min_length[5]|max_length[23]');
        }
        $this->form_validation->set_rules('fname', '' . lang('rr_fname') . '', 'required|min_length[3]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('sname', '' . lang('rr_surname') . '', 'required|min_length[3]|max_length[255]|xss_clean');
        return $this->form_validation->run();
    }

    private function ajaxplusadmin()
    {
        if (!$this->input->is_ajax_request()) {
            return false;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            return false;
        }
        $isAdmin = $this->j_auth->isAdministrator();
        if (!$isAdmin) {
            return false;
        }
        return true;
    }

    /**
     * @param $encoded_user
     * @return bool
     */
    private function isOwner($encoded_user)
    {
        $decodedUser = base64url_decode(trim($encoded_user));
        $sessionUsername = $this->session->userdata('username');
        if (!empty($sessionUsername) && strlen(trim($sessionUsername)) > 0 && strcasecmp($decodedUser, $sessionUsername) == 0) {
            return true;
        } else {
            return false;
        }
    }

    private function ajaxplusowner($encoded_user)
    {
        if (!$this->input->is_ajax_request()) {
            return false;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            return false;
        }
        return $this->isOwner($encoded_user);
    }

    private function getRolenamesToJson(models\User $user, $range = null)
    {
        $roles = $user->getRoles();
        $result = array();
        if (!empty($range) && $range === 'system') {
            foreach ($roles as $r) {
                $rtype = $r->getType();
                if ($rtype === 'system') {
                    $result[] = $r->getName();
                }
            }
        } else {
            foreach ($roles as $r) {
                $result[] = $r->getName();
            }
        }
        return json_encode($result);
    }

    public function currentRoles($encodeduser)
    {
        $encodeduser = strip_tags($encodeduser);
        if (!$this->ajaxplusadmin() && !$this->ajaxplusowner($encodeduser)) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $username = base64url_decode(trim($encodeduser));
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            return;
        }
        if (empty($user)) {
            set_status_header(404);
            echo 'user not found';
            return;
        }
        $result = $this->getRolenamesToJson($user);
        echo $result;
        return;
    }

    public function currentSroles($encodeduser)
    {
        if (!$this->ajaxplusadmin()) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $username = base64url_decode(trim($encodeduser));
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            return;
        }

        if (empty($user)) {
            set_status_header(404);
            echo 'user not found';
            return;
        }
        $result = $this->getRolenamesToJson($user, 'system');
        echo $result;
        return;
    }

    public function updateSecondFactor($encodeduser)
    {
        if (!$this->input->is_ajax_request()) {
            set_status_header(403);
            echo 'denied';
            return;

        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo 'denied';
            return;

        }

        $encodeduser = trim($encodeduser);

        $this->load->library('rrpreference');

        $user2fset = $this->rrpreference->getStatusByName('user2fset');
        $isOwner = $this->isOwner($encodeduser);

        $userAllowed = $user2fset && $isOwner;

        $isAdmin = $this->j_auth->isAdministrator();

        if (!$isAdmin && !$userAllowed) {
            set_status_header(403);
            echo 'denied';
            return;


        }


        $username = base64url_decode(trim($encodeduser));
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            echo 'DB problem';
            return;
        }

        if (empty($user)) {
            set_status_header(404);
            echo 'user not found';
            return;
        }
        $secondfactor = $this->input->post('secondfactor');
        $allowed2ef = $this->config->item('2fengines');
        if (empty($allowed2ef) || !is_array($allowed2ef)) {
            $allowed2ef = array();
        }
        if (in_array($secondfactor, $allowed2ef)) {
            $user->setSecondFactor($secondfactor);
        } else {
            $user->setSecondFactor(null);
        }
        $this->em->persist($user);
        try {
            $this->em->flush();
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            echo 'DB problem';
            return;
        }
        $result = array('secondfactor' => $secondfactor);
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function updateRole($encodeduser)
    {
        if (!$this->ajaxplusadmin()) {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $username = base64url_decode(trim($encodeduser));
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            return;
        }
        if (empty($user)) {
            set_status_header(404);
            echo 'user not found';
            return;
        }

        $inputroles = $this->input->post('checkrole[]');
        $currentRoles = $user->getRoles();
        foreach ($currentRoles as $r) {
            $currentRolename = $r->getName();
            $roleType = $r->getType();
            if (!in_array($currentRolename, $inputroles) && ($roleType === 'system')) {
                $user->unsetRole($r);
            }
        }
        /**
         * @var $sysroles models\AclRole[]
         */
        $sysroles = $this->em->getRepository("models\AclRole")->findBy(array('type' => 'system'));
        foreach ($sysroles as $newRole) {
            $newRolename = $newRole->getName();
            if (in_array($newRolename, $inputroles)) {
                $user->setRole($newRole);
            }
        }
        $this->em->persist($user);
        $this->em->flush();
        $r = $this->getRolenamesToJson($user);
        echo $r;
        return;
    }

    public function add()
    {
        if (!$this->input->is_ajax_request()) {
            set_status_header(403);
            echo 'Permission denied';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo 'Permission denied';
            return;
        }
        $access = $this->zacl->check_acl('user', 'create', 'default', '');
        if (!$access) {
            set_status_header(403);
            echo 'Permission denied';
            return;
        }
        if ($this->addSubmitValidate()) {
            $username = $this->input->post('username');
            $email = $this->input->post('email');
            $fname = $this->input->post('fname');
            $sname = $this->input->post('sname');
            $access = $this->input->post('access');
            if (!strcasecmp($access, 'fed') == 0) {
                $password = $this->input->post('password');
            } else {
                $password = str_generator();
            }
            $user = new models\User;
            $user->setSalt();
            $user->setUsername($username);
            $user->setPassword($password);
            $user->setEmail($email);
            $user->setGivenname($fname);
            $user->setSurname($sname);
            if ($access == 'both') {
                $user->setLocalEnabled();
                $user->setFederatedEnabled();
            } elseif ($access == 'fed') {
                $user->setLocalDisabled();
                $user->setFederatedEnabled();
            } elseif ($access == 'local') {
                $user->setLocalEnabled();
                $user->setFederatedDisabled();
            }

            $user->setAccepted();
            $user->setEnabled();
            $user->setValid();
            /**
             * @var $member models\AclRole
             */
            $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Member'));
            if (!empty($member)) {
                $user->setRole($member);
            }
            $p_role = new models\AclRole;
            $p_role->setName($username);
            $p_role->setType('user');
            $p_role->setDescription('personal role for user ' . $username);
            $user->setRole($p_role);
            $this->em->persist($p_role);
            $this->em->persist($user);
            $this->tracker->save_track('user', 'create', $username, 'user created in the system', false);

            try {
                $this->em->flush();
                echo 'OK';
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
                show_error('Error occurred', 500);
            }
        } else {
            $errors = validation_errors('<div>', '</div>');

            if (!empty($errors)) {
                echo $errors;
            }
        }
    }

    public function bookmarkedit($encoded_username, $type = null)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        if (empty($type)) {
            show_error(lang('error404'), 404);
        }
        $allowedtypes = array('idp', 'sp', 'fed');
        if (!in_array($type, $allowedtypes)) {
            show_error('' . lang('rerror_incorrectenttype') . '', 404);
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error('User not found', 404);
        }
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$write_access) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        $userpref = $user->getUserpref();
        if (isset($userpref['board'])) {
        }
        $data['content_view'] = 'manage/userbookmarkedit_view';
        $this->load->view('page', $data);
    }

    public function show($encoded_username)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $encoded_username = trim($encoded_username);
        $username = base64url_decode($encoded_username);
        $limit_authn = 15;
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
            return;
        }
        if (empty($user)) {
            show_error('User not found', 404);
        }

        $loggedUsername = $this->j_auth->current_user();
        $match = (strcasecmp($loggedUsername, $user->getUsername()) == 0);
        $isAdmin = $this->j_auth->isAdministrator();
        $access = $this->zacl->check_acl('u_' . $user->getId(), 'read', 'user', '');
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!($access || $match)) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        $accessListUsers = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$accessListUsers) {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist'), 'type' => 'unavailable'),
                array('url' => base_url('#'), 'name' => html_escape($user->getUsername()), 'type' => 'current')
            );
        } else {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist')),
                array('url' => base_url('#'), 'name' => html_escape($user->getUsername()), 'type' => 'current')
            );
        }

        $passedit_link = '<span><a href="' . base_url() . 'manage/users/passedit/' . $encoded_username . '" class="edit" title="edit" ><i class="fi-pencil"></i></a></span>';

        /**
         * @var $authn_logs models\Tracker[]
         * @var $action_logs models\Tracker[]
         */
        $authn_logs = $this->em->getRepository("models\Tracker")->findBy(array('resourcename' => $user->getUsername()), array('createdAt' => 'DESC'), $limit_authn);

        $action_logs = $this->em->getRepository("models\Tracker")->findBy(array('user' => $user->getUsername()), array('createdAt' => 'DESC'));

        $data['caption'] = html_escape($user->getUsername());
        $local_access = $user->getLocal();
        $federated_access = $user->getFederated();

        $systemTwoFactorAuthn = $this->config->item('twofactorauthn');
        $secondFactor = $user->getSecondFactor();


        $tab1[] = array('key' => lang('rr_username'), 'val' => htmlspecialchars($user->getUsername()));
        if ($write_access) {
            $tab1[] = array('key' => lang('rr_password'), 'val' => $passedit_link);
        }
        $tab1[] = array('key' => '' . lang('rr_userfullname') . '', 'val' => htmlspecialchars($user->getFullname()));
        $tab1[] = array('key' => '' . lang('rr_uemail') . '', 'val' => htmlspecialchars($user->getEmail()));
        $access_type_str = array();
        if ($local_access) {
            $access_type_str[] = lang('rr_local_authn');
        }
        if ($federated_access) {
            $access_type_str[] = lang('federated_access');
        }
        $tab1[] = array('key' => '' . lang('rr_typeaccess') . '', 'val' => implode(", ", $access_type_str));

        if ($isAdmin) {
            $manageBtn = $this->manageRoleBtn($encoded_username);
        } else {
            $manageBtn = '';
        }
        $twoFactorLabel = '<span data-tooltip aria-haspopup="true" class="has-tip" title="' . lang('rr_twofactorauthn') . '">' . lang('rr_twofactorauthn') . '</span>';
        $tab1[] = array('key' => lang('rr_assignedroles'), 'val' => '<span id="currentroles">' . implode(", ", $user->getRoleNames()) . '</span> ' . $manageBtn);
        $tab1[] = array('key' => lang('rrnotifications'), 'val' => anchor(base_url() . 'notifications/subscriber/mysubscriptions/' . $encoded_username . '', lang('rrmynotifications')));
        $this->load->library('rrpreference');
        $allowed2fglobal = $this->rrpreference->getStatusByName('user2fset');
        if (isset($_SESSION['username']) && strcasecmp($_SESSION['username'], $username) == 0) {
            $isOwner = true;
        } else {
            $isOwner = false;
        }

        if ($isAdmin || ($isOwner && $allowed2fglobal)) {
            $bb = $this->manage2fBtn($encoded_username);
        } else {
            $bb = '';
        }
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
        $tab2[] = array('data' => array('data' => 'Dashboard', 'class' => 'highlight', 'colspan' => 2));
        $bookmarks = '';
        $userpref = $user->getUserpref();
        if (isset($userpref['board'])) {
            $board = $userpref['board'];
        }

        if (!empty($board) && is_array($board)) {
            if (array_key_exists('idp', $board) && is_array($board['idp'])) {
                $bookmarks .= '<p><ul class="no-bullet"><b>' . lang('identityproviders') . '</b>';
                foreach ($board['idp'] as $key => $value) {
                    $bookmarks .= '<li><a href="' . base_url() . 'providers/detail/show/' . $key . '">' . $value['name'] . '</a><br /> <small>' . $value['entity'] . '</small></li>';
                }
                $bookmarks .= '</ul></p>';
            }
            if (array_key_exists('sp', $board) && is_array($board['sp'])) {
                $bookmarks .= '<p><ul class="no-bullet"><b>' . lang('serviceproviders') . '</b>';
                foreach ($board['sp'] as $key => $value) {
                    $bookmarks .= '<li><a href="' . base_url('providers/detail/show/' . $key . '') . '">' . $value['name'] . '</a><br /><small>' . $value['entity'] . '</small></li>';
                }
                $bookmarks .= '</ul></p>';
            }
            if (array_key_exists('fed', $board) && is_array($board['fed'])) {
                $bookmarks .= '<p><ul class="no-bullet"><b>' . lang('federations') . '</b>';
                foreach ($board['fed'] as $key => $value) {
                    $bookmarks .= '<li><a href="' . base_url() . 'federations/manage/show/' . $value['url'] . '">' . $value['name'] . '</a></li>';
                }
                $bookmarks .= '</ul></p>';
            }
        }
        $tab2[] = array('key' => lang('rr_bookmarked'), 'val' => $bookmarks);
        $tab3[] = array('data' => array('data' => lang('authnlogs') . ' - ' . lang('rr_lastrecent') . ' ' . $limit_authn, 'class' => 'highlight', 'colspan' => 2));
        foreach ($authn_logs as $ath) {
            $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U') + j_auth::$timeOffset);
            $detail = $ath->getDetail() . "<br /><small><i>" . $ath->getAgent() . "</i></small>";
            $tab3[] = array('key' => $date, 'val' => $detail);
        }

        $tab4[] = array('data' => array('data' => lang('actionlogs'), 'class' => 'highlight', 'colspan' => 2));
        foreach ($action_logs as $ath) {
            $subtype = $ath->getSubType();
            if ($subtype == 'modification') {
                $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U') + j_auth::$timeOffset);
                $d = unserialize($ath->getDetail());
                $dstr = '<br />';
                if (is_array($d)) {
                    foreach ($d as $k => $v) {
                        $dstr .= '<b>' . $k . ':</b><br />';
                        if (is_array($v)) {
                            foreach ($v as $h => $l) {
                                if (!is_array($l)) {
                                    $dstr .= $h . ':' . $l . '<br />';
                                } else {
                                    foreach ($l as $lk => $lv) {
                                        $dstr .= $h . ':' . $lk . '::' . $lv . '<br />';
                                    }
                                }
                            }
                        }
                    }
                }
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $dstr;
                $tab4[] = array('key' => $date, 'val' => $detail);
            } elseif ($subtype == 'create' || $subtype == 'remove') {
                $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U') + j_auth::$timeOffset);
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $ath->getDetail();
                $tab4[] = array('key' => $date, 'val' => $detail);
            }
        }


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
            array(
                'tabid' => 'tab4',
                'tabtitle' => lang('actionlogs'),
                'tabdata' => $tab4,
            )
        );

        $data['breadcrumbs'] = $breadcrumbs;

        $data['titlepage'] = lang('rr_detforuser') . ': ' . $data['caption'];
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
        foreach ($roles as $v) {
            $result .= '<div class="small-12 column"><div class="small-6 column">' . $v->getName() . '</div><div class="small-6 column"><input type="checkbox" name="checkrole[]" value="' . $v->getName() . '"  /></div></div>';
        }
        $result .= '<button type="button" name="updaterole" class="button small">' . lang('btnupdate') . '</button>';
        $result .= form_close() . '<a class="close-reveal-modal">&#215;</a></div>';
        return $result;
    }

    public function showlist()
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
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
            $encoded_username = base64url_encode($u->getUsername());
            $roles = $u->getRoleNames();
            if (in_array('Administrator', $roles)) {
                $action = '';
            } else {
                $action = '<a href="#" class="rmusericon" data-jagger-username="' . html_escape($u->getUsername()) . '" data-jagger-encodeduser="' . $encoded_username . '"><i class="fi-trash"></i><a>';
            }
            $last = $u->getLastlogin();
            $lastlogin = '';
            if (!empty($last)) {
                $lastlogin = date('Y-m-d H:i:s', $last->format('U') + j_auth::$timeOffset);
            }
            $usersList[] = array('user' => anchor($showlink . '/' . $encoded_username, html_escape($u->getUsername())), 'fullname' => html_escape($u->getFullname()), 'email' => safe_mailto($u->getEmail()), 'last' => $lastlogin, 'ip' => $u->getIp(), $action);
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
        $this->form_validation->set_rules('encodedusr','ff');
        return $this->form_validation->run();
    }

    private function accessmodifySubmitValidate()
    {
        log_message('debug', '(modify authz type) validating form initialized');
        $this->form_validation->set_rules('authz', 'Access', 'xss');
        return $this->form_validation->run();
    }

    public function remove()
    {
        $loggedin = $this->j_auth->logged_in();
        $isAjax = $this->input->is_ajax_request();
        if (!$loggedin || !$isAjax) {
            set_status_header(403);
            echo 'Permission denied';
            return;
        }

        $access = $this->zacl->check_acl('user', 'remove', 'default', '');
        if (!$access) {
            set_status_header(403);
            echo 'Permission denied';
            return;
        }
        if (!$this->removeSubmitValidate()) {
            set_status_header(403);

            echo validation_errors('<div>', '</div>');
            return;

        } else {
            $this->load->library('user_manage');
            /**
             * @var $user models\User
             */
            $inputUsername = trim($this->input->post('username'));
            $hiddenEcondedUser = trim($this->input->post('encodedusr'));
            if(empty($inputUsername) || strcmp(base64url_encode($inputUsername),$hiddenEcondedUser)!=0)
            {
                set_status_header(403);
                echo 'Entered username doesnt match';
                return;
            }

            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->input->post('username')));
            if (!empty($user)) {
                $userRoles = $user->getRoleNames();
                if(in_array('Administrator',$userRoles))
                {
                    set_status_header(403);
                    echo 'You cannot remover user who has Admninitrator role set';
                    return;
                }
                $selected_username = strtolower($user->getUsername());
                $current_username = strtolower($this->session->userdata('username'));
                if (strcmp($selected_username, $current_username) != 0) {
                    $this->user_manage->remove($user);
                    echo 'user has been removed';
                    $this->load->library('tracker');
                    $this->tracker->save_track('user', 'remove', $selected_username, 'user removed from the system', true);
                    return;
                } else {
                    set_status_header(403);
                    echo lang('error_cannotrmyouself');
                    return;
                }
            } else {
                set_status_header(403);
                echo lang('error_usernotexist');
                return;
            }

        }

    }

    public function accessedit($encoded_username)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error(lang('error404'), 404);
            return;
        }
        $manage_access = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        if (!$manage_access) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        if ($this->accessmodifySubmitValidate() === TRUE) {
            $i = $this->input->post('authz');
        } else {
            $form_attributes = array('id' => 'formver2', 'class' => 'span-16');
            $action = current_url();
            $form = form_open($action, $form_attributes) . form_fieldset('Access manage for user ' . $username);
            $form .= '<ol><li>' . form_label('Authorization', 'authz') . '<ol>';
            $form .= '<li>Local authentication' . form_checkbox('authz[local]', '1', $user->getLocal()) . '</li>';
            $form .= '<li>Federated access' . form_checkbox('authz[federated]', '1', $user->getFederated()) . '</li>';
            $form .= '</ol></li><li>' . form_label('Account enabled', 'status');
            $form .= '<ol><li>' . form_checkbox('status', '1', $user->isEnabled()) . '</li>';
            $form .= '</ol></li></ol><div class="buttons"><button type="submit" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div>';
            $form .= form_fieldset_close() . form_close();
            $data['content_view'] = 'manage/user_access_edit_view';
            $data['form'] = $form;
            $this->load->view('page', $data);
            return;
        }
    }

    public function passedit($encoded_username)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error('User not found', 404);
        }
        $manage_access = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$write_access && !$manage_access) {
            $data['error'] = 'You have no access';
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        $accessListUsers = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$accessListUsers) {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist'), 'type' => 'unavailable'),
                array('url' => base_url('manage/users/show/' . $encoded_username . ''), 'name' => html_escape($user->getUsername())),
                array('url' => base_url('#'), 'name' => lang('rr_changepass'), 'type' => 'current')
            );
        } else {
            $breadcrumbs = array(
                array('url' => base_url('manage/users/showlist'), 'name' => lang('rr_userslist')),
                array('url' => base_url('manage/users/show/' . $encoded_username . ''), 'name' => html_escape($user->getUsername()),),
                array('url' => base_url('#'), 'name' => lang('rr_changepass'), 'type' => 'current')
            );
        }
        $data['breadcrumbs'] = $breadcrumbs;
        $data['encoded_username'] = $encoded_username;
        $data['manage_access'] = $manage_access;
        $data['write_access'] = $write_access;
        if (!$this->modifySubmitValidate()) {
            $data['titlepage'] = lang('rr_changepass') . ': ' . html_escape($user->getUsername());
            $data['content_view'] = 'manage/password_change_view';
            $this->load->view('page', $data);
        } else {
            $password = $this->input->post('password');
            if ($manage_access) {
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
