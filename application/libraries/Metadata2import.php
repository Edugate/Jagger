<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * Jagger
 *
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @copyright Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Metadata2import Class
 *
 * @package    Jagger
 * @subpackage Libraries
 * @author     Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadata2import
{

    private $metadataInArray;
    private $metadata;
    private $type;
    private $full;
    private $defaults;
    private $other;
    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
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
            'localimport' => false,
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
     * @param $report
     * @return bool
     */
    private function genReport($report)
    {
        if (!(!empty($report) && is_array($report))) {
            return false;
        }
        $this->ci->load->library('email_sender');
        $body = 'Report' . PHP_EOL;
        foreach ($report['body'] as $bb) {
            $body .= $bb . PHP_EOL;
        }
        $structureChanged = FALSE;
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

    private function getAttributesByNames()
    {
        /**
         * @var $attrsDefinitions \models\Attribute[]
         */
        $attrsDefinitions = $this->em->getRepository("models\Attribute")->findAll();
        $attributes = array();
        foreach ($attrsDefinitions as $v) {
            $attributes['' . $v->getOid() . ''] = $v;
        }
        return $attributes;
    }

    private function getAttrReqByFed(\models\Federation $federation)
    {
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


    public function import($metadata, $type, $full, array $defaults, $other = null)
    {
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
         * @var $coclist models\Coc[]
         * @var $regpollist models\Coc[]
         */
        $coclist = $this->em->getRepository("models\Coc")->findBy(array('type' => 'entcat'));
        $regpollist = $this->em->getRepository("models\Coc")->findBy(array('type' => 'regpol'));
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
        $regpollistconverted = array();
        $regpollistarray = array();
        $regpollistlangarray = array();

        foreach ($coclist as $k => $c) {
            $coclistconverted['' . $c->getId() . ''] = $c;
            $coclistarray['' . $c->getId() . ''] = $c->getUrl();
            $ncoclistarray['' . $c->getSubtype() . '']['' . $c->getId() . ''] = $c->getUrl();
        }
        foreach ($regpollist as $k => $c) {
            $regpollistconverted['' . $c->getId() . ''] = $c;
            $regpollistarray['' . $c->getId() . ''] = $c->getUrl();
            $regpollistlangarray['' . $c->getId() . ''] = $c->getLang();
        }


        /**
         * @var $federations models\Federation[]
         */
        $federations = array();
        if (array_key_exists('federations', $this->defaults)) {
            $federations = $this->em->getRepository("models\Federation")->findBy(array('name' => $this->defaults['federations']));
            foreach ($federations as $ff) {
                $report['body'][] = 'Sync with federation: ' . $ff->getName();
            }
        }
        /**
         * if param static is not provided then static is set to true
         */
        $static = true;
        if (array_key_exists('static', $this->defaults) && $this->defaults['static'] === false) {
            $static = false;
        }
        $local = false;
        if (array_key_exists('local', $this->defaults) && $this->defaults['local'] === true) {
            $local = true;
        }
        $active = false;
        if (array_key_exists('active', $this->defaults) && $this->defaults['active'] === true) {
            $active = true;
        }
        $overwritelocal = false;
        if (array_key_exists('overwritelocal', $this->defaults) && $this->defaults['overwritelocal'] === TRUE) {
            $overwritelocal = true;
        }


        // remove external entities if they're not member of any other federation
        $removeexternal = false;
        if (array_key_exists('removeexternal', $this->defaults) && $this->defaults['removeexternal'] === true) {
            $removeexternal = true;
        }
        $attrreqinherit = false;
        if (array_key_exists('attrreqinherit', $this->defaults) && $this->defaults['attrreqinherit'] === true) {
            $attrreqinherit = true;
        }

        $timeStart = microtime(true);
        $this->metadataInArray = $this->ci->metadata2array->rootConvert($metadata, $full);
        $timeEnd = microtime(true);
        $timeExecution = $timeEnd - $timeStart;
        log_message('debug', __METHOD__ . ' time execution of converting metadata to array took: ' . $timeExecution);
        if (!(empty($this->metadataInArray) || is_array($this->metadataInArray) || count($this->metadataInArray) == 0)) {
            \log_message('warning', __METHOD__ . ' converting xml metadata 
                               into array resulted empty array or null value');
            return false;
        }


        foreach ($federations as $f) {
            $attrRequiredByFed = $this->getAttrReqByFed($f);
            $copyFedAttrReq = false;
            if ($attrreqinherit === true && count($attrRequiredByFed) > 0) {
                $copyFedAttrReq = true;
            }

            $fedMembershipColl = $f->getMembership();

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
                \log_message('info', __METHOD__ . ' running as sync for ' . $f->getName());
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
                        log_message('error', __METHOD__ . " missing type for entity: " . $ent['entityid']);
                        continue;
                    }
                    $counter++;
                    if ($ent['type'] === 'BOTH' || $ent['type'] === $type || $type === 'ALL') {

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
                            $attrsset = array();
                            if (isset($ent['details']['reqattrs'])) {
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

                                if ($ent['details']['reqattrsinmeta'] === false & $copyFedAttrReq === true) {
                                    foreach ($attrRequiredByFed as $rt) {
                                        if (!in_array($rt['name'], $attrsset)) {
                                            $reqattr = new models\AttributeRequirement;
                                            $reqattr->setAttribute($attributes['' . $rt['name'] . '']);
                                            $reqattr->setType('SP');
                                            $reqattr->setSP($importedProvider);
                                            if (isset($rt['req']) && strcasecmp($rt['req'], 'true') == 0) {
                                                $reqattr->setStatus('required');
                                            } else {
                                                $reqattr->setStatus('desired');
                                            }
                                            $importedProvider->setAttributesRequirement($reqattr);
                                            $this->em->persist($reqattr);
                                            $attrsset[] = $rt['name'];
                                        }
                                    }
                                }

                            }

                            // attr req end
                            $newmembership = new models\Federationmembers();
                            $newmembership->setProvider($importedProvider);
                            $newmembership->setFederation($f);
                            $newmembership->setJoinState('3');
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
                                    if ($ent['details']['reqattrsinmeta'] === false & $copyFedAttrReq === true) {
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


                            if (!array_key_exists($existingProvider->getEntityId(), $membershipByEnt)) {
                                if (($isLocal && !$isLocked) || !($isLocal)) {

                                    $newMembership = new models\FederationMembers;
                                    $newMembership->setProvider($existingProvider);
                                    $newMembership->setFederation($f);
                                    $newMembership->setJoinState('3');
                                    $this->em->persist($newMembership);
                                    $report['provider']['joinfed'][] = $existingProvider->getEntityId();
                                }
                            }
                            $this->em->persist($existingProvider);
                        }
                        if ($counter > 300) {
                            $this->em->flush();
                            $counter = 0;
                        }
                    } // END if type matches
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
                        log_message('debug', __METHOD__ . ' proceeding removing ' . $mm2->getProvider()->getEntityId() . ' from fed:' . $f->getName());
                        $mm2joinstate = $mm2->getJoinState();
                        $tmpprov = $mm2->getProvider();

                        $isLocal = $mm2->getProvider()->getLocal();

                        log_message('debug', __METHOD__ . ' current state of provider:: joinstate-' . $mm2joinstate . ', islocal-' . $isLocal);
                        if (!($mm2joinstate == 0 || $mm2joinstate == 1)) {

                            log_message('debug', 'proceeding ' . $mm2->getProvider()->getEntityId() . ' joinstatus:' . $mm2joinstate);
                            if (!$isLocal && $removeexternal) {

                                $ff = $tmpprov->getFederations();
                                $countFeds = $ff->count();
                                if ($countFeds < 2 && $ff->contains($f)) {
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
                \log_message('info', __METHOD__ . ' running as import for ' . $f->getName() . '
                  - new entities will be created and added to federation(s)');

                $counter = 0;
                foreach ($this->metadataInArray as $ent) {
                    $counter++;
                    if ($ent['type'] === 'BOTH' || $ent['type'] === $type || $type == 'ALL') {
                        $importedProvider = new models\Provider;
                        $importedProvider->setProviderFromArray($ent);

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
                            $newmembership->setFederation($f);
                            if ($isLocal) {
                                $newmembership->setJoinState('1');
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
                                $settingMebership = $this->em->getRepository("models\FederationMembers")->findOneBy(array('provider' => $existingProvider, 'federation' => $f->getId()));
                                if (empty($settingMebership)) {
                                    $newMembership = new models\FederationMembers();
                                    $newMembership->setProvider($existingProvider);
                                    $newMembership->setFederation($f);
                                    $newMembership->setJoinState('1');
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
                    }
                    if ($counter > 50) {
                        $this->em->flush();
                        $counter = 0;
                    }
                }
            } // END import 
        }
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

}
