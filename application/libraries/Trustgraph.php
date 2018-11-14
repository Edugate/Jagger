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
    public function getTrustGraphLight(){

        $providers = new models\Providers();
        $query = $providers->getTrustgraph();
        $result = [];
        foreach ($query as $q){
            $entityID = $q->getEntityId();
            $sha1entity = sha1($entityID);
            $result[$sha1entity] = array(
                'entityid' => $entityID,
                'feds'=>[]);
            $feds = $q->getMembership();
            foreach ($feds as $f){
               $result[$sha1entity]['feds'][] = $f->getFederation()->getId();
            }
        }
        return $result;
    }


}