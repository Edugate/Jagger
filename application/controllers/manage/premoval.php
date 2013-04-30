<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * Premoval Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Premoval extends MY_Controller {

    private $tmp_providers;
    private $tmp_attributes;
    private $tmp_arps;
    private $other_error;
    private $access;

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', base_url());
            redirect('auth/login', 'refresh');
        }
        $this->load->library('zacl');
        $this->load->library('form_validation');
    }

    private function _submitValidate()
    {

        $this->form_validation->set_rules('entity', 'entity', 'required');
        return $this->form_validation->run();
    }

    public function providertoremove($id)
    {
        $data['showform'] = false;
        $data['error_message'] = null;
        $data['content_view'] = 'manage/removeprovider_view';

        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
        if (empty($provider))
        {
            show_error('Provider not found', 404);
        }
        $data['entityid'] = $provider->getEntityId();
        $data['type'] = $provider->getType();
        $data['providerid'] = $provider->getId();
        $data['link'] = anchor(base_url() . 'providers/detail/show/'. $data['providerid'], '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        $enabled = $provider->getActive();
        $rmaccess = $this->zacl->check_acl($provider->getId(), 'manage', 'entity', '');
        if (!$rmaccess)
        {
            $data['error_message'] = lang('rr_noperm');
            $data['showform'] = false;
            $this->load->view('page', $data);
        }
        else
        {


            if ($this->_submitValidate() === TRUE)
            {
                if ($enabled)
                {
                    show_error('You must change status as inactive first', 403);
                }
                else
                {
                    $entitytoremove = $this->input->post('entity');
                    if (!($entitytoremove === $provider->getEntityId()))
                    {
                        $data['error_message'] = 'entityID you filled didn\'t match provider\'s entiyID';
                        $data['showform'] = true;
                        $this->load->view('page', $data);
                    }
                    else
                    {

                        $this->load->library('ProviderRemover');
                        $status = $this->providerremover->removeProvider($provider);
                        if ($status)
                        {
                            $this->load->library('tracker');
                            $this->remove_ProviderTrack($data['entityid']);
                            $this->em->flush();
                            $recipients = array();
                            $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name'=>'Administrator'));
                            $a_members = $a->getMembers();
                            foreach($a_members as $m)
                            {
                                 $recipients[] = $m->getEmail();
                            }
                            $sbj = 'Provider has been removed from system';
                            $body = "Dear Administrator\r\n";
                            $body .= $this->j_auth->current_user(). "(IP:".$_SERVER['REMOTE_ADDR'].") removed provider:". $data['entityid']. "from the system\r\n";
                            $this->load->library('email_sender');
                            $this->email_sender->send($recipients,$sbj,$body); 
                            $data['success_message'] = lang('rr_provider') . ' ' . $data['entityid'] . ' ' . lang('rr_hasbeenremoved');
                            $data['showform'] = false;
                            $this->load->view('page', $data);
                        }
                    }
                }
            }
            else
            {

                if ($enabled)
                {
                    $data['error_message'] = 'Provider is still enabled. To be able remove it you must disable it first';
                }
                $data['showform'] = true;
                $this->load->view('page', $data);
            }
        }
    }

}
