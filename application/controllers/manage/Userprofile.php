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
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->isAdmin = $this->j_auth->isAdministrator();
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
        $this->form_validation->set_rules('fname', 'First name', 'trim|required');
        $this->form_validation->set_rules('sname', 'S name', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('newpassword', 'New pass', 'trim');
        $this->form_validation->set_rules('accesstype[]', 'Access type', 'trim|in_list[fed,local]');
        $this->form_validation->set_rules('accessroles[]', 'Access role', 'trim[Administrator,Member,Guest]');
        $this->form_validation->set_rules('secondf[]', 'second factor', 'trim');
        $this->form_validation->run();

        $accessroles = $this->input->post('accessroles[]');

        $npassword = $this->input->post('newpassword');
        if ($npassword !== null && $npassword !== '' && $islocal) {
            $this->form_validation->set_rules('confirmnpassword', 'conf New pass', 'trim|required|matches[newpassword]');
            if (!$this->isAdmin) {
                $this->form_validation->set_message('currentpass_callable', 'Current password does not match');
                $this->form_validation->set_rules('currentpass', 'Current Password',
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
        $emailForm = $this->input->post('email');
        $emailUser = $user->getEmail();
        if ($emailForm !== $emailUser) {
            $this->form_validation->set_rules('confirmemail', 'Confirm Email', 'trim|required|valid_email|matches[email]');
        }


        return $this->form_validation->run();
    }


    /**
     * @param null $encodedUsername
     */
    public function edit($encodedUsername = null)
    {
        $grantAccess = false;
        $loggedinUser = $this->j_auth->current_user();
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
        }
        catch(Exception $e)
        {
            log_message('error',__METHOD__.' '.$e);
            show_error('Internal server error',500);
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
            $this->load->view('page', $data);
        } else {

            $fname = $this->input->post('fname');
            $user->setGivenname($fname);
            $sname = $this->input->post('sname');
            $user->setSurname($sname);
            $islocal = $user->getLocal();
            $newpassword = $this->input->post('newpassword');
            if($islocal && $newpassword !== null && $newpassword !== '')
            {
                $user->setPassword($newpassword);
            }
            $email = $this->input->post('email');
            $user->setEmail($email);
            if($this->isAdmin)
            {

            }


            $this->em->persist($user);


            try {
                $this->em->flush();
                $data2 = array(
                    'content_view' => 'manage/userprofileupdatesuccess',
                    'target' => base_url('manage/users/show/' . base64url_encode($user->getUsername())),
                    'msg' => 'Updated'
                );
                $this->load->view('page', $data2);
            }
            catch(Exception $e)
            {
                log_message('error', __METHOD__. ' '.$e);
                show_error('Interlan', 500);
            }
        }

    }

}
