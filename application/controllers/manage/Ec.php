<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Ec extends MY_Controller
{

    function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');

    }

    public function show($entcatId = null) {
        if ($entcatId !== null && !ctype_digit($entcatId)) {
            show_error('Argument passed to page  not allowed', 403);
        }

        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->title = lang('title_entcats');
        $hasWriteAccess = $this->zacl->check_acl('coc', 'write', 'default', '');
        /**
         * @var models\Coc[] $entCategories
         */
        $entCategories = $this->em->getRepository("models\Coc")->findBy(array('type' => 'entcat'));
        $preDefAvailFor = array(
            'idp' => 'idp',
            'sp' => 'sp',
            'both' => 'idp, sp'
        );
        $data['rows'] = array();
        if (count($entCategories) > 0) {
            foreach ($entCategories as $entCat) {
                $countProviders = $entCat->getProvidersCount();
                $isEnabled = $entCat->getAvailable();
                $availFor = $entCat->getAvailFor();
                $availForStr = 'idp, sp';
                if (in_array($availFor, $preDefAvailFor)) {
                    $availForStr = $preDefAvailFor['' . $availFor . ''];
                }
                $linetxt = '';
                if ($hasWriteAccess) {
                    $linetxt = '<a href="' . base_url() . 'manage/ec/edit/' . $entCat->getId() . '" ><i class="fi-pencil"></i></a>';
                    if (!$isEnabled) {
                        $linetxt .= '&nbsp;&nbsp;<a href="' . base_url() . 'manage/ec/remove/' . $entCat->getId() . '" class="withconfirm" data-jagger-fieldname="' . $entCat->getName() . '" data-jagger-ec="' . $entCat->getId() . '" data-jagger-counter="' . $countProviders . '"><i class="fi-trash"></i></a>';
                    }
                }

                $lbl = '<span class="lbl lbl-disabled">' . lang('rr_disabled') . '</span>';
                if ($isEnabled) {
                    $lbl = '<span class="lbl lbl-active">' . lang('rr_enabled') . '</span>';
                }
                $lbl .= '<span class="label secondary ecmembers" data-jagger-jsource="' . base_url('manage/regpolicy/getmembers/' . $entCat->getId() . '') . '">' . $countProviders . '</span> ';
                $subtype = $entCat->getSubtype();
                if (empty($subtype)) {
                    $subtype = '<span class="label alert">' . lang('lbl_missing') . '</span>';
                }
                $data['rows'][] = array($entCat->getName(), $subtype, anchor($entCat->getUrl(), $entCat->getUrl(), array('target' => '_blank', 'class' => 'new_window')), html_escape($entCat->getDescription()), $lbl, $availForStr, $linetxt);
            }
        } else {
            $data['error_message'] = lang('rr_noentcatsregistered');
        }
        $data['showaddbutton'] = false;
        if ($hasWriteAccess) {
            $data['showaddbutton'] = true;
        }

        $data['titlepage'] = lang('ent_list_title');

        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('entcats_menulink'), 'type' => 'current'),
        );
        $data['content_view'] = 'manage/coc_show_view';
        $this->load->view('page', $data);
    }

    function getMembers($ecid) {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $this->load->library('zacl');
        $myLang = MY_Controller::getLang();
        /**
         * @var $entCategory models\Coc
         */
        $entCategory = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $ecid));
        if (empty($entCategory)) {
            return $this->output->set_status_header(404)->set_output('no members found');
        }
        /**
         * @var $ecMembers models\Provider[]
         */
        $ecMembers = $entCategory->getProviders();
        $result = array();
        foreach ($ecMembers as $member) {
            $result[] = array(
                'entityid' => $member->getEntityId(),
                'provid' => $member->getId(),
                'name' => $member->getNameToWebInLang($myLang),
            );
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    private function _add_submit_validate() {
        $this->form_validation->set_rules('name', lang('entcat_displayname'), 'required|trim|cocname_unique');
        $this->form_validation->set_rules('attrname', lang('rr_attr_name'), 'trim|required');
        $attrname = $this->input->post('attrname');
        $this->form_validation->set_rules('url', lang('entcat_value'), 'required|trim|valid_url|ecUrlInsert[' . $attrname . ']');
        $this->form_validation->set_rules('description', lang('entcat_description'), 'trim');
        $this->form_validation->set_rules('cenabled', lang('entcat_enabled'), 'trim');
        $this->form_validation->set_rules('availfor', lang('rravailforenttypelng'), 'trim|required');
        return $this->form_validation->run();
    }

    private function _edit_submit_validate($entcatId) {
        $attrname = $this->input->post('attrname');
        $this->form_validation->set_rules('name', lang('entcat_displayname'), 'trim|required|cocname_unique_update[' . $entcatId . ']');
        $this->form_validation->set_rules('attrname', lang('rr_attr_name'), 'trim|required');
        $ecUrlUpdateParams = serialize(array('id' => $entcatId, 'subtype' => $attrname));
        $this->form_validation->set_rules('url', lang('entcat_value'), 'trim|required|valid_url|ecUrlUpdate[' . $ecUrlUpdateParams . ']');
        $this->form_validation->set_rules('description', lang('entcat_description'), 'trim');
        $this->form_validation->set_rules('cenabled', lang('entcat_enabled'), 'trim');
        $this->form_validation->set_rules('availfor', lang('rravailforenttypelng'), 'trim|required');
        return $this->form_validation->run();
    }

    public function add() {

        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->title = lang('title_addentcat');
        $data['titlepage'] = lang('title_addentcat');
        $hasWriteAccess = $this->zacl->check_acl('coc', 'write', 'default', '');
        if (!$hasWriteAccess) {
            show_error('No access', 401);
        }

        if ($this->_add_submit_validate() === true) {

            $cenabled = $this->input->post('cenabled');

            $ncoc = new models\Coc;
            $ncoc->setName($this->input->post('name'));
            $ncoc->setUrl($this->input->post('url'));
            $ncoc->setType('entcat');
            $availfor = $this->input->post('availfor');
            if (in_array($availfor, array('idp', 'sp', 'both'))) {
                $ncoc->setAvailFor($availfor);
            }
            $allowedattrs = attrsEntCategoryList();
            $inputAttrname = $this->input->post('attrname');
            if (in_array($inputAttrname, $allowedattrs)) {
                $ncoc->setSubtype($inputAttrname);
            }

            $ncoc->setDescription(trim($this->input->post('description')));

            if ($cenabled === 'accept') {
                $ncoc->setAvailable(TRUE);
            }
            $this->em->persist($ncoc);
            $this->em->flush();

            $data['success_message'] = lang('rr_entcatadded');
        } else {
            $this->load->library('formelement');
            $form = form_open() .
                $this->formelement->generateAddCoc() .
                '<div class="buttons small-12 medium-10 large-10 columns end text-right">' .
                '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">' . lang('rr_reset') . '</button> ' .
                '<button type="submit" name="modify" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div>' .
                form_close();
            $data['form'] = $form;
        }
        $data['breadcrumbs'] = array(
            array('url' => base_url('manage/ec/show'), 'name' => lang('title_entcats')),
            array('url' => '#', 'name' => lang('title_addentcat'), 'type' => 'current'),
        );
        $data['content_view'] = 'manage/coc_add_view';
        $this->load->view('page', $data);
    }

    public function edit($entcatId) {
        if (!ctype_digit($entcatId)) {
            show_error('Not found', 404);
        }
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
        $this->title = lang('title_entcatedit');


        /**
         * @var models\Coc $coc
         */
        $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $entcatId, 'type' => 'entcat'));
        if ($coc === null) {
            show_error('Not found', 404);
        }
        $hasWriteAccess = $this->zacl->check_acl('coc', 'write', 'default', '');
        if (!$hasWriteAccess) {
            show_error('No access', 401);
        }
        $data = array(
            'titlepage' => lang('title_entcat') . ': ' . html_escape($coc->getName()),
            'subtitlepage' => lang('title_entcatedit')
        );

        if ($this->_edit_submit_validate($entcatId) === true) {
            $enable = trim($this->input->post('cenabled'));
            if ($enable === 'accept') {
                $coc->setAvailable(true);
            } else {
                $coc->setAvailable(false);
            }
            $coc->setName($this->input->post('name'));
            $coc->setUrl($this->input->post('url'));
            $availFor = $this->input->post('availfor');
            if (in_array($availFor, array('idp', 'sp', 'both'), true)) {
                $coc->setAvailFor($availFor);
            }
            $allowedattrs = attrsEntCategoryList();
            $inputAttrname = $this->input->post('attrname');
            if (in_array($inputAttrname, $allowedattrs)) {
                $coc->setSubtype($inputAttrname);
            }
            $coc->setDescription($this->input->post('description'));
            $this->em->persist($coc);
            $this->em->flush();
            $data['success_message'] = lang('updated');
        }
        $data['coc_name'] = $coc->getName();
        $this->load->library('formelement');
        $data['form'] = form_open() .
            $this->formelement->generateEditCoc($coc) .
            '<div class="buttons large-10 medium-10 small-12 text-right columns end">' .
            '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">' . lang('rr_reset') . '</button> ' .
            '<button type="submit" name="modify" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div>' . form_close();
        $data['breadcrumbs'] = array(
            array('url' => base_url('manage/ec/show'), 'name' => lang('title_entcats')),
            array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),
        );
        $data['content_view'] = 'manage/coc_edit_view';
        $this->load->view('page', $data);
    }

    public function remove($entcatId = null) {
        if (!ctype_digit($entcatId)) {
            return $this->output->set_status_header(404)->set_output('incorrect id or id not provided');
        }
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('access denied');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('access denied');
        }

        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl('coc', 'write', 'default', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('access denied');
        }
        /**
         * @var models\Coc $entcat
         */
        $entcat = $this->em->getRepository("models\Coc")->findOneBy(array('id' => '' . $entcatId . '', 'type' => 'entcat', 'is_enabled' => false));
        if ($entcat === null) {
            return $this->output->set_status_header(403)->set_output('Registration policy doesnt exist or is not disabled');
        }
        /**
         * @var models\AttributeReleasePolicy[] $arps
         */
        $arps = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('type' => 'entcat', 'requester' => $entcat->getId()));
        $this->em->remove($entcat);
        foreach ($arps as $arp) {
            $this->em->remove($arp);
        }
        try {
            $this->em->flush();
            return $this->output->set_status_header(200)->set_output('OK');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
    }
}
