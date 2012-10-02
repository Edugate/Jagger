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

        $providerprefix = base_url() . "signedmetadata/provider/"; 
        $tmp_providers = new models\Providers;
        $onlylocals = TRUE;
        $idps = $tmp_providers->getSps_inNative($onlylocals);
        $sps = $tmp_providers->getIdps_inNative($onlylocals);
        $tarray = array();
        foreach($idps as $idp)
        {
            $tarray[] = array('<a href="'.$providerprefix. base64url_encode($idp->getEntityId()).'/metadata.xml">signed metadata</a>', '<span title="'.$idp->getDisplayName().'">'.$idp->getDisplayName(40).'</span>',$idp->getEntityId());
        }

        $sarray = array();
        foreach($sps as $sp)
        {
            $sarray[] = array('<a href="'.$providerprefix. base64url_encode($sp->getEntityId()).'/metadata.xml">signed metadata</a>', '<span title="'.$sp->getDisplayName().'">'.$sp->getDisplayName(40).'<span>',$sp->getEntityId());
        }
       
        $data['tarray'] = $tarray;
        $data['sarray'] = $sarray;
        $data['content_view'] = 'metadatalocations_view';
        if(!empty($this->config->item('metadatasigner_url')))
        {
           $data['metadatasigner_url'] = $this->config->item('metadatasigner_url');
        }
        $this->load->view('page',$data);

    }
}
