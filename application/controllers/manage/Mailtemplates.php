<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mailtemplates extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->title = lang('title_mailtemplates');
        MY_Controller::$menuactive = 'admins';
        $this->load->library('form_validation');
    }

    private function submitValidate($id = null, $isNew)
    {
        if ($isNew) {

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

    public function edit($id = NULL)
    {
        if (!$this->jauth->isAdministrator()) {
            show_error('Permission denied', 403);
            return;
        }

        $langsDropdown = MY_Controller::$langselect;
        $mailtmplGroups = Email_sender::mailTemplatesGroups();
        $groupDropdown = array();
        foreach ($mailtmplGroups as $k => $v) {
            $groupDropdown['' . $k . ''] = lang('' . $v['desclang'] . '');
        }
        if (!empty($id)) {
            if (ctype_digit($id)) {
                $m = $this->em->getRepository("models\MailLocalization")->findOneById($id);
            } else {
                show_error('Incorrect arg passed', 404);
                return;
            }
            if (empty($m)) {
                show_error('Not found', 404);
                return;
            }

            $data = array(
                'msggroup' => $m->getGroup(),
                'msgsubj' => $m->getSubject(),
                'msgbody' => $m->getBody(),
                'msglang' => $m->getLanguage(),
                'msgenabled' => $m->isEnabled(),
                'msgdefault' => $m->isDefault(),
                'msgattach' => $m->isAlwaysAttached(),
                'newtmpl' => FALSE,
                'success' => lang('msgtmplupdated'),
                'titlepage' => lang('title_mailtmpledit'),
            );
        } else {
            $m = new models\MailLocalization;

            $data = array(
                'msgsubj' => '',
                'msgbody' => '',
                'msglang' => '',
                'msgenabled' => TRUE,
                'msgdefault' => FALSE,
                'msgattach' => FALSE,
                'newtmpl' => TRUE,
                'titlepage' => lang('title_mailtmplnew'),
                'success' => lang('msgtmpladded'),
            );
        }


        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => base_url('manage/mailtemplates/showlist'), 'name' => lang('title_mailtemplates')),
            array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),
        );
        $data['groupdropdown'] = $groupDropdown;
        $data['langdropdown'] = $langsDropdown;
        $data['mailtmplGroups'] = $mailtmplGroups;
        if ($this->submitValidate($id, $data['newtmpl']) === TRUE) {
            $nmsgenabled = $this->input->post('msgenabled');
            $nmsgdefault = $this->input->post('msgdefault');
            $nmsgattach = $this->input->post('msgattach');

            if ($data['newtmpl'] === TRUE) {
                $m->setLanguage($this->input->post('msglang'));
                $m->setGroup($this->input->post('msggroup'));
            }
            if (!empty($nmsgdefault) && strcmp($nmsgdefault, 'yes') == 0) {
                $m->setDefault(TRUE);
                $mid = $m->getId();
                $existingDefault = $this->em->getRepository("models\MailLocalization")->findOneBy(array('mgroup' => $m->getGroup(), 'isdefault' => TRUE));
                if (!empty($existingDefault)) {
                    if ((!empty($mid) && $mid != $existingDefault->getId()) || (empty($mid))) {
                        $existingDefault->setDefault(FALSE);
                        $this->em->persist($existingDefault);
                    }
                }
            } else {
                $m->setDefault(FALSE);
            }
            $m->setBody($this->input->post('msgbody'));
            $m->setSubject($this->input->post('msgsubj'));


            if (!empty($nmsgenabled) && strcmp($nmsgenabled, 'yes') == 0) {
                $m->setEnabled(TRUE);
            } else {
                $m->setEnabled(FALSE);
            }

            if (!empty($nmsgattach) && strcmp($nmsgattach, 'yes') == 0) {
                $m->setAlwaysAttach(TRUE);
            } else {
                $m->setAlwaysAttach(FALSE);
            }

            $this->em->persist($m);
            try {
                $this->em->flush();
                $data['content_view'] = 'manage/mailtemplateseditsuccess_view';
                $this->load->view('page', $data);
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
            }
        }

        $data['content_view'] = 'manage/mailtemplatesedit_view';
        $this->load->view('page', $data);
    }

    public function showlist()
    {
        if (!$this->jauth->isAdministrator()) {
            show_error('Permission denied', 403);
            return;
        }
        $data['showaddbtn'] = TRUE;
        $mtemplates = $this->em->getRepository("models\MailLocalization")->findAll();
        $templgroups = Email_sender::mailTemplatesGroups();
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
            array('url' => base_url('manage/mailtemplates/showlist'), 'name' => lang('title_mailtemplates'),'type' => 'current'),
        );
        $this->load->view('page', $data);
    }

}
