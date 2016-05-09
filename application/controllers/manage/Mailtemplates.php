<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package     Jagger
 * @author      Middleware Team HEAnet
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright   2015 HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 */
class Mailtemplates extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->title = lang('title_mailtemplates');
        MY_Controller::$menuactive = 'admins';
        $this->load->library('form_validation');
    }

    /**
     * @param $isNew
     * @return bool
     */
    private function submitValidate($isNew) {
        if ($isNew === true) {
            $this->form_validation->set_rules('msglang', 'Lang', 'trim|required');
            $this->form_validation->set_rules('msggroup', 'Group', 'trim|required|mailtemplate_unique[msglang]');
            $this->form_validation->set_rules('msgdefault', 'default', 'xss_clean|mailtemplate_isdefault[msggroup]');
        } else {
            $this->form_validation->set_rules('msgdefault', 'default', 'xss_clean');
        }
        $this->form_validation->set_rules('msgsubj', lang('mtmplsbj'), 'required|xss_clean');

        $this->form_validation->set_rules('msgbody', lang('mtmplbody'), 'required');

        return $this->form_validation->run();
    }

    private function getMailtmpGroups() {
        $mailtmplGroups = Emailsender::mailTemplatesGroups();
        $groupDropdown = array();
        foreach ($mailtmplGroups as $k => $v) {
            $groupDropdown['' . $k . ''] = lang('' . $v['desclang'] . '');
        }

        return $groupDropdown;
    }

    private function getMailTemplate($mid = null) {
        $mTemplate = null;
        if ($mid === null) {
            $mTemplate = new models\MailLocalization;
            $mTemplate->setEnabled(true);
            $mTemplate->setDefault(false);
            $mTemplate->isAlwaysAttached(false);
        } elseif (ctype_digit($mid)) {
            $mTemplate = $this->em->getRepository('models\MailLocalization')->findOneBy(array('id' => $mid));
        }

        return $mTemplate;
    }

    public function edit($mailTempId = null) {
        if (!$this->jauth->isAdministrator()) {
            show_error('Permission denied', 403);
        }

        $langsDropdown = MY_Controller::$langselect;
        $groupDropdown = $this->getMailtmpGroups();
        /**
         * @var models\MailLocalization $mTemplate
         */
        $mTemplate = $this->getMailTemplate($mailTempId);
        if ($mTemplate === null) {
            show_error('Not found', 404);
        }
        $idExist = $mTemplate->getId();
        $data = array(
            'msggroup'       => $mTemplate->getGroup(),
            'msgsubj'        => $mTemplate->getSubject(),
            'msgbody'        => $mTemplate->getBody(),
            'msglang'        => $mTemplate->getLanguage(),
            'msgenabled'     => $mTemplate->isEnabled(),
            'msgdefault'     => $mTemplate->isDefault(),
            'msgattach'      => $mTemplate->isAlwaysAttached(),
            'newtmpl'        => (bool)($idExist === null),
            'titlepage'      => lang('title_mailtmplnew'),
            'success'        => lang('msgtmpladded'),
            'breadcrumbs'    => array(
                array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
                array('url' => base_url('manage/mailtemplates/showlist'), 'name' => lang('title_mailtemplates')),
                array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),
            ),
            'groupdropdown'  => $groupDropdown,
            'langdropdown'   => $langsDropdown,
            'mailtmplGroups' => Emailsender::mailTemplatesGroups(),
        );
        if ($idExist !== null) {
            $data['success'] = lang('msgtmplupdated');
            $data['titlepage'] = lang('title_mailtmpledit');
        }


        if ($this->submitValidate($data['newtmpl']) !== true) {
            $data['content_view'] = 'manage/mailtemplatesedit_view';

            return $this->load->view(MY_Controller::$page, $data);
        }

        $nmsgenabled = $this->input->post('msgenabled');
        $nmsgdefault = $this->input->post('msgdefault');
        $nmsgattach = $this->input->post('msgattach');

        if ($data['newtmpl'] === true) {
            $mTemplate->setLanguage($this->input->post('msglang'));
            $mTemplate->setGroup($this->input->post('msggroup'));
        }
        $mTemplate->setDefault(($nmsgdefault === 'yes'));
        if ($nmsgdefault === 'yes') {
            $mid = $mTemplate->getId();
            /**
             * @var models\MailLocalization $existingDefault
             */
            $existingDefault = $this->em->getRepository("models\MailLocalization")->findOneBy(array('mgroup' => $mTemplate->getGroup(), 'isdefault' => true));
            if ($existingDefault !== null && ($mid !== $existingDefault->getId())) {
                $existingDefault->setDefault(false);
                $this->em->persist($existingDefault);

            }
        }

        $mTemplate->setBody($this->input->post('msgbody'));
        $mTemplate->setSubject($this->input->post('msgsubj'));
        $mTemplate->setEnabled($nmsgenabled === 'yes');
        $mTemplate->setAlwaysAttach($nmsgattach === 'yes');

        $this->em->persist($mTemplate);
        try {
            $this->em->flush();
            $data['content_view'] = 'manage/mailtemplateseditsuccess_view';
            $this->load->view(MY_Controller::$page, $data);
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error(500, 'Internal server error');
        }


    }

    public function showlist() {
        if (!$this->jauth->isAdministrator()) {
            show_error('Permission denied', 403);
        }
        $data['showaddbtn'] = true;
        /**
         * @var models\MailLocalization[] $mtemplates
         */
        $mtemplates = $this->em->getRepository("models\MailLocalization")->findAll();
        $templgroups = Emailsender::mailTemplatesGroups();
        foreach ($mtemplates as $t) {
            if (array_key_exists($t->getGroup(), $templgroups)) {
                $templgroups['' . $t->getGroup() . '']['data'][] = $t;
            } else {
                log_message('error', __METHOD__ . ' found record in mailtemplate table where group "' . $t->getGroup() . '" does not exist in allowed groups');
            }
        }
        $data['templgroups'] = $templgroups;
        $data['titlepage'] = lang('title_mailtemplates');
        $data['content_view'] = 'manage/mailtemplateslist_view';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => base_url('manage/mailtemplates/showlist'), 'name' => lang('title_mailtemplates'), 'type' => 'current'),
        );
        $this->load->view(MY_Controller::$page, $data);
    }

}
