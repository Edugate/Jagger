<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Mailtemplates extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $this->title = lang('title_mailtemplates');
        MY_Controller::$menuactive = 'admins';
        $this->load->library('form_validation');
    }

    private function submitValidate($isNew)
    {
        if ($isNew)
        {
            $this->form_validation->set_rules('msgsubj', 'Subjecy', 'required');
            $this->form_validation->set_rules('msgbody', 'Body', 'required');
            $this->form_validation->set_rules('msglang', 'Lang', 'required');
        }
        return $this->form_validation->run();
    }

    public function edit($id = NULL)
    {
        if (!$this->j_auth->isAdministrator())
        {
            show_error('Permission denied', 403);
            return;
        }
        $langs = Email_sender::getLangs();
        $langsDropdown = languagesCodes($langs);
        $mailtmplGroups = mailTemplatesGroups();
        $groupDropdown = array();
        foreach ($mailtmplGroups as $k => $v)
        {
            $groupDropdown['' . $k . ''] = lang('' . $v['desclang'] . '');
        }
        if (!empty($id))
        {
            if (ctype_digit($id))
            {
                $m = $this->em->getRepository("models\MailLocalization")->findOneById($id);
            }
            else
            {
                show_error('Incorrect arg passed', 404);
                return;
            }
            if (empty($m))
            {
                show_error('Not found', 404);
                return;
            }

            $data = array(
                'msgsubj' => $m->getSubject(),
                'msgbody' => $m->getBody(),
                'msglang' => $m->getLanguage(),
                'newtmpl' => FALSE,
            );
        }
        else
        {
            $m = new models\MailLocalization;
            $data = array(
                'msgsubj' => '',
                'msgbody' => '',
                'msglang' => '',
                'newtmpl' => TRUE,
                'titlepage'=>'New mail template',
            );
        }


        $data['groupdropdown'] = $groupDropdown;
        $data['langdropdown'] = $langsDropdown;
        if ($this->submitValidate($data['newtmpl']) === TRUE)
        {
            if($data['newtmpl'] === TRUE)
            {
                $m->setBody($this->input->post('msgbody'));
                $m->setSubject($this->input->post('msgsubj'));
                $m->setLanguage($this->input->post('msglang'));
                $m->setGroup($this->input->post('msggroup'));
                $m->setAlwaysAttach(TRUE);
                $m->setDefault(TRUE);
                $m->setEnabled(TRUE);
                $this->em->persist($m);
                $this->em->flush();
            }
        }
        $data['content_view'] = 'manage/mailtemplatesedit_view';
        $this->load->view('page', $data);
    }

    public function showlist()
    {
        if (!$this->j_auth->isAdministrator())
        {
            show_error('Permission denied', 403);
            return;
        }
        $data['showaddbtn'] = TRUE;
        $mtemplates = $this->em->getRepository("models\MailLocalization")->findAll();
        $templgroups = mailTemplatesGroups();
        foreach ($mtemplates as $t)
        {
            if (array_key_exists($t->getGroup(), $templgroups))
            {
                $templgroups['' . $t->getGroup() . '']['data'][] = $t;
            }
            else
            {
                log_message('error', __METHOD__ . ' found record in mailtemplate table where group "' . $t->getGroup() . '" does not exist in allowed groups');
            }
        }
        $data['templgroups'] = $templgroups;
        $data['titlepage'] = lang('title_mailtemplates');
        $data['content_view'] = 'manage/mailtemplateslist_view';
        $this->load->view('page', $data);
    }

}
