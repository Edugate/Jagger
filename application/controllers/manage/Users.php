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

class Users extends MY_Controller {

    function __construct() {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->helper(array('cert', 'form'));
        $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element', 'table', 'zacl'));
        // show_error('functionality not ready yet', 503);
    }

    private function _modify_submit_validate() {
        $this->form_validation->set_rules('oldpassword', 'Old password', 'min_length[5]|max_length[50]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[5]|max_length[50]|matches[passwordconf]');
        $this->form_validation->set_rules('passwordconf', 'Password Confirmation', 'required|min_length[5]|max_length[50]');
        return $this->form_validation->run();
    }
    private function _add_submit_validate() {
        log_message('debug',  '(add user) validating form initialized');
        $this->form_validation->set_rules('username', ''.lang('rr_username').'', 'required|min_length[5]|max_length[128]|user_username_unique[username]|xss_clean');
        $this->form_validation->set_rules('email', 'E-mail', 'required|min_length[5]|max_length[128]|valid_email|user_mail_unique[email]|xss_clean');
        $this->form_validation->set_rules('access', 'Access type', 'required|xss_clean');
        $accesstype = trim($this->input->post('access'));
        if(!strcasecmp($accesstype,'fed')==0)
        {
           $this->form_validation->set_rules('password', 'Password', 'required|min_length[5]|max_length[23]|matches[passwordconf]');
           $this->form_validation->set_rules('passwordconf', 'Password Confirmation', 'required|min_length[5]|max_length[23]');
        }
        $this->form_validation->set_rules('fname', 'First name', 'required|min_length[3]|max_length[255]|xss_clean');
        $this->form_validation->set_rules('sname', 'Surname', 'required|min_length[3]|max_length[255]|xss_clean');
        return $this->form_validation->run();
    }

    public function add() {
        $access = $this->zacl->check_acl('user', 'create', 'default', '');
        if (!$access) {
            $data['error'] = lang('rr_nopermnewuser');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
        } else {
            if (!$this->_add_submit_validate()) {
                $form_attributes = array('id' => 'formver2', 'class' => 'register');
                $action = base_url() . "manage/users/add";

                $form = form_open($action, $form_attributes);
                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_username').'', 'username').'</div>';
                $form .= '<div class="small-6 large-7 columns end">'.form_input('username',set_value('username')).'</div>';
                $form .= '</div>';

                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_uemail').'', 'email').'</div>';
                $form .= '<div class="small-6 large-7 columns end">'.form_input('email',set_value('email')).'</div>';
                $form .= '</div>';
                $form .= '<div class="small-12 columns passwordrow">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_password').'', 'password').'</div>';
                $form .= '<div class="small-6 large-7 columns end">'.form_password('password').'</div>';
                $form .= '</div>';
                $form .= '<div class="small-12 columns passwordrow">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_passwordconf').'', 'passwordconf').'</div>';
                $form .= '<div class="small-6 large-7 columns end">'.form_password('passwordconf').'</div>';
                $form .= '</div>';
                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_fname').'', 'fname').'</div>';
                $form .= '<div class="small-6 large-7 columns end">'.form_input('fname',set_value('fname')).'</div>';
                $form .= '</div>';
                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_lname').'', 'sname').'</div>';
                $form .= '<div class="small-6 large-7 columns end">'.form_input('sname',set_value('sname')).'</div>';
                $form .= '</div>';
                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-3 columns">'.jform_label(''.lang('rr_typeaccess').'', 'access').'</div>';
                $access_type = array('' => ''.lang('rr_select').'', 'local' => ''.lang('rr_onlylocalauthn').'', 'fed' => ''.lang('rr_onlyfedauth').'', 'both' => ''.lang('rr_bothauth').'');
                $form .= '<div class="small-6 large-7 columns end">'.form_dropdown('access', $access_type,set_value('access'),'class="nuseraccesstype"').'</div>';
                $form .= '</div>';
                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-9 large-10 text-right columns"><button type="submit"  name="submit" value="submit" class="addbutton addicon">'.lang('adduser_btn').'</button></div>';
                $form .='</div>';
                $form .= form_close();
                $data['message'] = $form;
                $data['titlepage'] = lang('userregisterform');
                $data['content_view'] = 'manage/new_user_view';
                $this->load->view('page', $data);
            } else {
                $username = $this->input->post('username');
                $email = $this->input->post('email');
                $fname = $this->input->post('fname');
                $sname = $this->input->post('sname');
                $access = $this->input->post('access');
                if(!strcasecmp($access,'fed')==0)
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
                $member = new models\AclRole;
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

                $this->em->flush();

                $this->tracker->save_track('user', 'create', $username, 'user created in the system', true);


                $data['message'] = 'user has been added';
                $data['content_view'] = 'manage/new_user_view';
                $this->load->view('page', $data);
            }
        }
    }

    public function bookmarkedit($encoded_username,$type=null)
    {
        if(empty($type))
        {
            show_error( lang('error404'), 404);
        }
        $allowedtypes= array('idp','sp','fed');
        if(!in_array($type,$allowedtypes))
        {
           show_error(''.lang('rerror_incorrectenttype').'', 404);
        }
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error( 'User not found', 404);
        }
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$write_access) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        $userpref = $user->getUserpref();
        if(isset($userpref['board']))
        {
            $board = $userpref['board'];
        }
        $data['content_view'] = 'manage/userbookmarkedit_view';
        $this->load->view('page',$data);  

    }

    public function show($encoded_username) {
        $username = base64url_decode($encoded_username);
        $limit_authn = 15;
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error('User not found', 404);
        }

        $loggedUsername = $this->j_auth->current_user();
        $match = (strcasecmp($loggedUsername,$user->getUsername())==0);
        $access = $this->zacl->check_acl('u_' . $user->getId(), 'read', 'user', '');
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!($access || $match)) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }

        $image_link = '<img src="' . base_url() . 'images/icons/pencil-field.png"/>';
        $passedit_link = '<span><a href="' . base_url() . 'manage/users/passedit/' . $encoded_username . '" class="edit" title="edit" >' . $image_link . '</a></span>';

        $authn_logs = $this->em->getRepository("models\Tracker")->findBy(array('resourcename' => $user->getUsername()), array('createdAt' => 'DESC'),$limit_authn);

        $action_logs = $this->em->getRepository("models\Tracker")->findBy(array('user' => $user->getUsername()), array('createdAt' => 'DESC'));

        $data['caption'] = $user->getUsername();
        $local_access = $user->getLocal();
        $federated_access = $user->getFederated();
        $i = 0;
        $det = array();
        $det[$i++] = array('data' => array('data' => 'Basic', 'class' => 'highlight', 'colspan' => 2));
        
        $det[$i++] = array('key' => 'Username', 'val' => $user->getUsername());
        if($write_access)
        {
              $det[$i++] = array('key' => 'password', 'val' => $passedit_link);
          
        }
        $det[$i++] = array('key' => 'Fullname', 'val' => $user->getFullname());
        $det[$i++] = array('key' => 'Email', 'val' => $user->getEmail());
        $access_type_str = array();
        if ($local_access) {
            $access_type_str[] = "Local authentication";
        }
        if ($federated_access) {
            $access_type_str[] = "Federated access";
        }
        $det[$i++] = array('key' => 'Access types', 'val' => implode(", ", $access_type_str));
        $det[$i++] = array('key' => 'Assigned roles', 'val' => implode(", ", $user->getRoleNames()));
        $det[$i++] = array('key'=> lang('rrnotifications'),'val'=>anchor(base_url().'notifications/subscriber/mysubscriptions/'.$encoded_username.'',lang('rrmynotifications')));
        $det[$i++] = array('data' => array('data' => 'Dashboard', 'class' => 'highlight', 'colspan' => 2));
        $bookmarks = '';
        $userpref = $user->getUserpref();
        if(isset($userpref['board']))
        {
            $board = $userpref['board'];
        }
       
        if(!empty($board)&& is_array($board))
        {
           if(array_key_exists('idp',$board) && is_array($board['idp']))
           {
               $bookmarks .= '<p><ul><b>'.lang('identityproviders').'</b>';
               foreach($board['idp'] as $key=>$value)
               {
                   $bookmarks .= '<li><a href="'.base_url().'providers/detail/show/'.$key.'">'.$value['name'].'</a><br /> <small>'.$value['entity'].'</small></li>';
               }
               $bookmarks .= '</ul></p>';
           }
           if(array_key_exists('sp',$board) && is_array($board['sp']))
           {
               $bookmarks .= '<p><ul><b>'.lang('serviceproviders').'</b>';
               foreach($board['sp'] as $key=>$value)
               {
                   $bookmarks .= '<li><a href="'.base_url().'providers/detail/show/'.$key.'">'.$value['name'].'</a><br /><small>'.$value['entity'].'</small></li>';
               }
               $bookmarks .= '</ul></p>';
           }
           if(array_key_exists('fed',$board) && is_array($board['fed']))
           {
              $bookmarks .= '<p><ul><b>'.lang('federations').'</b>';
              foreach($board['fed'] as $key=>$value)
              {
                   $bookmarks .= '<li><a href="'.base_url().'federations/manage/show/'.$value['url'].'">'.$value['name'].'</a></li>';
              }
              $bookmarks .= '</ul></p>';
           }
        }
        $det[$i++] = array('key'=>'Bookmarked','val'=>$bookmarks);


        $det[$i++] = array('data' => array('data' => 'Authn logs - last '.$limit_authn, 'class' => 'highlight', 'colspan' => 2));
        foreach ($authn_logs as $ath) {
            $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U')+ j_auth::$timeOffset);
            $detail = $ath->getDetail() . "<br /><small><i>" . $ath->getAgent() . "</i></small>";
            $det[$i++] = array('key' => $date, 'val' => $detail);
        }
        $det[$i++] = array('data' => array('data' => 'Action Logs', 'class' => 'highlight', 'colspan' => 2));
        foreach ($action_logs as $ath) {
            $subtype = $ath->getSubType();
            if ($subtype == 'modification') {
                $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U')+ j_auth::$timeOffset);
                $d = unserialize($ath->getDetail());
                $dstr ='<br />';
                if(is_array($d))
                {
                   foreach($d as $k=>$v)
                   {
                        $dstr .= '<b>'.$k .':</b><br />';
                        if(is_array($v))
                        {
                           foreach($v as $h=>$l)
                           {
                              if(!is_array($l))
                              {
                                 $dstr .= $h .':'.$l.'<br />';
                              }
                               else
                              {  
                                 foreach($l as $lk=>$lv)
                                {
                                    $dstr .= $h .':'.$lk.'::'.$lv.'<br />';
                                }
                              }
                           }
                        }
                   }
                }
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $dstr;
                $det[$i++] = array('key' => $date, 'val' => $detail);
            }
            elseif($subtype == 'create' or $subtype == 'remove')
            {
                $date = date('Y-m-d H:i:s', $ath->getCreated()->format('U')+ j_auth::$timeOffset);
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $ath->getDetail();
                $det[$i++] = array('key' => $date, 'val' => $detail);
            }
        }

        $data['det'] = $det;
        $data['titlepage'] = lang('rr_detforuser').': '.$data['caption']; 
        $data['content_view'] = 'manage/userdetail_view';
        $this->load->view('page', $data);
    }

    public function showlist() {
        $access = $this->zacl->check_acl('', 'read', 'user', '');
        if (!$access) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }

        $users = $this->em->getRepository("models\User")->findAll();
        $userlist = array();
        $showlink = base_url() . 'manage/users/show/';

        foreach ($users as $u) {
            $encoded_username = base64url_encode($u->getUsername());
            $last = $u->getLastlogin();
            if (!empty($last)) {
                $lastlogin = date('Y-m-d H:i:s', $last->format('U')+ j_auth::$timeOffset);
            } else {
                $lastlogin = 'never';
            }
            $ip = null;
            $ip = $u->getIp();
            $userlist[] = array('user' => anchor($showlink . $encoded_username, $u->getUsername()), 'fullname' => $u->getFullname(), 'email' => safe_mailto($u->getEmail()), 'last' => $lastlogin, 'ip' => $ip);
        }
        $data['titlepage'] =  lang('rr_userslist');
        $data['userlist'] = $userlist;
        $data['content_view'] = 'manage/userlist_view';
        $this->load->view('page', $data);
    }

    private function _remove_submit_validate() {
        log_message('debug', '(remove user) validating form initialized');
        $this->form_validation->set_rules('username', 'Username', 'required|min_length[5]|max_length[128]|user_username_exists[username]');
        return $this->form_validation->run();
    }
    
    private function _accessmodify_submit_validate()
    {
        log_message('debug', '(modify authz type) validating form initialized');
        $this->form_validation->set_rules('authz','Access','xss');
        return $this->form_validation->run();
        //return TRUE;
    }

    public function remove() {
        $access = $this->zacl->check_acl('user', 'remove', 'default', '');
        if (!$access) {
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
        } else {
            if (!$this->_remove_submit_validate()) {
                $form_attributes = array('id' => 'formver2', 'class' => 'register');
                $action = base_url() . "manage/users/remove";
                $f = form_open($action, $form_attributes);
                $f .='<div class="small-12 columns">';
                $f .= '<div class="small-3 columns">';
                $f .= jform_label(''.lang('rr_username').'', 'username').'</div>';
                $f .= '<div class="small-6 large-7 end columns">'.form_input('username').'</div>';
                $f .= '</div>';
                $f .= '<div class="buttons small-12 columns"><div class="small-9 large-10 end columns text-right"><button type="submit" name="remove" value="remove" class="resetbutton deleteicon">'.lang('rr_rmuserbtn').'</button></div></div>';
                $f .= form_close();

                $data['form'] = $f;
                $data['titlepage'] = lang('rr_rminguser');
                $data['content_view'] = 'manage/remove_user_view';
                $this->load->view('page', $data);
            } else {
                $this->load->library('user_manage');
                $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $this->input->post('username')));
                if (!empty($user)) {
                    $selected_username = strtolower($user->getUsername());
                    $current_username = strtolower($_SESSION['username']);
                    if ($selected_username != $current_username) {
                        $this->user_manage->remove($user);
                        $data['message'] = 'user has been removed';
                        $this->load->library('tracker');
                        $this->tracker->save_track('user', 'remove', $selected_username, 'user removed from the system', true);
                    } else {
                        $data['message'] = lang('error_cannotrmyouself');
                    }
                } else {
                    $data['message'] = lang('error_usernotexist');
                }
                $data['content_view'] = 'manage/remove_user_view';
                $this->load->view('page', $data);
            }
        }
    }

    public function  accessedit($encoded_username)
    {
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) 
        {
           show_error(lang('error404'), 404);
           return;
        }
        $manage_access = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        if(!$manage_access) 
        { 
            $data['error'] = lang('error403');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        if($this->_accessmodify_submit_validate() === TRUE)
        {
            $i = $this->input->post('authz');
            print_r($i);
        }
        else
        {
            $form_attributes = array('id'=>'formver2','class'=>'span-16');
            $action = current_url();
            $form = form_open($action,$form_attributes);
            $form .= form_fieldset('Access manage for user '.$username);
            $form .= '<ol>';
            $form .= '<li>';
            $form .= form_label('Authorization','authz');
            $form .= '<ol>';
            $form .= '<li>Local authentication'. form_checkbox('authz[local]','1',$user->getLocal()).'</li>';
            $form .= '<li>Federated access'. form_checkbox('authz[federated]','1',$user->getFederated()).'</li>';
            $form .= '</ol>';
            $form .= '</li>';
            $form .= '<li>';
            $form .= form_label('Account enabled','status');
            $form .= '<ol>';
            $form .= '<li>'.form_checkbox('status','1',$user->isEnabled()).'</li>';
            $form .= '</ol>';
            $form .= '</li>';
            $form .= '</ol>';
            $form .= '<div class="buttons"><button type="submit" value="submit" class="savebutton saveicon">'.lang('rr_save').'</button></div';
            $form .= form_fieldset_close();
            $form .= form_close();
            $data['content_view'] = 'manage/user_access_edit_view';
            $data['form'] = $form;
            $this->load->view('page',$data);
            return;
        }
    }

    public function passedit($encoded_username) {
        $username = base64url_decode($encoded_username);
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            show_error( 'User not found', 404);
        }

        $manage_access = $this->zacl->check_acl('u_' . $user->getId(), 'manage', 'user', '');
        $write_access = $this->zacl->check_acl('u_' . $user->getId(), 'write', 'user', '');
        if (!$write_access && !$manage_access) {
            $data['error'] = 'You have no access';
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
            return;
        }
        if(!$this->_modify_submit_validate()) {
            $form_attributes = array('id'=>'formver2','class'=>'register');
            $action = base_url() . "manage/users/passedit/".$encoded_username;
            $form = form_open($action,$form_attributes);
            
            if($write_access  && !$manage_access)
            {
                $form .= '<div class="small-12 columns">';
                $form .= '<div class="small-3 columns">'.jform_label('Current password', 'oldpassword').'</div>';
                $form .= '<div class="small-6 large-6 columns end">'. form_password('oldpassword').'</div>';
                $form .="</div>";
              
            }
            $form .= '<div class="small-12 columns">';
            $form .= '<div class="small-3 columns">'.jform_label('New password', 'password').'</div>';
            $form .= '<div class="small-6 large-6 columns end">'.form_password('password').'</div>';
            $form .= '</div>';
            $form .= '<div class="small-12 columns">';
            $form .= '<div class="small-3 columns">'.jform_label('New password confirmation', 'passwordconf').'</div>';
            $form .= '<div class="small-6 large-6 columns end">'.form_password('passwordconf').'</div>';
            $form .="</div>";
            $form .= '<div class="buttons small-12 columns text-right">';
            $form .= '<div class="small-9 columns "><button type="submit"  name="submit", value="submit" class="button savebutton saveicon">'.lang('rr_changepass').'</button></div>';
            $form .='</div>';
            $form .= form_close();
            $data['message'] = $form;
            $data['titlepage'] = lang('rr_changepass').': '. htmlentities($user->getUsername());
            $data['content_view'] = 'manage/password_change_view';
            $this->load->view('page', $data);
        }
        else
        {
            $oldpassword = $this->input->post('oldpassword');
            $password = $this->input->post('password');
            if($manage_access)
            {
                $user->setPassword($password);
                $user->setLocalEnabled();
                $this->em->persist($user);
                $this->em->flush();
                $data['message'] = ''.lang('rr_passchangedsucces').': '.htmlentities($user->getUsername());
                $data['content_view'] = 'manage/password_change_view';
                $this->load->view('page', $data);
            }
            
        }
    
        
    }

    private function _add() {
        
    }

    private function _modify() {
        
    }

    public function submit($userid) {
        
    }

}
