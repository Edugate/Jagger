<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use PhpAmqpLib\Connection\AMQPStreamConnection;


/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2013 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Gearmanw
{

    public function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('file');
    }

    /**
     * @param GearmanJob|\PhpAmqpLib\Message\AMQPMessage $job
     * @return bool
     */
    private static function fn_externalstatcollection($job) {


        $ci = &get_instance();
        log_message('info', __METHOD__ . ' received job');
        $em = $ci->doctrine->em;
        $jobStatus = false;
        if ($job instanceof GearmanJob) {
            $args = @unserialize($job->workload());
            if (empty($args) || !is_array($args)) {
                $args = json_decode($job->workload(), true);
                if (empty($args) || !is_array($args)) {
                    log_message('error', 'GEARMAN ::' . __METHOD__ . ' didnt received args from requester');
                    $em->clear();

                    return false;
                }
                $jobStatus = true;
            }
        } elseif ($job instanceof \PhpAmqpLib\Message\AMQPMessage) {
            $args = @unserialize($job->body);
            if (empty($args) || !is_array($args)) {
                $args = json_decode($job->body, true);
                if (empty($args) || !is_array($args)) {
                    log_message('error', 'RABBIT ::' . __METHOD__ . ' didnt received args from requester');
                    $em->clear();
                    return false;
                }
            }
        } else {
            throw new Exception('incorrect instance');
        }



        if ($jobStatus) {
            $job->sendStatus(1, 10);
        }
        sleep(1);
        $storage = $ci->config->item('datastorage_path');
        $img_mimes = array(
            'image/jpeg'  => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png'   => 'png',
            'image/x-png' => 'png',
            'image/gif'   => 'gif',
        );

        if (empty($storage)) {
            log_message('error', __METHOD__ . ' :: datastorage not found');
            $em->clear();

            return false;
        }

        $statstorage = $storage . 'stats/';
        if (!array_key_exists('defid', $args)) {
            log_message('error', __METHOD__ . ' ::' . __METHOD__ . ' definition stat id not found in args');
            $em->clear();

            return false;
        } else {
            log_message('debug', 'GEARMAN ::' . __METHOD__ . ' processing job for defid ' . $args['defid'] . '');
        }
        $maxattempts = 2;
        $attempt = 0;
        /**
         * @var $def models\ProviderStatsDef
         */
        while ($attempt < $maxattempts) {
            try {
                $def = $em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $args['defid'], 'type' => 'ext'));
                $attempt = $maxattempts;
            } catch (Exception $e) {
                if ($attempt < $maxattempts) {
                    log_message('warning', __METHOD__ . ' lost connection to database trying to reconnect');
                } else {
                    log_message('error', __METHOD__ . ' no connection to database');
                }
                $em->getConnection()->close();
                $em->getConnection()->connect();
                $attempt++;
                sleep(2);
            }
        }

        if (null === $def) {
            log_message('error', 'GEARMAN ::' . __METHOD__ . ' defition stat not found with provider defid');
            $em->clear();

            return false;
        }
        if ($jobStatus) {
            $job->sendStatus(1, 10);
        }
        $provider = $def->getProvider();

        if ($provider === null) {
            log_message('debug', __METHOD__ . ' statdefinition has no provider owner');
            $em->clear();

            return false;
        }
        if ($jobStatus) {
            $job->sendStatus(2, 10);
        }
        $expectedformat = $def->getFormatType();
        $overwrite = $def->getOverwrite();
        $s = null;
        if (!empty($overwrite)) {
            $stats = $em->getRepository("models\ProviderStatsCollection")->findBy(array('provider' => $provider->getId(), 'statdefinition' => $def->getId()), array('id' => 'DESC'));
            if (count($stats) > 0) {
                $s = $stats['0'];
                $filename = $s->getFilename();
            }
        }
        if ($jobStatus) {
            $job->sendStatus(3, 10);
        }

        $data = null;
        $method = $def->getHttpMethod();
        $params = (array)$def->getPostOptions();
        $accesstype = $def->getAccessType();

        $ci->curl->create('' . $def->getSourceUrl() . '');
        if ($accesstype === 'basicauthn') {
            $ci->curl->http_login('' . $def->getAuthUser() . '', '' . $def->getAuthPass() . '');
        }

        if ($method === 'post') {
            $ci->curl->post($params);
        }
        log_message('debug', __METHOD__ . ' executing curl');
        $curltimeout = $ci->config->item('curltimeout');

        if (isset($curltimeout)) {
            $addoptions = array('TIMEOUT' => (int)$curltimeout);
            log_message('debug', __METHOD__ . ' curl setting timeout: ' . (int)$curltimeout);
            $ci->curl->options($addoptions);
        }

        $data = $ci->curl->execute();

        if (!empty($data)) {
            log_message('debug', __METHOD__ . ' received data not empty');
            $job->sendStatus(5, 10);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($data);
            if ($expectedformat === 'image') {
                log_message('debug', __METHOD__ . ' mimetype of received data: ' . $mimeType . ' checking if allowed');
                if (!array_key_exists($mimeType, $img_mimes)) {
                    log_message('error', __METHOD__ . ' not allowed mimetype: ' . $mimeType);
                    $em->clear();

                    return false;
                } else {
                    log_message('debug', __METHOD__ . ' mimetype is allowed... processing');
                    $extension = $img_mimes['' . $mimeType . ''];
                    $statformat = 'image';
                }
            } elseif ($expectedformat === 'svg' && $mimeType === 'image/svg+xml') {
                $extension = 'svg';
                $statformat = 'svg';
            } else {
                log_message('error', __METHOD__ . ' not allowed mimetype: ' . $mimeType);
            }

            sleep(1);
            if ($jobStatus) {
                $job->sendStatus(7, 10);
            }

            if (!empty($extension) && !empty($statformat)) {
                if (empty($filename)) {
                    $filename = $provider->getId() . '_' . $def->getId() . '_' . mt_rand() . '.' . $extension;
                }
                if (!write_file($statstorage . $filename, $data)) {
                    log_message('debug', __METHOD__ . ' coulnd write file ' . $statstorage . $filename . ' on disk');

                    return false;
                } else {
                    $em->getConnection()->close();
                    $em->getConnection()->connect();
                    if (empty($s)) {
                        $st = new models\ProviderStatsCollection;
                        $st->setFilename($filename);
                        $st->setFormat($statformat);
                        $st->setProvider($provider);
                        $st->setStatDefinition($def);
                        $em->persist($st);
                    } else {
                        $s->updateDate();
                        $em->persist($s);
                    }
                    if ($jobStatus) {
                        $job->sendStatus(9, 10);
                    }
                    try {
                        $em->flush();
                    } catch (Exception $e) {
                        log_message('error', __METHOD__ . ' ' . $e);
                    }
                    if($jobStatus) {
                        $job->sendStatus(10, 10);
                    }
                }
            }
        } else {
            log_message('error', __METHOD__.'::'.$ci->curl->error_string);
        }

        $em->clear();
        sleep(2);

        return true;
    }




    /**
     * @todo finish rabbit
     */
    private function registerRabbitCollectorWorkers() {
        log_message('debug','Registering rabbit workers');
        $config = $this->ci->config->item('rabbitmq');
        $vhost = '/';
        if (isset($config['vhost'])) {
            $vhost = $config['vhost'];
        }
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password'], $vhost);
        $channel = $connection->channel();

        $callback = function ($msq) {
            self::fn_externalstatcollection($msq);
        };
        $channel->basic_consume('externalstatcollection', '', false, true, false, false, $callback);
        while (count($channel->callbacks)) {
            $channel->wait();
        }

    }

    /**
     * @deprecated
     */
    private function registerGearmanCollectorWorkers() {
        $gearmanConfig = $this->ci->config->item('gearmanconf');
        if (empty($gearmanConfig) || !isset($gearmanConfig['jobserver'])) {
            log_message('error', 'config[gearmanconf][jobserver] not found in config_rr file');

            return false;
        }
        $gm = new GearmanWorker();
        foreach ($gearmanConfig['jobserver'] as $j) {
            $gm->addServer('' . $j['ip'], $j['port']);
        }
        $gm->addFunction('externalstatcollection', 'Gearmanw::fn_externalstatcollection');
        $predefined = $this->ci->config->item('predefinedstats');
        if (!empty($predefined) && is_array($predefined)) {
            echo "predefined exists\n";
            echo APPPATH . "libraries/third_party/Gstatcollectors.php\n";

            if (file_exists(APPPATH . "libraries/third_party/Gstatcollectors.php")) {
                echo "lib Gstatcollectors exists\n";
                $this->ci->load->library('third_party/Gstatcollectors.php');
                foreach ($predefined as $key => $value) {
                    $w = $value['worker'];
                    echo "www " . $w . "\n";
                    if (!empty($w)) {
                        $gm->addFunction('' . $w . '', 'Gstatcollectors::fn_' . $w . '');
                    }
                }
            }
        }
        while ($gm->work());
    }

    private function registerCollectorWorkers() {
        $mq = $this->ci->config->item('mq');
        if ($mq !== 'rabbitmq') {
            $this->registerGearmanCollectorWorkers();
        } else {
            $this->registerRabbitCollectorWorkers();
        }
    }

    public function worker() {
        $this->registerCollectorWorkers();
    }

}
