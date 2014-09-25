<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet Ltd.
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Arp Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Arp extends MY_Controller
{

    protected $resourcetype;
    protected $subtype;

    function __construct()
    {
        parent::__construct();
        $this->load->library('arp_generator');
        $this->resourcetype = 'idp';
        $this->subtype = 'arp_download';
        $this->output->set_content_type('text/xml');
    }

    /**
     *
     * @param models\Provider $idp
     * @return string|null
     */
    private function generateXml($idp, $inherit=FALSE)
    {
        if($inherit)
        {
           $result1 = $this->arp_generator->arpToXML($idp,FALSE,TRUE);
        }
        else
        {
           $result1 = $this->arp_generator->arpToXML($idp,FALSE,FALSE);
        }
        if (!empty($result1)) {
            $result = $result1->saveXML();
        }
        else {

            $result = null;
        }
        return $result;
    }

    /**
     *
     * @param string $idp_entityid
     * @param string $m
     * @return string 
     */
    public function format2($idp_entityid, $m = null)
    {
        if (!empty($m) && $m != 'arp.xml') {
            show_error('Request not allowed', 403);
        }
        $data = array();
        $tmp_idp = new models\Providers;
        $idp = new models\Provider;
        $idp = $tmp_idp->getOneIdpByEntityId(base64url_decode($idp_entityid));
        if (empty($idp)) {
            log_message('debug', 'IdP not found with id:.' . $idp_entityid);
            show_error("Identity Provider not found", 404);
        }
        $entityid = $idp->getEntityId();
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));

        $inherit = $this->config->item('arpbyinherit');
       
        if(empty($inherit))
        { 
           $cacheid = 'arp_' . $idp->getId();
        }
        else
        {
           $cacheid = 'arp2_' . $idp->getId();
        }

        $arpcached = $this->cache->get($cacheid);
        if (empty($arpcached)) {
            log_message('debug', 'not found in memcache');
            if(empty($inherit))
            {
               $data['out'] = $this->generateXml($idp,FALSE);
            }
            else
            {
               $data['out'] = $this->generateXml($idp,TRUE);
            }
            if (!empty($data['out'])) {
                $this->cache->save($cacheid, $data['out'], 120);
            }
        }
        else {
            log_message('debug', 'got from memcache');
            $data['out'] = $arpcached;
        }
        if (!empty($data['out'])) {
            $this->load->view('metadata_view', $data);
            log_message('info', 'Downloaded......');
            $this->trackRequest($entityid);
        }
        else {
            show_error('ARP cannot be generated because no policy had been set', 404);
        }
    }


    private function trackRequest($resourcename)
    {
            $ref = null;
            $reqtype = null;
            if (isset($_SERVER['HTTP_REFERER'])) {
                $ref = $_SERVER['HTTP_REFERER'];
                log_message('debug', 'REFERER:' . $ref);
            }
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $reqtype = $_SERVER['REQUEST_METHOD'];
            }
            $reftomatch = base_url() . 'reports/sp_matrix/show';
            if ((!empty($reqtype) && $reqtype == 'GET') && ((!empty($ref) && stristr($ref, $reftomatch) === FALSE) || (empty($ref)))) {
                log_message('debug', 'Arp downloading set1');
                $sync_with_db = true;
                $details = null;
                $this->tracker->save_track($this->resourcetype, $this->subtype, $resourcename, $details, $sync_with_db);
            }
          

    }

    /**
     *
     * @param string $idp_entityid
     * @param string $m
     * @return string 
     */
    public function devdefault($idp_entityid, $m = null)
    {
        $allowed = $this->config->item('arpdevshow');
        if(empty($allowed))
        {
            show_error('Request not allowed', 403);
        }
        if (!empty($m) && $m != 'arp.xml') {
            show_error('Request not allowed', 403);
        }
        $data = array();
        $tmp_idp = new models\Providers;
        $idp = new models\Provider;
        $idp = $tmp_idp->getOneIdpByEntityId(base64url_decode($idp_entityid));
        if (empty($idp)) {
            log_message('debug', 'IdP not found with id:.' . $idp_entityid);
            show_error("Identity Provider not found", 404);
        }
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));
        $cacheid = 'arp_' . $idp->getId();

        $arpcached = $this->cache->get($cacheid);
        if (empty($arpcached)) {
            log_message('debug', 'not found in memcache');
            $data['out'] = $this->generateXml($idp,FALSE);
            if (!empty($data['out'])) {
                $this->cache->save($cacheid, $data['out'], 120);
            }
        }
        else {
            log_message('debug', 'got from memcache');
            $data['out'] = $arpcached;
        }
        if (!empty($data['out'])) {
            $this->load->view('metadata_view', $data);
        }
        else {
            show_error('ARP cannot be generated because no policy had been set', 404);
        }
    }
    /**
     *
     * @param string $idp_entityid
     * @param string $m
     * @return string 
     */
    public function devinherit($idp_entityid, $m = null)
    {
        $allowed = $this->config->item('arpdevshow');
        if(empty($allowed))
        {
            show_error('Request not allowed', 403);
        }
        if (!empty($m) && $m != 'arp.xml') {
            show_error('Request not allowed', 403);
        }
        $data = array();
        $tmp_idp = new models\Providers;
        $idp = new models\Provider;
        $idp = $tmp_idp->getOneIdpByEntityId(base64url_decode($idp_entityid));
        if (empty($idp)) {
            log_message('debug', 'IdP not found with id:.' . $idp_entityid);
            show_error("Identity Provider not found", 404);
        }
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));
        $cacheid = 'arp2_' . $idp->getId();

        $arpcached = $this->cache->get($cacheid);
        if (empty($arpcached)) {
            log_message('debug', 'not found in memcache');
            $data['out'] = $this->generateXml($idp,TRUE);
            if (!empty($data['out'])) {
                $this->cache->save($cacheid, $data['out'], 120);
            }
        }
        else {
            log_message('debug', 'got from memcache');
            $data['out'] = $arpcached;
        }
        if (!empty($data['out'])) {
            $this->load->view('metadata_view', $data);
        }
        else {
            show_error('ARP cannot be generated because no policy had been set', 404);
        }
    }

}
