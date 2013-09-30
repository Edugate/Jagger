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
            return false;
        }
        $statstorage = $storage.'stats/';
        
        if(empty($args) || !is_array($args))
        {
            return false;
        }
        if(!array_key_exists('defid', $args))
        {
            return false;
        }
        $def = $em->getRepository("models\ProviderStatsDef")->findOneBy(array('id'=>$args['defid'],'type'=>'ext'));
        
        if(empty($def))
        {
            return false;
        }
        $provider = $def->getProvider();
        
        if(empty($provider))
        {
            return false;
        }
        $expectedformat = $def->getFormatType();
        $overwrite = $def->getOverwrite();
        $s = null;
        if(!empty($overwrite))
        {
           $stats = $this->em->getRepository("models\ProviderStatsCollection")->findBy(array('provider'=>$p->getId(),'statdefinition'=>$def->getId()), array('id'=>'DESC'));
           if(count($stats)>0)
           {
              $s = $stats['0'];
              $filename = $s->getFilename();
           }
        }

              
        $data = $ci->curl->simple_get($def->getSourceUrl());
        if(!empty($data))
        {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            //$fileContents = file_get_contents($_FILES[''.$data.'']['tmp_name']);
            $mimeType = $finfo->buffer($data);
            if($expectedformat === 'image')
            {
                
                if(!array_key_exists($mimeType, $img_mimes))
                {
                    
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
                    if(empty($s))
                    {
                       $st = new models\ProviderStatsCollection;
                       $st->setFilename($filename);
                       $st->setFormat($statformat);
                       $st->setProvider($provider);
                       $st->setStatDefinition($def);
                       $em->persist($st);
                    }
                    else
                    {
                        $s->updateDate();
                        $em->persist($s);
                    }
                    $em->flush();
                }
            }
            
        }
        else
        {
            echo "empty\n";
        }
            

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
