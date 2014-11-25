<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * ProviderUpdater Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Providerupdater
{

    protected $ci;
    protected $em;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;

        $this->ci->load->library('tracker');
    }

    public function getChangeProposal(models\Provider $ent, $chg)
    {
        $p['entityid'] = $ent->getEntityId();
    }

    public function updateRegPolicies(models\Provider $ent, array $ch, $isAdmin = false)
    {
        $currentCocs = $ent->getCoc();
        if (array_key_exists('regpol', $ch))
        {
            log_message('debug', 'GKS ' . __METHOD__ . ' ' . serialize($ch['regpol']) . '');
            $currentRegPol = &$currentCocs;
            $regPolToAssign = array();
            foreach ($currentRegPol as $k => $v)
            {
                $cid = $v->getId();
                $ctype = $v->getType();
                if ($ctype === 'regpol')
                {
                    $foundkey = array_search($cid, $ch['regpol']);
                    if ($foundkey === null || $foundkey === false)
                    {
                        log_message('debug', 'GKS not found ' . $cid);
                        $ent->removeCoc($v);
                    }
                }
            }
            $requestNew = false;
            foreach ($ch['regpol'] as $k => $v)
            {
                if (!empty($v) && is_numeric($v))
                {
                    $c = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $v, 'type' => 'regpol'));
                    if (!empty($c))
                    {
                        log_message('debug', 'GKS found regpl with id:' . $c->getId());
                    }
                    if (!empty($ent))
                    {
                        log_message('debug', 'GKS ENT:' . $ent->getId());
                    }
                    if (!empty($c) && !$currentRegPol->contains($c))
                    {
                        $requestNew = true;

                        if ($isAdmin)
                        {
                            $ent->setCoc($c);
                            log_message('debug', 'GKS setting coc');
                        }
                        else
                        {
                            $this->ci->approval->applyForRegistrationPolicy($c, $ent);
                        }
                    }
                }
            }
        }
        if (!$requestNew)
        {
            $this->ci->globalnotices[] = lang('updated');
        }
    }

    public function updateProvider(models\Provider $ent, array $ch)
    {
        // $m - array for modifications
        $entid = $ent->getId();
        $m = array();
        $type = $ent->getType();
        $langCodes = languagesCodes();
        $ex = $ent->getExtendMetadata();
        $idpMDUIparent = null;
        $spMDUIparent = null;
        $extend = array();
        $allowedAABind = getAllowedSOAPBindings();
        $spartidx = array();
        $idpartidx = array('-1');
        $acsidx = array();
        $isAdmin = $this->ci->j_auth->isAdministrator();
        if ($isAdmin)
        {
            $dissalowedparts = array();
        }
        else
        {
            $dissalowedparts = $this->ci->config->item('entpartschangesdisallowed');
            if (empty($dissalowedparts) || !is_array($dissalowedparts))
            {
                $dissalowedparts = array();
            }
        }
        log_message('debug', 'disallowedpart: ' . serialize($dissalowedparts));
        foreach ($ex as $e)
        {
            $extend['' . $e->getType() . '']['' . $e->getNamespace() . '']['' . $e->getElement() . ''][] = $e;
            if ($e->getElement() == 'UIInfo' && $e->getNamespace() == 'mdui')
            {
                if ($e->getType() === 'idp')
                {
                    $idpMDUIparent = $e;
                }
                elseif ($e->getType() === 'sp')
                {
                    $spMDUIparent = $e;
                }
            }
        }

        if (array_key_exists('reqattr', $ch) && strcasecmp($type, 'IDP') != 0)
        {


            log_message('debug', __METHOD__ . ' OKA: ' . count($ch['reqattr']));
            $attrstmp = $this->em->getRepository("models\Attribute")->findAll();
            foreach ($attrstmp as $attrv)
            {
                $attributes['' . $attrv->getId() . ''] = $attrv;
            }
            $trs = $ent->getAttributesRequirement();
            $origAttrReqs = array();
            $attrIdsDefined = array();
            foreach ($trs as $tr)
            {
                $keyid = $tr->getAttribute()->getId();
                if (array_key_exists($keyid, $origAttrReqs))
                {
                    log_error('warning', __METHOD__ . ' found duplicate in attr req for entityid:' . $ent->getEntityId());
                    $trs->removeElement($tr);
                    $this->em->remove($tr);
                    continue;
                }

                $origAttrReqs['' . $keyid . ''] = $tr;
            }


            foreach ($ch['reqattr'] as $newAttrReq)
            {
                $alreadyDefined = false;
                $idCheck = $newAttrReq['attrid'];
                if (in_array($idCheck, $attrIdsDefined))
                {
                    $alreadyDefined = true;
                }
                else
                {
                    $attrIdsDefined[] = $idCheck;
                }
                if (array_key_exists($idCheck, $origAttrReqs))
                {

                    if ($alreadyDefined)
                    {
                        $trs->removeElement($origAttrReqs['' . $idCheck . '']);
                        $this->em->remove($origAttrReqs['' . $idCheck . '']);
                    }
                    else
                    {
                        $origAttrReqs['' . $idCheck . '']->setReason($newAttrReq['reason']);
                        $origAttrReqs['' . $idCheck . '']->setStatus($newAttrReq['status']);
                        $this->em->persist($origAttrReqs['' . $idCheck . '']);
                        unset($origAttrReqs['' . $idCheck . '']);
                    }
                }
                elseif (!$alreadyDefined && isset($attributes['' . $idCheck . '']))
                {
                    log_message('debug', __METHOD__ . ' OKA: new reqattr');
                    $nreq = new models\AttributeRequirement;
                    $nreq->setStatus($newAttrReq['status']);
                    $nreq->setReason($newAttrReq['reason']);
                    $nreq->setType('SP');
                    $nreq->setSP($ent);
                    $nreq->setAttribute($attributes['' . $idCheck . '']);
                    $ent->setAttributesRequirement($nreq);
                    $this->em->persist($nreq);
                    unset($origAttrReqs['' . $idCheck . '']);
                }
            }
            foreach ($origAttrReqs as $orv)
            {

                $trs->removeElement($orv);
                $this->em->remove($orv);
            }
        }

        if ($type !== 'SP')
        {
            if (empty($idpMDUIparent))
            {
                $idpMDUIparent = new models\ExtendMetadata;
                $idpMDUIparent->setType('idp');
                $idpMDUIparent->setNamespace('mdui');
                $idpMDUIparent->setElement('UIInfo');
                $ent->setExtendMetadata($idpMDUIparent);
                $this->em->persist($idpMDUIparent);
            }

            /**
             * set scopes
             */
            if (array_key_exists('scopes', $ch) && (!in_array('scope', $dissalowedparts) || empty($entid)))
            {
                $origscopesso = implode(',', $ent->getScope('idpsso'));
                $origscopeaa = implode(',', $ent->getScope('aa'));
                if (array_key_exists('idpsso', $ch['scopes']) && !empty($ch['scopes']['idpsso']))
                {
                    $idpssoscopes = array_filter(explode(',', $ch['scopes']['idpsso']));
                    $ent->setScope('idpsso', array_unique($idpssoscopes));
                    if ($origscopesso != implode(',', $idpssoscopes))
                    {
                        $m['Scope IDPSSO'] = array('before' => $origscopesso, 'after' => implode(',', $idpssoscopes));
                    }
                }
                else
                {
                    $ent->setScope('idpsso', array());
                    if (!empty($origscopesso))
                    {
                        $m['Scope IDPSSO'] = array('before' => $origscopesso, 'after' => '');
                    }
                }
                if (array_key_exists('aa', $ch['scopes']) && !empty($ch['scopes']['aa']))
                {
                    log_message('debug', 'GKS SCOPE AA yes');

                    $aascopes = array_filter(explode(',', $ch['scopes']['aa']));
                    $ent->setScope('aa', array_unique($aascopes));
                    if ($origscopeaa != implode(',', $aascopes))
                    {
                        $m['Scope AA'] = array('before' => $origscopeaa, 'after' => implode(',', $aascopes));
                    }
                }
                else
                {
                    log_message('debug', 'GKS SCOPE AA no');
                    $ent->setScope('aa', array());
                    if (!empty($origscopeaa))
                    {
                        $m['Scope AA'] = array('before' => $origscopeaa, 'after' => '');
                    }
                }
                $origscopesso = null;
            }
        }
        if (($type !== 'IDP') && empty($spMDUIparent))
        {
            $spMDUIparent = new models\ExtendMetadata;
            $spMDUIparent->setType('sp');
            $spMDUIparent->setNamespace('mdui');
            $spMDUIparent->setElement('UIInfo');
            $ent->setExtendMetadata($spMDUIparent);
            $this->em->persist($spMDUIparent);
        }

        if (array_key_exists('entityid', $ch) && !empty($ch['entityid']))
        {
            if (!empty($entid))
            {
                if (strcmp($ent->getEntityId(), $ch['entityid']) != 0 && !in_array('entityid', $dissalowedparts))
                {
                    $m['EntityID'] = array('before' => $ent->getEntityId(), 'after' => $ch['entityid']);
                    $this->ci->tracker->renameProviderResourcename($ent->getEntityId(), $ch['entityid']);
                    $ent->setEntityId($ch['entityid']);
                }
            }
            else
            {
                $ent->setEntityId($ch['entityid']);
            }
        }
        if (array_key_exists('lname', $ch) && is_array($ch['lname']))
        {
            $origs = $ent->getMergedLocalName();
            $trackorigs = $origs;
            $langs = array_keys(languagesCodes());
            foreach ($ch['lname'] as $key => $value)
            {
                if (!in_array($key, $langs))
                {
                    unset($ch['lname']['' . $key . '']);
                    log_message('warning', __METHOD__ . ' lang code ' . $key . ' (localized name) not found in allowed langs');
                }
            }
            $lnamediffs = FALSE;
            $diff1 = array_diff_assoc($trackorigs, $ch['lname']);
            if (count($diff1) > 0)
            {
                $lnamediffs = TRUE;
            }
            else
            {
                $diff1 = array_diff_assoc($ch['lname'], $trackorigs);
                if (count($diff1) > 0)
                {
                    $lnamediffs = TRUE;
                }
            }
            if (isset($ch['lname']['en']))
            {
                $ent->setName($ch['lname']['en']);
                unset($ch['lname']['en']);
            }
            else
            {
                $ent->setName(null);
            }
            $ent->setLocalName($ch['lname']);
            if ($lnamediffs === TRUE)
            {
                $m['Localized name'] = array('before' => arrayWithKeysToHtml($trackorigs), 'after' => arrayWithKeysToHtml($ch['lname']));
            }
        }

        if (array_key_exists('ldisplayname', $ch) && is_array($ch['ldisplayname']))
        {
            $origs = $ent->getMergedLocalDisplayname();
            $langs = array_keys(languagesCodes());
            foreach ($ch['ldisplayname'] as $key => $value)
            {
                if (!in_array($key, $langs))
                {
                    unset($ch['ldisplayname']['' . $key . '']);
                    log_message('warning', __METHOD__ . ' lang code ' . $key . ' (localized displayname) not found in allowed langs');
                }
            }
            $isDifferent = FALSE;
            $diff1 = array_diff_assoc($origs, $ch['ldisplayname']);
            if (count($diff1) > 0)
            {
                $isDifferent = TRUE;
            }
            else
            {
                $diff1 = array_diff_assoc($ch['ldisplayname'], $origs);
                if (count($diff1) > 0)
                {
                    $isDifferent = TRUE;
                }
            }
            if ($isDifferent)
            {
                if (isset($ch['ldisplayname']['en']))
                {
                    $ent->setDisplayName($ch['ldisplayname']['en']);
                    unset($ch['ldisplayname']['en']);
                }
                else
                {
                    $ent->setDisplayName(null);
                }
                $ent->setLocalDisplayName($ch['ldisplayname']);
                $tmpbefore = str_replace(array("{", "}", ":", "\/"), array("", "", ":", "/"), json_encode($origs));
                $tmpafter = str_replace(array("{", "}", ":", "\/"), array("", "", ":", "/"), json_encode($ch['ldisplayname']));
                $m['Localized DisplayName'] = array('before' => $tmpbefore, 'after' => $tmpafter);
            }
        }
        if ($isAdmin)
        {
            if (array_key_exists('regauthority', $ch))
            {
                if ($ent->getRegistrationAuthority() !== $ch['regauthority'])
                {
                    $m['RegistrationAuthority'] = array('before' => $ent->getRegistrationAuthority(), 'after' => $ch['regauthority']);
                }
                $ent->setRegistrationAuthority($ch['regauthority']);
            }
            if (array_key_exists('registrationdate', $ch))
            {
                $prevregdate = $ent->getRegistrationDate();
                if (isset($prevregdate))
                {
                    $prevregdate = date('Y-m-d', $prevregdate->format('U') + j_auth::$timeOffset);
                }
                else
                {
                    $prevregdate = '';
                }
                if ($prevregdate !== $ch['registrationdate'])
                {
                    $m['RegistrationDate'] = array('before' => $prevregdate, 'after' => $ch['registrationdate']);
                    if (!empty($ch['registrationdate']))
                    {
                        $ent->setRegistrationDate(\DateTime::createFromFormat('Y-m-d H:i:s', $ch['registrationdate'] . ' 00:00:00'));
                    }
                    else
                    {
                        $ent->setRegistrationDate(null);
                    }
                }
            }
        }
        if (array_key_exists('lhelpdesk', $ch) && is_array($ch['lhelpdesk']))
        {
            $origs = $ent->getHelpdeskUrlLocalized();
            $langs = array_keys(languagesCodes());
            foreach ($ch['lhelpdesk'] as $key => $value)
            {
                if (!in_array($key, $langs))
                {
                    unset($ch['lhelpdesk']['' . $key . '']);
                    log_message('warning', __METHOD__ . ' lang code ' . $key . ' (localized helpdeskurl) not found in allowed langs');
                }
            }
            $isDifferent = FALSE;
            $diff1 = array_diff_assoc($origs, $ch['lhelpdesk']);
            if (count($diff1) > 0)
            {
                $isDifferent = TRUE;
            }
            else
            {
                $diff1 = array_diff_assoc($ch['lhelpdesk'], $origs);
                if (count($diff1) > 0)
                {
                    $isDifferent = TRUE;
                }
            }
            if ($isDifferent)
            {
                if (isset($ch['lhelpdesk']['en']))
                {
                    $ent->setHelpdeskUrl($ch['lhelpdesk']['en']);
                    unset($ch['lhelpdesk']['en']);
                }
                else
                {
                    $ent->setHelpdeskUrl(null);
                }
                $ent->setLocalHelpdeskUrl($ch['lhelpdesk']);
                $tmpbefore = str_replace(array("{", "}", ":", "\/"), array("", "", ":", "/"), json_encode($origs));
                $tmpafter = str_replace(array("{", "}", ":", "\/"), array("", "", ":", "/"), json_encode($ch['lhelpdesk']));
                $m['Localized HelpdeskURL'] = array('before' => $tmpbefore, 'after' => $tmpafter);
            }
        }

        $currentCocs = $ent->getCoc();
        /**
         * @todo track coc changes
         */
        if (array_key_exists('coc', $ch))
        {
            $currentEntCat = &$currentCocs;
            foreach ($currentEntCat as $k => $v)
            {
                $cid = $v->getId();
                $ctype = $v->getType();
                if ($ctype === 'entcat')
                {
                    $foundkey = array_search($cid, $ch['coc']);
                    if ($foundkey === null || $foundkey === false)
                    {
                        $ent->removeCoc($v);
                    }
                }
            }
            foreach ($ch['coc'] as $k => $v)
            {
                if (!empty($v) && is_numeric($v))
                {
                    $c = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $v, 'type' => 'entcat'));
                    if (!empty($c) && !$currentEntCat->contains($c))
                    {
                        if ($isAdmin)
                        {
                            $ent->setCoc($c);
                        }
                        else
                        {
                            $this->ci->approval->applyForEntityCategory($c, $ent);
                        }
                    }
                }
            }
        }



        if (array_key_exists('privacyurl', $ch))
        {
            if ($ent->getPrivacyURL() !== $ch['privacyurl'])
            {
                $m['PrivacyURL general'] = array('before' => $ent->getPrivacyURL(), 'after' => $ch['privacyurl']);
            }
            $ent->setPrivacyUrl($ch['privacyurl']);
        }
        if (array_key_exists('prvurl', $ch))
        {
            if ($type !== 'IDP')
            {
                $origex = array();
                $origs = array();
                if (isset($extend['sp']['mdui']['PrivacyStatementURL']))
                {
                    foreach ($extend['sp']['mdui']['PrivacyStatementURL'] as $v)
                    {
                        $l = $v->getAttributes();
                        $origex['' . $l['xml:lang'] . ''] = $v;
                        $origs['' . $l['xml:lang'] . ''] = $v->getElementValue();
                    }
                }
                $newex = array();
                if (isset($ch['prvurl']['spsso']))
                {
                    $newex = $ch['prvurl']['spsso'];
                    foreach ($origex as $key => $value)
                    {
                        if (array_key_exists($key, $ch['prvurl']['spsso']))
                        {
                            if (empty($ch['prvurl']['spsso']['' . $key . '']))
                            {
                                $value->setProvider(NULL);
                                $ex->removeElement($value);
                                $this->em->remove($value);
                                unset($newex['' . $key . '']);
                            }
                            else
                            {
                                $value->setValue($ch['prvurl']['spsso']['' . $key . '']);
                                $this->em->persist($value);
                            }
                            unset($ch['prvurl']['spsso']['' . $key . '']);
                        }
                        else
                        {
                            $value->setProvider(NULL);
                            $ex->removeElement($value);
                            $this->em->remove($value);
                            unset($newex['' . $key . '']);
                            unset($ch['prvurl']['spsso']['' . $key . '']);
                        }
                    }

                    foreach ($ch['prvurl']['spsso'] as $key2 => $value2)
                    {
                        if (!empty($value2))
                        {
                            $nprvurl = new models\ExtendMetadata();
                            $nprvurl->setType('sp');
                            $nprvurl->setNamespace('mdui');
                            $nprvurl->setElement('PrivacyStatementURL');
                            $nprvurl->setAttributes(array('xml:lang' => $key2));
                            $nprvurl->setValue($value2);
                            $ent->setExtendMetadata($nprvurl);
                            $nprvurl->setParent($spMDUIparent);
                            $this->em->persist($nprvurl);
                        }
                    }
                    $prvurldiffs = FALSE;
                    $diff1 = array_diff_assoc($origs, $newex);
                    if (count($diff1) > 0)
                    {
                        $prvurldiffs = TRUE;
                    }
                    else
                    {
                        $diff1 = array_diff_assoc($newex, $origs);
                        if (count($diff1) > 0)
                        {
                            $prvurldiffs = TRUE;
                        }
                    }
                    if ($prvurldiffs === TRUE)
                    {
                        $m['Privacy Statement URLs (SP)'] = array('before' => arrayWithKeysToHtml($origs), 'after' => arrayWithKeysToHtml($newex));
                    }
                }
            }
            if ($type !== 'SP')
            {
                $origex = array();
                $origs = array();
                if (isset($extend['idp']['mdui']['PrivacyStatementURL']))
                {
                    foreach ($extend['idp']['mdui']['PrivacyStatementURL'] as $v)
                    {
                        $l = $v->getAttributes();
                        $origex['' . $l['xml:lang'] . ''] = $v;
                        $origs['' . $l['xml:lang'] . ''] = $v->getElementValue();
                    }
                }
                $newex = array();
                if (isset($ch['prvurl']['idpsso']))
                {
                    $newex = $ch['prvurl']['idpsso'];
                    foreach ($origex as $key => $value)
                    {
                        if (array_key_exists($key, $ch['prvurl']['idpsso']))
                        {
                            if (empty($ch['prvurl']['idpsso']['' . $key . '']))
                            {
                                $value->setProvider(NULL);
                                $ex->removeElement($value);
                                $this->em->remove($value);
                                unset($newex['' . $key . '']);
                            }
                            else
                            {
                                $value->setValue($ch['prvurl']['idpsso']['' . $key . '']);
                                $this->em->persist($value);
                            }
                            unset($ch['prvurl']['idpsso']['' . $key . '']);
                        }
                        else
                        {
                            $value->setProvider(NULL);
                            $ex->removeElement($value);
                            $this->em->remove($value);
                            unset($newex['' . $key . '']);
                            unset($ch['prvurl']['idpsso']['' . $key . '']);
                        }
                    }

                    foreach ($ch['prvurl']['idpsso'] as $key2 => $value2)
                    {
                        if (!empty($value2))
                        {
                            $nprvurl = new models\ExtendMetadata();
                            $nprvurl->setType('idp');
                            $nprvurl->setNamespace('mdui');
                            $nprvurl->setElement('PrivacyStatementURL');
                            $nprvurl->setAttributes(array('xml:lang' => $key2));
                            $nprvurl->setValue($value2);
                            $ent->setExtendMetadata($nprvurl);
                            $nprvurl->setParent($idpMDUIparent);
                            $this->em->persist($nprvurl);
                        }
                    }
                    $prvurldiffs = FALSE;
                    $diff1 = array_diff_assoc($origs, $newex);
                    if (count($diff1) > 0)
                    {
                        $prvurldiffs = TRUE;
                    }
                    else
                    {
                        $diff1 = array_diff_assoc($newex, $origs);
                        if (count($diff1) > 0)
                        {
                            $prvurldiffs = TRUE;
                        }
                    }
                    if ($prvurldiffs === TRUE)
                    {
                        $m['Privacy Statement URLs (IdP)'] = array('before' => arrayWithKeysToHtml($origs), 'after' => arrayWithKeysToHtml($newex));
                    }
                }
            }
        }

        /**
         * START update protocols enumeration
         */
        $protocolSupport['idpsso'] = $ent->getProtocolSupport('idpsso');
        $protocolSupport['spsso'] = $ent->getProtocolSupport('spsso');
        $protocolSupport['aa'] = $ent->getProtocolSupport('aa');
        if (array_key_exists('prot', $ch) && !empty($ch['prot']) && is_array($ch['prot']))
        {
            if (isset($ch['prot']['aa']) && is_array($ch['prot']['aa']))
            {
                $ent->setProtocolSupport('aa', $ch['prot']['aa']);
            }
            if (isset($ch['prot']['idpsso']) && is_array($ch['prot']['idpsso']))
            {
                $ent->setProtocolSupport('idpsso', $ch['prot']['idpsso']);
            }
            if (isset($ch['prot']['spsso']) && is_array($ch['prot']['spsso']))
            {
                $ent->setProtocolSupport('spsso', $ch['prot']['spsso']);
            }
            $newProtocolSupport['idpsso'] = $ent->getProtocolSupport('idpsso');
            $newProtocolSupport['spsso'] = $ent->getProtocolSupport('spsso');
            $newProtocolSupport['aa'] = $ent->getProtocolSupport('aa');
            foreach ($newProtocolSupport as $k => $v)
            {
                if (count(array_diff_assoc($newProtocolSupport['' . $k . ''], $protocolSupport['' . $k . ''])) > 0 || count(array_diff_assoc($protocolSupport['' . $k . ''], $newProtocolSupport['' . $k . ''])) > 0)
                {
                    $m['ProtocolEnumeration ' . $k . ''] = array('before' => arrayWithKeysToHtml($protocolSupport['' . $k . '']), 'after' => arrayWithKeysToHtml($newProtocolSupport['' . $k . '']));
                }
            }
        }

        /**
         * @todo add track for nameids
         */
        $origNameIds['idpsso'] = $ent->getNameIds('idpsso');
        $origNameIds['spsso'] = $ent->getNameIds('spsso');
        $origNameIds['aa'] = $ent->getNameIds('aa');
        if (!array_key_exists('nameids', $ch))
        {
            if ($type !== 'SP')
            {
                $ent->setNameIds('idpsso', array());
                $ent->setNameIds('aa', array());
            }
            if ($type !== 'IDP')
            {
                $ent->setNameIds('spsso', array());
            }
        }
        if ($type !== 'SP')
        {
            if (isset($ch['nameids']['idpsso']) && is_array($ch['nameids']['idpsso']))
            {
                $ent->setNameIds('idpsso', $ch['nameids']['idpsso']);
            }
            else
            {
                $ent->setNameIds('idpsso', array());
            }
            if (isset($ch['nameids']['idpaa']) && is_array($ch['nameids']['idpaa']))
            {
                $ent->setNameIds('aa', $ch['nameids']['idpaa']);
            }
            else
            {
                $ent->setNameIds('aa', array());
            }
        }
        if ($type !== 'IDP')
        {
            if (isset($ch['nameids']['spsso']) && is_array($ch['nameids']['spsso']))
            {
                $ent->setNameIds('spsso', $ch['nameids']['spsso']);
            }
            else
            {
                $ent->setNameIds('spsso', array());
            }
        }
        $newNameIds['idpsso'] = $ent->getNameIds('idpsso');
        $newNameIds['spsso'] = $ent->getNameIds('spsso');
        $newNameIds['aa'] = $ent->getNameIds('aa');
        if (count(array_diff_assoc($newNameIds['idpsso'], $origNameIds['idpsso'])) > 0 || count(array_diff_assoc($origNameIds['idpsso'], $newNameIds['idpsso'])) > 0)
        {
            $m['NameID: idpsso'] = array('before' => arrayWithKeysToHtml($origNameIds['idpsso']), 'after' => arrayWithKeysToHtml($newNameIds['idpsso']));
        }
        if (count(array_diff_assoc($newNameIds['aa'], $origNameIds['aa'])) > 0 || count(array_diff_assoc($origNameIds['aa'], $newNameIds['aa'])) > 0)
        {
            $m['NameID: idpaa'] = array('before' => arrayWithKeysToHtml($origNameIds['aa']), 'after' => arrayWithKeysToHtml($newNameIds['aa']));
        }
        if (count(array_diff_assoc($newNameIds['spsso'], $origNameIds['spsso'])) > 0 || count(array_diff_assoc($origNameIds['spsso'], $newNameIds['spsso'])) > 0)
        {
            $m['NameID: spsso'] = array('before' => arrayWithKeysToHtml($origNameIds['spsso']), 'after' => arrayWithKeysToHtml($newNameIds['spsso']));
        }


        /**
         * START update service locations
         */
        $ssobinds = array();
        $idpslobinds = array();
        $spslobinds = array();
        $idpaabinds = array();
        // acsidx - array to collect indexes of AssertionConsumerService
        $acsidx = array('-1');
        $acsdefaultset = false;
        // dridx  - array to collect indexes of DiscoveryResponse
        $dridx = array('-1');
        if (array_key_exists('srv', $ch) && !empty($ch['srv']) && is_array($ch['srv']))
        {
            $srvs = $ch['srv'];
            $orgsrvs = $ent->getServiceLocations();
            $origServicesInArray = array();
            foreach ($orgsrvs as $v)
            {
                $origServicesInArray['' . $v->getId() . ''] = '' . $v->getType() . ' ::: ' . $v->getBindingName() . ' ::: ' . $v->getUrl() . ' ::: ' . $v->getOrder() . ' ::: ' . (int) $v->getDefault() . '';
                $srvtype = $v->getType();
                if (array_key_exists($srvtype, $srvs))
                {
                    if ($srvtype === 'SingleSignOnService')
                    {
                        if ($type === 'SP')
                        {
                            $ent->removeServiceLocation($v);
                        }
                        else
                        {
                            if (array_key_exists($v->getId(), $srvs[$srvtype]))
                            {
                                if ($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind'] == $v->getBindingName())
                                {
                                    if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']))
                                    {
                                        $ent->removeServiceLocation($v);
                                    }
                                    else
                                    {
                                        if (!in_array($v->getBindingName(), $ssobinds))
                                        {

                                            $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                            $this->em->persist($v);
                                            $ssobinds[] = $v->getBindingName();
                                        }
                                        else
                                        {
                                            log_message('error', 'Found more than one SingSignOnService with the same binding protocol for entity:' . $ent->getEntityId());
                                            log_message('debug', 'Removing duplicate entry');
                                            $ent->removeServiceLocation($v);
                                        }
                                        unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                                    }
                                }
                            }
                        }
                    }
                    elseif ($srvtype === 'IDPSingleLogoutService')
                    {
                        log_message('debug', 'GG:IDPSingleLogoutService type found');
                        if ($type === 'SP')
                        {
                            log_message('debug', 'GG:IDPSingleLogoutService entity SP removein service');
                            $ent->removeServiceLocation($v);
                        }
                        elseif (in_array($v->getBindingName(), $idpslobinds))
                        {
                            log_message('debug', 'GG: found bind:' . $v->getBindingName() . ' in array idpslobinds');
                            log_message('debug', 'GG current values in idpslobinds: ' . serialize($idpslobinds));
                            $ent->removeServiceLocation($v);
                        }
                        else
                        {
                            log_message('debug', 'GG: step 2');
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                log_message('debug', 'GG:IDPSingleLogoutService: found id in form:' . $v->getId() . ' with url: ' . $v->getUrl());
                                $idpslobinds[] = $v->getBindingName();
                                if ($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind'] === $v->getBindingName())
                                {
                                    if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']))
                                    {
                                        $ent->removeServiceLocation($v);
                                    }
                                    else
                                    {
                                        $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                        $this->em->persist($v);
                                    }
                                    unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                                }
                            }
                        }
                    }
                    elseif ($srvtype === 'SPSingleLogoutService')
                    {
                        log_message('debug', 'GG:SPSingleLogoutService type found');
                        if ($type == 'IDP')
                        {
                            log_message('debug', 'GG:SPSingleLogoutService entity SP removein service');
                            $ent->removeServiceLocation($v);
                        }
                        elseif (in_array($v->getBindingName(), $spslobinds))
                        {
                            log_message('debug', 'GG: found bind:' . $v->getBindingName() . ' in array idpslobinds');
                            log_message('debug', 'GG current values in spslobinds: ' . serialize($spslobinds));
                            $ent->removeServiceLocation($v);
                        }
                        else
                        {
                            log_message('debug', 'GG: step 2');
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                log_message('debug', 'GG:SPSingleLogoutService: found id in form:' . $v->getId() . ' with url: ' . $v->getUrl());
                                $spslobinds[] = $v->getBindingName();
                                if ($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind'] === $v->getBindingName())
                                {
                                    if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']))
                                    {
                                        $ent->removeServiceLocation($v);
                                    }
                                    else
                                    {
                                        $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                        $this->em->persist($v);
                                    }
                                    unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                                }
                            }
                        }
                    }
                    elseif ($srvtype === 'IDPAttributeService')
                    {
                        log_message('debug', 'GG:IDPAttributeService type found');
                        if ($type == 'SP')
                        {
                            log_message('debug', 'GG:IDPAttributeService entity SP removein service');
                            $ent->removeServiceLocation($v);
                        }
                        elseif (in_array($v->getBindingName(), $idpaabinds) || !in_array($v->getBindingName(), $allowedAABind))
                        {
                            log_message('debug', 'GG: found bind:' . $v->getBindingName() . ' in array idpslobinds');
                            log_message('debug', 'GG current values in spslobinds: ' . serialize($idpaabinds));
                            $ent->removeServiceLocation($v);
                        }
                        else
                        {
                            log_message('debug', 'GG: step 2');
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                log_message('debug', 'GG:SPSingleLogoutService: found id in form:' . $v->getId() . ' with url: ' . $v->getUrl());
                                $idpaabinds[] = $v->getBindingName();
                                if ($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind'] === $v->getBindingName())
                                {
                                    if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']))
                                    {
                                        $ent->removeServiceLocation($v);
                                    }
                                    else
                                    {
                                        $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                        $this->em->persist($v);
                                    }
                                    unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                                }
                            }
                            else
                            {
                                $ent->removeServiceLocation($v);
                            }
                        }
                    }
                    elseif ($srvtype === 'IDPArtifactResolutionService')
                    {
                        log_message('debug', 'GG:IDPArtifactResolutionService type found');
                        if ($type === 'SP')
                        {
                            log_message('debug', 'GG:IDPArtifactResolutionService entity recognized as SP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                        else
                        {
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']) or empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']))
                                {
                                    $ent->removeServiceLocation($v);
                                }
                                else
                                {
                                    $v->setDefault(FALSE);
                                    $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']) && !in_array($srvs['' . $srvtype . '']['' . $v->getId() . '']['order'], $idpartidx))
                                    {
                                        $v->setOrder($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']);
                                        $idpartidx[] = $srvs['' . $srvtype . '']['' . $v->getId() . '']['order'];
                                    }
                                    else
                                    {
                                        $maxidpartindex = max($idpartidx) + 1;
                                        $v->setOrder($maxidpartindex);
                                        $idpartidx[] = $maxidpartindex;
                                    }
                                    $v->setBindingName($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']);
                                    $this->em->persist($v);
                                }
                            }
                            else
                            {
                                $ent->removeServiceLocation($v);
                            }
                            unset($srvs[$srvtype][$v->getId()]);
                        }
                    }
                    elseif ($srvtype === 'AssertionConsumerService')
                    {
                        log_message('debug', 'GG:AssertionConsumerService type found');
                        if ($type == 'IDP')
                        {
                            log_message('debug', 'GG:AssertionConsumerService entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                        else
                        {
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']) or empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']))
                                {
                                    $ent->removeServiceLocation($v);
                                }
                                else
                                {
                                    if ($acsdefaultset || empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['default']))
                                    {
                                        $v->setDefault(FALSE);
                                    }
                                    else
                                    {
                                        $v->setDefault(TRUE);
                                        $acsdefaultset = TRUE;
                                    }
                                    $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']) && !in_array($srvs['' . $srvtype . '']['' . $v->getId() . '']['order'], $acsidx))
                                    {
                                        $v->setOrder($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']);
                                        $acsidx[] = $srvs['' . $srvtype . '']['' . $v->getId() . '']['order'];
                                    }
                                    else
                                    {
                                        $maxacsindex = max($acsidx) + 1;
                                        $v->setOrder($maxacsindex);
                                        $acsidx[] = $maxacsindex;
                                    }
                                    $v->setBindingName($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']);
                                    $this->em->persist($v);
                                }
                            }
                            else
                            {

                                $ent->removeServiceLocation($v);
                            }
                            unset($srvs[$srvtype][$v->getId()]);
                        }
                    }
                    elseif ($srvtype === 'SPArtifactResolutionService')
                    {
                        log_message('debug', 'GG:SPArtifactResolutionService type found');
                        if ($type === 'IDP')
                        {
                            log_message('debug', 'GG:SPArtifactResolutionService entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                        else
                        {
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']) or empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']))
                                {
                                    $ent->removeServiceLocation($v);
                                }
                                else
                                {
                                    $v->setDefault(FALSE);
                                    $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']) && !in_array($srvs['' . $srvtype . '']['' . $v->getId() . '']['order'], $spartidx))
                                    {
                                        $v->setOrder($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']);
                                        $spartidx[] = $srvs['' . $srvtype . '']['' . $v->getId() . '']['order'];
                                    }
                                    else
                                    {
                                        $maxspartindex = max($spartidx) + 1;
                                        $v->setOrder($maxspartindex);
                                        $spartidx[] = $maxspartindex;
                                    }
                                    $v->setBindingName($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']);
                                    $this->em->persist($v);
                                }
                            }
                            else
                            {
                                $ent->removeServiceLocation($v);
                            }
                            unset($srvs[$srvtype][$v->getId()]);
                        }
                    }
                    elseif ($srvtype === 'DiscoveryResponse')
                    {
                        log_message('debug', 'GG:DiscoveryResponse type found');
                        if ($type === 'IDP')
                        {
                            log_message('debug', 'GG:DiscoveryResponse entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                        else
                        {
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']))
                            {
                                if (empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']) or empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['bind']))
                                {
                                    $ent->removeServiceLocation($v);
                                }
                                else
                                {
                                    $v->setDefault(FALSE);

                                    $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                    if (isset($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']) && !in_array($srvs['' . $srvtype . '']['' . $v->getId() . '']['order'], $acsidx))
                                    {
                                        $v->setOrder($srvs['' . $srvtype . '']['' . $v->getId() . '']['order']);
                                        $dridx[] = $srvs['' . $srvtype . '']['' . $v->getId() . '']['order'];
                                    }
                                    else
                                    {
                                        $maxdrindex = max($dridx) + 1;
                                        $v->setOrder($maxdrindex);
                                        $dridx[] = $maxdrindex;
                                    }
                                    $v->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol');
                                    $this->em->persist($v);
                                }
                            }
                            else
                            {
                                $ent->removeServiceLocation($v);
                            }
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                    }
                    elseif ($srvtype === 'RequestInitiator')
                    {
                        log_message('debug', 'GG:RequestInitiator type found');
                        if ($type === 'IDP')
                        {
                            log_message('debug', 'GG:RequestInitiator entity recognized as IDP removin service');
                            $ent->removeServiceLocation($v);
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                        else
                        {
                            if (array_key_exists($v->getId(), $srvs['' . $srvtype . '']) && !empty($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']))
                            {
                                $v->setDefault(FALSE);
                                $v->setUrl($srvs['' . $srvtype . '']['' . $v->getId() . '']['url']);
                                $v->setOrderNull();
                                $v->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:request-init');
                                $this->em->persist($v);
                            }
                            else
                            {
                                $ent->removeServiceLocation($v);
                                $this->em->remove($v);
                            }
                            unset($srvs['' . $srvtype . '']['' . $v->getId() . '']);
                        }
                    }
                }
            }

            /**
             * adding new service locations from form
             */
            foreach ($srvs as $k => $v)
            {
                if ($k === 'SingleSignOnService' && $type != 'SP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new sso');
                            if (!in_array($v1['bind'], $ssobinds))
                            {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('SingleSignOnService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $ssobinds[] = $v1['bind'];
                            }
                            else
                            {
                                log_message('error', 'SingSignOnService url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                }
                elseif ($k === 'IDPSingleLogoutService' && $type != 'SP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new IDP SingleLogout');
                            if (!in_array($v1['bind'], $idpslobinds))
                            {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('IDPSingleLogoutService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $idpslobinds[] = $v1['bind'];
                            }
                            else
                            {
                                log_message('error', 'IDP SingLogout url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                }
                elseif ($k === 'IDPAttributeService' && $type != 'SP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']) && in_array($v1['bind'], $allowedAABind))
                        {
                            log_message('debug', 'GGG new IDP IDPAttributeService');
                            if (!in_array($v1['bind'], $idpaabinds))
                            {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('IDPAttributeService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $idpaabinds[] = $v1['bind'];
                            }
                            else
                            {
                                log_message('error', 'IDP AttributeService url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                }
                elseif ($k === 'SPSingleLogoutService' && $type != 'IDP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new SP SingleLogout');
                            if (!in_array($v1['bind'], $spslobinds))
                            {
                                $newservice = new models\ServiceLocation();
                                $newservice->setBindingName($v1['bind']);
                                $newservice->setUrl($v1['url']);
                                $newservice->setType('SPSingleLogoutService');
                                $newservice->setProvider($ent);
                                $ent->setServiceLocation($newservice);
                                $this->em->persist($newservice);
                                $spslobinds[] = $v1['bind'];
                            }
                            else
                            {
                                log_message('error', 'SP SingLogout url already set for binding proto: ' . $v1['bind'] . ' for entity' . $ent->getEntityId());
                            }
                        }
                    }
                }
                elseif ($k === 'AssertionConsumerService' && $type != 'IDP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new SP AsserttionConsumerService');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('AssertionConsumerService');
                            if ($acsdefaultset)
                            {
                                $newservice->setDefault(FALSE);
                            }
                            elseif (isset($v1['default']) && $v1['default'] == 1)
                            {
                                $newservice->setDefault(TRUE);
                            }
                            else
                            {
                                $newservice->setDefault(FALSE);
                            }
                            if (isset($v1['order']) && is_numeric($v1['order']))
                            {
                                if (in_array($v1['order'], $acsidx))
                                {
                                    $maxacsindex = max($acsidx) + 1;
                                    $newservice->setOrder($maxacsindex);
                                }
                                else
                                {
                                    $newservice->setOrder($v1['order']);
                                }
                            }
                            else
                            {
                                $maxacsindex = max($acsidx) + 1;
                                $newservice->setOrder($maxacsindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                }
                elseif ($k === 'IDPArtifactResolutionService' && $type != 'SP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new  IDP ArtifactResolutionService');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('IDPArtifactResolutionService');
                            $newservice->setDefault(FALSE);
                            if (isset($v1['order']) && is_numeric($v1['order']))
                            {
                                if (in_array($v1['order'], $idpartidx))
                                {
                                    $maxidpartindex = max($idpartidx) + 1;
                                    $newservice->setOrder($maxidpartindex);
                                }
                                else
                                {
                                    $newservice->setOrder($v1['order']);
                                }
                            }
                            else
                            {
                                $maxidpartindex = max($idpartidx) + 1;
                                $newservice->setOrder($maxidpartindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                }
                elseif ($k === 'SPArtifactResolutionService' && $type != 'IDP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new SP SPArtifactResolutionService');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('SPArtifactResolutionService');
                            $newservice->setDefault(FALSE);
                            if (isset($v1['order']) && is_numeric($v1['order']))
                            {
                                if (in_array($v1['order'], $spartidx))
                                {
                                    $maxspartindex = max($spartidx) + 1;
                                    $newservice->setOrder($maxspartindex);
                                }
                                else
                                {
                                    $newservice->setOrder($v1['order']);
                                }
                            }
                            else
                            {
                                $maxspartindex = max($spartidx) + 1;
                                $newservice->setOrder($maxspartindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                }
                elseif ($k === 'DiscoveryResponse' && $type != 'IDP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
                        if (!empty($v1['bind']) && !empty($v1['url']))
                        {
                            log_message('debug', 'GGG new SP DiscoveryResponse');
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol');
                            $newservice->setUrl($v1['url']);
                            $newservice->setType('DiscoveryResponse');
                            $newservice->setDefault(FALSE);
                            if (isset($v1['order']) && is_numeric($v1['order']))
                            {
                                if (in_array($v1['order'], $dridx))
                                {
                                    $maxdrindex = max($dridx) + 1;
                                    $newservice->setOrder($maxdrindex);
                                }
                                else
                                {
                                    $newservice->setOrder($v1['order']);
                                }
                            }
                            else
                            {
                                $maxdrindex = max($dridx) + 1;
                                $newservice->setOrder($maxdrindex);
                            }
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                        }
                    }
                }
                elseif ($k === 'RequestInitiator' && $type != 'IDP')
                {
                    foreach ($srvs[$k] as $k1 => $v1)
                    {
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
        $ii = 0;
        foreach ($newsrvs as $v)
        {
            $vid = $v->getId();
            if (empty($vid))
            {
                $d = 'n' . $ii;
            }
            else
            {
                $d = $v->getId();
            }
            $newServicesInArray[$d] = '' . $v->getType() . ' ::: ' . $v->getBindingName() . ' ::: ' . $v->getUrl() . ' ::: ' . $v->getOrder() . ' ::: ' . (int) $v->getDefault() . '';
            $ii++;
        }
        $diff1 = array_diff_assoc($newServicesInArray, $origServicesInArray);
        $diff2 = array_diff_assoc($origServicesInArray, $newServicesInArray);
        if (count($diff1) > 0 || count($diff2) > 0)
        {
            $m['ServiceLocations'] = array('before' => arrayWithKeysToHtml($diff2), 'after' => arrayWithKeysToHtml($diff1));
        }
        /**
         * END update service locations
         */
        /**
         * BEGIN update certs
         */
        /**
          @todo add track
         */
        if (array_key_exists('crt', $ch) && !empty($ch['crt']) && is_array($ch['crt']))
        {
            $crts = $ch['crt'];
            $origcrts = array();
            $tmpcrt = $ent->getCertificates();
            $allowedusecase = array('signing', 'encryption', 'both');
            foreach ($tmpcrt as $v)
            {
                if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']))
                {
                    $tkeyname = false;
                    $tdata = false;
                    $crtusecase = $ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['usage'];
                    if (!empty($crtusecase) && in_array($crtusecase, $allowedusecase))
                    {
                        $v->setCertUse($crtusecase);
                    }
                    if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['keyname']))
                    {
                        if (!empty($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['keyname']))
                        {
                            $tkeyname = true;
                        }
                        $v->setKeyname($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['keyname']);
                    }
                    if (isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['certdata']))
                    {
                        if (!empty($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['certdata']))
                        {
                            $tdata = true;
                        }
                        $v->setCertData($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']['certdata']);
                    }
                    if ($tdata === false && $tkeyname === false)
                    {
                        $ent->removeCertificate($v);
                        $this->em->remove($v);
                    }
                    else
                    {
                        $this->em->persist($v);
                    }
                    unset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']);
                }
                else
                {
                    $ent->removeCertificate($v);
                    $this->em->remove($v);
                }
            }
            /**
             * setting new certs 
             */
            foreach ($ch['crt'] as $k1 => $v1)
            {
                if ($k1 === 'spsso' && $type !== 'IDP')
                {
                    foreach ($v1 as $k2 => $v2)
                    {
                        $ncert = new models\Certificate();
                        $ncert->setType('spsso');
                        $ncert->setCertType();
                        $ncert->setCertUse($v2['usage']);
                        $ent->setCertificate($ncert);
                        $ncert->setProvider($ent);
                        $ncert->setKeyname($v2['keyname']);
                        $ncert->setCertData($v2['certdata']);
                        $this->em->persist($ncert);
                    }
                }
                elseif ($k1 === 'idpsso' && $type !== 'SP')
                {
                    foreach ($v1 as $k2 => $v2)
                    {
                        $ncert = new models\Certificate();
                        $ncert->setType('idpsso');
                        $ncert->setCertType();
                        $ncert->setCertUse($v2['usage']);
                        $ent->setCertificate($ncert);
                        $ncert->setProvider($ent);
                        $ncert->setKeyname($v2['keyname']);
                        $ncert->setCertData($v2['certdata']);
                        $this->em->persist($ncert);
                    }
                }
                elseif ($k1 === 'aa' && $type !== 'SP')
                {
                    foreach ($v1 as $k2 => $v2)
                    {
                        $ncert = new models\Certificate();
                        $ncert->setType('aa');
                        $ncert->setCertType();
                        $ncert->setCertUse($v2['usage']);
                        $ent->setCertificate($ncert);
                        $ncert->setProvider($ent);
                        $ncert->setKeyname($v2['keyname']);
                        $ncert->setCertData($v2['certdata']);
                        $this->em->persist($ncert);
                    }
                }
            }
        }
        /**
         * END update certs
         */
        if (array_key_exists('contact', $ch) && is_array($ch['contact']))
        {
            $ncnt = $ch['contact'];
            $orgcnt = $ent->getContacts();
            $origcntArray = array();
            $newcntArray = array();
            foreach ($orgcnt as $v)
            {
                $i = $v->getId();
                $origcntArray[$i] = '' . $v->getType() . ' : (' . $v->getGivenname() . ' ' . $v->getSurname() . ') ' . $v->getEmail();
                if (array_key_exists($i, $ncnt))
                {
                    if (!isset($ncnt['' . $i . '']) || empty($ncnt['' . $i . '']['email']))
                    {
                        $ent->removeContact($v);
                        $this->em->remove($v);
                    }
                    else
                    {
                        $v->setType($ncnt['' . $i . '']['type']);
                        $v->setGivenname($ncnt['' . $i . '']['fname']);
                        $v->setSurname($ncnt['' . $i . '']['sname']);
                        $v->setEmail($ncnt['' . $i . '']['email']);
                        $this->em->persist($v);
                        $newcntArray['' . $i . ''] = '' . $v->getType() . ' : (' . $v->getGivenname() . ' ' . $v->getSurname() . ') ' . $v->getEmail();
                        unset($ncnt['' . $i . '']);
                    }
                }
                else
                {
                    $ent->removeContact($v);
                    $this->em->remove($v);
                }
            }
            foreach ($ncnt as $cc)
            {
                if (!empty($cc['email']) && !empty($cc['type']))
                {
                    $ncontact = new models\Contact();
                    $ncontact->setEmail($cc['email']);
                    $ncontact->setType($cc['type']);
                    $ncontact->setSurname($cc['sname']);
                    $ncontact->setGivenname($cc['fname']);
                    $ent->setContact($ncontact);
                    $ncontact->setProvider($ent);
                    $this->em->persist($ncontact);
                }
            }
            $newcnts = $ent->getContacts();
            $ii = 0;
            foreach ($newcnts as $v)
            {
                $ii++;
                $idc = $v->getId();
                if (empty($idc))
                {
                    $idc = 'n' . $ii;
                }
                $newcntArray[$idc] = '' . $v->getType() . ' : (' . $v->getGivenname() . ' ' . $v->getSurname() . ') ' . $v->getEmail();
            }
            $diff1 = array_diff_assoc($newcntArray, $origcntArray);
            $diff2 = array_diff_assoc($origcntArray, $newcntArray);
            if (count($diff1) > 0 || count($diff2) > 0)
            {
                $m['Contacts'] = array('before' => arrayWithKeysToHtml($origcntArray), 'after' => arrayWithKeysToHtml($newcntArray));
            }
        }

        /**
         * start update UII
         */
        if ($type !== 'SP')
        {
            $typeFilter = array('idp');
            $idpextend = $ent->getExtendMetadata()->filter(
                    function(models\ExtendMetadata $entry) use ($typeFilter) {
                return in_array($entry->getType(), $typeFilter);
            });




            $doFilter = array('t' => array('idp'), 'n' => array('mdui'), 'e' => array('DisplayName', 'Description', 'InformationURL'));
            $e = $ent->getExtendMetadata()->filter(
                    function(models\ExtendMetadata $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter['t']) && in_array($entry->getNamespace(), $doFilter['n']) && in_array($entry->getElement(), $doFilter['e']);
            });
            $exarray = array();
            foreach ($e as $v)
            {
                $l = $v->getAttributes();
                if (isset($l['xml:lang']))
                {
                    $exarray['' . $v->getElement() . '']['' . $l['xml:lang'] . ''] = $v;
                }
                else
                {
                    log_message('error', 'ExentedMetadata element with id:' . $v->getId() . ' doesnt contains xml:lang attr');
                }
            }
            $mduiel = array('displayname' => 'DisplayName', 'desc' => 'Description', 'helpdesk' => 'InformationURL');
            foreach ($mduiel as $elkey => $elvalue)
            {
                if (isset($ch['uii']['idpsso']['' . $elkey . '']) && is_array($ch['uii']['idpsso']['' . $elkey . '']))
                {
                    $doFilter = array('' . $elvalue . '');
                    $collection = $ent->getExtendMetadata()->filter(
                            function(models\ExtendMetadata $entry) use ($doFilter) {
                        return ($entry->getType() === 'idp') && ($entry->getNamespace() === 'mdui') && in_array($entry->getElement(), $doFilter);
                    });
                    foreach ($collection as $c)
                    {
                        $attrs = $c->getAttributes();
                        $lang = $attrs['xml:lang'];
                        if (!isset($ch['uii']['idpsso']['' . $elkey . '']['' . $lang . '']))
                        {
                            $ent->getExtendMetadata()->removeElement($c);
                            $this->em->remove($c);
                        }
                    }
                    foreach ($ch['uii']['idpsso']['' . $elkey . ''] as $key3 => $value3)
                    {

                        if (!isset($exarray['' . $elvalue . '']['' . $key3 . '']) && !empty($value3) && array_key_exists($key3, $langCodes))
                        {
                            $newelement = new models\ExtendMetadata;
                            $newelement->setParent($idpMDUIparent);
                            $newelement->setType('idp');
                            $newelement->setNamespace('mdui');
                            $newelement->setValue($value3);
                            $newelement->setElement($elvalue);
                            $newelement->setAttributes(array('xml:lang' => $key3));
                            $ent->setExtendMetadata($newelement);
                            $this->em->persist($newelement);
                        }
                        elseif (isset($exarray['' . $elvalue . '']['' . $key3 . '']))
                        {
                            if (empty($value3))
                            {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setProvider(NULL);
                                $ent->getExtendMetadata()->removeElement($exarray['' . $elvalue . '']['' . $key3 . '']);
                                $this->em->remove($exarray['' . $elvalue . '']['' . $key3 . '']);
                            }
                            else
                            {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setValue($value3);
                                $this->em->persist($exarray['' . $elvalue . '']['' . $key3 . '']);
                            }
                        }
                    }
                }
            }
            // logos not updatting value - just remove entry or add new one
            if (isset($ch['uii']['idpsso']['logo']) && is_array($ch['uii']['idpsso']['logo']))
            {
                $doFilter = array('Logo');
                $collection = $ent->getExtendMetadata()->filter(
                        function(models\ExtendMetadata $entry) use ($doFilter) {
                    return ($entry->getType() === 'idp') && ($entry->getNamespace() === 'mdui') && in_array($entry->getElement(), $doFilter);
                });

                foreach ($collection as $c)
                {
                    $attrs = $c->getAttributes();
                    $lang = @$attrs['xml:lang'];
                    $url = $c->getEvalue();

                    $width = $attrs['width'];
                    $height = $attrs['height'];
                    $size = $width . 'x' . $height;
                    $logoid = $c->getId();
                    if (empty($lang))
                    {
                        $lang = 0;
                    }

                    if (!isset($ch['uii']['idpsso']['logo']['' . $logoid . '']))
                    {
                        log_message('debug', 'PKS logo with id:' . $logoid . ' is removed');
                        $ent->getExtendMetadata()->removeElement($c);
                        $this->em->remove($c);
                    }
                    else
                    {
                        unset($ch['uii']['idpsso']['logo']['' . $logoid . '']);
                    }
                }
                foreach ($ch['uii']['idpsso']['logo'] as $ke => $ve)
                {
                    if (isset($ve['url']) && isset($ve['lang']) && isset($ve['size']))
                    {
                        $canAdd = true;
                        $nlogo = new models\ExtendMetadata;
                        $nlogo->setParent($idpMDUIparent);
                        $nlogo->setType('idp');
                        $nlogo->setNamespace('mdui');
                        $nlogo->setValue($ve['url']);
                        $nlogo->setElement('Logo');
                        $attrs = array();
                        if (strcasecmp($ve['lang'], '0') != 0)
                        {
                            $attrs['xml:lang'] = $ve['lang'];
                        }
                        $size = explode('x', $ve['size']);
                        if (count($size) == 2)
                        {
                            foreach ($size as $sv)
                            {
                                if (!is_numeric($sv))
                                {
                                    $canAdd = false;
                                    break;
                                }
                            }
                            $attrs['width'] = $size[0];
                            $attrs['height'] = $size[1];
                        }
                        else
                        {
                            $canAdd = false;
                        }
                        $nlogo->setAttributes($attrs);
                        if ($canAdd)
                        {
                            $ent->setExtendMetadata($nlogo);
                            $this->em->persist($nlogo);
                        }
                    }
                    else
                    {
                        log_message('warning', __METHOD__ . ' missing url/lang/size of new logo in form - not adding into db');
                    }
                }
            }
            else
            {
                log_message('debug', 'PKS logo array not found in session');
            }
        }
        if ($type !== 'IDP')
        {
            $typeFilter = array('sp');
            $spextend = $ent->getExtendMetadata()->filter(
                    function(models\ExtendMetadata $entry) use ($typeFilter) {
                return in_array($entry->getType(), $typeFilter);
            });
            $doFilter = array('t' => array('sp'), 'n' => array('mdui'), 'e' => array('DisplayName', 'Description', 'InformationURL'));
            $e = $ent->getExtendMetadata()->filter(
                    function(models\ExtendMetadata $entry) use ($doFilter) {
                return in_array($entry->getType(), $doFilter['t']) && in_array($entry->getNamespace(), $doFilter['n']) && in_array($entry->getElement(), $doFilter['e']);
            });
            $exarray = array();
            foreach ($e as $v)
            {
                $l = $v->getAttributes();
                if (isset($l['xml:lang']))
                {
                    $exarray['' . $v->getElement() . '']['' . $l['xml:lang'] . ''] = $v;
                }
                else
                {
                    log_message('error', 'ExentedMetadata element with id:' . $v->getId() . ' doesnt contains xml:lang attr');
                }
            }
            $mduiel = array('displayname' => 'DisplayName', 'desc' => 'Description', 'helpdesk' => 'InformationURL');
            foreach ($mduiel as $elkey => $elvalue)
            {
                if (isset($ch['uii']['spsso']['' . $elkey . '']) && is_array($ch['uii']['spsso']['' . $elkey . '']))
                {
                    $doFilter = array('' . $elvalue . '');
                    $collection = $ent->getExtendMetadata()->filter(
                            function(models\ExtendMetadata $entry) use ($doFilter) {
                        return ($entry->getType() === 'sp') && ($entry->getNamespace() === 'mdui') && in_array($entry->getElement(), $doFilter);
                    });
                    foreach ($collection as $c)
                    {
                        $attrs = $c->getAttributes();
                        $lang = $attrs['xml:lang'];
                        if (!isset($ch['uii']['spsso']['' . $elkey . '']['' . $lang . '']))
                        {
                            $ent->getExtendMetadata()->removeElement($c);
                            $this->em->remove($c);
                        }
                    }
                    foreach ($ch['uii']['spsso']['' . $elkey . ''] as $key3 => $value3)
                    {

                        if (!isset($exarray['' . $elvalue . '']['' . $key3 . '']) && !empty($value3) && array_key_exists($key3, $langCodes))
                        {
                            $newelement = new models\ExtendMetadata;
                            $newelement->setParent($spMDUIparent);
                            $newelement->setType('sp');
                            $newelement->setNamespace('mdui');
                            $newelement->setValue($value3);
                            $newelement->setElement($elvalue);
                            $newelement->setAttributes(array('xml:lang' => $key3));
                            $ent->setExtendMetadata($newelement);
                            $this->em->persist($newelement);
                        }
                        elseif (isset($exarray['' . $elvalue . '']['' . $key3 . '']))
                        {
                            if (empty($value3))
                            {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setProvider(NULL);
                                $ent->getExtendMetadata()->removeElement($exarray['' . $elvalue . '']['' . $key3 . '']);
                                $this->em->remove($exarray['' . $elvalue . '']['' . $key3 . '']);
                            }
                            else
                            {
                                $exarray['' . $elvalue . '']['' . $key3 . '']->setValue($value3);
                                $this->em->persist($exarray['' . $elvalue . '']['' . $key3 . '']);
                            }
                        }
                    }
                }
            }
            // logos not updatting value - just remove entry or add new one
            if (isset($ch['uii']['spsso']['logo']) && is_array($ch['uii']['spsso']['logo']))
            {

                $doFilter = array('Logo');
                $collection = $ent->getExtendMetadata()->filter(
                        function(models\ExtendMetadata $entry) use ($doFilter) {
                    return ($entry->getType() === 'sp') && ($entry->getNamespace() === 'mdui') && in_array($entry->getElement(), $doFilter);
                });

                foreach ($collection as $c)
                {
                    log_message('debug', 'PKS collection');
                    $attrs = $c->getAttributes();
                    $lang = @$attrs['xml:lang'];
                    $url = $c->getEvalue();

                    $width = $attrs['width'];
                    $height = $attrs['height'];
                    $size = $width . 'x' . $height;
                    $logoid = $c->getId();
                    if (empty($lang))
                    {
                        $lang = 0;
                    }

                    if (!isset($ch['uii']['spsso']['logo']['' . $logoid . '']))
                    {
                        log_message('debug', __METHOD__ . ' Logo with id:' . $logoid . ' is removed');
                        $ent->getExtendMetadata()->removeElement($c);
                        $this->em->remove($c);
                    }
                    else
                    {
                        unset($ch['uii']['spsso']['logo']['' . $logoid . '']);
                    }
                }
                foreach ($ch['uii']['spsso']['logo'] as $ke => $ve)
                {
                    if (isset($ve['url']) && isset($ve['lang']) && isset($ve['size']))
                    {
                        $canAdd = true;
                        $nlogo = new models\ExtendMetadata;
                        $nlogo->setParent($idpMDUIparent);
                        $nlogo->setType('sp');
                        $nlogo->setNamespace('mdui');
                        $nlogo->setValue($ve['url']);
                        $nlogo->setElement('Logo');
                        $attrs = array();
                        if (strcasecmp($ve['lang'], '0') != 0)
                        {
                            $attrs['xml:lang'] = $ve['lang'];
                        }
                        $size = explode('x', $ve['size']);
                        if (count($size) == 2)
                        {
                            foreach ($size as $sv)
                            {
                                if (!is_numeric($sv))
                                {
                                    $canAdd = false;
                                    break;
                                }
                            }
                            $attrs['width'] = $size[0];
                            $attrs['height'] = $size[1];
                        }
                        else
                        {
                            $canAdd = false;
                        }
                        $nlogo->setAttributes($attrs);
                        if ($canAdd)
                        {
                            $ent->setExtendMetadata($nlogo);
                            $this->em->persist($nlogo);
                        }
                    }
                    else
                    {
                        log_message('warning', __METHOD__ . ' missing url/lang/size of new logo in form - not adding into db');
                    }
                }
            }
            else
            {
                log_message('debug', 'PKS logo array not found in session');
            }
        }
        /**
         * end update UII
         */
        if (!array_key_exists('usestatic', $ch))
        {
            $ent->setStatic(false);
        }
        if (array_key_exists('static', $ch))
        {
            $exmeta = $ent->getStaticMetadata();
            if (empty($exmeta))
            {
                $exmeta = new models\StaticMetadata;
            }
            $exmeta->setMetadata($ch['static']);
            $exmeta->setProvider($ent);
            $ent->setStaticMetadata($exmeta);
            $this->em->persist($exmeta);

            $exmetaAfter = $ent->getStaticMetadata();
            if (!empty($exmetaAfter))
            {
                if (array_key_exists('usestatic', $ch) && ($ch['usestatic'] === 'accept'))
                {
                    $ent->setStatic(true);
                }
            }
        }
        if (count($m) > 0 && !empty($entid))
        {
            $this->ci->tracker->save_track('ent', 'modification', $ent->getEntityId(), serialize($m), FALSE);
        }
        return $ent;
    }

}
