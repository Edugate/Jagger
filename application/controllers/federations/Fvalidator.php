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
 * Fvalidator Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * @todo add permission to check for public or private perms
 */
class Fvalidator extends MY_Controller {
     
    function __construct()
    {
         parent::__construct();
    }

    public function detail($fedid,$validatorid)
    {
       $loggedin = $this->j_auth->logged_in();
       if(!$loggedin)
       {
          redirect('auth/login', 'location');
       } 

    }


    private function _submit_validate()
    {
         /**
          * @todo add validation
          */
         return TRUE;
    }    
    public function validate($providerid,$fvalidatorid)
    {
        $loggedin = $this->j_auth->logged_in();
        $is_ajax = $this->input->is_ajax_request();
        if(!($loggedin && $is_ajax))
        {
            show_error('not authenticated',403);
        }
        if(!$this->_submit_validate())
        {
            show_error('incorrect/missing paramters  passed',403);
        }
        
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$providerid));
        $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id'=>$fvalidatorid));         
        if(!($provider && $fvalidator))
        {
            show_error('not found ',404);
        }
       
        $providerMetadataUrl = base_url().'/metadata/service/'.base64url_encode($provider->getEntityId()) . '/metadata.xml';
        $method = $fvalidator->getMethod();
        $remoteUrl = $fvalidator->getUrl();
        $entityParam = $fvalidator->getEntityParam();
        $optArgs = $fvalidator->getOptargs();
        $params = array();
        if(strcmp($method,'GET')==0)
        {
            $separator= $fvalidator->getSeparator();
            $optArgsStr = '';
            foreach ($optArgs as $k=>$v)
            {
                if($v === null)
                {
                    $optArgsStr .=$k.$separator;
                }
                else
                {
                    $optArgsStr .= $k.'='.$v.''.$separator;
                }
            }
            $optArgsStr .=$entityParam.'='.urlencode($providerMetadataUrl);
            $remoteUrl = $remoteUrl.$optArgsStr;
            $this->curl->create(''.$remoteUrl.'');
        }
        else
        {
            $params = $optArgs;
            $params[''.$entityParam.''] = $providerMetadataUrl;	
            $this->curl->create(''.$remoteUrl.'');
            $this->curl->post($params);
        }

        $addoptions = array();
        $this->curl->options($addoptions);
        $data = $this->curl->execute();
   //      echo '<pre>';
   //      echo $remoteUrl;
   //     print_r($data);
   //     echo '</pre>';
    }



}



