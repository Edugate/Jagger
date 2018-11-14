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
    protected $metadataNS;
    public function __construct() {
        parent::__construct();
        $this->metadataNS = h_metadataNamespaces();
        $additionalNs = $this->config->item('metadatans');
        if (is_array($additionalNs)) {
            $this->metadataNS = array_merge($this->metadataNS, $additionalNs);
        }
        $this->load->library(array('mdqsigner','providertoxml'));
    }

    /**
     * @return bool
     */
    private function isFeatureEnabled() {
        $isEnabled = $this->config->item('mdq') ?: false;

        return $isEnabled;
    }


    /**
     * @param \models\Provider $entity
     * @return CI_Output
     */
    private function genMetada(\models\Provider $entity) {
        if (null === $entity) {
            return $this->output->set_status_header(404)->set_output("Not found");
        }
        $entityInSha = sha1($entity->getEntityId());
        $this->load->library(array('providertoxml'));
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
            try {
                $this->mdqsigner->storeMetadada($entityInSha, $signeMetadata);
            } catch (Exception $e) {
                log_message('ERROR', __METHOD__ . ' ' . $e);
            }

            return $this->output->set_content_type('application/samlmetadata+xml')->set_output($signeMetadata);


        }

        return $this->output->set_status_header(404)->set_output("Not found");

    }


    private function trustgraph() {
        $this->load->library(array('j_ncache', 'trustgraph'));
        $cached = $this->j_ncache->getTrustGraph();
        if (is_array($cached)) {
            return $cached;
        }
        $result = $this->trustgraph->getTrustGraphLight();
        $this->j_ncache->saveTrustGraph($result);
        return $result;
    }

    public function trustgraphjson() {

        $result = $this->trustgraph();

        return $this->output->set_content_type('application/json')->set_output(json_encode(array('providers' => $result)));

    }

    private function validForInSecs($days) {
        return (86400 * $days);
    }

    private function getmdq($entityid) {


        $metadata = $this->mdqsigner->getStoredMetadata(sha1($entityid));
        if (null !== $metadata) {
            $now = new \DateTime('now', new \DateTimezone('UTC'));
            $diff = (int)$now->format('U') - (int)$metadata['modified'];
            if ($diff < $this->validForInSecs(2)) {
                return $this->output->set_content_type('application/samlmetadata+xml')->set_output($metadata['metadata']);
            }
        }

        try {
            $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));
        } catch (Exception $e) {
            log_message('ERROR', __METHOD__ . ': ' . $e);

            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

        $this->genMetada($entity);
    }

    public function circle($hash = null, $arg1 = null, $arg2 = null, $arg3 = null) {

        if (!$this->isFeatureEnabled()) {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Page not found: MDQ is not enabled');
        }
        $mpaths = $this->mdqsigner->getMdqStoragePaths();
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


            return $this->output->set_status_header(301)->set_header('Location: ' . base_url($mpaths['entity'] . $arg1 . '/metadata.xml') . '');
        }
        /**
         *
         */

        if ($isSHA1) {
            $entityidInSHA = $arg3;
        } else {
            $seg3Decoded = urldecode($arg3);

            $entityidInSHA = sha1($seg3Decoded);
        }

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


    private function regenerateStatic(models\Provider $entity) {

        $standardNS = h_metadataNamespaces();

        $xmlOut = $this->providertoxml->createXMLDocument();
        $this->providertoxml->entityStaticConvert($xmlOut, $entity);
        $xmlOut->endDocument();


        $outPut = $xmlOut->outputMemory();
        $domXML = new DOMDocument();

        $domXML->loadXML($outPut, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($domXML);
        $xpath->registerNamespace('', 'urn:oasis:names:tc:SAML:2.0:metadata');
        foreach ($standardNS as $key => $val) {
            $xpath->registerNamespace('' . $key . '', '' . $val . '');
        }
        /**
         * @var \DOMElement $element
         */
        $element = $domXML->getElementsByTagName('EntityDescriptor')->item(0);
        if ($element === null) {
            $element = $domXML->getElementsByTagName('md:EntityDescriptor')->item(0);
        }
        if ($element !== null) {
            $element->setAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
            foreach ($standardNS as $key => $val) {
                $element->setAttribute('xmlns:' . $key . '', '' . $val . '');
            }
        }
        $out = $domXML->saveXML();

        return $out;
    }

    /**
     * @todo finish
     * @param null $seg1
     * @param null $seg2
     * @param null $seg3
     * @return CI_Output
     */
    private function federation($seg1 = null, $seg2 = null, $seg3 = null) {

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