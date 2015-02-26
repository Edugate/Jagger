<?php


class Taskscheduler extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        MY_Controller::$menuactive = 'admins';
    }

    private function hasAccess()
    {

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
        if (!isset($featureEnabled['tasksmngmt']) || $featureEnabled['tasksmngmt'] !== TRUE) {
            show_error('Feature is not enabled', 403);
            return;
        }

        $this->title='Tasks Scheduler';
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
            if ($isEnabled) {
                $isEnabledHtml = '<span class="label">'.lang('rr_enabled').'</span>';
            } else {
                $isEnabledHtml = '<span class="label alert">'.lang('rr_disabled').'</span>';
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
                html_escape($t->getJcommand()),
                $paramsToHtml,
                $isDue,
                $lastRunHtml,
                $nextrun,
                $isEnabledHtml,
                '<i class="fi-pencil"></i>',

            );

        }

        $data['rows'] = &$rows;
        $data['content_view'] = 'smanage/tasklist_view';
        $this->load->view('page', $data);


    }
}