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
 * Arp_generator Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Arp_generator {

    private $idp;
    private $tmp_providers;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        log_message('debug', 'Arp_generator intitiated');
        $this->idp = new models\Provider;
        $this->tmp_providers = new models\Providers;
    }

    public function arpToXML(models\Provider $idp, $return_in_array = FALSE, $byInherit = FALSE)
    {
        $this->idp = $idp;
        $idp_id = $idp->getId();
        if ($byInherit)
        {
            $res = $this->ci->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp_id), $this->ci->config->item('arp_cache_time'));
        } else
        {
            $res = $this->ci->j_cache->library('arp_generator', 'arpToArray', array($idp_id), $this->ci->config->item('arp_cache_time'));
        }
        if (!empty($return_in_array))
        {
            return $res;
        }
        $result = null;
        if (!empty($res) && is_array($res))
        {

            $result = $this->arpArrayToXML($res);
            if (empty($result))
            {
                $result = new \DOMDocument();
                $xpath = new \DomXPath($result);
                $xpath->registerNamespace('afp', 'urn:mace:shibboleth:2.0:afp');
                $xpath->registerNamespace('saml', 'urn:mace:shibboleth:2.0:afp');
                $xpath->registerNamespace('basic', 'urn:mace:shibboleth:2.0:afp:mf:basic');
                $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $AttributeFilterPolicyGroup = $result->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'afp:AttributeFilterPolicyGroup');
                $AttributeFilterPolicyGroup->setAttribute('id', 'policy');
                $AttributeFilterPolicyGroup->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $AttributeFilterPolicyGroup->setAttribute('xsi:schemaLocation', 'urn:mace:shibboleth:2.0:afp classpath:/schema/shibboleth-2.0-afp.xsd urn:mace:shibboleth:2.0:afp:mf:basic classpath:/schema/shibboleth-2.0-afp-mf-basic.xsd urn:mace:shibboleth:2.0:afp:mf:saml classpath:/schema/shibboleth-2.0-afp-mf-saml.xsd');
                $result->appendChild($AttributeFilterPolicyGroup);
                $comment = "\nno attribute release policy definitions found\n";
                $c = $result->createComment(str_replace('--', '-' . chr(194) . chr(173) . '-', $comment));
                $AttributeFilterPolicyGroup->appendChild($c);
            }
        } else
        {
            $this->ci->j_cache->library('arp_generator', 'arpToArray', array($idp_id), -1);
        }
        return $result;
    }

    public function arpArrayToXML(array $release)
    {
        $excluded = $this->idp->getExcarps();
        $excluded_comment = implode(', ', $excluded);
        $docXML = new \DOMDocument();
        $docXML->formatOutput = true;
        $xpath = new \DomXPath($docXML);
        $xpath->registerNamespace('afp', 'urn:mace:shibboleth:2.0:afp');
        $xpath->registerNamespace('saml', 'urn:mace:shibboleth:2.0:afp');
        $xpath->registerNamespace('basic', 'urn:mace:shibboleth:2.0:afp:mf:basic');
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $AttributeFilterPolicyGroup = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'afp:AttributeFilterPolicyGroup');
        $AttributeFilterPolicyGroup->setAttribute('id', 'policy');

        $AttributeFilterPolicyGroup->setAttribute('xsi:schemaLocation', 'urn:mace:shibboleth:2.0:afp classpath:/schema/shibboleth-2.0-afp.xsd urn:mace:shibboleth:2.0:afp:mf:basic classpath:/schema/shibboleth-2.0-afp-mf-basic.xsd urn:mace:shibboleth:2.0:afp:mf:saml classpath:/schema/shibboleth-2.0-afp-mf-saml.xsd');
        $AttributeFilterPolicyGroup->setAttribute('xmlns:basic', 'urn:mace:shibboleth:2.0:afp:mf:basic');
        $comment = "\n
			======================================================\n
			Attribute Release Policy for " . $this->idp->getName() . " (" . $this->idp->getEntityId() . ")\n
                        generated on " . date("D M j G:i:s T Y") . "\n
			=======================================================\n";
        if (!empty($excluded_comment))
        {
            $comment .= "\nList of excluded service providers from generated ARP:\n" . $excluded_comment . "\n";
        }
        $c = $docXML->createComment(str_replace('--', '-' . chr(194) . chr(173) . '-', $comment));
        $AttributeFilterPolicyGroup->appendChild($c);

        foreach ($release as $key => $value)
        {
            $AttributeFilterPolicy = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'AttributeFilterPolicy');
            $AttributeFilterPolicy->setAttribute('id', $key);
            $comment = "\n";
            if (array_key_exists('name', $value))
            {
                $comment .= "" . $value['name'] . "\n";
            }
            if (array_key_exists('federations', $value))
            {
                $comment .= "" . $value['federations'] . "\n";
            }


            $c = $docXML->createComment(str_replace('--', '-' . chr(194) . chr(173) . '-', $comment));
            $AttributeFilterPolicyGroup->appendChild($c);
            $AttributeFilterPolicyGroup->appendChild($AttributeFilterPolicy);
            if (count($value > 0))
            {
                $PolicyRequirementRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'PolicyRequirementRule');
                $PolicyRequirementRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:AttributeRequesterString');
                $PolicyRequirementRule->setAttribute('value', $key);

                $AttributeFilterPolicy->appendChild($PolicyRequirementRule);
                foreach ($value['attributes'] as $attr_name => $attr_value)
                {
                    log_message('debug', 'generating arpXML attr: ' . $attr_name . ' for:' . $key);
                    //log_message('debug', 'keys are:' . implode(";", array_keys($value)) . ' for:' . $key);
                    if (array_key_exists($attr_name, $value['custom']) && $attr_value == 1)
                    {
                        log_message('debug', 'found custom for attr: ' . $attr_name . ' for:' . $key);
                        $AttributeRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'AttributeRule');
                        $AttributeRule->setAttribute('attributeID', $attr_name);
                        if (array_key_exists('permit', $value['custom'][$attr_name]) && count($value['custom'][$attr_name]['permit']) > 0)
                        {
                            log_message('debug', 'Found custom permit for:' . $key);
                            if (count($value['custom'][$attr_name]['permit']) > 1)
                            {
                                $PermitValueRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'PermitValueRule');
                                $PermitValueRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:OR');

                                foreach ($value['custom'][$attr_name]['permit'] as $kvalue)
                                {
                                    $value_permited = $docXML->CreateElement('basic:Rule');
                                    $value_permited->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:AttributeValueString');
                                    $value_permited->setAttribute('value', '' . $kvalue . '');
                                    $value_permited->setAttribute('ignoreCase', 'true');
                                    $PermitValueRule->appendChild($value_permited);
                                }

                                $AttributeRule->appendChild($PermitValueRule);
                                $AttributeFilterPolicy->appendChild($AttributeRule);
                            } else
                            {
                                $PermitValueRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'PermitValueRule');
                                $PermitValueRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:AttributeValueString');
                                $kvalue = $value['custom'][$attr_name]['permit'][0];
                                $PermitValueRule->setAttribute('value', '' . $kvalue . '');
                                $PermitValueRule->setAttribute('ignoreCase', 'true');
                                $AttributeRule->appendChild($PermitValueRule);
                                $AttributeFilterPolicy->appendChild($AttributeRule);
                            }
                        }
                        if (array_key_exists('deny', $value['custom'][$attr_name]) && count($value['custom'][$attr_name]['deny']) > 0)
                        {
                            log_message('debug', 'Found custom deny for:' . $key);
                            if (count($value['custom'][$attr_name]['deny']) > 1)
                            {
                                $DenyValueRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'DenyValueRule');
                                $DenyValueRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:OR');

                                foreach ($value['custom'][$attr_name]['deny'] as $kvalue)
                                {
                                    $value_denied = $docXML->CreateElement('basic:Rule');
                                    $value_denied->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:AttributeValueString');
                                    $value_denied->setAttribute('value', '' . $kvalue . '');
                                    $value_denied->setAttribute('ignoreCase', 'true');
                                    $DenyValueRule->appendChild($value_denied);
                                }

                                $AttributeRule->appendChild($DenyValueRule);
                                $AttributeFilterPolicy->appendChild($AttributeRule);
                            } else
                            {
                                $DenyValueRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'DenyValueRule');
                                $DenyValueRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:AttributeValueString');
                                $kvalue = $value['custom'][$attr_name]['deny'][0];
                                $DenyValueRule->setAttribute('value', '' . $kvalue . '');
                                $DenyValueRule->setAttribute('ignoreCase', 'true');
                                $AttributeRule->appendChild($DenyValueRule);
                                $AttributeFilterPolicy->appendChild($AttributeRule);
                            }
                        }
                    } else
                    {
                        if ($attr_value == 0)
                        {
                            $AttributeRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'AttributeRule');
                            $AttributeRule->setAttribute('attributeID', $attr_name);
                            $PermitValueRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'DenyValueRule');
                            $PermitValueRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:ANY');
                            $AttributeFilterPolicy->appendChild($AttributeRule);
                            $AttributeRule->appendChild($PermitValueRule);
                        } elseif ($attr_value == 1)
                        {
                            $AttributeRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'AttributeRule');
                            $AttributeRule->setAttribute('attributeID', $attr_name);
                            $PermitValueRule = $docXML->CreateElementNS('urn:mace:shibboleth:2.0:afp', 'PermitValueRule');
                            $PermitValueRule->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:type', 'basic:ANY');
                            $AttributeFilterPolicy->appendChild($AttributeRule);
                            $AttributeRule->appendChild($PermitValueRule);
                        }
                    }
                }
            }
        }

        $docXML->appendChild($AttributeFilterPolicyGroup);
        return $docXML;
    }

    public function arpToArray($provider)
    {
        $idp = null;
        $release = array();
        if (empty($idp_id))
        {
            $idp = $this->idp;
        } elseif ($provider instanceOf models\Provider)
        {
            $idp = $provider;
        } elseif (is_numeric($provider) && !empty($this->idp))
        {
            $tmp_id = $this->idp->getId();
            if ($tmp_id == $provider)
            {
                $idp = $this->idp;
            }
        }

        $global_policy = array();
        $tmp_attrs = new models\Attributes();
        $attrDefinitions = $tmp_attrs->getAttributes();
        $tmp_idp = new models\Providers;
        if (empty($idp))
        {
            log_message('debug', "IdP not found with id:." . $idp->getId());
            return null;
        }
        $policies = $idp->getAttributeReleasePolicies();

        $tmp_myfeds = $idp->getActiveFederations();
        $feds_collection = array();
        foreach ($tmp_myfeds as $t)
        {
            $fedEnabled = $t->getActive();
            if($fedEnabled)
            {
              $feds_collection[$t->getId()] = $t->getActiveMembers();
            }
        }
        $tmp_requirements = new models\AttributeRequirements;

        $members = null;
        $members_byid = array();

        $members = $tmp_idp->getCircleMembersByType($idp);
        $excluded = $idp->getExcarps();
        $excludedById = array();
        if (is_array($excluded) && count($excluded) > 0)
        {
            $tmpexl = $this->em->getRepository("models\Provider")->findBy(array('entityid' => $excluded));
            foreach ($tmpexl as $tmpv)
            {
                $excludedById[] = $tmpv->getId();
                if($members->contains($tmpv))
                {
                   $members->removeElement($tmpv);
                }
            }

        }
        log_message('debug', 'excluded SP from arp by id:' . serialize($excludedById));

        if ($members->count() > 0)
        {
            foreach ($members as $m_value)
            {

                $members_byid[$m_value->getId()] = $m_value;
            }
        } else
        {
            log_message('debug', 'no members found');
            return null;
        }

        $members_requirements = $tmp_requirements->getRequirementsBySPs(array_keys($members_byid));

        log_message('debug', 'Arp: found ' . $members->count() . ' for idp (id:' . $idp->getId() . '): ' . $idp->getEntityId() . '');

        $attrs = array();
        /**
         * get supported attributes 
         */
        $tmp_s_attrs = new models\AttributeReleasePolicies;
        /* supported attrs  collection */
        $s_attrs = $tmp_s_attrs->getSupportedAttributes($idp);
        /* global policy collection */
        $g_attrs = $tmp_s_attrs->getGlobalPolicyAttributes($idp);

        $f_attrs = $tmp_s_attrs->getFedPolicyAttributes($idp);

        $spec_attrs = $tmp_s_attrs->getSpecificPolicyAttributes($idp);

        /* get custom policies */
        $custom_attrs = $tmp_s_attrs->getCustomSpPolicyAttributes($idp);
        $custom_policies = array();
        if (!empty($custom_attrs) and count($custom_attrs) > 0)
        {
            log_message('debug', "Found custom permit/deny for IDP EntityID: " . $idp->getEntityId());
            foreach ($custom_attrs as $key)
            {
                $sp_requester = $this->tmp_providers->getOneSpById($key->getRequester());
                if (!empty($sp_requester))
                {
                    $sp_entityid = $sp_requester->getEntityId();
                    $custom_policies[$sp_entityid][$key->getAttribute()->getName()] = $key->getRawdata();
                } else
                {
                    log_message('warning', 'Found orphaned or requester SP is disabled: custom policy with id:' . $key->getId());
                }
            }
        }
        $specific_attributes = array();
        $t_sp = new models\Providers;
        $spec_attrs = $tmp_s_attrs->getSpecificPolicyAttributes($idp);
        if (!empty($spec_attrs))
        {
            foreach ($spec_attrs as $skey => $svalue)
            {
                //$ent = $t_sp->getOneById($svalue->getRequester());
                if (isset($members_byid[$svalue->getRequester()]))
                {
                    $ent = $members_byid[$svalue->getRequester()];
                } elseif (!in_array($svalue->getRequester(), $excludedById))
                {
                    log_message('warning', 'found orphaned arps in db : sprequest:' . $svalue->getRequester() . ' doesn exist in provider table - SP might be disabled');
                } else
                {
                    log_message('debug', 'sprequest:' . $svalue->getRequester() . ' is excluded from arp not generating');
                }
                if (!empty($ent))
                {
                    $specific_attributes[$ent->getEntityId()][$svalue->getAttribute()->getName()] = $svalue->getPolicy();
                }
            }
        }
        $federation_policy = array();
        if (!empty($f_attrs))
        {
            foreach ($f_attrs as $f)
            {
                $federation_policy[$f->getRequester()][$f->getAttribute()->getName()] = $f->getPolicy();
            }
        }

        foreach ($g_attrs as $g)
        {
            $global_policy[$g->getAttribute()->getName()] = $g->getPolicy();
        }
        if (empty($s_attrs) or (is_array($s_attrs) && count($s_attrs) == 0))
        {
            log_message('debug', 'Arp:  no supported attributes for idp: ' . $idp->getId());
            return null;
        }

        foreach ($s_attrs as $s)
        {
            $supported_attrs[$s->getAttribute()->getName()] = 0;
        }
        foreach ($supported_attrs as $k => $v)
        {
            if (array_key_exists($k, $global_policy))
            {
                $m_policy[$k] = $global_policy[$k];
            } else
            {
                $m_policy[$k] = 0;
            }
        }
        foreach ($members as $m)
        {
            /* set with merged array : supported with overwiten by global policy */
            $attrs[$m->getEntityId()] = $m_policy;

            /* overwite with fede */
            foreach ($feds_collection as $key => $value)
            {
                if (array_key_exists($key, $federation_policy))
                {
                    /* check if entityid is a members of specified federtion */
                    if ($value->containsKey($m->getEntityId()))
                    {
                        /**
                         * overwrite policy
                         */
                        foreach ($attrs[$m->getEntityId()] as $atkey => $atvalue)
                        {
                            if (array_key_exists($atkey, $federation_policy[$key]))
                            {
                                if ($atvalue <= $federation_policy[$key][$atkey])
                                {
                                    $attrs[$m->getEntityId()][$atkey] = $federation_policy[$key][$atkey];
                                }
                            } else
                            {
                                $attrs[$m->getEntityId()][$atkey] = 0;
                            }
                        }
                    }
                }
            }
        }
        $i = 0;

        foreach ($specific_attributes as $pkey => $pvalue)
        {

            if (array_key_exists($pkey, $attrs))
            {
                foreach ($attrs[$pkey] as $kattr => $kvalue)
                {
                    if (array_key_exists($kattr, $specific_attributes[$pkey]))
                    {
                        $attrs[$pkey][$kattr] = $specific_attributes[$pkey][$kattr];
                    } else
                    {
                        $attrs[$pkey][$kattr] = 0;
                    }
                }
            }
        }
        foreach ($members as $m)
        {
            $r = null;
            $m_entityid = $m->getEntityId();
            $release[$m_entityid]['attributes'] = array();
            $release[$m_entityid]['custom'] = array();
            $release[$m_entityid]['entityid'] = $m_entityid;
            $release[$m_entityid]['name'] = $m->getName();
            $release[$m_entityid]['attributes'] = $supported_attrs;
            $release[$m_entityid]['spid'] = $m->getId();
            $release[$m_entityid]['req'] = array();
            if (array_key_exists($m->getId(), $members_requirements))
            {
                $r = $members_requirements[$m->getId()];
            }

            if (!empty($r))
            {
                $requiredAttrs = array();
                foreach ($r as $rk)
                {
                    $requiredAttrs[$rk->getAttribute()->getName()] = $rk->getStatus();
                }

                foreach ($attrs[$m_entityid] as $attr_name => $attr_value)
                {
                    $release[$m_entityid]['req'] = $requiredAttrs;
                    if (array_key_exists($attr_name, $requiredAttrs))
                    {
                        $rel_value = $attrs[$m_entityid][$attr_name];
                        $req_value = $requiredAttrs[$attr_name];
                        if ($req_value == 'required' && ($rel_value > 0))
                        {
                            $release[$m_entityid]['attributes'][$attr_name] = 1;
                        } elseif ($rel_value == 2)
                        {
                            $release[$m_entityid]['attributes'][$attr_name] = 1;
                        } else
                        {
                            $release[$m_entityid]['attributes'][$attr_name] = 0;
                        }
                    }
                }
            } else
            {
                $feds_1 = $m->getActiveFederations();
                if (!empty($feds_1))
                {
                    $tmp_fed_req = array();
                    foreach ($feds_1->getValues() as $f_key => $f_value)
                    {
                        /* check if sp's federation matches idp federation */
                        if (array_key_exists($f_value->getId(), $feds_collection))
                        {
                            $n_req = array();
                            $n_req = $tmp_requirements->getRequirementsByFed($f_value);
                            if (!empty($n_req))
                            {
                                $requiredAttrs = array();
                                foreach ($n_req as $nk)
                                {
                                    $requiredAttrs[$nk->getAttribute()->getName()] = $nk->getStatus();
                                }
                                $release[$m->getEntityId()]['req'] = $requiredAttrs;
                                foreach ($attrs[$m->getEntityId()] as $attr_name => $attr_value)
                                {
                                    if (array_key_exists($attr_name, $requiredAttrs))
                                    {
                                        $rel_value = $attrs[$m->getEntityId()][$attr_name];
                                        $req_value = $requiredAttrs[$attr_name];
                                        if ($req_value == 'required' && ($rel_value > 0))
                                        {
                                            $release[$m->getEntityId()]['attributes'][$attr_name] = 1;
                                        } elseif ($rel_value == 2)
                                        {
                                            $release[$m->getEntityId()]['attributes'][$attr_name] = 1;
                                        }
                                    }
                                }
                            } else
                            {
                                foreach ($attrs[$m->getEntityId()] as $attr_name => $attr_value)
                                {
                                    $release[$m->getEntityId()]['attributes'][$attr_name] = 0;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($custom_policies as $key => $value)
        {

            if (array_key_exists($key, $release))
            {
                $release[$key]['custom'] = $value;
            }
        }
        return $release;
    }

    public function arpToArrayByInherit($provider)
    {
        $lang = 'en';
        /**
         * disabled for the moment
         *     $langMethodExist = method_exists('MY_Controller','getLang');
         *     if($langMethodExist)
         *     {
         *         $lang = MY_Controller::getLang();
         *     }
         */
        $idp = null;
        $release = array();
      
        if($provider instanceOf models\Provider)
        {
           $idp = $provider;
        }
        elseif(is_numeric($provider) && !empty($this->idp))
        {
            $tmp_id = $this->idp->getId();
            if ($tmp_id == $provider)
            {
                $idp = $this->idp;
            }
            else
            {
                log_message('debug', "IdP not found");
                return null;

            }
        }
        else
        {
            log_message('error','PE: arpToArrayByInherit couldnt');
            return null;
        }

        log_message('debug','PE: start arpToArrayByInherit for entityid: '.$idp); 

        $global_policy = array();
        $tmp_attrs = new models\Attributes();
        $tmp_providers = new models\Providers();
        $attrDefinitions = $tmp_attrs->getAttributes();
        $tmp_idp = new models\Providers;
        /**
         * get all defined policies for idp
         */
        $policies = $idp->getAttributeReleasePolicies();

        $tmp_myfeds = $tmp_providers->getTrustedActiveFeds($idp);
        $members = $tmp_providers->getSPsForArp($idp);
        $feds_collection = array();
        if ($members->count() == 0)
        {
            return null;
        }
        foreach ($members as $t)
        {
            $feds2 = $t->getMembership();
            foreach($feds2 as $ff)
            {
                $fedid = $ff->getFederation()->getId();
                if(!isset($feds_collection[''.$fedid.'']))
                {
                    $feds_collection[''.$fedid.''] = new \Doctrine\Common\Collections\ArrayCollection();
                     
                }
                $feds_collection[''.$fedid.'']->add($t) ;
            }
        }
        $tmp_requirements = new models\AttributeRequirements;

        $members_byid = array();


        $excluded = $idp->getExcarps();
        $excludedById = array();
        /**
         * @todo do not check if excluded is array
         */
        if (count($excluded) > 0)
        {
            $tmpexl = $this->em->getRepository("models\Provider")->findBy(array('entityid' => $excluded));
            foreach ($tmpexl as $tmpv)
            {
                if($members->contains($tmpv))
                {
                   $members->removeElement($tmpv);
                }
                $excludedById[] = $tmpv->getId();
            }
        }
        
        log_message('debug', 'excluded SP from arp by id:' . serialize($excludedById));

        foreach ($members as $m_value)
        {
            $members_byid[$m_value->getId()] = $m_value;
        }

        $members_requirements = $tmp_requirements->getRequirementsBySPs(array_keys($members_byid));

        log_message('debug', 'Arp: found ' . count($members) . ' for idp (id:' . $idp->getId() . '): ' . $idp->getEntityId() . '');

        $attrs = array();
        /**
         * get supported attributes 
         */
        $tmp_s_attrs = new models\AttributeReleasePolicies;
        /* supported attrs  collection */
        $s_attrs = $tmp_s_attrs->getSupportedAttributes($idp);
        if (count($s_attrs) == 0)
        {
            log_message('warning', 'Arp:  no supported attributes found for idp: ' . $idp->getEntityId());
            return null;
        }
        /* global policy collection */
        $g_attrs = $tmp_s_attrs->getGlobalPolicyAttributes($idp);

        $f_attrs = $tmp_s_attrs->getFedPolicyAttributes($idp);

        $spec_attrs = $tmp_s_attrs->getSpecificPolicyAttributes($idp);

        /* get custom policies */
        $custom_attrs = $tmp_s_attrs->getCustomSpPolicyAttributes($idp);
        $custom_policies = array();
        if (!empty($custom_attrs) and count($custom_attrs) > 0)
        {
            log_message('debug', "Found custom permit/deny for IDP EntityID: " . $idp->getEntityId());
            foreach ($custom_attrs as $key)
            {
                $sp_requester = $this->tmp_providers->getOneSpById($key->getRequester());
                if (!empty($sp_requester))
                {
                    $sp_entityid = $sp_requester->getEntityId();
                    $custom_policies[$sp_entityid][$key->getAttribute()->getName()] = $key->getRawdata();
                } else
                {
                    log_message('warning', 'Found orphaned (or SP is disabled) custom policy with id:' . $key->getId());
                }
            }
        }
        $specific_attributes = array();
        $t_sp = new models\Providers;
        $spec_attrs = $tmp_s_attrs->getSpecificPolicyAttributes($idp);
        if (!empty($spec_attrs))
        {
            foreach ($spec_attrs as $skey => $svalue)
            {
                if (isset($members_byid[$svalue->getRequester()]))
                {
                    $ent = $members_byid[$svalue->getRequester()];
                } elseif (!in_array($svalue->getRequester(), $excludedById))
                {
                    log_message('warning', 'found orphaned arps in db : sprequest:' . $svalue->getRequester() . ' doesn exist in provider table or SP is disabled');
                } else
                {
                    log_message('debug', 'sprequest:' . $svalue->getRequester() . ' is excluded from arp not generating');
                }
                if (!empty($ent))
                {
                    $specific_attributes[$ent->getEntityId()][$svalue->getAttribute()->getName()] = $svalue->getPolicy();
                }
            }
        }
        $federation_policy = array();
        if (!empty($f_attrs))
        {
            foreach ($f_attrs as $f)
            {
                $federation_policy[$f->getRequester()][$f->getAttribute()->getName()] = $f->getPolicy();
            }
        }
        log_message('debug','PE federation policy: '.serialize($federation_policy));

        foreach ($g_attrs as $g)
        {
            $global_policy[$g->getAttribute()->getName()] = $g->getPolicy();
        }
      

        foreach ($s_attrs as $s)
        {
            $supported_attrs[$s->getAttribute()->getName()] = 0;
        }
        $m_policy = array();
        foreach ($supported_attrs as $k => $v)
        {
            if (array_key_exists($k, $global_policy))
            {
                $m_policy[$k] = $global_policy[$k];
            } else
            {
                $m_policy[$k] = 0;
            }
        }

        log_message('debug','PE supported/default merge: '. serialize($m_policy));
        
        
        
        foreach ($members as $m)
        {
            /* set default policy */
            $attrs[$m->getEntityId()] = $m_policy;
            $overwritePolicy = array();
            /* overwite with fede */
            foreach ($feds_collection as $key => $value)
            {
                if (array_key_exists($key, $federation_policy))
                {
                    
                    /* check if entityid is a members of specified federtion */
                    if ($value->contains($m))
                    {
                        log_message('debug','PE : collection '.$key.' :: '.$m->getEntityId());
                        /**
                         * overwrite policy
                         */
                        foreach($federation_policy[$key] as $k2=>$v2)
                        {
                            
                            if(isset($overwritePolicy[$k2]))
                            {
                                if($v2 > $overwritePolicy[$k2])
                                {
                                    $overwritePolicy[$k2] = $v2;
                                }
                            }
                            else
                            {
                                $overwritePolicy[$k2] = $v2;
                            }
                        }             
                    }
                }
            }
            $attrs[$m->getEntityId()] = array_replace($attrs[$m->getEntityId()], array_intersect_key($overwritePolicy,$attrs[$m->getEntityId()]));
        }
        $i = 0;

        foreach ($specific_attributes as $pkey => $pvalue)
        {          
            if (array_key_exists($pkey, $attrs))
            {
                //$attrs[$pkey] = array_replace($attrs[$pkey], $pvalue);             
                $attrs[$pkey] = array_merge($attrs[$pkey], array_intersect_key($pvalue, $attrs[$pkey]));
            }
        }
        foreach ($members as $m)
        {
            $r = null;
            $m_entityid = $m->getEntityId();
            $release[$m_entityid]['attributes'] = array();
            $release[$m_entityid]['custom'] = array();
            $release[$m_entityid]['entityid'] = $m_entityid;
            //$release[$m_entityid]['name'] = $m->getName();
            $release[$m_entityid]['name'] = $m->getNameToWebInLang($lang,'sp');
            $release[$m_entityid]['attributes'] = $supported_attrs;
            $release[$m_entityid]['spid'] = $m->getId();
            $release[$m_entityid]['req'] = array();
            if (array_key_exists($m->getId(), $members_requirements))
            {
                $r = $members_requirements[$m->getId()];
            }

            if (!empty($r))
            {
                $requiredAttrs = array();
                foreach ($r as $rk)
                {
                    $requiredAttrs[$rk->getAttribute()->getName()] = $rk->getStatus();
                }

                foreach ($attrs[$m_entityid] as $attr_name => $attr_value)
                {
                    $release[$m_entityid]['req'] = $requiredAttrs;
                    if (array_key_exists($attr_name, $requiredAttrs))
                    {
                        $rel_value = $attrs[$m_entityid][$attr_name];
                        $req_value = $requiredAttrs[$attr_name];
                        if ($req_value == 'required' && ($rel_value > 0))
                        {
                            $release[$m_entityid]['attributes'][$attr_name] = 1;
                        } elseif ($rel_value == 2)
                        {
                            $release[$m_entityid]['attributes'][$attr_name] = 1;
                        } else
                        {
                            $release[$m_entityid]['attributes'][$attr_name] = 0;
                        }
                    }
                }
            } else
            {
                $feds_1 = $m->getActiveFederations();
                if (!empty($feds_1))
                {
                    $tmp_fed_req = array();
                    foreach ($feds_1->getValues() as $f_key => $f_value)
                    {
                        /* check if sp's federation matches idp federation */
                        if (array_key_exists($f_value->getId(), $feds_collection))
                        {
                            $n_req = array();
                            $n_req = $tmp_requirements->getRequirementsByFed($f_value);
                            if (!empty($n_req))
                            {
                                $requiredAttrs = array();
                                foreach ($n_req as $nk)
                                {
                                    $requiredAttrs[$nk->getAttribute()->getName()] = $nk->getStatus();
                                }
                                $release[$m->getEntityId()]['req'] = $requiredAttrs;
                                foreach ($attrs[$m->getEntityId()] as $attr_name => $attr_value)
                                {
                                    if (array_key_exists($attr_name, $requiredAttrs))
                                    {
                                        $rel_value = $attrs[$m->getEntityId()][$attr_name];
                                        $req_value = $requiredAttrs[$attr_name];
                                        if ($req_value == 'required' && ($rel_value > 0))
                                        {
                                            $release[$m->getEntityId()]['attributes'][$attr_name] = 1;
                                        } elseif ($rel_value == 2)
                                        {
                                            $release[$m->getEntityId()]['attributes'][$attr_name] = 1;
                                        }
                                    }
                                }
                            } else
                            {
                                foreach ($attrs[$m->getEntityId()] as $attr_name => $attr_value)
                                {
                                    $release[$m->getEntityId()]['attributes'][$attr_name] = 0;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($custom_policies as $key => $value)
        {

            if (array_key_exists($key, $release))
            {
                $release[$key]['custom'] = $value;
            }
        }
        return $release;
    }

}
