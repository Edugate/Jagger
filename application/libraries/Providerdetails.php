<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   JAGGER
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Providerdetails
{
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $CI;

    /**
     * @var models\Provider $ent
     */
    protected $ent;
    protected $idppart = true;
    protected $sppart = true;
    protected $presubtitle;
    protected $entmenu = array();

    public function __construct(array $args) {
        $this->CI = &get_instance();
        $this->em = $this->CI->doctrine->em;
        if (!array_key_exists('ent', $args) || !($args['ent'] instanceof models\Provider)) {
            throw new Exception('models\Provider instance must be passed to library');
        }
        $this->ent = $args['ent'];
        $type = $this->ent->getType();
        if ($type === 'SP') {
            $this->idppart = false;
            MY_Controller::$menuactive = 'sps';
            $this->presubtitle = lang('serviceprovider');
        } elseif ($type === 'IDP') {
            $this->sppart = false;
            MY_Controller::$menuactive = 'idps';
            $this->presubtitle = lang('identityprovider');

        } else {
            $this->presubtitle = lang('rr_asboth');
        }


    }

    private function genCertView(models\Certificate $cert) {
        $certusage = $cert->getCertUse();
        if ($certusage === 'signing') {
            $langcertusage = lang('certsign');
        } elseif ($certusage === 'encryption') {
            $langcertusage = lang('certenc');
        } else {
            $langcertusage = lang('certsign') . '/' . lang('certenc');
        }
        $d = array(
            array('header' => lang('rr_certificate')),
            array('name' => lang('rr_certusage'), 'value' => $langcertusage));
        $keyname = $cert->getKeyname();
        if (!empty($keyname)) {
            $d[] = array('name' => lang('rr_keyname'), 'value' => $keyname);
        }
        $certData = $cert->getCertData();
        if (!empty($certData)) {
            $certtype = $cert->getCertType();
            if ($certtype === 'X509Certificate') {
                $certValid = validateX509($certData);
                if ($certValid) {
                    $pemdata = $cert->getPEM($cert->getCertData());
                    array_push($d,
                        array('name' => lang('rr_keysize'), 'value' => '' . getKeysize($pemdata) . ''),
                        array('name' => lang('rr_fingerprint') . ' (md5)', 'value' => '' . generateFingerprint($certData, 'md5') . ''),
                        array('name' => lang('rr_fingerprint') . ' (sha1)', 'value' => '' . generateFingerprint($certData, 'sha1') . ''),
                        array(
                            'name' => '',
                            'value' => '<ul class="accordion" data-accordion data-allow-all-closed="true"><li class="accordion-item" data-accordion-item><a href="#c' . $cert->getId() . '" class="accordion-title">' . lang('rr_certbody') . '</a><div class="accordion-content" data-tab-content><code id="c' . $cert->getId() . '" class="content">' . trim($certData) . '</code></div></li></ul>'
                        ));
                }
            }
        }
        $encryptMethods = $cert->getEncryptMethods();
        if (count($encryptMethods) > 0) {
            $d[] = array('name' => 'EncryptionMethods', 'value' => implode('<br />', $encryptMethods));
        }

        return $d;
    }

    private function genCertTab() {
        $result = array();
        $tcerts = $this->ent->getCertificates();
        $certs = array('idpsso' => array(), 'aa' => array(), 'spsso' => array());
        foreach ($tcerts as $c) {
            $certs[$c->getType()][] = $c;
        }
        if ($this->idppart) {
            $result[]['msection'] = 'IDPSSODescriptor';
            foreach ($certs['idpsso'] as $v1) {
                $c = $this->genCertView($v1);
                foreach ($c as $v2) {
                    $result[] = $v2;
                }
            }
            // AA
            $result[]['msection'] = 'AttributeAuthorityDescriptor';
            foreach ($certs['aa'] as $v1) {
                $c = $this->genCertView($v1);
                foreach ($c as $v2) {
                    $result[] = $v2;
                }
            }
        }
        if ($this->sppart) {
            $result[]['msection'] = 'SPSSODescriptor';
            foreach ($certs['spsso'] as $v1) {
                $c = $this->genCertView($v1);
                foreach ($c as $v2) {
                    $result[] = $v2;
                }
            }
        }

        return $result;
    }

    private function genFedView(\models\Provider $ent) {
        $lockicon = genIcon('locked');
        $id = $ent->getId();
        $hasWriteAccess = $this->CI->zacl->check_acl($id, 'write', 'entity', '');
        $hasManageAccess = $this->CI->zacl->check_acl($id, 'manage', 'entity', '');
        $isAdmin = $this->CI->jauth->isAdministrator();
        $isLocked = $ent->getLocked();
        $isLocal = $ent->getLocal();
        $sppart = $this->sppart;
        $feathide = (array)$this->CI->config->item('feathide');
        $featdisable = (array)$this->CI->config->item('featdisable');
        $manageMembershipButtons = array();

        $srv_metalink = base_url('metadata/service/' . base64url_encode($ent->getEntityId()) . '/metadata.xml');

        $disable_extcirclemeta = $this->CI->config->item('disable_extcirclemeta');

        $i = 1;
        if (!(isset($feathide['metasonprov']) && $feathide['metasonprov'] === true)) {
            $d[++$i]['header'] = lang('rr_metadata');
            $d[++$i]['name'] = '<a name="metadata"></a>' . lang('rr_servicemetadataurl');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . ':</span> <span class="accordionContent"><br />' . $srv_metalink . '&nbsp;</span>&nbsp; ' . anchor($srv_metalink, '<i class="fa fa-arrow-right"></i>', '');
        }
        $circleEnabled = !((isset($featdisable['circlemeta']) && $featdisable['circlemeta'] === true) || (isset($feathide['circlemeta']) && $feathide['circlemeta'] === true));

        if ($circleEnabled) {

            if (!$isLocal && !empty($disable_extcirclemeta) && $disable_extcirclemeta === true) {
                $d[++$i]['name'] = lang('rr_circleoftrust');
                $d[$i]['value'] = lang('disableexternalcirclemeta');
                $d[++$i]['name'] = lang('rr_circleoftrust') . '<i>(' . lang('signed') . ')</i>';
                $d[$i]['value'] = lang('disableexternalcirclemeta');
            } else {
                $srvCircleMetalink = base_url() . 'metadata/circle/' . base64url_encode($ent->getEntityId()) . '/metadata.xml';
                $srvCircleMetalinkSigned = base_url() . 'signedmetadata/provider/' . base64url_encode($ent->getEntityId()) . '/metadata.xml';

                $d[++$i]['name'] = lang('rr_circleoftrust');
                $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . ':</span> <span class="accordionContent"><br />' . $srvCircleMetalink . '&nbsp;</span>&nbsp; ' . anchor($srvCircleMetalink, '<i class="fa fa-arrow-right"></i>', 'class=""');
                $d[++$i]['name'] = lang('rr_circleoftrust') . '<i>(' . lang('signed') . ')</i>';
                $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . ':</span> <span class="accordionContent"><br />' . $srvCircleMetalinkSigned . '&nbsp;</span>&nbsp; ' . anchor_popup($srvCircleMetalinkSigned, '<i class="fa fa-arrow-right"></i>');
            }
        }
        $isMQEnabled = $this->CI->mq->isClientEnabled();
        if ($isLocal && $hasWriteAccess && $isMQEnabled && $circleEnabled) {
            $d[++$i]['name'] = lang('signmetadata') . showBubbleHelp(lang('rhelp_signmetadata'));
            $d[$i]['value'] = '<a href="' . base_url() . 'msigner/signer/provider/' . $ent->getId() . '" id="providermetasigner" class="button">' . lang('btn_signmetadata') . '</a>';
        }
        $wayfhide = false;

        if ((isset($feathide['discojuice']) && $feathide['discojuice'] === true) || (isset($featdisable['discojuice']) && $featdisable['discojuice'] === true)) {
            $wayfhide = true;
        }
        if ($sppart && !$wayfhide) {
            $d[++$i]['header'] = 'WAYF';
            $d[++$i]['name'] = lang('rr_ds_json_url') . ' <div class="dhelp">' . lang('entdswayf') . '</div>';

            $d[$i]['value'] = anchor(base_url() . 'disco/circle/' . base64url_encode($ent->getEntityId()) . '/metadata.json?callback=dj_md_1', lang('rr_link'));

            $tmpwayflist = $ent->getWayfList();
            if (!empty($tmpwayflist) && is_array($tmpwayflist)) {
                if (isset($tmpwayflist['white'])) {
                    if (is_array($tmpwayflist['white'])) {
                        $discolist = implode('<br />', array_values($tmpwayflist['white']));
                        $d[++$i]['name'] = lang('rr_ds_white');
                        $d[$i]['value'] = $discolist;
                    }
                } elseif (isset($tmpwayflist['black']) && is_array($tmpwayflist['black']) && count($tmpwayflist['black']) > 0) {
                    $discolist = implode('<br />', array_values($tmpwayflist['black']));
                    $d[++$i]['name'] = lang('rr_ds_black');
                    $d[$i]['value'] = $discolist;
                }
            }
        }
        /**
         * Federation
         */
        $d[++$i]['name'] = lang('rr_memberof');
        $federationsString = '';
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $no_feds = 0;
        /**
         * @var models\FederationMembers[] $membership
         */
        $membership = $ent->getMembership();
        $membershipNotLeft = array();
        $showMetalinks = true;

        $manage_membership = '';
        if (isset($feathide['metasonprov']) && $feathide['metasonprov'] === true) {
            $showMetalinks = false;
        }
        if (!empty($membership)) {
            $federationsString = '<ul class="no-bullet">';
            foreach ($membership as $f) {
                $mngmtBtns = array();
                $joinstate = $f->getJoinState();
                if ($joinstate === 2) {
                    continue;
                }
                $membershipNotLeft[] = 1;
                $membershipDisabled = '';
                if ($f->isDisabled()) {
                    $membershipDisabled = makeLabel('disabled', lang('membership_inactive'), lang('membership_inactive'));

                    if ($hasManageAccess) {
                        $valTmp = $ent->getId() . '|' . $f->getFederation()->getId() . '|dis|0';
                        $mngmtBtns[] = '<button data-jagger-desc="Reactivate membership" class="button small revealc" value="' . $valTmp . '">'.lang('btntmpactmemb').'</button>';
                    }
                } else if ($hasManageAccess) {
                    $valTmp = $ent->getId() . '|' . $f->getFederation()->getId() . '|dis|1';
                    $mngmtBtns[] = '<button data-jagger-desc="Temporary suspend membership without leaving federation" class="button alert small revealc " value="' . $valTmp . '">'.lang('btntmpsuspendmemb').'</button>';
                }
                $membershipBanned = '';
                if ($f->isBanned()) {
                    if ($isAdmin) {
                        $valTmp = $ent->getId() . '|' . $f->getFederation()->getId() . '|ban|0';
                        $mngmtBtns[] = '<button data-jagger-desc="Reactivate membership" class="button small revealc" value="' . $valTmp . '">'.lang('btnadmactmemb').'</button>';
                    }
                    $membershipBanned = makeLabel('disabled', lang('membership_banned'), lang('membership_banned'));
                } else if ($isAdmin) {
                    $valTmp = $ent->getId() . '|' . $f->getFederation()->getId() . '|ban|1';
                    $mngmtBtns[] = '<button data-jagger-desc="Administravely suspend membership without leaving federation" class="button small revealc alert" value="' . $valTmp . '">'.lang('btnadmsuspendmemb').'</button>';
                }
                $fedActive = $f->getFederation()->getActive();

                $fedlink = base_url('federations/manage/show/' . base64url_encode($f->getFederation()->getName()));

                $federationsString .= '<li>';
                if ($showMetalinks) {
                    $metalink = base_url('metadata/federation/' . $f->getFederation()->getSysname() . '/metadata.xml');
                    if ($fedActive) {
                        $federationsString .= $membershipDisabled . '  ' . $membershipBanned . ' ' . anchor($fedlink, html_escape($f->getFederation()->getName())) . ' <span class="accordionButton">' . lang('rr_metadataurl') . ': </span><span class="accordionContent"><br /><i>' . $metalink . '</i>&nbsp;</span> &nbsp;&nbsp;' . anchor($metalink, '<i class="fa fa-arrow-right"></i>', 'class=""') ;
                    } else {
                        $federationsString .= $membershipDisabled . ' ' . $membershipBanned . ' ' . makeLabel('disabled', lang('rr_fed_inactive_full'), lang('rr_fed_inactive_full')) . ' ' . anchor($fedlink, $f->getFederation()->getName()) . ' <span class="accordionButton">' . lang('rr_metadataurl') . ': </span><span class="accordionContent"><br /><i>' . $metalink . '</i>&nbsp;</span> &nbsp;&nbsp;' . anchor($metalink, '<i class="fa fa-arrow-right"></i>', 'class=""') ;
                    }
                } else {
                    if ($fedActive) {
                        $federationsString .= $membershipDisabled . '  ' . $membershipBanned . ' ' . anchor($fedlink, html_escape($f->getFederation()->getName()));
                    } else {
                        $federationsString .= $membershipDisabled . ' ' . $membershipBanned . ' ' . makeLabel('disabled', lang('rr_fed_inactive_full'), lang('rr_fed_inactive_full')) . ' ' . anchor($fedlink, $f->getFederation()->getName());
                    }
                }
                $federationsString .= '<br />'.implode(' ', $mngmtBtns);
                $federationsString .='</li>';

            }
            $federationsString .= '</ul>';

            $no_feds = $membership->count();
            if ($no_feds > 0 && $hasWriteAccess) {
                if (!$isLocked) {
                    $manageMembershipButtons[] = '<a href="' . base_url('manage/leavefed/leavefederation/' . $ent->getId() . '').'" class="button alert">' . lang('rr_leave') . '</a>';
                    $this->entmenu[11] = array('name' => lang('rr_federationleave'), 'link' => '' . base_url() . 'manage/leavefed/leavefederation/' . $ent->getId() . '', 'class' => '');
                } else {
                    $manage_membership .= '<b>' . lang('rr_federationleave') . '</b> ' . $lockicon . ' <br />';
                }
            }
            if ($hasWriteAccess && (count($membershipNotLeft) < count($all_federations))) {
                if (!$isLocked) {
                    $manageMembershipButtons[] = '<a href="' . base_url('manage/joinfed/joinfederation/' . $ent->getId() . '').'" class="button">' . lang('rr_join') . '</a>';
                    $this->entmenu[10] = array('name' => lang('rr_federationjoin'), 'link' => '' . base_url() . 'manage/joinfed/joinfederation/' . $ent->getId() . '', 'class' => '');
                } else {
                    $manage_membership .= '<b>' . lang('rr_federationjoin') . '</b> ' . $lockicon . '<br />';
                }
            }
        }
        $d[$i]['value'] = '<p>' . $federationsString . '</p><p>' . $manage_membership . '</p>';
        if ($no_feds > 0) {
            $d[++$i]['name'] = '';
            $d[$i]['value'] = '<a href="' . base_url() . 'providers/detail/showmembers/' . $id . '" id="getmembers"><button type="button" class="button secondary arrowdownicon ">' . lang('showmemb_btn') . '</button></a>';

            $d[++$i]['2cols'] = '<div id="membership"></div>';
        }

        $d[0]['2cols'] = revealBtnsRow($manageMembershipButtons);
        ksort($d);
        return $d;
    }

    private function makeStatusLabels() {
        $ent = $this->ent;
        $entStatus = '';
        $isValidTime = $ent->isValidFromTo();
        $isActive = $ent->getActive();
        $isLocal = $ent->getLocal();
        $isPublicListed = $ent->getPublicVisible();
        $isLocked = $ent->getLocked();
        $isStatic = $ent->getStatic();

        if (empty($isPublicListed)) {
            $entStatus .= ' ' . makeLabel('disabled', lang('lbl_publichidden'), lang('lbl_publichidden'));
        }
        if (empty($isActive)) {
            $entStatus .= ' ' . makeLabel('disabled', lang('lbl_disabled'), lang('lbl_disabled'));
        } else {
            $entStatus .= ' ' . makeLabel('active', lang('lbl_enabled'), lang('lbl_enabled'));
        }
        if (!$isValidTime) {
            $entStatus .= ' ' . makeLabel('alert', lang('rr_validfromto_notmatched1'), strtolower(lang('rr_metadata')) . ' ' . lang('rr_expired'));
        }
        if ($isLocked) {
            $entStatus .= ' ' . makeLabel('locked', lang('rr_locked'), lang('rr_locked'));
        }
        if ($isLocal) {
            $entStatus .= ' ' . makeLabel('local', lang('rr_managedlocally'), lang('rr_managedlocally'));
        } else {
            $entStatus .= ' ' . makeLabel('local', lang('rr_external'), lang('rr_external'));
        }
        if ($isStatic) {
            $entStatus .= ' ' . makeLabel('static', lang('lbl_static'), lang('lbl_static'));
        }

        return $entStatus;
    }

    private function genOrgTab() {

        $d = array();
        $iCounter = 0;
        $d[++$iCounter]['name'] = lang('e_orgname');
        $lname = $this->ent->getMergedLocalName();
        $lvalues = '';
        if (count($lname) > 0) {
            foreach ($lname as $k => $v) {
                $lvalues .= '<b>' . $k . ':</b> ' . html_escape($v) . '<br />';
            }
            $d[$iCounter]['value'] = $lvalues;
        } else {
            $d[$iCounter]['value'] = '<span class="label alert">' . lang('rr_notset') . '</span>';
        }
        $d[++$iCounter]['name'] = lang('e_orgdisplayname');
        $ldisplayname = $this->ent->getMergedLocalDisplayName();
        $lvalues = '';
        if (count($ldisplayname) > 0) {
            foreach ($ldisplayname as $k => $v) {
                $lvalues .= '<b>' . $k . ':</b> ' . html_escape($v) . '<br />';
            }
            $d[$iCounter]['value'] = '<div>' . $lvalues . '</div>';
        } else {
            $d[$iCounter]['value'] = '<span class="label alert">' . lang('rr_notset') . '</span>';
        }
        $d[++$iCounter]['name'] = lang('e_orgurl');
        $localizedHelpdesk = $this->ent->getHelpdeskUrlLocalized();
        if (is_array($localizedHelpdesk) && count($localizedHelpdesk) > 0) {
            $lvalues = '';
            foreach ($localizedHelpdesk as $k => $v) {
                $lvalues .= '<div><b>' . $k . ':</b> ' . html_escape($v) . '</div>';
            }
            $d[$iCounter]['value'] = $lvalues;
        } else {
            $d[$iCounter]['value'] = '<span class="label alert">' . lang('rr_notset') . '</span>';
        }

        return $d;


    }

    private function genContactsTab() {
        $result = array();
        $contacts = $this->ent->getContacts();
        $typesInLang = array(
            'technical' => lang('rr_cnt_type_tech'),
            'administrative' => lang('rr_cnt_type_admin'),
            'support' => lang('rr_cnt_type_support'),
            'billing' => lang('rr_cnt_type_bill'),
            'other' => lang('rr_cnt_type_other'),
            'other-sirfti' => lang('rr_cnt_type_other-sirfti'),
        );
        if (count($contacts) > 0) {
            foreach ($contacts as $c) {
                $part = array(
                    array('header' => lang('rr_contact')),
                    array('name' => lang('type'), 'value' => $typesInLang['' . strtolower($c->getTypeToForm()) . '']),
                    array('name' => lang('rr_contactfirstname'), 'value' => html_escape($c->getGivenname())),
                    array('name' => lang('rr_contactlastname'), 'value' => html_escape($c->getSurname())),
                    array('name' => lang('rr_contactemail'), 'value' => '<span data-jagger-contactmail="' . html_escape($c->getEmail()) . '">' . html_escape($c->getEmail()) . '</span>'),
                );
                $result = array_merge($result, $part);
            }
        } else {
            $result[]['2cols'] = '<div  class="alert-box warning">' . lang('rr_notset') . '</div>';
        }

        return $result;
    }


    public function generateForControllerProvidersDetail() {

        $ent = $this->ent;

        $alerts = array();


        $isStatic = $ent->getStatic();

        $sppart = $this->sppart;
        $idppart = $this->idppart;
        $type = strtolower($ent->getType());
        $edit_attributes = '';

        $data['type'] = $type;
        $data['presubtitle'] = $this->presubtitle;

        $id = $ent->getId();
        $hasWriteAccess = $this->CI->zacl->check_acl($id, 'write', 'entity', '');
        $hasManageAccess = $this->CI->zacl->check_acl($id, 'manage', 'entity', '');
        // off canvas menu for provider

        $editLink = '';

        if ($isStatic) {

            $alerts[] = lang('staticmeta_info');
        }
        $isActive = $ent->getActive();
        $isValidTime = $ent->isValidFromTo();
        $isLocal = $ent->getLocal();
        $isLocked = $ent->getLocked();
        if (!$hasWriteAccess) {
            $editLink .= makeLabel('noperm', lang('rr_nopermission'), lang('rr_nopermission'));
            $this->entmenu[0] = array('name' => '' . lang('rr_nopermission') . '', 'link' => '#', 'class' => 'alert');
        } elseif (!$isLocal) {
            $editLink .= makeLabel('external', lang('rr_externalentity'), lang('rr_external'));
            $this->entmenu[0] = array('name' => '' . lang('rr_externalentity') . '', 'link' => '#', 'class' => 'alert');
        } elseif ($isLocked) {
            $editLink .= makeLabel('locked', lang('rr_lockedentity'), lang('rr_lockedentity'));
            $this->entmenu[0] = array('name' => '' . lang('rr_lockedentity') . '', 'link' => '#', 'class' => 'alert');
        } else {
            $editLink .= '<a href="' . base_url() . 'manage/entityedit/show/' . $id . '" class="button" id="editprovider" title="edit" >' . lang('rr_edit') . '</a>';
            $this->entmenu[0] = array('name' => '' . lang('rr_editentity') . '', 'link' => '' . base_url() . 'manage/entityedit/show/' . $id . '', 'class' => '');
            $data['showclearcache'] = true;
        }
        $data['edit_link'] = $editLink;

        $extend = $ent->getExtendMetadata();
        /**
         * get first assinged logo to display on site
         */
        $isLogo = false;
        foreach ($extend as $v) {
            if ($isLogo) {
                break;
            }
            if ($v->getElement() === 'Logo') {
                $providerlogourl = $v->getLogoValue();
                $isLogo = true;
            }
        }
        if (!empty($providerlogourl)) {
            $data['providerlogourl'] = $providerlogourl;
        }
        $data['entid'] = $ent->getId();
        $guiLang = MY_Controller::getLang();
        $data['name'] = $ent->getNameToWebInLang($guiLang, $type);
        $this->title = lang('rr_providerdetails') . ' :: ' . $data['name'];
        $b = $this->CI->session->userdata('board');
        if (is_array($b)) {
            if (($type === 'idp' || $type === 'both') && isset($b['idp'][$id])) {
                $data['bookmarked'] = true;
            } elseif (($type === 'sp' || $type === 'both') && isset($b['sp'][$id])) {
                $data['bookmarked'] = true;
            }
        }


        /**
         * BASIC
         */
        $d = array();
        $i = 0;
        $d[++$i]['name'] = lang('rr_status') . ' ' . showBubbleHelp('' . lang('lbl_enabled') . ': '. lang('provinmeta') . '; ' .
                lang('lbl_disabled') .': ' . lang('provexclmeta') . '; ' .
                lang('rr_managedlocally') . ': ' .lang('provmanlocal') . '; ' .
                lang('rr_external') . ': ' . lang('provexternal') . '') . '';
        $entStatus = $this->makeStatusLabels();
        $d[$i]['value'] = '<b>' . $entStatus . '</b>';
        $d[++$i]['name'] = lang('rr_lastmodification');
        $d[$i]['value'] = '<b>' . jaggerDisplayDateTimeByOffset($ent->getLastModified(), jauth::$timeOffset) . '</b>';
        $entityIdRecord = array('name' => lang('rr_entityid'), 'value' => $ent->getEntityId());
        $d[++$i] = &$entityIdRecord;


        $d[++$i]['name'] = lang('e_orgname');
        $lname = $ent->getMergedLocalName();
        $lvalues = '';
        foreach ($lname as $k => $v) {
            $lvalues .= '<b>' . $k . ':</b> ' . html_escape($v) . '<br />';
        }
        $d[$i]['value'] = $lvalues;
        $d[++$i]['name'] = lang('e_orgdisplayname');
        $ldisplayname = $ent->getMergedLocalDisplayName();
        $lvalues = '';
        if (count($ldisplayname) > 0) {
            foreach ($ldisplayname as $k => $v) {
                $lvalues .= '<b>' . $k . ':</b> ' . html_escape($v) . '<br />';
            }
        }
        $d[$i]['value'] = '<div id="selectme">' . $lvalues . '</div>';
        $d[++$i]['name'] = lang('e_orgurl');
        $localizedHelpdesk = $ent->getHelpdeskUrlLocalized();
        $lvalues = '';
        if (is_array($localizedHelpdesk) && count($localizedHelpdesk) > 0) {
            foreach ($localizedHelpdesk as $k => $v) {
                $lvalues .= '<div><b>' . $k . ':</b> <a href="' . html_escape($v) . '"  target="_blank">' . html_escape($v) . '</a></div>';
            }
        }
        $d[$i]['value'] = $lvalues;

        $d[++$i]['name'] = lang('rr_regauthority');
        $regauthority = $ent->getRegistrationAuthority();
        $confRegAuth = $this->CI->config->item('registrationAutority');
        $confRegLoad = $this->CI->config->item('load_registrationAutority');
        $confRegistPolicy = $this->CI->config->item('registrationPolicy');
        $regauthoritytext = null;
        if (empty($regauthority)) {
            if ($isLocal && !empty($confRegLoad) && !empty($confRegAuth)) {
                $regauthoritytext = lang('rr_regauthority_alt') . ' <b>' . $confRegAuth . '</b><br /><small><i>' . lang('loadedfromglobalcnf') . '</i></small>';
            } else {
                $regauthoritytext = lang('rr_notset');
            }
            $d[$i]['value'] = $regauthoritytext;
        } else {
            $d[$i]['value'] = $regauthority;
        }

        $d[++$i]['name'] = lang('rr_regdate');
        $regdate = $ent->getRegistrationDate();
        if ($regdate !== null) {
            $d[$i]['value'] = '<span data-tooltip aria-haspopup="true" data-options="disable_for_touch:true" class="has-tip" title="' . date('Y-m-d H:i', $regdate->format('U')) . ' UTC">' . jaggerDisplayDateTimeByOffset($regdate, jauth::$timeOffset) . '</span>';
        } else {
            $d[$i]['value'] = null;
        }
        $regpolicy = $ent->getCoc();
        $regpolicy_value = '';
        if (count($regpolicy) > 0) {
            foreach ($regpolicy as $v) {
                $vtype = $v->getType();
                $venabled = $v->getAvailable();
                $l = '';
                if (!$venabled) {
                    $l = '<span class="label alert">' . lang('rr_disabled') . '</span>';
                }
                if (strcasecmp($vtype, 'regpol') == 0) {
                    $regpolicy_value .= '<div><b>' . $v->getLang() . '</b>: <a href="' . $v->getUrl() . '" target="_blank">' . html_escape($v->getName()) . '</a> ' . $l . '</div>';
                }
            }
        } elseif (!empty($confRegistPolicy) && !empty($confRegLoad)) {
            $regpolicy_value .= '<b>en:</b> ' . $confRegistPolicy . ' <div data-alert class="alert-box info">' . lang('loadedfromglobalcnf') . '</div>';
        }
        $d[++$i]['name'] = lang('rr_regpolicy');
        $d[$i]['value'] = $regpolicy_value;

        $defaultprivacyurl = $ent->getPrivacyUrl();
        if (!empty($defaultprivacyurl)) {
            $d[++$i]['name'] = lang('rr_defaultprivacyurl');
            $d[$i]['value'] = html_escape($defaultprivacyurl);
        }

        /**
         * @var models\Coc[] $entityCategories
         */
        $entityCategories = array();
        $a = array();

        $d[++$i]['name'] = lang('rr_entattr');
        $coc = $ent->getCoc();
        if ($coc->count() > 0) {
            foreach ($coc as $k => $v) {
                $coctype = $v->getType();
                if ($coctype === 'entcat') {
                    $cocvalue = '<a href="' . html_escape($v->getUrl()) . '"  target="_blank" title="' . html_escape($v->getDescription()) . '">' . html_escape($v->getName()) . '</a>';
                    if (!$v->getAvailable()) {
                        $cocvalue .= makeLabel('disabled', lang('rr_disabled'), lang('rr_disabled'));
                    }
                    $entityCategories[] = $v;
                    $a[] = $cocvalue;
                }
            }
            $d[$i]['value'] = implode('<br />', $a);
        } else {
            $d[$i]['value'] = lang('rr_notset');
        }

        $d[++$i]['name'] = lang('rr_validfromto') . ' <div class="dhelp">' . lang('d_validfromto') . '</div>';
        $validfrom = lang('rr_unlimited');
        if ($ent->getValidFrom()) {
            $validfrom = jaggerDisplayDateTimeByOffset($ent->getValidFrom(), jauth::$timeOffset);
        }
        $validto = lang('rr_unlimited');
        if ($ent->getValidTo()) {
            $validto = jaggerDisplayDateTimeByOffset($ent->getValidTo(), jauth::$timeOffset);
        }
        if ($isValidTime) {
            $d[$i]['value'] = $validfrom . ' <b>--</b> ' . $validto;
        } else {
            $d[$i]['value'] = '<span class="lbl lbl-alert">' . $validfrom . ' <b>--</b> ' . $validto . '</span>';
        }


        $result[] = array('section' => 'general', 'title' => '' . lang('tabGeneral') . '', 'data' => $d);


        $subresult[2] = array('section' => 'orgtab', 'title' => '' . lang('taborganization') . '', 'data' => $this->genOrgTab());


        /**
         * Metadata urls
         */

        $result[] = array('section' => 'federation', 'title' => '' . lang('tabMembership') . '', 'data' => $this->genFedView($ent));


        $d = array();
        $i = 0;

        if ($isStatic) {
            /**
             * @var models\StaticMetadata $tmp_st
             */
            $tmp_st = $ent->getStaticMetadata();
            if (!empty($tmp_st)) {
                $static_metadata = $tmp_st->getMetadata();
            } else {
                $static_metadata = null;
            }
            if (empty($static_metadata)) {
                $d[++$i]['name'] = lang('rr_staticmetadataactive');
                $d[$i]['value'] = '<span class="alert">' . lang('rr_isempty') . '</span>';
            } else {
                $d[++$i]['header'] = lang('rr_staticmetadataactive');
                $d[++$i]['2cols'] = '<pre><code class="xml">' . html_escape($static_metadata) . '</code></pre>';
            }
            $subresult[20] = array('section' => 'staticmetadata', 'title' => '' . lang('tabStaticMeta') . '', 'data' => $d);
        }


        /**
         * SAMLTAB
         */
        $d = array();
        $i = 0;

        /**
         * @var models\ServiceLocation[][] $services
         */
        $services = array();
        $srvs = $ent->getServiceLocations();
        if ($srvs->count() > 0) {
            foreach ($srvs as $v) {
                $services[$v->getType()][] = $v;
            }
        }
        $d[++$i] = &$entityIdRecord;

        /**
         * @var models\ExtendMetadata[] $algs
         * @var models\ExtendMetadata[][] $algorithms
         */
        $algs = $ent->getExtendMetadata();
        $algorithms = array();
        foreach ($algs as $a) {
            if ($a->getNamespace() === 'alg' && $a->getType() === 'ent') {
                $algorithms['' . $a->getElement() . ''][] = $a;
            }
        }
        if (count($algorithms) > 0) {
            $d[++$i]['header'] = 'Algorithms';
            foreach ($algorithms as $key => $val) {
                $d[++$i]['name'] = $key;
                $algvalue = '';
                foreach ($val as $entry) {
                    $algvalue .= $entry->getEvalue();
                    if ($key === 'SigningMethod') {
                        $algvalueattr = $entry->getAttributes();
                        if (count($algvalueattr) > 0) {
                            $algvalue .= ' (';
                            if (isset($algvalueattr['MinKeySize'])) {
                                $algvalue .= 'MinKeySize: ' . $algvalueattr['MinKeySize'] . ' ';
                            }
                            if (isset($algvalueattr['MaxKeySize'])) {
                                $algvalue .= 'MaxKeySize: ' . $algvalueattr['MaxKeySize'] . ' ';
                            }
                            $algvalue .= ')';

                        }
                    }
                    $algvalue .= '<br />';

                }
                $d[$i]['value'] = $algvalue;
            }

        }


        if ($idppart) {
            $d[++$i]['msection'] = 'IDPSSODescriptor';
            $wantAuthnReqSigned = $ent->getWantAuthnRequestSigned();
            $d[++$i]['name'] = 'WantAuthnRequestsSigned';
            if ($wantAuthnReqSigned === true) {
                $d[$i]['value'] = 'yes';
            } else {
                $d[$i]['value'] = 'no/not set';
            }

            // protocols enumerations
            $d[++$i]['name'] = lang('rr_supportedprotocols');
            $v = implode('<br />', $ent->getProtocolSupport('idpsso'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_domainscope');
            $d[$i]['value'] = implode('; ',$ent->getScope('idpsso'));

            $d[++$i]['name'] = lang('rr_supportednameids');
            $nameids = '<ul class="no-bullet">';
            foreach ($ent->getNameIds('idpsso') as $r) {
                $nameids .= '<li>' . html_escape($r) . '</li>';
            }
            $nameids .= '</ul>';
            $d[$i]['value'] = trim($nameids);

            if (array_key_exists('SingleSignOnService', $services)) {
                $ssovalues = '';
                $d[++$i]['name'] = 'SingleSignOnService';
                foreach ($services['SingleSignOnService'] as $s) {
                    $def = '';
                    if ($s->getDefault()) {
                        $def = '<i>(' . lang('rr_default') . ')</i>';
                    }
                    $ssovalues .= '<li data-jagger-checkurlalive="' . html_escape($s->getUrl()) . '"><b>' . $def . ' ' . html_escape($s->getUrl()) . '</b><br /><small>' . html_escape($s->getBindingName()) . '</small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $ssovalues . '</ul>';
            }
            if (array_key_exists('IDPSingleLogoutService', $services)) {
                $d[++$i]['name'] = 'SingleLogoutService';
                $slvalues = '';
                foreach ($services['IDPSingleLogoutService'] as $s) {
                    $slvalues .= '<b> ' . html_escape($s->getUrl()) . '</b><br /><small>' . html_escape($s->getBindingName()) . '</small><br />';
                }
                $d[$i]['value'] = $slvalues;
            }
            if (array_key_exists('IDPArtifactResolutionService', $services)) {
                $d[++$i]['name'] = 'ArtifactResolutionService';
                $slvalues = '';
                foreach ($services['IDPArtifactResolutionService'] as $s) {
                    $slvalues .= '<b>' . html_escape($s->getUrl()) . '</b> <small><i>index: ' . $s->getOrder() . '</i></small><br /><small>' . html_escape($s->getBindingName()) . '</small><br />';
                }
                $d[$i]['value'] = $slvalues;
            }
            $d[++$i]['msection'] = 'AttributeAuthorityDescriptor';
            $d[++$i]['name'] = lang('rr_supportedprotocols') . '';
            $v = implode('<br />', $ent->getProtocolSupport('aa'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_domainscope') . '';
            $d[$i]['value'] = implode('; ',$ent->getScope('aa'));
            $aanameids = $ent->getNameIds('aa');
            if (count($aanameids) > 0) {
                $d[++$i]['name'] = lang('rr_supportednameids');
                $aanameid = '<ul class="no-bullet">';
                foreach ($aanameids as $r) {
                    $aanameid .= '<li>' . html_escape($r) . '</li>';
                }
                $aanameid .= '</ul>';
                $d[$i]['value'] = $aanameid;
            }

            if (array_key_exists('IDPAttributeService', $services)) {
                $d[++$i]['name'] = 'AttributeService';
                $slvalues = '';
                foreach ($services['IDPAttributeService'] as $s) {
                    $slvalues .= '<b>' . html_escape($s->getUrl()) . '</b><br /><small>' . html_escape($s->getBindingName()) . '</small><br />';
                }
                $d[$i]['value'] = $slvalues;
            }
        }
        if ($sppart) {
            $d[++$i]['msection'] = 'SPSSODescriptor';

            $wantAssertionSigned = $ent->getWantAssertionSigned();
            $d[++$i]['name'] = 'WantAssertionsSigned';
            if ($wantAssertionSigned === true) {
                $d[$i]['value'] = 'yes';
            } else {
                $d[$i]['value'] = 'no/not set';
            }

            $authnReqAsigned = $ent->getAuthnRequestSigned();
            $d[++$i]['name'] = 'AuthnRequestsSigned';
            if ($authnReqAsigned === true) {
                $d[$i]['value'] = 'yes';
            } else {
                $d[$i]['value'] = 'no/not set';
            }


            $d[++$i]['name'] = lang('rr_supportedprotocols');
            $v = implode('<br />', $ent->getProtocolSupport('spsso'));
            $d[$i]['value'] = $v;

            $d[++$i]['name'] = lang('rr_supportednameids');


            $d[$i]['value'] = '<ul class="no-bullet"><li>' . implode('</li><li>', $ent->getNameIds('spsso')) . '</li></ul>';
            if (array_key_exists('AssertionConsumerService', $services)) {
                $acsvalues = '';
                $d[++$i]['name'] = 'AssertionConsumerService';
                foreach ($services['AssertionConsumerService'] as $s) {
                    $def = '';
                    if ($s->getDefault()) {
                        $def = '<i>(' . lang('rr_default') . ')</i>';
                    }
                    $acsvalues .= '<li><b>' . $def . ' ' . html_escape($s->getUrl()) . '</b> <small><i>index: ' . $s->getOrder() . '</i></small><br /><small>' . html_escape($s->getBindingName()) . ' </small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $acsvalues . '</ul>';
            }
            if (array_key_exists('SPArtifactResolutionService', $services)) {
                $acsvalues = '';
                $d[++$i]['name'] = 'ArtifactResolutionService';
                foreach ($services['SPArtifactResolutionService'] as $s) {
                    $def = '';
                    if ($s->getDefault()) {
                        $def = '<i>(' . lang('rr_default') . ')</i>';
                    }
                    $acsvalues .= '<li><b>' . $def . ' ' . html_escape($s->getUrl()) . '</b> <small><i>index: ' . $s->getOrder() . '</i></small><br /><small>' . html_escape($s->getBindingName()) . ' </small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $acsvalues . '</ul>';
            }
            if (array_key_exists('SPSingleLogoutService', $services)) {
                $d[++$i]['name'] = 'SingleLogoutService';
                $slvalues = '';
                foreach ($services['SPSingleLogoutService'] as $s) {
                    $slvalues .= '<li><b> ' . html_escape($s->getUrl()) . '</b><br /><small>' . html_escape($s->getBindingName()) . '</small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $slvalues . '</ul>';
            }
            if (array_key_exists('RequestInitiator', $services) || array_key_exists('DiscoveryResponse', $services)) {
                $d[++$i]['header'] = 'SPSSODescriptor/Extensions';
                if (array_key_exists('RequestInitiator', $services)) {
                    $d[++$i]['name'] = 'RequestInitiator <br /><small>SPSSODescriptor/Extensions</small>';
                    $rivalues = '';
                    foreach ($services['RequestInitiator'] as $s) {
                        $rivalues .= '<li><b>' . html_escape($s->getUrl()) . '</b><br /><small>' . html_escape($s->getBindingName()) . '</small></li>';
                    }
                    $d[$i]['value'] = '<ul class="no-bullet">' . $rivalues . '</ul>';
                }
                if (array_key_exists('DiscoveryResponse', $services)) {
                    $d[++$i]['name'] = 'DiscoveryResponse <br /><small>SPSSODescriptor/Extensions</small>';
                    $drvalues = '';
                    foreach ($services['DiscoveryResponse'] as $s) {
                        $drvalues .= '<li><b>' . html_escape($s->getUrl()) . '</b>&nbsp;&nbsp;<small><i>index:' . $s->getOrder() . '</i></small><br /><small>' . html_escape($s->getBindingName()) . '</small></li>';
                    }
                    $d[$i]['value'] = '<ul class="no-bullet">' . $drvalues . '</ul>';
                }
            }
        }
        $subresult[6] = array('section' => 'samltab', 'title' => '' . lang('tabsaml') . '', 'data' => $d);


        // Begin Certificates

        $subresult[11] = array('section' => 'certificates', 'title' => '' . lang('tabCerts') . '', 'data' => $this->genCertTab());

        /**
         * End Certificates
         */


        $xmldata = $this->CI->providertoxml->entityConvertNewDocument($ent, array('attrs' => 1), true);
        $subresult[1] = array('section' => 'xmlmeta', 'title' => '<i class="fa fa-file"></i>', 'data' => '<pre><code class="xml">' . html_escape($xmldata) . '</code></pre>');

        $d = array();
        if (count($entityCategories) == 0) {
            $d[]['2cols'] = '<div data-alert class="alert-box notice">' . lang('entattr_notdefined') . '</div>';
        } else {
            foreach ($entityCategories as $entcat) {
                $d[]['header'] = lang('title_entattr');
                $d[] = array('name' => lang('entattr_displayname'), 'value' => html_escape($entcat->getName()));
                $d[] = array('name' => lang('rr_attr_name'), 'value' => $entcat->getSubtype());
                $d[] = array('name' => lang('entattr_value'), 'value' => html_escape($entcat->getUrl()));
                $d[] = array('name' => lang('entattr_description'), 'value' => html_escape($entcat->getDescription()));
                $entcatStatus = $entcat->getAvailable();
                if (!$entcatStatus) {
                    $d[] = array('name' => '', 'value' => '<div class="label alert">' . lang('rr_disabled') . '</div>');
                }
            }
        }
        $subresult[12] = array('section' => 'entcats', 'title' => '' . lang('tabEntattrs') . '', 'data' => $d);


        $subresult[3] = array('section' => 'contacts', 'title' => '' . lang('tabContacts') . '', 'data' => $this->genContactsTab());

        $d = array();
        $i = 0;
        if ($idppart) {
            $d[++$i]['header'] = '<a name="arp"></a>' . lang('rr_arp');
            $encoded_entityid = base64url_encode($ent->getEntityId());
            $arp_url = base_url() . 'arp/format2/' . $encoded_entityid . '/arp.xml';
            $d[++$i]['name'] = lang('rr_individualarpurl');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_arpurl') . '</span><span class="accordionContent"><br />' . $arp_url . '&nbsp;</span>&nbsp;' . anchor_popup($arp_url, '<i class="fa fa-arrow-right"></i>');
            //

            $exc = $ent->getExcarps();
            if (!$isLocked && $hasWriteAccess && $ent->getLocal()) {

                $mlink = '';
                $this->entmenu[20] = array('label' => '' . lang('rr_attributes') . '');
                $this->entmenu[21] = array('name' => lang('rr_arpexclist_edit'), 'link' => '' . base_url() . 'manage/arpsexcl/idp/' . $ent->getId() . '', 'class' => '');
                $d[++$i]['name'] = lang('rr_arpexclist_title') . ' <br />' . $mlink;
                if (is_array($exc) && count($exc) > 0) {
                    $l = '<ul class="no-bullet">';
                    foreach ($exc as $e) {
                        $l .= '<li>' . $e . '</li>';
                    }
                    $l .= '</ul>';
                    $d[$i]['value'] = $l;
                } else {
                    $d[$i]['value'] = '';
                }
            }
            $d[++$i]['name'] = lang('rr_arpoverview');
            $d[$i]['value'] = anchor(base_url('reports/idpmatrix/show/' . $ent->getId()), 'matrix', 'class="button"');
        }
        $logoUploadEnabled = $this->CI->config->item('rr_logoupload');
        /**
         * supported attributes by IDP part
         */
        if ($idppart) {
            if ($hasWriteAccess) {
                $this->entmenu[20] = array('label' => '' . lang('rr_attributes') . '');
                $this->entmenu[23] = array('name' => '' . lang('rr_attributepolicy') . '', 'link' => '' . base_url() . 'manage/attributepolicy/show/' . $id . '', 'class' => '');
                if (!empty($logoUploadEnabled) && $logoUploadEnabled === true) {
                    $this->entmenu[24] = array('name' => '' . lang('rr_logos') . ' <span class="label">deprecated</span>', 'link' => '' . base_url('manage/logomngmt/provider/idp/' . $ent->getId() . ''), 'class' => '');
                }
            }

            $d[++$i]['header'] = '<a name="attrs"></a>' . lang('rr_supportedattributes') . ' ' . $edit_attributes;
            $tmpAttrs = new models\AttributeReleasePolicies;
            /**
             * @var models\AttributeReleasePolicy[] $supportedAttributes
             */
            $supportedAttributes = $tmpAttrs->getSupportedAttributes($ent);
            foreach ($supportedAttributes as $s) {
                $d[++$i]['name'] = $s->getAttribute()->getName();
                $d[$i]['value'] = $s->getAttribute()->getDescription();
            }
        }

        /**
         * required attributes by SP part
         */
        if ($sppart) {

            if ($hasWriteAccess) {
                $d[++$i]['name'] = lang('rr_attrsoverview');
                $d[$i]['value'] = anchor(base_url() . 'reports/spmatrix/show/' . $ent->getId(), lang('rr_attrsoverview'), 'class="button small editbutton"');

                if (!empty($logoUploadEnabled) && $logoUploadEnabled === true) {
                    $this->entmenu[24] = array('name' => '' . lang('rr_logos') . ' <span class="label">deprecated</span>', 'link' => '' . base_url('manage/logomngmt/provider/sp/' . $ent->getId() . ''), 'class' => '');
                }
            }
            $requiredAttributes = $ent->getAttributesRequirement();
            if ($requiredAttributes->count() === 0) {
                $d[++$i]['name'] = '';
                $d[$i]['value'] = '<div data-alert class="alert-box warning">' . lang('rr_noregspecified_inherit_from_fed') . '</div>';
            } else {
                foreach ($requiredAttributes as $v) {
                    $d[++$i]['name'] = $v->getAttribute()->getName();
                    $d[$i]['value'] = '<b>' . $v->getStatus() . '</b>: <i>(' . html_escape($v->getReason()) . ')</i>';
                }
            }
        }
        $subresult[13] = array('section' => 'attrs', 'title' => '' . lang('tabAttrs') . '', 'data' => $d);
        $d = array();
        $i = 0;


        if ($idppart) {
            /**
             * @var models\ExtendMetadata[][] $uiiarray
             */
            $uiiarray = array();
            $d[++$i]['msection'] = lang('identityprovider');
            foreach ($extend as $e) {
                if ($e->getNamespace() === 'mdui' && $e->getType() === 'idp') {
                    $uiiarray[$e->getElement()][] = $e;
                }
            }
            $discohintsarray = $uiiarray;
            $d[++$i]['name'] = lang('e_idpservicename');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'DisplayName');
            $d[++$i]['name'] = lang('e_idpservicedesc');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'Description');

            $d[++$i]['name'] = lang('rr_uiikeywords');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'Keywords');

            $d[++$i]['name'] = lang('e_idpserviceinfourl');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'InformationURL');
            $d[++$i]['name'] = lang('e_idpserviceprivacyurl');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'PrivacyStatementURL');
            $d[++$i]['name'] = lang('rr_logoofservice');
            if (isset($uiiarray['Logo'])) {
                $str = '';
                foreach ($uiiarray['Logo'] as $v) {
                    $logovalue = $v->getLogoValue();
                    if ((substr($logovalue, 0, 5)) === 'data:') {
                        $figcap = lang('embedlogo');
                    } else {
                        $figcap = html_escape($logovalue);
                    }

                    $str .= '<figure><img src="' . $logovalue . '" style="max-height: 40px"/><figcaption>' . $figcap . '<br/></figcaption></figure><br />';

                }
                $d[$i]['value'] = $str;
            } else {
                $d[$i]['value'] = lang('rr_notset');
            }
        }
        if ($sppart) {
            /**
             * @var models\ExtendMetadata[][] $uiiarray
             */
            $uiiarray = array();
            $d[++$i]['msection'] = lang('serviceprovider');
            foreach ($extend as $e) {
                if ($e->getNamespace() === 'mdui' && $e->getType() === 'sp') {
                    $uiiarray[$e->getElement()][] = $e;
                }
            }
            $d[++$i]['name'] = lang('e_spservicename');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'DisplayName');
            $d[++$i]['name'] = lang('e_spservicedesc');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'Description');
            $d[++$i]['name'] = lang('rr_uiikeywords');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'Keywords');
            $d[++$i]['name'] = lang('e_spserviceprivacyurl');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'PrivacyStatementURL');
            $d[++$i]['name'] = lang('e_spserviceinfourl');
            $d[$i]['value'] = $this->getUrlsByLang($uiiarray, 'InformationURL');

            $d[++$i]['name'] = lang('rr_logoofservice');
            if (isset($uiiarray['Logo'])) {
                $str = '';
                foreach ($uiiarray['Logo'] as $v) {
                    $logovalue = $v->getLogoValue();
                    if ((substr($logovalue, 0, 5)) === 'data:') {
                        $figcap = lang('embedlogo');
                    } else {
                        $figcap = html_escape($logovalue);
                    }

                    $str .= '<figure><img src="' . $logovalue . '" style="max-height: 40px"/><figcaption>' . $figcap . '<br/></figcaption></figure><br />';

                }
                $d[$i]['value'] = $str;
            } else {
                $d[$i]['value'] = lang('rr_notset');
            }


        }

        $subresult[4] = array('section' => 'uii', 'title' => '' . lang('tabUII') . '', 'data' => $d);

        if ($idppart) {
            $d = array();
            $i = 0;
            $d[++$i]['name'] = lang('rr_geolocation');
            if (isset($discohintsarray['GeolocationHint'])) {
                $str = '';
                foreach ($discohintsarray['GeolocationHint'] as $v) {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            } else {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = 'IPHint';
            if (isset($discohintsarray['IPHint'])) {
                $str = '';
                foreach ($discohintsarray['IPHint'] as $v) {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            } else {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = 'DomainHint';
            if (isset($discohintsarray['DomainHint'])) {
                $str = '';
                foreach ($discohintsarray['DomainHint'] as $v) {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            } else {
                $d[$i]['value'] = lang('rr_notset');
            }


            $subresult[5] = array('section' => 'uiihints', 'title' => 'UI Hints', 'data' => $d);
        }
        $d = array();
        $i = 0;
        if ($hasManageAccess) {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' ' . anchor(base_url() . 'manage/entitystate/modify/' . $id, '<i class="fa fa-arrow-right"></i>');
            if (!$isActive) {
                $d[$i]['value'] .= '<div>' . lang('rr_rmprovider') . ' ' . anchor(base_url() . 'manage/premoval/providertoremove/' . $id, '<i class="fa fa-arrow-right"></i>') . '</div>';
            } else {
                $d[$i]['value'] .= '<div>' . lang('rr_rmprovider') . '<span class="alert"><i class="fa fa-lock"></i></span> <div class="label alert">' . lang('rmproviderdisablefirst') . '</div></div>';
            }
        } elseif ($hasWriteAccess) {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' <span class="alert"><i class="fa fa-lock"></i></span><div class="alert">' . lang('rerror_managepermneeded') . '</div>';
            $d[$i]['value'] .= '<div>' . lang('rr_rmprovider') . '<i class="fa fa-lock"></i><div class="alert">' . lang('rerror_managepermneeded') . '</div> </div>';
        } else {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' <span class="alert"><i class="fa fa-lock"></i></span>';
        }
        $d[++$i]['name'] = '';
        if ($hasManageAccess) {
            $d[$i]['value'] = lang('rr_displayaccess') . anchor(base_url() . 'manage/accessmanage/entity/' . $id, '<i class="fa fa-arrow-right"></i>');
        } else {
            $d[$i]['value'] = lang('rr_displayaccess') . '';
        }
        if (($hasManageAccess || $hasWriteAccess) && $isLocal) {

            $d[++$i] = array('name' => lang('regpols_menulink'), 'value' => '<a href="' . base_url() . 'manage/entitystate/regpolicies/' . $ent->getId() . '" class="button"><i class="fa fa-pencil"> </i> ' . lang('rr_edit') . '');
        }

        ksort($subresult);

        $finalsubtab = &$subresult;
        $result[] = array('section' => 'samlmetadata', 'title' => 'Metadata', 'subtab' => $finalsubtab);
        $result[] = array('section' => 'mngt', 'title' => '' . lang('tabMngt') . '', 'data' => $d);

        $data['tabs'] = $result;
        Detail::$alerts = $alerts;
        $data['entmenu'] = $this->entmenu;

        return $data;
    }

    /**
     * @param array $data
     * @param $key
     * @return string
     */
    private function getUrlsByLang(array $data, $key) {
        if (!array_key_exists($key, $data) || !is_array($data[$key]) || count($data[$key]) == 0) {
            return lang('rr_notset');
        }
        $str = '';
        foreach ($data[$key] as $val) {
            $attr = $val->getAttributes();
            $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . nl2br(html_escape($val->getEvalue())) . '<br />';
        }

        return $str;
    }

}
