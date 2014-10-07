<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
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
        $this->current_site = current_url();
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
        $usernameMinLength = $this->config->item('username_min_length') ? : 5;
        $this->form_validation->set_rules('username', '' . lang('rr_username') . '', 'required|min_length[' . $usernameMinLength . ']|max_length[128]|user_username_unique[username]|xss_clean');
        $this->form_validation->set_rules('email', 'E-mail', 'required|min_length[5]|max_length[128]|valid_email');
        $this->form_validation->set_rules('access', 'Access type', 'required|xss_clean');
        $accesstype = trim($this->input->post('access'));
        if (!strcasecmp($accesstype, 'fed') == 0)
        {
            $this->form_validation->set_rules('password', '' . lang('rr_password') . '', 'required|min_length[5]|max_length[23]|matches[passwordconf]');
            $this->form_validation->set_rules('passwordconf', '' . lang('rr_passwordconf') . '', 'required|min_length[5]|max_length[23]');
        }
        $this->form_validation->set_rules('fname', '' . lang('rr_fname') . '', 'required|min_length[3]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('sname', '' . lang('rr_surname') . '', 'required|min_length[3]|max_length[255]|xss_clean');
        return $this->form_validation->run();
    }

    private function ajaxplusadmin()
    {
        if (!$this->input->is_ajax_request())
        {
            return false;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            return false;
        }
        $isAdmin = $this->j_auth->isAdministrator();
        if (!$isAdmin)
        {
            return false;
        }
        return true;
    }

    private function ajaxplusowner($encoded_user)
    {
        if (!$this->input->is_ajax_request())
        {
            return false;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            return false;
        }
        $currnetUser = $_SESSION['username'];
        $decodedUser = base64url_decode(trim($encoded_user));
        if (!strcasecmp($currnetUser, $decodedUser) != 0)
        {
            return false;
        }
        return true;
    }

    private function getRolenamesToJson(models\User $user, $range = null)
    {
        $roles = $user->getRoles();
        $result = array();
        if (!empty($range) && $range === 'system')
        {
            foreach ($roles as $r)
            {
                $rtype = $r->getType();
                if ($rtype === 'system')
                {
                    $result[] = $r->getName();
                }
            }
        }
        else
        {
            foreach ($roles as $r)
            {
                $result[] = $r->getName();
            }
        }
        return json_encode($result);
    }

    public function currentRoles($encodeduser)
    {
        $encodeduser = strip_tags($encodeduser);
        if (!$this->ajaxplusadmin() && !$this->ajaxplusowner($encodeduser))
        {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $username = base64url_decode(trim($encodeduser));
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
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
        if (!$this->ajaxplusadmin())
        {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $username = base64url_decode(trim($encodeduser));
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
            set_status_header(404);
            echo 'user not found';
            return;
        }
        $result = $this->getRolenamesToJson($user, 'system');
        echo $result;
        return;
    }

    public function updateRole($encodeduser)
    {
        if (!$this->ajaxplusadmin())
        {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $username = base64url_decode(trim($encodeduser));
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
            set_status_header(404);
            echo 'user not found';
            return;
        }

        $inputroles = $this->input->post('checkrole[]');
        $currentRoles = $user->getRoles();
        foreach ($currentRoles as $r)
        {
            $currentRolename = $r->getName();
            $roleType = $r->getType();
            if (!in_array($currentRolename, $inputroles) && ($roleType === 'system'))
            {
                $user->unsetRole($r);
            }
        }
        $sysroles = $this->em->getRepository("models\AclRole")->findBy(array('type' => 'system'));
        foreach ($sysroles as $newRole)
        {
            $newRolename = $newRole->getName();
            if (in_array($newRolename, $inputroles))
            {
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
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $access = $this->zacl->check_acl('user', 'create', 'default', '');
        if (!$access)
        {
            $data['error'] = lang('rr_nopermnewuser');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        if (!$this->addSubmitValidate())
        {
            
            $data['titlepage'] = lang('userregisterform');
            $data['content_view'] = 'manage/new_user_view';
            $this->load->view('page', $data);
        }
        else
        {
            $username = $this->input->post('username');
            $email = $this->input->post('email');
            $fname = $this->input->post('fname');
            $sname = $this->input->post('sname');
            $access = $this->input->post('access');
            if (!strcasecmp($access, 'fed') == 0)
            {
                $password = $this->input->post('password');
            }
            else
            {
                $password = str_generator();
            }
            $user = new models\User;
            $user->setSalt();
            $user->setUsername($username);
            $user->setPassword($password);
            $user->setEmail($email);
            $user->setGivenname($fname);
            $user->setSurname($sname);
            if ($access == 'both')
            {
                $user->setLocalEnabled();
                $user->setFederatedEnabled();
            }
            elseif ($access == 'fed')
            {
                $user->setLocalDisabled();
                $user->setFederatedEnabled();
            }
            elseif ($access == 'local')
            {
                $user->setLocalEnabled();
                $user->setFederatedDisabled();
            }

            $user->setAccepted();
            $user->setEnabled();
            $user->setValid();
            $member = new models\AclRole;
            $member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Member'));
            if (!empty($member))
            {
                $user->setRole($member);
            }
            $p_role = new models\AclRole;
            $p_role->setName($username);
            $p_role->setType('user');
            $p_role->setDescription('personal role for user ' . $username);
            $user->setRole($p_role);
            $this->em->persist($p_role);
            $this->em->persist($user);

            $this->em->flush();

            $this->tracker->save_track('user', 'create', $username, 'user created in the system', true);


            $data['message'] = 'user has been added';
            $data['content_view'] = 'manage/new_user_view';
            $this->load->view('page', $data);
        }
    }

    public function bookmarkedit($encoded_username, $type = null)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        if (empty($type))
        {
            show_error(lang('error404'), 404);
        }
        $allowedtypes = array('idp', 'sp', 'fed');
        if (!in_array($type, $allowedtypes))
        {
            show_error('' . lang('rerror_incorrectenttype') . '', 404);
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
            show_error('User not found', 404);
        }
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$write_access)
        {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        $userpref = $user->getUserpref();
        if (isset($userpref['board']))
        {
            $board = $userpref['board'];
        }
        $data['content_view'] = 'manage/userbookmarkedit_view';
        $this->load->view('page', $data);
    }

    public function show($encoded_username)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $encoded_username = trim($encoded_username);
        $username = base64url_decode($encoded_username);
        $limit_authn = 15;
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
            show_error('User not found', 404);
        }

        $loggedUsername = $this->j_auth->current_user();
        $match = (strcasecmp($loggedUsername, $user->getUsername()) == 0);
        $isAdmin = $this->j_auth->isAdministrator();
        $access = $this->zacl->check_acl('u_' . $user->getId(), 'read', 'user', '');
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!($access || $match))
        {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }

        $passedit_link = '<span><a href="' . base_url() . 'manage/users/passedit/' . $encoded_username . '" class="edit" title="edit" ><i class="fi-pencil"></i></a></span>';

        $authn_logs = $this->em->getRepository("models\Tracker")->findBy(array('resourcename' => $user->getUsername()), array('createdAt' => 'DESC'), $limit_authn);

        $action_logs = $this->em->getRepository("models\Tracker")->findBy(array('user' => $user->getUsername()), array('createdAt' => 'DESC'));

        $data['caption'] = $user->getUsername();
        $local_access = $user->getLocal();
        $federated_access = $user->getFederated();
        $i = 0;
        $det = array();
        $det[$i++] = array('key' => lang('rr_username'), 'val' => $user->getUsername());
        if ($write_access)
        {
            $det[$i++] = array('key' => lang('rr_password'), 'val' => $passedit_link);
        }
        $det[$i++] = array('key' => '' . lang('rr_userfullname') . '', 'val' => $user->getFullname());
        $det[$i++] = array('key' => '' . lang('rr_uemail') . '', 'val' => $user->getEmail());
        $access_type_str = array();
        if ($local_access)
        {
            $access_type_str[] = lang('rr_local_authn');
        }
        if ($federated_access)
        {
            $access_type_str[] = lang('federated_access');
        }
        $det[$i++] = array('key' => '' . lang('rr_typeaccess') . '', 'val' => implode(", ", $access_type_str));

        if ($isAdmin)
        {
            $manageBtn = $this->manageRoleBtn($encoded_username);
        }
        else
        {
            $manageBtn = '';
        }
        $det[$i++] = array('key' => lang('rr_assignedroles'), 'val' => '<span id="currentroles">' . implode(", ", $user->getRoleNames()) . '</span> ' . $manageBtn);
        $det[$i++] = array('key' => lang('rrnotifications'), 'val' => anchor(base_url() . 'notifications/subscriber/mysubscriptions/' . $encoded_username . '', lang('rrmynotifications')));
        $det[$i++] = array('data' => array('data' => 'Dashboard', 'class' => 'highlight', 'colspan' => 2));
        $bookmarks = '';
        $userpref = $user->getUserpref();
        if (isset($userpref['board']))
        {
            $board = $userpref['board'];
        }

        if (!empty($board) && is_array($board))
        {
            if (array_key_exists('idp', $board) && is_array($board['idp']))
            {
                $bookmarks .= '<p><ul class="no-bullet"><b>' . lang('identityproviders') . '</b>';
                foreach ($board['idp'] as $key => $value)
                {
                    $bookmarks .= '<li><a href="' . base_url() . 'providers/detail/show/' . $key . '">' . $value['name'] . '</a><br /> <small>' . $value['entity'] . '</small></li>';
                }
                $bookmarks .= '</ul></p>';
            }
            if (array_key_exists('sp', $board) && is_array($board['sp']))
            {
                $bookmarks .= '<p><ul class="no-bullet"><b>' . lang('serviceproviders') . '</b>';
                foreach ($board['sp'] as $key => $value)
                {
                    $bookmarks .= '<li><a href="' . base_url() . 'providers/detail/show/' . $key . '">' . $value['name'] . '</a><br /><small>' . $value['entity'] . '</small></li>';
                }
                $bookmarks .= '</ul></p>';
            }
            if (array_key_exists('fed', $board) && is_array($board['fed']))
            {
                $bookmarks .= '<p><ul class="no-bullet"><b>' . lang('federations') . '</b>';
                foreach ($board['fed'] as $key => $value)
                {
                    $bookmarks .= '<li><a href="' . base_url() . 'federations/manage/show/' . $value['url'] . '">' . $value['name'] . '</a></li>';
                }
                $bookmarks .= '</ul></p>';
            }
        }
        $det[$i++] = array('key' => lang('rr_bookmarked'), 'val' => $bookmarks);


        $det[$i++] = array('data' => array('data' => lang('authnlogs') . ' - ' . lang('rr_lastrecent') . ' ' . $limit_authn, 'class' => 'highlight', 'colspan' => 2));
        foreach ($authn_logs as $ath)
        {
            $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U') + j_auth::$timeOffset);
            $detail = $ath->getDetail() . "<br /><small><i>" . $ath->getAgent() . "</i></small>";
            $det[$i++] = array('key' => $date, 'val' => $detail);
        }
        $det[$i++] = array('data' => array('data' => lang('actionlogs'), 'class' => 'highlight', 'colspan' => 2));
        foreach ($action_logs as $ath)
        {
            $subtype = $ath->getSubType();
            if ($subtype == 'modification')
            {
                $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U') + j_auth::$timeOffset);
                $d = unserialize($ath->getDetail());
                $dstr = '<br />';
                if (is_array($d))
                {
                    foreach ($d as $k => $v)
                    {
                        $dstr .= '<b>' . $k . ':</b><br />';
                        if (is_array($v))
                        {
                            foreach ($v as $h => $l)
                            {
                                if (!is_array($l))
                                {
                                    $dstr .= $h . ':' . $l . '<br />';
                                }
                                else
                                {
                                    foreach ($l as $lk => $lv)
                                    {
                                        $dstr .= $h . ':' . $lk . '::' . $lv . '<br />';
                                    }
                                }
                            }
                        }
                    }
                }
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $dstr;
                $det[$i++] = array('key' => $date, 'val' => $detail);
            }
            elseif ($subtype == 'create' || $subtype == 'remove')
            {
                $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U') + j_auth::$timeOffset);
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $ath->getDetail();
                $det[$i++] = array('key' => $date, 'val' => $detail);
            }
        }

        $data['det'] = $det;
        $data['titlepage'] = lang('rr_detforuser') . ': ' . $data['caption'];
        $data['content_view'] = 'manage/userdetail_view';
        $this->load->view('page', $data);
    }

    private function manageRoleBtn($encodeuser)
    {
        $formTarget = base_url() . 'manage/users/updaterole/' . $encodeuser;
        $roles = $this->em->getRepository("models\AclRole")->findBy(array('type' => 'system'));
        $r = '<button data-reveal-id="mroles" class="tiny" name="mrolebtn" value="' . base_url() . 'manage/users/currentSroles/' . $encodeuser . '">' . lang('btnmanageroles') . '</button>';
        $r .= '<div id="mroles" class="reveal-modal tiny" data-reveal>';
        $r .= '<h3>' . lang('rr_manageroles') . '</h3>';
        $r .= form_open($formTarget);
        foreach ($roles as $v)
        {
            $r .='<div class="small-12 column"><div class="small-6 column">' . $v->getName() . '</div><div class="small-6 column"><input type="checkbox" name="checkrole[]" value="' . $v->getName() . '"  /></div></div>';
        }
        $r .= '<button type="button" name="updaterole" class="button small">' . lang('btnupdate') . '</button>';
        $r .= form_close();
        $r .= '<a class="close-reveal-modal">&#215;</a>';
        $r .= '</div>';
        return $r;
    }

    public function showlist()
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $access = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$access)
        {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }

        $users = $this->em->getRepository("models\User")->findAll();
        $userlist = array();
        $showlink = base_url() . 'manage/users/show/';

        foreach ($users as $u)
        {
            $encoded_username = base64url_encode($u->getUsername());
            $last = $u->getLastlogin();
            if (!empty($last))
            {
                $lastlogin = date('Y-m-d H:i:s', $last->format('U') + j_auth::$timeOffset);
            }
            else
            {
                $lastlogin = 'never';
            }
            $ip = null;
            $ip = $u->getIp();
            $userlist[] = array('user' => anchor($showlink . $encoded_username, $u->getUsername()), 'fullname' => $u->getFullname(), 'email' => safe_mailto($u->getEmail()), 'last' => $lastlogin, 'ip' => $ip);
        }
        $data['titlepage'] = lang('rr_userslist');
        $data['userlist'] = $userlist;
        $data['content_view'] = 'manage/userlist_view';
        $this->load->view('page', $data);
    }

    private function removeSubmitValidate()
    {
        log_message('debug', '(remove user) validating form initialized');
        $this->form_validation->set_rules('username', 'Username', 'required|trim|max_length[128]|user_username_exists[username]');
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
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $access = $this->zacl->check_acl('user', 'remove', 'default', '');
        if (!$access)
        {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
        }
        else
        {
            if (!$this->removeSubmitValidate())
            {
                $form_attributes = array('id' => 'formver2', 'class' => 'register');
                $action = base_url() . "manage/users/remove";
                $f = form_open($action, $form_attributes);
                $f .='<div class="small-12 columns">';
                $f .= '<div class="small-3 columns">';
                $f .= jform_label('' . lang('rr_username') . '', 'username') . '</div>';
                $f .= '<div class="small-6 large-7 end columns">' . form_input('username') . '</div>';
                $f .= '</div>';
                $f .= '<div class="buttons small-12 columns"><div class="small-9 large-10 end columns text-right"><button type="submit" name="remove" value="remove" class="resetbutton deleteicon">' . lang('rr_rmuserbtn') . '</button></div></div>';
                $f .= form_close();

                $data['form'] = $f;
                $data['titlepage'] = lang('rr_rminguser');
                $data['content_view'] = 'manage/remove_user_view';
                $this->load->view('page', $data);
            }
            else
            {
                $this->load->library('user_manage');
                $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->input->post('username')));
                if (!empty($user))
                {
                    $selected_username = strtolower($user->getUsername());
                    $current_username = strtolower($_SESSION['username']);
                    if ($selected_username != $current_username)
                    {
                        $this->user_manage->remove($user);
                        $data['message'] = 'user has been removed';
                        $this->load->library('tracker');
                        $this->tracker->save_track('user', 'remove', $selected_username, 'user removed from the system', true);
                    }
                    else
                    {
                        $data['message'] = lang('error_cannotrmyouself');
                    }
                }
                else
                {
                    $data['message'] = lang('error_usernotexist');
                }
                $data['content_view'] = 'manage/remove_user_view';
                $this->load->view('page', $data);
            }
        }
    }

    public function accessedit($encoded_username)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
            show_error(lang('error404'), 404);
            return;
        }
        $manage_access = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        if (!$manage_access)
        {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        if ($this->accessmodifySubmitValidate() === TRUE)
        {
            $i = $this->input->post('authz');
        }
        else
        {
            $form_attributes = array('id' => 'formver2', 'class' => 'span-16');
            $action = current_url();
            $form = form_open($action, $form_attributes);
            $form .= form_fieldset('Access manage for user ' . $username);
            $form .= '<ol>';
            $form .= '<li>';
            $form .= form_label('Authorization', 'authz');
            $form .= '<ol>';
            $form .= '<li>Local authentication' . form_checkbox('authz[local]', '1', $user->getLocal()) . '</li>';
            $form .= '<li>Federated access' . form_checkbox('authz[federated]', '1', $user->getFederated()) . '</li>';
            $form .= '</ol>';
            $form .= '</li>';
            $form .= '<li>';
            $form .= form_label('Account enabled', 'status');
            $form .= '<ol>';
            $form .= '<li>' . form_checkbox('status', '1', $user->isEnabled()) . '</li>';
            $form .= '</ol>';
            $form .= '</li>';
            $form .= '</ol>';
            $form .= '<div class="buttons"><button type="submit" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div';
            $form .= form_fieldset_close();
            $form .= form_close();
            $data['content_view'] = 'manage/user_access_edit_view';
            $data['form'] = $form;
            $this->load->view('page', $data);
            return;
        }
    }

    public function passedit($encoded_username)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user))
        {
            show_error('User not found', 404);
        }

        
        $manage_access = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$write_access && !$manage_access)
        {
            $data['error'] = 'You have no access';
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        $data['encoded_username'] = $encoded_username;
        $data['manage_access'] = $manage_access;
        $data['write_access'] = $write_access;
        if (!$this->modifySubmitValidate())
        {
            $data['titlepage'] = lang('rr_changepass') . ': ' . htmlentities($user->getUsername());
            $data['content_view'] = 'manage/password_change_view';
            $this->load->view('page', $data);
        }
        else
        {
            $oldpassword = $this->input->post('oldpassword');
            $password = $this->input->post('password');
            if ($manage_access)
            {
                $user->setPassword($password);
                $user->setLocalEnabled();
                $this->em->persist($user);
                $this->em->flush();
                $data['message'] = '' . lang('rr_passchangedsucces') . ': ' . htmlentities($user->getUsername());
                $data['content_view'] = 'manage/password_change_view';
                $this->load->view('page', $data);
            }
        }
    }

}
