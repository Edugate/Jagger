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

    function __construct() {
        parent::__construct();
        $this->output->set_content_type('text/xml');
    }

    public function federation($federationName, $t = NULL) {
        $data = array();
        $name = base64url_decode($federationName);
        if (!empty($t) AND (($t === 'IDP') OR ($t === 'SP') OR ($t === 'idp') OR ($t === 'sp'))) {
            $type = strtoupper($t);
        } else {
            $type = 'all';
        }


        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $name));


        if (empty($federation)) {
            show_404('page', 'log_error');
        } else {
            /**
             * check if federation is active
             */
            $isactive = $federation->getActive();
            if (empty($isactive)) {
                /**
                 * dont display metadata if federation is inactive
                 */
                show_error('federation is not active', 404);
            }

            //$tmp_cnt = new models\Contacts;
            //$contacts = $tmp_cnt->getContacts();
           
            /**
             * check if required attribute must be added to federated metadata 
             */
            $include_attrs = $federation->getAttrsInmeta();
            $reqattrs_by_fed = null;
            $options = array();
            if($include_attrs)
            {
              $options['attrs'] = 1;
              $attrfedreq_tmp = new models\AttributeRequirements;
              $reqattrs_by_fed = $attrfedreq_tmp->getRequirementsByFed($federation);
              if(!empty($reqattrs_by_fed))
              {
                 $options['fedreqattrs'] = $reqattrs_by_fed ;
              }
            }

            $members = $federation->getMembers();
            $members_count = $members->count();
            $members_keys = $members->getKeys();
            log_message('debug', 'no federation members: ' . $members_count);

            //$count_members = count($members);
            $docXML = new \DOMDocument();
            $docXML->encoding = 'UTF-8';
            $docXML->formatOutput = true;
            $xpath = new \DomXPath($docXML);
            $termsofuse = $federation->getTou();

            if (!empty($termsofuse)) {
                $termsofuse = "TERMS OF USE\n" . $termsofuse;
                $termsofuse = h_metadataComment($termsofuse); 
                $comment = $docXML->createComment($termsofuse);
                $docXML->appendChild($comment);
            }
            /**
             * get metadata namespaces from metadata_elements_helper
             */
            $namespaces = h_metadataNamespaces();
            foreach($namespaces as $key=>$value)
            {
                $xpath->registerNamespace($key,$value);
            }
            $Entities_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntitiesDescriptor');
            $Entities_Node->setAttribute('Name', $federation->getUrn());
            $validfor = new \DateTime("now");
            $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
            $validuntil = $validfor->format('Y-m-d');
            $Entities_Node->setAttribute('validUntil', $validuntil . "T00:00:00Z");

            /**
             * @todo ValidUntil
             */
            if ($type === 'all') {
                log_message('debug' ,'Genereate for all entities');
                for ($i = 0; $i < $members_count; $i++) {
                    if($members->get($members_keys['' . $i . ''])->getAvailable())
                    {
                        $members->get($members_keys['' . $i . ''])->getProviderToXML($Entities_Node,$options);
                    }
                }
            } else {
                foreach ($members as $key) {
                    if ($key->getAvailable() && (($key->getType() === $type) or ($key->getType() === 'BOTH'))) {
                        $key->getProviderToXML($Entities_Node,$options);
                    }
                }
            }
            $docXML->appendChild($Entities_Node);

            $data['out'] = $docXML->saveXML();


            $this->load->view('metadata_view', $data);
        }
    }
    public function federationexport($federationName, $t = NULL) {
        $data = array();
        $name = base64url_decode($federationName);
        if (!empty($t) AND (($t == 'IDP') OR ($t == 'SP') OR ($t == 'idp') OR ($t == 'sp'))) {
            $type = strtoupper($t);
        } else {
            $type = 'all';
        }


        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('name' => $name, 'is_lexport'=>TRUE));


        if (empty($federation)) {
            show_404('page', 'log_error');
        } else {
            /**
             * check if federation is active
             */
            $isactive = $federation->getActive();
            if (empty($isactive)) {
                /**
                 * dont display metadata if federation is inactive
                 */
                show_error('federation is not active', 404);
            }

            //$tmp_cnt = new models\Contacts;
            //$contacts = $tmp_cnt->getContacts();
           
            /**
             * check if required attribute must be added to federated metadata 
             */
            $include_attrs = $federation->getAttrsInmeta();
            $reqattrs_by_fed = null;
            $options = array();
            if($include_attrs)
            {
              $options['attrs'] = 1;
              $attrfedreq_tmp = new models\AttributeRequirements;
              $reqattrs_by_fed = $attrfedreq_tmp->getRequirementsByFed($federation);
              if(!empty($reqattrs_by_fed))
              {
                 $options['fedreqattrs'] = $reqattrs_by_fed ;
              }
            }

            $members = $federation->getMembers();
            $members_count = $members->count();
            $members_keys = $members->getKeys();
            log_message('debug', 'no federation members: ' . $members_count);

            //$count_members = count($members);
            $docXML = new \DOMDocument();
            $docXML->encoding = 'UTF-8';
            $docXML->formatOutput = true;
            $xpath = new \DomXPath($docXML);
            $termsofuse = $federation->getTou();
            
            $topcomment = "\n===============================================================\n= Federation metadata containing only localy managed entities.=\n===============================================================\n";
            $tcomment  = $docXML->createComment($topcomment);
            $docXML->appendChild($tcomment);
            if (!empty($termsofuse)) {
                $termsofuse = "TERMS OF USE\n" . $termsofuse;
                $termsofuse = h_metadataComment($termsofuse); 
                $comment = $docXML->createComment($termsofuse);
                $docXML->appendChild($comment);
            }
            /**
             * get metadata namespaces from metadata_elements_helper
             */
            $namespaces = h_metadataNamespaces();
            foreach($namespaces as $key=>$value)
            {
                $xpath->registerNamespace($key,$value);
            }
            $Entities_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntitiesDescriptor');
            $Entities_Node->setAttribute('Name', $federation->getUrn());
            $validfor = new \DateTime("now");
            $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
            $validuntil = $validfor->format('Y-m-d');
            $Entities_Node->setAttribute('validUntil', $validuntil . "T00:00:00Z");

            /**
             * @todo ValidUntil
             */
            if ($type == 'all') {
                for ($i = 0; $i < $members_count; $i++) {
                    if($members->get($members_keys['' . $i . ''])->getLocalAvailable())
                    {
                        $members->get($members_keys['' . $i . ''])->getProviderToXML($Entities_Node,$options);
                    }
                }
            } else {
                foreach ($members as $key) {
                    if ($key->getLocalAvailable() && (($key->getType() == $type) or ($key->getType() == 'BOTH'))) {
                        $key->getProviderToXML($Entities_Node,$options);
                    }
                }
            }
            $docXML->appendChild($Entities_Node);

            $data['out'] = $docXML->saveXML();


            $this->load->view('metadata_view', $data);
        }
    }

    public function service($entityId,$m = null) {
        if(!empty($m) && $m != 'metadata.xml')
        {
             show_error('Request not allowed',403);
        }
        $data = array();

        $name = base64url_decode($entityId);
        $options = array();
        $entity = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $name));
        if (!empty($entity))
         {
                $y = $entity->getProviderToXML($parent=null,$options);
                if (empty($y))
                {
                    log_message('error',  'Got empty xml form Provider model');
                    log_message('error',  "Service metadata for " . $entity->getEntityId() . " couldn't be generated");
                    show_error("Metadata for " . $entity->getEntityId() . " couldn't be generated", 503);
                 }
                else
                {
                   $data['out'] = $y->saveXML();
                   $this->load->view('metadata_view', $data);
                }
        } else {
            log_message('debug', 'Identity Provider not found');
            show_error('Identity Provider not found', 404);
        }
    }

    public function circle($entityId, $m = NULL) {
        if(!empty($m) && $m != 'metadata.xml')
        {
            show_error('Request not allowed',403);
        }
        $data = array();
        $name = base64url_decode($entityId);
        $tmp = new models\Providers;
        $me = $tmp->getOneByEntityId($name);
        if (empty($me)) {
            log_message('debug', 'Failed generating circle metadata for ' . $name);
            show_error('unknown provider', 404);
            return;
        }
        $p = new models\Providers;
        $p1 = $p->getCircleMembers($me);
        if (empty($p1)) {
            show_error('empty', 404);
            return;
        }

        $docXML = new \DOMDocument();
        $docXML->encoding = 'UTF-8';
        $docXML->formatOutput = true;

        $xpath = new \DomXPath($docXML);
        $namespaces = h_metadataNamespaces();
        foreach($namespaces as $key=>$value)
        {
              $xpath->registerNamespace($key,$value);
        }
        $Entities_Node = $docXML->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntitiesDescriptor');
        $validfor = new \DateTime("now");
        $validfor->modify('+' . $this->config->item('metadata_validuntil_days') . ' day');
        $validuntil = $validfor->format('Y-m-d');
        $Entities_Node->setAttribute('validUntil', $validuntil . "T00:00:00Z");
        $Entities_Node->setAttribute('Name', "circle:" . $me->getEntityId());

        foreach ($p1 as $v) {
            if($v->getAvailable())
            {
                $v->getProviderToXML($Entities_Node);
            }
        }
        $docXML->appendChild($Entities_Node);
        $this->output->set_content_type('text/xml');
        $data['out'] = $docXML->saveXML();
        $this->load->view('metadata_view', $data);
    }

}
