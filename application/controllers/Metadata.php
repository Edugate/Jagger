<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2014 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */

class Metadata extends MY_Controller
{

    protected $metadataNS;

    public function __construct() {
        parent::__construct();
        $this->output->set_content_type('application/samlmetadata+xml');
        $this->load->library('j_ncache');
        $this->metadataNS = h_metadataNamespaces();
        $additionalNs = $this->config->item('metadatans');
        if(is_array($additionalNs)){
            $this->metadataNS = array_merge($this->metadataNS, $additionalNs);
        }
    }

    public function federation($federationName = null, $limitType = null) {
        if ($federationName === null) {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Not found');
        }

        $permitPull = $this->checkAccess();
        if ($permitPull !== true) {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());

            return $this->output->set_status_header(403)->set_content_type('text/html')->set_output('Access Denied');
        }

        $this->load->library('providertoxml');
        $data = array();
        $excludeType = null;
        $name = $federationName;

        if ($limitType === 'SP') {
            $excludeType = 'IDP';
        } elseif ($limitType === 'IDP') {
            $excludeType = 'SP';
        }

        /**
         * @var  models\Federation $federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $name, 'is_active' => true));

        if ($federation === null) {
            return $this->output->set_status_header(404)->set_content_type('text/html')->set_output('Federation not found or is inactive');
        }
        $publisher = $federation->getPublisher();
        $validfor = new \DateTime('now', new \DateTimezone('UTC'));
        $creationInstant = $validfor->format('Y-m-d\TH:i:s\Z');
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $entitiesDescriptorId = $federation->getDescriptorId();
        if (empty($entitiesDescriptorId)) {
            $idprefix = '';
            $prefid = $this->config->item('fedmetadataidprefix');
            if (!empty($prefid)) {
                $idprefix = $prefid;
            }
            $idsuffix = $validfor->format('YmdHis');
            $entitiesDescriptorId = $idprefix . $idsuffix;
        }
        $tmpAttrRequirements = new models\AttributeRequirements;
        $options = array(
            'attrs'       => 1,
            'fedreqattrs' => $tmpAttrRequirements->getRequirementsByFed($federation));

        $tmpm = new models\Providers;
        /**
         * @var \models\Provider[] $members
         */
        $members = $tmpm->getActiveFederationMembers($federation, $excludeType);

        $xmlOut = $this->providertoxml->createXMLDocument();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        // EntitiesDescriptor
        $xmlOut->startComment();
        $xmlOut->text('Metadata was generated on: ' . $now->format('Y-m-d H:i') . ' UTC' . PHP_EOL . 'TERMS OF USE' . PHP_EOL . $federation->getTou() . PHP_EOL);
        if ($excludeType !== null) {
            $xmlOut->text('Note: ' . $excludeType . ' type of entities have been exluded from the generated metadata' . PHP_EOL);
        }
        $xmlOut->endComment();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $entitiesDescriptorId . '');
        $xmlOut->writeAttribute('Name', $federation->getUrn());
        $xmlOut->writeAttribute('validUntil', $validuntil);
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        foreach ($this->metadataNS as $k => $v) {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }
        if (!empty($publisher)) {
            $xmlOut->startElementNs('md', 'Extensions', null);
            $xmlOut->startElementNs('mdrpi', 'PublicationInfo', null);
            $xmlOut->writeAttribute('creationInstant', $creationInstant);
            $xmlOut->writeAttribute('publisher', $publisher);
            $xmlOut->endElement(); // PublicationInfo
            $xmlOut->endElement(); // Extensions
        }
        foreach ($members as $k => $m) {
            $mtype = $m->getType();
            $xmlOut->startComment();
            $xmlOut->text(PHP_EOL . $m->getEntityId() . PHP_EOL);

            if (strcmp($mtype, 'IDP') == 0) {
                $metadataCached = $this->j_ncache->getMcircleMeta($m->getId());
                if (!empty($metadataCached)) {
                    $xmlOut->endComment();
                    $xmlOut->writeRaw($metadataCached);
                    unset($members[$k]);
                    continue;
                }
            }

            if ($m->isStaticMetadata()) {
                $xmlOut->text('static' . PHP_EOL);
                $xmlOut->endComment();
                $this->providertoxml->entityStaticConvert($xmlOut, $m);
            } else {
                $xmlOut->endComment();
                $this->providertoxml->entityConvert($xmlOut, $m, $options);
            }
            unset($members[$k]);
        }
        $xmlOut->endElement();
        $xmlOut->endDocument();
        $data['out'] = $xmlOut->outputMemory();
        $memUsage = memory_get_usage();
        $mem = round($memUsage / 1048576, 2);
        log_message('info', 'Memory usage: ' . $mem . 'M');
        $this->load->view('metadata_view', $data);
    }

    public function federationexport($federationName = null) {
        if ($federationName === null) {
            show_error('Not found', 404);
        }
        $data = array();
        $permitPull = $this->checkAccess();
        if ($permitPull !== true) {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }

        /**
         * @var $federation models\Federation
         */
        try {
            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $federationName, 'is_lexport' => true, 'is_active' => true));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);

            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

        if ($federation === null) {
            show_404('page', 'log_error');
        }
        $this->load->library('providertoxml');
        /**
         * check if federation is active
         */
        $publisher = $federation->getPublisherExport();
        $termsofuse = $federation->getTou();
        $attrfedreq_tmp = new models\AttributeRequirements;
        $options = array('attrs' => 1, 'fedreqattrs' => $attrfedreq_tmp->getRequirementsByFed($federation));

        $tmpm = new models\Providers;
        /**
         * @var $members models\Provider[]
         */
        $members = $tmpm->getActiveFederationmembersForExport($federation, null);
        $validfor = new \DateTime('now', new \DateTimezone('UTC'));
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $creationInstant = $validfor->format('Y-m-d\TH:i:s\Z');
        $entitiesDescriptorId = $federation->getDescriptorId();
        if (empty($entitiesDescriptorId)) {
            $idprefix = $this->config->item('fedexportmetadataidprefix');
            if (empty($idprefix)) {
                $idprefix = '';
            }
            $idsuffix = $validfor->format('YmdHis');
            $entitiesDescriptorId = $idprefix . $idsuffix;
        } else {
            $entitiesDescriptorId = 'export-' . $entitiesDescriptorId . '';
        }
        $xmlOut = $this->providertoxml->createXMLDocument();
        $topcomment = PHP_EOL . '===============================================================' . PHP_EOL . '= Federation metadata containing only localy managed entities.=' . PHP_EOL . '===============================================================' . PHP_EOL;
        // EntitiesDescriptor
        $xmlOut->startComment();
        $xmlOut->text($topcomment);
        if (!empty($termsofuse)) {
            $toucomment = PHP_EOL . 'TERMS OF USE:' . PHP_EOL . $termsofuse . PHP_EOL;
            $xmlOut->text(h_metadataComment($toucomment));
        }

        $xmlOut->endComment();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $entitiesDescriptorId . '');
        $xmlOut->writeAttribute('Name', $federation->getUrn());
        $xmlOut->writeAttribute('validUntil', $validuntil);
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        foreach ($this->metadataNS as $k => $v) {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }
        if (!empty($publisher)) {
            $xmlOut->startElementNs('md', 'Extensions', null);
            $xmlOut->startElementNs('mdrpi', 'PublicationInfo', null);
            $xmlOut->writeAttribute('creationInstant', $creationInstant);
            $xmlOut->writeAttribute('publisher', $publisher);
            $xmlOut->endElement(); // PublicationInfo
            $xmlOut->endElement(); // Extensions
        }
        foreach ($members as $k => $m) {

            $xmlOut->startComment();
            $xmlOut->text($m->getEntityId());
            if ($m->isStaticMetadata()) {
                $xmlOut->text(PHP_EOL . 'static' . PHP_EOL);
                $xmlOut->endComment();
                $this->providertoxml->entityStaticConvert($xmlOut, $m);
            } else {
                $xmlOut->endComment();
                $this->providertoxml->entityConvert($xmlOut, $m, $options);
            }
            unset($members[$k]);
        }
        $xmlOut->endElement();
        $xmlOut->endDocument();
        $data['out'] = $xmlOut->outputMemory();
        $mem = memory_get_usage();
        $mem = round($mem / 1048576, 2);
        log_message('info', __METHOD__ . ': Memory usage: ' . $mem . 'M');
        $this->load->view('metadata_view', $data);
    }

    public function preregister($tmpid) {
        if (!ctype_digit($tmpid)) {
            show_error('Not found');
        }
        $this->load->library('j_ncache');
        $cachedMetadata = $this->j_ncache->getPreregisterMetadata($tmpid);
        if (!empty($cachedMetadata)) {
            $data['out'] = $cachedMetadata;
            $this->load->view('metadata_view', $data);
        } else {
            show_error('Not found 2', 404);
        }
    }

    public function service($encodedEntityId = null, $fileName = null) {

        if (empty($encodedEntityId) || empty($fileName) || strcmp($fileName, 'metadata.xml') != 0) {
            return $this->output->set_status_header(404)->set_output('Page not found');
        }


        $data = array();
        $this->load->library('providertoxml');
        $entityId = base64url_decode($encodedEntityId);
        $options['attrs'] = 1;
        /**
         * @var $entity models\Provider
         */
        try {
            $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityId));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ': ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
        if ($entity === null) {
            log_message('debug', 'Identity/Service Provider not found');
            return $this->output->set_status_header(404)->set_output('Service metadata not found');
        }

        $isStatic = $entity->isStaticMetadata();
        /**
         * @var \XMLWriter $xmlOut
         */
        if ($isStatic) {
            $data['out'] = $this->regenerateStatic($entity);
            $mem = memory_get_usage();
            $mem = round($mem / 1048576, 2);
            log_message('info', 'Memory usage: ' . $mem . 'M');
            $this->load->view('metadata_view', $data);
        } else {
            $xmlOut = $this->providertoxml->entityConvertNewDocument($entity, $options);
            if (!empty($xmlOut)) {
                $data['out'] = $xmlOut->outputMemory();
                $mem = memory_get_usage();
                $mem = round($mem / 1048576, 2);
                log_message('info', 'Memory usage: ' . $mem . 'M');
                $this->load->view('metadata_view', $data);
            } else {
                log_message('error', __METHOD__ . ' empty xml has been generated');
                show_error('Internal server error', 500);
            }
        }

    }

    private function regenerateStatic(models\Provider $entity){
        $xmlOut = $this->providertoxml->createXMLDocument();
        $this->providertoxml->entityStaticConvert($xmlOut, $entity);
        $xmlOut->endDocument();


        $outPut = $xmlOut->outputMemory();
        $domXML = new DOMDocument();

        $domXML->loadXML($outPut, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($domXML);
        $xpath->registerNamespace('', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xpath->registerNamespace('mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
        $xpath->registerNamespace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
        $xpath->registerNamespace('mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
        $xpath->registerNamespace('shibmd', 'urn:mace:shibboleth:metadata:1.0');
        /**
         * @var \DOMElement $element
         */
        $element = $domXML->getElementsByTagName('EntityDescriptor')->item(0);
        if ($element === null) {
            $element = $domXML->getElementsByTagName('md:EntityDescriptor')->item(0);
        }
        if ($element !== null) {
            $element->setAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $element->setAttribute('xmlns:md', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $element->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
            $element->setAttribute('xmlns:mdrpi', 'urn:oasis:names:tc:SAML:metadata:rpi');
            $element->setAttribute('xmlns:mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
            $element->setAttribute('xmlns:mdattr', 'urn:oasis:names:tc:SAML:metadata:attribute');
            $element->setAttribute('xmlns:shibmd', 'urn:mace:shibboleth:metadata:1.0');
        }
        $out = $domXML->saveXML();
        return $out;
    }

    /**
     * @return bool
     */
    private function isCircleFeatureEnabled() {
        $cnf = $this->config->item('featdisable');

        return !(isset($cnf['circlemeta']) && $cnf['circlemeta'] === true);
    }

    /**
     * @param \models\Provider $provider
     * @return bool
     */
    private function isProviderAllowedForCircle(\models\Provider $provider) {
        $circleForExternalAllowed = $this->config->item('disable_extcirclemeta');
        $isLocal = $provider->getLocal();
        $result = true;
        if (!$isLocal && (!empty($circleForExternalAllowed) && $circleForExternalAllowed === true)) {
            $result = false;
        }

        return $result;
    }

    /**
     * @param null $encodedEntityId
     * @param null $fileName
     */
    public function circle($encodedEntityId = null, $fileName = null) {
        $isEnabled = $this->isCircleFeatureEnabled();
        if (!$isEnabled) {
            show_error('Circle of trust  metadata : Feature is disabled', 404);
        }
        if ($encodedEntityId === null || $fileName === null || strcmp($fileName, 'metadata.xml') != 0) {
            show_error('Request not allowed', 403);
        }
        $permitPull = $this->checkAccess();
        if ($permitPull !== true) {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }
        $data = array();
        $entityID = base64url_decode($encodedEntityId);
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => '' . $entityID . ''));
        if (empty($provider)) {
            log_message('debug', 'Failed generating circle metadata for ' . $entityID);
            show_error('unknown provider', 404);
        }
        if (!$this->isProviderAllowedForCircle($provider)) {
            log_message('warning', 'Cannot generate circle metadata for external provider:' . $provider->getEntityId());
            log_message('debug', 'To enable generate circle metadata for external entities please set disable_extcirclemeta in config to FALSE');
            show_error($provider->getEntityId() . ': This is not managed localy. Cannot generate circle metadata', 403);
        }
        $mtype = $provider->getType();
        $excludeType = null;
        if (strcasecmp($mtype, 'BOTH') != 0) {
            $excludeType = $mtype;
        }
        $options['attrs'] = 1;
        $p = new models\Providers;
        $tFeds = $p->getTrustedActiveFeds($provider);
        $feds = array();
        foreach ($tFeds as $f) {
            $feds[] = $f->getId();
        }

        /**
         * @var $members models\Provider[]
         */
        $members = $p->getActiveMembersOfFederations($feds, $excludeType);

        $validfor = new \DateTime('now', new \DateTimezone('UTC'));
        $idsuffix = $validfor->format('Ymd\THis');
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $idprefix = '';
        $prefid = $this->config->item('circlemetadataidprefix');
        if (!empty($prefid)) {
            $idprefix = $prefid;
        }
        $this->load->library('providertoxml');
        $xmlOut = $this->providertoxml->createXMLDocument();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $idprefix . $idsuffix . '');
        $xmlOut->writeAttribute('Name', '' . $provider->getEntityId() . '');
        $xmlOut->writeAttribute('validUntil', '' . $validuntil . '');
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        foreach ($this->metadataNS as $k => $v) {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }

        foreach ($members as $keyMember => $valueMember) {
            $metadataCached = $this->j_ncache->getMcircleMeta($valueMember->getId());
            $xmlOut->startComment();
            $xmlOut->text($valueMember->getEntityId());
            if (!empty($metadataCached)) {

                $xmlOut->endComment();
                $xmlOut->writeRaw($metadataCached);
            } else {

                if ($valueMember->isStaticMetadata()) {
                    $xmlOut->text(PHP_EOL . 'static' . PHP_EOL);
                    $xmlOut->endComment();
                    $this->providertoxml->entityStaticConvert($xmlOut, $valueMember);
                } else {
                    $xmlOut->endComment();
                    $this->providertoxml->entityConvert($xmlOut, $valueMember, $options, $valueMember->getId());
                }
            }
            unset($members[$keyMember]);
        }
        $xmlOut->endElement(); //EntitiesDescriptor
        $xmlOut->endDocument();
        $data['out'] = $xmlOut->outputMemory();
        $memUsage = memory_get_usage();
        $mem = round($memUsage / 1048576, 2);
        log_message('info', 'Memory usage: ' . $mem . 'M');
        $this->load->view('metadata_view', $data);
    }

    /**
     * @param $tokenid
     * @return bool|void
     */
    public function queue($tokenid) {
        if (strlen($tokenid) > 100 || !ctype_alnum($tokenid)) {
            show_error('Not found', 404);

            return null;
        }
        $queue = $this->em->getRepository("models\Queue")->findOneBy(array('token' => $tokenid));
        if (empty($queue)) {
            show_error('Not found', 404);
        }
        $queueAction = $queue->getAction();
        $queueObjType = $queue->getType();
        if (!(strcasecmp($queueAction, 'Create') == 0 && (strcasecmp($queueObjType, 'IDP') == 0 || strcasecmp($queueObjType, 'SP') == 0))) {
            show_error('Not found', 404);
        }
        $queueData = $queue->getData();
        $this->load->library('providertoxml');
        if (!isset($queueData['metadata'])) {
            $entity = new models\Provider;
            $entity->importFromArray($queueData);
            $options['attrs'] = 1;
            if (empty($entity)) {
                show_error('Not found', 404);
            }
            $result = $this->providertoxml->entityConvertNewDocument($entity, $options);
            $data['out'] = $result->outputMemory();
        } else {
            $this->load->library('xmlvalidator');
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = false;
            $metadataDOM->WarningChecking = false;
            $metadataDOM->loadXML(base64_decode($queueData['metadata']));
            $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, false, false);
            if (!$isValid) {
                show_error('invalida metadata', 404);
            }
            $data['out'] = $metadataDOM->saveXML();
        }
        $this->load->view('metadata_view', $data);
    }

    /**
     * @return bool
     */
    private function checkAccess() {
        if ($this->jauth->isLoggedIn()) {
            return true;
        }
        $remoteip = $this->input->ip_address();
        $limits = $this->config->item('unsignedmeta_iplimits');
        if (is_array($limits)) {
            if (in_array($remoteip, $limits, true)) {
                return true;
            }

            return false;
        }

        return true;
    }

}
