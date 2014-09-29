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
 * Metadata Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadata extends MY_Controller {

    //put your code here

    function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('text/xml');
    }

    public function federation($federationName = NULL, $t = NULL)
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

        $permitPull = $this->_checkAccess();
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

    public function federationexport($federationName = NULL, $t = NULL)
    {
        if (empty($federationName))
        {
            show_error('Not found', 404);
        }
        $data = array();
        $permitPull = $this->_checkAccess();
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

    public function service($entityId = null, $m = null)
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

    private function isCircleFeatureEnabled()
    {
        $circlemetaFeature = $this->config->item('featdisable');
        return !(is_array($circlemetaFeature) && isset($circlemetaFeature['circlemeta']) && $circlemetaFeature['circlemeta'] === TRUE);
    }

    public function circle($entityId = NULL, $m = NULL)
    {
        $circlemetaFeature = $this->config->item('featdisable');
        $circleEnabled = !(is_array($circlemetaFeature) && isset($circlemetaFeature['circlemeta']) && $circlemetaFeature['circlemeta'] === TRUE);
        $isEnabled = $this->isCircleFeatureEnabled();
        if (!$isEnabled)
        {
            show_error('Circle of trust  metadata : Feature is disabled', 404);
        }
        if (empty($entityId) || empty($m) || strcmp($m, 'metadata.xml') != 0)
        {
            show_error('Request not allowed', 403);
        }
        $permitPull = $this->_checkAccess();
        if ($permitPull !== TRUE)
        {
            log_message('error', __METHOD__ . ' access denied from ip: ' . $this->input->ip_address());
            show_error('Access denied', 403);
        }
        $data = array();
        $name = base64url_decode($entityId);
        $tmp = new models\Providers;
        $me = $tmp->getOneByEntityId($name);
        if (empty($me))
        {
            log_message('debug', 'Failed generating circle metadata for ' . $name);
            show_error('unknown provider', 404);
            return;
        }
        $disable_extcirclemeta = $this->config->item('disable_extcirclemeta');
        if (!empty($disable_extcirclemeta) && $disable_extcirclemeta === TRUE)
        {
            $is_local = $me->getLocal();
            if (!$is_local)
            {
                log_message('warning', 'Cannot generate circle metadata for external provider:' . $me->getEntityId());
                log_message('debug', 'To enable generate circle metadata for external entities please set disable_extcirclemeta in config to FALSE');
                show_error($me->getEntityId() . ': This is external provider. Cannot generate circle metadata', 403);
                return;
            }
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
        $Entities_Node->setAttribute('Name', 'circle:' . $me->getEntityId());
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

    private function _checkAccess()
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
