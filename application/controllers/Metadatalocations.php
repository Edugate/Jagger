<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
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
 * Metadatalocations Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadatalocations extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }
    public function index()
    {
       $this->load->library('table');

        $myLang = MY_Controller::getLang();
        $providerPrefixUrl = base_url('signedmetadata/provider') ;
        $federationPrefixUrl = base_url('signedmetadata/federation') ;
        $servicePrefixUrl = base_url('metadata/service');
        $tmpProviders = new models\Providers;
        $tmpFederations = new models\Federations;
        $feds = $tmpFederations->getFederations();

        /**
         * @var $sps models\Provider[]
         * @var $idps models\Provider[]
         */
        $sps = $tmpProviders->getPublicSpsInNative();
        $idps = $tmpProviders->getPublicIdpsInNative();
        $farray = array();
        foreach($feds as $fed)
        {
            $farray[] = array('<span title="'.$fed->getName().'">'.html_escape($fed->getName()).'</span>',$fed->getUrn(),'<a target="_blank" href="'.$federationPrefixUrl.'/'. $fed->getSysname().'/metadata.xml" class="button tiny">signed metadata</a>');
        }
        $tarray = array();
        foreach($idps as $idp)
        {
            $nameInLang = $idp->getNameToWebInLang($myLang,'sp');
            $tarray[] = array('<span title="'.$nameInLang.'">'.$nameInLang.'</span>',html_escape($idp->getEntityId()),'<a target="_blank" href="'.$providerPrefixUrl.'/'. base64url_encode($idp->getEntityId()).'/metadata.xml" class="button tiny">cot</a>','<a target="_blank" href="'.$servicePrefixUrl.'/'. base64url_encode($idp->getEntityId()).'/metadata.xml" class="button tiny">entity</a>');
        }

        $sarray = array();
        foreach($sps as $sp)
        {
            $nameInLang = $sp->getNameToWebInLang($myLang,'sp');
            $sarray[] = array( '<span title="'.$nameInLang.'">'.$nameInLang.'<span>',html_escape($sp->getEntityId()),'<a target="_blank" href="'.$providerPrefixUrl.'/'. base64url_encode($sp->getEntityId()).'/metadata.xml" class="button tiny">cot</a>','<a target="_blank" href="'.$servicePrefixUrl.'/'. base64url_encode($sp->getEntityId()).'/metadata.xml" class="button tiny">entity</a>');
        }
        $data = array(
            'farray' => $farray,
            'tarray'=> $tarray,
            'sarray' => $sarray,
            'content_view' => 'metadatalocations_view'
        );
        $this->load->view(MY_Controller::$page,$data);

    }
}
