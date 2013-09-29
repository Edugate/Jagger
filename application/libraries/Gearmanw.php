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

    public function loggg()
    {

        \log_message('debug', 'PS PSPS ');
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
        echo $expectedformat."\n";
        echo $provider->getId()."\n";
        print_r($args);
        
        $data = $ci->curl->simple_get($def->getSourceUrl());
        if(!empty($data))
        {
            echo "no empty\n";
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            //$fileContents = file_get_contents($_FILES[''.$data.'']['tmp_name']);
            $mimeType = $finfo->buffer($data);
            if($expectedformat === 'image')
            {
                
                if(!array_key_exists($mimeType, $img_mimes))
                {
                    echo "image fff \n";
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
                $filename = $provider->getId().'_'.$def->getId().'_'.mt_rand().'.'.$extension;
                if(!write_file($statstorage.$filename, $data))
                {
                    echo "couldnt write";
                }
                else
                {
                    $st = new models\ProviderStatsCollection;
                    $st->setFilename($filename);
                    $st->setFormat($statformat);
                    $st->setProvider($provider);
                    $st->setStatDefinition($def);
                    $em->persist($st);
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

    private function registerExtStatCollectorWorker()
    {
        $gm = new GearmanWorker();
        $gm->addServer('127.0.0.1', 4730);
        $gm->addFunction('externalstatcollection', 'Gearmanw::fn_externalstatcollection');
        while ($gm->work());
    }

    public function worker()
    {
        $this->registerExtStatCollectorWorker();
    }

}
