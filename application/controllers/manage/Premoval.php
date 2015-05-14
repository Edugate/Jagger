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
class Premoval extends MY_Controller
{


    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
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

    public function providertoremove($id = null)
    {
        if (empty($id) || !ctype_digit($id)) {
            show_error('Not found', 404);
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
        if (empty($provider)) {
            show_error('Provider not found', 404);
        }
        $data = array(
            'showform' => false,
            'error_message' => null,
            'content_view' => 'manage/removeprovider_view',
            'entityid' => $provider->getEntityId(),
            'type' => $provider->getType(),
            'providerid' => $provider->getId(),
            'link' => anchor(base_url() . 'providers/detail/show/' . $provider->getId() . '', '<i class="fi-arrow-right"></i>'),
        );
        $enabled = $provider->getActive();
        $rmaccess = $this->zacl->check_acl($provider->getId(), 'manage', 'entity', '');
        if (!$rmaccess) {
            $data['error_message'] = lang('rr_noperm');
            $data['showform'] = false;
            return $this->load->view('page', $data);
        }


        if ($this->_submitValidate() === TRUE) {
            if ($enabled) {
                show_error('You must change status as inactive first', 403);
            } else {
                $entitytoremove = $this->input->post('entity');
                if (strcmp($entitytoremove, $provider->getEntityId()) != 0) {
                    $data['error_message'] = 'entityID you filled didn\'t match provider\'s entiyID';
                    $data['showform'] = true;
                    $this->load->view('page', $data);
                } else {

                    $this->load->library('ProviderRemover');
                    $federations = $provider->getFederations();
                    $status = $this->providerremover->removeProvider($provider);
                    if ($status) {
                        $this->load->library('j_ncache');
                        $this->j_ncache->cleanProvidersList('idp');
                        $this->j_ncache->cleanProvidersList('sp');

                        $this->load->library('tracker');
                        $this->tracker->remove_ProviderTrack($data['entityid']);

                        foreach ($federations as $f) {
                            $subject = 'Federation members changed';
                            $body = 'Dear user' . PHP_EOL;
                            $body .= 'Provider ' . $provider->getEntityId() . ' has been removed from federation ' . $f->getName() . PHP_EOL;
                            $this->email_sender->addToMailQueue(array('fedmemberschanged'), $f, $subject, $body, array(), false);
                        }
                        $subject = 'Federations members changed';
                        $body = 'Dear user' . PHP_EOL;
                        $body .= 'Provider ' . $provider->getEntityId() . ' has been removed from federations:' . PHP_EOL;
                        foreach ($federations as $f) {
                            $body .= $f->getName() . PHP_EOL;
                        }
                        $this->email_sender->addToMailQueue(array('gfedmemberschanged'), null, $subject, $body, array(), false);
                        $sbj = 'Provider has been removed from system';
                        $body = 'Dear Administrator' . PHP_EOL;
                        $body .= $this->j_auth->current_user() . "(IP:" . $this->input->ip_address() . ") removed provider:" . $data['entityid'] . "from the system" . PHP_EOL;

                        $this->email_sender->addToMailQueue(array(), null, $sbj, $body, array(), false);
                        $this->em->flush();

                        $data['success_message'] = lang('rr_provider') . ' ' . $data['entityid'] . ' ' . lang('rr_hasbeenremoved');
                        $data['showform'] = false;
                        $this->load->view('page', $data);
                    }
                }
            }
        } else {

            if ($enabled) {
                $data['error_message'] = 'Provider is still enabled. To be able remove it you must disable it first';
            }
            $data['showform'] = true;
            $this->load->view('page', $data);
        }

    }

}
