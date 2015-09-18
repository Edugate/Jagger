<?php

namespace models;

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
 * AttributeReleasePolicies Class
 * 
 * @package     RR3
 * @subpackage  Models
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class AttributeReleasePolicies
{

    function __construct()
    {
        $this->ci = & get_instance();
        $this->em = $this->ci->doctrine->em;
    }

    public function getAllPolicies(Provider $idp)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array('idp' => $idp->getId()));
        return $collection;
    }

    public function getCustomSpPolicyAttributes(Provider $idp, $requester = null)
    {
        if (empty($requester))
        {
            $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
                'idp' => $idp->getId(),
                'type' => 'customsp'));
        }
        return $collection;
    }

    public function getCustomSpPolicyAttributesRequester(Provider $requester)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'requester' => $requester->getId(),
            'type' => 'customsp'));
        return $collection;
    }

    public function getSpecArpsToRemoveIDP(Provider $idp)
    {
        $collection = array();
        $custom = $this->getCustomSpPolicyAttributes($idp);
        $specific = $this->getSpecificPolicyAttributes($idp);
        $tmp_providers = new Providers;
        $circle = $tmp_providers->getCircleMembersSP($idp);
        $circle_ids = array();
        if (!empty($circle))
        {
            foreach ($circle as $c)
            {
                $circle_ids[] = $c->getId();
            }
        }
        if (!empty($custom) && is_array($custom) && count($custom) > 0)
        {
            foreach ($custom as $k)
            {
                if (!in_array($k->getRequester(), $circle_ids))
                {
                    $collection[$k->getId()] = $k;
                }
            }
        }
        if (!empty($specific) && is_array($specific) && count($specific) > 0)
        {
            foreach ($specific as $l)
            {
                if (!in_array($l->getRequester(), $circle_ids))
                {
                    $collection[$l->getId()] = $l;
                }
            }
        }

        return $collection;
    }

    public function getSpecArpsToRemoveSP(Provider $sp)
    {
        $collection = array();
        $custom = $this->getCustomSpPolicyAttributesRequester($sp);
        $specific = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'type' => 'sp',
            'requester' => $sp->getId()));
        $tmp_providers = new Providers;
        $circle = $tmp_providers->getCircleMembersIDP($sp);
        $circle_ids = array();
        if (!empty($circle))
        {
            foreach ($circle as $c)
            {
                $circle_ids[] = $c->getId();
            }
        }
        if (!empty($custom) && is_array($custom) && count($custom) > 0)
        {
            foreach ($custom as $k)
            {
                if (!in_array($k->getProvider()->getId(), $circle_ids))
                {
                    $collection[$k->getId()] = $k;
                }
            }
        }
        if (!empty($specific) && is_array($specific) && count($specific) > 0)
        {
            foreach ($specific as $l)
            {
                if (!in_array($l->getProvider()->getId(), $circle_ids))
                {
                    $collection[$l->getId()] = $l;
                }
            }
        }
        return $collection;
    }

    public function getSpecCustomArpsToRemove(Provider $provider)
    {
        log_message('debug', 'getSpecCustomArpsToRemove started for provider:' . $provider->getName());
        $idparps = array();
        $sparps = array();
        $type = $provider->getType();
        if (strcasecmp($type, 'SP') != 0)
        {
            $idparps = $this->getSpecArpsToRemoveIDP($provider);
        }
        if (strcasecmp($type, 'IDP') != 0)
        {
            $sparps = $this->getSpecArpsToRemoveSP($provider);
        }

        $result = array_merge($idparps, $sparps);
        return $result;
    }

    public function getSpecificPolicyAttributes(Provider $idp, $requester = null)
    {
        if (empty($requester))
        {
            $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
                'idp' => $idp->getId(),
                'type' => 'sp'));
        }
        else
        {
            $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
                'idp' => $idp->getId(),
                'type' => 'sp',
                'requester' => $requester));
        }
        return $collection;
    }

    public function getSupportedAttributes(Provider $idp)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idp->getId(),
            'type' => 'supported'));
        return $collection;
    }

    public function getGlobalPolicyAttributes(Provider $idp)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idp->getId(),
            'type' => 'global'));
        return $collection;
    }

    public function getFedPolicyAttributes(Provider $idp)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idp->getId(),
            'type' => 'fed'));
        return $collection;
    }

    public function getFedPolicyAttributesByFed(Provider $idp, Federation $fed)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idp->getId(),
            'type' => 'fed',
            'requester' => $fed->getId()));
        return $collection;
    }

    public function getOneFedPolicyAttribute(Provider $idp, Federation $fed, $attrid)
    {
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array(
            'idp' => $idp->getId(),
            'type' => 'fed',
            'requester' => $fed->getId(),
            'attribute' => $attrid
        ));
        return $policy;
    }

    public function getOneGlobalPolicy($idpid, $attrid)
    {
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array(
            'idp' => $idpid,
            'attribute' => $attrid,
            'type' => 'global'
        ));

        return $policy;
    }

    public function getOneFedPolicy($idpid, $attrid, $requester)
    {
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array(
            'idp' => $idpid,
            'attribute' => $attrid,
            'type' => 'fed',
            'requester' => $requester,
        ));
        return $policy;
    }

    public function getOneSPPolicy($idpid, $attrid, $requester)
    {
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array(
            'idp' => $idpid,
            'attribute' => $attrid,
            'requester' => $requester,
            'type' => 'sp',
        ));
        return $policy;
    }

    public function getOneSPCustomPolicy($idpid, $attrid, $requester)
    {
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array(
            'idp' => $idpid,
            'attribute' => $attrid,
            'requester' => $requester,
            'type' => 'customsp',
        ));
        return $policy;
    }

    public function getSPPolicy($idpid)
    {
        /**
         * @todo finish function 
         */
        $policies = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idpid,
            'type' => 'sp'));
        return $policies;
    }

    public function getCustomSpArp(Provider $idp, Provider $sp)
    {
        $arp = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idp->getId(),
            'type' => 'customsp',
            'requester' => $sp->getId()));
        return $arp;
    }

    public function getCustomSpArpByAttribute(Provider $idp, Provider $sp, Attribute $attr)
    {
        $arp = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array(
            'idp' => $idp->getId(),
            'type' => 'customsp',
            'requester' => $sp->getId(),
            'attribute' => $attr->getId()
        ));
        return $arp;
    }

    public function removeSupportedAttribute(Provider $idp, Attribute $attribute)
    {
        $collection = $this->em->getRepository("models\AttributeReleasePolicy")->findBy(array(
            'idp' => $idp->getId(),
            'attribute' => $attribute->getId()
        ));
        if (!empty($collection))
        {
            foreach ($collection as $c)
            {
                $this->em->remove($c);
            }
        }
    }
}

