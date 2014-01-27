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
 * Metadata2import Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadata2import {

    private $metadata_in_array;
    private $metadata;
    private $type;
    private $full;
    private $defaults;
    private $other;
    protected $ci;
    protected $em;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->library('metadata2array');
        $this->metadata = null;
        $this->type = null;
        $this->full = false;

        $this->defaults = array(
            'static' => true,
            'local' => false,
            'federation' => null,
            'live' => false,
            'removeexternal' => false,
            'mailreport' => false,
        );
        $this->other = null;
    }

    /**
     * metadata must be valid - it won't be  verified by this function
     * type - what type of entitites you want to import - idp,sp,all
     * full - if it's false then basic imnformation are set and rest is stored in static
     * defaults - must be array with default settings
     *          values of defaults array:
     *                  static - true/false 
     *                  local - true/false    - entity is local/external
     *                  overwritelocal - true/false - can we overwtite information if local
     * other - some other - can be used later
     */
    public function import($metadata, $type, $full, array $defaults, $other = null)
    {
        $this->metadata = $metadata;
        $this->full = $full;
        $this->type = $type;
        $this->other = $other;
        $this->defaults = array_merge($this->defaults, $defaults);
        $coclist = $this->em->getRepository("models\Coc")->findAll();
        $attrtmp = $this->em->getRepository("models\Attribute")->findAll();
        $attributes = array();
        $report = array(
            'subject' => '',
            'body' => array(),
            'provider' => array(
                'new' => array(),
                'del' => array(),
                'joinfed' => array(),
                'leavefed' => array(),
            ),
        );
        foreach ($attrtmp as $v)
        {
            $attributes['' . $v->getOid() . ''] = $v;
        }
        log_message('debug', 'IMPORT attr in table: ' . serialize(array_keys($attributes)));
        $coclistconverted = array();
        $coclistarray = array();

        foreach ($coclist as $k => $c)
        {
            $coclistconverted[$c->getId()] = $c;
            $coclistarray['' . $c->getId() . ''] = $c->getUrl();
        }
        if (empty($this->full) && empty($this->defaults['static']))
        {
            return false;
        }
        if (array_key_exists('federations', $this->defaults))
        {
            $federations = $this->em->getRepository("models\Federation")->findBy(array('name' => $this->defaults['federations']));
            foreach($federations as $ff)
            {
                $report['body'][] = 'Sync with federation: '.$ff->getName();
            }
        }
        /**
         * if param static is not provided then static is set to true 
         */
        if (array_key_exists('static', $this->defaults) && $this->defaults['static'] === FALSE)
        {
            $static = false;
        } else
        {
            $static = true;
        }
        if (array_key_exists('local', $this->defaults) && $this->defaults['local'] === true)
        {
            $local = true;
        } else
        {
            $local = false;
        }
        if (array_key_exists('active', $this->defaults) && $this->defaults['active'] === true)
        {
            $active = true;
        } else
        {
            $active = false;
        }
        if (array_key_exists('overwritelocal', $this->defaults) && $this->defaults['overwritelocal'] === TRUE)
        {
            $overwritelocal = true;
        } else
        {
            $overwritelocal = false;
        }

        if (array_key_exists('removeexternal', $this->defaults) && $this->defaults['removeexternal'] === TRUE)
        {
            $removeexternal = true;
        } else
        {
            $removeexternal = false;
        }
        $mailReport = FALSE;
        $mailAddresses = array();
        if (array_key_exists('mailreport', $this->defaults) && $this->defaults['mailreport'] === TRUE)
        {
            $mailReport = TRUE;
            if (array_key_exists('email', $this->defaults) && !empty($this->defaults['email']))
            {
                $mailAddresses[] = $this->defaults['email'];
            } else
            {
                $a = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator'));
                $a_members = $a->getMembers();
                foreach ($a_members as $m)
                {
                    $mailAddresses[] = $m->getEmail();
                }
            }
        }
        $this->metadata_in_array = $this->ci->metadata2array->rootConvert($metadata, $full);

        if (!(empty($this->metadata_in_array) || is_array($this->metadata_in_array) || count($this->metadata_in_array) == 0))
        {
            return null;
        }
        if ($removeexternal === TRUE)
        {
            $current_providers = array();
            foreach ($federations as $f)
            {
                $fid = $f->getId();
                $current_providers[$fid] = array();
                $cmembers = $f->getMembers();
                foreach ($cmembers as $cm)
                {
                    $centity = $cm->getEntityId();
                    if (!array_key_exists($centity, $this->metadata_in_array))
                    {
                        $cmislocal = $cm->getLocal();
                        if ($cmislocal === FALSE)
                        {
                            $cmfederations = $cm->getFederations();
                            if ($cmfederations->count() === 1)
                            {
                                $aclresources = $this->em->getRepository("models\AclResource")->findBy(array('resource' => $cm->getId()));
                                if (!empty($aclresources))
                                {
                                    foreach ($aclresources as $a)
                                    {
                                        $this->em->remove($a);
                                    }
                                }
                                $cmstaticmetadata = $cm->getStaticMetadata();
                                if (!empty($cmstaticmetadata))
                                {
                                    $this->em->remove($cmstaticmetadata);
                                }

                                $report['provider']['del'][] = $cm->getEntityId();
                                $this->em->remove($cm);

                                $this->em->flush();
                            } else
                            {
                                $p_tmp = new models\AttributeReleasePolicies;
                                $arp_fed = $p_tmp->getFedPolicyAttributesByFed($cm, $f);
                                if (!empty($arp_fed) && is_array($arp_fed) && count($arp_fed) > 0)
                                {
                                    foreach ($arp_fed as $r)
                                    {
                                        $this->em->remove($r);
                                    }
                                }
                                $report['provider']['leavefed'][] = $cm->getEntityId();
                                $cm->removeFederation($f);
                                $this->em->persist($cm);
                                $this->em->flush();
                            }
                        }
                    }
                }
            }
        }

        $tmpProviders = new models\Providers;
        $i = 0;
        foreach ($this->metadata_in_array as $ent)
        {
            if ($ent['type'] == 'BOTH' or $ent['type'] == $type or $type == 'ALL')
            {
                $importedProvider = new models\Provider;
                $importedProvider->setProviderFromArray($ent);
                //$tmp = new models\Providers;
                //              $existingProvider = new models\Provider;
                //$existingProvider = $tmp->getOneByEntityId($importedProvider->getEntityId());
                $existingProvider = $tmpProviders->getOneByEntityId($importedProvider->getEntityId());
                if (empty($existingProvider))
                {
                    $importedProvider->setStatic($static);
                    $importedProvider->setLocal($local);
                    $importedProvider->setActive($active);
                    if (array_key_exists('coc', $ent))
                    {
                        if (!empty($ent['coc']))
                        {
                            $y = array_search($ent['coc'], $coclistarray);
                            if ($y != NULL OR $y != FALSE)
                            {
                                $celement = $coclistconverted['' . $y . ''];
                                if (!empty($celement))
                                {
                                    $importedProvider->setCoc($celement);
                                }
                            }
                        } else
                        {
                            $importedProvider->setCoc(NULL);
                        }
                    }
                    if (isset($ent['details']['reqattrs']))
                    {
                        $attrsset = array();
                        foreach ($ent['details']['reqattrs'] as $r)
                        {
                            if (array_key_exists($r['name'], $attributes))
                            {
                                if (!in_array($r['name'], $attrsset))
                                {
                                    $reqattr = new models\AttributeRequirement;
                                    $reqattr->setAttribute($attributes['' . $r['name'] . '']);
                                    $reqattr->setType('SP');
                                    $reqattr->setSP($importedProvider);
                                    if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0)
                                    {
                                        $reqattr->setStatus('required');
                                    } else
                                    {
                                        $reqattr->setStatus('desired');
                                    }
                                    $reqattr->setReason('');
                                    $importedProvider->setAttributesRequirement($reqattr);
                                    $this->em->persist($reqattr);
                                    $attrsset[] = $r['name'];
                                }
                            } else
                            {
                                log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
                            }
                        }
                    }

                    /**
                     * insert new entity into database and add it to federations 
                     */
                    foreach ($federations as $f)
                    {
                        $importedProvider->setFederation($f);
                    }
                    $report['provider']['new'][] = $importedProvider->getEntityId();
                    $this->em->persist($importedProvider);
                } else
                {
                    $elocal = $existingProvider->getLocal();
                    $isLocked = $existingProvider->getLocked();
                    $updateAllowed = (($elocal AND $overwritelocal AND !$isLocked) OR !$elocal);
                    if ($updateAllowed)
                    {
                        log_message('debug', 'updateAllowed is: ' . $updateAllowed . ' and local is :' . $this->defaults['local']);
                        $existingProvider->overwriteByProvider($importedProvider);
                        $existingProvider->setLocal($this->defaults['local']);
                        $existingProvider->setStatic($static);

                        if (array_key_exists('coc', $ent))
                        {
                            if (!empty($ent['coc']))
                            {
                                $y = array_search($ent['coc'], $coclistarray);
                                if ($y != NULL OR $y != FALSE)
                                {
                                    $celement = $coclistconverted['' . $y . ''];
                                    if (!empty($celement))
                                    {
                                        $existingProvider->setCoc($celement);
                                    }
                                }
                            } else
                            {
                                $existingProvider->setCoc(NULL);
                            }
                        }
                        $existingRequiredAttrs = array();
                        $requiteAttrs = $existingProvider->getAttributesRequirement();
                        foreach ($requiteAttrs as $a)
                        {
                            $existingRequiredAttrs['' . $a->getAttribute()->getOid() . ''] = $a;
                        }
                        $duplicatesFound = FALSE;
                        $duplicates = array();
                        foreach ($requiteAttrs as $pl)
                        {
                            $duplicates[] = $pl->getAttribute()->getOid();
                        }
                        if (count($duplicates) != count(array_unique($duplicates)))
                        {
                            $duplicatesFound = TRUE;
                            log_message('warning', __METHOD__ . ' found duplicates in attrs requirements, doing cleanning');
                        }
                        if ($duplicatesFound === TRUE)
                        {
                            foreach ($requiteAttrs as $pl)
                            {
                                unset($existingRequiredAttrs['' . $pl->getAttribute()->getOid() . '']);
                                $requiteAttrs->removeElement($pl);
                                $this->em->remove($pl);
                            }
                        }

                        if (isset($ent['details']['reqattrs']) && is_array($ent['details']['reqattrs']))
                        {
                            $convertedReqs = array();
                            foreach ($ent['details']['reqattrs'] as $k => $v)
                            {
                                $convertedReqs[$v['name']] = array('k' => $k, 'req' => $v['req']);
                            }
                            foreach ($requiteAttrs as $v)
                            {
                                if (!array_key_exists($v->getAttribute()->getOid(), $convertedReqs))
                                {
                                    $requiteAttrs->removeElement($v);
                                    unset($existingRequiredAttrs['' . $convertedReqs['' . $v->getAttribute()->getOid() . '']['k'] . '']);
                                    $this->em->remove($v);
                                } else
                                {
                                    $tmpattr = $ent['details']['reqattrs']['' . $convertedReqs['' . $v->getAttribute()->getOid() . '']['k'] . ''];
                                    if (isset($tmpattr['req']) && strcasecmp($tmpattr['req'], 'true') == 0)
                                    {
                                        $v->setStatus('required');
                                    } else
                                    {
                                        $v->setStatus('desired');
                                    }
                                    $this->em->persist($v);

                                    unset($ent['details']['reqattrs']['' . $convertedReqs['' . $v->getAttribute()->getOid() . '']['k'] . '']);
                                }
                            }
                            foreach ($ent['details']['reqattrs'] as $r)
                            {
                                if (array_key_exists($r['name'], $attributes))
                                {
                                    if (array_key_exists($r['name'], $existingRequiredAttrs))
                                    {
                                        if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0)
                                        {
                                            $existingRequiredAttrs['' . $r['name'] . '']->setStatus('required');
                                        } else
                                        {
                                            $existingRequiredAttrs['' . $r['name'] . '']->setStatus('desired');
                                        }
                                        $this->em->persist($existingRequiredAttrs['' . $r['name'] . '']);
                                    } else
                                    {
                                        $reqattr = new models\AttributeRequirement;
                                        $reqattr->setAttribute($attributes['' . $r['name'] . '']);
                                        $reqattr->setType('SP');
                                        $reqattr->setSP($existingProvider);
                                        if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0)
                                        {
                                            $reqattr->setStatus('required');
                                        } else
                                        {
                                            $reqattr->setStatus('desired');
                                        }
                                        $reqattr->setReason('');
                                        $existingProvider->setAttributesRequirement($reqattr);
                                        $this->em->persist($reqattr);
                                    }
                                } else
                                {
                                    log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
                                }
                            }
                        }
                    }

                    foreach ($federations as $f)
                    {
                        if (!$isLocked)
                        {
                            $existingProviderFederations = $existingProvider->getFederations();
                            if (!$existingProviderFederations->contains($f))
                            {
                                $report['provider']['joinfed'] = $existingProvider->getEntityId();
                                $existingProvider->setFederation($f);
                            }
                        }
                    }

                    /**
                     * @todo decide if overwiter status/static etc 
                     */
                    $this->em->persist($existingProvider);
                }
                $i++;
                if ($i == 100)
                {
                    $this->em->flush();
                    $i = 0;
                    $tmpProviders = new models\Providers;
                }
            }
        }
        log_message('debug', __METHOD__ . ' import ' . serialize($report));
        $this->ci->load->library('email_sender');
            $body = 'Report'.PHP_EOL;

            foreach($report['body']  as $bb)
            {
                $body .= $bb.PHP_EOL;
            }


            $structureChanged = FALSE;
            if(count($report['provider']['new'])> 0)
            {
                $structureChanged = TRUE;
                $body .='List new providers registered during sync:'.PHP_EOL;
                foreach($report['provider']['new'] as $a)
                {
                    $body .= $a.PHP_EOL;
                }
            }
            if(count($report['provider']['joinfed'])> 0)
            {
                $structureChanged = TRUE;
                $body .='List existing providers added to federation during sync:'.PHP_EOL;
                foreach($report['provider']['joinfed'] as $a)
                {
                    $body .= $a.PHP_EOL;
                }
            }
            if(count($report['provider']['del'])> 0)
            {
                $structureChanged = TRUE;
                $body .='List providers removed from the system during sync:'.PHP_EOL;
                foreach($report['provider']['del'] as $a)
                {
                    $body .= $a.PHP_EOL;
                }
            }
            if(count($report['provider']['leavefed'])> 0)
            {
                $structureChanged = TRUE;
                $body .='List providers removed from federation during sync:'.PHP_EOL;
                foreach($report['provider']['leavefed'] as $a)
                {
                    $body .= $a.PHP_EOL;
                }
            }
            $nbody = '';
            if(!$structureChanged)
            {
                $nbody ='No entities have been added/removed after sync/import'.PHP_EOL;
            }
            else
            {
                $this->ci->email_sender->addToMailQueue(array('gfedmemberschanged'),null,'Federation sync/import report',$body,array(),false);
            }
        $this->em->flush();

        if($mailReport)
        {
            $this->ci->email_sender->send($mailAddresses,'Federation sync/import report',$body.$nbody);
        }
        return true;
    }

    public function importIntoQueue()
    {
        
    }

}
