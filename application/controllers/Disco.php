<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 *
 * @package   RR3
 * @author    Middleware Team HEAnet
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
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

                $result['icon'] = $ex->getLogoValue();
                $logoSet = true;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isFeatureEnabled()
    {
        $cnf = $this->config->item('featdisable');
        if (isset($cnf['discojuice']) && $cnf['discojuice'] === true) {
            return false;
        }
        return true;
    }


    public function getall($filename = null)
    {
        if ($filename !== 'metadata.json') {
            return $this->output->set_status_header(403)->set_output('Request not allowed');
        }
        if (!$this->isFeatureEnabled()) {
            return $this->output->set_status_header(404)->set_output('The feature not enabled');
        }
        $call = $this->input->get('callback');
        $callArray = array_filter(explode('_', $call));
        $inopaq = (count($callArray) == 3 && $callArray['0'] == 'dj' && $callArray['1'] == 'md' && is_numeric($callArray['2']));
        $cachedDisco = $this->j_ncache->getFullDisco();
        if (empty($cachedDisco)) {
            $tmpProviders = new models\Providers;
            /**
             * @var $providersForWayf models\Provider[]
             */
            $providersForWayf = $tmpProviders->getAllIdPsForWayf();
            if (empty($providersForWayf)) {
                return $this->output->set_status_header(404)->set_output('no result');
            }
            $output = array();
            $icounter = 0;
            foreach ($providersForWayf as $ents) {
                $output[$icounter] = $this->providerToDisco($ents, 'idp');
                $icounter++;
            }
            $jsonoutput = json_encode($output);
            $this->j_ncache->saveFullDisco($jsonoutput);
            if ($inopaq) {
                $data['result'] = $call . '(' . $jsonoutput . ')';
            } else {
                $data['result'] = $jsonoutput;
            }
        } else {
            if ($inopaq) {
                $data['result'] = $call . '(' . $cachedDisco . ')';
            } else {
                $data['result'] = $cachedDisco;
            }
        }
        $this->load->view('disco_view', $data);
    }

    /**
     * @param $entityId
     * @param null $filename
     * @return CI_Output
     */
    public function circle($entityId, $filename = null)
    {

        if ($filename !== 'metadata.json') {
            return $this->output->set_status_header(403)->set_output('Request not allowed');
        }
        if (!$this->isFeatureEnabled()) {
            return $this->output->set_status_header(404)->set_output('The feature not enabled');
        }
        $call = $this->input->get('callback');
        $callArray = array_filter(explode('_', $call));
        $inopaq = (count($callArray) == 3 && $callArray['0'] == 'dj' && $callArray['1'] == 'md' && is_numeric($callArray['2']));
        $data = array();
        $decodedEntityId = base64url_decode($entityId);
        $tmp = new models\Providers;
        /**
         * @var $ent models\Provider
         */
        $ent = $tmp->getOneSpByEntityId($decodedEntityId);
        if ($ent === null) {
            log_message('warning', 'Failed generating json  for provided entity:' . $decodedEntityId);
            return $this->output->set_status_header(404)->set_output('Unknown serivce provider');

        }

        $cachedDisco = $this->j_ncache->getCircleDisco($ent->getId());
        if (empty($cachedDisco)) {
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
                return $this->output->set_status_header(404)->set_output('no result');
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
            $this->j_ncache->saveCircleDisco($ent->getId(), $jsonoutput);
            if ($inopaq) {
                $data['result'] = $call . '(' . $jsonoutput . ')';
            } else {
                $data['result'] = $jsonoutput;
            }

        } else {
            if ($inopaq) {
                $data['result'] = $call . '(' . $cachedDisco . ')';
            } else {
                $data['result'] = $cachedDisco;
            }
        }
        $this->load->view('disco_view', $data);
    }


    public function requester($encodedEntity = null)
    {
        if ($encodedEntity === null) {
            return $this->output->set_status_header(404)->set_output('entityID not provided');
        }
        $entityid = base64url_decode($encodedEntity);
        $tmp = new models\Providers;
        /**
         * @var $ent models\Provider
         */
        $ent = $tmp->getOneSpByEntityId($entityid);
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Unknown serivce provider');
        }
        $result = $this->providerToDisco($ent, 'sp');
        $data['result'] = json_encode($result);
        $this->load->view('disco_view', $data);
    }

}
