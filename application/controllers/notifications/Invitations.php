<?php
/**
 * Created by PhpStorm.
 * User: janul
 * Date: 18/05/18
 * Time: 13:55
 */

class Invitations extends MY_Controller
{


    public function verification() {
        if (!$this->jauth->isLoggedIn()) {

            show_error('Access denied',401);
        } else {
            $this->load->library('zacl');
            $data['titlepage'] = 'Invitation';
            $data['subtitlepage'] = 'Code verification';
            $data['content_view'] = 'notifications/invitationverify_view';
            $this->load->view(MY_Controller::$page, $data);

        }
    }

    private function validateInvverification() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('token', 'token', 'required|trim|alpha_numeric');
        $this->form_validation->set_rules('verifykey', 'verificationcode', 'required|trim|alpha_numeric');

        return $this->form_validation->run();
    }

    public function test(){
        $inv  = new models\Invitation;
        $inv->setToken();
        $inv->setValidationKey();
        $inv->setActionType('acl');
        $inv->setActionValue('+rw');
        $inv->setTargetId('16');
        $inv->setTargetType('provider');
        $epchNextWeek = time() + (7 * 24 * 60 * 60);
        $inv->setValidTo($epchNextWeek);
        $inv->setValid();
        $inv->setMailFrom('support@example.com');
        $inv->setMailTo('okok@example.com');
        $this->em->persist($inv);
        $this->em->flush();
        echo 'ok';

    }

    public function invverification() {
        if(!$this->jauth->isLoggedIn()){
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
            }
            catch(Exception $e){
                log_message('error', $e);
                return $this->output->set_status_header(500)->set_output('DB ERROR');
            }
            if($provider === null){
                return $this->output->set_status_header(404)->set_output('Target not found');
            }
            if ($actionType === 'acl') {


                if($actionValue === '+rw'){
                    $this->zacl->addAccessToUserByInvitation($targetId, 'write', $currentUser, 'entity', '');
                    $this->zacl->addAccessToUserByInvitation($targetId, 'read', $currentUser, 'entity', '');

                    try {
                        $this->em->flush();
                    }
                    catch (Exception $e){
                        log_message('error', $e);
                        return $this->output->set_status_header(500)->set_output('DB ERROR');
                    }
                    return $this->output->set_output('Permissions has been assigned');
                }
                if($actionValue === '+mngmt'){
                    $this->zacl->addAccessToUserByInvitation($targetId, 'write', $currentUser, 'entity', '');
                    $this->zacl->addAccessToUserByInvitation($targetId, 'read', $currentUser, 'entity', '');
                    $this->zacl->addAccessToUserByInvitation($targetId, 'manage', $currentUser, 'entity', '');

                    try {
                        log_message('error', $e);
                        $this->em->flush();
                    }
                    catch (Exception $e){
                        return $this->output->set_status_header(500)->set_output('DB ERROR');
                    }
                    return $this->output->set_output('Permissions has been assigned');
                }
            }
        }

        return $this->output->set_status_header(401)->set_output('Action not understood');


    }

    public function inviteuser($type, $id){

        if(!$this->jauth->isLoggedIn()){
            return $this->output->set_status_header('401')->set_output('Not authenticated');
        }
        if($this->inviteUserValidateSubmit() !== true){
            return $this->output->set_status_header('401')->set_output(validation_errors());
        }

    }

    private function inviteUserValidateSubmit(){
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email','email','required|trim|valid_email');
        return $this->form_validation->run();
        
    }

}