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
 * Tracker Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Tracker {

    protected $ci;
    protected $em;

    function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    /**
     * sync_with_db - it should be always false. 
     * you can set to true if u dont have any other db operations in you controller/libr etc
     */
    function save_track($resourcetype, $subtype, $resourcename, $details, $sync_with_db = false) {
        $track = new models\Tracker;
        $current_user = $this->ci->j_auth->current_user();
        $track->setUser($current_user);
        $track->setResourceType($resourcetype);
        $track->setSubType($subtype);
        $track->setDetail($details);
        $track->setResourceName($resourcename);
        $this->em->persist($track);
        if ($sync_with_db === TRUE) {
            $this->em->flush();
        }
    }
    function renameProviderResourcename($oldname, $newname)
    {
       $q = $this->em->createQuery('update models\Tracker t set t.resourcename = ?1 where t.resourcename = ?2 and t.resourcetype in (\'idp\',\'sp\',\'both\',\'ent\')');
       $q->setParameter(1,$newname);
       $q->setParameter(2,$oldname);
       $numUpdated = $q->execute();

    }
    
    function remove_ProviderTrack($entityid)
    {
       $tracks = $this->em->getRepository("models\Tracker")->findBy(array('resourcename'=>$entityid,'resourcetype'=>array('idp','sp') ));
       if(!empty($tracks))
       {
          foreach($tracks as $t)
          {
              $this->em->remove($t);
          }
       }
       return true;
    }

}
