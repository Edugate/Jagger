<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Tracker
{

    protected $ci;
    protected $em;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    /**
     * sync_with_db - it should be always false.
     * you can set to true if u dont have any other db operations in you controller/libr etc
     */
    public function save_track($resourcetype, $subtype, $resourcename, $details, $sync_with_db = false) {
        $track = new models\Tracker;
        $current_user = $this->ci->jauth->getLoggedinUsername();
        $track->setUser($current_user);
        $track->setResourceType($resourcetype);
        $track->setSubType($subtype);
        $track->setDetail($details);
        $track->setResourceName($resourcename);
        $this->em->persist($track);
        if ($sync_with_db === true) {
            $this->em->flush();
        }
    }

    public function renameProviderResourcename($oldname, $newname) {
        $query = $this->em->createQuery('update models\Tracker t set t.resourcename = ?1 where t.resourcename = ?2 and t.resourcetype in (\'idp\',\'sp\',\'both\',\'ent\')');
        $query->setParameter(1, $newname);
        $query->setParameter(2, $oldname);
        $query->execute();
    }

    public function remove_ProviderTrack($entityid) {
        /**
         * @var models\Tracker[] $tracks
         */
        $tracks = $this->em->getRepository("models\Tracker")->findBy(array('resourcename' => $entityid, 'resourcetype' => array('idp', 'sp')));
        if ($tracks !== null) {
            foreach ($tracks as $t) {
                $this->em->remove($t);
            }
        }

        return true;
    }

}
