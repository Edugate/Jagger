<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
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
 * Leavefed Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Leavefed extends MY_Controller
{


    private $current_site;

    public function __construct() {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');


    }

    private function submitValidate() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('fedid', lang('rr_federation'), 'trim|required|numeric');
        $this->form_validation->set_rules('message','Message','trim|required');

        return $this->form_validation->run();
    }

    public function leavefederation($providerid = null) {
        if (!ctype_digit($providerid)) {
            show_error('Incorrect provider id provided', 404);
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid));
        if ($provider === null) {
            show_error('Provider not found', 404);
        }
        $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', strtolower($provider->getType()), '');
        if (!$hasWriteAccess) {
            show_error('No access', 403);
        }
        if ($provider->getLocked()) {
            show_error('Provider is locked', 403);
        }
        $this->load->helper('form');
        $federations = $provider->getFederations();
        $feds_dropdown = array('none' => lang('selectfed'));
        foreach ($federations as $f) {
            $feds_dropdown[$f->getId()] = $f->getName();
        }
        $lang = MY_Controller::getLang();
        $enttype = $provider->getType();

        $providerNameInLang = $provider->getNameToWebInLang($lang, $enttype);
        if (strcasecmp($enttype, 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        } else {
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        }

        $data = array(
            'breadcrumbs'  => array(
                $plist,
                array('url' => base_url('providers/detail/show/' . $provider->getId() . ''), 'name' => '' . html_escape($providerNameInLang) . ''),
                array('url' => '#', 'name' => lang('leavefederation'), 'type' => 'current'),
            ),
            'name'         => $providerNameInLang,
            'titlepage'    => anchor(base_url() . 'providers/detail/show/' . $provider->getId() . '', $providerNameInLang),
            'subtitlepage' => lang('leavefederation'),
            'providertype' => $enttype,
            'providerid' => $providerid

        );


        if ($this->submitValidate() !== true) {
            if (count($feds_dropdown) > 1) {
                $data['feds_dropdown'] = $feds_dropdown;
                $data['showform'] = true;
            } else {
                $data['error_message'] = lang('cantleavefednonefound');
            }
            $data['content_view'] = 'manage/leavefederation_view';

            return $this->load->view(MY_Controller::$page, $data);
        }


        $fedid = $this->input->post('fedid');
        $message = $this->input->post('message');
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if ($federation === null) {
            show_error('Federation you want  to leave doesnt exist', 404);
        }
        /**
         * @var $membership models\FederationMembers
         */
        $membership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $provider->getId(), 'federation' => $federation->getId()));
        if ($membership !== null) {
            $p_tmp = new models\AttributeReleasePolicies;
            $arp_fed = $p_tmp->getFedPolicyAttributesByFed($provider, $federation);
            $rm_arp_msg = '';
            if (is_array($arp_fed) && count($arp_fed) > 0) {
                foreach ($arp_fed as $r) {
                    $this->em->remove($r);
                }
                $rm_arp_msg = 'Also existing attribute release policy for this federation has been removed<br/>' .
                    'It means when in the future you join this federation you will need to set attribute release policy for it again<br />';
            }
            $spec_arps_to_remove = $p_tmp->getSpecCustomArpsToRemove($provider);
            if (!empty($spec_arps_to_remove) && is_array($spec_arps_to_remove) && count($spec_arps_to_remove) > 0) {
                foreach ($spec_arps_to_remove as $rp) {
                    $this->em->remove($rp);
                }
            }

            if ($provider->getLocal()) {
                $membership->setJoinState('2');
                $this->em->persist($membership);
            } else {
                $this->em->remove($membership);

            }
            $this->tracker->save_track(strtolower($provider->getType()), 'request', $provider->getEntityId(), 'left federation: ' . $federation->getName() . '. Message attached: ' . html_escape($message) . '', false);
            try {
                $this->em->flush();
                $data['success_message'] = lang('rr_youleftfed') . ': ' . $federation->getName() . '<br />' . $rm_arp_msg;
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
                $data['error_message'] = 'Unknown error occured';
            }
            $data['content_view'] = 'manage/leavefederation_view';

            return $this->load->view(MY_Controller::$page, $data);

        }
        $data['error_message'] = lang('rr_youleftfed');
        $data['content_view'] = 'manage/leavefederation_view';
        return $this->load->view(MY_Controller::$page, $data);

    }


}
