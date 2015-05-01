<?php


class Fedactions extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('cert', 'form'));
        MY_Controller::$menuactive = 'fed';
        $this->load->library('j_ncache');
    }


    function changestatus()
    {
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'access denied';
            return;
        }
        $status = trim($this->input->post('status'));
        $fedname = trim($this->input->post('fedname'));
        if (empty($status) || empty($fedname)) {
            set_status_header(403);
            echo 'missing arguments';
            return;
        }
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => '' . htmlspecialchars(base64url_decode($fedname)) . ''));
        if (empty($federation)) {
            set_status_header(404);
            echo 'Federarion not found';
            return;
        }
        $this->load->library('zacl');
        $has_manage_access = $this->zacl->check_acl('f_' . $federation->getId(), 'manage', 'federation', '');
        if (!$has_manage_access) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $currentStatus = $federation->getActive();
        if ($currentStatus && strcmp($status, 'disablefed') == 0) {
            $federation->setAsDisactive();
            $this->em->persist($federation);
            $this->em->flush();
            echo "deactivated";
            return;
        } elseif (!$currentStatus && strcmp($status, 'enablefed') == 0) {
            $federation->setAsActive();
            $this->em->persist($federation);
            $this->em->flush();
            echo "activated";
            return;
        } elseif (!$currentStatus && strcmp($status, 'delfed') == 0) {
            /**
             * @todo finish
             */
            $this->load->library('Approval');
            $q = $this->approval->removeFederation($federation);
            $this->em->persist($q);
            $this->em->flush();
            echo "todelete";
            return;
        }
        set_status_header(403);
        echo "incorrect params sent";
        return;
    }
}