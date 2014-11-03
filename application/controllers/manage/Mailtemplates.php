<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mailtemplates extends MY_Controller
{

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

    private function submitValidate()
    {
        
    }
    public function edit($id = NULL)
    {
        if (!$this->j_auth->isAdministrator())
        {
            show_error('Permission denied', 403);
            return;
        }
        $mailtmplGroups = mailTemplatesGroups();
        $groupDropdown = array();
        foreach($mailtmplGroups as $k=>$v)
        {
            $groupDropdown[''.$k.''] = lang(''.$v['desclang'].'');
        }
        if (!empty($id))
        {
            if (ctype_digit($id))
            {
                $m = $this->em->getRepository("models\MailTemplate")->findOneById($id);       
            }
            else
            {
                show_error('Incorrect arg passed', 404);
                return;
            }
            if(empty($m))
            {
                show_error('Not found',404);
                return;
            }
            
            $data = array(
                'msgsubj'=>$m->getSubject(),
                'msgbody'=>$m->getBody(),
                'newtmpl'=>FALSE,
            );
        }
        else
        {
            $m = new models\MailTemplate();
            $data = array(
                'msgsubj'=>'',
                'msgbody'=>'',
                'newtmpl'=>TRUE,
            );
        }


        $data['groupdropdown'] =  $groupDropdown;
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
        $mtemplates = $this->em->getRepository("models\MailTemplate")->findAll();
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
