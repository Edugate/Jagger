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
    private function edit($encodedUsername = null)
    {
        if ($encodedUsername === null) {
            $decodedUsername = $this->session->userdata('username');
        } else {
            $decodedUsername = base64url_decode($encodedUsername);
        }


        /**
         * @var models\User $user
         */
        $user = $this->em->getRepository('models\User')->findOneBy(array('username' => $decodedUsername));
        if ($user === null) {
            show_error(404, 'User not found');
        }

        $data['content_view'] = 'manage/userprofile_edit';
        $this->load->view('page', $data);

    }

}