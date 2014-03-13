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
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->load->library('form_validation');
    }

    private function _submitValidate()
    {

        $this->form_validation->set_rules('entity', 'entity', 'required');
        return $this->form_validation->run();
    }

    public function providertoremove($id=null)
    {
        if(empty($id) || !ctype_digit($id))
        {
            show_error('Not found',404);
            return;
        }   
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
                    if (strcmp($entitytoremove,$provider->getEntityId())!=0)
                    {
                        $data['error_message'] = 'entityID you filled didn\'t match provider\'s entiyID';
                        $data['showform'] = true;
                        $this->load->view('page', $data);
                    }
                    else
                    {

                        $this->load->library('ProviderRemover');
                        $federations = $provider->getFederations();
                        $status = $this->providerremover->removeProvider($provider);
                        if ($status)
                        {
                            $this->load->library('tracker');
                            $this->tracker->remove_ProviderTrack($data['entityid']);
                        
                            foreach($federations as $f)
                            {
                               $subject = 'Federation members changed';
                               $body = 'Dear user'.PHP_EOL;
                               $body .= 'Provider '.$provider->getEntityId(). ' has been removed from federation '.$f->getName().PHP_EOL; 
                               $this->email_sender->addToMailQueue(array('fedmemberschanged'),$f,$subject,$body,array(),false);
                            }
                            $subject = 'Federations members changed';
                            $body = 'Dear user'.PHP_EOL;
                            $body .= 'Provider '.$provider->getEntityId(). ' has been removed from federations:'.PHP_EOL;
                            foreach($federations as $f)
                            {
                                $body .=$f->getName().PHP_EOL;
                            }
                            $this->email_sender->addToMailQueue(array('gfedmemberschanged'),null,$subject,$body,array(),false) ;
                            $sbj = 'Provider has been removed from system';
                            $body = 'Dear Administrator'.PHP_EOL;
                            $body .= $this->j_auth->current_user(). "(IP:".$_SERVER['REMOTE_ADDR'].") removed provider:". $data['entityid']. "from the system".PHP_EOL;
                            
                            $this->email_sender->addToMailQueue(array(),null,$sbj,$body,array(),false);              
                            $this->em->flush();

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
