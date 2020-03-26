<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Message\AMQPMessage;


class Mdqworker extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    private function signer($job)
    {
        echo 'signer triggered' . PHP_EOL;
        $this->load->library('mdqsigner');
        $this->mdqsigner->sign($job['entityid']);

    }

    private function refresh($job)
    {
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

        if (is_array($result)) {
            echo "storing ......." . count($result);
            $this->j_ncache->saveTrustGraph($result);
        }

    }


    private function mdqCallback($msg)
    {
        echo ' [x] mdqCallback Received ', $msg->body, "\n";
        $decodedBody = urlsafeB64Decode($msg->body);
        echo ' [x] mdqCallback Decoded ', $decodedBody, "\n";
        $data = json_decode($decodedBody, true);

        if (count($data) > 0) {
            if (array_key_exists('action', $data)) {
                $newdata[] = $data;
            } else {
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

    /**
     * @return AMQPStreamConnection
     */
    private function connect()
    {
        $vhost = '/';
        $conf = $this->config->item('rabbitmq');
        if (isset($conf['vhost'])) {
            $vhost = $conf['vhost'];
        }
        return new AMQPStreamConnection(
            $conf['host'],
            $conf['port'],
            $conf['user'],
            $conf['password'],
            $vhost,
            false,
            'AMQPLAIN',
            null,
            'en_US',
            3.0,
            3.0,
            null,
            true,
            30,
            0.0,
            null);
    }

    /**
     * @param $connection AMQPStreamConnection
     */
    private function processConnection($connection)
    {
        $callback = function ($msg) {
            try {
                $this->mdqCallback($msg);
            } catch (Exception $e) {
                echo 'Exception:  ' . $e->getMessage() . PHP_EOL;
            }
        };



        $channel = $connection->channel();
        $channel->queue_declare('mdq', false, true, false, false);
        echo " [*] Waiting for messages. To exit press CTRL+C\n";
        $channel->basic_consume('mdq', '', false, true, false, false, $callback);
        while ($channel->is_consuming()) {
            log_message('debug','MDQWORKER in consuming state');
            $channel->wait();


        }


    }

    private function cleanup_connection($connection)
    {
        // Connection might already be closed.
        // Ignoring exceptions.
        try {
            if ($connection !== null) {
                $connection->close();
                $connection = null;
            }
        } catch (\ErrorException $e) {
        }
    }



    public function shutdown($connection)
    {

        $connection->close();

    }


    public function worker()
    {
        if (!is_cli()) {
            die();
        }

        $connection = null;
        $conf = $this->config->item('rabbitmq');
        if (!isset($conf['enabled'])) {
            log_message('error', __METHOD__ . ' missing config for rabbitmq or $config["rabbitmq"]["enabled"] is not set');
            throw new Exception('rabbitmq not enabled');
        }

        $usleepTime = 1000000;

        while (true) {

            try {
                log_message('debug','MDQWORKER connecting to rabbitmqserver');
                $connection = $this->connect();
                register_shutdown_function([$this, 'shutdown'], $connection);
                $this->processConnection($connection);
                log_message('debug','MDQWORKER connection to rabbitmqserver established');
            } catch (AMQPRuntimeException $e) {
                echo $e->getMessage() . PHP_EOL;
                log_message('error', __METHOD__ . ' ' . $e);
                $this->cleanup_connection($connection);
                usleep($usleepTime);
            } catch (\RuntimeException $e) {
                echo "Runtime exception " . PHP_EOL;
                log_message('error', __METHOD__ . ' ' . $e);
                $this->cleanup_connection($connection);
                usleep($usleepTime);
            } catch (\ErrorException $e) {
                echo "Error exception " . $e . PHP_EOL;
                log_message('error', __METHOD__ . ' ' . $e);
                $this->cleanup_connection($connection);
                usleep($usleepTime);
            } catch (AMQPIOException $e) {
                echo "Error exception " . $e . PHP_EOL;
                log_message('error', __METHOD__ . ' ' . $e);
                $this->cleanup_connection($connection);
                usleep($usleepTime);
            } catch (Exception $e) {
                echo "Error exception " . $e . PHP_EOL;
                log_message('error', __METHOD__ . ' ' . $e);
                $this->cleanup_connection($connection);
                usleep($usleepTime);
            }

        }
    }

}
        