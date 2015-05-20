<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Jagger
 *
 * @package     Jagger
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Metadata Class
 *
 * @package     Jagger
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * @property Providertoxml $providertoxml
 * @property Xmlvalidator $xmlvalidator
 */
class Metadata extends MY_Controller
{


    function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/samlmetadata+xml');
        $this->load->library('j_ncache');
    }

    public function federation($federationName = NULL, $limitType = NULL)
    {
        if (empty($federationName)) {
            show_error('Not found', 404);
        }
        $this->load->library('providertoxml');
        $data = array();
        $excludeType = null;
        $name = $federationName;

        if (strcasecmp($limitType, 'SP') == 0) {
            $excludeType = 'IDP';
        } elseif (strcasecmp($limitType, 'IDP') == 0) {
            $excludeType = 'SP';
        }

        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE) {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }

        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $name, 'is_active' => true));

        if (empty($federation)) {
            set_status_header(404);
            echo 'Federation not found or is inactive';
            return;
        }
        $publisher = $federation->getPublisher();
        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
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
        $options = array('attrs' => 1, 'fedreqattrs' => $tmpAttrRequirements->getRequirementsByFed($federation));

        $tmpm = new models\Providers;
        /**
         * @var $members \models\Provider[]
         */
        $members = $tmpm->getActiveFederationMembers($federation, $excludeType);

        $xmlOut = $this->providertoxml->createXMLDocument();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        // EntitiesDescriptor
        $xmlOut->startComment();
        $xmlOut->text('Metadata was generated on: ' . $now->format('Y-m-d H:i') . ' UTC' . PHP_EOL . 'TERMS OF USE' . PHP_EOL . $federation->getTou() . PHP_EOL);
        $xmlOut->endComment();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $entitiesDescriptorId . '');
        $xmlOut->writeAttribute('Name', $federation->getUrn());
        $xmlOut->writeAttribute('validUntil', $validuntil);
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v) {
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
            $cacheId = null;
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

    public function federationexport($federationName = NULL)
    {
        if (empty($federationName)) {
            show_error('Not found', 404);
        }
        $data = array();
        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE) {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }

        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $federationName, 'is_lexport' => TRUE, 'is_active' => TRUE));

        if (empty($federation)) {
            show_404('page', 'log_error');
        }
        $this->load->library('providertoxml');
        /**
         * check if federation is active
         */
        $termsofuse = $federation->getTou();
        $attrfedreq_tmp = new models\AttributeRequirements;
        $options = array('attrs' => 1, 'fedreqattrs' => $attrfedreq_tmp->getRequirementsByFed($federation));

        $tmpm = new models\Providers;
        /**
         * @var $members models\Provider[]
         */
        $members = $tmpm->getActiveFederationmembersForExport($federation, null);
        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');

        $entitiesDescriptorId = $federation->getDescriptorId();
        if (strlen($entitiesDescriptorId) == 0) {

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
            $toucomment = PHP_EOL . "TERMS OF USE:" . PHP_EOL . $termsofuse . PHP_EOL;
            $xmlOut->text(h_metadataComment($toucomment));
        }

        $xmlOut->endComment();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $entitiesDescriptorId . '');
        $xmlOut->writeAttribute('Name', $federation->getUrn());
        $xmlOut->writeAttribute('validUntil', $validuntil);
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v) {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
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

    public function preregister($tmpid)
    {
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

    public function service($entityId = null, $fileName = null)
    {
        if (empty($entityId) || empty($fileName) || strcmp($fileName, 'metadata.xml') != 0) {
            show_error('Page not found', 404);
        }


        $data = array();
        $this->load->library('providertoxml');
        $name = base64url_decode($entityId);
        $options['attrs'] = 1;
        /**
         * @var $entity models\Provider
         */
        $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
        if (!empty($entity)) {
            $isStatic = $entity->isStaticMetadata();
            if ($isStatic) {
                $xmlOut = $this->providertoxml->createXMLDocument();
                $this->providertoxml->entityStaticConvert($xmlOut, $entity);
                $xmlOut->endDocument();
                $data['out'] = $xmlOut->outputMemory();
                $mem = memory_get_usage();
                $mem = round($mem / 1048576, 2);
                log_message('info', 'Memory usage: ' . $mem . 'M');
                $this->load->view('metadata_view', $data);
            } else {
                $xmlOut = $this->providertoxml->entityConvertNewDocument($entity, $options);
            }
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
        } else {
            log_message('debug', 'Identity Provider not found');
            show_error('Identity Provider not found', 404);
        }
    }

    private function isCircleFeatureEnabled()
    {
        $circlemetaFeature = $this->config->item('featdisable');
        return !(is_array($circlemetaFeature) && isset($circlemetaFeature['circlemeta']) && $circlemetaFeature['circlemeta'] === TRUE);
    }

    private function isProviderAllowedForCircle(\models\Provider $provider)
    {
        $circleForExternalAllowed = $this->config->item('disable_extcirclemeta');
        $isLocal = $provider->getLocal();
        $result = true;
        if (!$isLocal && (!empty($circleForExternalAllowed) && $circleForExternalAllowed === true)) {
            $result = false;
        }
        return $result;
    }

    public function circle($entityId = NULL, $fileName = NULL)
    {
        $isEnabled = $this->isCircleFeatureEnabled();
        if (!$isEnabled) {
            show_error('Circle of trust  metadata : Feature is disabled', 404);
        }
        if (empty($entityId) || empty($fileName) || strcmp($fileName, 'metadata.xml') != 0) {
            show_error('Request not allowed', 403);
        }
        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE) {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }
        $data = array();
        $name = base64url_decode($entityId);
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => '' . $name . ''));
        if (empty($provider)) {
            log_message('debug', 'Failed generating circle metadata for ' . $name);
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

        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
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
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v) {
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
    public function queue($tokenid)
    {
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
            $metadataDOM->strictErrorChecking = FALSE;
            $metadataDOM->WarningChecking = FALSE;
            $metadataDOM->loadXML(base64_decode($queueData['metadata']));
            $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
            if (!$isValid) {
                show_error('invalida metadata', 404);
            }
            $data['out'] = $metadataDOM->saveXML();
        }
        $this->load->view('metadata_view', $data);
    }

    private function checkAccess()
    {
        $permitPull = FALSE;

        $isAjax = $this->input->is_ajax_request();
        if (!$isAjax) {
            $limits = $this->config->item('unsignedmeta_iplimits');
            if (!empty($limits) && is_array($limits) && count($limits) > 0) {
                $remoteip = $this->input->ip_address();
                if (in_array($remoteip, $limits)) {
                    $permitPull = TRUE;
                }
            } else {
                $permitPull = TRUE;
            }
        } else {
            $permitPull = TRUE;
        }


        return $permitPull;
    }

}
