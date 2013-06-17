<?php

namespace models;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\ORM\Query\ResultSetMapping;

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
 * Providers Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Providers {

    protected $providers;
    protected $em;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;

        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function  getCircleMembersCached(Provider $provider)
    {
        $providerid = $provider->getId();
        $federations = $provider->getFederations();
        $fedids = array();
        foreach($federations as $v)
        {
            $fedids[] = $v->getId();
        }
        $in = implode(',',array_values($fedids));
        $query=$this->em->createQuery('SELECT u,m,g,c FROM models\Provider u JOIN u.metadata m JOIN u.contacts g JOIN  u.certificates c  JOIN u.federations  a  WHERE a.id IN ('.$in.') '); 
        $query->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache());
        $query->useResultCache(true)
              ->setResultCacheLifeTime($seconds = 600);
        $query->setResultCacheId('providermembers_'.$providerid);
        $providers = $query->getResult();
        return $providers;
    }

     public function getCircleMembers(Provider $provider)
     {
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
         $federations = $provider->getFederations();
        foreach ($federations->getValues() as $f)
         {
            $y = $f->getMembers();
            foreach ($y->getKeys() as $key)
            {
                $this->providers->set($key, $y->get($key));
            }
         }

        return $this->providers;
     }
     /**
      * getting trusted entities by given provider
      * if provider is IDP then result give only trusted SPs
      * if provider is SP then result give only trusted IDPs
      */
     public function getCircleMembersByType(Provider $provider)
     {
        $entype = $provider->getType();
        $federations = $provider->getFederations();
        $feds = array();
        if(!empty($federations))
        {
           foreach($federations as $f)
           {
             $feds[] = $f->getId();
           }
        }
        if(count($feds) == 0 )
        {
            return array();
        }
        $in = implode(',',array_values($feds));
        $sqlsuffix = ' ';
        if($entype === 'IDP')
        {
           $sql = "SELECT u  FROM models\Provider u WHERE u.type IN ('SP','BOTH') AND (";
           $sqlsuffix = ' ) ';
        }
        elseif($entype === 'SP')
        {
           $sql = "SELECT u  FROM models\Provider u WHERE u.type IN ('IDP','BOTH') AND (";
           $sqlsuffix = ' ) ';
        }
        else
        {
           $sql = 'SELECT u  FROM models\Provider u WHERE ';
        }
        foreach($feds as $key=>$value)
        {
            $temp[]= '?'.$key.' MEMBER OF u.federations ';
        }
        $sql .= implode(' OR ', $temp) . $sqlsuffix;
        
        $query = $this->em->createQuery($sql);
        foreach($feds as $key=>$value)
        {
            $query->setParameter($key, $value);
        }
        return $query->getResult(); 
     }

     public function getMultiFederationMembers($arrayb)
     {
        $dql = 'SELECT p,a,c,s,e   FROM models\Provider p LEFT JOIN p.metadata a LEFT JOIN p.contacts c LEFT JOIN p.certificates s  LEFT JOIN p.extend e WHERE ';
        foreach ($arrayb as $key=>$value)
        {
           $temp[]= '?'.$key.' MEMBER OF p.federations ';
        }
        $dql .= implode(" OR ", $temp);

        $query = $this->em->createQuery($dql);
        foreach($arrayb as $key=>$value)
        {
            $query->setParameter($key, $value);

        }
        return $query->getResult();

     }

    public function getCircleMembersSP(Provider $provider, $federations = null)
    {
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
        /**
         * @todo add confition only active federations
         */
        $federations = $provider->getFederations();

        foreach ($federations->getValues() as $f)
        {
            if ($f->getActive())
            {

                $y = $f->getMembers();
                foreach ($y->getKeys() as $key)
                {
                    $type = $y->get($key)->getType();
                    //  log_message('debug','TYPE::::: '.$type);
                    if ($type == 'SP' OR $type == 'BOTH')
                    {
                        $this->providers->set($key, $y->get($key));
                    }
                }
            }
        }
        return $this->providers;
    }

    public function getCircleMembersIDP(Provider $provider, $federations = null)
    {
        $this->providers = new \Doctrine\Common\Collections\ArrayCollection();
        /**
         * @todo add confition only active federations
         */
        $federations = $provider->getFederations();

        foreach ($federations->getValues() as $f)
        {
            if ($f->getActive())
            {

                $y = $f->getMembers();
                foreach ($y->getKeys() as $key)
                {
                    $type = $y->get($key)->getType();
                    //  log_message('debug','TYPE::::: '.$type);
                    if ($type == 'IDP' OR $type == 'BOTH')
                    {
                        $this->providers->set($key, $y->get($key));
                    }
                }
            }
        }
        return $this->providers;
    }

    public function getAll()
    {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array(), array('name' => 'ASC'));
        return $this->providers;
    }

    public function getLocalProviders()
    {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('is_local' => TRUE), array('name' => 'ASC'));
        return $this->providers;
    }

    public function getIdps()
    {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('IDP', 'BOTH')), array('name' => 'ASC'));
        return $this->providers;
    }
    
    public function getIdpsLight()
    {
        log_message('debug', 'run: models\Providers::getIdpsLight()');
        $dql = "SELECT p FROM models\Provider p WHERE p.type IN ('IDP','BOTH') ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();  
    }
    public function getIdpsLightLocal()
    {
        log_message('debug', 'run: models\Providers::getIdpsLightLocal()');
        $dql = "SELECT p FROM models\Provider p WHERE p.type IN ('IDP','BOTH') AND p.is_local = '1' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();  
    }
    public function getIdpsLightExternal()
    {
        log_message('debug', 'run: models\Providers::getIdpsLightExternal()');
        $dql = "SELECT p FROM models\Provider p WHERE p.type IN ('IDP','BOTH') AND p.is_local = '0' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();  
    }

    /**
     *  getIdps_inNative and getSps_inNative to display just the list of entities without details.
     *   it's faster than getIdps and getSps but its sesnsitive to database changes like column names etc
     */
    public function getIdps_inNative($local = null)
    {

        $rsm = new ResultSetMapping;

        $rsm->addEntityResult('models\Provider', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'entityid', 'entityid');
        $rsm->addFieldResult('u', 'helpdeskurl', 'helpdeskurl');
        $rsm->addFieldResult('u', 'homeurl', 'homeurl');
        $rsm->addFieldResult('u', 'is_active', 'is_active');
        $rsm->addFieldResult('u', 'is_approved', 'is_approved');
        $rsm->addFieldResult('u', 'validfrom', 'validfrom');
        $rsm->addFieldResult('u', 'validto', 'validto');
        $rsm->addFieldResult('u', 'displayname', 'displayname');
        $rsm->addFieldResult('u', 'contacts', 'contacts');
        if (!empty($local) && $local === TRUE)
        {
            $query = $this->em->createNativeQuery('SELECT id,name,entityid,helpdeskurl,homeurl,displayname,is_active,is_approved,validfrom,validto FROM provider WHERE type IN (?,?) AND is_local = \'1\' ORDER BY name ASC', $rsm);
        }
        else
        {
            $query = $this->em->createNativeQuery('SELECT id,name,entityid,helpdeskurl,homeurl,displayname,is_active,is_approved,validfrom,validto FROM provider WHERE type IN (?,?) ORDER BY name ASC', $rsm);
        }
        $query->setParameter(1, 'IDP');
        $query->setParameter(2, 'BOTH');
        $this->providers = $query->execute();

        return $this->providers;
    }

    public function getSps_inNative($local = null)
    {

        $rsm = new ResultSetMapping;

        $rsm->addEntityResult('models\Provider', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'entityid', 'entityid');
        $rsm->addFieldResult('u', 'helpdeskurl', 'helpdeskurl');
        $rsm->addFieldResult('u', 'homeurl', 'homeurl');
        $rsm->addFieldResult('u', 'displayname', 'displayname');
        $rsm->addFieldResult('u', 'is_active', 'is_active');
        $rsm->addFieldResult('u', 'is_approved', 'is_approved');
        $rsm->addFieldResult('u', 'validfrom', 'validfrom');
        $rsm->addFieldResult('u', 'validto', 'validto');
        $rsm->addFieldResult('u', 'contacts', 'contacts');
        if (!empty($local) && $local === TRUE)
        {
            $query = $this->em->createNativeQuery('SELECT id,name,entityid,helpdeskurl,homeurl,displayname,is_active,is_approved,validfrom,validto FROM provider WHERE type IN (?,?) AND is_local = \'1\' ORDER BY name ASC', $rsm);
            $query->setParameter(1, 'SP');
            $query->setParameter(2, 'BOTH');
        }
        else
        {
            $query = $this->em->createNativeQuery('SELECT id,name,entityid,helpdeskurl,homeurl,displayname,is_active,is_approved,validfrom,validto FROM provider WHERE type IN (?,?) ORDER BY name ASC', $rsm);
            $query->setParameter(1, 'SP');
            $query->setParameter(2, 'BOTH');
        }
        $this->providers = $query->execute();

        return $this->providers;
    }

    public function getSpsByEntities($entityids_in_array)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('SP', 'BOTH'), 'entityid' => $entityids_in_array));
        return $this->providers;
    }

    public function getIdpsByEntities($entityids_in_array)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('IDP', 'BOTH'), 'entityid' => $entityids_in_array));
        return $this->providers;
    }

    public function test_getIdps()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('models\Provider', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $query = $this->em->createNativeQuery('SELECT id,name FROM provider WHERE type = ?', $rsm);
        $query->setParameter(1, 'SP');

        $users = $query->getResult();

        return $users;
    }

    public function getOneIdpById($id)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('type' => array('IDP', 'BOTH'), 'id' => $id));
        return $this->providers;
    }

    public function getOneById($id)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
        return $this->providers;
    }

    public function getOneByEntityId($id)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $id));
        return $this->providers;
    }

    public function getOneIdpByEntityId($id)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $id, 'type' => array('IDP', 'BOTH')));
        return $this->providers;
    }

    public function getOneSpByEntityId($id)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $id, 'type' => array('SP', 'BOTH')));
        return $this->providers;
    }

    public function getOneSpById($id)
    {
        $this->providers = $this->em->getRepository("models\Provider")->findOneBy(array('type' => array('SP', 'BOTH'), 'id' => $id));
        return $this->providers;
    }

    public function getSps()
    {
        log_message('debug', 'run: models\Providers::getSps()');
        $this->providers = $this->em->getRepository("models\Provider")->findBy(array('type' => array('SP', 'BOTH')), array('name' => 'ASC'));
        return $this->providers;
    }
    public function getSpsLight()
    {
        log_message('debug', 'run: models\Providers::getSpsLight()');
        $dql = "SELECT p FROM models\Provider p WHERE p.type IN ('SP','BOTH') ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();
    }
    public function getSpsLightExternal()
    {
        log_message('debug', 'run: models\Providers::getSpsLightExternal()');
        $dql = "SELECT p FROM models\Provider p WHERE p.type IN ('SP','BOTH') AND p.is_local = '0' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();  
    }
    public function getSpsLightLocal()
    {
        log_message('debug', 'run: models\Providers::getSpsLightLocal()');
        $dql = "SELECT p FROM models\Provider p WHERE p.type IN ('SP','BOTH') AND p.is_local = '1' ORDER BY p.name ASC ";
        $query = $this->em->createQuery($dql);
        $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
        return $query->getResult();  
    }
    
    public function getProviders()
    {
        return $this->providers;
    }

    public function getFromXML($metadata)
    {
        $doc = \DOMDocument::loadXML($metadata);
        return $doc->saveXML($doc);
    }

}
