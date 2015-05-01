<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * Manage Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * @todo add permission to check for public or private perms
 */
class Manage extends MY_Controller
{

    private $tmp_providers;

    function __construct()
    {
        parent::__construct();
        $this->current_site = current_url();
        $this->load->helper(array('cert', 'form'));
        $this->session->set_userdata(array('currentMenu' => 'federation'));
        /**
         * @todo add check loggedin
         */
        $this->tmp_providers = new models\Providers;
        MY_Controller::$menuactive = 'fed';
        $this->load->library('j_ncache');
    }

    function index()
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->title = lang('title_fedlist');
        /**
         * @var $federationCategories models\FederationCategory[]
         */
        $federationCategories = $this->em->getRepository("models\FederationCategory")->findAll();
        $data = array(
            'categories' => array(),
            'titlepage' => lang('rr_federation_list'),
            'content_view' => 'federation/list_view.php',
            'breadcrumbs' => array(
                array('url' => base_url('federations/manage'), 'name' => lang('rr_federations'), 'type' => 'current')
            ),
        );
        foreach ($federationCategories as $v) {
            $data['categories'][] = array('catid' => '' . $v->getId() . '',
                'name' => '' . $v->getName() . '',
                'title' => '' . $v->getFullName() . '',
                'desc' => '' . $v->getDescription() . '',
                'default' => '' . $v->isDefault() . '');
        }
        $this->load->view('page', $data);

    }

    private function getMembers(models\Federation $federation, $lang)
    {
        $cachedResult = $this->j_ncache->getFederationMembers($federation->getId(), $lang);
        if (!empty($cachedResult)) {
            log_message('debug', __METHOD__ . ' retrieved fedmembers (lang:' . $lang . ') from cache');
            return $cachedResult;
        } else {
            log_message('debug', __METHOD__ . ' no data in cache');
        }


        $tmpProviders = new models\Providers();
        /**
         * @var $membership models\Provider[]
         */
        $membership = $tmpProviders->getFederationMembersInLight($federation);

        $membersInArray = array('idp' => array(), 'sp' => array(), 'both' => array(), 'definitions' => array(
            'idp' => lang('identityproviders'),
            'sp' => lang('serviceproviders'),
            'both' => lang('identityserviceproviders'),
            'preurl' => base_url() . 'providers/detail/show/',
            'nomembers' => lang('rr_nomembers'),

        ));
        foreach ($membership as $p) {
            $regdate = $p->getRegistrationDate();
            if (!empty($regdate)) {
                $preg = date('Y-m', $regdate->format('U'));

            } else {
                $preg = null;
            }
            $m = $p->getMembership()->first();
            $ptype = strtolower($p->getType());
            if ($ptype === 'idp') {
                $name = $p->getNameToWebInLang($lang, 'idp');
            } else {
                $name = $p->getNameToWebInLang($lang, 'sp');
            }
            $membersInArray['' . $ptype . ''][] = array(
                'pid' => $p->getId(),
                'mdisabled' => (int)$m->isDisabled(),
                'mbanned' => (int)$m->isBanned(),
                'entityid' => $p->getEntityId(),
                'pname' => html_escape($name),
                'penabled' => $p->getAvailable(),
                'regdate' => $preg
            );
        }

        $this->j_ncache->saveFederationMembers($federation->getId(), $lang, $membersInArray);

        return $membersInArray;
    }

    function showmembers($fedid)
    {
        if (!$this->input->is_ajax_request()) {
            set_status_header(404);
            echo 'Request not allowed';
            return;
        }
        if (!$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'access denied. invalid session';
            return;
        }
        $lang = MY_Controller::getLang();

        $this->load->library('zacl');

        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if (empty($federation)) {
            set_status_header(404);
            echo 'Federarion not found';
            return;
        }

        $result = $this->getMembers($federation, $lang);
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    function fedmemberscount($fedid)
    {
        if (!$this->input->is_ajax_request()) {
            set_status_header(404);
            echo 'Request not allowed';
            return;
        }
        $lang = MY_Controller::getLang();
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if (empty($federation)) {
            set_status_header(404);
            echo 'Federarion not found';
            return;
        }
        $preresult = $this->getMembers($federation, $lang);
        $result = array('idp' => count($preresult['idp']), 'sp' => count($preresult['sp']), 'both' => count($preresult['both']), 'definitions' => $preresult['definitions']);
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    function showcontactlist($fed_name, $type = NULL)
    {
        if (!$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $this->load->library('zacl');
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($fed_name)));
        if (empty($federation)) {
            show_error('Federation not found', 404);
            return;
        }
        /**
         * @var $federationMembers models\Provider[]
         */
        $federationMembers = $federation->getActiveMembers();
        $members_ids = array();
        if (!empty($type) && (strcasecmp($type, 'idp') == 0 || strcasecmp($type, 'sp') == 0)) {
            foreach ($federationMembers as $m) {
                $entype = $m->getType();
                if (strcasecmp($entype, $type) == 0) {
                    $members_ids[] = $m->getId();
                }
            }
        } else {
            foreach ($federationMembers as $m) {
                $members_ids[] = $m->getId();
            }
        }
        if (count($members_ids) == 0) {
            show_error(lang('error_nomembersforfed'), 404);
            return;
        }
        /**
         * @var $contacts models\Contact[]
         */
        $contacts = $this->em->getRepository("models\Contact")->findBy(array('provider' => $members_ids));
        $cont_array = array();
        foreach ($contacts as $c) {
            $cont_array[$c->getEmail()] = $c->getFullName();
        }
        $this->output->set_content_type('text/plain');
        $result = "";
        foreach ($cont_array as $key => $value) {
            $result .= $key . ';' . trim($value) . ';' . PHP_EOL;
        }

        $this->load->helper('download');
        $filename = 'federationcontactlist.txt';
        force_download($filename, $result, 'text/plain');
    }

    private function showMetadataTab(models\Federation $federation, $hasWriteAccess)
    {
        $d = array();
        $altMetaUrlEnabled = $federation->getAltMetaUrlEnabled();

        if ($altMetaUrlEnabled === TRUE) {
            $altMetaUrl = $federation->getAltMetaUrl();
            $lbl = lang('metapublicationurl');
            $d[] = array($lbl, anchor($altMetaUrl));
            return $d;
        }

        $defaultDigest = $this->config->item('signdigest');
        if (empty($defaultDigest)) {
            $defaultDigest = 'SHA-1';
        }
        $digest = $federation->getDigest();
        if (empty($digest)) {
            $digest = $defaultDigest;
        }
        $digestExport = $federation->getDigestExport();
        if (empty($digestExport)) {
            $digestExport = $defaultDigest;
        }
        $metaLink = base_url() . 'metadata/federation/' . $federation->getSysname() . '/metadata.xml';
        $metaLinkSigned = base_url() . 'signedmetadata/federation/' . $federation->getSysname() . '/metadata.xml';
        $metaExportLink = base_url() . 'metadata/federationexport/' . $federation->getSysname() . '/metadata.xml';
        $metaExportLinkSigned = base_url() . 'signedmetadata/federationexport/' . $federation->getSysname() . '/metadata.xml';
        if ($federation->getAttrsInmeta()) {
            $d[] = array('data' => array('data' => '<div data-alert class="alert-box info">' . lang('rr_meta_with_attr') . '</div>', 'class' => '', 'colspan' => 2));
        } else {
            $d[] = array('data' => array('data' => '<div data-alert class="alert-box info">' . lang('rr_meta_with_noattr') . '</div>', 'class' => '', 'colspan' => 2));
        }
        if (!$federation->getActive()) {
            $d[] = array(lang('rr_fedmetaunsingedlink'), '<span class="lbl lbl-disabled fedstatusinactive">' . lang('rr_fed_inactive') . '</span> ' . $metaLink);
            $d[] = array(lang('rr_fedmetasingedlink'), '<span class="lbl lbl-disabled fedstatusinactive">' . lang('rr_fed_inactive') . '</span> ' . $metaLinkSigned);
        } else {
            $d[] = array(
                lang('rr_fedmetaunsingedlink'),
                $metaLink . ' ' . anchor($metaLink, '<i class="fi-arrow-right"></i>', 'class="showmetadata"')
            );
            $d[] = array(
                lang('rr_fedmetasingedlink') . ' <span class="label">' . $digest . '</span>', $metaLinkSigned . " " . anchor_popup($metaLinkSigned, '<i class="fi-arrow-right"></i>'));
        }
        $lexportenabled = $federation->getLocalExport();
        if ($lexportenabled === TRUE) {
            $d[] = array(lang('rr_fedmetaexportunsingedlink'), $metaExportLink . " " . anchor_popup($metaExportLink, '<i class="fi-arrow-right"></i>', 'class="showmetadata"'));
            $d[] = array(lang('rr_fedmetaexportsingedlink') . ' <span class="label">' . $digestExport . '</span>', $metaExportLinkSigned . " " . anchor_popup($metaExportLinkSigned, '<i class="fi-arrow-right"></i>'));
        }
        if ($federation->getActive()) {
            $gearmanenabled = $this->config->item('gearman');
            if ($hasWriteAccess && !empty($gearmanenabled)) {
                $d[] = array('' . lang('signmetadata') . showBubbleHelp(lang('rhelp_signmetadata')) . '', '<a href="' . base_url() . 'msigner/signer/federation/' . $federation->getId() . '" id="fedmetasigner"/><button type="button" class="savebutton staricon tiny">' . lang('btn_signmetadata') . '</button></a>', '');
            }
        }

        return $d;
    }

    function show($encodedFedName)
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->load->library('show_element');
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($encodedFedName)));
        if (empty($federation)) {
            show_error(lang('error_fednotfound'), 404);
            return;
        }
        $resource = $federation->getId();
        $group = 'federation';
        $access = array(
            'hasReadAccess' => $this->zacl->check_acl('f_' . $resource, 'read', $group, ''),
            'hasWriteAccess' => $this->zacl->check_acl('f_' . $resource, 'write', $group, ''),
            'hasAddbulkAccess' => $this->zacl->check_acl('f_' . $resource, 'addbulk', $group, ''),
            'hasManageAccess' => $this->zacl->check_acl('f_' . $resource, 'manage', $group, '')
        );
        $canEdit = (boolean)($access['hasManageAccess'] || $access['hasWriteAccess']);
        $editAttributesLink = '';

        $this->title = lang('rr_federation_detail');

        $breadcrumbs = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => '#', 'name' => '' . $federation->getName() . '', 'type' => 'current'),

        );

        if (!$access['hasReadAccess'] && ($federation->getPublic() === FALSE)) {
            $data = array(
                'content_view' => 'nopermission',
                'error' => lang('rrerror_noperm_viewfed'),
                'breadcrumbs' => $breadcrumbs
            );
            return $this->load->view('page', $data);
        }


        if (!$canEdit) {
            $sideicons[] = '<a href="#" title="' . lang('noperm_fededit') . '"><i class="fi-prohibited"></i></a>';

        } else {
            $sideicons[] = '<a href="' . base_url() . 'manage/fededit/show/' . $federation->getId() . '" title="' . lang('rr_fededit') . '"><i class="fi-pencil"></i></a>';
            $editAttributesLink = '<a href="' . base_url() . 'manage/attribute_requirement/fed/' . $federation->getId() . ' " class="editbutton editicon button small">' . lang('rr_edit') . ' ' . lang('rr_attributes') . '</a>';
        }

        $bookmarked = false;
        $b = $this->session->userdata('board');
        if (!empty($b) && is_array($b) && isset($b['fed'][$data['' . $federation->getId() . '']])) {
            $bookmarked = true;
        } else {
            $sideicons[] = '<a href="' . base_url() . 'ajax/bookfed/' . $data['federation_id'] . '" class="updatebookmark bookentity"  data-jagger-bookmark="add" title="Add to dashboard"><i class="fi-plus"></i></a>';

        }


        $data = array(
            'federation_id' => $federation->getId(),
            'bookmarked' => $bookmarked,
            'federation_name' => html_escape($federation->getName()),
            'federation_sysname' => html_escape($federation->getSysname()),
            'federation_urn' => html_escape($federation->getUrn()),
            'federation_desc' => html_escape($federation->getDescription()),
            'federation_is_active' => $federation->getActive(),
            'titlepage' => lang('rr_feddetail') . ': ' . html_escape($federation->getName()),
            'content_view' => 'federation/federation_show_view',
            'breadcrumbs' => $breadcrumbs,
            'fedpiechart' => '<div class="row"><div><canvas id="fedpiechart" ></canvas></div><div id="fedpiechartlegend"></div></div>',
            'sideicons' => &$sideicons,
            'result' => array('fvalidators' => array()),
        );
        if ($access['hasWriteAccess']) {
            $data['fvalidator'] = TRUE;
            $addbtn = '<a href="' . base_url('manage/fvalidatoredit/vedit/' . $federation->getId() . '') . '" class="button small">' . lang('rr_add') . '</a>';
            $data['result']['fvalidators'][] = array('data' => array('data' => $addbtn, 'class' => 'text-right', 'colspan' => 2));
        }

        $requiredAttributes = $federation->getAttributesRequirement()->getValues();


        if (empty($data['federation_is_active'])) {
            $data['result']['general'][] = array(
                'data' => array('data' => '<div data-alert class="alert-box alert">' . lang('rr_fed_inactive_full') . '</div>', 'class' => 'fedstatusinactive', 'colspan' => 2)
            );
        } else {
            $data['result']['general'][] = array(
                'data' => array('data' => '<div data-alert class="alert-box alert">' . lang('rr_fed_inactive_full') . '</div>', 'class' => 'fedstatusinactive', 'style' => 'display: none', 'colspan' => 2)
            );
        }
        $idpContactList = anchor(base_url() . 'federations/manage/showcontactlist/' . $encodedFedName . '/idp', lang('rr_fed_cntidps_list') . ' <i class="fi-download"></i>');
        $spContactList = anchor(base_url() . 'federations/manage/showcontactlist/' . $encodedFedName . '/sp', lang('rr_fed_cntisps_list') . ' <i class="fi-download"></i>');
        $allContactList = anchor(base_url() . 'federations/manage/showcontactlist/' . $encodedFedName . '', lang('rr_fed_cnt_list') . ' <i class="fi-download"></i>');

        $general = array(
            array(lang('rr_fed_name'), html_escape($federation->getName())),
            array(lang('fednameinmeta'), html_escape($federation->getUrn())),
            array(lang('rr_fed_sysname'), html_escape($federation->getSysname())),
            $this->genEntitiesDescriptorId($federation),
            array(lang('rr_fed_publisher'), html_escape($federation->getPublisher())),
            array(lang('rr_fed_desc'), html_escape($federation->getDescription())),
            array(lang('rr_fed_tou'), html_escape($federation->getTou())),
            array(lang('rr_downcontactsintxt'), $idpContactList . '<br />' . $spContactList . '<br />' . $allContactList),
            array(lang('rr_timeline'), '<a href="' . base_url('reports/timelines/showregistered/' . $federation->getId() . '') . '" class="button secondary">Diagram</a>')
        );

        $data['result']['general'] = array_merge($data['result']['general'], $general);


        $data['result']['attrs'][] = array('data' => array('data' => $editAttributesLink . '', 'class' => 'text-right', 'colspan' => 2));
        if (!$access['hasWriteAccess']) {
            $data['result']['attrs'][] = array('data' => array('data' => '<div class="notice"><small>' . lang('rr_noperm_edit') . '</small></div>', 'colspan' => 2));
        }
        foreach ($requiredAttributes as $key) {
            $data['result']['attrs'][] = array($key->getAttribute()->getName(), $key->getStatus() . "<br /><i>(" . html_escape($key->getReason()) . ")</i>");
        }


        $data['result']['membership'][] = array('data' => array('data' => lang('rr_membermanagement'), 'class' => 'highlight', 'colspan' => 2));
        if (!$access['hasAddbulkAccess']) {
            $data['result']['membership'][] = array('data' => array('data' => '<div class="notice"><small>' . lang('rr_noperm_bulks') . '</small></div>', 'colspan' => 2));
        } else {
            $data['result']['membership'][] = array('IDPs', lang('rr_addnewidpsnoinv') . anchor(base_url() . 'federations/fedactions/addbulk/' . $encodedFedName . '/idp', '<i class="fi-arrow-right"></i>'));

            $data['result']['membership'][] = array('SPs', lang('rr_addnewspsnoinv') . anchor(base_url() . 'federations/fedactions/addbulk/' . $encodedFedName . '/sp', '<i class="fi-arrow-right"></i>'));
        }
        if ($access['hasWriteAccess']) {
            $data['result']['membership'][] = array(lang('rr_fedinvitation'), lang('rr_fedinvidpsp') . anchor(base_url() . 'federations/manage/inviteprovider/' . $encodedFedName . '', '<i class="fi-arrow-right"></i>'));
            $data['result']['membership'][] = array(lang('rr_fedrmmember'), lang('rr_fedrmidpsp') . anchor(base_url() . 'federations/manage/removeprovider/' . $encodedFedName . '', '<i class="fi-arrow-right"></i>'));
        } else {
            $data['result']['membership'][] = array('data' => array('data' => '<div class="notice"><small>' . lang('rr_noperm_invmembers') . '</small></div>', 'colspan' => 2));
        }


        if ($access['hasManageAccess']) {
            $data['result']['management'][] = array('data' => array('data' => lang('access_mngmt') . anchor(base_url() . 'manage/access_manage/federation/' . $resource, '<i class="fi-arrow-right"></i>'), 'colspan' => 2));
            $data['hiddenspan'] = '<span id="fednameencoded" style="display:none">' . $encodedFedName . '</span>';
            if ($federation->getActive()) {
                $b = '<button type="button" name="fedstatus" value="disablefed" class="resetbutton reseticon alert small" title="' . lang('btn_deactivatefed') . ': ' . html_escape($federation->getName()) . '">' . lang('btn_deactivatefed') . '</button>';
                $data['result']['management'][] = array('data' => array('data' => '' . $b . '', 'colspan' => 2));
                $b = '<br /><button type="button" name="fedstatus" value="enablefed" class="savebutton staricon small" style="display:none">' . lang('btn_activatefed') . '</button>';
                $data['result']['management'][] = array('data' => array('data' => '' . $b . '', 'colspan' => 2));
                $b = '<br /><button type="button" name="fedstatus"  value="delfed" class="resetbutton reseticon alert small" style="display: none" title="' . lang('btn_applytodelfed') . ': ' . html_escape($federation->getName()) . '">' . lang('btn_applytodelfed') . '</button>';
                $data['result']['management'][] = array('data' => array('data' => '' . $b . '', 'colspan' => 2));
            } else {
                $b = '<button type="button" name="fedstatus" value="disablefed" class="resetbutton reseticon alert small" style="display: none" title="' . lang('btn_deactivatefed') . ': ' . html_escape($federation->getName()) . '">' . lang('btn_deactivatefed') . '</button>';
                $b .= '<br /><button type="button" name="fedstatus" value="enablefed" class="savebutton staricon small">' . lang('btn_activatefed') . '</button>';
                $data['result']['management'][] = array('data' => array('data' => '' . $b . '', 'colspan' => 2));
                $b = '<button type="button" name="fedstatus"  value="delfed" class="resetbutton reseticon alert small" title="' . lang('btn_applytodelfed') . ': ' . html_escape($federation->getName()) . '">' . lang('btn_applytodelfed') . '</button>';
                $data['result']['management'][] = array('data' => array('data' => '' . $b . '', 'colspan' => 2));
            }
        } else {
            $data['result']['management'][] = array('data' => array('data' => '<div data-alert class="alert-box warning"><small>' . lang('rr_noperm_accessmngt') . '</small></div>', 'colspan' => 2));
        }


        $metadataTab = $this->showMetadataTab($federation, $access['hasWriteAccess']);
        if (!isset($data['result']['metadata'])) {
            $data['result']['metadata'] = array();
        }
        $data['result']['metadata'] = array_merge($data['result']['metadata'], $metadataTab);


        if (!empty($data['federation_is_active'])) {
            $data['result']['membership'][] = array('data' => array('data' => '<div id="membership2" data-jagger-link="' . base_url() . 'federations/manage/showmembers/' . $federation->getId() . '"><div data-alert class="alert-box info center">Loading....<a href="#" class="close">&times;</a>
</div></div>', 'colspan' => 2));
        }

        $data['result']['fvalidators'] = array_merge($data['result']['fvalidators'], $this->genValidators($federation, $canEdit));


        $this->load->view('page', $data);
    }

    private function inviteSubmitValidate()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('provider', lang('rr_provider'), 'required|numeric|xss_clean');
        $this->form_validation->set_rules('message', lang('rr_message'), 'required|xss_clean');
        return $this->form_validation->run();
    }

    private function removeSubmitValidate()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('provider', lang('rr_provider'), 'required|numeric|xss_clean');
        $this->form_validation->set_rules('message', lang('rr_message'), 'required|xss_clean');
        return $this->form_validation->run();
    }

    public function inviteprovider($fed_name)
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $myLang = MY_Controller::getLang();
        $this->load->library(array('zacl', 'show_element'));
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($fed_name)));
        if (empty($federation)) {
            show_error('Federation not found', 404);
        }

        $hasWriteAccess = $this->zacl->check_acl('f_' . $federation->getId(), 'write', 'federation', '');
        if (!$hasWriteAccess) {
            show_error('no access', 403);
        }
        $data['subtitle'] = lang('rr_federation') . ': ' . $federation->getName() . ' ' . anchor(base_url() . 'federations/manage/show/' . base64url_encode($federation->getName()), '<i class="fi-arrow-right"></i>');
        log_message('debug', '_________Before validation');
        if ($this->inviteSubmitValidate() === TRUE) {
            log_message('debug', 'Invitation form is valid');
            $provider_id = $this->input->post('provider');
            $message = $this->input->post('message');
            /**
             * @var $invitedProvider models\Provider
             */
            $invitedProvider = $this->tmp_providers->getOneById($provider_id);
            if (empty($invitedProvider)) {
                $data['error'] = lang('rerror_providernotexist');
            } else {
                $inv_member_federations = $invitedProvider->getFederations();
                if ($inv_member_federations->contains($federation)) {
                    $data['error'] = sprintf(lang('rr_provideralready_member_of'), $federation->getName());
                } else {
                    $this->load->library('approval');
                    /* create request in queue with flush */
                    $add_to_queue = $this->approval->invitationProviderToQueue($federation, $invitedProvider, 'Join');
                    if ($add_to_queue) {
                        $mailSubject = "Invitation: join federation: " . $federation->getName();
                        $mailBody = 'Hi,' . PHP_EOL . 'Just few moments ago Administator of federation "' . $federation->getName() . '"' . PHP_EOL .
                            'invited Provider: "' . $invitedProvider->getName() . ' (' . $invitedProvider->getEntityId() . ')"' . PHP_EOL .
                            'to join his federation.' . PHP_EOL .
                            'To accept or reject this request please go to Resource Registry' . PHP_EOL .
                            base_url('reports/awaiting') . PHP_EOL . PHP_EOL . PHP_EOL .
                            '======= additional message attached by requestor ===========' . PHP_EOL .
                            html_escape($message) . PHP_EOL .
                            '=============================================================' . PHP_EOL;
                        $this->email_sender->addToMailQueue(array('grequeststoproviders', 'requeststoproviders'), $invitedProvider, $mailSubject, $mailBody, array(), true);
                    }
                }
            }
        }
        $current_members = $federation->getMembers();
        $local_providers = $this->tmp_providers->getLocalProviders();
        $list = array('IDP' => array(), 'SP' => array(), 'BOTH' => array());
        foreach ($local_providers as $l) {
            if (!$current_members->contains($l)) {
                $ltype = strtolower($l->getType());
                if (strcmp($ltype, 'both') == 0) {
                    $ltype = 'idp';
                }
                $list[$l->getType()][$l->getId()] = $l->getNameToWebInLang($myLang, $ltype) . ' (' . $l->getEntityId() . ')';
            }
        }
        $list = array_filter($list);
        if (count($list) > 0) {
            $data['providers'] = $list;
        } else {
            $data['error_message'] = lang('rr_fednoprovidersavail');
        }
        $data['fedname'] = $federation->getName();

        $data['titlepage'] = lang('rr_federation') . ': <a href="' . base_url() . 'federations/manage/show/' . base64url_encode($federation->getName()) . '">' . $federation->getName() . '</a>';

        $data['fedurl'] = base_url('federations/manage/show/' . base64url_encode($federation->getName()) . '');
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . base64url_encode($federation->getName()) . ''), 'name' => html_escape($federation->getName())),
            array('url' => base_url('#'), 'name' => 'Invitation', 'type' => 'current'),
        );
        $data['content_view'] = 'federation/invite_provider_view';
        $this->load->view('page', $data);
    }

    public function removeprovider($encodedFedName)
    {
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => base64url_decode($encodedFedName)));
        if (empty($federation)) {
            show_error('Federation not found', 404);
        }
        $myLang = MY_Controller::getLang();
        $hasWriteAccess = $this->zacl->check_acl('f_' . $federation->getId(), 'write', 'federation', '');
        if (!$hasWriteAccess) {
            show_error('no access', 403);
        }
        if ($this->removeSubmitValidate() === TRUE) {
            log_message('debug', 'Remove provider from fed form is valid');
            $provider_id = $this->input->post('provider');
            $message = $this->input->post('message');
            /**
             * @var $invitedProvider models\Provider
             */
            $invitedProvider = $this->tmp_providers->getOneById($provider_id);
            if (empty($invitedProvider)) {
                $data['error_message'] = lang('rerror_providernotexist');
            } else {
                if ($this->config->item('rr_rm_member_from_fed') === TRUE) {
                    $p_tmp = new models\AttributeReleasePolicies;
                    $arp_fed = $p_tmp->getFedPolicyAttributesByFed($invitedProvider, $federation);
                    $rm_arp_msg = '';
                    if (!empty($arp_fed) && is_array($arp_fed) && count($arp_fed) > 0) {
                        foreach ($arp_fed as $r) {
                            $this->em->remove($r);
                        }
                        $rm_arp_msg = "Also existing attribute release policy for this federation has been removed<br/>" .
                            "It means when in the future you join this federation you will need to set attribute release policy for it again<br />";
                    }
                    $doFilter = array('' . $federation->getId() . '');
                    $m2 = $invitedProvider->getMembership()->filter(
                        function (models\FederationMembers $entry) use ($doFilter) {
                            return (in_array($entry->getFederation()->getId(), $doFilter));
                        }
                    );
                    foreach ($m2 as $v2) {
                        log_message('debug', 'GKS OOOO');
                        if ($invitedProvider->getLocal()) {
                            $v2->setJoinState('2');
                            $this->em->persist($v2);
                        } else {
                            $invitedProvider->getMembership()->removeElement($v2);
                            $this->em->remove($v2);
                        }
                    }
                    $entype = strtolower($invitedProvider->getType());
                    if (strcmp($entype, 'both') == 0) {
                        $entype = 'idp';
                    }
                    $provider_name = $invitedProvider->getNameToWebInLang($myLang, $entype);;
                    $this->em->persist($invitedProvider);
                    $spec_arps_to_remove = $p_tmp->getSpecCustomArpsToRemove($invitedProvider);
                    if (!empty($spec_arps_to_remove) && is_array($spec_arps_to_remove) && count($spec_arps_to_remove) > 0) {
                        foreach ($spec_arps_to_remove as $rp) {
                            $this->em->remove($rp);
                        }
                    }
                    $data['success_message'] = "You just removed provider <b>" . $provider_name . "</b> from federation: <b>" . $federation->getName() . "</b><br />" . $rm_arp_msg;

                    $mail_sbj = "\"" . $provider_name . "\" has been removed from federation \"" . $federation->getName() . "\"";
                    $mail_body = 'Hi,' . PHP_EOL . 'Just few moments ago Administator of federation "' . $federation->getName() . '"' . PHP_EOL .
                        'removed ' . $provider_name . ' (' . $invitedProvider->getEntityId() . ') from federation' . PHP_EOL;
                    if (!empty($message)) {
                        $mail_body .= PHP_EOL . PHP_EOL . '======= additional message attached by administrator ===========' . PHP_EOL . $message . PHP_EOL .
                            '================================================================' . PHP_EOL;
                    }

                    $this->email_sender->addToMailQueue(array('gfedmemberschanged', 'fedmemberschanged'), $federation, $mail_sbj, $mail_body, array(), $sync = false);
                    $this->em->flush();

                } else {
                    log_message('error', 'rr_rm_member_from_fed is not set in config');
                    show_error('missed some config setting, Please contact with admin.', 500);
                }
            }
        }
        $data['titlepage'] = lang('rr_federation') . ': ' . ' ' . anchor(base_url() . 'federations/manage/show/' . base64url_encode($federation->getName()), $federation->getName());
        $data['subtitlepage'] = lang('rmprovfromfed');
        $current_members = $federation->getMembers();
        if ($current_members->count() > 0) {
            $list = array('IDP' => array(), 'SP' => array(), 'BOTH' => array());
            foreach ($current_members as $l) {
                $ltype = strtolower($l->getType());
                if (strcmp($ltype, 'both') == 0) {
                    $ltype = 'idp';
                }

                $list[$l->getType()][$l->getId()] = $l->getNameToWebInLang($myLang, $ltype) . ' (' . $l->getEntityId() . ')';
            }
            $list = array_filter($list);
            $data['providers'] = $list;
            $data['fedname'] = $federation->getName();
        } else {
            $data['error_message'] = lang('error_notfoundmemberstoberm');
        }

        $data['content_view'] = 'federation/remove_provider_view';
        $data['encodedfedname'] = base64url_encode($federation->getName());
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . base64url_encode($federation->getName()) . ''), 'name' => '' . $federation->getName() . ''),
            array('url' => '#', 'type' => 'current', 'name' => lang('rmprovfromfed'))

        );


        $this->load->view('page', $data);
    }

    /**
     * @param \models\Federation $federation
     * @return array
     */
    private function genEntitiesDescriptorId(\models\Federation $federation)
    {
        $entitiesDescriptorId = $federation->getDescriptorId();
        if (!empty($entitiesDescriptorId)) {
            return array('EntitiesDescriptor ID', html_escape($entitiesDescriptorId));
        } else {
            $validfor = new \DateTime("now", new \DateTimezone('UTC'));
            $idprefix = '';
            $prefid = $this->config->item('fedmetadataidprefix');
            if (!empty($prefid)) {
                $idprefix = $prefid;
            }
            $idsuffix = $validfor->format('YmdHis');
            $entitiesDescriptorId = $idprefix . $idsuffix;
            return array(lang('rr_fed_descid'), html_escape($entitiesDescriptorId) . ' <span class="label">' . lang('rr_entdesciddyn') . '</span>');
        }

    }

    private function genValidators(\models\Federation $federation, $hasWriteAccess = false)
    {
        /**
         * @var $fvalidators models\FederationValidator[]
         */
        $fvalidators = $federation->getValidators();


        if ($fvalidators->count() > 0) {

            if ($hasWriteAccess) {
                $fvdata = '<dl class="accordion" data-accordion>';
                foreach ($fvalidators as $f) {
                    $fvdata .= ' <dd class="accordion-navigation">'.
                        '<a href="#fvdata' . $f->getId() . '" class="accordion-icon">' . $f->getName() . '</a>'.
                        '<div id="fvdata' . $f->getId() . '" class="content">';
                    $editbtn = '<a href="' . base_url() . 'manage/fvalidatoredit/vedit/' . $federation->getId() . '/' . $f->getId() . '" class="editbutton editicon right button small">' . lang('rr_edit') . '</a>';

                    $method = $f->getMethod();
                    $fedstatusLabels = '';
                    if ($f->getEnabled()) {
                        $fedstatusLabels .= ' ' . makeLabel('active', lang('lbl_enabled'), lang('lbl_enabled'));
                    } else {
                        $fedstatusLabels .= ' ' . makeLabel('disabled', lang('lbl_disabled'), lang('lbl_disabled'));
                    }
                    if ($f->getMandatory()) {
                        $fedstatusLabels .= ' ' . makeLabel('active', lang('lbl_mandatory'), lang('lbl_mandatory'));
                    } else {
                        $fedstatusLabels .= ' ' . makeLabel('disabled', lang('lbl_optional'), lang('lbl_optional'));
                    }
                    if ($f->isEnabledForRegistration()) {
                        $fedstatusLabels .= ' ' . makeLabel('active', lang('lbl_fvalidonreg'), lang('lbl_fvalidonreg'));
                    }
                    $optargs1 = $f->getOptargs();
                    $optargsStr = array();
                    foreach ($optargs1 as $k => $v) {
                        if ($v === null) {
                            $optargsStr[] = $k;
                        } else {
                            $optargsStr[] = $k . '=' . $v;
                        }
                    }
                    $retvalues = $f->getReturnCodeValues();
                    $retvaluesToHtml = '';
                    foreach ($retvalues as $k => $v) {
                        $retvaluesToHtml .= '<div>' . $k . ': ';
                        if (!empty($v) && is_array($v)) {
                            foreach ($v as $v1) {
                                $retvaluesToHtml .= '"' . $v1 . '"; ';
                            }
                        }
                    }

                    $tbl = array(
                        array('data' => array('data' => ' ' . $editbtn, 'class' => '', 'colspan' => 2)),
                        array('data' => lang('rr_status'), 'value' => $fedstatusLabels),
                        array('data' => lang('Description'), 'value' => html_escape($f->getDescription())),
                        array('data' => lang('fvalid_doctype'), 'value' => $f->getDocutmentType()),
                        array('data' => lang('fvalid_url'), 'value' => $f->getUrl()),
                        array('data' => lang('rr_httpmethod'), 'value' => $method),
                        array('data' => lang('fvalid_entparam'), 'value' => $f->getEntityParam()),
                        array('data' => lang('fvalid_optargs'), 'value' => implode('<br />', $optargsStr)),
                    );


                    if (strcmp($method, 'GET') == 0) {
                        $tbl[] = array('data' => lang('rr_argsep'), 'value' => $f->getSeparator());
                    }
                    $tbl[] = array('data' => lang('fvalid_retelements'), 'value' => implode('<br />', $f->getReturnCodeElement()));
                    $tbl[] = array('data' => lang('fvalid_retelements'), 'value' => $retvaluesToHtml);
                    $tbl[] = array('data' => lang('fvalid_msgelements'), 'value' => implode('<br />', $f->getMessageCodeElements()));

                    $fvdata .= $this->table->generate($tbl);
                    $fvdata .= '</div>';
                    $fvdata .= '</dd>';
                    $this->table->clear();
                }
                $fvdata .= '</dl>';
                return array(array('data' => array('data' => $fvdata, 'colspan' => 2, 'class' => '')));
            } else {
                return array(array('data' => array('data' => '<div class="alert">' . lang('rr_noperm') . '</div>', 'colspan' => 2)));
            }
        }
        return array();
    }

}
