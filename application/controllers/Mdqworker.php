<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class Mdqworker extends MY_Controller
{
    public function __construct() {
        parent::__construct();
    }

    private function signer($job) {
        echo 'signer triggered' . PHP_EOL;
        $this->load->library('mdqsigner');
        $this->mdqsigner->sign($job['entityid']);

    }

    private function refresh($job) {
        echo 'refresh triggered' . PHP_EOL;
        $this->load->library(array('j_ncache', 'trustgraph'));
        $maxAttempts = 4;
        $attempt = 1;
        $result = null;

        while ($attempt < $maxAttempts) {
            try {
                $result = $this->trustgraph->getTrustGraphLight();
                $attempt = $maxAttempts;
            } catch (Exception $e) {
                $this->em->getConnection()->close();
                $this->em->getConnection()->connect();
                $attempt++;
                sleep(2);
            }

        }

        if(is_array($result)) {
            echo "storing .......". count($result);
            $this->j_ncache->saveTrustGraph($result);
        }

    }

    private function mdqCallback($msg) {
        echo ' [x] mdqCallback Received ', $msg->body, "\n";
        $decodedBody = urlsafeB64Decode($msg->body);
        echo ' [x] mdqCallback Decoded ', $decodedBody, "\n";
        $data = json_decode($decodedBody, true);

        if (count($data) > 0) {
            if(array_key_exists('action',$data)){
                $newdata[] = $data;
            }
            else {
                $newdata = $data;
            }

            foreach ($newdata as $job) {
                if (array_key_exists('action', $job)) {
                    if ($job['action'] === 'sign') {
                        $this->signer($job);
                        continue;
                    }
                    if ($job['action'] === 'refresh') {

                        $this->refresh($job);
                        continue;
                    }
                }
            }
        }


        sleep(substr_count($msg->body, '.'));
        echo " [x] mdqCallback Done\n";

    }


    public function worker() {
        if (!is_cli()) {
            die();
        }
        $vhost = '/';
        $conf = $this->config->item('rabbitmq');
        if (!isset($conf['enabled'])) {
            log_message('error', __METHOD__ . ' missing config for rabbitmq');
            throw new Exception('Rabbit not enabled');
        }
        if (isset($conf['vhost'])) {
            $vhost = $conf['vhost'];
        }


        $callback = function ($msg) {
            try {
                $this->mdqCallback($msg);
            } catch (Exception $e) {
                echo 'Exception:  ' . $e->getMessage() . PHP_EOL;
            }
        };


        while (true) {
            $connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['password'], $vhost);
            $channel = $connection->channel();
            $channel->queue_declare('mdq', false, true, false, false);
            echo " [*] Waiting for messages. To exit press CTRL+C\n";
            $channel->basic_consume('mdq', '', false, true, false, false, $callback);

            $timeout = 120;
            while (!is_null($channel) && count($channel->callbacks)) {
                try {
                    $channel->wait(null, false, $timeout);
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    $channel->close();
                    $connection->close();
                    $channel = null;
                    $connection = null;
                } catch (Exception $e) {
                    echo ">>>> exception <<<" . PHP_EOL;
                    $channel->close();
                    $connection->close();
                    $channel = null;
                    $connection = null;
                }

            }
        }
    }

}