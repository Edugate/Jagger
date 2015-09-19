<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Premoval extends MY_Controller
{



    public function __construct()
    {
        parent::__construct();
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library(array('zacl', 'form_validation'));
        $this->title = lang('rr_rmprovider');
    }

    private function _submitValidate()
    {

        $this->form_validation->set_rules('entity', 'entity', 'trim|required');
        return $this->form_validation->run();
    }

    public function providertoremove($pid = null)
    {
        if (!ctype_digit($pid)) {
            show_error('Not found', 404);
        }

        /**
         * @var models\Provider $provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $pid));
        if ($provider === null) {
            show_error('Provider not found', 404);
        }

        $entityType = $provider->getType();
        $entityID = $provider->getEntityId();
        $providernameinlang = html_escape($provider->getNameToWebInLang(MY_Controller::getLang()));
        $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        if ($entityType === 'SP') {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        }
        $data = array(
            'titlepage' => '<a href="' . base_url() . 'providers/detail/show/' . $provider->getId() . '">' . $providernameinlang . '</a>',
            'subtitlepage' => $this->title,
            'showform' => false,
            'error_message' => null,
            'content_view' => 'manage/removeprovider_view',
            'entityid' => $entityID,
            'type' => $entityType,
            'providerid' => $pid,
            'providernameinlang' => $providernameinlang,
            'providerurl' => base_url('providers/detail/show/' . $pid . ''),
            'link' => anchor(base_url('providers/detail/show/' . $pid . ''), '<i class="fi-arrow-right"></i>'),
            'breadcrumbs' => array(
                $plist,
                array('url' => base_url('providers/detail/show/' . $provider->getId() . ''), 'name' => '' . html_escape($providernameinlang) . ''),
                array('url' => '#', 'name' => lang('rr_rmprovider'), 'type' => 'current'),
            ),
        );
        $isEnabled = $provider->getActive();
        $rmaccess = $this->zacl->check_acl($provider->getId(), 'manage', 'entity', '');
        if ($isEnabled || !$rmaccess) {
            $data['error_message'] = lang('rr_noperm');
            $data['showform'] = false;
            return $this->load->view('page', $data);
        }

        if ($this->_submitValidate() !== true) {
            $data['showform'] = true;
            return $this->load->view('page', $data);
        }


        $entitytoremove = $this->input->post('entity');
        if ($entitytoremove !== $entityID) {
            $data['error_message'] = 'entityID you filled didn\'t match provider\'s entityID';
            $data['showform'] = true;
            return $this->load->view('page', $data);
        }


        $this->load->library(array('ProviderRemover','tracker'));

        $federations = $provider->getFederations();
        $this->providerremover->removeProvider($provider);

        $this->load->library('j_ncache');
        $this->j_ncache->cleanProvidersList('idp');
        $this->j_ncache->cleanProvidersList('sp');
        $this->tracker->remove_ProviderTrack($data['entityid']);
        $msgTwoBody = 'Dear user' . PHP_EOL . 'Provider ' . $provider->getEntityId() . ' has been removed from federations:' . PHP_EOL;
        foreach ($federations as $f) {
            $msgFirstBody = 'Dear user' . PHP_EOL . 'Provider ' . $provider->getEntityId() . ' has been removed from federation ' . $f->getName() . PHP_EOL;
            $this->email_sender->addToMailQueue(array('fedmemberschanged'), $f, 'Federation members changed', $msgFirstBody, array(), false);
            $msgTwoBody .= $f->getName() . PHP_EOL;
        }
        $this->email_sender->addToMailQueue(array('gfedmemberschanged'), null, 'Federations members changed', $msgTwoBody, array(), false);
        $msgThreeBody = 'Dear Administrator' . PHP_EOL . $this->j_auth->current_user() . '(IP:' . $this->input->ip_address() . ') removed provider:' . $data['entityid'] . 'from the system' . PHP_EOL;
        $this->email_sender->addToMailQueue(array(), null, 'Provider has been removed from system', $msgThreeBody, array(), false);
        $this->em->flush();
        $data['success_message'] = lang('rr_provider') . ' ' . $data['entityid'] . ' ' . lang('rr_hasbeenremoved');
        $data['showform'] = false;
        return $this->load->view('page', $data);


    }

}
