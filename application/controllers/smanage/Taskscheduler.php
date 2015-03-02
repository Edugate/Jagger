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
        $this->form_validation->set_rules('isenabled', 'Id enab', 'trim');
        $this->form_validation->set_rules('cron[minute]', 'Minute', 'trim|required');
        $this->form_validation->set_rules('cron[hour]', 'Hour', 'trim|required');
        $this->form_validation->set_rules('cron[dom]', 'Day of month', 'trim|required');
        $this->form_validation->set_rules('cron[month]', 'Month', 'trim|required');
        $this->form_validation->set_rules('cron[dow]', 'Day of week', 'trim|required');
        $this->form_validation->set_rules('comment', 'Comment', 'trim|required');
        $this->form_validation->set_rules('istemplate', 'Template', 'trim');
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
            array('url' => base_url('p/page/front_page'), 'name' => lang('home')),
            array('url' => base_url(), 'name' => lang('dashboard')),
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
            array('url' => base_url('p/page/front_page'), 'name' => lang('home')),
            array('url' => base_url(), 'name' => lang('dashboard')),
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => base_url('smanage/taskscheduler/tasklist'), 'name' => lang('tasks_menulink'), 'type' => 'current'),

        );
        $data['rows'] = &$rows;
        $data['content_view'] = 'smanage/tasklist_view';
        $this->load->view('page', $data);


    }
}