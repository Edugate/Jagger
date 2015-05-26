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
 * Metadatalocations Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadatalocations extends MY_Controller {
    function __construct() {
        parent::__construct();
    }
    function index()
    {
       $this->load->library('table');

        $myLang = MY_Controller::getLang();
        $providerPrefixUrl = base_url('signedmetadata/provider') ;
        $federationPrefixUrl = base_url('signedmetadata/federation') ;
        $tmpProviders = new models\Providers;
        $tmpFederations = new models\Federations;
        $feds = $tmpFederations->getFederations();

        /**
         * @var $sps models\Provider[]
         * @var $idps models\Provider[]
         */
        $sps = $tmpProviders->getPublicSps_inNative();
        $idps = $tmpProviders->getPublicIdps_inNative();
        $farray = array();
        foreach($feds as $fed)
        {
            $farray[] = array('<a href="'.$federationPrefixUrl.'/'. $fed->getSysname().'/metadata.xml">signed metadata</a>',
                              '<span title="'.$fed->getName().'">'.$fed->getName().'</span>',$fed->getUrn());
        }
        $tarray = array();
        foreach($idps as $idp)
        {
            $tarray[] = array('<a href="'.$providerPrefixUrl.'/'. base64url_encode($idp->getEntityId()).'/metadata.xml">signed metadata</a>', '<span title="'.$idp->getNameToWebInLang($myLang,'idp').'">'.$idp->getDisplayName(40).'</span>',$idp->getEntityId());
        }

        $sarray = array();
        foreach($sps as $sp)
        {
            $sarray[] = array('<a href="'.$providerPrefixUrl.'/'. base64url_encode($sp->getEntityId()).'/metadata.xml">signed metadata</a>', '<span title="'.$sp->getNameToWebInLang($myLang,'sp').'">'.$sp->getDisplayName(40).'<span>',$sp->getEntityId());
        }
        $data['farray'] = &$farray;
        $data['tarray'] = &$tarray;
        $data['sarray'] = &$sarray;
        $data['content_view'] = 'metadatalocations_view';
        $this->load->view('page',$data);

    }
}
