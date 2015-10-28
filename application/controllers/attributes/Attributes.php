<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Attributes extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->session->set_userdata(array('currentMenu' => 'general'));
        $this->current_idp = $this->session->userdata('current_idp');
        $this->current_idp_name = $this->session->userdata('current_idp_name');
        $this->current_sp = $this->session->userdata('current_sp');
        $this->current_sp_name = $this->session->userdata('current_sp_name');
        $this->load->library('form_validation');
        MY_Controller::$menuactive = 'admins';
    }

    private function addSubmitValidate() {
        $this->form_validation->set_rules('attrname', lang('attrname'), 'trim|required|min_length[1]|max_length[128]|xss_clean|no_white_spaces|attribute_unique[name]');
        $this->form_validation->set_rules('attroidname', lang('attrsaml2'), 'trim|required|min_length[1]|max_length[128]|xss_clean|no_white_spaces|attribute_unique[oid]');
        $this->form_validation->set_rules('attrurnname', lang('attrsaml1'), 'trim|required|min_length[3]|max_length[128]|xss_clean|no_white_spaces|attribute_unique[urn]');
        $this->form_validation->set_rules('attrfullname', lang('attrfullname'), 'trim|required|min_length[3]|max_length[128]|xss_clean|attribute_unique[fullname]');
        $this->form_validation->set_rules('description', lang('rr_description'), 'trim|required|min_length[3]|max_length[128]|xss_clean');

        return $this->form_validation->run();
    }

    public function add() {
        $this->title = lang('rr_newattr_title');
        $isAdmin = $this->jauth->isAdministrator();
        $data['titlepage'] = lang('rr_newattr_title');
        $data['breadcrumbs'] = array(
            array('url' => base_url('attributes/attributes/show'), 'name' => lang('attrsdeflist')),
            array('url' => '#', 'name' => lang('rr_newattr_title'), 'type' => 'current'),

        );
        if (!$isAdmin) {
            show_error('Access Denied', 401);
        }

        $this->load->helper('form');
        if ($this->addSubmitValidate()) {
            $attrname = $this->input->post('attrname');
            $attroid = $this->input->post('attroidname');
            $attrurn = $this->input->post('attrurnname');
            $attrfullname = $this->input->post('attrfullname');
            $description = $this->input->post('description');
            $attr = new models\Attribute;
            $attr->setName($attrname);
            $attr->setFullname($attrfullname);
            $attr->setOid($attroid);
            $attr->setUrn($attrurn);
            $attr->setDescription($description);
            $attr->setShowInmetadata(true);
            $this->em->persist($attr);
            $data['content_view'] = 'attradd_success_view';
            $data['success'] = lang('attraddsuccess');
            try {
                $this->em->flush();
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
                show_error('Couldnt store new attr in db', 500);
            }
        } else {
            $data['content_view'] = 'attribute_add_view';

        }
        $this->load->view('page', $data);
    }

    public function show() {
        $this->title = lang('attrsdeflist');
        /**
         * @var $attributes models\Attribute[]
         */
        $tmpAttributes = new models\Attributes();
        $attributes = $tmpAttributes->getAttributes();
        $dataRows = array();
        $excluded = '<span class="lbl lbl-alert" title="' . lang('rr_attronlyinarpdet') . '">' . lang('rr_attronlyinarp') . '</span>';

        $data['titlepage'] = lang('attrsdeflist');

        foreach ($attributes as $a) {
            $notice = '';
            $i = $a->showInMetadata();
            if ($i === false) {
                $notice = '<br />' . $excluded;
            }
            $dataRows[] = array(showBubbleHelp($a->getDescription()) . ' ' . $a->getName() . $notice, $a->getFullname(), $a->getOid(), $a->getUrn());
        }
        $data['isadmin'] = $this->jauth->isAdministrator();
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('attrsdeflist'), 'type' => 'current'),

        );
        $data['attributes'] = $dataRows;
        $data['content_view'] = 'attribute_list_view';
        $this->load->view('page', $data);
    }

}
