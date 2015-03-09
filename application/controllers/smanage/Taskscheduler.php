<?php


class Taskscheduler extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        MY_Controller::$menuactive = 'admins';
    }

    private function submit_validate()
    {

        $params = $this->input->post('params');
        if(!empty($params) && is_array($params))
        {
            foreach($params as $k=>$p)
            {
                if(array_key_exists('name',$p))
                {
                    if(array_key_exists('value',$p))
                    {
                        $this->form_validation->set_rules('params['.$k.'][value]','Params value','trim');

                        if(!empty($p['value']))
                        {
                            $this->form_validation->set_rules('params['.$k.'][name]','Params name','trim|required');
                        }
                    }
                }


            }
        }
        $this->form_validation->set_rules('isenabled', lang('taskenabled'), 'trim');
        $this->form_validation->set_rules('cron[minute]', lang('cronminute'), 'trim|required|valid_cronminute');
        $this->form_validation->set_rules('cron[hour]', lang('cronhour'), 'trim|required|valid_cronhour');
        $this->form_validation->set_rules('cron[dom]', lang('crondom'), 'trim|required|valid_crondom');
        $this->form_validation->set_rules('cron[month]', lang('cronmonth'), 'trim|required|valid_cronmonth');
        $this->form_validation->set_rules('cron[dow]', lang('crondow'), 'trim|required|valid_crondow');
        $this->form_validation->set_rules('comment', lang('rr_description'), 'trim|required|alpha_numeric_spaces');
        $this->form_validation->set_rules('istemplate', lang('tasktemplate'), 'trim');
        $this->form_validation->set_rules('fnname','Fn name','trim|required|alpha_dash');

        return $this->form_validation->run();

    }

    public function taskedit($id = null)
    {
        if (!empty($id) && !ctype_digit($id)) {
            show_error('Incorrect param provided', 403);
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
            return;
        }

        if (!$this->j_auth->isAdministrator()) {
            show_error('no permission', 403);
            return;
        }

        $this->load->library('form_validation');
        $featureEnabled = $this->config->item('featenable');
        if (!isset($featureEnabled['tasks']) || $featureEnabled['tasks'] !== TRUE) {
            show_error('Feature is not enabled', 403);
            return;
        }
        if (!empty($id)) {
            $task = $this->em->getRepository("models\Jcrontab")->findOneBy(array('id' => $id));
            if (empty($task)) {
                show_error('task not found', 404);
            }
        } else {
            $task = new models\Jcrontab();
        }


        $orig = array(
            'comment' => $task->getJcomment(),
            'jminute' => $task->getMinutes(),
            'jhour' => $task->getHours(),
            'jdow' => $task->getDaysOfWeek(),
            'jdom' => $task->getDaysOfMonth(),
            'jmonth' => $task->getMonths(),
            'jenabled' => $task->getEnabled(),
            'jtemplate' => $task->getTemplate(),
            'jcommand' => $task->getJcommand(),
            'jparams' => $task->getJparams(),
        );

        $data['orig'] = $orig;


        $data['content_view'] = 'smanage/taskedit_view';
        if ($this->submit_validate() === TRUE) {

            $task->setMinutes($this->input->post('cron[minute]'));
            $task->setHours($this->input->post('cron[hour]'));
            $task->setDayofweek($this->input->post('cron[dow]'));
            $task->setDayofmonth($this->input->post('cron[dom]'));
            $task->setMonths($this->input->post('cron[month]'));

            $task->setJcomment($this->input->post('comment'));
            $task->setJcommand($this->input->post('fnname'));
            $isenabeld = $this->input->post('isenabled');
            $istemplate = $this->input->post('istemplate');
            if(!empty($isenabeld))
            {
                $task->setEnabled(true);
            }
            else{
                $task->setEnabled(false);
            }
            if(!empty($istemplate))
            {
                $task->setTemplate(true);
            }
            else
            {
                $task->setTemplate(false);
            }

            $parameters = $this->input->post('params');
            $paramsToSet = array();
            if(is_array($parameters))
            {
                foreach($parameters as $k=>$v)
                {
                    if(!array_key_exists('name',$v) || strlen($v['name']) ==0)
                    {
                        unset($parameters[$k]);
                        continue;
                    }

                    if(array_key_exists('value', $v)) {
                        $paramsToSet['' . $v['name'] . ''] = $v['value'];
                    }
                    else
                    {
                        $paramsToSet['' . $v['name'] . ''] = null;
                    }

                }
            }

            $task->setJparams($paramsToSet);

            try{
                $cronToTest = Cron\CronExpression::factory($task->getCronToStr());
                $this->em->persist($task);
                $this->em->flush();
                $data['msg'] = html_escape(lang('taskupsuccess'));
            }
            catch(Exception $e)
            {
                log_message('error',__METHOD__.' '.$e);
                $data['errormsg'] = html_escape('One of the value (minutes,hours,etc) is invalid');

            }



            $data['content_view'] = 'smanage/taskupdatesuccess_view';

            $this->load->view('page',$data);



        } else {

            if($this->input->post()) {

                $data['paramssubmit'] = $this->input->post('params');
                if(empty($data['paramssubmit']))
                {
                    $data['paramssubmit'] = array();
                }
            }
            else
            {
                $data['paramssubmit'] = null;

            }

            $data['formdata'] = 'sf';


        }


        if (empty($id)) {
            $title = lang('task_new');
        } else {
            $title = lang('task_edit');
        }
        $this->title = $title;
        $data['titlepage'] = $title;

        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => base_url('smanage/taskscheduler/tasklist'), 'name' => lang('tasks_menulink')),
            array('url' => '#', 'name' => $title, 'type' => 'current')
        );
        $this->load->view('page', $data);

    }

    public function tasklist()
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
            return;
        }

        if (!$this->j_auth->isAdministrator()) {
            show_error('no permission', 403);
            return;
        }

        $featureEnabled = $this->config->item('featenable');
        if (!isset($featureEnabled['tasks']) || $featureEnabled['tasks'] !== TRUE) {
            show_error('Feature is not enabled', 403);
            return;
        }

        $this->load->library('table');
        $this->title = 'Tasks Scheduler';
        $data['titlepage'] = $this->title;

        $tasks = $this->em->getRepository("models\Jcrontab")->findAll();
        $rows = array();
        foreach ($tasks as $t) {
            $cron = Cron\CronExpression::factory($t->getCronToStr());
            $isDue = lang('rr_no');
            if ($cron->isDue()) {

                $isDue = lang('rr_yes');

            }
            $nextrun = $cron->getNextRunDate()->format('Y-m-d H:i:s');


            $isEnabled = $t->getEnabled();
            $isTemplate = $t->getTemplate();
            if ($isTemplate) {
                $isTemplateHtml = '<span class="label">template</span>';
            } else {
                $isTemplateHtml = '';
            }
            if ($isEnabled) {
                $isEnabledHtml = '<span class="label">' . lang('rr_enabled') . '</span>';
            } else {
                $isEnabledHtml = '<span class="label alert">' . lang('rr_disabled') . '</span>';
            }
            $lastRun = $t->getLastRun();
            $lastRunHtml = 'never';
            if (!empty($lastRun)) {
                $lastRunHtml = date('Y-m-d H:i:s', $lastRun->format('U') + j_auth::$timeOffset);

            }
            $params = $t->getJparams();
            $paramsToHtml = '';
            foreach ($params as $k => $p) {
                $paramsToHtml .= '' . html_escape($k) . ':' . html_escape($p) . '<br />';
            }
            $rows[] = array(


                html_escape($t->getCronToStr()),
                html_escape($t->getJcomment()),
                html_escape($t->getJcommand()) . ' ' . $isTemplateHtml,
                $paramsToHtml,
                $isDue,
                $lastRunHtml,
                $nextrun,
                $isEnabledHtml,
                '<a href="' . base_url('smanage/taskscheduler/taskedit/' . $t->getId() . '') . '"<i class="fi-pencil"></i></a>',

            );

        }
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => base_url('smanage/taskscheduler/tasklist'), 'name' => lang('tasks_menulink'), 'type' => 'current'),

        );
        $data['rows'] = &$rows;
        $data['content_view'] = 'smanage/tasklist_view';
        $this->load->view('page', $data);


    }
}
