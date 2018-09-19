<?php
namespace models;
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\ORM\Query\ResultSetMapping;

/**
 * @package     Jagger
 * @author      Middleware Team HEAnet
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 */
class Providers
{
    protected $providers;
    protected $em;
    protected $ci;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function getTrustedActiveFeds(Provider $provider) {
        $feds = new \Doctrine\Common\Collections\ArrayCollection();
        $query = $this->em->createQuery("SELECT m,f FROM models\FederationMembers m JOIN m.federation f WHERE m.provider = ?1 AND m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND f.is_active = '1'");
        $query->setParameter(1, $provider->getId());
        $result = $query->getResult();
        foreach ($result as $r) {
            $feds->add($r->getFederation());
        }
        return $feds;
    }

    public function getSPsForArp(Provider $provider) {
        $query1 = $this->em->createQuery("SELECT partial m.{id, federation},partial f.{id} FROM models\FederationMembers m JOIN m.federation f WHERE m.provider = ?1 AND m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND f.is_active = '1'");
        $query1->setParameter(1, $provider->getId());
        $query1->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result1 = $query1->getResult();
        $feds = array();
        foreach ($result1 as $r) {
            $feds[] = $r->getFederation()->getId();
        }
        if (count($feds) == 0) {
            return array();
        }
        $query = $this->em->createQuery("SELECT partial p.{id, entityid,type,ldisplayname,displayname,lname, name},e,partial m.{id, provider, federation},partial f.{id} FROM models\Provider p LEFT JOIN p.membership m LEFT JOIN m.federation f LEFT JOIN p.extend e WHERE m.federation IN (:feds) AND  m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND  p.type IN ('SP','BOTH') AND  p.is_active = '1' AND p.is_approved = '1'");
        $query->setParameter('feds', $feds);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        $r2 = new \Doctrine\Common\Collections\ArrayCollection;
        foreach ($result as $r) {
            $r2->add($r);
        }
        return $r2;
    }

    public function getSPsForArpIncEntCats(Provider $provider, array $excludedEntityIDs) {
        $query1 = $this->em->createQuery("SELECT partial m.{id, federation},partial f.{id} FROM models\FederationMembers m JOIN m.federation f WHERE m.provider = ?1 AND m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND f.is_active = '1'");
        $query1->setParameter(1, $provider->getId());
        $query1->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result1 = $query1->getResult();
        $feds = array();
        foreach ($result1 as $r) {
            $feds[] = $r->getFederation()->getId();
        }
        if (count($feds) == 0) {
            return array();
        }
        if (count($excludedEntityIDs) > 0) {
            $query = $this->em->createQuery("SELECT partial p.{id, entityid,type},partial m.{id, provider, federation},partial f.{id},c FROM models\Provider p LEFT JOIN p.membership m LEFT JOIN m.federation f LEFT JOIN p.coc c WHERE m.federation IN (:feds) AND  m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND p.entityid NOT IN (:excludedents) AND  p.type IN ('SP','BOTH') AND  p.is_active = '1' AND p.is_approved = '1'");
            $query->setParameter('excludedents', $excludedEntityIDs);
        } else {
            $query = $this->em->createQuery("SELECT partial p.{id, entityid,type},partial m.{id, provider, federation},partial f.{id},c FROM models\Provider p LEFT JOIN p.membership m LEFT JOIN m.federation f LEFT JOIN p.coc c WHERE m.federation IN (:feds) AND  m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND p.type IN ('SP','BOTH') AND  p.is_active = '1' AND p.is_approved = '1'");
        }

        $query->setParameter('feds', $feds);

        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        $r2 = new \Doctrine\Common\Collections\ArrayCollection;
        foreach ($result as $r) {
            $r2->add($r);
        }

        return $r2;
    }


    public function getAllIdPsForWayf() {
        $currentTime = new \DateTime("now", new \DateTimeZone('UTC'));
        $query = $this->em->createQuery("SELECT partial p.{id,entityid,type,ldisplayname,lname,name,displayname},e FROM models\Provider p LEFT JOIN p.extend e WHERE p.is_active = '1' AND p.is_approved = '1' AND (p.validto is null OR p.validto >= :now) AND (p.validfrom is null OR p.validfrom <= :now) AND p.type IN ('IDP','BOTH')");
        $query->setParameter('now', $currentTime);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();

        return $result;
    }


    public function getIdPsForWayf(Provider $provider) {
        $spid = $provider->getId();
        $startTime = microtime(true);
        $query1 = $this->em->createQuery("SELECT m,f FROM models\FederationMembers m JOIN m.federation f WHERE m.provider = ?1 AND m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND f.is_active = '1'");
        $query1->setParameter(1, $spid);
        $query1->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result1 = $query1->getResult();
        $feds = array();
        foreach ($result1 as $r) {
            $feds[] = $r->getFederation()->getId();
        }
        if (count($feds) == 0) {
            return array();
        }
        $currentTime = new \DateTime("now", new \DateTimeZone('UTC'));
        $query = $this->em->createQuery("SELECT partial p.{id,entityid,type,ldisplayname,lname,name,displayname},e, partial m.{id} FROM models\Provider p LEFT JOIN p.extend e LEFT JOIN p.membership m LEFT JOIN m.federation f  WHERE m.federation IN (:feds) AND  m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND p.id != ?2 AND p.is_active = '1' AND p.is_approved = '1' AND (p.validto is null OR p.validto >= :now) AND (p.validfrom is null OR p.validfrom <= :now) AND p.type IN ('IDP','BOTH')");
        $query->setParameter('feds', $feds);
        $query->setParameter(2, $spid);
        $query->setParameter('now', $currentTime);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        $endTime = microtime(true);
        $execTime = $endTime - $startTime;
        \log_message('info', __METHOD__ . ' time execution: ' . $execTime);
        return $result;
    }

   

    /**
     * get all trusted entites excluding the same type as param including their feds
     */
    public function getTrustedServicesWithFeds(Provider $provider) {
        $type = $provider->getType();
        if ($type === 'IDP') {

            $rtype = array('SP', 'BOTH');
        } elseif ($type === 'SP') {

            $rtype = array('IDP', 'BOTH');
        } else {

            $rtype = array('IDP', 'SP', 'BOTH');
        }
        $feds = array();
        $membership = $provider->getMembership();
        foreach ($membership as $m) {
            \log_message('debug', __METHOD__ . ': disabled: ' . (int)$m->isDisabled() . ' : ' . $m->getFederation()->getName());
            $fullyEnabled = $m->isFinalMembership();
            \log_message('debug', __METHOD__ . ': fullyEnabled: ' . $fullyEnabled . ' : ' . $m->getFederation()->getName());
            $federationEnabled = $m->getFederation()->getActive();
            if ($fullyEnabled && $federationEnabled) {
                $feds[] = '' . $m->getFederation()->getId() . '';
            }
        }
        if (count($feds) == 0) {
            return array();
        }
        $query = $this->em->createQuery("SELECT p,m, f FROM models\Provider p JOIN p.membership m JOIN m.federation f  WHERE m.federation IN (:feds) AND  m.joinstate != '2' AND m.isDisabled = '0' AND m.isBanned='0' AND p.id != :provid AND p.is_active = '1' AND p.is_approved = '1' AND p.type IN (:types)");
        $query->setParameter('feds', $feds);
        $query->setParameter('types', $rtype);
        $query->setParameter('provid', $provider->getId());
        $result = $query->getResult();

        return $result;
    }

    public function getCircleMembersLight(Provider $provider) {
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
        $type = $provider->getType();
        if ($type === 'IDP') {
            $types = array('SP', 'BOTH');
        } elseif ($type === 'SP') {
            $types = array('IDP', 'BOTH');
        } else {
            $types = array('IDP', 'SP', 'BOTH');
        }
        $federations = $provider->getFederations();
        $feds = array();
        foreach ($federations as $f) {
            if ($f->getActive() === true) {
                $feds[] = $f->getId();
            }
        }
        if (count($feds) > 0) {

            $query = $this->em->createQuery("SELECT p,a FROM models\Provider p LEFT JOIN p.membership a WHERE p.type IN (:types) AND a.federation IN (:feds) AND a.joinstate != '2' AND a.isBanned='0' AND a.isDisabled = '0' ORDER BY p.name ASC");
            $query->setParameter('types', $types);
            $query->setParameter('feds', $feds);
            $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

            return $query->getResult();
        }

        return $this->providers;
    }

    public function getCircleMembersSP(Provider $provider) {
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
        /**
         * @todo add confition only active federations
         */
        $federations = $provider->getFederations();

        foreach ($federations->getValues() as $f) {
            if ($f->getActive()) {

                $y = $f->getMembers();
                foreach ($y->getKeys() as $key) {
                    $type = $y->get($key)->getType();
                    if ($type == 'SP' || $type == 'BOTH') {
                        $this->providers->set($key, $y->get($key));
                    }
                }
            }
        }

        return $this->providers;
    }

    public function getCircleMembersIDP(Provider $provider, $federations = null, $onlylocal = false) {
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
        if ($federations === null) {
            $federations = $provider->getFederations();
        }

        foreach ($federations->getValues() as $f) {
            if ($f->getActive()) {
                $doFilter['type'] = array('IDP', 'BOTH');
                $doFilter['local'] = array('1');
                if (!$onlylocal) {
                    $doFilter['local'][] = '0';
                }

                $y = $f->getMembers()->filter(
                    function (Provider $entry) use ($doFilter) {
                        return (in_array($entry->getType(), $doFilter['type']) && in_array($entry->getLocal(), $doFilter['local']));
                    }
                );
                foreach ($y->getKeys() as $key) {
                    $this->providers->set($key, $y->get($key));
                }
            }
        }

        return $this->providers;
    }

    public function getAll() {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array(), array('name' => 'ASC'));

        return $this->providers;
    }

    public function getLocalProviders() {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true), array('name' => 'ASC'));

        return $this->providers;
    }

    public function getLocalPublicVisibleProviders() {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => true, 'hidepublic' => false, 'is_active' => true), array('name' => 'ASC'));

        return $this->providers;
    }

    public function getIdps() {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('IDP', 'BOTH')), array('name' => 'ASC'));

        return $this->providers;
    }

    public function getIdpsLight() {
        log_message('debug', 'run: models\Providers::getIdpsLight()');
        $dql = "SELECT p,a FROM models\Provider p LEFT JOIN p.extend a  WHERE p.type IN ('IDP','BOTH') ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }

    public function getIdpsLightLocal() {
        log_message('debug', 'run: models\Providers::getIdpsLightLocal()');
        $dql = "SELECT p,a FROM models\Provider p LEFT JOIN p.extend a WHERE p.type IN ('IDP','BOTH') AND p.is_local = '1' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }

    public function getIdpsLightExternal() {
        log_message('debug', 'run: models\Providers::getIdpsLightExternal()');
        $dql = "SELECT p,a FROM models\Provider p LEFT JOIN p.extend a WHERE p.type IN ('IDP','BOTH') AND p.is_local = '0' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }

    public function getProvidersListPartialInfo($type) {
        $dql = "SELECT partial p.{id,entityid,name,lname,displayname,ldisplayname,type,helpdeskurl,lhelpdeskurl,registerdate,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c, a FROM models\Provider p LEFT OUTER JOIN p.contacts c LEFT OUTER JOIN p.extend a  WHERE p.type IN (?1,?2)";
        $query = $this->em->createQuery($dql);
        $query->setParameter(1, '' . $type . '');
        $query->setParameter(2, 'BOTH');
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();
    }

    public function getProvidersPartialWithCerts($type = null){
        if($type === null){
            $dql = "SELECT partial p.{id,entityid,type,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c FROM models\Provider p LEFT OUTER JOIN p.certificates c";
            $query = $this->em->createQuery($dql);
        }
        else {
            $dql = "SELECT partial p.{id,entityid,type,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c FROM models\Provider p LEFT OUTER JOIN p.certificates c  WHERE p.type IN (?1,?2)";
            $query = $this->em->createQuery($dql);
            $query->setParameter(1, '' . $type . '');
            $query->setParameter(2, 'BOTH');
        }
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();
    }

    public function getLocalProvidersPartialWithCerts($type = null){
        if($type === null){
            $dql = "SELECT partial p.{id,entityid,type,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c FROM models\Provider p LEFT OUTER JOIN p.certificates c WHERE p.is_local = '1' ";
            $query = $this->em->createQuery($dql);
        }
        else {
            $dql = "SELECT partial p.{id,entityid,type,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c FROM models\Provider p LEFT OUTER JOIN p.certificates c  WHERE p.is_local = '1' AND p.type IN (?1,?2)";
            $query = $this->em->createQuery($dql);
            $query->setParameter(1, '' . $type . '');
            $query->setParameter(2, 'BOTH');
        }
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();
    }
    public function getExtProvidersPartialWithCerts($type = null){
        if($type === null){
            $dql = "SELECT partial p.{id,entityid,type,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c FROM models\Provider p LEFT OUTER JOIN p.certificates c WHERE p.is_local = '0' ";
            $query = $this->em->createQuery($dql);
        }
        else {
            $dql = "SELECT partial p.{id,entityid,type,validfrom,validto,is_approved,is_active,is_locked,is_local,hidepublic},c FROM models\Provider p LEFT OUTER JOIN p.certificates c  WHERE p.is_local = '0' AND p.type IN (?1,?2)";
            $query = $this->em->createQuery($dql);
            $query->setParameter(1, '' . $type . '');
            $query->setParameter(2, 'BOTH');
        }
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();
    }

    public function getSPsEntities() {
        $dql = "SELECT partial p.{id,entityid} FROM models\Provider p WHERE p.type IN (?1,?2)";
        $query = $this->em->createQuery($dql);
        $query->setParameter(1, 'SP');
        $query->setParameter(2, 'BOTH');
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();

    }

    public function getPublicIdpsInNative() {

        $rsm = new ResultSetMapping;

        $rsm->addEntityResult('models\Provider', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'entityid', 'entityid');
        $rsm->addFieldResult('u', 'helpdeskurl', 'helpdeskurl');
        $rsm->addFieldResult('u', 'is_active', 'is_active');
        $rsm->addFieldResult('u', 'is_approved', 'is_approved');
        $rsm->addFieldResult('u', 'validfrom', 'validfrom');
        $rsm->addFieldResult('u', 'validto', 'validto');
        $rsm->addFieldResult('u', 'displayname', 'displayname');
        $rsm->addFieldResult('u', 'contacts', 'contacts');
        $query = $this->em->createNativeQuery('SELECT id,name,entityid,helpdeskurl,displayname,is_active,is_approved,validfrom,validto FROM provider WHERE type IN (?,?) AND is_local = \'1\' AND hidepublic = \'0\' AND is_active = \'1\' ORDER BY name ASC', $rsm);
        $query->setParameter(1, 'IDP');
        $query->setParameter(2, 'BOTH');
        $this->providers = $query->execute();

        return $this->providers;
    }

    public function getPublicSpsInNative() {

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('models\Provider', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'entityid', 'entityid');
        $rsm->addFieldResult('u', 'helpdeskurl', 'helpdeskurl');
        $rsm->addFieldResult('u', 'displayname', 'displayname');
        $rsm->addFieldResult('u', 'is_active', 'is_active');
        $rsm->addFieldResult('u', 'is_approved', 'is_approved');
        $rsm->addFieldResult('u', 'validfrom', 'validfrom');
        $rsm->addFieldResult('u', 'validto', 'validto');
        $rsm->addFieldResult('u', 'contacts', 'contacts');
        $query = $this->em->createNativeQuery('SELECT id,name,entityid,helpdeskurl,displayname,is_active,is_approved,validfrom,validto FROM provider WHERE type IN (?,?) AND is_local = \'1\' AND hidepublic = \'0\' AND is_active = \'1\' ORDER BY name ASC', $rsm);
        $query->setParameter(1, 'SP');
        $query->setParameter(2, 'BOTH');
        $this->providers = $query->execute();

        return $this->providers;
    }

    public function getLocalIdpsIdsEntities() {
        $query = $this->em->createQuery("SELECT p.id, p.entityid from models\Provider as p  WHERE p.is_local = '1' and p.type IN ('IDP','BOTH') ORDER by p.entityid ASC");
        return $query->getResult();
    }

    public function getLocalIdsEntities() {
        $query = $this->em->createQuery("

             SELECT p.id, p.entityid, p.name from models\Provider as p  WHERE p.is_local = '1'  ORDER by p.entityid ASC
         ");
        $result = $query->getResult();

        return $result;
    }

    public function getSpsByEntities($entityidsInArray) {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('SP', 'BOTH'), 'entityid' => $entityidsInArray));

        return $this->providers;
    }

    public function getIdpsByEntities($entityidsInArray) {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('IDP', 'BOTH'), 'entityid' => $entityidsInArray));

        return $this->providers;
    }

    public function getActiveFederationmembersForExport(Federation $federation, $excludeType = null) {
        if ($excludeType === null) {
            $query = $this->em->createQuery("SELECT p,m FROM models\Provider p LEFT JOIN p.membership m WHERE m.federation = ?1  AND m.joinstate IN ('0','1') AND m.isBanned='0' AND m.isDisabled='0' AND p.is_active='1' AND p.is_approved='1' AND p.is_local='1' AND (p.validto is null OR p.validto > :today) AND (p.validfrom is null OR p.validfrom < :today)");
        } else {
            $query = $this->em->createQuery("SELECT p,m FROM models\Provider p LEFT JOIN p.membership m WHERE m.federation = ?1  AND m.joinstate IN ('0','1') AND m.isBanned='0' AND m.isDisabled='0' AND p.type != :type AND  p.is_active='1' AND p.islocal='1' AND p.is_approved='1' AND (p.validto is null OR p.validto > :today) AND (p.validfrom is null OR p.validfrom < :today)");

            $query->setParameter('type', strtoupper($excludeType));
        }
        $query->setParameter(1, $federation->getId());
        $query->setParameter('today', new \DateTime("now"));

        return $query->getResult();

    }

    public function getActiveFederationMembers(Federation $federation, $excludeType = null) {
        if ($excludeType === null) {
            $query = $this->em->createQuery("SELECT p,m FROM models\Provider p LEFT JOIN p.membership m WHERE m.federation = ?1  AND m.joinstate != '2' AND m.isBanned='0' AND m.isDisabled='0' AND p.is_active='1' AND p.is_approved='1' AND (p.validto is null OR p.validto > :today) AND (p.validfrom is null OR p.validfrom < :today)");
        } else {
            $query = $this->em->createQuery("SELECT p,m FROM models\Provider p LEFT JOIN p.membership m WHERE m.federation = ?1  AND m.joinstate != '2' AND m.isBanned='0' AND m.isDisabled='0' AND p.type != :type AND  p.is_active='1' AND p.is_approved='1' AND (p.validto is null OR p.validto > :today) AND (p.validfrom is null OR p.validfrom < :today)");

            $query->setParameter('type', strtoupper($excludeType));
        }
        $query->setParameter(1, $federation->getId());
        $query->setParameter('today', new \DateTime("now"));

        return $query->getResult();
    }


    public function getActiveMembersOfFederations($federations, $excludeType = null) {
        if ($excludeType === null) {
            $query = $this->em->createQuery("SELECT p,m FROM models\Provider p LEFT JOIN p.membership m WHERE m.federation in (:feds)  AND m.joinstate != '2' AND m.isBanned='0' AND m.isDisabled='0' AND p.is_active='1' AND p.is_approved='1' AND (p.validto is null OR p.validto > :today) AND (p.validfrom is null OR p.validfrom < :today)");
        } else {
            $query = $this->em->createQuery("SELECT p,m FROM models\Provider p LEFT JOIN p.membership m WHERE m.federation in (:feds)  AND m.joinstate != '2' AND m.isBanned='0' AND m.isDisabled='0' AND p.type != :type AND  p.is_active='1' AND p.is_approved='1' AND (p.validto is null OR p.validto > :today) AND (p.validfrom is null OR p.validfrom < :today)");

            $query->setParameter('type', strtoupper($excludeType));
        }
        $query->setParameter('feds', $federations);
        $query->setParameter('today', new \DateTime("now"));

        return $query->getResult();
    }

    public function getFederationMembersInLight(Federation $federation) {

        $query = $this->em->createQuery("SELECT PARTIAL p.{id,entityid,name,lname,displayname,ldisplayname,type,validfrom,validto,is_approved,is_active,is_locked,is_local,registerdate},m,e FROM models\Provider p LEFT JOIN p.membership m LEFT JOIN p.extend e WHERE m.federation = ?1  AND m.joinstate != '2' AND p.is_approved = '1'");
        $query->setParameter(1, $federation->getId());
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }


    public function getOneIdpById($id) {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('type' => array('IDP', 'BOTH'), 'id' => $id));

        return $this->providers;
    }

    public function getOneById($id) {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));

        return $this->providers;
    }

    public function getOneByEntityId($id) {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $id));

        return $this->providers;
    }

    public function getOneIdpByEntityId($id) {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $id, 'type' => array('IDP', 'BOTH')));

        return $this->providers;
    }

    public function getOneSpByEntityId($id) {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $id, 'type' => array('SP', 'BOTH')));

        return $this->providers;
    }

    public function getOneSpById($id) {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('type' => array('SP', 'BOTH'), 'id' => $id));

        return $this->providers;
    }

    public function getSps() {
        log_message('debug', 'run: models\Providers::getSps()');
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('SP', 'BOTH')), array('name' => 'ASC'));

        return $this->providers;
    }

    public function getSpsLight() {
        log_message('debug', 'run: models\Providers::getSpsLight()');
        $dql = "SELECT p,a FROM models\Provider p LEFT JOIN p.extend a WHERE p.type IN ('SP','BOTH') ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }

    public function getSpsLightExternal() {
        log_message('debug', 'run: models\Providers::getSpsLightExternal()');
        $dql = "SELECT p,a FROM models\Provider p LEFT JOIN p.extend a WHERE p.type IN ('SP','BOTH') AND p.is_local = '0' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }

    public function getSpsLightLocal() {
        log_message('debug', 'run: models\Providers::getSpsLightLocal()');
        $dql = "SELECT p,a FROM models\Provider p LEFT JOIN  p.extend a  WHERE p.type IN ('SP','BOTH') AND p.is_local = '1' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getResult();
    }

    public function getProviders() {
        return $this->providers;
    }


}
