<?php

namespace models;

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
 * Trackers Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Trackers {

    protected $em;
    protected $timezone;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->timezone = new \DateTimeZone('UTC');
    }

    public function getArpDownloaded(Provider $provider)
    {
        $resourcename = $provider->getEntityId();
        $track = $this->em->getRepository("models\Tracker")->findBy(array('resourcetype' => 'idp', 'subtype' => 'arp_download', 'resourcename' => $resourcename), array('createdAt' => 'DESC'), '10');
        return $track;
    }

    public function getArpDownloadedByTime($d)
    {
        $datetime = new \DateTime('now',$this->timezone);
        $datetime->modify('- ' . $d . ' minutes');
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('models\Tracker', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'resourcetype', 'resourcetype');
        $rsm->addFieldResult('u', 'subtype', 'subtype');
        $rsm->addFieldResult('u', 'resourcename', 'resourcename');
        $rsm->addFieldResult('u', 'created_at', 'createdAt');
        $query = $this->em->createNativeQuery('SELECT id,resourcetype,resourcename,created_at,subtype from tracker where resourcetype = \'idp\' and subtype = \'arp_download\' and created_at > (?)', $rsm);
        $query->setParameter(1, $datetime);
        $result = $query->execute();
        return $result;
    }

    public function getProviderModifications(Provider $provider, $count)
    {
        $resourcename = $provider->getEntityId();
        $tracks = $this->em->getRepository("models\Tracker")->findBy(array('subtype' => 'modification', 'resourcename' => $resourcename, 'resourcetype' => array('idp', 'sp', 'both', 'ent')), array('createdAt' => 'DESC'), $count);
        return $tracks;
    }

}
