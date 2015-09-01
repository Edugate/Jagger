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
        $user = $this->em->getRepository('models\User')->findOneBy(array('username' => $decodedUsername));
        if ($user === null) {
            show_error(404, 'User not found');
        }



        $data = array(
            'username'=>$user->getUsername(),
            'fname'=> $user->getGivenname(),
            'sname'=>$user->getSurname(),
            'email'=>$user->getEmail(),
            'local'=>$user->getLocal(),
            'federated'=>(bool) $user->getFederated(),
            'isadmin' => $this->isAdmin,
            'show2fa' => false,
            'content_view'=> 'manage/userprofile_edit',
        );
        $systeRoles = array('Administrator','Member','Guest');
        $userSRolesNames = $user->getSystemRoleNames();

        foreach($systeRoles as $role)
        {
            if(in_array($role,$userSRolesNames))
            {
                $data['roles'][''.$role.''] = true;
            }
            else
            {
                $data['roles'][''.$role.''] = false;
            }
        }
        $allowed2fglobal = $this->rrpreference->getStatusByName('user2fset');
        if ($this->isAdmin  || ($grantAccess && $allowed2fglobal)) {
            $data['show2fa'] = true;
        }


        $this->load->view('page', $data);

    }

}
