<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Regpolicy Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * @property Formelement $formelement
 */
class Regpolicy extends MY_Controller
{

    public function __construct() {
        parent::__construct();

    }

    public function show($id = null) {
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->library('zacl');
        $this->title = lang('title_regpols');
        if (isset($id)) {
            show_error('Argument passed to page  not allowed', 403);

            return;

        }
        $has_write_access = $this->zacl->check_acl('regpol', 'write', 'default', '');
        /**
         * @var $obj_list models\Coc[]
         */
        $obj_list = $this->em->getRepository("models\Coc")->findBy(array('type' => 'regpol'));
        $data['rows'] = array();
        if (is_array($obj_list) && count($obj_list) > 0) {
            foreach ($obj_list as $c) {
                $countProviders = $c->getProvidersCount();
                $isEnabled = $c->getAvailable();
                if ($has_write_access) {
                    $l = '<a href="' . base_url() . 'manage/regpolicy/edit/' . $c->getId() . '" ><i class="fa fa-edit"></i></a>';
                    if (!$isEnabled) {
                        $l .= '&nbsp;&nbsp;<a href="' . base_url() . 'manage/regpolicy/remove/' . $c->getId() . '" class="withconfirm" data-jagger-regpolicy="' . $c->getId() . '" data-jagger-fieldname="' . $c->getName() . '"  data-jagger-counter="' . $countProviders . '"><i class="fa fa-trash"></i></a>';
                    }
                } else {
                    $l = '';
                }

                if ($isEnabled) {
                    $lbl = '<span class="lbl lbl-active">' . lang('rr_enabled') . '</span>';
                } else {
                    $lbl = '<span class="lbl lbl-disabled">' . lang('rr_disabled') . '</span>';
                }
                $lbl .= '<span class="label secondary policymembers" data-jagger-jsource="' . base_url('manage/regpolicy/getmembers/' . $c->getId() . '') . '">' . $countProviders . '</span> ';
                /**
                 * @todo add extracting row to show providers connected to policy
                 */
                $data['rows'][] = array($c->getName(), $c->getLang(), anchor($c->getUrl(), $c->getUrl(), array('target' => '_blank', 'class' => 'new_window')), $c->getDescription(), $lbl, $l);

            }
        } else {
            $data['error_message'] = lang('rr_noregpolsregistered');
        }
        $data['showaddbutton'] = false;
        if ($has_write_access) {
            $data['showaddbutton'] = true;
        }

        $data['titlepage'] = lang('title_regpols');

        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('title_regpols'), 'type' => 'current'),
        );


        $data['content_view'] = 'manage/regpol_show_view';
        $this->load->view(MY_Controller::$page, $data);

    }

    private function _add_submit_validate() {
        $this->form_validation->set_rules('name', lang('regpol_shortname'), 'required|trim|cocname_unique');
        $this->form_validation->set_rules('regpollang', lang('regpol_language'), 'required|trim|match_language');
        $this->form_validation->set_rules('url', lang('regpol_url'), 'required|trim|valid_url');
        $this->form_validation->set_rules('description', lang('regpol_description'), 'xss_clean');
        $this->form_validation->set_rules('cenabled', lang('regpol_enabled'), 'xss_clean');

        return $this->form_validation->run();
    }

    private function _edit_submit_validate($id) {
        $this->form_validation->set_rules('name', lang('regpol_shortname'), 'required|trim|cocname_unique_update[' . $id . ']');
        $this->form_validation->set_rules('url', lang('regpol_url'), 'required|trim|valid_url');
        $this->form_validation->set_rules('regpollang', lang('regpol_language'), 'required|trim|match_language');
        $this->form_validation->set_rules('description', lang('regpol_description'), 'xss_clean');
        $this->form_validation->set_rules('cenabled', lang('regpol_enabled'), 'xss_clean');

        return $this->form_validation->run();
    }

    public function add() {
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->library('zacl');
        $this->title = lang('title_addregpol');
        $data['titlepage'] = lang('title_addregpol');
        $has_write_access = $this->zacl->check_acl('regpol', 'write', 'default', '');
        if (!$has_write_access) {
            show_error('No access', 401);

            return;
        }

        if ($this->_add_submit_validate() === true) {
            $name = $this->input->post('name');
            $url = $this->input->post('url');
            $cenabled = $this->input->post('cenabled');
            $description = $this->input->post('description');
            $lang = $this->input->post('regpollang');


            $ncoc = new models\Coc;
            $ncoc->setName($name);
            $ncoc->setUrl($url);
            $ncoc->setType('regpol');
            if (!empty($description)) {
                $ncoc->setDescription($description);
            }
            if (!empty($cenabled) && $cenabled == 'accept') {
                $ncoc->setAvailable(true);
            } else {
                $ncoc->setAvailable(false);
            }
            $ncoc->setLang($lang);
            $this->em->persist($ncoc);
            $this->em->flush();

            $data['success_message'] = lang('rr_regpoladded');
        } else {
            $f = form_open();
            $this->load->library('formelement');
            $f .= $this->formelement->generateAddRegpol();
            $f .= '<div class="small-12 column"></div><div class="medium-9 large-9 columns end text-right">';
            $f .= '<button type="reset" name="reset" value="reset" class="button alert">' . lang('rr_reset') . '</button> ';
            $f .= '<button type="submit" name="modify" value="submit" class="button">' . lang('rr_save') . '</button></div></div>';

            $f .= form_close();
            $data['form'] = $f;
        }
        $data['breadcrumbs'] = array(
            array('url' => base_url('manage/regpolicy/show'), 'name' => lang('title_regpols')),
            array('url' => '#', 'name' => lang('title_addregpol'), 'type' => 'current'),
        );
        $data['content_view'] = 'manage/regpol_add_view';
        $this->load->view(MY_Controller::$page, $data);
    }

    public function edit($id) {
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->load->helper('form');
        $this->load->library(array('form_validation', 'zacl'));
        $this->title = lang('title_regpoledit');

        if (empty($id) || !ctype_digit($id)) {
            show_error('Not found', 404);
        }
        /**
         * @var $coc models\Coc
         */
        $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $id, 'type' => 'regpol'));
        if (empty($coc)) {
            show_error('Not found', 404);
        }
        $has_write_access = $this->zacl->check_acl('regpol', 'write', 'default', '');
        if (!$has_write_access) {
            show_error('No access', 401);
        }
        $data = array(
            'titlepage'    => lang('title_regpol') . ': ' . html_escape($coc->getName()),
            'subtitlepage' => lang('title_regpoledit')
        );
        if ($this->_edit_submit_validate($id) === true) {
            $enable = $this->input->post('cenabled');
            if (!empty($enable) && $enable == 'accept') {
                $coc->setAvailable(true);
            } else {
                $coc->setAvailable(false);
            }
            $coc->setName($this->input->post('name'));
            $coc->setUrl($this->input->post('url'));
            $coc->setDescription($this->input->post('description'));
            $coc->setLang($this->input->post('regpollang'));
            $this->em->persist($coc);
            $this->em->flush();
            $data['success_message'] = lang('updated');
        }
        $data['coc_name'] = $coc->getName();
        $this->load->library('formelement');
        $f = form_open() . $this->formelement->generateEditRegpol($coc);
        $f .= '<div class="small-12 column"><div class="buttons large-9 medium-9 small-12 text-right columns end">';
        $f .= '<button type="reset" name="reset" value="reset" class="button alert">' . lang('rr_reset') . '</button> ';
        $f .= '<button type="submit" name="modify" value="submit" class="button">' . lang('rr_save') . '</button></div></div>';
        $f .= form_close();
        $data['breadcrumbs'] = array(
            array('url' => base_url('manage/regpolicy/show'), 'name' => lang('title_regpols')),
            array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),
        );
        $data['form'] = $f;
        $data['content_view'] = 'manage/regpol_edit_view';
        $this->load->view(MY_Controller::$page, $data);

    }

    public function getMembers($regpolid) {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $myLang = MY_Controller::getLang();
        /**
         * @var $regPolicy models\Coc
         */
        $regPolicy = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $regpolid));
        if ($regPolicy === null) {
            return $this->output->set_status_header(404)->set_output('no members found');

        }
        /**
         * @var $policyMembers models\Provider[]
         */
        $policyMembers = $regPolicy->getProviders();
        $result = array();
        foreach ($policyMembers as $member) {
            $result[] = array(
                'entityid' => $member->getEntityId(),
                'provid'   => $member->getId(),
                'name'     => $member->getNameToWebInLang($myLang),
            );
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }


    public function remove($id = null) {
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->library('zacl');
        if (empty($id) || !ctype_digit($id)) {
            return $this->output->set_status_header(404)->set_output('incorrect id or id not provided');
        }
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $has_write_access = $this->zacl->check_acl('regpol', 'write', 'default', '');
        if (!$has_write_access) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        /**
         * @var $regpol models\Coc
         */
        $regpol = $this->em->getRepository("models\Coc")->findOneBy(array('id' => '' . $id . '', 'type' => 'regpol', 'is_enabled' => false));
        if (empty($regpol)) {
            return $this->output->set_status_header(403)->set_output('Registration policy doesnt exist or is not disabled');
        }
        $this->em->remove($regpol);
        try {
            $this->em->flush();
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);

            return $this->output->set_status_header(500)->set_output('Error occurred during string data in DB');
        }

        return $this->output->set_status_header(200)->set_output('OK');
    }

}
