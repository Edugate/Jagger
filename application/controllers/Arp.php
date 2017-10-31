<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet Ltd.
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Arp extends MY_Controller
{

    protected $resourcetype;
    protected $subtype;

    public function __construct() {
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
    private function generateXml($idp) {
        $returnArray = FALSE;
        $result1 = $this->arp_generator->arpToXML($idp, $returnArray);
        $result = null;
        if (!empty($result1)) {
            $result = $result1->saveXML();
        }
        return $result;
    }


    private function arpexperimental($encodedEntity, $version, $filename = null) {
        if ($filename !== 'arp.xml') {
            return $this->output->set_content_type('text/html')->set_status_header(403)->set_output('Request not allowed');
        }
        $entityID = base64_decode($encodedEntity);
        /**
         * @var models\Provider $ent
         */
        try {
            $ent = $this->em->getRepository('models\Provider')->findOneBy(array('entityid' => $entityID, 'type' => array('IDP', 'BOTH')));
        } catch (Exception $e) {
            log_message('error', $e);
            return $this->output->set_content_type('text/html')->set_status_header(500)->set_output('Internal server error');
        }
        if ($ent === null) {
            log_message('error', 'IdP not found with id:.' . $entityID);
            show_error("Identity Provider not found", 404);
        }
        try {
            $this->load->library('arpgen');
        } catch (Exception $e) {
            log_message('error', $e);
            return $this->output->set_content_type('text/html')->set_status_header(500)->set_output('Internal server error');
        }
        if($version === 3){
            $rxmldata =$this->arpgen->genXMLv3($ent);

        }
        else {
            $rxmldata = $this->arpgen->genXML($ent);

        }
        $xml = $rxmldata['xml'];
        $dataCreated = $rxmldata['created'];
        $result = $xml->outputMemory();
        $jate = new DateTime();
        $jate->setTimestamp($dataCreated);
        $this->trackRequest($ent->getEntityId());
        return $this->output->set_content_type('text/xml')->set_header('Last-Modified: '.$jate->format('D, d M Y H:i:s').' GMT')->set_output($result);

    }

    public function format2exp($encodedEntity, $filename = null) {
        $this->arpexperimental($encodedEntity, 2, $filename);
    }


    public function format3exp($encodedEntity, $filename = null) {

        $this->arpexperimental($encodedEntity, 3, $filename);
    }


    /**
     *
     * @param string $idpEntityID
     * @param string $filename
     * @return string
     */
    public function format2($idpEntityID, $filename = null) {
        if ($filename !== 'arp.xml') {
            show_error('Request not allowed', 403);
        }
        $data = array();
        $tmpProviders = new models\Providers;

        /**
         * @var $idp models\Provider
         */
        try {
            $idp = $tmpProviders->getOneIdpByEntityId(base64url_decode($idpEntityID));
        } catch (Exception $e) {
            $this->output->set_content_type('text/html');
            log_message('error', $e);
            return $this->output->set_content_type('text/html')->set_status_header(500)->set_output('Internal server error');
        }
        if ($idp === null) {
            log_message('debug', 'IdP not found with id:.' . $idpEntityID);
            return $this->output->set_content_type('text/html')->set_status_header(404)->set_output('Identity Provider Not Found');
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
            $lastUpdated = time();
            $this->output->set_header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastUpdated).' GMT');
            $this->load->view('metadata_view', $data);
            log_message('info', __METHOD__ . ' ARP for ' . $entityid . ' :: Downloaded......');
            $this->trackRequest($entityid);
        } else {
            log_message('warning', __METHOD__ . ' ARP for ' . $entityid . ' cannot be generated because no policy had been set');
            show_error('ARP cannot be generated because no policy had been set', 404);
        }
    }


    private function trackRequest($resourcename) {
        $ref = $this->input->server('HTTP_REFERER');
        $reqtype = $this->input->server('REQUEST_METHOD');
        $reftomatch = base_url('reports/sp_matrix/show');
        if ($ref !== null) {
            log_message('debug', 'REFERER: ' . $ref);

        }


        if (($reqtype == 'GET') && ((!empty($ref) && stristr($ref, $reftomatch) === FALSE) || (empty($ref)))) {
            $syncWithDB = true;
            $details = null;
            try {
                $this->tracker->save_track($this->resourcetype, $this->subtype, $resourcename, $details, $syncWithDB);
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
            }
        }
    }
}
