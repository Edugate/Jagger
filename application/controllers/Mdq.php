<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2018 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Mdq extends MY_Controller
{
    public function __construct() {
        parent::__construct();
    }


    /**
     * @param \models\Provider $entity
     * @return CI_Output
     */
    private function    genMetada(\models\Provider $entity) {
        if (null === $entity) {
            return $this->output->set_status_header(404)->set_output("Not found");
        }
        $entityInSha = sha1($entity->getEntityId());
        $this->load->library('providertoxml');
        $this->load->library('mdqsigner');
        $options['attrs'] = 1;
        $isStatic = $entity->isStaticMetadata();
        $unsignedMetadata = null;
        /**
         * @var \XMLWriter $xmlOut
         */
        if ($isStatic) {

            $unsignedMetadata = $this->regenerateStatic($entity);
            $mem = memory_get_usage();
            $mem = round($mem / 1048576, 2);
            log_message('info', 'Memory usage: ' . $mem . 'M');
            //   $this->load->view('metadata_view', $data);
        } else {
            $xmlOut = $this->providertoxml->entityConvertNewDocument($entity, $options);
            if (!empty($xmlOut)) {
                $unsignedMetadata = $xmlOut->outputMemory();
                $mem = memory_get_usage();
                $mem = round($mem / 1048576, 2);
                log_message('info', 'Memory usage: ' . $mem . 'M');
                //    $this->load->view('metadata_view', $data);
            } else {
                log_message('error', __METHOD__ . ' empty xml has been generated');
                show_error('Internal server error', 500);
            }
        }


        if ($unsignedMetadata !== null) {

            try {
                $signeMetadata = $this->mdqsigner->signXML($unsignedMetadata, null);
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);

                return $this->output->set_status_header(500)->set_output("Problem with signing");
            }
            $this->mdqsigner->storeMetadada($entityInSha, $signeMetadata);

            return $this->output->set_content_type('application/samlmetadata+xml')->set_output($signeMetadata);


        }

        return $this->output->set_status_header(404)->set_output("Not found");

    }


    private function trustgraph() {
        $this->load->library('j_ncache');
        $this->load->library('trustgraph');
        $cached = null;
        //$cached = $this->j_ncache->getTrustGraph();
        if ($cached) {
            return $cached;
        }

        $result = $this->trustgraph->genTrustLight();
        $this->j_ncache->saveTrustGraph($result);

        return $result;

    }

    public function trustgraphjson() {

        $result = $this->trustgraph();

        return $this->output->set_content_type('application/json')->set_output(json_encode(array('providers' => $result)));

    }

    private function getmdq($entityid) {


        $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));

        $this->genMetada($entity);
    }

    public function circle($hash = null, $arg1 = null, $arg2 = null, $arg3 = null) {
        $isSHA1 = false;

        if (strtolower($hash) === 'sha1') {
            $isSHA1 = true;
        } else if ($hash !== 'nohash') {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Page not found: incorrect hash type');
        }

        $encodedEntityID = trim($arg1);
        if ($encodedEntityID === '') {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Metadata not found: incorrect/missing encoded entityid');
        }
        if ($arg2 !== 'entities') {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Incorrect URL provided. Missing "entities" segment');
        }

        $entityID = base64url_decode($encodedEntityID);

        // echo $entityID.PHP_EOL;
        /**
         * @var $provider models\Provider
         */
        try {
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => '' . $entityID . ''));
        } catch (\Exception $e) {
            log_message('error', __METHOD__ . ' : ' . $e);

            return $this->output->set_status_header(500)->set_content_type('text/html')->set_output('Internal server error');
        }
        if (null === $provider) {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Metadata not found');
        }

        if (trim($arg3) === '') {

            return $this->output->set_status_header(301)->set_header('Location: ' . base_url('signedmetadata/provider/' . $arg1 . '/metadata.xml') . '');
        }
        /**
         *
         */

        $seg3Decoded = urldecode($arg3);
        
        $entityidInSHA = sha1($seg3Decoded);

        $trust = $this->trustgraph();
        $feds1 = array();

        if (array_key_exists(sha1($provider->getEntityId()), $trust)) {
            $feds1 = $trust['' . sha1($provider->getEntityId()) . '']['feds'];
        }
        $feds2 = array();
        if (array_key_exists($entityidInSHA, $trust)) {
            $feds2 = $trust['' . $entityidInSHA . '']['feds'];
        }

        $fedIntersection = array_intersect($feds1, $feds2);


        if (count($fedIntersection) === 0) {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Metadata not found ::: (no trust between ' . html_escape($entityID) . ' and ' . html_escape("{SHA1}" . $entityidInSHA) . ')');
        }


        $metadaEntityID = $trust[$entityidInSHA]['entityid'];
        $this->getmdq($metadaEntityID);

    }

    public function federation($seg1 = null, $seg2 = null, $seg3 = null) {

        if (trim($seg1) === '') {
            return $this->output->set_status_header(404)->set_output("Not found");
        }
        /**
         * @todo validate $seg1
         */

        if ($seg2 !== 'entities') {
            return $this->output->set_status_header(404)->set_output("Not found");
        }


        /**
         * @var models\Federation $federation
         */
        $federation = $this->em->getRepository('models\Federation')->findOneBy(array('sysname' => $seg1, 'is_active' => true));

        if (null === $federation) {
            return $this->output->set_status_header(404)->set_output("Federation not found");
        }

        $fedId = $federation->getId();

        if (trim($seg3) === '') {
            return $this->output->set_status_header(301)->set_header('Location: ' . base_url('signedmetadata/federation/' . $federation->getSysname() . '/metadata.xml') . '');
        }

        /**
         * @todo check if $seg1 is sha1
         */
        $seg3Decoded = urldecode($seg3);
        log_message('error', 'KOP urldecoded seg3: ' . $seg3Decoded);
        $isSha = false;
        if (strpos($seg3Decoded, '{sha1}') === 0) {
            log_message('error', 'KOP : isSha1 = true');
            $isSha = true;
        }


        if ($isSha) {
            $providerInSha = str_replace('{sha1}', '', $seg3Decoded);
        } else {
            $providerInSha = sha1($seg3);
        }

        $activeMembers = $federation->getActiveMembers();


        $tmpm = new models\Providers;
        /**
         * @var \models\Provider[] $members
         */
        $members = $tmpm->getActiveFederationMembers($federation, null);
        $memberBlock = array();
        foreach ($members as $m) {
            $entityID = $m->getEntityId();
            $memberBlock['' . sha1($entityID) . ''] = $entityID;
        }

        $providerToSearch = $providerInSha;
        if (array_key_exists($providerToSearch, $memberBlock)) {
            echo $memberBlock['' . $providerToSearch . ''];
        } else {
            echo "not found: " . $seg3 . PHP_EOL;
            $segs = $this->uri->segment_array();

            foreach ($segs as $segment) {
                echo $segment;
                echo '<br />';
            }
            echo '===============' . PHP_EOL;
            $segs = $this->uri->rsegment_array();

            foreach ($segs as $segment) {
                echo $segment;
                echo '<br />';
            }
        }


    }
}