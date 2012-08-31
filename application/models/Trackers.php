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

class Trackers 
{

    protected $em;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
    }

	public function getArpDownloaded(Provider $provider)
	{
		$resourcename = $provider->getEntityId();
		$track = $this->em->getRepository("models\Tracker")->findBy(array('resourcename'=>$resourcename,'resourcetype'=>'idp','subtype'=>'arp_download'),array('createdAt'=>'DESC'), '10');
		return $track;
	
	}
	public function getProviderModifications(Provider $provider, $count)
	{
		$resourcename = $provider->getEntityId();
		$tracks = $this->em->getRepository("models\Tracker")->findBy(array('resourcename'=>$resourcename, 'subtype'=>'modification','resourcetype'=>array('idp','sp','both')),array('createdAt'=>'DESC'), $count);
		return $tracks;
	}


}
