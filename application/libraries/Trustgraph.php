<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


class Trustgraph
{
    protected $ci;
    protected $em;
    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function genTrustLight(){
        /**
         * @var models\FederationMembers[] $allFedsMembers
         */
        $allFedsMembers = $this->em->getRepository('models\FederationMembers')->findAll();
        $result = array();
        foreach ($allFedsMembers as $m) {
            if ($m->isBanned() === true || $m->isDisabled() === true || $m->getJoinState() === 2) {
                continue;
            }
            if ($m->getProvider()->getAvailable() !== true) {
                continue;
            }
            $entityID = $m->getProvider()->getEntityId();
            $sha1entity = sha1($entityID);
            if (!array_key_exists($sha1entity, $result)) {
                $result[$sha1entity] = array();
            }

            if (!array_key_exists('entityid', $result[$sha1entity])) {
                $result[$sha1entity]['entityid'] = $entityID;
            }
            $result[$sha1entity]['feds'][] = $m->getFederation()->getId();
        }
        return $result;
    }


}