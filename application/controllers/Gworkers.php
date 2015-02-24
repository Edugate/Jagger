<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Gworkers
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Gworkers extends MY_Controller
{


    function __construct()
    {
        parent::__construct();
    }

    function worker()
    {
        if (is_cli()) {
            $this->load->library('gearmanw');
            $this->gearmanw->worker();
        } else {
            show_error('denied', 403);
        }
    }

    function mailqueuesender()
    {
        if (!is_cli()) {
            show_error('denied', 403);
            return;
        }
        log_message('info', 'MAILQUEUE STARTED : daemon needs to be restarted after any changes in configs');
        $this->load->library('doctrine');
        $em = $this->doctrine->em;
        $sendOptions = array(
            'sendenabled' => $this->config->item('mail_sending_active'),
            'mailfrom' => $this->config->item('mail_from'),
            'subjsuffix' => $this->config->item('mail_subject_suffix'),
            'mailfooter' => $this->config->item('mail_footer')
        );
        if (empty($sendOptions['subjsuffix'])) {
            $sendOptions['subjsuffix'] = '';
        }
        if (empty($sendOptions['mailfooter'])) {
            log_message('warning', 'MAILQUEUE ::  it is recommended to  set default footer (mail_footer) for mails in email.php config file');
            $sendOptions['mailfooter'] = '';
        }
        while (TRUE) {
            if (empty($sendOptions['sendenabled'])) {
                log_message('warning', 'MAILQUEUE :: sending mails is disabled - check config "mail_sending_active" ');
            } else {
                log_message('debug', 'MAILQUEUE :: checks for mails to be sent');
                try {
                    $mails = $em->getRepository("models\MailQueue")->findBy(array('deliverytype' => 'mail', 'frequence' => '1', 'issent' => false));

                    foreach ($mails as $m) {
                        log_message('debug', 'MAILQUEUE sending ' . $m->getId());
                        $maildata = $m->getMailToArray();
                        $this->email->clear();
                        $this->email->from($sendOptions['mailfrom']);
                        $this->email->to($maildata['to']);
                        $this->email->subject($maildata['subject'] . ' ' . $sendOptions['subjsuffix']);
                        $this->email->message($maildata['data'] . PHP_EOL . '' . $sendOptions['mailfooter'] . PHP_EOL);
                        if ($this->email->send()) {
                            $m->setMailSent();
                            $em->persist($m);
                        } else {
                            log_message('error', 'MAILQUEUE couldnt sent mail to ' . $maildata['to'] . '    ::' . $this->email->print_debugger());
                        }
                    }
                    $em->flush();
                    $em->clear();
                } catch (Exception $e) {
                    log_message('error', 'MAIL QUEUE ::' . __METHOD__ . ' lost connection to database trying to reconnect');
                    $em->getConnection()->close();
                    sleep(10);
                    $em->getConnection()->connect();
                }
            }
            sleep(60);
        }
    }

    public function jcronmonitor()
    {
        if (!is_cli()) {
            set_status_header('403');
            echo 'denied';
            return;
        }
        while(TRUE)
        {
            $cronEntries = $this->em->getRepository("models\Jcrontab")->findBy(array('isenabled'=>true));
            echo count($cronEntries).PHP_EOL;
            foreach($cronEntries as $c)
            {
                $cron = Cron\CronExpression::factory($c->getCronToStr());
                if($cron->isDue())
                {

                }
            }
            sleep(15);
        }

    }

    private function jcronRun(\models\Jcrontab $c)
    {
        $gearmanConf = $this->config->item('gearmanconf');

        $gclient =  new GearmanClient();
        foreach($gearmanConf['jobserver'] as $gs)
        {
            $gclient->addServer(''.$gs['ip'].'',$gs['port']);
        }

        $jcommand = $c->getJcommand();
        $jparams = $c->getJparams();

        $job = $gclient->doBackground($jcommand,json_encode($jparams));






    }


}
