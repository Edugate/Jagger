<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet <support@edugate.ie>
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @link      https://github.com/Edugate/Jagger
 */
class ProviderRemover
{

    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;


    public function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    /**
     * @param \models\Provider $provider
     * @return bool
     */
    public function removeProvider(models\Provider $provider)
    {
        /**
         * @var models\AclResource[] $aclresources
         */
        $aclresources = $this->em->getRepository("models\AclResource")->findBy(array('resource' => $provider->getId()));

        foreach ($aclresources as $a) {
            $this->em->remove($a);
        }
        $attreqtmp = new models\AttributeRequirements;

        /**
         * @var models\AttributeRequirement[] $attrsrequests
         */
        $attrsrequests = $attreqtmp->getRequirementsBySP($provider);

        foreach ($attrsrequests as $r) {
            $this->em->remove($r);
        }

        $attrpoltmp = new models\AttributeReleasePolicies;
        /**
         * @var models\AttributeReleasePolicy[] $policies
         */
        $policies = $attrpoltmp->getAllPolicies($provider);

        foreach ($policies as $p) {
            $this->em->remove($p);
        }
        /**
         * @var models\AttributeReleasePolicy[] $policies2
         */
        $policies2 = $attrpoltmp->getCustomSpPolicyAttributesRequester($provider);

        foreach ($policies2 as $p2) {
            $this->em->remove($p2);
        }
        /**
         * @var models\AttributeReleasePolicy[] $policies3
         */
        $policies3 = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'type' => 'sp',
            'requester' => $provider->getId()));

        foreach ($policies3 as $p3) {
            $this->em->remove($p3);
        }
        $cmstaticmetadata = $provider->getStaticMetadata();
        if (!empty($cmstaticmetadata)) {
            $this->em->remove($cmstaticmetadata);
        }
        $this->em->remove($provider);
        return true;
    }
}
