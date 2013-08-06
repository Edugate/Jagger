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
 * Msigner Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Msigner extends MY_Controller {

   function __construct()
   {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            show_error('access denied',403);
            return;
        }
        $this->tmp_providers = new models\Providers;
        $this->load->library('zacl');
   }
  
   function signer()
   {
       if(!$this->input->is_ajax_request())
       {
          show_error('access denied',403);
          return;
       }
       $type =  $this->uri->segment(3);
       $id = $this->uri->segment(4);
       if(empty($type) || empty($id))
       {
           show_error('empty tyle or id: '.lang('error404').'',404);
       }
       $gearmanenabled = $this->config->item('gearman');
       if(empty($gearmanenabled))
       {
           show_error('gearman is not enabled '.lang('error404').'',404);
       }       
       $client = new GearmanClient();
       $jobservers = array();
       $j = $this->config->item('gearmanconf');
       foreach($j['jobserver'] as $v)
       {
          $jobservers[] = ''.$v['ip'].':'.$v['port'].'';
       }
       try{
            $client->addServers(''.implode(",",$jobservers).'');
       }
       catch (Exception $e)
       {
              log_message('error', 'GeamanClient couldnt add job-server');
              echo "GeamanClient couldnt add job-server";
              return false;
       }


       $options = array();
       if($type === 'federation' && is_numeric($id))
       {
           $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id'=>''.$id.''));
           if(empty($fed))
           {
                echo lang('error_fednotfound');
                return;
           }
           $has_write_access = $this->zacl->check_acl('f_' . $fed->getId(), 'write','federation', '');
           if(!$has_write_access)
           {
               echo lang('error403');
               return;
           }
           $encfedname = base64url_encode($fed->getName());
           $sourceurl = base_url().'metadata/federation/'.$encfedname.'/metadata.xml';
           $options[] = array('src'=>''.$sourceurl.'','type'=>'federation','encname'=>''.$encfedname.'');
           $localexport = $fed->getLocalExport();
           if(!empty($localexport))
           {
              $options[] = array('src'=>''.base_url().'metadata/federationexport/'.$encfedname.'/metadata.xml','type'=>'federationexport','encname'=>''.$encfedname.'');
           }

           foreach($options as $opt)
           {
              $result = $client->doBackground('metadatasigner',''.json_encode($opt).'' );
           }
           echo lang('taskssent'); 

       }
       elseif($type === 'provider' && is_numeric($id))
       {
          $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>''.$id.''));
          if(empty($provider))
          {     
               echo lang('rerror_provnotfound');
               return;
          }
          $is_local = $provider->getLocal();
          if($is_local !== TRUE)
          {
               echo lang('error403');
               return;
          }
          $has_write_access = $this->zacl->check_acl($provider->getId(), 'write','entity');
          if(!$has_write_access)
          {
              echo lang('error403');
              return;
          }
          $options = array();
          $encodedentity = base64url_encode($provider->getEntityId()); 
          $sourceurl = base_url().'metadata/circle/'.$encodedentity.'/metadata.xml';
          $options[] = array('src'=>''.$sourceurl.'','type'=>'provider','encname'=>''.$encodedentity.'');
          foreach($options as $opt)
          {
              $result = $client->doBackground('metadatasigner',''.json_encode($opt).'' );
          }
          echo lang('taskssent'); 
       }
        
   }

}
