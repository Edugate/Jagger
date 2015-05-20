<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Jagger
 *
 * @package     Jagger
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * ProviderUpdater Class
 *
 * @package     Jagger
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Providerupdater
{

    protected $ci;
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;
    protected $logtracks;
    protected $allowedLangCodes;
    protected $langCodes;
    protected $changes = array();
    protected $entityTypes = array();

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->library('tracker');
        $this->logtracks = array();
        $this->langCodes = languagesCodes();
        $this->allowedLangCodes = array_keys($this->langCodes);
    }

    /**
     * @param array $changes
     * @return bool
     */
    private function checkChangelog(array $changes)
    {
        if (count(array_diff($changes['before'], $changes['after'])) > 0 || count(array_diff($changes['after'], $changes['before'])) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $name
     * @param $old
     * @param $new
     */
    private function updateChanges($name, $old, $new)
    {

        $this->logtracks['' . $name . ''] = array('before' => $old, 'after' => $new);
    }


    private function updateCerts(\models\Provider $ent, array $ch)
    {
        if (!array_key_exists('crt', $ch) || empty($ch['crt']) || !is_array($ch['crt'])) {
            return false;
        }
        $origCertificates = $ent->getCertificates();
        $allowedusecase = array('signing', 'encryption', 'both');
        foreach ($origCertificates as $v) {
            if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . ''])) {
                $tkeyname = false;
                $tdata = false;
                $crtusecase = $ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['usage'];
                if (!empty($crtusecase) && in_array($crtusecase, $allowedusecase)) {
                    $v->setCertUse($crtusecase);
                }
                if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['keyname'])) {
                    if (!empty($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['keyname'])) {
                        $tkeyname = true;
                    }
                    $v->setKeyname($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['keyname']);
                }
                if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['certdata'])) {
                    if (!empty($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['certdata'])) {
                        $tdata = true;
                    }
                    $v->setCertData($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['certdata']);
                }
                if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['encmethods'])) {
                    if (!empty($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['encmethods']) && is_array($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['encmethods'])) {
                        $v->setEncryptMethods(array_filter($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['encmethods']));
                    } else {
                        $v->setEncryptMethods(null);
                    }
                }
                if ($tdata === false && $tkeyname === false) {
                    $ent->removeCertificate($v);
                    $this->em->remove($v);
                } else {
                    $this->em->persist($v);
                }
                unset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']);
            } else {
                $ent->removeCertificate($v);
                $this->em->remove($v);
            }
        }
        /**
         * setting new certs
         */
        foreach ($ch['crt'] as $k1 => $v1) {
            if ($k1 === 'spsso' && $this->entityTypes['sp'] === true) {
                foreach ($v1 as $k2 => $v2) {
                    $ncert = new models\Certificate();
                    $ncert->setType('spsso');
                    $ncert->setCertType();
                    $ncert->setCertUse($v2['usage']);
                    $ent->setCertificate($ncert);
                    $ncert->setProvider($ent);
                    $ncert->setKeyname($v2['keyname']);
                    $ncert->setCertData($v2['certdata']);
                    if (isset($v2['encmethods'])) {
                        $ncert->setEncryptMethods($v2['encmethods']);
                    }
                    $this->em->persist($ncert);
                }
            } elseif ($k1 === 'idpsso' && $this->entityTypes['idp']) {
                foreach ($v1 as $k2 => $v2) {
                    $ncert = new models\Certificate();
                    $ncert->setType('idpsso');
                    $ncert->setCertType();
                    $ncert->setCertUse($v2['usage']);
                    $ent->setCertificate($ncert);
                    $ncert->setProvider($ent);
                    $ncert->setKeyname($v2['keyname']);
                    $ncert->setCertData($v2['certdata']);
                    $ncert->setCertData($v2['certdata']);
                    if (isset($v2['encmethods'])) {
                        $ncert->setEncryptMethods($v2['encmethods']);
                    }
                    $this->em->persist($ncert);
                }
            } elseif ($k1 === 'aa' && $this->entityTypes['idp']) {
                foreach ($v1 as $k2 => $v2) {
                    $ncert = new models\Certificate();
                    $ncert->setType('aa');
                    $ncert->setCertType();
                    $ncert->setCertUse($v2['usage']);
                    $ent->setCertificate($ncert);
                    $ncert->setProvider($ent);
                    $ncert->setKeyname($v2['keyname']);
                    $ncert->setCertData(getPem($v2['certdata']));
                    $ncert->setCertData($v2['certdata']);
                    if (isset($v2['encmethods'])) {
                        $ncert->setEncryptMethods($v2['encmethods']);
                    }
                    $this->em->persist($ncert);
                }
            }
        }

    }

    /**
     * @param \models\Provider $ent
     * @param array $ch
     * @return bool
     */
    private function updateReqAttrs(\models\Provider $ent, array $ch)
    {
        if (!isset($ch['reqattr'])) {
            return false;
        }
        $attrsTmp = new models\Attributes();
        /**
         * @var $attributes models\Attribute[]
         * @var $attrsRequirement models\AttributeRequirement[]
         */
        $attributes = $attrsTmp->getAttributesToArrayById();
        $attrsRequirement = $ent->getAttributesRequirement();
        $convertedInput = array();
        foreach ($ch['reqattr'] as $attr) {
            if (isset($attr['attrid']) && array_key_exists($attr['attrid'], $attributes)) {
                $convertedInput['' . $attr['attrid'] . ''] = $attr;
            }
        }
        $changes = array('before' => array(), 'after' => array());

        foreach ($attrsRequirement as $orig) {
            $changes['before'][$orig->getAttribute()->getName()] = $orig->getStatus();
            $keyid = $orig->getAttribute()->getId();
            if (!array_key_exists($keyid, $convertedInput)) {
                $attrsRequirement->removeElement($orig);
                $this->em->remove($orig);
                continue;
            }
            $orig->setReason($convertedInput['' . $keyid . '']['reason']);
            $orig->setStatus($convertedInput['' . $keyid . '']['status']);
            $this->em->persist($orig);
            $changes['after'][$orig->getAttribute()->getName()] = $orig->getStatus();
            unset($convertedInput['' . $keyid . '']);
        }
        foreach ($convertedInput as $k => $v) {
            $nreq = new models\AttributeRequirement;
            $nreq->setStatus($v['status']);
            $nreq->setReason($v['reason']);
            $nreq->setType('SP');
            $nreq->setSP($ent);
            $nreq->setAttribute($attributes['' . $k . '']);
            $ent->setAttributesRequirement($nreq);
            $this->em->persist($nreq);
            $changes['after'][$nreq->getAttribute()->getName()] = $v['status'];
        }
        $diff1 = array_diff_assoc($changes['before'], $changes['after']);
        $diff2 = array_diff_assoc($changes['after'], $changes['before']);
        if (count($diff1) > 0 || count($diff2) > 0) {
            $this->updateChanges('requiredAttrs', arrayWithKeysToHtml($changes['before']), arrayWithKeysToHtml($changes['after']));
        }
        return true;
    }

    /**
     * @param \models\Provider $ent
     * @param array $ch
     * @return bool
     */
    private function updateContacts(models\Provider $ent, array $ch)
    {
        if (!array_key_exists('contact', $ch) || !is_array($ch['contact'])) {
            return false;
        }
        $newContacts = $ch['contact'];
        /**
         * @var $origContacts models\Contact[]
         */
        $origContacts = $ent->getContacts();
        $origcntArray = array();
        $newcntArray = array();
        foreach ($origContacts as $v) {
            $contactID = $v->getId();

            $origcntArray[$contactID] = '' . $v->getType() . ' : (' . $v->getGivenname() . ' ' . $v->getSurname() . ') ' . $v->getEmail();

            if (array_key_exists($contactID, $newContacts)) {
                if (!isset($newContacts['' . $contactID . '']) || empty($newContacts['' . $contactID . '']['email'])) {
                    $ent->removeContact($v);
                    $this->em->remove($v);
                } else {
                    $v->setAllInfo($newContacts['' . $contactID . '']['fname'], $newContacts['' . $contactID . '']['sname'], $newContacts['' . $contactID . '']['type'], $newContacts['' . $contactID . '']['email'], $ent);
                    $this->em->persist($v);
                    $newcntArray['' . $contactID . ''] = '' . $v->getType() . ' : (' . $v->getGivenname() . ' ' . $v->getSurname() . ') ' . $v->getEmail();
                }
                unset($newContacts['' . $contactID . '']);
            } else {
                $ent->removeContact($v);
                $this->em->remove($v);
            }
        }
        foreach ($newContacts as $cc) {
            if (!empty($cc['email']) && !empty($cc['type'])) {
                $ncontact = new models\Contact();
                $ncontact->setAllInfo($cc['fname'], $cc['sname'], $cc['type'], $cc['email'], $ent);
                $this->em->persist($ncontact);
            }
        }
        $newcnts = $ent->getContacts();
        $counter = 0;
        foreach ($newcnts as $v) {
            $counter++;
            $idc = $v->getId();
            if (empty($idc)) {
                $idc = 'n' . $counter;
            }
            $newcntArray[$idc] = '' . $v->getType() . ' : (' . $v->getGivenname() . ' ' . $v->getSurname() . ') ' . $v->getEmail();
        }
        $diff1 = array_diff_assoc($newcntArray, $origcntArray);
        $diff2 = array_diff_assoc($origcntArray, $newcntArray);
        if (count($diff1) > 0 || count($diff2) > 0) {
            $this->updateChanges('contacts', arrayWithKeysToHtml($origcntArray), arrayWithKeysToHtml($newcntArray));
        }

        return true;
    }

    /**
     * @param \models\Provider $ent
     * @param array $ch
     * @param bool $isAdmin
     * @return \models\Provider
     */
    public function updateRegPolicies(models\Provider $ent, array $ch, $isAdmin = false)
    {
        $changes = array('before' => array(), 'after' => array());
        $entID = $ent->getId();
        $currentCocs = $ent->getCoc();
        if (array_key_exists('regpol', $ch) && is_array($ch['regpol'])) {
            foreach ($currentCocs as $k => $v) {
                $cid = $v->getId();
                $ctype = $v->getType();
                if ($ctype === 'regpol') {
                    $changes['before'][] = $v->getUrl();
                    $foundkey = array_search($cid, $ch['regpol']);
                    if ($foundkey === null || $foundkey === false) {
                        $ent->removeCoc($v);
                    } else {
                        $changes['after'][] = $v->getUrl();
                    }
                }
            }
            $requestNew = false;
            /**
             * @var $c models\Coc
             */
            foreach ($ch['regpol'] as $k => $v) {
                if (!empty($v) && ctype_digit($v)) {
                    $c = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $v, 'type' => 'regpol'));
                    if (!empty($c) && !$currentCocs->contains($c)) {
                        $requestNew = true;
                        if ($isAdmin) {
                            $changes['after'][] = $c->getUrl();
                            $ent->setCoc($c);
                        } else {
                            $this->ci->approval->applyForRegistrationPolicy($c, $ent);
                        }
                    }
                }
            }
        }
        if (!$requestNew) {
            $this->ci->globalnotices[] = lang('updated');
        }
        if ($this->checkChangelog($changes)) {
            $m['Registration Policies'] = array(
                'before' => implode(', ', $changes['before']),
                'after' => implode(', ', $changes['after']),
            );
            $this->logtracks = array_merge($this->logtracks, $m);
            if (count($this->logtracks) > 0 && !empty($entID)) {
                $this->ci->tracker->save_track('ent', 'modification', $ent->getEntityId(), serialize($this->logtracks), FALSE);
            }
        }

        return $ent;

    }

    /**
     * @param \models\Provider $ent
     * @param array $ch
     * @return \models\Provider
     */
    private function updateProviderExtend(models\Provider $ent, array $ch)
    {
        $m = array();
        $entityTypes = $ent->getTypesToArray();
        $filteredTypes = array_filter($entityTypes);
        $extypes = array_keys($filteredTypes);
        /**
         * @var $extendsCollection models\ExtendMetadata[]
         */
        $extendsCollection = $ent->getExtendMetadata();
        $uiinfoParent = array('idp' => null, 'sp' => null);
        $discohintsParent = null;
        $extendsInArray = array();
        foreach ($extendsCollection as $e) {
            $extendsInArray['' . $e->getType() . '']['' . $e->getNamespace() . '']['' . $e->getElement() . ''][] = $e;
            if ($e->getElement() === 'UIInfo') {
                $uiinfoParent['' . $e->getType() . ''] = $e;
            } elseif ($e->getElement() === 'DiscoHints' && $e->getType() === 'idp') {
                $discohintsParent = $e;
            }
        }

        $algsMethods = array('digest' => 'DigestMethod', 'signing' => 'SigningMethod');
        foreach ($algsMethods as $algKey => $algValue) {
            if (isset($ch['algs']['' . $algKey . '']) && is_array($ch['algs']['' . $algKey . ''])) {
                if (isset($extendsInArray['ent']['alg']['' . $algValue . ''])) {
                    foreach ($extendsInArray['ent']['alg']['' . $algValue . ''] as $k => $v) {
                        $dvalue = $v->getEvalue();

                        if (in_array($dvalue, $ch['algs']['' . $algKey . ''])) {
                            $ch['algs']['' . $algKey . ''] = array_diff($ch['algs']['' . $algKey . ''], array('' . $dvalue . ''));
                        } else {
                            $ent->getExtendMetadata()->removeElement($v);
                            $this->em->remove($v);
                        }
                    }
                }
                foreach ($ch['algs']['' . $algKey . ''] as $v2) {
                    $dig = new models\ExtendMetadata;
                    $dig->setAlgorithmMethod($v2, $algValue);
                    $ent->setExtendMetadata($dig);
                    $this->em->persist($dig);
                }

            }
        }

        if ($entityTypes['idp'] === true) {
            $idpDiscoHints = array('geo' => 'GeolocationHint', 'domainhint' => 'DomainHint', 'iphint' => 'IPHint');
            if (empty($discohintsParent)) {
                $discohintsParent = new models\ExtendMetadata;
                $discohintsParent->setType('idp');
                $discohintsParent->setNamespace('mdui');
                $discohintsParent->setElement('DiscoHints');
                $ent->setExtendMetadata($discohintsParent);
            }
            foreach ($idpDiscoHints as $key => $value) {
                if (isset($ch['uii']['idpsso']['' . $key . '']) && is_array($ch['uii']['idpsso']['' . $key . ''])) {
                    $ch['uii']['idpsso']['' . $key . ''] = array_unique($ch['uii']['idpsso']['' . $key . '']);
                    if (isset($extendsInArray['idp']['mdui']['' . $value . ''])) {
                        foreach ($extendsInArray['idp']['mdui']['' . $value . ''] as $k => $v) {
                            $vId = $v->getId();
                            if (array_key_exists($vId, $ch['uii']['idpsso']['' . $key . '']) && !empty($ch['uii']['idpsso']['' . $key . '']['' . $vId . ''])) {
                                $v->setValue($ch['uii']['idpsso']['' . $key . '']['' . $vId . '']);
                                $this->em->persist($v);
                            } else {
                                $ent->getExtendMetadata()->removeElement($v);
                                $this->em->remove($v);
                            }
                            unset($ch['uii']['idpsso']['' . $key . '']['' . $vId . '']);
                        }
                    }
                    foreach ($ch['uii']['idpsso']['' . $key . ''] as $v) {
                        if (!empty($v)) {
                            $newExtend = new models\ExtendMetadata;
                            $newExtend->setParent($discohintsParent);
                            $newExtend->setType('idp');
                            $newExtend->setNamespace('mdui');
                            $newExtend->setElement('' . $value . '');
                            $newExtend->setValue($v);
                            $newExtend->setAttributes(array());
                            $ent->setExtendMetadata($newExtend);
                            $this->em->persist($newExtend);
                        }
                    }
                }
            }
        }
        foreach ($extypes as $v) {
            if (empty($uiinfoParent['' . $v . ''])) {
                $vparent = new models\ExtendMetadata;
                $vparent->setType('' . $v . '');
                $vparent->setNamespace('mdui');
                $vparent->setElement('UIInfo');
                $ent->setExtendMetadata($vparent);
                $uiinfoParent['' . $v . ''] = $vparent;
            }

            if (isset($ch['prvurl']) && is_array($ch['prvurl'])) {
                $origex = array();
                $origs = array();
                if (isset($extendsInArray['' . $v . '']['mdui']['PrivacyStatementURL'])) {
                    foreach ($extendsInArray['' . $v . '']['mdui']['PrivacyStatementURL'] as $value) {
                        $l = $value->getAttributes();
                        $origex['' . $l['xml:lang'] . ''] = $value;
                        $origs['' . $l['xml:lang'] . ''] = $value->getElementValue();
                    }
                }

                if (isset($ch['prvurl']['' . $v . 'sso'])) {
                    $newex = $ch['prvurl']['' . $v . 'sso'];
                    foreach ($origex as $key => $value) {
                        if (array_key_exists($key, $ch['prvurl']['' . $v . 'sso'])) {
                            if (empty($ch['prvurl']['' . $v . 'sso']['' . $key . ''])) {
                                $value->setProvider(NULL);
                                $extendsCollection->removeElement($value);
                                $this->em->remove($value);
                                unset($newex['' . $key . '']);
                            } else {
                                $value->setValue($ch['prvurl']['' . $v . 'sso']['' . $key . '']);
                                $this->em->persist($value);
                            }

                        } else {
                            $value->setProvider(NULL);
                            $extendsCollection->removeElement($value);
                            $this->em->remove($value);
                            unset($newex['' . $key . '']);
                        }
                        unset($ch['prvurl']['' . $v . 'sso']['' . $key . '']);
                    }

                    foreach ($ch['prvurl']['' . $v . 'sso'] as $key2 => $value2) {
                        if (!empty($value2)) {
                            $nprvurl = new models\ExtendMetadata();
                            $nprvurl->setType('' . $v . '');
                            $nprvurl->setNamespace('mdui');
                            $nprvurl->setElement('PrivacyStatementURL');
                            $nprvurl->setAttributes(array('xml:lang' => $key2));
                            $nprvurl->setValue($value2);
                            $ent->setExtendMetadata($nprvurl);
                            $nprvurl->setParent($uiinfoParent['' . $v . '']);
                            $this->em->persist($nprvurl);
                        }
                    }
                    $diff1 = array_diff_assoc($origs, $newex);
                    $diff2 = array_diff_assoc($newex, $origs);
                    if (count($diff1) > 0 || count($diff2) > 0) {
                        $mk = 'PrivacyStatementURLs' . strtoupper($v);
                        $m['' . $mk . ''] = array('before' => arrayWithKeysToHtml($origs), 'after' => arrayWithKeysToHtml($newex));

                    }

                }
            }

            // logos not updatting value - just remove entry or add new one
            if (isset($ch['uii']['' . $v . 'sso']['logo']) && is_array($ch['uii']['' . $v . 'sso']['logo'])) {

                $collection = array();
                if (isset($extendsInArray['' . $v . '']['mdui']['Logo'])) {
                    $collection = &$extendsInArray['' . $v . '']['mdui']['Logo'];
                }


                foreach ($collection as $c) {
                    $logoid = $c->getId();
                    if (!isset($ch['uii']['' . $v . 'sso']['logo']['' . $logoid . ''])) {
                        log_message('debug', __METHOD__ . 'logo with id:' . $logoid . ' is removed');
                        $ent->getExtendMetadata()->removeElement($c);
                        $this->em->remove($c);
                    } else {
                        unset($ch['uii']['' . $v . 'sso']['logo']['' . $logoid . '']);
                    }
                }
                foreach ($ch['uii']['' . $v . 'sso']['logo'] as $ve) {
                    if (isset($ve['url']) && isset($ve['lang']) && isset($ve['size'])) {
                        $canAdd = true;
                        $attrs = array();
                        if (strcasecmp($ve['lang'], '0') != 0) {
                            $attrs['xml:lang'] = $ve['lang'];
                        }
                        $size = explode('x', $ve['size']);
                        if (count($size) == 2) {
                            foreach ($size as $sv) {
                                if (!is_numeric($sv)) {
                                    $canAdd = false;
                                    break;
                                }
                            }
                            $attrs['width'] = $size[0];
                            $attrs['height'] = $size[1];
                        } else {
                            $canAdd = false;
                        }
                        $nlogo = new models\ExtendMetadata;
                        $nlogo->setLogoNoProvider($ve['url'], $uiinfoParent['' . $v . ''], $attrs, $v);
                        if ($canAdd) {
                            $ent->setExtendMetadata($nlogo);
                            $this->em->persist($nlogo);
                        }
                    } else {
                        log_message('warning', __METHOD__ . ' missing url/lang/size of new logo in form - not adding into db');
                    }
                }
            } // end logos update
        }


        /**
         * start update UII
         */
        if ($entityTypes['sp'] !== true) {
            $doFilter = array('t' => array('idp'), 'n' => array('mdui'), 'e' => array('DisplayName', 'Description', 'InformationURL'));
            $e = $ent->getExtendMetadata()->filter(
                function (models\ExtendMetadata $entry) use ($doFilter) {
                    return in_array($entry->getType(), $doFilter['t']) && in_array($entry->getNamespace(), $doFilter['n']) && in_array($entry->getElement(), $doFilter['e']);
                });
            $exarray = array();
            foreach ($e as $v) {
                $l = $v->getAttributes();
                if (isset($l['xml:lang'])) {
                    if (isset($exarray['' . $v->getElement() . '']['' . $l['xml:lang'] . ''])) {
                        log_message('error', 'Found duplicated element for entity: ' . $ent->getEntityId() . ' about mdui element: ' . $v->getElement() . ' for lang ' . $l['xml:lang'] . ' automaticaly removed');
                        $e->removeElement($v);
                        $ent->getExtendMetadata()->removeElement($v);
                        $this->em->remove($v);
                    } else {
                        $exarray['' . $v->getElement() . '']['' . $l['xml:lang'] . ''] = $v;
                    }
                } else {
                    log_message('error', 'ExentedMetadata element with id:' . $v->getId() . ' doesnt contains xml:lang attr');
                }
            }

            $mduiel = array('displayname' => 'DisplayName', 'desc' => 'Description', 'helpdesk' => 'InformationURL');
            foreach ($mduiel as $elkey => $elvalue) {
                if (isset($ch['uii']['idpsso']['' . $elkey . '']) && is_array($ch['uii']['idpsso']['' . $elkey . ''])) {
                    $doFilter = array('' . $elvalue . '');
                    $collection = $ent->getExtendMetadata()->filter(
                        function (models\ExtendMetadata $entry) use ($doFilter) {
                            return ($entry->getType() === 'idp') && ($entry->getNamespace() === 'mdui') && in_array($entry->getElement(), $doFilter);
                        });
                    foreach ($collection as $c) {
                        $cid = $c->getId();
                        if ($cid === 1283948) {
                            log_message('debug', 'DUPA still is here: ' . $cid);
                        }
                        $attrs = $c->getAttributes();
                        $lang = $attrs['xml:lang'];
                        if (!isset($ch['uii']['idpsso']['' . $elkey . '']['' . $lang . ''])) {
                            $ent->getExtendMetadata()->removeElement($c);
                            $this->em->remove($c);
                        }
                    }
                    foreach ($ch['uii']['idpsso']['' . $elkey . ''] as $key3 => $value3) {

                        if (!isset($exarray['' . $elvalue . '']['' . $key3 . '']) && !empty($value3) && array_key_exists($key3, $this->langCodes)) {
                            $newelement = new models\ExtendMetadata;
                            $newelement->populateWithNoProvider($uiinfoParent['idp'], 'idp', 'mdui', $value3, $elvalue, array('xml:lang' => $key3));
                            $ent->setExtendMetadata($newelement);
                            $this->em->persist($newelement);
                        } elseif (isset($exarray['' . $elvalue . '']['' . $key3 . ''])) {
                            if (empty($value3)) {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setProvider(NULL);
                                $ent->getExtendMetadata()->removeElement($exarray['' . $elvalue . '']['' . $key3 . '']);
                                $this->em->remove($exarray['' . $elvalue . '']['' . $key3 . '']);
                            } else {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setValue($value3);
                                $this->em->persist($exarray['' . $elvalue . '']['' . $key3 . '']);
                            }
                        }
                    }
                }
            }
        }
        if ($entityTypes['idp'] !== true) {
            $doFilter = array('t' => array('sp'), 'n' => array('mdui'), 'e' => array('DisplayName', 'Description', 'InformationURL'));
            $e = $ent->getExtendMetadata()->filter(
                function (models\ExtendMetadata $entry) use ($doFilter) {
                    return in_array($entry->getType(), $doFilter['t']) && in_array($entry->getNamespace(), $doFilter['n']) && in_array($entry->getElement(), $doFilter['e']);
                });
            $exarray = array();
            foreach ($e as $v) {
                $l = $v->getAttributes();
                if (isset($l['xml:lang'])) {
                    $exarray['' . $v->getElement() . '']['' . $l['xml:lang'] . ''] = $v;
                } else {
                    log_message('error', 'ExentedMetadata element with id:' . $v->getId() . ' doesnt contains xml:lang attr');
                }
            }
            $mduiel = array('displayname' => 'DisplayName', 'desc' => 'Description', 'helpdesk' => 'InformationURL');
            foreach ($mduiel as $elkey => $elvalue) {
                if (isset($ch['uii']['spsso']['' . $elkey . '']) && is_array($ch['uii']['spsso']['' . $elkey . ''])) {
                    $doFilter = array('' . $elvalue . '');
                    $collection = $ent->getExtendMetadata()->filter(
                        function (models\ExtendMetadata $entry) use ($doFilter) {
                            return ($entry->getType() === 'sp') && ($entry->getNamespace() === 'mdui') && in_array($entry->getElement(), $doFilter);
                        });
                    foreach ($collection as $c) {
                        $attrs = $c->getAttributes();
                        $lang = $attrs['xml:lang'];
                        if (!isset($ch['uii']['spsso']['' . $elkey . '']['' . $lang . ''])) {
                            $ent->getExtendMetadata()->removeElement($c);
                            $this->em->remove($c);
                        }
                    }
                    foreach ($ch['uii']['spsso']['' . $elkey . ''] as $key3 => $value3) {

                        if (!isset($exarray['' . $elvalue . '']['' . $key3 . '']) && !empty($value3) && array_key_exists($key3, $this->langCodes)) {
                            $newelement = new models\ExtendMetadata;
                            $newelement->populateWithNoProvider($uiinfoParent['sp'], 'sp', 'mdui', $value3, $elvalue, array('xml:lang' => $key3));
                            $ent->setExtendMetadata($newelement);
                            $this->em->persist($newelement);
                        } elseif (isset($exarray['' . $elvalue . '']['' . $key3 . ''])) {
                            if (empty($value3)) {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setProvider(NULL);
                                $ent->getExtendMetadata()->removeElement($exarray['' . $elvalue . '']['' . $key3 . '']);
                                $this->em->remove($exarray['' . $elvalue . '']['' . $key3 . '']);
                            } else {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setValue($value3);
                                $this->em->persist($exarray['' . $elvalue . '']['' . $key3 . '']);
                            }
                        }
                    }
                }
            }
        }
        /**
         * end update UII
         */
        foreach ($uiinfoParent as $v) {
            if (!empty($v)) {
                $this->em->persist($v);
            }
        }
        $this->logtracks = array_merge($this->logtracks, $m);
        return $ent;
    }

    /**
     * @return array
     */
    private function getDisallowedParts()
    {
        $result = array();
        if (!$this->ci->j_auth->isAdministrator()) {
            $result = $this->ci->config->item('entpartschangesdisallowed');
        }
        if (empty($result) || !is_array($result)) {
            $result = array();
        }
        return $result;
    }

    public function updateProvider(models\Provider $ent, array $ch)
    {
        // $changeList - array for modifications
        $entid = $ent->getId();
        if (!empty($entid)) {
            $dump = new models\MetadataRevision($ent);
            $this->ci->load->library('providertoxml');
            $dumpXML = $this->ci->providertoxml->entityConvertNewDocument($ent, array('attrs' => 1), true);
            $dump->setMeta($dumpXML);
            //  $this->em->persist($dump);
        }
        $entityTypes = $ent->getTypesToArray();
        $this->entityTypes = $entityTypes;
        $changeList = array();
        $type = $ent->getType();
        $allowedAABind = getAllowedSOAPBindings();
        $spartidx = array();
        $idpartidx = array('-1');
        $isAdmin = $this->ci->j_auth->isAdministrator();

        $dissalowedparts = $this->getDisallowedParts();
        log_message('debug', 'disallowedpart: ' . serialize($dissalowedparts));


        $this->updateProviderExtend($ent, $ch);
        if ($entityTypes['sp'] === true) {
            $this->updateReqAttrs($ent, $ch);

        }


        if ($entityTypes['idp'] === true) {


            /**
             * set scopes
             */
            if (array_key_exists('scopes', $ch) && (!in_array('scope', $dissalowedparts) || empty($entid))) {

                $scopeTypes = array('idpsso', 'aa');
                foreach ($scopeTypes as $scopeType) {
                    $origScopes = implode(',', $ent->getScope($scopeType));
                    if (array_key_exists($scopeType, $ch['scopes']) && !empty($ch['scopes'][$scopeType])) {
                        $newScopes = array_filter(preg_split("/[\s,]+/", $ch['scopes'][$scopeType]));
                        $ent->setScope($scopeType, array_unique($newScopes));
                        if ($origScopes != implode(',', $newScopes)) {
                            $changeList['Scope ' . $scopeType . ''] = array('before' => $origScopes, 'after' => implode(',', $newScopes));
                        }
                    } else {
                        $ent->setScope($scopeType, array());
                        if (!empty($origScopes)) {
                            $changeList['Scope ' . $scopeType . ''] = array('before' => $origScopes, 'after' => '');
                        }
                    }
                }
            }
        }
        if (array_key_exists('entityid', $ch) && !empty($ch['entityid'])) {
            if (!empty($entid)) {
                if (strcmp($ent->getEntityId(), $ch['entityid']) != 0 && !in_array('entityid', $dissalowedparts)) {
                    $changeList['EntityID'] = array('before' => $ent->getEntityId(), 'after' => $ch['entityid']);
                    $this->ci->tracker->renameProviderResourcename($ent->getEntityId(), $ch['entityid']);
                    $ent->setEntityId(trim($ch['entityid']));
                }
            } else {
                $ent->setEntityId($ch['entityid']);
            }
        }

        $fields = array('lname', 'ldisplayname', 'lhelpdesk');
        $fieldsLongName = array(
            'lname' => 'OrganizationName',
            'ldisplayname' => 'OrganizationDisplayName',
            'lhelpdesk' => 'OrganizationURL'
        );
        foreach ($fields as $fieldName) {
            $trackorigs = array();
            if (array_key_exists($fieldName, $ch) && is_array($ch[$fieldName])) {
                foreach ($ch[$fieldName] as $key => $value) {
                    if (!in_array($key, $this->allowedLangCodes)) {
                        unset($ch[$fieldName]['' . $key . '']);
                        log_message('warning', __METHOD__ . ' lang code ' . $key . ' (' . $fieldsLongName[$fieldName] . ') not found in allowed langs');
                    }
                }
                if ($fieldName === 'lname') {
                    $trackorigs = $ent->getMergedLocalName();
                } elseif ($fieldName === 'ldisplayname') {
                    $trackorigs = $ent->getMergedLocalDisplayName();
                } elseif ($fieldName === 'lhelpdesk') {
                    $trackorigs = $ent->getHelpdeskUrlLocalized();
                }
                $isDiff = false;
                $diff1 = array_diff_assoc($trackorigs, $ch[$fieldName]);
                if (count($diff1) > 0) {
                    $isDiff = true;
                } else {
                    $diff1 = array_diff_assoc($ch[$fieldName], $trackorigs);
                    if (count($diff1) > 0) {
                        $isDiff = true;
                    }
                }
                if ($isDiff) {
                    if ($fieldName === 'lname') {
                        if (isset($ch['lname']['en'])) {
                            $ent->setName($ch['lname']['en']);
                            unset($ch['lname']['en']);
                        } else {
                            $ent->setName(null);
                        }
                        $ent->setLocalName($ch[$fieldName]);
                        $trackAfter = $ent->getMergedLocalName();
                    } elseif ($fieldName === 'ldisplayname') {

                        if (isset($ch['ldisplayname']['en'])) {
                            $ent->setDisplayName($ch['ldisplayname']['en']);
                            unset($ch['ldisplayname']['en']);
                        } else {
                            $ent->setDisplayName(null);
                        }
                        $ent->setLocalDisplayName($ch['ldisplayname']);
                        $trackAfter = $ent->getMergedLocalDisplayName();

                    } elseif ($fieldName === 'lhelpdesk') {
                        if (isset($ch['lhelpdesk']['en'])) {
                            $ent->setHelpdeskUrl($ch['lhelpdesk']['en']);
                            unset($ch['lhelpdesk']['en']);
                        } else {
                            $ent->setHelpdeskUrl(null);
                        }
                        $ent->setLocalHelpdeskUrl($ch['lhelpdesk']);
                        $trackAfter = $ent->getHelpdeskUrlLocalized();
                    }

                    $changeList[$fieldsLongName[$fieldName]] = array('before' => arrayWithKeysToHtml($trackorigs), 'after' => arrayWithKeysToHtml($trackAfter));

                }
            }
        }


        if ($isAdmin) {
            if (array_key_exists('regauthority', $ch)) {
                if ($ent->getRegistrationAuthority() !== $ch['regauthority']) {
                    $changeList['RegistrationAuthority'] = array('before' => $ent->getRegistrationAuthority(), 'after' => $ch['regauthority']);
                }
                $ent->setRegistrationAuthority($ch['regauthority']);
            }
            if (array_key_exists('registrationdate', $ch)) {
                $prevregdate = '';
                $prevregtime = '';
                $prevregistrationdate = $ent->getRegistrationDate();
                if (isset($prevregistrationdate)) {
                    $prevregdate = date('Y-m-d', $prevregistrationdate->format('U') + j_auth::$timeOffset);
                    $prevregtime = date('H:i', $prevregistrationdate->format('U') + j_auth::$timeOffset);
                }
                if (!array_key_exists('registrationtime', $ch) || empty($ch['registrationtime'])) {
                    $tmpnow = new \DateTime('now');
                    $ch['registrationtime'] = $tmpnow->format('H:i');
                }
                if ($prevregdate !== $ch['registrationdate'] || $prevregtime !== $ch['registrationtime']) {
                    $changeList['RegistrationDate'] = array('before' => $prevregdate . ' ' . $prevregtime, 'after' => '' . $ch['registrationdate'] . ' ' . $ch['registrationtime'] . '');
                    if (!empty($ch['registrationdate'])) {
                        $ent->setRegistrationDate(\DateTime::createFromFormat('Y-m-d H:i', $ch['registrationdate'] . ' ' . $ch['registrationtime']));
                    } else {
                        $ent->setRegistrationDate(null);
                    }
                }
            }
        }

        $currentCocs = $ent->getCoc();
        /**
         * @todo track coc changes
         */
        if (array_key_exists('coc', $ch)) {
            $currentEntCat = &$currentCocs;
            foreach ($currentEntCat as $k => $v) {
                $cid = $v->getId();
                $ctype = $v->getType();
                if ($ctype === 'entcat') {
                    $foundkey = array_search($cid, $ch['coc']);
                    if ($foundkey === null || $foundkey === false) {
                        $ent->removeCoc($v);
                    }
                }
            }
            foreach ($ch['coc'] as $k => $v) {
                if (!empty($v) && is_numeric($v)) {
                    /**
                     * @var $c models\Coc
                     */
                    $c = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $v, 'type' => 'entcat'));
                    if (!empty($c) && !$currentEntCat->contains($c)) {
                        if ($isAdmin) {
                            $ent->setCoc($c);
                        } else {
                            $this->ci->approval->applyForEntityCategory($c, $ent);
                        }
                    }
                }
            }
        }


        if (array_key_exists('privacyurl', $ch)) {
            if ($ent->getPrivacyURL() !== $ch['privacyurl']) {
                $changeList['PrivacyURL general'] = array('before' => $ent->getPrivacyURL(), 'after' => $ch['privacyurl']);
            }
            $ent->setPrivacyUrl($ch['privacyurl']);
        }


        /**
         * START update protocols enumeration
         */
        $protocolSupport['idpsso'] = $ent->getProtocolSupport('idpsso');
        $protocolSupport['spsso'] = $ent->getProtocolSupport('spsso');
        $protocolSupport['aa'] = $ent->getProtocolSupport('aa');
        if (array_key_exists('prot', $ch) && !empty($ch['prot']) && is_array($ch['prot'])) {
            if (isset($ch['prot']['aa']) && is_array($ch['prot']['aa'])) {
                $ent->setProtocolSupport('aa', $ch['prot']['aa']);
            }
            if (isset($ch['prot']['idpsso']) && is_array($ch['prot']['idpsso'])) {
                $ent->setProtocolSupport('idpsso', $ch['prot']['idpsso']);
            }
            if (isset($ch['prot']['spsso']) && is_array($ch['prot']['spsso'])) {
                $ent->setProtocolSupport('spsso', $ch['prot']['spsso']);
            }
            $newProtocolSupport['idpsso'] = $ent->getProtocolSupport('idpsso');
            $newProtocolSupport['spsso'] = $ent->getProtocolSupport('spsso');
            $newProtocolSupport['aa'] = $ent->getProtocolSupport('aa');
            foreach ($newProtocolSupport as $k => $v) {
                if (count(array_diff_assoc($newProtocolSupport['' . $k . ''], $protocolSupport['' . $k . ''])) > 0 || count(array_diff_assoc($protocolSupport['' . $k . ''], $newProtocolSupport['' . $k . ''])) > 0) {
                    $changeList['ProtocolEnumeration ' . $k . ''] = array('before' => arrayWithKeysToHtml($protocolSupport['' . $k . '']), 'after' => arrayWithKeysToHtml($newProtocolSupport['' . $k . '']));
                }
            }
        }

        /**
         * @todo add track for nameids
         */
        $origNameIds['idpsso'] = $ent->getNameIds('idpsso');
        $origNameIds['spsso'] = $ent->getNameIds('spsso');
        $origNameIds['aa'] = $ent->getNameIds('aa');
        if (!array_key_exists('nameids', $ch)) {
            if ($type !== 'SP') {
                $ent->setNameIds('idpsso', array());
                $ent->setNameIds('aa', array());
            }
            if ($type !== 'IDP') {
                $ent->setNameIds('spsso', array());
            }
        } else {
            if ($entityTypes['idp']) {
                if (isset($ch['nameids']['idpsso']) && is_array($ch['nameids']['idpsso'])) {
                    $ent->setNameIds('idpsso', $ch['nameids']['idpsso']);
                } else {
                    $ent->setNameIds('idpsso', array());
                }
                if (isset($ch['nameids']['idpaa']) && is_array($ch['nameids']['idpaa'])) {
                    $ent->setNameIds('aa', $ch['nameids']['idpaa']);
                } else {
                    $ent->setNameIds('aa', array());
                }
            }
            if ($entityTypes['sp'] === true) {
                if (isset($ch['nameids']['spsso']) && is_array($ch['nameids']['spsso'])) {
                    $ent->setNameIds('spsso', $ch['nameids']['spsso']);
                } else {
                    $ent->setNameIds('spsso', array());
                }
            }
        }
        $newNameIds['idpsso'] = $ent->getNameIds('idpsso');
        $newNameIds['spsso'] = $ent->getNameIds('spsso');
        $newNameIds['aa'] = $ent->getNameIds('aa');
        if (count(array_diff_assoc($newNameIds['idpsso'], $origNameIds['idpsso'])) > 0 || count(array_diff_assoc($origNameIds['idpsso'], $newNameIds['idpsso'])) > 0) {
            $changeList['NameID: idpsso'] = array('before' => arrayWithKeysToHtml($origNameIds['idpsso']), 'after' => arrayWithKeysToHtml($newNameIds['idpsso']));
        }
        if (count(array_diff_assoc($newNameIds['aa'], $origNameIds['aa'])) > 0 || count(array_diff_assoc($origNameIds['aa'], $newNameIds['aa'])) > 0) {
            $changeList['NameID: idpaa'] = array('before' => arrayWithKeysToHtml($origNameIds['aa']), 'after' => arrayWithKeysToHtml($newNameIds['aa']));
        }
        if (count(array_diff_assoc($newNameIds['spsso'], $origNameIds['spsso'])) > 0 || count(array_diff_assoc($origNameIds['spsso'], $newNameIds['spsso'])) > 0) {
            $changeList['NameID: spsso'] = array('before' => arrayWithKeysToHtml($origNameIds['spsso']), 'after' => arrayWithKeysToHtml($newNameIds['spsso']));
        }


        /**
         * START update service locations
         */

        $idpBinds = array(
            'IDPSingleLogoutService' => array(),
            'SingleSignOnService' => array(),
            'IDPAttributeService' => array(),
            'IDPArtifactResolutionService' => array(),
        );


        $ssobinds = array();
        $idpslobinds = array();
        $spslobinds = array();
        $idpaabinds = array();
        // acsidx - array to collect indexes of AssertionConsumerService
        $acsidx = array('-1');
        $acsdefaultset = false;
        // dridx  - array to collect indexes of DiscoveryResponse
        $dridx = array('-1');
        if (array_key_exists('srv', $ch) && !empty($ch['srv']) && is_array($ch['srv'])) {
            $srvsInput = $ch['srv'];
            $origServiceLocations = $ent->getServiceLocations();
            $origServicesInArray = array();
            foreach ($origServiceLocations as $v) {
                $origServicesInArray['' . $v->getId() . ''] = '' . $v->getType() . ' ::: ' . $v->getBindingName() . ' ::: ' . $v->getUrl() . ' ::: ' . $v->getOrder() . ' ::: ' . (int)$v->getDefault() . '';
                $origServiceType = $v->getType();
                if (array_key_exists($origServiceType, $srvsInput)) {
                    if (array_key_exists($origServiceType, $idpBinds) && !($origServiceType === 'IDPArtifactResolutionService')) {
                        if (!$entityTypes['idp'] && $entityTypes['sp']) {
                            $ent->removeServiceLocation($v);
                            continue;
                        }
                        if (array_key_exists($v->getId(), $srvsInput[$origServiceType])) {
                            if ($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'] == $v->getBindingName()) {
                                if (empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url'])) {
                                    $ent->removeServiceLocation($v);
                                } else {
                                    if (!in_array($v->getBindingName(), $idpBinds['' . $origServiceType . ''])) {

                                        $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                        $this->em->persist($v);
                                        $idpBinds['' . $origServiceType . ''][] = $v->getBindingName();
                                    } else {
                                        log_message('error', 'Found more than one SingSignOnService with the same binding protocol for entity:' . $ent->getEntityId());
                                        log_message('debug', 'Removing duplicate entry');
                                        $ent->removeServiceLocation($v);
                                    }
                                    unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                                }
                            }
                        }
                    } elseif ($origServiceType === 'IDPArtifactResolutionService') {
                        if ($type === 'SP') {
                            log_message('debug', 'GG:IDPArtifactResolutionService entity recognized as SP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        } else {
                            if (array_key_exists($v->getId(), $srvsInput['' . $origServiceType . ''])) {
                                if (empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']) || empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'])) {
                                    $ent->removeServiceLocation($v);
                                } else {
                                    $v->setDefault(FALSE);
                                    $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']) && !in_array($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'], $idpartidx)) {
                                        $v->setOrder($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']);
                                        $idpartidx[] = $srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'];
                                    } else {
                                        $maxidpartindex = max($idpartidx) + 1;
                                        $v->setOrder($maxidpartindex);
                                        $idpartidx[] = $maxidpartindex;
                                    }
                                    $v->setBindingName($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind']);
                                    $this->em->persist($v);
                                }
                            } else {
                                $ent->removeServiceLocation($v);
                            }
                            unset($srvsInput[$origServiceType][$v->getId()]);
                        }
                    } elseif ($origServiceType === 'AssertionConsumerService') {
                        log_message('debug', 'GG:AssertionConsumerService type found');
                        if ($type == 'IDP') {
                            log_message('debug', 'GG:AssertionConsumerService entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        } else {
                            if (array_key_exists($v->getId(), $srvsInput['' . $origServiceType . ''])) {
                                if (empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']) || empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'])) {
                                    $ent->removeServiceLocation($v);
                                } else {
                                    if ($acsdefaultset || empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['default'])) {
                                        $v->setDefault(FALSE);
                                    } else {
                                        $v->setDefault(TRUE);
                                        $acsdefaultset = TRUE;
                                    }
                                    $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']) && !in_array($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'], $acsidx)) {
                                        $v->setOrder($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']);
                                        $acsidx[] = $srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'];
                                    } else {
                                        $maxacsindex = max($acsidx) + 1;
                                        $v->setOrder($maxacsindex);
                                        $acsidx[] = $maxacsindex;
                                    }
                                    $v->setBindingName($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind']);
                                    $this->em->persist($v);
                                }
                            } else {

                                $ent->removeServiceLocation($v);
                            }
                            unset($srvsInput[$origServiceType][$v->getId()]);
                        }
                    } elseif ($origServiceType === 'SPArtifactResolutionService') {
                        log_message('debug', 'GG:SPArtifactResolutionService type found');
                        if ($type === 'IDP') {
                            log_message('debug', 'GG:SPArtifactResolutionService entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        } else {
                            if (array_key_exists($v->getId(), $srvsInput['' . $origServiceType . ''])) {
                                if (empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']) || empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'])) {
                                    $ent->removeServiceLocation($v);
                                } else {
                                    $v->setDefault(FALSE);
                                    $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']) && !in_array($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'], $spartidx)) {
                                        $v->setOrder($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']);
                                        $spartidx[] = $srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'];
                                    } else {
                                        $maxspartindex = max($spartidx) + 1;
                                        $v->setOrder($maxspartindex);
                                        $spartidx[] = $maxspartindex;
                                    }
                                    $v->setBindingName($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind']);
                                    $this->em->persist($v);
                                }
                            } else {
                                $ent->removeServiceLocation($v);
                            }
                            unset($srvsInput[$origServiceType][$v->getId()]);
                        }
                    } elseif ($origServiceType === 'DiscoveryResponse') {
                        log_message('debug', 'GG:DiscoveryResponse type found');
                        if ($type === 'IDP') {
                            log_message('debug', 'GG:DiscoveryResponse entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        } else {
                            if (array_key_exists($v->getId(), $srvsInput['' . $origServiceType . ''])) {
                                if (empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']) || empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'])) {
                                    $ent->removeServiceLocation($v);
                                } else {
                                    $v->setDefault(FALSE);

                                    $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']) && !in_array($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'], $acsidx)) {
                                        $v->setOrder($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order']);
                                        $dridx[] = $srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['order'];
                                    } else {
                                        $maxdrindex = max($dridx) + 1;
                                        $v->setOrder($maxdrindex);
                                        $dridx[] = $maxdrindex;
                                    }
                                    $v->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol');
                                    $this->em->persist($v);
                                }
                            } else {
                                $ent->removeServiceLocation($v);
                            }
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        }
                    } elseif ($origServiceType === 'RequestInitiator') {
                        log_message('debug', 'GG:RequestInitiator type found');
                        if ($type === 'IDP') {
                            log_message('debug', 'GG:RequestInitiator entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        } else {
                            if (array_key_exists($v->getId(), $srvsInput['' . $origServiceType . '']) && !empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url'])) {
                                $v->setDefault(FALSE);
                                $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                $v->setOrderNull();
                                $v->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:request-init');
                                $this->em->persist($v);
                            } else {
                                $ent->removeServiceLocation($v);
                                $this->em->remove($v);
                            }
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        }
                    } elseif ($origServiceType === 'SPSingleLogoutService') {
                        if ($type === 'IDP') {
                            $ent->removeServiceLocation($v);
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        } else {
                            if (array_key_exists($v->getId(), $srvsInput['' . $origServiceType . '']) && !empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url'])) {
                                if (!empty($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind']) && !in_array($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'], $spslobinds)) {
                                    $v->setUrl($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['url']);
                                    $this->em->persist($v);
                                    $spslobinds[] = $srvsInput['' . $origServiceType . '']['' . $v->getId() . '']['bind'];
                                } else {
                                    $ent->removeServiceLocation($v);
                                    $this->em->remove($v);
                                }

                            } else {
                                $ent->removeServiceLocation($v);
                                $this->em->remove($v);
                            }
                            unset($srvsInput['' . $origServiceType . '']['' . $v->getId() . '']);
                        }
                    }
                }
            }

            /**
             * adding new service locations from form
             */
            foreach ($srvsInput as $k => $v) {
                if ($k === 'SingleSignOnService' && $type != 'SP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new sso');
                            if (!in_array($v1['bind'], $ssobinds)) {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('SingleSignOnService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $ssobinds[] = $v1['bind'];
                            } else {
                                log_message('error', 'SingSignOnService url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                } elseif ($k === 'IDPSingleLogoutService' && $type != 'SP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new IDP SingleLogout');
                            if (!in_array($v1['bind'], $idpslobinds)) {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('IDPSingleLogoutService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $idpslobinds[] = $v1['bind'];
                            } else {
                                log_message('error', 'IDP SingLogout url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                } elseif ($k === 'IDPAttributeService' && $type != 'SP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url']) && in_array($v1['bind'], $allowedAABind)) {
                            log_message('debug', 'GGG new IDP IDPAttributeService');
                            if (!in_array($v1['bind'], $idpaabinds)) {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('IDPAttributeService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $idpaabinds[] = $v1['bind'];
                            } else {
                                log_message('error', 'IDP AttributeService url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                } elseif ($k === 'SPSingleLogoutService' && $type != 'IDP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new SP SingleLogout');
                            if (!in_array($v1['bind'], $spslobinds)) {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('SPSingleLogoutService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $spslobinds[] = $v1['bind'];
                            } else {
                                log_message('error', 'SP SingLogout url already set for binding proto: ' . $v1['bind'] . ' for entity ' . $ent->getEntityId()) . ' - removing';
                            }
                        }
                    }
                } elseif ($k === 'AssertionConsumerService' && $type != 'IDP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new SP AsserttionConsumerService');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('AssertionConsumerService');
                            if ($acsdefaultset) {
                                $newservice->setDefault(FALSE);
                            } elseif (isset($v1['default']) && $v1['default'] == 1) {
                                $newservice->setDefault(TRUE);
                            } else {
                                $newservice->setDefault(FALSE);
                            }
                            if (isset($v1['order']) && is_numeric($v1['order'])) {
                                if (in_array($v1['order'], $acsidx)) {
                                    $maxacsindex = max($acsidx) + 1;
                                    $newservice->setOrder($maxacsindex);
                                } else {
                                    $newservice->setOrder($v1['order']);
                                }
                            } else {
                                $maxacsindex = max($acsidx) + 1;
                                $newservice->setOrder($maxacsindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                } elseif ($k === 'IDPArtifactResolutionService' && $type != 'SP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new  IDP ArtifactResolutionService');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('IDPArtifactResolutionService');
                            $newservice->setDefault(FALSE);
                            if (isset($v1['order']) && is_numeric($v1['order'])) {
                                if (in_array($v1['order'], $idpartidx)) {
                                    $maxidpartindex = max($idpartidx) + 1;
                                    $newservice->setOrder($maxidpartindex);
                                } else {
                                    $newservice->setOrder($v1['order']);
                                }
                            } else {
                                $maxidpartindex = max($idpartidx) + 1;
                                $newservice->setOrder($maxidpartindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                } elseif ($k === 'SPArtifactResolutionService' && $type != 'IDP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new SP SPArtifactResolutionService');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('SPArtifactResolutionService');
                            $newservice->setDefault(FALSE);
                            if (isset($v1['order']) && is_numeric($v1['order'])) {
                                if (in_array($v1['order'], $spartidx)) {
                                    $maxspartindex = max($spartidx) + 1;
                                    $newservice->setOrder($maxspartindex);
                                } else {
                                    $newservice->setOrder($v1['order']);
                                }
                            } else {
                                $maxspartindex = max($spartidx) + 1;
                                $newservice->setOrder($maxspartindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                } elseif ($k === 'DiscoveryResponse' && $type != 'IDP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        if (!empty($v1['bind']) && !empty($v1['url'])) {
                            log_message('debug', 'GGG new SP DiscoveryResponse');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol');
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('DiscoveryResponse');
                            $newservice->setDefault(FALSE);
                            if (isset($v1['order']) && is_numeric($v1['order'])) {
                                if (in_array($v1['order'], $dridx)) {
                                    $maxdrindex = max($dridx) + 1;
                                    $newservice->setOrder($maxdrindex);
                                } else {
                                    $newservice->setOrder($v1['order']);
                                }
                            } else {
                                $maxdrindex = max($dridx) + 1;
                                $newservice->setOrder($maxdrindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                } elseif ($k === 'RequestInitiator' && $type != 'IDP') {
                    foreach ($srvsInput[$k] as $k1 => $v1) {
                        log_message('debug', 'GGG new SP RequestInitiator');
                        $newservice = new models\ServiceLocation();
                        $newservice->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:request-init');
                        $newservice->setUrl($v1['url']);
                        $newservice->setType('RequestInitiator');
                        $newservice->setDefault(FALSE);
                        $newservice->setOrderNull();
                        $newservice->setProvider($ent);
                        $ent->setServiceLocation($newservice);
                        $this->em->persist($newservice);
                    }
                }
            }
        }

        $newsrvs = $ent->getServiceLocations();
        $newServicesInArray = array();
        $counter = 0;
        foreach ($newsrvs as $v) {
            $vid = $v->getId();
            if (empty($vid)) {
                $d = 'n' . $counter;
            } else {
                $d = $v->getId();
            }
            $newServicesInArray[$d] = '' . $v->getType() . ' ::: ' . $v->getBindingName() . ' ::: ' . $v->getUrl() . ' ::: ' . $v->getOrder() . ' ::: ' . (int)$v->getDefault() . '';
            $counter++;
        }
        $diff1 = array_diff_assoc($newServicesInArray, $origServicesInArray);
        $diff2 = array_diff_assoc($origServicesInArray, $newServicesInArray);
        if (count($diff1) > 0 || count($diff2) > 0) {
            $changeList['ServiceLocations'] = array('before' => arrayWithKeysToHtml($diff2), 'after' => arrayWithKeysToHtml($diff1));
        }
        /**
         * END update service locations
         */

        $this->updateCerts($ent, $ch);
        $this->updateContacts($ent, $ch);


        if (!array_key_exists('usestatic', $ch)) {
            $ent->setStatic(false);
        }
        if (array_key_exists('static', $ch)) {
            $exmeta = $ent->getStaticMetadata();
            if (empty($exmeta)) {
                $exmeta = new models\StaticMetadata;
            }
            $ch['static'] = jXMLFilter($ch['static']);
            $exmeta->setMetadata(trim($ch['static']));
            $exmeta->setProvider($ent);
            $ent->setStaticMetadata($exmeta);
            $this->em->persist($exmeta);

            $exmetaAfter = $ent->getStaticMetadata();
            if (!empty($exmetaAfter)) {
                if (array_key_exists('usestatic', $ch) && ($ch['usestatic'] === 'accept')) {
                    $ent->setStatic(true);
                }
            }
        }
        $this->logtracks = array_merge($this->logtracks, $changeList);
        if (count($this->logtracks) > 0 && !empty($entid)) {
            $this->ci->tracker->save_track('ent', 'modification', $ent->getEntityId(), serialize($this->logtracks), FALSE);
        }
        $this->logtracks = array();
        return $ent;
    }

}
