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
        if (empty($this->full) && empty($this->defaults['static']))
        {
            log_message('error', 'Cannot import if metadata if settings full and static are false');
            return false;
        }
        if (array_key_exists('federations', $this->defaults))
        {
            $federations = $this->em->getRepository("models\Federation")->findBy(array('name' => $this->defaults['federations']));
        }
        /**
         * if param static is not provided then static is set to true 
         */
        if (array_key_exists('static', $this->defaults) && $this->defaults['static'] === FALSE)
        {
            log_message('debug', "l - static false");
            $static = false;
        } else
        {
            log_message('debug', "l - static true");
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


        $this->metadata_in_array = $this->ci->metadata2array->rootConvert($metadata, $full);

        if (empty($this->metadata_in_array))
        {
            return null;
        }
        if (!is_array($this->metadata_in_array))
        {
            return null;
        }
        if (count($this->metadata_in_array) == 0)
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
                            if ($cmfederations->count() == 1)
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
                                $cm->removeFederation($f);
                                $this->em->persist($cm);
                                $this->em->flush();
                            }
                        }
                    }
                }
            }
        }


        $i = 0;
        foreach ($this->metadata_in_array as $ent)
        {
            log_message('debug', 'type of entity: ' . $ent['type']);
            if ($ent['type'] == 'BOTH' or $ent['type'] == $type or $type == 'ALL')
            {
                $importedProvider = new models\Provider;
                $importedProvider->setProviderFromArray($ent);
                $tmp = new models\Providers;
                $existingProvider = new models\Provider;
                $existingProvider = $tmp->getOneByEntityId($importedProvider->getEntityId());

                if (empty($existingProvider))
                {
                    $importedProvider->setStatic($static);
                    $importedProvider->setLocal($local);
                    $importedProvider->setActive($active);

                    /**
                     * insert new entity into database and add it to federations 
                     */
                    foreach ($federations as $f)
                    {
                        $importedProvider->setFederation($f);
                    }

                    $this->em->persist($importedProvider);
                } else
                {
                    $elocal = $existingProvider->getLocal();
                    $update_allowed = (($elocal AND $overwritelocal) OR !$elocal);
                    if ($update_allowed)
                    {
                        log_message('debug', 'update_allowed is: ' . $update_allowed . ' and local is :' . $this->defaults['local']);
                        $existingProvider->overwriteByProvider($importedProvider);
                        $existingProvider->setLocal($this->defaults['local']);
                        $existingProvider->setStatic($static);
                    }
                    foreach ($federations as $f)
                    {
                        $existingProvider->setFederation($f);
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
                }
            }
        }
        $this->em->flush();
        //  echo count($importedProvider);  
        //  print_r($metadata_in_array);
        return true;
    }

    public function importIntoQueue()
    {
        
    }

}
