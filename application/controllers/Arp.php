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
    private function generateXml($idp)
    {
        $returnArray = FALSE;
        $result1 = $this->arp_generator->arpToXML($idp, $returnArray);
        if (!empty($result1)) {
            $result = $result1->saveXML();
        } else {

            $result = null;
        }
        return $result;
    }


    private function arpexperimental($encodedEntity, $version, $m = null)
    {
        if (!empty($m) && $m != 'arp.xml') {
            show_error('Request not allowed', 403);
        }
        $entityID = base64_decode($encodedEntity);
        try {
            $ent = $this->em->getRepository('models\Provider')->findOneBy(array('entityid' => $entityID, 'type' => array('IDP', 'BOTH')));
        } catch (Exception $e) {
            $this->output->set_content_type('text/html');
            log_message('error', $e);
            set_status_header(500);
            echo 'Internal server error';
            return;
        }
        if (empty($ent)) {
            log_message('error', 'IdP not found with id:.' . $entityID);
            show_error("Identity Provider not found", 404);
        }
        try {
            $this->load->library('arpgen');
        } catch (Exception $e) {
            $this->output->set_content_type('text/html');
            log_message('error', $e);
            set_status_header(500);
            echo 'Internal server error';
            return;
        }
        $xml = $this->arpgen->genXML($ent,$version);
        $result = $xml->outputMemory();
        $this->output->set_content_type('text/xml')->set_output($result);

    }

    public function format2exp($encodedEntity, $m = null)
    {

        $this->arpexperimental($encodedEntity, 2, $m);

    }


    public function format3exp($encodedEntity, $m = null)
    {

        $this->arpexperimental($encodedEntity, 3, $m);
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

        /**
         * @var $idp models\Provider
         */
        try {
            $idp = $tmp_idp->getOneIdpByEntityId(base64url_decode($idp_entityid));
        } catch (Exception $e) {
            $this->output->set_content_type('text/html');
            log_message('error', $e);
            set_status_header(500);
            echo 'Internal server error';
            return;
        }
        if (empty($idp)) {
            log_message('debug', 'IdP not found with id:.' . $idp_entityid);
            show_error("Identity Provider not found", 404);
        }
        $entityid = $idp->getEntityId();
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));


        $cacheid = 'arp_' . $idp->getId();

        $arpcached = $this->cache->get($cacheid);
        if (empty($arpcached)) {
            log_message('debug', __METHOD__ . ' ARP for ' . $entityid . ' not found in memcache');
            $data['out'] = $this->generateXml($idp);
            if (!empty($data['out'])) {
                $this->cache->save($cacheid, $data['out'], 2400);
            }
        } else {
            log_message('debug', 'got from memcache');
            $data['out'] = $arpcached;
        }
        if (!empty($data['out'])) {
            $this->load->view('metadata_view', $data);
            log_message('info', __METHOD__ . ' ARP for ' . $entityid . ' :: Downloaded......');
            $this->trackRequest($entityid);
        } else {
            log_message('warning', __METHOD__ . ' ARP for ' . $entityid . ' cannot be generated because no policy had been set');
            show_error('ARP cannot be generated because no policy had been set', 404);
        }
    }


    private function trackRequest($resourcename)
    {
        $ref = null;
        $reqtype = null;
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];
            log_message('debug', 'REFERER: ' . $ref);
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $reqtype = $_SERVER['REQUEST_METHOD'];
        }
        $reftomatch = base_url() . 'reports/sp_matrix/show';
        if ((!empty($reqtype) && $reqtype == 'GET') && ((!empty($ref) && stristr($ref, $reftomatch) === FALSE) || (empty($ref)))) {
            $sync_with_db = true;
            $details = null;
            $this->tracker->save_track($this->resourcetype, $this->subtype, $resourcename, $details, $sync_with_db);
        }
    }
}
