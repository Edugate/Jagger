<?php


class Attributepolicy2 extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }


    public function show($idpid = null)
    {
        if (!ctype_digit($idpid)) {
            show_404();
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }

        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id'=>$idpid));
        if($ent === null)
        {
            show_404();
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if(!$hasWriteAccess)
        {
            show_error('Denied',401);
        }
        $this->load->library('arpgen');
        $data['arpgenresult'] = $this->arpgen->genPolicyDefs($ent);



        $data['content_view'] = 'manage/attributepolicy2_view';
        $this->load->view('page', $data);
    }

}