<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */

class FederationRemover
{

    protected $ci;
    protected $em;


    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function removeFederation(models\Federation $federation) {
        /**
         * @var \models\AclResource[] $aclresources
         */
        $aclresources = $this->em->getRepository("models\AclResource")->findBy(array('resource' => 'f_' . $federation->getId()));
        if (!empty($aclresources)) {
            foreach ($aclresources as $a) {
                $this->em->remove($a);
            }
        }
        $attreqtmp = new models\AttributeRequirements;
        $attrsrequests = $attreqtmp->getRequirementsByFed($federation);
        if (!empty($attrsrequests)) {
            foreach ($attrsrequests as $r) {
                $this->em->remove($r);
            }
        }
        /**
         * @var models\AttributeReleasePolicy[] $policies
         */
        $policies = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array('type' => 'fed', 'requester' => $federation->getId()));

        foreach ($policies as $p) {
            $this->em->remove($p);
        }

        $this->em->remove($federation);


    }

}
