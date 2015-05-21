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
    protected $srvTypes = array();

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->library('tracker');
        $this->logtracks = array();
        $this->langCodes = languagesCodes();
        $this->allowedLangCodes = array_keys($this->langCodes);
        $this->srvTypes = array(
            'idp' => array(
                'IDPSingleLogoutService',
                'SingleSignOnService',
                'IDPAttributeService',
                'IDPArtifactResolutionService'
            ),
            'sp' => array(
                'AssertionConsumerService',
                'SPArtifactResolutionService',
                'DiscoveryResponse',
                'RequestInitiator',
                'SPSingleLogoutService',

            ));
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


    private function cleanIncorrectServices(\models\Provider $ent)
    {
        $entityType = strtolower($ent->getType());
        if (strcmp($entityType, 'both') == 0) {
            return true;
        }
        /**
         * @var $services models\ServiceLocation[]
         */
        $typesToCheck = $this->srvTypes['' . $entityType . ''];
        $services = $ent->getServiceLocations();
        foreach ($services as $srv) {
            $srvType = $srv->getType();
            if (!in_array($srvType, $typesToCheck)) {
                log_message('error', __METHOD__ . ' ' . $ent->getEntityId() . ' found incorrect ServiceLocation type: "' . $srvType . '"" ... removing');
                $ent->removeServiceLocation($srv);
                $this->em->remove($srv);
            }
        }

        return true;
    }


    private function cleanIncorrectServicesInput(\models\Provider $ent, array $srvInput)
    {
        $allowed = array();
        $entityTypes = $ent->getTypesToArray();
        $inputKeys = array_keys($srvInput);
        foreach ($entityTypes as $k => $v) {
            if ($v === true) {
                $allowed = $allowed + $this->srvTypes[$k];
            }

        }
        foreach ($inputKeys as $in) {
            if (!in_array($in, $allowed)) {
                unset($srvInput['' . $in . '']);
                log_message('debug', __METHOD__ . ' unsetting: ' . $in . ' from form input');
            }
        }
        return $srvInput;
    }

    private function updateServices(\models\Provider $ent, array $ch)
    {

        if (!array_key_exists('srv', $ch) && !is_array($ch['srv'])) {
            log_message('error', __METHOD__);
            return false;
        }

        $this->cleanIncorrectServices($ent);

        $ch['srv'] = $this->cleanIncorrectServicesInput($ent, $ch['srv']);


        /**
         * @var $services models\ServiceLocation[]
         */
        $services = $ent->getServiceLocations();

        /**
         * START update service locations
         */
        $servicesByType = array();
        $srvsInput = &$ch['srv'];
        foreach ($services as $srv) {
            if (!isset($ch['srv']['' . $srv->getType() . '']['' . $srv->getId() . ''])) {
                $ent->removeServiceLocation($srv);
                continue;
            }
            $servicesByType['' . $srv->getType() . ''][$srv->getId()] = $srv;
        }

        $validationBinds = array(
            'IDPArtifactResolutionService' => array('urn:oasis:names:tc:SAML:2.0:bindings:SOAP', 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding'),
            'SPArtifactResolutionService' => array('urn:oasis:names:tc:SAML:2.0:bindings:SOAP', 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding'),
            'DiscoveryResponse' => array('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol'),
            'SPSingleLogoutService' => array('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact' => false),
            'IDPSingleLogoutService' => array('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact' => false),
            'SingleSignOnService' => array('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign' => false, 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP' => false, 'urn:mace:shibboleth:1.0:profiles:AuthnRequest' => false),
            'IDPAttributeService' => array('urn:oasis:names:tc:SAML:2.0:bindings:SOAP' => false, 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding' => false),
            'AssertionConsumerService' => array(
                'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
                'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
                'urn:oasis:names:tc:SAML:2.0:profiles:browser-post',
                'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
                'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01'
            ),
        );
        $servicesIndexes = array(
            'AssertionConsumerService' => array(),
            'IDPArtifactResolutionService' => array(),
            'SPArtifactResolutionService' => array(),
            'DiscoveryResponse' => array()
        );
        $acsdefaultset = false;
        $c = 20;
        foreach ($services as $srv) {
            $srvType = $srv->getType();
            $inputSrv = &$ch['srv']['' . $srvType . '']['' . $srv->getId() . ''];
            $inputBind = null;
            $inputUrl = null;
            $inputOrder = null;
            $inputDefault = null;

            if (array_key_exists('bind', $inputSrv)) {
                $inputBind = $inputSrv['bind'];
            }
            if (array_key_exists('url', $inputSrv)) {
                $inputUrl = $inputSrv['url'];
            }
            if (array_key_exists('order', $inputSrv)) {
                $inputOrder = $inputSrv['order'];
            }
            if (array_key_exists('default', $inputSrv)) {
                $inputDefault = $inputSrv['default'];
            }
            if (empty($inputUrl) || (empty($inputBind) && $srvType !== 'RequestInitiator')) {
                unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                $ent->removeServiceLocation($srv);
                continue;
            }
            if ($srvType === 'RequestInitiator') {
                $srv->setUrl($inputUrl);
                $this->em->persist($srv);
                unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                continue;
            }

            if (in_array($srvType, array('IDPArtifactResolutionService', 'SPArtifactResolutionService', 'DiscoveryResponse'))) {
                if (in_array($inputBind, $validationBinds['' . $srvType . ''])) {
                    if (in_array($inputOrder, $servicesIndexes['' . $srvType . '']) || is_null($inputOrder)) {
                        $inputOrder = $c++;
                    }
                    $srv->setUrl($inputUrl);
                    $srv->setBindingName($inputBind);
                    $srv->setOrder($inputOrder);
                    $this->em->persist($srv);
                    $servicesIndexes['' . $srvType . ''][] = $inputOrder;
                    unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);

                } else {
                    unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                    $ent->removeServiceLocation($srv);
                }

                continue;
            }
            if (in_array($srvType, array('SPSingleLogoutService', 'IDPSingleLogoutService', 'SingleSignOnService', 'IDPAttributeService'))) {
                if (array_key_exists($inputBind, $validationBinds['' . $srvType . ''])) {
                    if ($validationBinds['' . $srvType . '']['' . $inputBind . ''] === true) {
                        unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                        $ent->removeServiceLocation($srv);
                        continue;
                    }
                    $srv->setUrl($inputUrl);
                    $srv->setBindingName($inputBind);;
                    $this->em->persist($srv);
                    $validationBinds['' . $srvType . '']['' . $inputBind . ''] = true;
                    unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);

                } else {
                    unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                    $ent->removeServiceLocation($srv);
                }
                continue;
            }
            if ($srvType === 'AssertionConsumerService') {
                if (in_array($inputBind, $validationBinds['' . $srvType . ''])) {
                    if (in_array($inputOrder, $servicesIndexes['' . $srvType . '']) || is_null($inputOrder)) {
                        $inputOrder = $c++;
                    }
                    $srv->setUrl($inputUrl);
                    $srv->setBindingName($inputBind);
                    $srv->setOrder($inputOrder);
                    if (strcasecmp($inputDefault, '1') == 0 && $acsdefaultset !== true) {
                        $srv->setDefault(true);
                        $acsdefaultset = true;
                    } else {
                        $srv->setDefault(false);
                    }
                    $this->em->persist($srv);
                    $servicesIndexes['' . $srvType . ''][] = $inputOrder;
                    unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                } else {
                    unset($ch['srv']['' . $srvType . '']['' . $srv->getId() . '']);
                    $ent->removeServiceLocation($srv);
                }
                continue;
            }


        }

        foreach ($srvsInput as $srvType => $v) {
            if (in_array($srvType, array('SPSingleLogoutService', 'IDPSingleLogoutService', 'SingleSignOnService', 'IDPAttributeService'))) {
                foreach ($v as $k1 => $v1) {
                    if (!empty($v1['bind']) && !empty($v1['url'])) {
                        if (array_key_exists($v1['bind'], $validationBinds['' . $srvType . '']) && $validationBinds['' . $srvType . '']['' . $v1['bind'] . ''] !== true) {
                            $newservice = new models\ServiceLocation();
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType($srvType);
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                            $validationBinds['' . $srvType . '']['' . $v1['bind'] . ''] = true;
                            continue;
                        }
                    }
                }
            } elseif (in_array($srvType, array('IDPArtifactResolutionService', 'SPArtifactResolutionService', 'DiscoveryResponse'))) {
                foreach ($v as $k1 => $v1) {
                    if (!empty($v1['bind']) && !empty($v1['url'])) {
                        if (in_array($v1['bind'], $validationBinds['' . $srvType . ''])) {
                            $newservice = new models\ServiceLocation();
                            if (array_key_exists('order', $v1) && is_numeric($v1['order']) || !in_array($v1['order'], $servicesIndexes['' . $srvType . ''])) {
                                $newservice->setOrder($v1['order']);
                            } else {
                                $newservice->setOrder($c++);
                            }
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType($srvType);
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                            $servicesIndexes['' . $srvType . ''][] = $newservice->getOrder();
                            continue;
                        }
                    }
                }
            } elseif ($srvType === 'RequestInitiator') {
                foreach ($v as $k1 => $v1) {
                    if (!empty($v1['url'])) {
                        $newservice = new models\ServiceLocation();
                        $newservice->setBindingName('urn:oasis:names:tc:SAML:profiles:SSO:request-init');
                        $newservice->setUrl($v1['url']);
                        $newservice->setType($srvType);
                        $newservice->setProvider($ent);
                        $newservice->setDefault(FALSE);
                        $newservice->setOrderNull();
                        $ent->setServiceLocation($newservice);
                        $this->em->persist($newservice);
                        continue;

                    }
                }
            } elseif ($srvType === 'AssertionConsumerService') {
                foreach ($v as $k1 => $v1) {
                    if (!empty($v1['bind']) && !empty($v1['url'])) {
                        if (in_array($v1['bind'], $validationBinds['' . $srvType . ''])) {
                            $newservice = new models\ServiceLocation();
                            if (array_key_exists('order', $v1) && is_numeric($v1['order']) || !in_array($v1['order'], $servicesIndexes['' . $srvType . ''])) {
                                $newservice->setOrder($v1['order']);
                            } else {
                                $newservice->setOrder($c++);
                            }
                            if (array_key_exists('default', $v1) && strcmp($v1['default'], '1') == 0 && $acsdefaultset !== true) {
                                $newservice->setDefault(true);
                                $acsdefaultset = true;
                            }
                            $newservice->setBindingName($v1['bind']);
                            $newservice->setUrl($v1['url']);
                            $newservice->setType($srvType);
                            $newservice->setProvider($ent);
                            $ent->setServiceLocation($newservice);
                            $this->em->persist($newservice);
                            $servicesIndexes['' . $srvType . ''][] = $newservice->getOrder();
                            continue;
                        }
                    }
                }
            }
        }
        return true;
    }

    private function updateCerts(\models\Provider $ent, array $ch)
    {
        if (!array_key_exists('crt', $ch) || empty($ch['crt']) || !is_array($ch['crt'])) {
            return false;
        }
        $changes = array('before' => array(), 'after' => array());
        /**
         * @var $origCertificates models\Certificate[]
         */
        $origCertificates = $ent->getCertificates();
        foreach ($origCertificates as $v) {
            $changes['before'][] = $v->getType() . ':' . $v->getCertUseInStr() . ':' . $v->getFingerprint();
            if (!isset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . ''])) {
                $ent->removeCertificate($v);
                $this->em->remove($v);
                continue;
            }
            $curCrt = $ch['crt']['' . $v->getType() . '']['' . $v->getId() . ''];
            $tkeyname = false;
            $tdata = false;
            if (isset($curCrt['usage'])) {
                $v->setCertUse($curCrt['usage']);
            }
            if (isset($curCrt['keyname'])) {
                if (!empty($curCrt['keyname'])) {
                    $tkeyname = true;
                }
                $v->setKeyname($curCrt['keyname']);
            }
            if (isset($curCrt['certdata'])) {
                if (!empty($curCrt['certdata'])) {
                    $tdata = true;
                }
                $v->setCertData($curCrt['certdata']);
            }
            if (isset($curCrt['encmethods'])) {
                if (!empty($curCrt['encmethods']) && is_array($curCrt['encmethods'])) {
                    $v->setEncryptMethods(array_filter($curCrt['encmethods']));
                } else {
                    $v->setEncryptMethods(null);
                }
            }
            if ($tdata === false && $tkeyname === false) {
                $ent->removeCertificate($v);
                $this->em->remove($v);
            } else {
                $this->em->persist($v);
                $changes['after'][] = $v->getType() . ':' . $v->getCertUseInStr() . ':' . $v->getFingerprint();
            }
            unset($ch['crt']['' . $v->getType() . '']['' . $v->getId() . '']);

        }
        /**
         * setting new certs
         */
        if ($this->entityTypes['sp'] !== true) {
            unset($ch['crt']['spsso']);
        }
        if ($this->entityTypes['idp'] !== true) {
            unset($ch['crt']['idpsso']);
            unset($ch['crt']['aa']);
        }
        foreach ($ch['crt'] as $k1 => $v1) {
            if (!in_array($k1, array('spsso', 'idpsso', 'aa'))) {
                continue;
            }
            foreach ($v1 as $k2 => $v2) {
                $ncert = new models\Certificate();
                $ncert->setType($k1);
                $ncert->setCertType();
                $ncert->setCertUse($v2['usage']);
                $ncert->setKeyname($v2['keyname']);
                $ncert->setCertData($v2['certdata']);
                if (isset($v2['encmethods'])) {
                    $ncert->setEncryptMethods($v2['encmethods']);
                }
                $ent->setCertificate($ncert);
                $ncert->setProvider($ent);
                $this->em->persist($ncert);
                $changes['after'][] = $ncert->getType() . ':' . $ncert->getCertUseInStr() . ':' . $ncert->getFingerprint();
            }
        }
        $diff1 = array_diff_assoc($changes['before'], $changes['after']);
        $diff2 = array_diff_assoc($changes['after'], $changes['before']);
        if (count($diff1) > 0 || count($diff2) > 0) {
            $this->updateChanges('certs', arrayWithKeysToHtml($changes['before']), arrayWithKeysToHtml($changes['after']));
        }
        return true;

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
        $requestNew = true;
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
        $entityTypes = $ent->getTypesToArray();
        $this->entityTypes = $entityTypes;
        $changeList = array();
        $type = $ent->getType();

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
                    $trackAfter = array();
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


        $this->updateServices($ent, $ch);
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
