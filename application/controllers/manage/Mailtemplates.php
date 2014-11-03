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
    }

    public function showlist()
    {
        if(!$this->j_auth->isAdministrator())
        {
            show_error('Permission denied', 403);
            return;
        }
        $mtemplates = $this->em->getRepository("models\MailTemplate")->findAll();
        $templgroups = mailTemplatesGroups();
        foreach($mtemplates as $t)
        {
            if(array_key_exists($t->getGroup(), $templgroups))
            {
                $templgroups[''.$t->getGroup().'']['data'][] = $t;
            }
            else
            {
                log_message('error', __METHOD__.' found record in mailtemplate table where group "'.$t->getGroup().'" does not exist in allowed groups');
            }
        }
        $data['templgroups'] = $templgroups;
        $data['titlepage'] = lang('title_mailtemplates');
        $data['content_view'] = 'manage/mailtemplateslist';
        $this->load->view('page',$data);
    }

}
