<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * ProviderRemover Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class ProviderRemover {
   
    protected $ci;
    protected $em;


    function __construct()
    {
         $this->ci = &get_instance();
         $this->em = $this->ci->doctrine->em;
    }

    public function removeProvider(models\Provider $provider)
    {

       $aclresources = $this->em->getRepository("models\AclResource")->findBy(array('resource' => $provider->getId()));
       if (!empty($aclresources))
       {
           foreach ($aclresources as $a)
           {
                  $this->em->remove($a);
           }
       }
       $attreqtmp = new models\AttributeRequirements;
     
       $attrsrequests = $attreqtmp->getRequirementsBySP($provider);
       if(!empty($attrsrequests))
       {
           foreach($attrsrequests as $r)
           {
                $this->em->remove($r);
           }
       }
       $attrpoltmp = new models\AttributeReleasePolicies;
       $policies = $attrpoltmp->getAllPolicies($provider);
       if(!empty($policies))
       {
          foreach($policies as $p)
          {
             $this->em->remove($p);
          }
       }
       $policies2  = $attrpoltmp->getCustomSpPolicyAttributesRequester($provider);
       if(!empty($policies2))
       {
          foreach($policies2 as $p2)
          {
             $this->em->remove($p2);
          }
       }
       $policies3 = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
              'type'=>'sp',
              'requester'=>$provider->getId()));
       if(!empty($policies3))
       {
          foreach($policies3 as $p3)
          {
              $this->em->remove($p3);
          }
       }
       
       $cmstaticmetadata = $provider->getStaticMetadata();
       if (!empty($cmstaticmetadata))
       {
           $this->em->remove($cmstaticmetadata);
       }


       $this->em->remove($provider);
       return true;
    }
}
