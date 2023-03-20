<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet <middleware-noc@heanet.ie>
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @link      https://jagger.heanet.ie
 */
class Userprofile extends MY_Controller
{
    private $isAdmin;

    public function __construct()
    {
        parent::__construct();
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->isAdmin = $this->jauth->isAdministrator();
        $this->load->library('form_validation');

    }


    /**
     * @param \models\User $user
     * @return bool
     */
    private function validate(models\User $user)
    {

        $islocal = $user->getLocal();
        $this->form_validation->set_rules('username', 'Username', 'trim|required');
        if ($this->input->post('username') !== $user->getUsername()) {
            return false;
        }
        $this->form_validation->set_rules('currentpass', 'Current Password', 'trim');
        $this->form_validation->set_rules('fname', lang('rr_fname'), 'trim|required');
        $this->form_validation->set_rules('sname', lang('rr_surname'), 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('newpassword', lang('rr_npassword'), 'trim');


        $this->form_validation->set_rules('secondf[]', 'second factor', 'trim');
        $npassword = $this->input->post('newpassword');
        if ($npassword !== null && $npassword !== '' && $islocal) {
            $this->form_validation->set_rules('confirmnpassword', lang('rr_npasswordconf'), 'trim|required|matches[newpassword]');
            if (!$this->isAdmin) {
                $this->form_validation->set_message('currentpass_callable', lang('rr_oldpassword').' does not match');
                $this->form_validation->set_rules('currentpass', lang('rr_oldpassword'),
                    array(
                        'trim',
                        'required',
                        array(
                            'currentpass_callable',
                            function ($value) use ($user) {
                                return $user->isPasswordMatch($value);
                            }),
                    ));


            }
        }
        if ($this->isAdmin) {

            $this->form_validation->set_rules('accessroles[]', 'Roles',
                array(
                    'trim',
                    'required',
                    'in_list[Administrator,Member,Guest]',
                )
            );
            $this->form_validation->set_rules('accesstype[]', lang('rr_typeaccess'), 'trim|required|in_list[fed,local]');
        }
        $emailForm = $this->input->post('email');
        $emailUser = $user->getEmail();
        if ($emailForm !== $emailUser) {
            $this->form_validation->set_rules('confirmemail', lang('rr_confirmuemail'), 'trim|required|valid_email|matches[email]');
        }


        return $this->form_validation->run();
    }


    /**
     * @param null $encodedUsername
     */
    public function edit($encodedUsername = null)
    {
        $grantAccess = false;
        $loggedinUser = $this->jauth->getLoggedinUsername();
        if ($loggedinUser === null) {
            show_error('ff', 500);
        }
        if ($encodedUsername === null) {
            $decodedUsername = $loggedinUser;
            $grantAccess = true;
        } else {
            $decodedUsername = base64url_decode($encodedUsername);
            if ($decodedUsername === $loggedinUser || $this->isAdmin) {
                $grantAccess = true;
            }
        }


        if ($grantAccess !== true) {
            show_error('fsdfsd', 403);
        }
        /**
         * @var models\User $user
         */
        try {
            $user = $this->em->getRepository('models\User')->findOneBy(array('username' => $decodedUsername));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }
        if ($user === null) {
            show_error('User not found', 404);
        }

        $this->title = ' ' . html_escape($user->getUsername());

        $data = array(
            'username' => $user->getUsername(),
            'fname' => $user->getGivenname(),
            'sname' => $user->getSurname(),
            'email' => $user->getEmail(),
            'local' => $user->getLocal(),
            'federated' => (bool)$user->getFederated(),
            'isadmin' => $this->isAdmin,
            'show2fa' => false,
            'user2factor' => $user->getSecondFactor(),
            'content_view' => 'manage/userprofile_edit',
            'formaction' => current_url(),
            'userprofileurl' => base_url('manage/users/show/' . base64url_encode($decodedUsername) . ''),

        );

        $systeRoles = array('Administrator', 'Member', 'Guest');
        $userSRolesNames = $user->getSystemRoleNames();

        foreach ($systeRoles as $role) {
            if (in_array($role, $userSRolesNames, true)) {
                $data['roles']['' . $role . ''] = true;
            } else {
                $data['roles']['' . $role . ''] = false;
            }
        }
        $allowed2fglobal = $this->rrpreference->getStatusByName('user2fset');
        if ($this->isAdmin || ($grantAccess && $allowed2fglobal)) {
            $data['show2fa'] = true;
        }
        $allowed2fengines = $this->config->item('2fengines');
        if (!is_array($allowed2fengines)) {
            $allowed2fengines = array();
        }
        $data['allowed2fengines'] = $allowed2fengines;


        if ($this->validate($user) !== true) {
            $this->load->view(MY_Controller::$page, $data);
        } else {

            $fname = $this->input->post('fname');
            $user->setGivenname($fname);
            $sname = $this->input->post('sname');
            $user->setSurname($sname);
            $islocal = $user->getLocal();
            $newpassword = $this->input->post('newpassword');
            if ($islocal && $newpassword !== null && $newpassword !== '') {
                $user->setPassword($newpassword);
            }
            $email = $this->input->post('email');
            $user->setEmail($email);
            if ($this->isAdmin) {
                $newAccessRoles = $this->input->post('accessroles[]');
                /**
                 * @var models\AclRole[]
                 */
                $currenRoles = $user->getRoles();
                foreach ($currenRoles as $currentRole) {
                    $currentRolename = $currentRole->getName();
                    $roleType = $currentRole->getType();
                    $keyNewAccessRoles = array_search($currentRolename, $newAccessRoles, true);
                    if (($roleType === 'system') && !ctype_digit($keyNewAccessRoles)) {
                        $user->unsetRole($currentRole);
                        log_message('debug', 'JANUSZ unset:' . $currentRolename);
                    } else {
                        unset($newAccessRoles['' . $keyNewAccessRoles . '']);
                    }

                }
                if (is_array($newAccessRoles) && count($newAccessRoles) > 0) {
                    $newAclRoles = $this->em->getRepository('models\AclRole')->findBy(array('type' => 'system', 'name' => $newAccessRoles));
                    foreach ($newAclRoles as $nAclR) {
                        $user->setRole($nAclR);
                    }
                }


                $newAccessTypes = $this->input->post('accesstype[]');

                if (is_array($newAccessTypes)) {
                    if (in_array('fed', $newAccessTypes, true)) {
                        $user->setFederatedEnabled();
                    } else {
                        $user->setFederatedDisabled();
                    }
                    if (in_array('local', $newAccessTypes, true)) {
                        $user->setLocalEnabled();
                    } else {
                        $user->setLocalDisabled();
                    }
                }

            }
            if($data['show2fa'] === true)
            {
                $inputSecondF = $this->input->post('secondf[]');
                if(is_array($inputSecondF) && count($inputSecondF) == 1)
                {
                    foreach($inputSecondF as $sFactor)
                    {
                        if($sFactor === 'none')
                        {
                            $user->setSecondFactor(null);
                        }
                        elseif(in_array($sFactor,$allowed2fengines,true)){
                            $user->setSecondFactor($sFactor);
                        }
                    }
                }
            }


            $this->em->persist($user);


            try {
                $this->em->flush();
                $data2 = array(
                    'content_view' => 'manage/userprofileupdatesuccess',
                    'target' => base_url('manage/users/show/' . base64url_encode($user->getUsername())),
                    'msg' => 'Updated'
                );
                $this->load->view(MY_Controller::$page, $data2);
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
                show_error('Interlan', 500);
            }
        }

    }

}
