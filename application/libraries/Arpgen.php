<?php

/**
 * Created by PhpStorm.
 * User: januszu
 * Date: 05/06/15
 * Time: 09:09
 */
class Arpgen
{

    protected $CI;

    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;
    /**
     * @var $attrsDefs models\Attribute[]
     */
    protected $attrsDefs;
    protected $attrDefsSmplArray = array();
    protected $tempARPolsInstance;
    protected $entityCategories = array();
    protected $attrRequiredByFeds = array();
    protected static $supportedAttrs = array();
    protected static $globalsAttrs = array();

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->em = $this->CI->doctrine->em;


        $tmpAttrs = new models\Attributes();
        $this->attrsDefs = $tmpAttrs->getAttributesToArrayById();
        foreach ($this->attrsDefs as $k => $v) {
            $this->attrDefsSmplArray[$k] = $v->getName();
        }
        natcasesort($this->attrDefsSmplArray);
        $this->tempARPolsInstance = new models\AttributeReleasePolicies;


        $cachedEC = $this->CI->j_ncache->getEntityCategoriesDefs();



        if(empty($cachedEC))
        {
            /**
             * @var $entcats models\Coc[]
             */
            $entcats = $this->em->getRepository('models\Coc')->findBy(array('type' => 'entcat'));
            foreach ($entcats as $e) {
                $this->entityCategories[$e->getId()] = array('value' => $e->getUrl(), 'name' => $e->getSubtype());
            }
            $this->CI->j_ncache->saveEntityCategoriesDefs($this->entityCategories);
        }
        else
        {
            $this->entityCategories = $cachedEC;
        }




        /**
         * @var $fedAttrReqs models\AttributeRequirement[]
         */
        $fedAttrReqs = $this->em->getRepository('models\AttributeRequirement')->findBy(array('type' => 'FED'));
        foreach ($fedAttrReqs as $req) {
            if ($req->getFederation() !== null) {
                $this->attrRequiredByFeds[$req->getFederation()->getId()][$req->getAttribute()->getId()] = $req->getStatusToInt();
            }
        }


    }

    public function getAttrDefs()
    {
        return $this->attrDefsSmplArray;
    }

    public function getSupportAttributes(\models\Provider $idp)
    {
        $idpID = $idp->getId();
        if(array_key_exists($idpID,self::$supportedAttrs))
        {
            return self::$supportedAttrs[$idpID];
        }
        /**
         * @var $supportAttrColl \models\AttributeReleasePolicy[]
         */
        $supportAttrColl = $this->tempARPolsInstance->getSupportedAttributes($idp);
        foreach ($supportAttrColl as $attr) {
            self::$supportedAttrs[$idpID][] = $attr->getAttribute()->getId();
        }
        return self::$supportedAttrs[$idpID];
    }

    private function getPoliciec(\models\Provider $idp)
    {
        $federations = $this->getActiveFederations($idp);
        /**
         * @var $policies models\AttributeReleasePolicy[]
         */
        $policies = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(
            array(
                'idp' => $idp,
            //    'attribute' => $this->getSupportAttributes($idp),
                'type' => array('fed', 'sp', 'entcat', 'customsp')
            )
        );
        $result = array('fed' => array(), 'sp' => array(), 'entcat' => array(), 'customsp' => array());
        foreach ($policies as $entry) {
            $entryType = $entry->getType();
            $valuePolicy = $entry->getPolicy();
            if ($entryType === 'customsp') {
                $valuePolicy = $entry->getRawdata();
                $result[$entryType][$entry->getRequester()][$entry->getAttribute()->getId()] = $valuePolicy;
                continue;
            } elseif ($entryType === 'fed' && !in_array($entry->getRequester(), $federations)) {
                continue;
            }

            $result[$entryType][$entry->getAttribute()->getId()][$entry->getRequester()] = $valuePolicy;
        }
        return $result;
    }

    private function mergeReqAttrsByFeds($feds)
    {
        $filteredFeds = array_intersect_key($this->attrRequiredByFeds, $feds);
        $result = array();
        foreach ($filteredFeds as $fedid => $attrs) {
            foreach ($attrs as $attrid => $status) {
                if ((array_key_exists($attrid, $result) && $status < $result[$attrid]) || (!array_key_exists($attrid, $result))) {
                    $result[$attrid] = $status;
                }
            }
        }
        return $result;
    }

    public function getSPRequirements($sps)
    {
        $tempAttrReqs = new models\AttributeRequirements;

        $result = array();

        $res = $tempAttrReqs->getRequirementsBySPs($sps);
        /**
         * @var $v models\AttributeRequirement[]
         */
        foreach ($res as $k => $v) {
            foreach ($v as $req) {
                $result[$k]['req'][$req->getAttribute()->getId()] = $req->getStatusToInt();
            }
        }
        return $result;
    }



    /// may contain unsuported attrs
    public function genGlobal(\models\Provider $idp)
    {
        /**
         * @var $globals models\AttributeReleasePolicy[]
         */
        $globals = $this->tempARPolsInstance->getGlobalPolicyAttributes($idp);
        $result = array();
        foreach ($globals as $g) {
            $result[$g->getAttribute()->getId()] = $g->getPolicy();
        }
        $supportedAttrs = $this->getSupportAttributes($idp);
        foreach ($supportedAttrs as $v) {
            if (!array_key_exists($v, $result)) {
                $result[$v] = 0;
            }
        }
        return $result;
    }


    private function mergeFedPolicies(array $source, array $limit)
    {
        $flippedLimit = array_flip($limit);
        $filtered = array_intersect_key($source, $flippedLimit);

        if (count($filtered) < 1) {
            return array();
        }

        $presum = array();
        foreach ($filtered as $k => $arr) {

            foreach ($arr as $k3 => $v3) {
                $presum[$k3][] = $v3;
            }
        }
        $final = array();
        foreach ($presum as $k4 => $v4) {
            $final[$k4] = max($v4);
        }
        return $final;

    }

    public function getActiveFederations(\models\Provider $idp)
    {

        $membership = $idp->getActiveFederations();
        $result = array();
        foreach ($membership as $mm) {
            $isActiveFed = $mm->getActive();
            if ($isActiveFed) {
                $result[] = $mm->getId();
            }
        }
        return $result;
    }

    public function genPolicyDefs(\models\Provider $idp)
    {

        $globalPolicy = $this->genGlobal($idp);
        $policies = $this->getPoliciec($idp);
        $supportedAttrs = $this->getSupportAttributes($idp);
        $supAttrsFlipped = array_flip($supportedAttrs);
        /**
         * @var $members models\Provider[]
         */
        $members = $this->getMembers($idp, $idp->getExcarps());

        $result = array(
            'definitions' => array(
                'attrs' => $this->attrDefsSmplArray,
                'ec' => $this->entityCategories,
            ),
            'memberof' => $this->getActiveFederations($idp),
            'supported' => $supportedAttrs,
            'global' => $globalPolicy,
            'ecPolicies' => $policies['entcat'],
            'fedPolicies' => $policies['fed'],
            'fedPoliciesPerFed' => array(),
            'reqAttrByFeds' => $this->attrRequiredByFeds,
            'spPoliciec' => $policies['sp'],
            'sps' => array()
        );


        foreach ($policies['fed'] as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $result['fedPoliciesPerFed'][$k2][$k] = $v2;
            }
        }

        $membersIDs = array();
        foreach ($members as $member) {
            $membersIDs[] = $member->getId();
        };
        $result['sps'] = array_fill_keys($membersIDs, array('active' => true, 'entcat' => array(), 'customsp' => array(), 'req' => array(), 'feds' => array(), 'spec' => array(), 'prefinal' => $globalPolicy));

        // get required attrs by all SP and fille reseult 'req'
        $req = $this->getSPRequirements(array_keys($result['sps']));
        foreach ($req as $kreq => $kvalu) {
            $result['sps'][$kreq]['req'] = $kvalu['req'];
        }
        foreach ($members as $member) {

            $pid = $member->getId();
            if (isset($policies['customsp'][$pid])) {
                $result['sps'][$pid]['custom'] = $policies['customsp'][$pid];
            }
            $result['sps'][$pid]['type'] = $member->getType();
            $result['sps'][$pid]['entid'] = $member->getId();
            $result['sps'][$pid]['entityid'] = $member->getEntityId();
            $feds = $member->getActiveFederations();
            foreach ($feds as $f) {
                $result['sps'][$pid]['feds'][] = $f->getId();
            }

            if (count($result['sps'][$pid]['req']) == 0) {
                $result['sps'][$pid]['req'] = $this->mergeReqAttrsByFeds(array_flip($result['sps'][$pid]['feds']));
            }


            // start entityCategory

            $pp = $member->getCoc();
            foreach ($pp as $d) {
                $t = $d->getType();
                if ($t === 'entcat') {
                    $result['sps'][$pid]['entcat'][] = $d->getId();
                }
            }
            // end entityCategory


        }


        foreach ($policies['sp'] as $attrid => $spPolArr) {

            foreach ($spPolArr as $spId => $spPolicy) {
                $result['sps'][$spId]['spec'][$attrid] = $spPolicy;
            }

        }

        foreach ($result['sps'] as $spid => $spdet) {

            if (array_key_exists('active', $spdet)) {
                $result['sps'][$spid]['final'] = array_replace($spdet['prefinal'], $this->mergeFedPolicies($result['fedPoliciesPerFed'], $spdet['feds']), $spdet['spec']);
                //remeain only supported attrs
                $result['sps'][$spid]['final'] = array_intersect_key($result['sps'][$spid]['final'], $supAttrsFlipped);
                $result['sps'][$spid]['final'] = array_intersect_key($result['sps'][$spid]['final'], $spdet['req']);
            }
        }
        return $result;


    }

    private function getMembers(\models\Provider $idp, array $exclude)
    {
        $tempProviders = new models\Providers;
        $members = $tempProviders->getSPsForArpIncEntCats($idp, $exclude);
        return $members;
    }

    public function genXML(\models\Provider $idp, $version = null)
    {
        if ($version === null) {
            $version = 2;
        }

        // difference for shibboleth ver 2.x and ver 3.x
        if ($version === 2) {
            $entcatRuleTxt = 'saml:AttributeRequesterEntityAttributeExactMatch';
        } else {
            $entcatRuleTxt = 'saml:EntityAttributeExactMatch';
        }
        $policy = $this->genPolicyDefs($idp);

        $xml = $this->createXMLHead();

        $comment = PHP_EOL . '
			Experimental verion Attribute Release Policy for ' . $idp->getEntityId() . PHP_EOL . '
                        generated on ' . date('D M j G:i:s T Y') . PHP_EOL . '
                        compatible with shibboleth idp version: ' . $version . '.x
			' . PHP_EOL;


        $xml->startElementNs('afp', 'AttributeFilterPolicyGroup', 'urn:mace:shibboleth:2.0:afp');
        $xml->startAttribute('id');
        $xml->text('policy');
        $xml->endAttribute();


        $xml->startAttributeNs('xsi', 'schemaLocation', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->text('urn:mace:shibboleth:2.0:afp classpath:/schema/shibboleth-2.0-afp.xsd urn:mace:shibboleth:2.0:afp:mf:basic classpath:/schema/shibboleth-2.0-afp-mf-basic.xsd urn:mace:shibboleth:2.0:afp:mf:saml classpath:/schema/shibboleth-2.0-afp-mf-saml.xsd');
        $xml->endAttribute();


        $xml->startAttribute('xmlns:basic');
        $xml->text('urn:mace:shibboleth:2.0:afp:mf:basic');
        $xml->endAttribute();

        $xml->writeComment($comment);

///////////////// ENTITY CATEGORIES /////////////////////////
        $ecPolicies = $policy['ecPolicies'];
        $ecPoliciesByEntCat = array();
        foreach ($ecPolicies as $key => $value) {
            foreach ($value as $key2 => $value2) {
                $ecPoliciesByEntCat[$key2][$key] = $value2;
            }
        }


        foreach ($ecPoliciesByEntCat as $lkey => $lval) {
            $xml->writeComment('EntityCategory: ' . $policy['definitions']['ec'][$lkey]['value']);
            $xml->startElementNs('afp', 'AttributeFilterPolicy', null);
            $xml->startAttribute('id');
            $xml->text('EntityAttribute-' . $lkey);
            $xml->endAttribute();

            $xml->startElementNs('afp', 'PolicyRequirementRule', null);
            $xml->startAttributeNs('xsi', 'type', null);
            $xml->text($entcatRuleTxt);
            $xml->endAttribute();
            $xml->startAttribute('attributeName');
            $xml->text($policy['definitions']['ec'][$lkey]['name']);
            $xml->endAttribute();
            $xml->startAttribute('attributeValue');
            $xml->text($policy['definitions']['ec'][$lkey]['value']);
            $xml->endAttribute();
            $xml->endElement();


            foreach ($lval as $attr1ID => $attrP) {
                $xml->startElementNs('afp', 'AttributeRule', null);
                $xml->startAttribute('attributeID');
                $xml->text($policy['definitions']['attrs'][$attr1ID]);
                $xml->endAttribute();


                /**
                 * @todo decide if always add saml:AttributeInMetadata
                 */
                if ($attrP === 0) {
                    $xml->startElementNs('afp', 'DenyValueRule', null);
                    $xml->startAttributeNs('xsi', 'type', null);
                    $xml->text('basic:ANY');
                    $xml->endAttribute();
                    $xml->endElement();
                } else {
                    $xml->startElementNs('afp', 'PermitValueRule', null);
                    $xml->startAttributeNs('xsi', 'type', null);
                    $xml->text('saml:AttributeInMetadata');
                    $xml->endAttribute();
                    $xml->startAttribute('id');
                    $xml->text('PermitRule');
                    $xml->endAttribute();

                    $xml->startAttribute('onlyIfRequired');
                    if ($attrP === 2) {
                        $xml->text('false');
                    } else {
                        $xml->text('true');
                    }
                    $xml->endAttribute();

                    $xml->endElement();
                }
                $xml->endElement();
            }
            $xml->endElement();
        }
///////////////////////END ENTITY CATEGORIES //////////////////////////
        /// start per sp
        foreach ($policy['sps'] as  $spdets) {
            if (!array_key_exists('active', $spdets)) {
                continue;
            }
            $requireAttrsIds = array_keys($spdets['req']);
            foreach ($requireAttrsIds as $reqattrid) {
                foreach ($spdets['entcat'] as $encats) {
                    if (!array_key_exists($reqattrid, $policy['global']) || (isset($policy['ecPolicies'][$reqattrid][$encats]) && !isset($spdets['spec'][$reqattrid]))) {
                        unset($spdets['req'][$reqattrid], $spdets['final'][$reqattrid]);
                    }
                }
            }
            if (count($spdets['final']) == 0) {
                $xml->writeComment('Ommited requester: ' . $spdets['entityid'] . '');
                continue;
            }

            $releases = array();


            foreach ($spdets['final'] as $finattrid => $finpolicy) {
                if ($finpolicy >= $spdets['req'][$finattrid]) {
                    $releases[$finattrid] = 1;
                } elseif(array_key_exists($finattrid,$policy['ecPolicies'])) {
                    $releases[$finattrid] = 0;
                }
            }
            if (count($releases) == 0) {
                $xml->writeComment('Ommited requester: ' . $spdets['entityid'] . '');
                continue;
            }
            $xml->writeComment('Requester: ' . $spdets['entityid'] . '');
            $xml->startElementNs('afp', 'AttributeFilterPolicy', null);
            $xml->startAttribute('id');
            $xml->text($spdets['entityid']);
            $xml->endAttribute();
            $xml->startElementNs('afp', 'PolicyRequirementRule', null);
            $xml->startAttribute('xsi:type');
            $xml->text('basic:AttributeRequesterString');
            $xml->endAttribute();
            $xml->startAttribute('value');
            $xml->text($spdets['entityid']);
            $xml->endAttribute();
            $xml->endElement();

            foreach ($releases as $attrsToRelease => $permordeny) {
                $xml->startElementNs('afp', 'AttributeRule', null);
                $xml->startAttribute('attributeID');
                $xml->text($policy['definitions']['attrs'][$attrsToRelease]);
                $xml->endAttribute();
                if ($permordeny === 1) {
                    if (isset($spdets['custom'][$attrsToRelease]) && count($spdets['custom'][$attrsToRelease]) > 0) {
                        foreach ($spdets['custom'][$attrsToRelease] as $accessType => $values) {
                            if ($accessType === 'deny') {
                                $xml->startElementNs('afp', 'DenyValueRule', null);
                            } else {
                                $xml->startElementNs('afp', 'PermitValueRule', null);
                            }
                            if (count($values) > 1) {
                                $xml->startAttribute('xsi:type');
                                $xml->text('basic:OR');
                                $xml->endAttribute();
                                foreach ($values as $singleValue) {
                                    $xml->startElementNs('basic', 'Rule', null);
                                    $xml->startAttribute('xsi:type');
                                    $xml->text('basic:AttributeValueString');
                                    $xml->endAttribute();
                                    $xml->startAttribute('value');
                                    $xml->text($singleValue);
                                    $xml->endAttribute();
                                    $xml->startAttribute('ignoreCase');
                                    $xml->text('true');
                                    $xml->endAttribute();
                                    $xml->endElement();
                                }
                            } else {

                                $xml->startAttribute('xsi:type');
                                $xml->text('basic:AttributeValueString');
                                $xml->startAttribute('ignoreCase');
                                $xml->text('true');
                                $xml->endAttribute();
                                $xml->startAttribute('value');
                                $xml->text(array_shift($values));
                                $xml->endAttribute();
                            }

                            $xml->endElement();
                            //   break;  //
                        }

                    } else {
                        $xml->startElementNs('afp', 'PermitValueRule', null);
                        $xml->startAttribute('xsi:type');
                        $xml->text('basic:ANY');
                        $xml->endAttribute();
                        $xml->endElement();
                    }
                } else {
                    $xml->startElementNs('afp', 'DenyValueRule', null);
                    $xml->startAttribute('xsi:type');
                    $xml->text('basic:ANY');
                    $xml->endAttribute();
                    $xml->endElement();
                }

                $xml->endElement();
            }

            $xml->endElement();

        }
//////
        $xml->endElement();
        $xml->endDocument();
        return $xml;
    }

    /**
     * @return XMLWriter
     */
    public function createXMLHead()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString(' ');
        $xml->startDocument('1.0', 'UTF-8');
        return $xml;
    }

}
