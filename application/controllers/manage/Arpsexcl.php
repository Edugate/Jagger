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
 * Idp_edit Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Arpsexcl extends MY_Controller
{

    protected $tmpProviders;

    public function __construct()
    {
        parent::__construct();
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->tmpProviders = new models\Providers;
        $this->load->library(array('formelement', 'form_validation', 'zacl'));
    }

    public function idp($providerID)
    {
        if (!ctype_digit($providerID)) {
            show_error('Incorrect id provided', 404);
        }
        $success = null;
        /**
         * @var $idp models\Provider
         */
        $idp = $this->tmpProviders->getOneIdpById($providerID);
        if (empty($idp)) {
            log_message('error', __METHOD__ . "IdP edit: Identity Provider with id=" . $providerID . " not found");
            show_error(lang('rerror_idpnotfound'), 404);
        }

        $myLang = MY_Controller::getLang();
        $providerNameInLang = $idp->getNameToWebInLang($myLang, 'IDP');
        $locked = $idp->getLocked();
        $hasWriteAccess = $this->zacl->check_acl($idp->getId(), 'write', 'entity', '');
        if (!$hasWriteAccess || $locked) {
            $data = array(
                'content_view' => 'nopermission',
                'error' => '' . lang('rrerror_noperm_provedit') . ': ' . $idp->getEntityId() . '',
                'breadcrumbs' => array(
                    array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
                    array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . $providerNameInLang . ''),
                    array('url' => base_url('manage/attributepolicy/show/' . $idp->getId() . ''), 'name' => '' . lang('rr_attributereleasepolicy') . ''),
                    array('url' => '#', 'name' => lang('rr_arpexcl1'), 'type' => 'current'),

                ),
            );

            return $this->load->view('page', $data);
        }
        $isLocal = $idp->getLocal();
        if (!$isLocal) {
            $data['error'] = anchor(base_url() . "providers/detail/show/" . $idp->getId(), $idp->getName()) . ' ' . lang('rerror_cannotmanageexternal');
            $data['content_view'] = "nopermission";
            $data['breadcrumbs'] = array(
                array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
                array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . $providerNameInLang . ''),
                array('url' => base_url('manage/attributepolicy/show/' . $idp->getId() . ''), 'name' => '' . lang('rr_attributereleasepolicy') . ''),
                array('url' => '#', 'name' => lang('rr_arpexcl1'), 'type' => 'current'),

            );
            return $this->load->view('page', $data);
        }
        if ($this->_submit_validate() === true) {
            $excarray = $this->input->post('exc');
            if (empty($excarray)) {
                $excarray = array();
            }
            foreach ($excarray as $k => $v) {
                if (empty($v)) {
                    unset($excarray[$k]);
                }
            }
            $idp->setExcarps($excarray);
            $this->em->persist($idp);
            try {
                $this->em->flush();

                $success = lang('updated');
            }
            catch(Exception $e)
            {
                log_message('error',__METHOD__.' '.$e);
                show_error('Internal server error',500);
            }

        }


        $this->title = $providerNameInLang . ': ARP excludes';
        $data = array(
            'success'=>$success,
            'rows' => $this->formelement->excludedArpsForm($idp),
            'idp_name' => $idp->getName(),
            'idp_id' => $idp->getId(),
            'idp_entityid' => $idp->getEntityId(),
            'content_view' => 'manage/arpsexcl_view',
            'titlepage' => anchor(base_url() . 'providers/detail/show/' . $idp->getId(), $providerNameInLang),
            'subtitlepage' => lang('rr_arpexcl1'),
            'breadcrumbs' => array(
                array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
                array('url' => base_url('providers/detail/show/' . $idp->getId() . ''), 'name' => '' . $providerNameInLang . ''),
                array('url' => base_url('manage/attributepolicy/show/' . $idp->getId() . ''), 'name' => '' . lang('rr_attributereleasepolicy') . ''),
                array('url' => '#', 'name' => lang('rr_arpexcl1'), 'type' => 'current')
            )
        );

        $this->load->view('page', $data);

    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('idpid' , 'idpid', 'trim|required');
        $exc = $this->input->post('exc');
        if(is_array($exc))
        {
            foreach($exc as $k=>$v)
            {
                $this->form_validation->set_rules('exc['.$k.']' , 'Excluded entity', 'trim');
            }
        }

        return $this->form_validation->run();

    }

}
