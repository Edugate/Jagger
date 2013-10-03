<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Gearmanw Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Gearmanw {

    function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('file');
    }

    
    static public function fn_externalstatcollection($job)
    {
        $ci =  & get_instance();
        $em = $ci->doctrine->em;
        $args = unserialize($job->workload());
        $job->sendStatus(1,10);
        sleep(1);
        $storage = $ci->config->item('datastorage_path');
        $img_mimes = array(
            'image/jpeg'=>'jpg',
            'image/pjpeg'=>'jpg',
            'image/png'=>'png',
            'image/x-png'=>'png',
            'image/gif'=>'gif',         
        );

        if(empty($storage))
        {
        $em->clear();
            return false;
        }
        $statstorage = $storage.'stats/';
        
        if(empty($args) || !is_array($args))
        {
            $em->clear();
            return false;
        }
        if(!array_key_exists('defid', $args))
        {
            $em->clear();
            return false;
        }
        $def = $em->getRepository("models\ProviderStatsDef")->findOneBy(array('id'=>$args['defid'],'type'=>'ext'));
        
        if(empty($def))
        {
            $em->clear();
            return false;
        }
        $job->sendStatus(1,10);
        $provider = $def->getProvider();
        
        if(empty($provider))
        {
            $em->clear();
            return false;
        }
        $job->sendStatus(2,10);
        $expectedformat = $def->getFormatType();
        $overwrite = $def->getOverwrite();
        $s = null;
        if(!empty($overwrite))
        {
           $stats = $em->getRepository("models\ProviderStatsCollection")->findBy(array('provider'=>$provider->getId(),'statdefinition'=>$def->getId()), array('id'=>'DESC'));
           if(count($stats)>0)
           {
              $s = $stats['0'];
              $filename = $s->getFilename();
           }
        }
        $job->sendStatus(3,10);

        $data = null;
        $method = $def->getHttpMethod();
        $params = $def->getPostOptions();
        if(empty($params) || !is_array($params))
        {
           $params = array();
        }
        $accesstype = $def->getAccessType();

        $ci->curl->create(''.$def->getSourceUrl().'');
        if($accesstype === 'basicauthn')
        {
          $ci->curl->http_login(''.$def->getAuthUser().'',''.$def->getAuthPass().'');
        }
        
        if($method === 'post')
        {
            $ci->curl->post($params);
        }
        $data = $ci->curl->execute();
        if(!empty($data))
        {
            $job->sendStatus(5,10);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($data);
            if($expectedformat === 'image')
            {
                
                if(!array_key_exists($mimeType, $img_mimes))
                {
                   $em->clear();
                   return false;
                }
                else
                {
                    $extension = $img_mimes[''.$mimeType.''];
                    $statformat = 'image';
                }
                
                
            }
            elseif($expectedformat === 'svg' && $mimeType === 'image/svg+xml')
            {
                $extension = 'svg';
                $statformat ='svg';
                
            }
            sleep(1);
            $job->sendStatus(7,10);
            
            if(!empty($extension) && !empty($statformat))
            {
                if(empty($filename))
                {
                   $filename = $provider->getId().'_'.$def->getId().'_'.mt_rand().'.'.$extension;
                }
                if(!write_file($statstorage.$filename, $data))
                {
                    echo "couldnt write";
                }
                else
                {
                    $job->sendStatus(8,10);
                    if(empty($s))
                    {
                       $st = new models\ProviderStatsCollection;
                       $st->setFilename($filename);
                       $st->setFormat($statformat);
                       $st->setProvider($provider);
                       $st->setStatDefinition($def);
                       $em->persist($st);
                       $job->sendStatus(9,10);
                    }
                    else
                    {
                        $s->updateDate();
                        $em->persist($s);
                        $job->sendStatus(9,10);
                    }
                    $em->flush();
                    $job->sendStatus(10,10);
                }
            }
            
        }
        else
        {
            
            log_message('debug','gworker no data stat retrieved');
        }
            
        $em->clear();
        sleep(2);

        return true;
    }

    private function registerCollectorWorkers()
    {
        $gm = new GearmanWorker();
        $gm->addServer('127.0.0.1', 4730);
        $gm->addFunction('externalstatcollection', 'Gearmanw::fn_externalstatcollection');
        $predifend = $this->ci->config->item('predefinedstats');
        if(!empty($predifend) && is_array($predifend))
        {
           echo "predefined exists\n";
           echo APPPATH."libraries/third_party/Gstatcollectors.php\n";

           if(file_exists(APPPATH."libraries/third_party/Gstatcollectors.php"))
           {
              echo "lib Gstatcollectors exists\n";
              $this->ci->load->library('third_party/Gstatcollectors.php');
              foreach($predifend as $key=>$value)
              {
                 $w = $value['worker'];
                 echo "www ".$w."\n";
                 if(!empty($w))
                 {
                     $gm->addFunction(''.$w.'', 'Gstatcollectors::fn_'.$w.'');
                 }
              }
           }
        }
        while ($gm->work());
    }

    public function worker()
    {
        $this->registerCollectorWorkers();
    }

}
