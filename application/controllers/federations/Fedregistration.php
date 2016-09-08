<?php
if (!defined('BASEPATH')) {
    exit('Ni direct script access allowed');
}
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
 * Federation_registration Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * @property Emailsender $emailsender
 * @property Approval $approval
 */
class Fedregistration extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->helper('url');
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'register'));
        $this->title = lang('rr_federation_regform_title');
        $this->load->library('approval');
        $this->load->library('zacl');
        MY_Controller::$menuactive = 'reg';
    }

    public function index() {

        $access = $this->zacl->check_acl('federation', 'read', '', '');
        if ($access) {
            $data['breadcrumbs'] = array(
                array('url' => base_url('#'), 'name' => lang('rr_federation_regform_title'), 'type' => 'current'),
            );
            $data['titlepage'] = lang('rr_federation_regform_title');
            $data['content_view'] = 'federation/federation_register_form';
        } else {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rrerror_noperm_regfed');
        }
        $this->load->view(MY_Controller::$page, $data);
    }

    public function submit() {
        if ($this->_submit_validate() === false) {
            $this->index();

            return;
        }
        $access = $this->zacl->check_acl('federation', 'read', '', '');
        if (!$access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rrerror_noperm_regfed');
            $this->load->view(MY_Controller::$page, $data);

        }
        $fedname = $this->input->post('fedname');
        $fedsysname = $this->input->post('fedsysname');
        $federation = new models\Federation;
        $federation->setName($fedname);
        $federation->setSysname($fedsysname);
        $federation->setUrn($this->input->post('fedurn'));
        $ispub = $this->input->post('ispublic');
        if ($ispub === 'public') {
            $federation->publish();
        } else {
            $federation->unPublish();
        }
        $federation->setAsActive();
        $federation->setDescription($this->input->post('description'));
        $federation->setTou($this->input->post('termsofuse'));
        $queue = $this->approval->addToQueue($federation, 'Create');
        $this->em->persist($queue);
        /**
         * @todo send mail to confirm link if needed, and to admin for approval
         */
        /**
         * send email
         */

        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        $templateArgs = array(
            'fedname'     => $fedname,
            'srcip'       => $this->input->ip_address(),
            'requsername' => $this->jauth->getLoggedinUsername(),
            'reqemail'    => $queue->getEmail(),
            'token'       => $queue->getToken(),
            'qurl'        => '' . base_url() . 'reports/awaiting/detail/' . $queue->getToken() . '',
            'datetimeutc' => '' . $nowUtc->format('Y-m-d h:i:s') . ' UTC',
        );

        $mailTemplate = $this->emailsender->generateLocalizedMail('fedregresquest', $templateArgs);
        if (is_array($mailTemplate)) {
            $this->emailsender->addToMailQueue(array('greqisterreq', 'gfedreqisterreq'), null, $mailTemplate['subject'], $mailTemplate['body'], array(), false);
        } else {
            $sbj = 'Federation registration request';
            $body = 'Dear user' . PHP_EOL;
            $body .= $queue->getEmail() . ' just filled Federation Registration form' . PHP_EOL;
            $body .= "Requester's IP :" . $this->input->ip_address() . PHP_EOL;
            $body .= 'Federation name: ' . $fedname . PHP_EOL;
            $body .= 'You can approve or reject it on ' . base_url() . 'reports/awaiting/detail/' . $queue->getToken() . PHP_EOL;
            $this->emailsender->addToMailQueue(array('greqisterreq', 'gfedreqisterreq'), null, $sbj, $body, array(), false);
        }
        $this->em->flush();
        $data['success'] = lang('rr_fed_req_sent');
        $data['content_view'] = 'federation/success_view';
        $this->load->view(MY_Controller::$page, $data);
    }

    private function _submit_validate() {
        $fednameMinLength = $this->config->item('fedname_min_length') ?: 5;
        $this->form_validation->set_rules('fedname', lang('rr_fed_name'), 'required|min_length[' . $fednameMinLength . ']|max_length[128]|xss_clean|federation_unique[name]');
        $this->form_validation->set_rules('fedsysname', lang('rr_fed_sysname'), 'required|min_length[' . $fednameMinLength . ']|max_length[128]|alpha_dash|xss_clean|federation_unique[sysname]');
        $this->form_validation->set_rules('fedurn', lang('fednameinmeta'), 'required|min_length[5]|max_length[128]|xss_clean|federation_unique[uri]');
        $this->form_validation->set_rules('description', lang('rr_fed_desc'), 'min_length[5]|max_length[500]|xss_clean');
        $this->form_validation->set_rules('termsofuse', lang('rr_fed_tou'), 'min_length[5]|max_length[1000]|xss_clean');

        return $this->form_validation->run();
    }

}
