<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Disco extends MY_Controller
{

    protected $logoUrl, $logoBasePath, $logoBaseUrl, $wayfList;

    public function __construct() {
        parent::__construct();
        $this->logoBasePath = $this->config->item('rr_logouriprefix');
        $this->logoBaseUrl = $this->config->item('rr_logobaseurl');
        if ($this->logoBaseUrl === null) {
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
    private function providerToDisco(models\Provider $ent, $type) {
        $result['entityID'] = $ent->getEntityId();
        $result['title'] = $ent->getNameToWebInLang('en');
        $doFilter = array('t' => array('' . $type . ''), 'n' => array('mdui'), 'e' => array('GeolocationHint', 'Logo'));
        /**
         * @var $extend models\ExtendMetadata[]
         */
        $extend = $ent->getExtendMetadata()->filter(
            function (models\ExtendMetadata $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter['t'], true) && in_array($entry->getNamespace(), $doFilter['n'], true) && in_array($entry->getElement(), $doFilter['e'], true);
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
    private function isFeatureEnabled() {
        $cnf = $this->config->item('featdisable');

        return (bool)!(is_array($cnf) && array_key_exists('discojuice', $cnf) && $cnf['discojuice'] === true);
    }

    private function getFullDiscoData() {
        $cachedDisco = $this->j_ncache->getFullDisco();
        if (empty($cachedDisco)) {
            $tmpProviders = new models\Providers;
            /**
             * @var $providersForWayf models\Provider[]
             */
            $providersForWayf = $tmpProviders->getAllIdPsForWayf();
            if (count($providersForWayf) === 0) {
                throw new Exception('No result');
            }
            $output = array();
            $icounter = 0;
            foreach ($providersForWayf as $ents) {
                $output[$icounter] = $this->providerToDisco($ents, 'idp');
                $icounter++;
            }
            $this->j_ncache->saveFullDisco($output);
            $result = $output;

        } else {
            $result = $cachedDisco;
        }

        return $result;


    }


    /**
     * @param null $filename
     * @return CI_Output
     */
    public function getall($filename = null) {
        if ($filename !== 'metadata.json') {
            return $this->output->set_status_header(403)->set_output('Request not allowed');
        }
        if (!$this->isFeatureEnabled()) {
            return $this->output->set_status_header(404)->set_output('The feature not enabled');
        }

        try {
            $result = $this->getFullDiscoData();
        } catch (Exception $e) {
            return $this->output->set_status_header(403)->set_output($e->getMessage());
        }

        $result = json_encode($result);
        $callback = $this->input->get('callback');
        if ($callback !== null && $this->isCallbackValid($callback)) {
            return $this->output->set_content_type('application/javascript')->set_output('' . $callback . '(' . $result . ')');

        }

        return $this->output->set_content_type('application/json')->set_output($result);
    }

    /**
     * @param $entityId
     * @param null $filename
     * @return CI_Output
     */
    public function circle($entityId, $filename = null) {

        if ($filename !== 'metadata.json') {
            return $this->output->set_status_header(403)->set_output('Request not allowed');
        }
        if (!$this->isFeatureEnabled()) {
            return $this->output->set_status_header(404)->set_output('The feature not enabled');
        }
        $decodedEntityId = base64url_decode($entityId);
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('entityid' => $decodedEntityId, 'type' => array('SP', 'BOTH')));
        if ($ent === null) {
            log_message('warning', 'Failed generating json  for provided entity:' . $decodedEntityId);
            return $this->output->set_status_header(404)->set_output('Unknown serivce provider');
        }

        $result = $this->j_ncache->getCircleDisco($ent->getId());
        if (empty($result)) {
            $overwayf = $ent->getWayfList();
            $white = false;
            $black = false;

            if(is_array($overwayf)){
                if (array_key_exists('white', $overwayf) && count($overwayf['white']) > 0) {
                    $white = true;
                    $this->wayfList = $overwayf['white'];
                }
                if(array_key_exists('black',$overwayf) && count($overwayf['black']) > 0){
                    $black = true;
                }
            }


            $tmpProviders = new models\Providers;
            /**
             * @var $providersForWayf models\Provider[]
             */
            $providersForWayf = $tmpProviders->getIdPsForWayf($ent);
            if (count($providersForWayf) === 0) {
                return $this->output->set_status_header(404)->set_output('no result');
            }
            $output = array();
            $icounter = 0;
            foreach ($providersForWayf as $ents) {
                $allowed = true;
                $entityToCheck = $ents->getEntityId();
                if ($white && !in_array($entityToCheck, $this->wayfList, true)) {
                    $allowed = false;
                }
                if($black && in_array($entityToCheck,$overwayf['black'], true)){
                    $allowed = false;
                }
                if ($allowed) {

                    $output[$icounter] = $this->providerToDisco($ents, 'idp');
                    $icounter++;
                }
            }
            $result = json_encode($output, JSON_UNESCAPED_SLASHES);
            $this->j_ncache->saveCircleDisco($ent->getId(), $result);
        }
        $callback = $this->input->get('callback');
        if ($callback !== null && $this->isCallbackValid($callback)) {
            return $this->output->set_content_type('application/javascript')->set_output('' . $callback . '(' . $result . ')');

        }
        return $this->output->set_content_type('application/json')->set_output($result);
    }


    public function requester($encodedEntity = null) {
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
        $output = json_encode($result);
        $callback = $this->input->get('callback');
        if ($callback !== null && $this->isCallbackValid($callback)) {
            return $this->output->set_content_type('application/javascript')->set_output('' . $callback . '(' . $output . ')');

        }

        return $this->output->set_content_type('application/json')->set_output($output);

    }

    /**
     * @param $str
     * @return bool
     */
    private function isCallbackValid($str) {
        return (bool)preg_match('/^[a-z0-9$_]+$/i', $str);
    }

}
