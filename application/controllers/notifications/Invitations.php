<?php
/**
 * Created by PhpStorm.
 * User: janul
 * Date: 18/05/18
 * Time: 13:55
 */

class Invitations extends MY_Controller
{


    public function verification()
    {
        if (!$this->jauth->isLoggedIn()) {

            show_error('Access denied', 401);
        } else {
            $this->load->library('zacl');
            $data['titlepage'] = 'Invitation';
            $data['subtitlepage'] = 'Code verification';
            $data['content_view'] = 'notifications/invitationverify_view';
            $this->load->view(MY_Controller::$page, $data);

        }
    }

    private function validateInvverification()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('token', 'token', 'required|trim|alpha_numeric');
        $this->form_validation->set_rules('verifykey', 'verificationcode', 'required|trim|alpha_numeric');

        return $this->form_validation->run();
    }
    

    public function invverification()
    {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        if ($this->validateInvverification() !== true) {

            return $this->output->set_status_header(401)->set_output(validation_errors());
        }
        /**
         * @var models\Invitation $invitation
         */
        $token = $this->input->post('token');
        $verifyKey = $this->input->post('verifykey');

        $invitation = $this->em->getRepository("models\Invitation")->findOneBy(array('token' => $token));
        if (null === $invitation) {
            return $this->output->set_status_header(404)->set_output('Invition not found');
        }
        $isValid = $invitation->isInvitationValid($token, $verifyKey);
        if (!$isValid) {
            return $this->output->set_status_header(401)->set_output('The invidation:  unknown/invalid/expired');
        }
        $targetId = $invitation->getTargetId();
        $targetType = $invitation->getTargetType();
        $actionType = $invitation->getActionType();
        $actionValue = $invitation->getActionValue();
        $currentUser = $this->jauth->getLoggedinUsername();
        if ($currentUser === null) {
            return $this->output->set_status_header(401)->set_output('Access denied');
        }

        $invitation->setInvalid();
        $this->load->library('zacl');
        /**
         * targetType = provider
         * targetId = providerid
         * actionType = acl
         * actionValue = +/-ro,rw,mngmt
         */
        if ($targetType === 'provider') {
            try {
                $provider = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $targetId));
            } catch (Exception $e) {
                log_message('error', $e);
                return $this->output->set_status_header(500)->set_output('DB ERROR');
            }
            if ($provider === null) {
                return $this->output->set_status_header(404)->set_output('Target not found');
            }
            if ($actionType === 'acl') {


                if ($actionValue === '+rw') {
                    $this->zacl->addAccessToUserByInvitation($targetId, 'write', $currentUser, 'entity', '');
                    $this->zacl->addAccessToUserByInvitation($targetId, 'read', $currentUser, 'entity', '');

                    try {
                        $this->em->flush();
                    } catch (Exception $e) {
                        log_message('error', $e);
                        return $this->output->set_status_header(500)->set_output('DB ERROR');
                    }
                    return $this->output->set_output('Permissions has been assigned');
                }
                if ($actionValue === '+mngmt') {
                    $this->zacl->addAccessToUserByInvitation($targetId, 'write', $currentUser, 'entity', '');
                    $this->zacl->addAccessToUserByInvitation($targetId, 'read', $currentUser, 'entity', '');
                    $this->zacl->addAccessToUserByInvitation($targetId, 'manage', $currentUser, 'entity', '');

                    try {

                        $this->em->flush();
                    } catch (Exception $e) {
                        log_message('error', $e);
                        return $this->output->set_status_header(500)->set_output('DB ERROR');
                    }
                    return $this->output->set_output('Permissions has been assigned');
                }
            }
        }

        return $this->output->set_status_header(401)->set_output('Action not understood');


    }

    public function inviteuser($type, $id)
    {

        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header('401')->set_output('Not authenticated');
        }
        if ($this->inviteUserValidateSubmit() !== true) {
            return $this->output->set_status_header('401')->set_output(validation_errors());
        }
        /**
         * @var models\Invitation $invitation
         * @var models\User $user ;
         */
        $loggeduser = $this->jauth->getLoggedinUsername();

        $user = $this->em->getRepository('models\User')->findOneBy(array('username' => $loggeduser));

        $aclInput = $this->input->post('permissions');

        $acl = null;
        if ($aclInput === 'plusrw') {
            $acl = '+rw';
        } elseif ($aclInput === 'plusmanage') {
            $acl = '+mngmt';
        }


        $emailFrom = $user->getEmail();

        $invitation = new models\Invitation();
        $invitation->setMailFrom($emailFrom);
        $invitation->setToken();
        $invitation->setValidationKey();
        $invitation->setTargetType('provider');
        $invitation->setTargetId($id);
        $invitation->setActionType('acl');
        $invitation->setActionValue($acl);
        $invitation->setMailTo($this->input->post('email'));
        $invitation->setValid();
        $this->em->persist($invitation);

        $subject = "Invitation";
        $body = "Dear User".PHP_EOL.
            "Please log into ".base_url().' and provide following token and verification key on '.base_url('notifications/invitations/verification').PHP_EOL.
            "Token: ".$invitation->getToken().PHP_EOL.
            "Veryfication key:  ".$invitation->getValidationKey().PHP_EOL.PHP_EOL.
            "After successful validation you get higer access level on ".base_url('providers/detail/show/'.$id);

        $this->load->library('emailsender');
        $this->emailsender->addToMailQueue(null,null,$subject,$body,array($this->input->post('email')),false);

        $this->em->flush();

        return $this->output->set_status_header('401')->set_output('fdddff');

    }

    private function inviteUserValidateSubmit()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'email', 'required|trim|valid_email');
        $this->form_validation->set_rules('email_confirm', 'email confirm', 'required|trim|valid_email|matches[email]');
        $this->form_validation->set_rules('permissions', 'access level', 'trim|required|in_list[plusmanage,plusrw]');
        return $this->form_validation->run();

    }

}