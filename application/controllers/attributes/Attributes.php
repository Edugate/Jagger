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
class Attributes extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
    }

    private function addSubmitValidate() {
        $this->form_validation->set_rules('attrname', lang('attrname'), 'trim|required|min_length[1]|max_length[128]|xss_clean|no_white_spaces|attribute_unique[name]');
        $this->form_validation->set_rules('attroidname', lang('attrsaml2'), 'trim|required|min_length[1]|max_length[128]|xss_clean|no_white_spaces|attribute_unique[oid]');

        $this->form_validation->set_rules('attrurnname', lang('attrsaml1'), 'trim|min_length[3]|max_length[128]|xss_clean|no_white_spaces|attribute_unique[urn]');
        $this->form_validation->set_rules('attrfullname', lang('attrfullname'), 'trim|required|min_length[3]|max_length[128]|xss_clean|attribute_unique[fullname]');
        $this->form_validation->set_rules('description', lang('rr_description'), 'trim|required|min_length[3]|max_length[128]|xss_clean');

        return $this->form_validation->run();
    }

    private function deleteSubmitValidate(){
        $this->form_validation->set_rules('attrname', lang('attrname'), 'trim|required|min_length[1]|max_length[128]|xss_clean|no_white_spaces');
        $this->form_validation->set_rules('attrid', 'attrid', 'trim|required|numeric');
        return $this->form_validation->run();
    }

    public function remove() {
        if (!$this->jauth->isLoggedIn() || !$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(401)->set_output('Access denied');
        }

        if($this->deleteSubmitValidate() !== true){
            return $this->output->set_status_header(401)->set_output(validation_errors());
        }

        /**
         * @var models\Attribute $attribute
         */
        try {
            $attribute = $this->em->getRepository('models\Attribute')->findOneBy(array(
                'id' => $this->input->post('attrid'),
                'name' => $this->input->post('attrname')
            ));
        }catch (Exception $e){
            log_message('error',__METHOD__.' '.$e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
        if(null === $attribute){
            return $this->output->set_status_header(404)->set_output('Could not found attribute');
        }

        /**
         * @var models\AttributeRequirement[] $attributeReq
         * @var models\AttributeReleasePolicy[] $attributePol
         */

        $attributeReq = $this->em->getRepository('models\AttributeRequirement')->findBy(array('attribute_id'=>$attribute));
        $attributePol = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('attribute'=>$attribute));
        foreach ($attributeReq as $item){
            $this->em->remove($item);
        }
        foreach ($attributePol as $item){
            $this->em->remove($item);
        }
        $this->em->remove($attribute);

        $body = 'Dear user,'.PHP_EOL;
        $body .=''.$this->jauth->getLoggedinUsername().' just removed attribute: '.$attribute->getName().' from the system'.PHP_EOL;
        $body .= 'SRC IP: '.$this->input->ip_address().PHP_EOL;
        $this->emailsender->addToMailQueue(array(), null, 'Attribute removed from the system', $body, array(), false);
        try {
            $this->em->flush();
        }catch (Exception $e){
            log_message('error',__METHOD__.' '.$e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }


        $result = array(
            'status'  => 200,
            'message' => 'success'
        );
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function add() {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->title = lang('rr_newattr_title');
        $isAdmin = $this->jauth->isAdministrator();
        MY_Controller::$menuactive = 'admins';
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
        $this->load->view(MY_Controller::$page, $data);
    }

    public function show() {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        MY_Controller::$menuactive = 'admins';
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
            $y = '';
            if ($this->jauth->isAdministrator()) {
                $y = '<a class="delattribute alert"  data-jagger-attrname="' . $a->getName() . '" data-jagger-attrid="' . $a->getId() . '"><i class="fa fa-trash alert"></i></a>';
            }
            $dataRows[] = array(showBubbleHelp($a->getDescription()) . ' ' . $a->getName() . $notice, $a->getFullname(), $a->getOid(), $a->getUrn(), '<a class="attrinfo" data-jagger-attrid="' . $a->getId() . '"><i class="fa fa-expand"></i></a>' . ' ' . $y . '');
        }
        $data['isadmin'] = $this->jauth->isAdministrator();
        $data['breadcrumbs'] = array(
            array(
                'url'  => '#',
                'name' => lang('attrsdeflist'),
                'type' => 'current')
        );
        $data['attributes'] = $dataRows;
        $data['content_view'] = 'attribute_list_view';
        $this->load->view(MY_Controller::$page, $data);
    }


    /**
     * @param null $attrid
     * @return CI_Output
     */
    public function byid($attrid = null) {
        if (!$this->jauth->isLoggedIn() || !$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(401)->set_output('Access denied');
        }
        if (!ctype_digit($attrid)) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }

        $attr = new models\Attributes();
        try {
            $result = $attr->getAttributeUsageById($attrid);
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ': ' . $e);

            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
        if (null === $result) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }

        $result['status'] = 'success';

        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

}

