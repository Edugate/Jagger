<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2017 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Msigner extends MY_Controller
{

    public function __construct() {
        parent::__construct();
    }

    private function genOptions($hash, $type, $oid) {
        if ($type === 'federation') {
            $fed = $this->em->getRepository("\models\Federation")->findOneBy(array('id' => '' . $oid . ''));
            if ($fed === null) {
                throw new Exception('Federation not found');
            }
            $hasWriteAccess = $this->zacl->check_acl('f_' . $fed->getId(), 'write', 'federation', '');
            if (!$hasWriteAccess) {
                throw new Exception('Access denied');
            }
            $digest1 = $fed->getDigest();
            if (empty($digest1)) {
                $digest1 = $hash;
            }
            $digest2 = $fed->getDigestExport();
            if (empty($digest2)) {
                $digest2 = $hash;
            }
            log_message('debug', __METHOD__ . ' final digestsign is set to: ' . $digest1 . 'and for export-federation if enabled set to: ' . $digest2);
            $encfedname = $fed->getSysname();
            $sourceurl = base_url('metadata/federation/' . $encfedname . '/metadata.xml');
            $options[] = array(
                'src'     => '' . $sourceurl . '',
                'type'    => 'federation',
                'encname' => '' . $encfedname . '',
                'digest'  => '' . $digest1 . '');
            $localexport = $fed->getLocalExport();
            if (!empty($localexport)) {
                $options[] = array('src' => '' . base_url() . 'metadata/federationexport/' . $encfedname . '/metadata.xml', 'type' => 'federationexport', 'encname' => '' . $encfedname . '', 'digest' => '' . $digest2 . '');
            }

            return $options;

        } elseif ($type === 'provider') {
            $provider = $this->em->getRepository("\models\Provider")->findOneBy(array('id' => '' . $oid . ''));
            if (null === $provider) {
                throw new Exception('Provider not found');
            }
            $isLocal = $provider->getLocal();
            $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity');
            if ($isLocal !== true || !$hasWriteAccess) {
                throw new Exception('Access denied');
            }
            $digest1 = $provider->getDigest();
            if (empty($digest1)) {
                $digest1 = $hash;
            }
            $encodedentity = base64url_encode($provider->getEntityId());
            $sourceurl = base_url('metadata/circle/' . $encodedentity . '/metadata.xml');
            $options[] = array(
                'src'     => '' . $sourceurl . '',
                'type'    => 'provider',
                'encname' => '' . $encodedentity . '',
                'digest'  => '' . $digest1 . '');

            return $options;

        } else {
            throw new Exception('Unknown request');
        }


    }

    private function runViaGearman($hash, $type, $oid) {
        if (!class_exists('GearmanClient')) {
            throw new Exception('Gearman is not supported by the system');
        }
        $gearmanenabled = $this->config->item('gearman');
        if ($gearmanenabled !== true) {
            throw new Exception('Gearman is not enabled');
        }
        $client = new GearmanClient();
        $jobservers = array();
        $gearmanConf = $this->config->item('gearmanconf');
        foreach ($gearmanConf['jobserver'] as $v) {
            $jobservers[] = '' . $v['ip'] . ':' . $v['port'] . '';
        }
        try {
            $client->addServers('' . implode(',', $jobservers) . '');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' GeamanClient couldnt add job-server');
            throw new Exception('Cant connect/add to job-server(s)');
        }


        $options = $this->genOptions($hash, $type, $oid);


        foreach ($options as $opt) {
            $client->doBackground('metadatasigner', '' . json_encode($opt) . '');
        }

    }


    private function runViaRabbit($hash, $type, $oid) {
        $conf = (array)$this->config->item('rabbitmq');
        if (!isset($conf['enabled'])) {
            log_message('error', __METHOD__ . ' missing config for rabbitmq');
            throw new Exception('Rabbit not enabled');
        }

        $connection = new AMQPStreamConnection('' . $conf['host'], $conf['port'], '' . $conf['user'] . '', '' . $conf['password'] . '');
        $channel = $connection->channel();

        $channel->queue_declare('metadatasigner', false, true, false, false);

        $options = $this->genOptions($hash, $type, $oid);

        $data = urlsafeB64Encode(json_encode($options));

        $msg = new AMQPMessage($data,
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );
        $channel->basic_publish($msg, '', 'metadatasigner');

        $channel->close();
        $connection->close();


    }

    public function signer() {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $hash = $this->config->item('signdigest');
        if ($hash === null) {
            log_message('debug', __METHOD__ . ' signdigest empty or not found in config file, using system default: SHA-1');
            $hash = 'SHA-1';
        }

        $type = $this->uri->segment(3);
        $oid = $this->uri->segment(4);
        if (empty($type) || empty($oid) || !ctype_digit($oid)) {
            return $this->output->set_status_header(404)->set_output(lang('error404'));
        }

        $this->load->library('zacl');


        $mqueue = $this->config->item('mq');
        if ($mqueue !== 'rabbitmq') {
            try {
                $this->runViaGearman($hash, $type, $oid);
            } catch (Exception $e) {
                $code = $e->getCode();
                log_message('error',__METHOD__.' '.$e->getMessage());
                if ($code === 404 || $code === 403) {
                    return $this->output->set_status_header($code)->set_output($e->getMessage());
                }
                return $this->output->set_status_header(500)->set_output($e->getMessage());
            }
        } else {
            try {
                $this->runViaRabbit($hash, $type, $oid);
            } catch (Exception $e) {
                log_message('error',__METHOD__.' '.$e->getMessage());
                $code = $e->getCode();
                if ($code === 404 || $code === 403) {
                    return $this->output->set_status_header($code)->set_output($e->getMessage());
                }
                return $this->output->set_status_header(500)->set_output($e->getMessage());
            }
        }
        return $this->output->set_status_header(200)->set_output(lang('taskssent'));

    }

}
