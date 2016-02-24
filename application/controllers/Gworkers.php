<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Gworkers extends MY_Controller
{


    public function __construct() {
        parent::__construct();
        $this->load->library('gworkertemplates');
    }

    public function worker() {
        if (is_cli()) {
            $this->load->library('gearmanw');
            $this->gearmanw->worker();
        } else {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
    }

    public function mailqueuesender() {
        if (!is_cli()) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        log_message('info', 'MAILQUEUE STARTED : daemon needs to be restarted after any changes in configs');
        $this->load->library('doctrine');
        $em = $this->doctrine->em;
        $this->load->library('rrpreference');
        $sendOptions = array(
            'mailfrom'   => $this->config->item('mail_from'),
            'subjsuffix' => (string)$this->config->item('mail_subject_suffix')
        );
        /**
         * @var $mailsSent array
         */
        $mailsSent = array();
        while (true) {
            sleep(60);
            $isSendingEnabled = $this->config->item('mail_sending_active');
            if ($isSendingEnabled !== true) {
                log_message('warning', 'MAILQUEUE :: sending mails is disabled - check config "mail_sending_active" ');
                continue;
            }
            log_message('debug', 'MAILQUEUE :: checks for mails to be sent');
            try {
                /**
                 * @var models\MailQueue[] $mails
                 */
                $mails = $em->getRepository("models\MailQueue")->findBy(array('deliverytype' => 'mail', 'frequence' => '1', 'issent' => false));
                $mailFooter = $this->rrpreference->getTextValueByName('mailfooter');
                foreach ($mails as $mailRow) {
                    $mailId = $mailRow->getId();
                    log_message('info', 'MAILQUEUE sending mail with id: ' . $mailId);
                    if (in_array($mailId, $mailsSent)) {
                        $mailRow->setMailSent();
                        $em->persist($mailRow);
                        continue;
                    }
                    $maildata = $mailRow->getMailToArray();
                    $this->email->clear();
                    $this->email->from($sendOptions['mailfrom']);
                    $this->email->to($maildata['to']);
                    $this->email->subject($maildata['subject'] . ' ' . $sendOptions['subjsuffix']);
                    $this->email->message($maildata['data'] . PHP_EOL . '' . $mailFooter . PHP_EOL);

                    if ($this->email->send()) {
                        $mailsSent[] = $mailRow->getId();
                        $mailRow->setMailSent();
                        $em->persist($mailRow);
                    } else {
                        log_message('error', 'MAILQUEUE couldnt sent mail to ' . $maildata['to'] . '    ::' . $this->email->print_debugger());
                    }
                }
                try {
                    $em->flush();
                    $mailsSent = array();
                } catch (Exception $e) {
                    log_message('eror', 'MAILQUEUE: could not update db about sent status');
                }
                $em->clear();
            } catch (Exception $e) {
                log_message('error', 'MAILQUEUE ::' . __METHOD__ . ' lost connection to database trying to reconnect');
                $em->getConnection()->close();
                sleep(10);
                $em->getConnection()->connect();
            }

        }

        return $this->output->set_status_header(500)->set_output('unexpected exit');
    }

    public function jcronmonitor() {
        if (!is_cli()) {
            log_message('error', __METHOD__ . ' called not via cli');
            return $this->output->set_status_header(403)->set_output('Denied');
        }

        $gearmanConf = $this->config->item('gearmanconf');

        $runJobs = array();
        while (true) {
            sleep(15);
            try {
                $cronEntries = $this->em->getRepository("models\Jcrontab")->findBy(array('isenabled' => true));
                $currentTime = new \DateTime("now");
                foreach ($cronEntries as $c) {
                    $cron = Cron\CronExpression::factory($c->getCronToStr());
                    if ($cron->isDue()) {

                        $didRunInRange = $c->isLastRunMatchRange($currentTime, 60);
                        if (!$didRunInRange) {
                            $r = $this->jcronRun($c);
                            if (!empty($r)) {
                                foreach ($r as $rv) {
                                    $runJobs[] = $rv;
                                }
                            }
                        }
                    }
                }
                $this->em->flush();

            } catch (Exception $e) {
                log_message('error', 'JCRONMONITOR :: Probably lost connection to database trying to reconnect');
                $this->em->getConnection()->close();
                $this->em->getConnection()->connect();
            }

            if (count($runJobs) > 0) {
                $gclient = new GearmanClient();
                foreach ($gearmanConf['jobserver'] as $gs) {
                    try {
                        $gclient->addServer('' . $gs['ip'] . '', $gs['port']);
                    } catch (Exception $e) {
                        echo 'Exception : ' . $e . PHP_EOL;
                    }
                }

                foreach ($runJobs as $k => $j) {
                    $jstatus = $gclient->jobStatus($j);
                    if (array_key_exists('0', $jstatus) && empty($jstatus[0])) {
                        log_message('info', 'JCRON:: ' . $j . ' not known (already finished or removed from jobserver)');
                        unset($runJobs[$k]);
                        continue;
                    }
                    if (array_key_exists('1', $jstatus) && !empty($jstatus[1])) {
                        log_message('info', 'JCRON:: ' . $j . ' is still running on jobserver');
                        continue;
                    }
                    if (array_key_exists('1', $jstatus) && empty($jstatus[1])) {
                        log_message('info', 'JCRON:: ' . $j . ' is on jobserver but it is waiting for worker');
                    }
                }
            }
            $this->em->clear();
        }
        return $this->output->set_status_header(500)->set_output('unexpected exit');
    }

    private function jcronRun(\models\Jcrontab $cronEntry) {
        $isTemplate = $cronEntry->getTemplate();
        if ($isTemplate) {
            $resolvedT = $this->gworkertemplates->resolveTemplate($cronEntry->getJcommand(), $cronEntry->getJparams());
            if (empty($resolvedT)) {
                log_message('error', __METHOD__ . ' could not resolve taskcheduler template for name:' . $cronEntry->getJcommand());

                return false;
            }
        } else {
            $resolvedT[] = array('fname' => $cronEntry->getJcommand(), 'fparams' => $cronEntry->getJparams());
        }
        $gearmanConf = $this->config->item('gearmanconf');
        $jobOwnServers = $cronEntry->getJservers();
        $result = array();
        foreach ($resolvedT as $jobTo) {
            $gclient = new GearmanClient();
            if (!empty($jobOwnServers) && is_array($jobOwnServers)) {
                foreach ($jobOwnServers as $gs) {
                    $gclient->addServer('' . $gs['ip'] . '', $gs['port']);
                }
            } else {
                foreach ($gearmanConf['jobserver'] as $gs) {
                    try {
                        $gclient->addServer('' . $gs['ip'] . '', $gs['port']);
                    } catch (Exception $e) {
                        echo 'Errrpr: ' . $e;
                    }
                }
            }
            $jcommand = $jobTo['fname'];
            $jparams = $jobTo['fparams'];
            $jparams['cronid'] = $cronEntry->getId();

            try {
                $jobhandle = $gclient->doBackground($jcommand, json_encode($jparams));
                $result[] = $jobhandle;

            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
            }


        }
        if (count($result) > 0) {
            $cronEntry->setLastRun();
            $this->em->persist($cronEntry);

            return $result;
        }

        return false;


    }


}
