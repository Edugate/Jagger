<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
class Metadata extends MY_Controller {

    private $useNewMetagen = false;

    function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('text/xml');
        $c = $this->config->item('useNewMetagenerator');
        if (!empty($c) && $c === true)
        {
            $this->useNewMetagen = true;
        }
    }

    public function federation($federationName = NULL, $t = NULL)
    {
        if ($this->useNewMetagen)
        {
            $this->federationNew($federationName, $t);
        }
        else
        {
            $this->federationOld($federationName, $t);
        }
    }

    public function federationNew($federationName = NULL, $t = NULL)
    {
        $this->load->library('providertoxml');
        if (empty($federationName))
        {
            show_error('Not found', 404);
        }
        $data = array();
        $excludeType = null;
        $name = $federationName;
        if (!empty($t) && ((strcasecmp($t, 'SP') == 0) || (strcasecmp($t, 'IDP') == 0) ))
        {
            $type = strtoupper($t);
            if (strcasecmp($t, 'SP') == 0)
            {
                $excludeType = 'IDP';
            }
            else
            {
                $excludeType = 'SP';
            }
        }
        else
        {
            $type = 'all';
        }

        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }

        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $name));

        if (empty($federation))
        {
            show_404('page', 'log_error');
        }
        $isactive = $federation->getActive();
        if (empty($isactive))
        {
            /**
             * dont display metadata if federation is inactive
             */
            show_error('federation is not active', 404);
        }
        $publisher = $federation->getPublisher();
        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
        $creationInstant = $validfor->format('Y-m-d\TH:i:s\Z');
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $prefid = $this->config->item('fedmetadataidprefix');
        if (!empty($prefid))
        {
            $idprefix = $prefid;
        }
        $idsuffix = $validfor->format('YmdHis');

        $includeAttrRequirement = $federation->getAttrsInmeta();
        $options = array('attrs' => 0, 'fedreqattrs' => array());
        if ($includeAttrRequirement)
        {
            $options['attrs'] = 1;
            $attrfedreq_tmp = new models\AttributeRequirements;
            $options['fedreqattrs'] = $attrfedreq_tmp->getRequirementsByFed($federation);
        }
        $tmpm = new models\Providers;
        $members = $tmpm->getActiveFederationMembers($federation, $excludeType);

        $xmlOut = $this->providertoxml->createXMLDocument();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        // EntitiesDescriptor
        $xmlOut->startComment();
        $xmlOut->text('nMetadata was generated on: ' . $now->format('Y-m-d H:i') . ' UTC' . PHP_EOL . 'TERMS OF USE' . PHP_EOL . $federation->getTou() . PHP_EOL);
        $xmlOut->endComment();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $idprefix . $idsuffix . '');
        $xmlOut->writeAttribute('Name', $federation->getUrn());
        $xmlOut->writeAttribute('validUntil', $validuntil);
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v)
        {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }
        foreach ($members as $k => $m)
        {
            $xmlOut->startComment();
            $xmlOut->text($m->getEntityId());
            if ($m->isStaticMetadata())
            {
                $xmlOut->text(PHP_EOL . 'static' . PHP_EOL);
                $xmlOut->endComment();
                $this->providertoxml->entityStaticConvert($xmlOut, $m);
            }
            else
            {
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

    public function federationOld($federationName = NULL, $t = NULL)
    {
        if (empty($federationName))
        {
            show_error('Not found', 404);
        }
        $data = array();
        $name = $federationName;
        if (!empty($t) && ((strcasecmp($t, 'SP') == 0) || (strcasecmp($t, 'IDP') == 0) ))
        {
            $type = strtoupper($t);
        }
        else
        {
            $type = 'all';
        }

        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }

        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $name));


        if (empty($federation))
        {
            show_404('page', 'log_error');
        }
        else
        {
            /**
             * check if federation is active
             */
            $isactive = $federation->getActive();
            if (empty($isactive))
            {
                /**
                 * dont display metadata if federation is inactive
                 */
                show_error('federation is not active', 404);
            }


            /**
             * check if required attribute must be added to federated metadata 
             */
            $include_attrs = $federation->getAttrsInmeta();
            $reqattrs_by_fed = null;
            $options = array();
            if ($include_attrs)
            {
                $options['attrs'] = 1;
                $attrfedreq_tmp = new models\AttributeRequirements;
                $reqattrs_by_fed = $attrfedreq_tmp->getRequirementsByFed($federation);
                if (!empty($reqattrs_by_fed))
                {
                    $options['fedreqattrs'] = $reqattrs_by_fed;
                }
            }

            $members = $federation->getActiveMembers();
            $members_count = $members->count();
            $members_keys = $members->getKeys();
            log_message('debug', 'no federation members: ' . $members_count);

            $docXML = new \DOMDocument();
            $docXML->encoding = 'UTF-8';
            $docXML->formatOutput = true;
            $xpath = new \DomXPath($docXML);
            $termsofuse = $federation->getTou();

            if (!empty($termsofuse))
            {
                $termsofuse = "TERMS OF USE\n" . $termsofuse;
                $termsofuse = h_metadataComment($termsofuse);
                $comment = $docXML->createComment($termsofuse);
                $docXML->appendChild($comment);
            }
            /**
             * get metadata namespaces from metadata_elements_helper
             */
            $namespaces = h_metadataNamespaces();
            foreach ($namespaces as $key => $value)
            {
                $xpath->registerNamespace($key, $value);
            }
            $publisher = $federation->getPublisher();
            $Entities_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntitiesDescriptor');
            $Entities_Node->setAttribute('Name', $federation->getUrn());
            $validfor = new \DateTime("now", new \DateTimezone('UTC'));
            $creationInstant = $validfor->format('Y-m-d\TH:i:s\Z');
            $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
            $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
            $Entities_Node->setAttribute('validUntil', $validuntil);
            $idprefix = '';
            $prefid = $this->config->item('fedmetadataidprefix');
            if (!empty($prefid))
            {
                $idprefix = $prefid;
            }
            $idsuffix = $validfor->format('YmdHis');
            $Entities_Node->setAttribute('ID', '' . $idprefix . $idsuffix . '');
            if (!empty($publisher))
            {
                $extensionNode = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:Extensions');
                $publicationNode = $docXML->createElementNS('urn:oasis:names:tc:SAML:metadata:rpi', 'mdrpi:PublicationInfo');
                $publicationNode->setAttribute('creationInstant', $creationInstant);
                $publicationNode->setAttribute('publisher', $publisher);
                $extensionNode->appendChild($publicationNode);
                $Entities_Node->appendChild($extensionNode);
            }

            /**
             * @todo ValidUntil
             */
            if ($type === 'all')
            {
                log_message('debug', 'Genereate for all entities');
                for ($i = 0; $i < $members_count; $i++)
                {
                    if ($members->get($members_keys['' . $i . ''])->getAvailable())
                    {
                        $members->get($members_keys['' . $i . ''])->getProviderToXML($Entities_Node, $options);
                    }
                }
            }
            else
            {
                foreach ($members as $key)
                {
                    if ($key->getAvailable() && (($key->getType() === $type) || ($key->getType() === 'BOTH')))
                    {
                        $key->getProviderToXML($Entities_Node, $options);
                    }
                }
            }
            $docXML->appendChild($Entities_Node);
            $data['out'] = $docXML->saveXML();
            $mem = memory_get_usage();
            $mem = round($mem / 1048576, 2);
            log_message('debug', 'Memory usage: ' . $mem . 'M');
            $this->load->view('metadata_view', $data);
        }
    }

    private function federationexportNew($federationName = NULL, $t = NULL)
    {
        if (empty($federationName))
        {
            show_error('Not found', 404);
        }
        $data = array();
        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }


        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $federationName, 'is_lexport' => TRUE));


        if (empty($federation))
        {
            show_404('page', 'log_error');
        }
        $this->load->library('providertoxml');
        /**
         * check if federation is active
         */
        $isactive = $federation->getActive();
        if (empty($isactive))
        {
            /**
             * dont display metadata if federation is inactive
             */
            show_error('federation is not active', 404);
        }
        $termsofuse = $federation->getTou();
        $include_attrs = $federation->getAttrsInmeta();
        $reqattrs_by_fed = null;
        $options = array('attrs' => 0, 'fedreqattrs' => array());
        if ($include_attrs)
        {
            $options['attrs'] = 1;
            $attrfedreq_tmp = new models\AttributeRequirements;
            $options['fedreqattrs'] = $attrfedreq_tmp->getRequirementsByFed($federation);
        }
        $tmpm = new models\Providers;
        $members = $tmpm->getActiveFederationmembersForExport($federation, $excludeType);
        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $idprefix = $this->config->item('fedexportmetadataidprefix');
        if (empty($idprefix))
        {
            $idprefix = '';
        }
        $idsuffix = $validfor->format('YmdHis');
        $xmlOut = $this->providertoxml->createXMLDocument();
        $topcomment = PHP_EOL . '===============================================================' . PHP_EOL . '= Federation metadata containing only localy managed entities.=' . PHP_EOL . '===============================================================' . PHP_EOL;
        // EntitiesDescriptor
        $xmlOut->startComment();
        $xmlOut->text($topcomment);
        if (!empty($termsofuse))
        {
            $toucomment = PHP_EOL . "TERMS OF USE:" . PHP_EOL . $termsofuse . PHP_EOL;
            $xmlOut->text(h_metadataComment($toucomment));
        }

        $xmlOut->endComment();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $idprefix . $idsuffix . '');
        $xmlOut->writeAttribute('Name', $federation->getUrn());
        $xmlOut->writeAttribute('validUntil', $validuntil);
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v)
        {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }
        foreach ($members as $k => $m)
        {

            $xmlOut->startComment();
            $xmlOut->text($m->getEntityId());
            if ($m->isStaticMetadata())
            {
                $xmlOut->text(PHP_EOL . 'static' . PHP_EOL);
                $xmlOut->endComment();
                $this->providertoxml->entityStaticConvert($xmlOut, $m);
            }
            else
            {
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
        log_message('info', 'Memory usage: ' . $mem . 'M');
        $this->load->view('metadata_view', $data);
    }

    public function federationexport($federationName = NULL, $t = NULL)
    {
        if ($this->useNewMetagen)
        {
            $this->federationexportNew($federationName, $t);
        }
        else
        {
            $this->federationexportOld($federationName, $t);
        }
    }

    private function federationexportOld($federationName = NULL, $t = NULL)
    {
        if (empty($federationName))
        {
            show_error('Not found', 404);
        }
        $data = array();
        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }


        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('sysname' => $federationName, 'is_lexport' => TRUE));


        if (empty($federation))
        {
            show_404('page', 'log_error');
        }
        else
        {
            /**
             * check if federation is active
             */
            $isactive = $federation->getActive();
            if (empty($isactive))
            {
                /**
                 * dont display metadata if federation is inactive
                 */
                show_error('federation is not active', 404);
            }


            /**
             * check if required attribute must be added to federated metadata 
             */
            $include_attrs = $federation->getAttrsInmeta();
            $reqattrs_by_fed = null;
            $options = array();
            if ($include_attrs)
            {
                $options['attrs'] = 1;
                $attrfedreq_tmp = new models\AttributeRequirements;
                $reqattrs_by_fed = $attrfedreq_tmp->getRequirementsByFed($federation);
                if (!empty($reqattrs_by_fed))
                {
                    $options['fedreqattrs'] = $reqattrs_by_fed;
                }
            }

            $members = $federation->getMembersForExport();
            $membersCount = $members->count();
            $membersKeys = $members->getKeys();
            log_message('debug', 'no federation members: ' . $membersCount);

            $docXML = new \DOMDocument();
            $docXML->encoding = 'UTF-8';
            $docXML->formatOutput = true;
            $xpath = new \DomXPath($docXML);
            $termsofuse = $federation->getTou();

            $topcomment = PHP_EOL . '===============================================================' . PHP_EOL . '= Federation metadata containing only localy managed entities.=' . PHP_EOL . '===============================================================' . PHP_EOL;
            $tcomment = $docXML->createComment($topcomment);
            $docXML->appendChild($tcomment);
            if (!empty($termsofuse))
            {
                $termsofuse = PHP_EOL . "TERMS OF USE:" . PHP_EOL . $termsofuse . PHP_EOL;
                $termsofuse = h_metadataComment($termsofuse);
                $comment = $docXML->createComment($termsofuse);
                $docXML->appendChild($comment);
            }
            /**
             * get metadata namespaces from metadata_elements_helper
             */
            $namespaces = h_metadataNamespaces();
            foreach ($namespaces as $key => $value)
            {
                $xpath->registerNamespace($key, $value);
            }
            $Entities_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntitiesDescriptor');
            $Entities_Node->setAttribute('Name', $federation->getUrn());
            $validfor = new \DateTime("now", new \DateTimezone('UTC'));
            $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
            $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
            $Entities_Node->setAttribute('validUntil', $validuntil);
            $idprefix = $this->config->item('fedexportmetadataidprefix');
            if (empty($idprefix))
            {
                $idprefix = '';
            }
            $idsuffix = $validfor->format('YmdHis');
            $Entities_Node->setAttribute('ID', '' . $idprefix . $idsuffix . '');

            /**
             * @todo ValidUntil
             */
            if (!empty($t) && (strcasecmp($t, 'IDP') == 0 || (strcasecmp($t, 'SP') == 0)))
            {
                foreach ($members as $key)
                {
                    if ($key->getLocalAvailable() && (strcasecmp($key->getType(), $t) == 0 || strcasecmp($key->getType(), 'BOTH') == 0))
                    {
                        $key->getProviderToXML($Entities_Node, $options);
                    }
                }
            }
            else
            {
                for ($i = 0; $i < $membersCount; $i++)
                {
                    if ($members->get($membersKeys['' . $i . ''])->getLocalAvailable())
                    {
                        $members->get($membersKeys['' . $i . ''])->getProviderToXML($Entities_Node, $options);
                    }
                }
            }

            $docXML->appendChild($Entities_Node);
            $data['out'] = $docXML->saveXML();
            $this->load->view('metadata_view', $data);
        }
    }

    public function serviceNew($entityId = null, $m = null)
    {
        if (empty($entityId) || empty($m) || strcmp($m, 'metadata.xml') != 0)
        {
            show_error('Page not found', 404);
        }


        $data = array();
        $this->load->library('providertoxml');
        $name = base64url_decode($entityId);
        $options['attrs'] = 1;
        $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
        if (!empty($entity))
        {
            $isStatic = $entity->isStaticMetadata();
            if ($isStatic)
            {
                $xmlOut = $this->providertoxml->createXMLDocument();
                $this->providertoxml->entityStaticConvert($xmlOut, $entity);
                $xmlOut->endDocument();
                $data['out'] = $xmlOut->outputMemory();
                $mem = memory_get_usage();
                $mem = round($mem / 1048576, 2);
                log_message('info', 'Memory usage: ' . $mem . 'M');
                $this->load->view('metadata_view', $data);
            }
            else
            {
                $xmlOut = $this->providertoxml->entityConvertNewDocument($entity, $options);
            }
            if (!empty($xmlOut))
            {
                $data['out'] = $xmlOut->outputMemory();
                $mem = memory_get_usage();
                $mem = round($mem / 1048576, 2);
                log_message('info', 'Memory usage: ' . $mem . 'M');
                $this->load->view('metadata_view', $data);
            }
            else
            {
                log_message('error', __METHOD__ . ' empty xml has been generated');
                show_error('Internal server error', 500);
            }
        }
        else
        {
            log_message('debug', 'Identity Provider not found');
            show_error('Identity Provider not found', 404);
        }
    }

    public function service($entityId = null, $m = null)
    {
        $this->serviceOld($entityId, $m);
    }

    public function serviceOld($entityId = null, $m = null)
    {
        if (empty($entityId) || empty($m) || strcmp($m, 'metadata.xml') != 0)
        {
            show_error('Page not found', 404);
        }


        $data = array();

        $name = base64url_decode($entityId);
        $options['attrs'] = 1;
        $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
        if (!empty($entity))
        {
            $y = $entity->getProviderToXML($parent = null, $options);
            log_message('debug', get_class($y));
            if (empty($y))
            {
                log_message('error', 'Got empty xml form Provider model');
                log_message('error', "Service metadata for " . $entity->getEntityId() . " couldn't be generated");
                show_error("Metadata for " . $entity->getEntityId() . " couldn't be generated", 503);
            }
            else
            {
                $data['out'] = $y->saveXML();
                $this->load->view('metadata_view', $data);
            }
        }
        else
        {
            log_message('debug', 'Identity Provider not found');
            show_error('Identity Provider not found', 404);
        }
    }

    public function queue($tokenid)
    {
        if (strlen($tokenid) > 100 || !ctype_alnum($tokenid))
        {
            show_error('Not found', 404);
            return;
        }
        $q = $this->em->getRepository("models\Queue")->findOneBy(array('token' => $tokenid));
        if (empty($q))
        {
            show_error('Not found', 404);
            return;
        }
        $queueAction = $q->getAction();
        $queueObjType = $q->getType();
        if (!(strcasecmp($queueAction, 'Create') == 0 && (strcasecmp($queueObjType, 'IDP') == 0 || strcasecmp($queueObjType, 'SP') == 0)))
        {
            show_error('Not found', 404);
            return;
        }
        $d = $q->getData();
        if (!isset($d['metadata']))
        {
            $entity = new models\Provider;
            $entity->importFromArray($d);
            $options['attrs'] = 1;
            if (empty($entity))
            {
                show_error('Not found', 404);
            }
            $y = $entity->getProviderToXML($parent = null, $options);
            $data['out'] = $y->saveXML();
        }
        else
        {
            $this->load->library('xmlvalidator');
            libxml_use_internal_errors(true);
            $metadataDOM = new \DOMDocument();
            $metadataDOM->strictErrorChecking = FALSE;
            $metadataDOM->WarningChecking = FALSE;
            $metadataDOM->loadXML(base64_decode($d['metadata']));
            $isValid = $this->xmlvalidator->validateMetadata($metadataDOM, FALSE, FALSE);
            if (!$isValid)
            {
                show_error('invalida metadata', 404);
                return false;
            }
            $data['out'] = $metadataDOM->saveXML();
        }
        $this->load->view('metadata_view', $data);
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
        if (!$isLocal && (!empty($circleForExternalAllowed) && $circleForExternalAllowed === true))
        {
            $result = false;
        }
        return $result;
    }

    public function circle($entityId = NULL, $m = NULL)
    {
        if ($this->useNewMetagen)
        {
            $this->circleNew($entityId, $m);
        }
        else
        {
            $this->circleOld($entityId, $m);
        }
    }

    public function circleNew($entityId = NULL, $m = NULL)
    {
        $isEnabled = $this->isCircleFeatureEnabled();
        if (!$isEnabled)
        {
            show_error('Circle of trust  metadata : Feature is disabled', 404);
        }
        if (empty($entityId) || empty($m) || strcmp($m, 'metadata.xml') != 0)
        {
            show_error('Request not allowed', 403);
        }
        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }
        $data = array();
        $name = base64url_decode($entityId);
        $me = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => '' . $name . ''));

        if (empty($me))
        {
            log_message('debug', 'Failed generating circle metadata for ' . $name);
            show_error('unknown provider', 404);
            return;
        }
        $disable_extcirclemeta = $this->config->item('disable_extcirclemeta');

        if (!$this->isProviderAllowedForCircle($me))
        {
            log_message('warning', 'Cannot generate circle metadata for external provider:' . $me->getEntityId());
            log_message('debug', 'To enable generate circle metadata for external entities please set disable_extcirclemeta in config to FALSE');
            show_error($me->getEntityId() . ': This is not managed localy. Cannot generate circle metadata', 403);
            return;
        }
        $mtype = $me->getType();
        $excludeType = null;
        if (strcasecmp($mtype, 'BOTH') != 0)
        {
            $excludeType = $mtype;
        }
        $keyPrefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
        $cacheId = 'circlemeta_' . $me->getId();
        $options['attrs'] = 1;
        $p = new models\Providers;
        $tFeds = $p->getTrustedActiveFeds($me);
        $feds = array();
        foreach ($tFeds as $f)
        {
            $feds[] = $f->getId();
        }

        $members = $p->getActiveMembersOfFederations($feds, $excludeType);

        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
        $idsuffix = $validfor->format('Ymd\THis');
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $idprefix = '';
        $prefid = $this->config->item('circlemetadataidprefix');
        if (!empty($prefid))
        {
            $idprefix = $prefid;
        }
        $this->load->library('providertoxml');
        $xmlOut = $this->providertoxml->createXMLDocument();
        $xmlOut->startElementNs('md', 'EntitiesDescriptor', null);
        $xmlOut->writeAttribute('ID', '' . $idprefix . $idsuffix . '');
        $xmlOut->writeAttribute('Name', '' . $me->getEntityId() . '');
        $xmlOut->writeAttribute('validUntil', '' . $validuntil . '');
        $xmlOut->writeAttribute('xmlns', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $regNamespaces = h_metadataNamespaces();
        foreach ($regNamespaces as $k => $v)
        {
            $xmlOut->writeAttribute('xmlns:' . $k . '', '' . $v . '');
        }

        foreach ($members as $keyMember => $valueMember)
        {

            $cacheId = 'mcircle_' . $valueMember->getId() . '';
            $metadataCached = $this->cache->get($cacheId);          
            $xmlOut->startComment();
            $xmlOut->text($valueMember->getEntityId());
            if (!empty($metadataCached))
            {
                $xmlOut->text(PHP_EOL . 'from cache' . PHP_EOL);
                $xmlOut->endComment();
                $xmlOut->writeRaw($metadataCached);
            }
            else
            {

                if ($valueMember->isStaticMetadata())
                {
                    $xmlOut->text(PHP_EOL . 'static' . PHP_EOL);
                    $xmlOut->endComment();
                    $this->providertoxml->entityStaticConvert($xmlOut, $valueMember);
                }
                else
                {
                    $xmlOut->endComment();
                    $this->providertoxml->entityConvert($xmlOut, $valueMember, $options, $cacheId);
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

    public function circleOld($entityId = NULL, $m = NULL)
    {
        $isEnabled = $this->isCircleFeatureEnabled();
        if (!$isEnabled)
        {
            show_error('Circle of trust  metadata : Feature is disabled', 404);
        }
        if (empty($entityId) || empty($m) || strcmp($m, 'metadata.xml') != 0)
        {
            show_error('Request not allowed', 403);
        }
        $permitPull = $this->checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }
        $data = array();
        $name = base64url_decode($entityId);
        $me = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => '' . $name . ''));

        if (empty($me))
        {
            log_message('debug', 'Failed generating circle metadata for ' . $name);
            show_error('unknown provider', 404);
            return;
        }
        $disable_extcirclemeta = $this->config->item('disable_extcirclemeta');

        if (!$this->isProviderAllowedForCircle($me))
        {
            log_message('warning', 'Cannot generate circle metadata for external provider:' . $me->getEntityId());
            log_message('debug', 'To enable generate circle metadata for external entities please set disable_extcirclemeta in config to FALSE');
            show_error($me->getEntityId() . ': This is not managed localy. Cannot generate circle metadata', 403);
            return;
        }
        $keyPrefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
        $cacheId = 'circlemeta_' . $me->getId();
        $options['attrs'] = 1;
        $p = new models\Providers;
        $p1 = $p->getCircleMembersByType($me, $excludeDisabledFeds = TRUE);
        $docXML = new \DOMDocument();
        $docXML->encoding = 'UTF-8';
        $docXML->formatOutput = true;

        $xpath = new \DomXPath($docXML);
        $namespaces = h_metadataNamespaces();
        foreach ($namespaces as $key => $value)
        {
            $xpath->registerNamespace($key, $value);
        }

        $validfor = new \DateTime("now", new \DateTimezone('UTC'));
        $idsuffix = $validfor->format('Ymd\THis');
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d\TH:i:s\Z');
        $idprefix = '';
        $prefid = $this->config->item('circlemetadataidprefix');
        if (!empty($prefid))
        {
            $idprefix = $prefid;
        }
        $Entities_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntitiesDescriptor');
        $Entities_Node->setAttribute('validUntil', $validuntil);
        $Entities_Node->setAttribute('Name', '' . $me->getEntityId() . '');
        $Entities_Node->setAttribute('ID', '' . $idprefix . $idsuffix . '');

        foreach ($p1 as $v)
        {
            if ($v->getAvailable())
            {
                $comment = " \n\"" . htmlspecialchars($v->getEntityId()) . "\"\n";
                if ($v->getStatic())
                {
                    $comment .= "static\n";
                }
                $c = $Entities_Node->ownerDocument->createComment(str_replace('--', '-' . chr(194) . chr(173) . '-', $comment));
                $Entities_Node->appendChild($c);
                $cacheId = 'mcircle_' . $v->getId() . '';
                $metadataCached = $this->cache->get($cacheId);
                if (!empty($metadataCached))
                {
                    $y = $metadataCached;
                }
                else
                {
                    $y = $v->getProviderToXML(NULL, $options);
                    if (!empty($y))
                    {
                        $y = $y->saveXML();
                    }
                    if (!empty($y))
                    {
                        $this->cache->save($cacheId, $y, 600);
                    }
                }
                if (!empty($y))
                {
                    $z = new XMLReader();
                    $z->XML($y);
                    while ($z->read())
                    {
                        if ($z->nodeType == XMLReader::ELEMENT &&
                                ($z->name === 'md:EntityDescriptor' || $z->name === 'EntityDescriptor'))
                        {

                            $y = $Entities_Node->ownerDocument->importNode($z->expand(), true);
                            $Entities_Node->appendChild($y);
                            break;
                        }
                    }
                    $z->close();
                }
            }
        }
        $docXML->appendChild($Entities_Node);
        $this->output->set_content_type('text/xml');
        log_message('debug', __METHOD__ . ' memory: ' . memory_get_usage());
        $data['out'] = $docXML->saveXML();
        $this->load->view('metadata_view', $data);
    }

    private function checkAccess()
    {
        $permitPull = FALSE;

        $isAjax = $this->input->is_ajax_request();
        if (!$isAjax)
        {
            $limits = $this->config->item('unsignedmeta_iplimits');
            if (!empty($limits) and is_array($limits) and count($limits) > 0)
            {
                $remoteip = $this->input->ip_address();
                if (in_array($remoteip, $limits))
                {
                    $permitPull = TRUE;
                }
            }
            else
            {
                $permitPull = TRUE;
            }
        }
        else
        {
            $permitPull = TRUE;
        }


        return $permitPull;
    }

}
