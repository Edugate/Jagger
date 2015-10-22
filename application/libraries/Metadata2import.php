<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Metadata2import
{

    private $metadataInArray;
    private $metadata;
    private $type;
    private $full;
    /**
     * @var array $defaults
     */
    private $defaults;
    private $other;
    protected $ci;
    /**
     * @var bool $copyFedAttrReq
     */
    private $copyFedAttrReq;
    /**
     * @var Doctrine\ORM\EntityManager $em
     */
    protected $em;
    protected $attrsDefinitions;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->library('metadata2array');
        $this->metadata = null;
        $this->type = null;
        $this->full = false;
        $this->copyFedAttrReq = false;

        $this->defaults = array(
            'localimport' => false,
            'static' => true,
            'local' => false,
            'federationid' => null,
            'live' => false,
            'removeexternal' => false,
            'mailreport' => false,
            'active' => false,
            'overwritelocal' => false,
            'attrreqinherit' => false,
        );
        $this->other = null;
    }

    /**
     * @param $report
     * @return bool
     */
    private function genReport($report) {
        if (!is_array($report)) {
            return false;
        }
        $this->ci->load->library('email_sender');
        $body = 'Report' . PHP_EOL;
        foreach ($report['body'] as $bb) {
            $body .= $bb . PHP_EOL;
        }
        $structureChanged = false;
        $sections = array(
            'new' => 'List new providers registered during sync',
            'joinfed' => 'List existing providers added to federation during sync',
            'del' => 'List providers removed from the system during sync',
            'leavefed' => 'List providers removed from federation during sync');
        foreach ($sections as $section => $sectionTitle) {
            if (count($report['provider']['' . $section . '']) > 0) {
                $structureChanged = TRUE;
                $body .= $sectionTitle . ':' . PHP_EOL;
                foreach ($report['provider']['' . $section . ''] as $a) {
                    $body .= $a . PHP_EOL;
                }
            }
        }
        if ($structureChanged) {
            $this->ci->email_sender->addToMailQueue(array('gfedmemberschanged'), null, 'Federation sync/import report', $body, array(), false);
        }
        return true;
    }

    private function getAttributesByNames() {
        if (!is_array($this->attrsDefinitions)) {
            /**
             * @var $attrs \models\Attribute[]
             */
            $attrs = $this->em->getRepository("models\Attribute")->findAll();
            $this->attrsDefinitions = array();
            foreach ($attrs as $v) {
                $this->attrsDefinitions['' . $v->getOid() . ''] = $v;
            }
        }
        return $this->attrsDefinitions;
    }

    /**
     * @param \models\Federation $federation
     * @return array
     */
    private function getAttrReqByFed(\models\Federation $federation) {
        /**
         * @var $fedReqAttrs models\AttributeRequirement[]
         */
        $fedReqAttrs = $federation->getAttributesRequirement();

        $attrRequiredByFed = array();

        foreach ($fedReqAttrs as $rv) {
            $attrRequiredByFed[] = array(
                'name' => $rv->getAttribute()->getOid(),
                'req' => $rv->isRequiredToStr()
            );
        }
        return $attrRequiredByFed;
    }


    public function import($metadata, $type, $full, array $defaults, $other = null) {
        $tmpProviders = new models\Providers;
        $this->metadata = &$metadata;
        $this->full = $full;
        $this->type = $type;
        $this->other = $other;
        $this->defaults = array_merge($this->defaults, $defaults);
        if (empty($this->full) && empty($this->defaults['static'])) {
            return false;
        }

        /**
         * @var $cocreglist models\Coc[]
         */
        $cocreglist = $this->em->getRepository("models\Coc")->findBy(array('type' => array('regpol', 'entcat')));
        $attributes = $this->getAttributesByNames();


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

        $coclistconverted = array();
        $coclistarray = array();
        /**
         * @var models\Coc[] $regpollistconverted
         */
        $regpollistconverted = array();
        $regpollistarray = array();

        foreach ($cocreglist as $cocreg) {
            $cocregtype = $cocreg->getType();
            if ($cocregtype === 'entcat') {
                $coclistconverted['' . $cocreg->getId() . ''] = $cocreg;
                $coclistarray['' . $cocreg->getId() . ''] = $cocreg->getUrl();
                $ncoclistarray['' . $cocreg->getSubtype() . '']['' . $cocreg->getId() . ''] = $cocreg->getUrl();
            } else {
                $regpollistconverted['' . $cocreg->getId() . ''] = $cocreg;
                $regpollistarray['' . $cocreg->getId() . ''] = $cocreg->getUrl();
            }
        }


        /**
         * @var $federation models\Federation
         */

        if (array_key_exists('federationid', $this->defaults)) {
            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $this->defaults['federationid']));
            if ($federation === null) {
                log_message('error', __METHOD__ . ' federation not found yu want to import to');
                return false;
            }

            $report['body'][] = 'Sync with federation: ' . $federation->getName();

        }
        /**
         * if param static is not provided then static is set to true
         */
        $static = $this->defaults['static'];
        $local = $this->defaults['local'];
        $active = $this->defaults['active'];
        $overwritelocal = $this->defaults['overwritelocal'];
        // remove external entities if they're not member of any other federation
        $removeexternal = $this->defaults['removeexternal'];
        $attrreqinherit = $this->defaults['attrreqinherit'];


        $timeStart = microtime(true);
        $this->metadataInArray = $this->ci->metadata2array->rootConvert($metadata, $full);
        $timeEnd = microtime(true);
        $timeExecution = $timeEnd - $timeStart;
        log_message('debug', __METHOD__ . ' time execution of converting metadata to array took: ' . $timeExecution);
        if (!is_array($this->metadataInArray) || count($this->metadataInArray) == 0) {
            \log_message('warning', __METHOD__ . ' converting xml metadata 
                               into array resulted empty array or null value');
            return false;
        }


        $attrRequiredByFed = $this->getAttrReqByFed($federation);
        if ($attrreqinherit === true && count($attrRequiredByFed) > 0) {
            $this->copyFedAttrReq = true;
        }

        $fedMembershipColl = $federation->getMembership();

        /**
         * @var $membership \models\FederationMembers[]
         */
        $membership = $fedMembershipColl->toArray();
        $membershipByEnt = array();
        foreach ($membership as $k => $m) {
            $membershipByEnt['' . $m->getProvider()->getEntityId() . ''] = array('mshipKey' => $k, 'mship' => &$m);
        }

        // run sync
        if ($this->defaults['localimport'] !== true) {
            \log_message('info', __METHOD__ . ' running as sync for ' . $federation->getName());
            foreach ($fedMembershipColl as $m) {
                $membershipByEnt['' . $m->getProvider()->getEntityId() . ''] = $m;
            }
            // list entities in the source
            $membersFromExtSrc = array();
            $counter = 0;
            foreach ($this->metadataInArray as $ent) {
                $startTime = microtime(true);
                // START if type matches
                if (!isset($ent['type'])) {
                    log_message('error', __METHOD__ . ' missing type for entity: ' . $ent['entityid']);
                    continue;
                }
                $counter++;
                if (!($type === 'ALL' || $ent['type'] === 'BOTH' || $ent['type'] === $type)) {
                    continue;
                }


                $importedProvider = new models\Provider;
                $importedProvider->setProviderFromArray($ent);
                /**
                 * @var $existingProvider models\Provider
                 */
                $existingProvider = $tmpProviders->getOneByEntityId($ent['entityid']);
                if ($existingProvider === null) {

                    $membersFromExtSrc[] = $importedProvider->getEntityId();
                    $importedProvider->setStatic($static);
                    $importedProvider->setLocal($local);
                    $importedProvider->setActive($active);
                    // entityCategory begin
                    foreach ($ent['coc'] as $attrname => $v) {
                        if (isset($ncoclistarray['' . $attrname . ''])) {
                            foreach ($v as $kv => $pv) {
                                $y = array_search($v, $ncoclistarray['' . $attrname . '']);
                                if ($y !== NULL && $y !== FALSE) {
                                    $celement = $coclistconverted['' . $y . ''];
                                    if (!empty($celement)) {
                                        $importedProvider->setCoc($celement);
                                    }
                                }
                            }
                        }
                    }
                    foreach ($ent['regpol'] as $k => $v) {
                        $y = array_search($v['url'], $regpollistarray);

                        if ($y != NULL && $y != FALSE) {
                            foreach ($regpollistconverted as $p) {
                                $purl = $p->getUrl();
                                $plang = $p->getLang();
                                if (strcmp($purl, $v['url']) == 0 && strcasecmp($plang, $v['lang']) == 0) {
                                    $importedProvider->setCoc($p);
                                    break;
                                }
                            }
                        }
                    }

                    // end entityCategory
                    // attr req  start
                    if (isset($ent['details']['reqattrs'])) {
                        $this->setReqAttrs($ent['details']['reqattrs'], $attrRequiredByFed, $importedProvider);
                    }

                    // attr req end
                    $newmembership = new models\Federationmembers();
                    $newmembership->setProvider($importedProvider);
                    $newmembership->setFederation($federation);
                    $newmembership->setJoinstate('3');
                    $report['provider']['new'][] = $importedProvider->getEntityId();
                    $this->em->persist($newmembership);
                    $this->em->persist($importedProvider);
                } // END for new provider
                else { // provider exist
                    $membersFromExtSrc[] = $existingProvider->getEntityId();
                    $isLocal = $existingProvider->getLocal();
                    $isLocked = $existingProvider->getLocked();
                    $updateAllowed = (($isLocal && $overwritelocal && !$isLocked) || !$isLocal);
                    if ($updateAllowed) {
                        $existingProvider->overwriteByProvider($importedProvider);
                        $currentCocs = $existingProvider->getCoc();
                        foreach ($currentCocs as $c) {
                            $cType = $c->getType();
                            if ($cType === 'entcat') {
                                $cUrl = $c->getUrl();
                                $cSubtype = $c->getSubtype();
                                if (!isset($ent['coc']['' . $cSubtype . ''])) {
                                    $existingProvider->removeCoc($c);
                                } else {
                                    $y = array_search($cUrl, $ent['coc']['' . $cSubtype . '']);
                                    if ($y === NULL || $y === FALSE) {
                                        $existingProvider->removeCoc($c);
                                    } else {
                                        unset($ent['coc']['' . $cSubtype . '']['' . $y . '']);
                                    }
                                }
                            } elseif ($cType === 'regpol') {
                                $cUrl = $c->getUrl();
                                $cLang = $c->getLang();
                                $cExist = FALSE;
                                $cKey = null;
                                foreach ($ent['regpol'] as $k => $v) {
                                    if (strcmp($cUrl, $v['url']) == 0 && strcasecmp($cLang, $v['lang']) == 0) {
                                        $cExist = TRUE;
                                        $cKey = $k;
                                        break;
                                    }
                                }
                                if ($cExist === FALSE) {
                                    $existingProvider->removeCoc($c);
                                } else {
                                    unset($ent['regpol']['' . $cKey . '']);
                                }
                            }
                        }
                        foreach ($ent['coc'] as $attrname => $v) {
                            if (isset($ncoclistarray['' . $attrname . ''])) {
                                foreach ($v as $k => $p) {
                                    $y = array_search($p, $ncoclistarray['' . $attrname . '']);
                                    if ($y !== null && $y !== FALSE) {
                                        $existingProvider->setCoc($coclistconverted['' . $y . '']);
                                    }
                                }
                            }
                        }
                        foreach ($ent['regpol'] as $v) {
                            foreach ($regpollistconverted as $c) {
                                $cUrl = $c->getUrl();
                                $cLang = $c->getLang();
                                if (strcmp($cUrl, $v['url']) == 0 && strcasecmp($cLang, $v['lang']) == 0) {
                                    $existingProvider->setCoc($c);
                                    break;
                                }
                            }
                        }

                        $existingProvider->setStatic($static);
                        $duplicateControl = array();
                        $requiredAttrs = $existingProvider->getAttributesRequirement();
                        foreach ($requiredAttrs as $a) {
                            $oid = $a->getAttribute()->getOid();
                            if (in_array('' . $oid . '', $duplicateControl)) {
                                $requiredAttrs->removeElement($a);
                                $this->em->remove($a);
                            } else {
                                $duplicateControl[] = $oid;
                            }
                        }
                        if (isset($ent['details']['reqattrs']) && is_array($ent['details']['reqattrs'])) {
                            foreach ($requiredAttrs as $r) {
                                $found = false;
                                $roid = $r->getAttribute()->getOid();
                                foreach ($ent['details']['reqattrs'] as $k => $v) {
                                    if (strcmp($roid, $v['name']) == 0) {
                                        $found = true;
                                        if (isset($v['req']) && strcasecmp($v['req'], 'true') == 0) {
                                            $r->setStatus('required');
                                        } else {
                                            $r->setStatus('desired');
                                        }
                                        unset($ent['details']['reqattrs']['' . $k . '']);
                                        $this->em->persist($r);
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $requiredAttrs->removeElement($r);
                                    $this->em->remove($r);
                                }
                            }
                            foreach ($ent['details']['reqattrs'] as $nr) {
                                if (isset($nr['name']) && array_key_exists($nr['name'], $attributes)) {
                                    $reqattr = new models\AttributeRequirement;
                                    $reqattr->setAttribute($attributes['' . $nr['name'] . '']);
                                    $reqattr->setType('SP');
                                    $reqattr->setSP($existingProvider);
                                    if (isset($nr['req']) && strcasecmp($nr['req'], 'true') == 0) {
                                        $reqattr->setStatus('required');
                                    } else {
                                        $reqattr->setStatus('desired');
                                    }
                                    $existingProvider->setAttributesRequirement($reqattr);
                                    $this->em->persist($reqattr);
                                    $duplicateControl[] = $nr['name'];
                                } else {
                                    log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $nr['name']);
                                }
                            }
                            if ($ent['details']['reqattrsinmeta'] === false & $this->copyFedAttrReq === true) {
                                foreach ($attrRequiredByFed as $rt) {
                                    if (!in_array($rt['name'], $duplicateControl)) {
                                        $reqattr = new models\AttributeRequirement;
                                        $reqattr->setAttribute($attributes['' . $rt['name'] . '']);
                                        $reqattr->setType('SP');
                                        $reqattr->setSP($existingProvider);
                                        if (isset($rt['req']) && strcasecmp($rt['req'], 'true') == 0) {
                                            $reqattr->setStatus('required');
                                        } else {
                                            $reqattr->setStatus('desired');
                                        }
                                        $existingProvider->setAttributesRequirement($reqattr);
                                        $this->em->persist($reqattr);
                                        $duplicateControl[] = $rt['name'];
                                    }
                                }
                            }
                        }
                        /**
                         * END attrs requirements processing
                         */
                    }


                    if ((($isLocal && !$isLocked) || !($isLocal)) && !array_key_exists($existingProvider->getEntityId(), $membershipByEnt)) {
                        $newMembership = new models\FederationMembers;
                        $newMembership->setProvider($existingProvider);
                        $newMembership->setFederation($federation);
                        $newMembership->setJoinstate('3');
                        $this->em->persist($newMembership);
                        $report['provider']['joinfed'][] = $existingProvider->getEntityId();

                    }
                    $this->em->persist($existingProvider);
                }
                if ($counter > 300) {
                    $this->em->flush();
                    $counter = 0;
                }

                $endTime = microtime(true);
                $looptime = $endTime - $startTime;
                log_message('debug', 'running in loop time execution:: ' . $looptime);
            }

            $currMembersList = array_keys($membershipByEnt);
            $membersdiff = array_diff($currMembersList, $membersFromExtSrc);
            if (count($membersdiff) > 0) {
                log_message('debug', __METHOD__ . ' found diff in membership, not existing members in external metadata ' . serialize($membersdiff));
                foreach ($membersdiff as $d) {
                    $mm2 = $membershipByEnt['' . $d . ''];
                    log_message('debug', __METHOD__ . ' proceeding removing ' . $mm2->getProvider()->getEntityId() . ' from fed:' . $federation->getName());
                    $mm2joinstate = $mm2->getJoinState();
                    $tmpprov = $mm2->getProvider();

                    $isLocal = $mm2->getProvider()->getLocal();

                    log_message('debug', __METHOD__ . ' current state of provider:: joinstate-' . $mm2joinstate . ', islocal-' . $isLocal);
                    if (!($mm2joinstate === 0 || $mm2joinstate === 1)) {

                        log_message('debug', 'proceeding ' . $mm2->getProvider()->getEntityId() . ' joinstatus:' . $mm2joinstate);
                        if (!$isLocal && $removeexternal) {

                            $ff = $tmpprov->getFederations();
                            $countFeds = $ff->count();
                            if ($countFeds < 2 && $ff->contains($federation)) {
                                $report['provider']['del'][] = $tmpprov->getEntityId();
                                $this->em->remove($tmpprov);
                            } else {
                                $report['provider']['leavefed'][] = $tmpprov->getEntityId();
                                $this->em->remove($mm2);
                            }
                        } elseif ($mm2joinstate != 2) {
                            $this->em->remove($mm2);
                        }
                    } elseif ($mm2joinstate == 0 && !$isLocal) {
                        if ($removeexternal) {
                            $countFeds = $mm2->getProvider()->getFederations()->count();
                            if ($countFeds < 2) {
                                $this->em->remove($mm2->getProvider());
                            }
                        } else {
                            $this->em->remove($mm2);
                        }
                    }
                }
            }
            try {
                $this->genReport($report);
                $this->em->flush();
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ' ' . $e);
                return false;
            }
        }  // END SYNC
        else {
            \log_message('info', __METHOD__ . ' running as import for ' . $federation->getName() . '
                  - new entities will be created and added to federation(s)');

            $counter = 0;
            foreach ($this->metadataInArray as $ent) {
                $counter++;
                if (!($type == 'ALL' || $ent['type'] === 'BOTH' || $ent['type'] === $type)) {
                    continue;
                }
                $importedProvider = new models\Provider;
                $importedProvider->setProviderFromArray($ent);

                /**
                 * @var models\Provider $existingProvider
                 */
                $existingProvider = $tmpProviders->getOneByEntityId($importedProvider->getEntityId());
                if (empty($existingProvider)) {
                    $importResult[] = lang('provcreated') . ': ' . $importedProvider->getEntityId();
                    $importedProvider->setStatic($static);
                    $importedProvider->setLocal($local);
                    $importedProvider->setActive($active);
                    // coc begin

                    foreach ($ent['coc'] as $attrname => $v) {
                        if (isset($coclistarray['' . $attrname . ''])) {
                            $y = array_search($v, $coclistarray['' . $attrname . '']);
                            if ($y != NULL && $y != FALSE) {
                                $celement = $coclistconverted['' . $y . ''];
                                if (!empty($celement)) {
                                    $importedProvider->setCoc($celement);
                                }
                            }
                        }
                    }
                    // coc end
                    foreach ($ent['regpol'] as $v) {
                        foreach ($regpollistconverted as $c) {
                            $cUrl = $c->getUrl();
                            $cLang = $c->getLang();
                            if (strcmp($cUrl, $v['url']) == 0 && strcasecmp($cLang, $v['lang']) == 0) {
                                $importedProvider->setCoc($c);
                                break;
                            }
                        }
                    }

                    // attr req  start
                    if (isset($ent['details']['reqattrs'])) {
                        $attrsset = array();
                        foreach ($ent['details']['reqattrs'] as $r) {
                            if (array_key_exists($r['name'], $attributes)) {
                                if (!in_array($r['name'], $attrsset)) {
                                    $reqattr = new models\AttributeRequirement;
                                    $reqattr->setAttribute($attributes['' . $r['name'] . '']);
                                    $reqattr->setType('SP');
                                    $reqattr->setSP($importedProvider);
                                    if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0) {
                                        $reqattr->setStatus('required');
                                    } else {
                                        $reqattr->setStatus('desired');
                                    }
                                    $importedProvider->setAttributesRequirement($reqattr);
                                    $this->em->persist($reqattr);
                                    $attrsset[] = $r['name'];
                                }
                            } else {
                                log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
                            }
                        }
                    }

                    // attr req end
                    // set membership
                    $isLocal = $importedProvider->getLocal();
                    $newmembership = new models\Federationmembers();
                    $newmembership->setProvider($importedProvider);
                    $newmembership->setFederation($federation);
                    if ($isLocal) {
                        $newmembership->setJoinstate('1');
                    }
                    //set membership end

                    $this->em->persist($newmembership);
                    $this->em->persist($importedProvider);
                } else { // for existing entity
                    $importEntity = '';
                    $elocal = $existingProvider->getLocal();
                    $isLocked = $existingProvider->getLocked();
                    $updateAllowed = (($elocal && $overwritelocal && !$isLocked) OR !$elocal);
                    if ($updateAllowed) {
                        $importEntity .= lang('provupdated');
                        $existingProvider->overwriteByProvider($importedProvider);
                        $existingProvider->setLocal($this->defaults['local']);
                        $currentCocs = $existingProvider->getCoc();
                        foreach ($currentCocs as $c) {
                            $cType = $c->getType();
                            if ($cType === 'entcat') {
                                $cUrl = $c->getUrl();
                                $cSubtype = $c->getSubtype();
                                if (!isset($ent['coc']['' . $cSubtype . ''])) {
                                    $existingProvider->removeCoc($c);
                                } else {
                                    $y = array_search($cUrl, $ent['coc']['' . $cSubtype . '']);
                                    if ($y === NULL || $y === FALSE) {
                                        $existingProvider->removeCoc($c);
                                    } else {
                                        unset($ent['coc']['' . $cSubtype . '']['' . $y . '']);
                                    }
                                }
                            } elseif ($cType === 'regpol') {
                                $cUrl = $c->getUrl();
                                $cLang = $c->getLang();
                                $cExist = FALSE;
                                $cKey = null;
                                foreach ($ent['regpol'] as $k => $v) {
                                    if (strcmp($cUrl, $v['url']) == 0 && strcasecmp($cLang, $v['lang']) == 0) {
                                        $cExist = TRUE;
                                        $cKey = $k;
                                        break;
                                    }
                                }
                                if ($cExist === FALSE) {
                                    $existingProvider->removeCoc($c);
                                } else {
                                    unset($ent['regpol']['' . $cKey . '']);
                                }
                            }
                        }

                        foreach ($ent['coc'] as $attrname => $v) {
                            if (isset($ncoclistarray['' . $attrname . ''])) {
                                foreach ($v as $k => $p) {
                                    $y = array_search($p, $ncoclistarray['' . $attrname . '']);
                                    if ($y !== null && $y !== FALSE) {
                                        $existingProvider->setCoc($coclistconverted['' . $y . '']);
                                    }
                                }
                            }
                        }
                        foreach ($ent['regpol'] as $v) {
                            foreach ($regpollistconverted as $c) {
                                $cUrl = $c->getUrl();
                                $cLang = $c->getLang();
                                if (strcmp($cUrl, $v['url']) == 0 && strcasecmp($cLang, $v['lang']) == 0) {
                                    $existingProvider->setCoc($c);
                                    break;
                                }
                            }
                        }


                        $existingProvider->setStatic($static);
                        /**
                         *   attrs requirements processing
                         */
                        $duplicateControl = array();
                        $requiredAttrs = $existingProvider->getAttributesRequirement();
                        foreach ($requiredAttrs as $a) {
                            $oid = $a->getAttribute()->getOid();
                            if (in_array('' . $oid . '', $duplicateControl)) {
                                $requiredAttrs->removeElement($a);
                                $this->em->remove($a);
                            } else {
                                $duplicateControl[] = $oid;
                            }
                        }
                        if (isset($ent['details']['reqattrs']) && is_array($ent['details']['reqattrs'])) {
                            foreach ($requiredAttrs as $r) {
                                $found = false;
                                $roid = $r->getAttribute()->getOid();
                                foreach ($ent['details']['reqattrs'] as $k => $v) {
                                    if (strcmp($roid, $v['name']) == 0) {
                                        $found = true;
                                        if (isset($v['req']) && strcasecmp($v['req'], 'true') == 0) {
                                            $r->setStatus('required');
                                        } else {
                                            $r->setStatus('desired');
                                        }
                                        unset($ent['details']['reqattrs']['' . $k . '']);
                                        $this->em->persist($r);
                                    }
                                    if ($found) {
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $requiredAttrs->removeElement($r);
                                    $this->em->remove($r);
                                }
                            }
                            foreach ($ent['details']['reqattrs'] as $nr) {
                                if (isset($nr['name']) && array_key_exists($nr['name'], $attributes)) {
                                    $reqattr = new models\AttributeRequirement;
                                    $reqattr->setAttribute($attributes['' . $nr['name'] . '']);
                                    $reqattr->setType('SP');
                                    $reqattr->setSP($existingProvider);
                                    if (isset($nr['req']) && strcasecmp($nr['req'], 'true') == 0) {
                                        $reqattr->setStatus('required');
                                    } else {
                                        $reqattr->setStatus('desired');
                                    }
                                    $existingProvider->setAttributesRequirement($reqattr);
                                    $this->em->persist($reqattr);
                                } else {
                                    log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $nr['name']);
                                }
                            }
                        }
                        /**
                         * END attrs requirements processing
                         */
                    }
                    if (!($isLocked && $elocal)) {
                        $settingMebership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $existingProvider, 'federation' => $federation->getId()));
                        if (empty($settingMebership)) {
                            $newMembership = new models\FederationMembers();
                            $newMembership->setProvider($existingProvider);
                            $newMembership->setFederation($federation);
                            $newMembership->setJoinstate('1');
                            $this->em->persist($newMembership);
                            $importEntity .= ', ' . lang('rr_addedtofed');
                        } else {
                            $cjoinstate = $settingMebership->getJoinState();
                            if ($cjoinstate == 2) {
                                $importEntity .= '; ' . lang('rr_addedtofed');
                            } elseif ($cjoinstate == 3) {
                                $importEntity .= '; ' . lang('rr_convertjoinstate31');
                            } else {
                                $importEntity .= '; ' . lang('rr_joinstatealreadyinfed');
                            }
                            $settingMebership->setJoinState('1');
                            $this->em->persist($settingMebership);
                        }
                    }
                    $importResult[] = $importEntity . ': ' . $existingProvider->getEntityId();
                } // end for existing provider

                if ($counter > 50) {
                    $this->em->flush();
                    $counter = 0;
                }
            }
        } // END import

        try {
            $this->em->flush();
            if (!empty($importResult)) {
                $this->ci->globalnotices['metadataimportmessage'] = $importResult;
            }
            return true;
        } catch (Exception $e) {
            \log_message('error', __METHOD__ . ' ' . $e);
            return false;
        }
    }


    /**
     * @param array $reqattrs
     * @param array $attrRequiredByFed
     * @param \models\Provider $ent
     */
    private function setReqAttrs(array $reqattrs, array $attrRequiredByFed, models\Provider $ent) {
        $attrsset = array();
        $attributes = $this->getAttributesByNames();
        foreach ($reqattrs as $r) {
            if (array_key_exists($r['name'], $attributes)) {
                if (!in_array($r['name'], $attrsset)) {
                    $reqattr = new models\AttributeRequirement;
                    $reqattr->setAttribute($attributes['' . $r['name'] . '']);
                    $reqattr->setType('SP');
                    $reqattr->setSP($ent);
                    if (isset($r['req']) && strcasecmp($r['req'], 'true') == 0) {
                        $reqattr->setStatus('required');
                    } else {
                        $reqattr->setStatus('desired');
                    }
                    $ent->setAttributesRequirement($reqattr);
                    $this->em->persist($reqattr);
                    $attrsset[] = $r['name'];
                }
            } else {
                log_message('warning', 'Attr couldnt be set as required becuase doesnt exist in attrs table: ' . $r['name']);
            }
        }

        if ($ent['details']['reqattrsinmeta'] === false && $this->copyFedAttrReq === true) {
            foreach ($attrRequiredByFed as $rt) {
                if (!in_array($rt['name'], $attrsset)) {
                    $reqattr = new models\AttributeRequirement;
                    $reqattr->setAttribute($attributes['' . $rt['name'] . '']);
                    $reqattr->setType('SP');
                    $reqattr->setSP($ent);
                    if (isset($rt['req']) && strcasecmp($rt['req'], 'true') == 0) {
                        $reqattr->setStatus('required');
                    } else {
                        $reqattr->setStatus('desired');
                    }
                    $ent->setAttributesRequirement($reqattr);
                    $this->em->persist($reqattr);
                    $attrsset[] = $rt['name'];
                }
            }
        }

    }
}
