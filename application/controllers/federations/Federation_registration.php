<?php
if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
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

class Federation_registration extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->helper('url');
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu'=>'register'));
        $this->title = lang('rr_federation_regform_title');
        $this->load->library('approval');
        $this->load->library('zacl');
        MY_Controller::$menuactive = 'reg';
    }

    function index()
    {
                
        $access = $this->zacl->check_acl('federation','read','','');
        if($access)
        {
                $data['titlepage'] =  lang('rr_federation_regform_title');
                $data['content_view'] = 'federation/federation_register_form';
        }
        else
        {
                $data['content_view'] = 'nopermission';
                $data['error'] = lang('rrerror_noperm_regfed');
        }
        $this->load->view('page', $data);
    }

    public function submit()
    {
        if ($this->_submit_validate() === FALSE)
        {
            $this->index();
            return;
        }
        $access = $this->zacl->check_acl('federation','read','','');
        if(!$access)
        {
                $data['content_view'] = 'nopermission';
                $data['error'] = lang('rrerror_noperm_regfed');
                $this->load->view('page', $data);
            
        }
        $fedname = $this->input->post('fedname');
        $fedsysname = $this->input->post('fedsysname');
        $federation = new models\Federation;
        $federation->setName($fedname);
        $federation->setSysname($fedsysname);
        $federation->setUrn($this->input->post('fedurn'));
        $ispub = $this->input->post('ispublic');
        if (!empty($ispub) && $ispub == 'public')
        {
            $federation->publish();
        } else
        {
            $federation->unPublish();
        }
        $federation->setAsActive();
        $federation->setDescription($this->input->post('description'));
        $federation->setTou($this->input->post('termsofuse'));
        $q = $this->approval->addToQueue($federation,'Create');
        $this->em->persist($q);
        /**
         * @todo send mail to confirm link if needed, and to admin for approval
         */
            /**
             * send email
             */

       $nowUtc = new \DateTime( 'now',  new \DateTimeZone( 'UTC' ) );
       
       $templateArgs = array(
         'fedname'=>$fedname,
         'srcip'=>$this->input->ip_address(),
         'requsername'=>$this->j_auth->current_user(),
         'reqemail'=>$q->getEmail(),
         'token'=>$q->getToken(),
         'qurl'=>''.base_url().'reports/awaiting/detail/'.$q->getToken().'',
         'datetimeutc'=>''.$nowUtc->format('Y-m-d h:i:s').' UTC',
       );
      
       $mailTemplate = $this->email_sender->generateLocalizedMail('fedregresquest',$templateArgs);
       if(is_array($mailTemplate))
       {
          $this->email_sender->addToMailQueue(array('greqisterreq','gfedreqisterreq'),null,$mailTemplate['subject'],$mailTemplate['body'],array(),FALSE);
       }
       else
       {
          $sbj = 'Federation registration request';
          $body = 'Dear user'.PHP_EOL;
          $body .= $q->getEmail().' just filled Federation Registration form'.PHP_EOL;
          if(isset($_SERVER['REMOTE_ADDR']))
          {
            $body .= "Requester's IP :". $_SERVER['REMOTE_ADDR']. PHP_EOL;
          }
          $body .= 'Federation name: '.$fedname. PHP_EOL;
          $body .= 'You can approve or reject it on '.base_url().'reports/awaiting/detail/'.$q->getToken().PHP_EOL;
          $this->email_sender->addToMailQueue(array('greqisterreq','gfedreqisterreq'),null,$sbj,$body,array(),FALSE);
       }
       $this->em->flush();
       $data['success'] = lang('rr_fed_req_sent');
       $data['content_view'] = 'federation/success_view';
       $this->load->view('page',$data);
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('fedname', lang('rr_fed_name'), 'required|min_length[5]|max_length[128]|xss_clean|federation_unique[name]');
        $this->form_validation->set_rules('fedsysname', lang('rr_fed_sysname'), 'required|min_length[5]|max_length[128]|alpha_dash|xss_clean|federation_unique[sysname]');
        $this->form_validation->set_rules('fedurn', lang('fednameinmeta'), 'required|min_length[5]|max_length[128]|xss_clean|federation_unique[uri]');
        $this->form_validation->set_rules('description', lang('rr_fed_desc'), 'min_length[5]|max_length[500]|xss_clean');
        $this->form_validation->set_rules('termsofuse', lang('rr_fed_tou'), 'min_length[5]|max_length[1000]|xss_clean');
        return $this->form_validation->run();
    }

}
