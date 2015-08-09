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
 * Disco Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Disco extends MY_Controller
{

    protected $logoUrl, $logoBasePath, $logoBaseUrl, $wayfList;

    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
        $this->logoBasePath = $this->config->item('rr_logouriprefix');
        $this->logoBaseUrl = $this->config->item('rr_logobaseurl');
        if (empty($this->logoBaseUrl)) {
            $this->logoBaseUrl = base_url();
        }
        $this->logoUrl = $this->logoBaseUrl . $this->logoBasePath;
        $this->wayfList = array();
        $this->output->set_header('X-Frame-Options: SAMEORIGIN');
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }


    /**
     * @param \models\Provider $ent
     * @param $type
     * @return mixed
     */
    private function providerToDisco(models\Provider $ent, $type)
    {
        $result['entityID'] = $ent->getEntityId();
        $result['title'] = $ent->getNameToWebInLang('en');
        $doFilter = array('t' => array('' . $type . ''), 'n' => array('mdui'), 'e' => array('GeolocationHint', 'Logo'));
        /**
         * @var $extend models\ExtendMetadata[]
         */
        $extend = $ent->getExtendMetadata()->filter(
            function (models\ExtendMetadata $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter['t']) && in_array($entry->getNamespace(), $doFilter['n']) && in_array($entry->getElement(), $doFilter['e']);
            });
        $logoSet = false;
        $geoSet = false;
        foreach ($extend as $ex) {
            $eElement = $ex->getElement();
            if ($eElement === 'GeolocationHint') {
                if ($geoSet === true) {
                    continue;
                }
                $eValue = explode(',', $ex->getEvalue());
                $result['geo'] = array('lat' => $eValue[0], 'lon' => $eValue[1]);
                $geoSet = true;
            } elseif ($eElement === 'Logo') {
                if ($logoSet === true) {
                    continue;
                }
                if (!(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $ex->getEvalue(), $matches))) {
                    $elementValue = $this->logoUrl . $ex->getEvalue();
                } else {
                    $elementValue = $ex->getEvalue();
                }

                $result['icon'] = $elementValue;
                $logoSet = true;
            }
        }

        return $result;
    }

    public function circle($entityId, $filename = NULL)
    {

        if ($filename !== 'metadata.json') {
            set_status_header(403);
            echo 'Request not allowed';
            return;
        }
        $cnf = $this->config->item('featdisable');
        if (isset($cnf['discojuice']) && $cnf['discojuice'] === true) {
            set_status_header(404);
            echo 'The feature no enabled';
            return;
        }
        $call = $this->input->get('callback');
        $callArray = array();
        if ($call !== null) {
            $callArray = explode('_', $call);
        }
        $data = array();
        $decodedEntityId = base64url_decode($entityId);
        $tmp = new models\Providers;
        /**
         * @var $ent models\Provider
         */
        $ent = $tmp->getOneSpByEntityId($decodedEntityId);
        if ($ent === null) {
            log_message('error', 'Failed generating json  for provided entity:' . $decodedEntityId);
            set_status_header(404);
            echo 'Unknown serivce provider';
            return;

        }
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));
        $cacheid = 'disco_' . $ent->getId();
        $cachedDisco = $this->cache->get($cacheid);
        if (empty($cachedDisco)) {
            log_message('debug', 'Cache: discojuice for entity:' . $ent->getId() . ' with cacheid ' . $cacheid . ' not found in cache, generating...');
            $overwayf = $ent->getWayfList();
            $white = false;

            if (is_array($overwayf) && array_key_exists('white', $overwayf) && count($overwayf['white']) > 0) {
                $white = true;
                $this->wayfList = $overwayf['white'];
            }
            $tmpProviders = new models\Providers;
            /**
             * @var $providersForWayf models\Provider[]
             */
            $providersForWayf = $tmpProviders->getIdPsForWayf($ent);
            if (empty($providersForWayf)) {
                set_status_header(404);
                echo 'no result';
                return;
            }
            $output = array();
            $icounter = 0;
            foreach ($providersForWayf as $ents) {
                $allowed = true;
                if ($white && !in_array($ents->getEntityId(), $this->wayfList)) {
                    $allowed = false;
                }
                if ($allowed) {

                    $output[$icounter] = $this->providerToDisco($ents, 'idp');
                    $icounter++;
                }
            }
            $jsonoutput = json_encode($output);
            $this->cache->save($cacheid, $jsonoutput, 600);

            if ( count($callArray) == 3 && $callArray['0'] == 'dj' && $callArray['1'] == 'md' && is_numeric($callArray['2'])) {
                $data['result'] = $call . '(' . $jsonoutput . ')';
            } else {
                $data['result'] = $jsonoutput;
            }

        } else {
            log_message('debug', 'Cache: Discojoice for entity ' . $ent->getId() . ' found in cache id:' . $cacheid . ', retrieving...');

            if (!empty($callArray) && is_array($callArray) && count($callArray) == 3 && $callArray['0'] == 'dj' && $callArray['1'] == 'md' && is_numeric($callArray['2'])) {
                $data['result'] = $call . '(' . $cachedDisco . ')';
            } else {
                $data['result'] = $cachedDisco;
            }
        }
        $this->load->view('disco_view', $data);
    }


    public function requester($encodedEntity = null)
    {
        if (empty($encodedEntity)) {
            set_status_header(404);
            echo 'entityID not provided';
            return;
        }
        $entityid = base64url_decode($encodedEntity);
        $tmp = new models\Providers;
        /**
         * @var $ent models\Provider
         */
        $ent = $tmp->getOneSpByEntityId($entityid);
        if ($ent === null) {
            set_status_header(404);
            echo 'Unknown serivce provider';
            return;
        }
        $result = $this->providerToDisco($ent, 'sp');
        $jsonoutput = json_encode($result);
        $data['result'] = $jsonoutput;
        $this->load->view('disco_view', $data);
    }

}
