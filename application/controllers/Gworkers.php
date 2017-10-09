<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2017, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Gworkers extends MY_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->library('gworkertemplates');
        $this->load->library('gearmanw');
    }


    /**
     * @throws Exception
     */
    public function worker()
    {
        if (!is_cli()) {
            show_error('Access denied', 403);
        }
        $this->gearmanw->worker();
    }

    /**
     * @return array
     */
    private function getSendOptions()
    {
        $this->load->library('rrpreference');
        $replyTo = trim($this->config->item('reply_to'));
        if(FILTER_VALIDATE_EMAIL)
        $sendOptions = array(
            'mailfrom' => $this->config->item('mail_from'),
            'subjsuffix' => (string)$this->config->item('mail_subject_suffix'),
            'replyto' => null
        );

        if(filter_var($replyTo, FILTER_VALIDATE_EMAIL)){
            $sendOptions['replyto'] = $replyTo;
        }
        return $sendOptions;
    }

    public function mailqueuesender()
    {
        if (!is_cli()) {
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        log_message('info', 'MAILQUEUE STARTED : daemon needs to be restarted after any changes in configs');
        $sendOptions = $this->getSendOptions();
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
                $mails = $this->em->getRepository("models\MailQueue")->findBy(array('deliverytype' => 'mail', 'frequence' => '1', 'issent' => false));
                $mailFooter = $this->rrpreference->getTextValueByName('mailfooter');
                foreach ($mails as $mailRow) {
                    $mailId = $mailRow->getId();
                    log_message('info', 'MAILQUEUE sending mail with id: ' . $mailId);
                    if (in_array($mailId, $mailsSent, true)) {
                        $mailRow->setMailSent();
                        $this->em->persist($mailRow);
                        continue;
                    }
                    $maildata = $mailRow->getMailToArray();
                    $this->email->clear();
                    $this->email->from($sendOptions['mailfrom']);
                    if($sendOptions['replyto'] !== null){
                        $this->email->reply_to($sendOptions['replyto']);
                    }
                    $this->email->to($maildata['to']);
                    $this->email->subject($maildata['subject'] . ' ' . $sendOptions['subjsuffix']);
                    $this->email->message($maildata['data'] . PHP_EOL . '' . $mailFooter . PHP_EOL);

                    if ($this->email->send()) {
                        $mailsSent[] = $mailRow->getId();
                        $mailRow->setMailSent();
                        $this->em->persist($mailRow);
                    } else {
                        log_message('error', 'MAILQUEUE couldnt sent mail to ' . $maildata['to'] . '    ::' . $this->email->print_debugger());
                    }
                }
                try {
                    $this->em->flush();
                    $mailsSent = array();
                } catch (Exception $e) {
                    log_message('error', 'MAILQUEUE: could not update db about sent status: ' . $e);
                }
                $this->em->clear();
            } catch (Exception $e) {
                log_message('error', 'MAILQUEUE ::' . __METHOD__ . ' lost connection to database trying to reconnect: ' . $e);
                $this->em->getConnection()->close();
                sleep(10);
                $this->em->getConnection()->connect();
            }

        }

        return $this->output->set_status_header(500)->set_output('unexpected exit');
    }

    public function jcronmonitor()
    {
        if (!is_cli()) {
            log_message('error', __METHOD__ . ' called not via cli');
            return $this->output->set_status_header(403)->set_output('Denied');
        }
        log_message('info', 'Jcronmonitor : started');
        $mqueue = $this->config->item('mq');
        if ($mqueue !== 'rabbitmq') {
            $mqueue = 'gearman';
        }
        log_message('info', 'JCRONMONITOR : ' . $mqueue . ' mechanism loaded');
        while (true) {
            sleep(15);
            try {
                log_message('debug', 'JCRONMONITOR : checking for new tasks');
                /**
                 * @var $cronEntries models\Jcrontab[]
                 */
                $cronEntries = $this->em->getRepository("models\Jcrontab")->findBy(array('isenabled' => true));
                $currentTime = new \DateTime('now');
                foreach ($cronEntries as $c) {
                    $cron = Cron\CronExpression::factory($c->getCronToStr());
                    if ($cron->isDue()) {

                        $didRunInRange = $c->isLastRunMatchRange($currentTime, 60);
                        if (!$didRunInRange) {
                            $this->jcronRun($c);
                        }
                    }
                }
                $this->em->flush();

            } catch (Exception $e) {
                log_message('error', 'JCRONMONITOR :: Probably lost connection to database trying to reconnect: ' . $e);
                $this->em->getConnection()->close();
                $this->em->getConnection()->connect();
            }

            $this->em->clear();
        }

        return $this->output->set_status_header(500)->set_output('unexpected exit');
    }


    /**
     * @param \models\Jcrontab $cronEntry
     * @return array|null
     */
    private function resolveTemplate(\models\Jcrontab $cronEntry)
    {
        $isTemplate = $cronEntry->getTemplate();
        if ($isTemplate) {
            $resolvedT = $this->gworkertemplates->resolveTemplate($cronEntry->getJcommand(), $cronEntry->getJparams());
            if (empty($resolvedT)) {
                log_message('error', __METHOD__ . ' could not resolve taskcheduler template for name:' . $cronEntry->getJcommand());

                return null;
            }
        } else {
            $resolvedT[] = array('fname' => $cronEntry->getJcommand(), 'fparams' => $cronEntry->getJparams());
        }

        return $resolvedT;
    }

    private function jcronRunRabbit(\models\Jcrontab $cronEntry)
    {
        $resolvedT = (array)$this->resolveTemplate($cronEntry);
        $vhost = '/';
        $conf = $this->config->item('rabbitmq');
        if (!isset($conf['enabled'])) {
            log_message('error', __METHOD__ . ' missing config for rabbitmq');
            throw new Exception('Rabbit not enabled');
        }
        if (isset($conf['vhost'])) {
            $vhost = $conf['vhost'];
        }
        foreach ($resolvedT as $jobTo) {
            $jcommand = $jobTo['fname'];
            $jparams = $jobTo['fparams'];
            $jparams['cronid'] = $cronEntry->getId();
            $connection = new AMQPStreamConnection('' . $conf['host'], $conf['port'], '' . $conf['user'] . '', '' . $conf['password'] . '', $vhost);
            $channel = $connection->channel();
            $channel->queue_declare($jcommand, false, true, false, false);
            $data = urlsafeB64Encode(json_encode($jparams));
            $msg = new AMQPMessage($data,
                array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
            );
            $channel->basic_publish($msg, '', $jcommand);
            $channel->close();
            $connection->close();
        }

        return array('f');


    }

    /**
     * @param \models\Jcrontab $cronEntry
     * @return array|null
     */
    private function jcronRunGearman(\models\Jcrontab $cronEntry)
    {

        $resolvedT = $this->resolveTemplate($cronEntry);
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

        return $result;
    }


    /**
     * @param \models\Jcrontab $cronEntry
     * @return array|null
     */
    private function jcronRun(\models\Jcrontab $cronEntry)
    {
        $mqueue = $this->config->item('mq');
        if ($mqueue !== 'rabbitmq') {
            $result = (array)$this->jcronRunGearman($cronEntry);
        } else {
            $result = (array)$this->jcronRunRabbit($cronEntry);
        }

        if (count($result) > 0) {
            $cronEntry->setLastRun();
            $this->em->persist($cronEntry);

            return $result;
        }
        return null;
    }


}

