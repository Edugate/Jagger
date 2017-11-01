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
class Syncwrk extends MY_Controller
{
    protected $curlMaxsize, $curlTimeout;

    public function __construct() {
        parent::__construct();
        $this->curlMaxsize = $this->config->item('curl_metadata_maxsize');
        if ($this->curlMaxsize === null) {
            $this->curlMaxsize = 20000;
        }
        $this->curlTimeout = $this->config->item('curl_timeout');
        if ($this->curlTimeout === null) {
            $this->curlTimeout = 60;
        }

    }


    private function syncEntity(\PhpAmqpLib\Message\AMQPMessage $job) {

        $decoded = json_decode(urlsafeB64Decode($job->body), true);
        if (!array_key_exists('entityid', $decoded) || !array_key_exists('url', $decoded)) {
            return false;
        }
        $entityid = $decoded['entityid'];
        $url = $decoded['url'];
        $this->load->library('metadata2array');
        $maxsize = $this->curlMaxsize;
        $sslvalidate = true;
        $sslverifyhost = 0;
        if ($sslvalidate) {
            $sslverifyhost = 2;
        }
        $curloptions = array(
            CURLOPT_SSL_VERIFYPEER   => $sslvalidate,
            CURLOPT_SSL_VERIFYHOST   => $sslverifyhost,
            CURLOPT_TIMEOUT          => $this->curlTimeout,
            CURLOPT_BUFFERSIZE       => 8192,
            CURLOPT_NOPROGRESS       => false,
            CURLOPT_USERAGENT        => 'Jagger Agent',
            CURLOPT_PROGRESSFUNCTION => function ($DownloadSize, $Downloaded, $UploadSize, $Uploaded) use ($maxsize) {
                return ($Downloaded > ($maxsize * 1024)) ? 1 : 0;
            }
        );
        $xmlbody = $this->curl->simple_get('' . $url . '', array(), $curloptions);
        if (empty($xmlbody)) {
            echo(html_escape($this->curl->error_string));

            return false;
        }
        libxml_use_internal_errors(true);

        $metadata = $this->metadata2array->rootConvert($xmlbody, true);

        if (!is_array($metadata) || !array_key_exists($entityid, $metadata)) {
            log_message('error', __METHOD__.'entity: ' . $entityid . ' not found in synced metadata');
            return false;
        }

        $newEnt = $metadata['' . $entityid . ''];
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('entityid' => $entityid));
        if ($ent === null) {
            log_message('error', __METHOD__ . ' ' . $entityid . ' does not exist in db');

            return false;
        }

        $type = $ent->getTypesToArray();
        // replace certs for IDP parts
        if ($type['idp']) {
            $newIDPSSOcerts = null;
            $newIDPAACerts = null;
            if (isset($newEnt['details']['idpssodescriptor']['certificate'])) {
                $newIDPSSOcerts = $newEnt['details']['idpssodescriptor']['certificate'];
                $oldCerts = $ent->getCertificates();
                foreach ($oldCerts as $v) {
                    $t = $v->getType();
                    if ($t === 'idpsso') {
                        $ent->removeCertificate($v);
                    }
                }
                foreach ($newIDPSSOcerts as $z) {
                    $k = new models\Certificate();
                    $k->setCertUse($z['use']);
                    $k->setCertdata($z['x509data']['x509certificate']);
                    $k->setCertType('x509');
                    $k->setAsIDPSSO();
                    $k->setProvider($ent);
                    if(isset($z['encmethods']) && is_array($z['encmethods'])){
                        $k->setEncryptMethods($z['encmethods']);
                    }
                    $ent->setCertificate($k);
                    $this->em->persist($k);
                }
            }
            if (isset($newEnt['details']['aadescriptor']['certificate'])) {
                $newIDPAACerts = $newEnt['details']['aadescriptor']['certificate'];
                $oldCerts = $ent->getCertificates();
                foreach ($oldCerts as $v) {
                    $t = $v->getType();
                    if ($t === 'aa') {
                        $ent->removeCertificate($v);
                    }
                }
                foreach ($newIDPAACerts as $z) {
                    $k = new models\Certificate();
                    $k->setCertUse($z['use']);
                    $k->setCertdata($z['x509data']['x509certificate']);
                    $k->setCertType('x509');
                    $k->setType('aa');
                    if(isset($z['encmethods']) && is_array($z['encmethods'])){
                        $k->setEncryptMethods($z['encmethods']);
                    }
                    $k->setProvider($ent);
                    $ent->setCertificate($k);
                    $this->em->persist($k);
                }
            }

        }


        if ($type['sp']) {
            if (isset($newEnt['details']['spssodescriptor']['certificate'])) {
                $newSPSSOcerts = $newEnt['details']['spssodescriptor']['certificate'];
                $oldCerts = $ent->getCertificates();
                foreach ($oldCerts as $v) {
                    $t = $v->getType();
                    if ($t === 'spsso') {
                        $ent->removeCertificate($v);
                    }
                }
                foreach ($newSPSSOcerts as $z) {
                    $k = new models\Certificate();
                    $k->setCertUse($z['use']);
                    $k->setCertdata($z['x509data']['x509certificate']);
                    $k->setCertType('x509');
                    $k->setAsSPSSO();
                    if(isset($z['encmethods']) && is_array($z['encmethods'])){
                        $k->setEncryptMethods($z['encmethods']);
                    }
                    $k->setProvider($ent);
                    $ent->setCertificate($k);
                    $this->em->persist($k);
                }
            }
        }

        $this->em->persist($ent);

        try {
            $this->em->flush();
        } catch (Exception $e) {
            log_message('error ', __METHOD__ . ' ' . $e);

            return false;
        }

        return true;
    }

    public function rabbitworker() {
         if (!is_cli()) {
            show_error('Access denied', 403);
        }
        log_message('debug', 'Registering rabbitmq worker');
        $config = $this->config->item('rabbitmq');
        $vhost = '/';
        if (isset($config['vhost'])) {
            $vhost = $config['vhost'];
        }
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password'], $vhost);
        $channel = $connection->channel();

        $callback = function ($msq) {
            $this->syncEntity($msq);
        };
        $channel->basic_consume('syncentity', '', false, true, false, false, $callback);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }


}
