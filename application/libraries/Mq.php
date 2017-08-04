<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Mq
{
    private $mq;

    public function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('file');
        $this->mq = $this->setMQ();
    }


    /**
     * @return mixed
     */
    public function setMQ(){
        $m = $this->ci->config->item('mq');
        if($m !== null){
            $this->mq = $m;
        }
        return $this->mq;

    }

    /**
     * @return mixed
     */
    public function getMQ(){
        return $this->setMQ();
    }

    /**
     * @return bool
     */
    public function isClientEnabled(){
        $mq = $this->getMQ();
        if($mq === 'rabbitmq'){
            $rabbitmq = $this->ci->config->item('rabbitmq');
            if(!is_array($rabbitmq)){
                log_message('error','config:mq is set to rabbitmq but no conf info found');
                return false;
            }
            if(isset($rabbitmq['enabled'])&& $rabbitmq['enabled'] === true){
                return true;
            }
            return false;
        }
        if($mq === 'gearman'){
            $gearmanEnaled = $this->ci->config->item('gearman');
            if($gearmanEnaled === true){
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isReceiverEnabled(){

        $mq = $this->getMQ();
        if($mq === 'rabbitmq'){
            $rabbitmq = $this->ci->config->item('rabbitmq');
            if(!is_array($rabbitmq)){
                log_message('error','config:mq is set to rabbitmq but no conf info found');
                return false;
            }
            if(isset($rabbitmq['enabled'])&& $rabbitmq['enabled'] === true){
                return true;
            }
            return false;
        }
        if($mq === 'gearman'){
            $gearmanEnaled = $this->ci->config->item('gearman');
            if($gearmanEnaled === true){
                return true;
            }
            return false;
        }


        return false;
    }

}
