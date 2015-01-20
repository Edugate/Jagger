<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');

/**
 * JAGGER
 * 
 * @package     JAGGER
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2015, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Providerdetails Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Providerdetails {

    protected $CI, $em;

    function __construct()
    {
        $this->CI = &get_instance();
        $this->em = $this->CI->doctrine->em;
    }

    private function _genCertView($cert)
    {
        $certusage = $cert->getCertuse();
        if ($certusage === 'signing')
        {
            $langcertusage = lang('certsign');
        }
        elseif ($certusage === 'encryption')
        {
            $langcertusage = lang('certenc');
        }
        else
        {
            $langcertusage = lang('certsign') . '/' . lang('certenc');
        }
        $d = array();
        $d[] = array('header' => lang('rr_certificate'));
        $d[] = array('name' => lang('rr_certusage'), 'value' => $langcertusage);
        $keyname = $cert->getKeyname();
        if (!empty($keyname))
        {
            $d[] = array('name' => lang('rr_keyname'), 'value' => $keyname);
        }
        $certData = $cert->getCertData();
        if (!empty($certData))
        {
            $certtype = $cert->getCertType();
            if ($certtype === 'X509Certificate')
            {
                $fingerprint_md5 = generateFingerprint($certData, 'md5');
                $fingerprint_sha1 = generateFingerprint($certData, 'sha1');
                $certValid = validateX509($certData);
                if (!$certValid)
                {
                    $cString = '<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                }
                else
                {
                    $pemdata = $cert->getPEM($cert->getCertData());

                    $d[] = array('name' => lang('rr_keysize'), 'value' => '' . getKeysize($pemdata) . '');
                    $d[] = array('name' => lang('rr_fingerprint') . ' (md5)', 'value' => '' . generateFingerprint($certData, 'md5') . '');
                    $d[] = array('name' => lang('rr_fingerprint') . ' (sha1)', 'value' => '' . generateFingerprint($certData, 'sha1') . '');
                    $d[] = array(
                        'name' => '',
                        'value' => '<dl class="accordion" data-accordion>   <dd class="accordion-navigation"><a href="#c' . $cert->getId() . '" class="accordion-icon">' . lang('rr_certbody') . '</a><code id="c' . $cert->getId() . '" class="content">' . trim($certData) . '</code></dd></dl>'
                    );
                }
            }
        }
        return $d;
    }

    private function makeStatusLabels(\models\Provider $ent)
    {
        $entStatus = '';
        $isValidTime = $ent->isValidFromTo();
        $isActive = $ent->getActive();
        $isLocal = $ent->getLocal();
        $isPublicListed = $ent->getPublicVisible();
        $isLocked = $ent->getLocked();
        $isStatic = $ent->getStatic();

        if (empty($isPublicListed))
        {
            $entStatus .= ' ' . makeLabel('disabled', lang('lbl_publichidden'), lang('lbl_publichidden'));
        }
        if (empty($isActive))
        {
            $entStatus .= ' ' . makeLabel('disabled', lang('lbl_disabled'), lang('lbl_disabled'));
        }
        else
        {
            $entStatus .= ' ' . makeLabel('active', lang('lbl_enabled'), lang('lbl_enabled'));
        }
        if (!$isValidTime)
        {
            $entStatus .= ' ' . makeLabel('alert', lang('rr_validfromto_notmatched1'), strtolower(lang('rr_metadata')) . ' ' . lang('rr_expired'));
        }
        if ($isLocked)
        {
            $entStatus .= ' ' . makeLabel('locked', lang('rr_locked'), lang('rr_locked'));
        }
        if ($isLocal)
        {
            $entStatus .= ' ' . makeLabel('local', lang('rr_managedlocally'), lang('rr_managedlocally'));
        }
        else
        {
            $entStatus .= ' ' . makeLabel('local', lang('rr_external'), lang('rr_external'));
        }
        if ($isStatic)
        {
            $entStatus .= ' ' . makeLabel('static', lang('lbl_static'), lang('lbl_static'));
        }
        return $entStatus;
    }

    public function generateAlertsDetails(\models\Provider $provider)
    {
        $result = array();


        $contacts = $provider->getContacts();
        if (count($contacts) == 0)
        {
            $result[] = array('msg' => 'No contacts are defined', 'level' => 'warning');
        }

        $certificates = $provider->getCertificates();
        $this->CI->load->helper('cert');
        $minkeysize = $this->CI->config->item('entkeysizemin');
        foreach ($certificates as $certificate)
        {
            $cert = $certificate->getCertData();
            $i = explode("\n", $cert);
            $c = count($i);
            if ($c < 2)
            {
                $pem = chunk_split($cert, 64, PHP_EOL);
                $cert = $pem;
            }

            $ncert = getPEM($cert);
            $res = @openssl_x509_parse($ncert);
            if (is_array($res))
            {
                if (!empty($minkeysize))
                {
                    $minkeysize = (int) $minkeysize;
                }
                else
                {
                    $minkeysize = 2048;
                }
                $r = openssl_pkey_get_public($ncert);
                $keysize = 0;
                if (!empty($r))
                {
                    $data = array();
                    $data = openssl_pkey_get_details($r);
                    if (isset($data['bits']))
                    {
                        $keysize = $data['bits'];
                    }
                    else
                    {
                        $result[] = array('msg' => 'Could not compute keysize', 'level' => 'warning');
                        continue;
                    }
                }

                if ($minkeysize > $keysize)
                {

                    $result[] = array('msg' => 'The keysize of one of the certificates is less than ' . $minkeysize, 'level' => 'warning');
                    continue;
                }

                if (isset($res['validTo_time_t']))
                {

                    $validto = date('Y-m-d H:i:s', $res['validTo_time_t']);
                   // $result[] = array('msg' => 'Valid to: ' . $validto, 'level' => 'info');
                }
            }
            else
            {
                $result[] = array('msg' => 'One of certs is not valid', 'level' => 'warning');
                continue;
            }
        }

        $serviceLocation = $provider->getServiceLocations();
        $srvsTcpChecked = array();
        foreach ($serviceLocation as $s)
        {
            $surl = $s->getUrl();
            $parsedUrl = parse_url($surl);
            $urlPort = null;
            $isHostOK = true;
            $hostsByIP = array();
            if (array_key_exists('port', $parsedUrl) && !empty($parsedUrl['port']))
            {
                $urlPort = $parsedUrl['port'];
            }
            elseif (array_key_exists('scheme', $parsedUrl))
            {
                if ($parsedUrl['scheme'] === 'http')
                {
                    $urlPort = 80;
                }
                elseif ($parsedUrl['scheme'] === 'https')
                {
                    $urlPort = 443;
                }
                else
                {
                    $result[] = array('msg' => 'Incorrect protocol in service url :' . htmlspecialchars($surl), 'level' => 'error');
                }
            }
            if (array_key_exists('host', $parsedUrl))
            {
                $srvHost = $parsedUrl['host'];
                if (!empty($srvHost) && filter_var($srvHost, FILTER_VALIDATE_IP))
                {
                    $result[] = array('msg' => 'Service URL: ' . htmlspecialchars($surl) . ' -  contains IP address', 'level' => 'warning');
                    $isHostOK = false;
                }
                else
                {
                    $resolved = dns_get_record($srvHost, DNS_A + DNS_AAAA);
                    if (!empty($resolved))
                    {
                        foreach ($resolved as $r)
                        {
                            if (array_key_exists('ip', $r))
                            {

                                if (!(filter_var($r['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) && filter_var($r['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)))
                                {
                                    $result[] = array('msg' => 'Service URL: ' . htmlspecialchars($surl) . ' - Resolving host  result IP: ' . $r['ip'] . ' which is in private or reserved pool', 'level' => 'warning');
                                    $isHostOK = false;
                                }
                                else
                                {
                                    $hostsByIP['ipv4'][] = $r['ip'];
                                }
                            }
                            if (array_key_exists('ipv6', $r))
                            {
                                if (!filter_var($r['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE))
                                {
                                    $result[] = array('msg' => 'Service URL: ' . htmlspecialchars($surl) . ' - Resolving host  results : ' . $r['ipv6'] . ' which is in private or reserved pool', 'level' => 'warning');
                                    $isHostOK = false;
                                }
                                else
                                {
                                    $hostsByIP['ipv6'][] = $r['ipv6'];
                                }
                            }
                        }
                    }
                    else
                    {
                        $result[] = array('msg' => 'Service URL: ' . htmlspecialchars($surl) . ' - Could not resolve a domain from service URL: ', 'level' => 'warning');
                        $isHostOK = false;
                    }
                }
            }
            else
            {
                $isHostOK = false;
            }
            if ($isHostOK === true && !empty($urlPort))
            {
                if (array_key_exists('ipv4', $hostsByIP))
                {
                    foreach ($hostsByIP['ipv4'] as $ip)
                    {

                        if (!in_array('' . $ip . '_' . $urlPort, $srvsTcpChecked))
                        {
                            $fp = fsockopen($ip, $urlPort, $errno, $errstr, 2);
                            if (!$fp)
                            {
                                $result[] = array('msg' => 'Service URL: ' . htmlspecialchars($surl) . ' : ' . $ip . ' : ' . $errstr . ' (' . $errno . ')', 'level' => 'alert');
                            }
                            $srvsTcpChecked[] = '' . $ip . '_' . $urlPort;
                        }
                    }
                }
                if (array_key_exists('ipv6', $hostsByIP))
                {


                    foreach ($hostsByIP['ipv6'] as $ip)
                    {
                        if (!in_array('' . $ip . '_' . $urlPort, $srvsTcpChecked))
                        {
                            $fp = fsockopen('tcp://[' . $ip . ']', $urlPort, $errno, $errstr, 2);
                            if (!$fp)
                            {
                                $result[] = array('msg' => 'Service URL: ' . htmlspecialchars($surl) . ' : ' . $ip . ' : ' . $errstr . ' (' . $errno . ')', 'level' => 'alert');
                            }
                            $srvsTcpChecked[] = '' . $ip . '_' . $urlPort;
                        }
                    }
                }
            }
        }


        if (count($result) == 0)
        {
            $result[] = array('msg' => 'No alerts', 'level' => 'info');
        }

        return $result;
    }

    public function generateForControllerProvidersDetail(\models\Provider $ent)
    {

        $feathide = $this->CI->config->item('feathide');
        if (empty($feathide))
        {
            $feathide = array();
        }
        $featdisable = $this->CI->config->item('featdisable');
        if (empty($featdisable))
        {
            $featdisable = array();
        }
        $alerts = array();

        $lockicon = genIcon('locked');
        $is_static = $ent->getStatic();

        $params = array(
            'enable_classes' => true,
        );
        $sppart = FALSE;
        $idppart = FALSE;
        $type = strtolower($ent->getType());
        $data['type'] = $type;
        $edit_attributes = '';
        $edit_policy = '';


        if ($type === 'idp')
        {
            MY_Controller::$menuactive = 'idps';
            $idppart = TRUE;
            $data['presubtitle'] = lang('identityprovider');
        }
        elseif ($type === 'sp')
        {
            MY_Controller::$menuactive = 'sps';
            $sppart = TRUE;
            $data['presubtitle'] = lang('serviceprovider');
        }
        elseif ($type === 'both')
        {
            $sppart = TRUE;
            $idppart = TRUE;
            $data['presubtitle'] = lang('rr_asboth');
        }
        $id = $ent->getId();
        $hasReadAccess = $this->CI->zacl->check_acl($id, 'read', 'entity', '');
        $hasWriteAccess = $this->CI->zacl->check_acl($id, 'write', 'entity', '');
        $hasManageAccess = $this->CI->zacl->check_acl($id, 'manage', 'entity', '');
        if (!$hasReadAccess)
        {
            return false;
        }
        // off canvas menu for provider
        $entmenu = array();



        $edit_link = '';

        if ($is_static)
        {

            $alerts[] = lang('staticmeta_info');
        }
        $isActive = $ent->getActive();
        $isValidTime = $ent->isValidFromTo();
        $isLocal = $ent->getLocal();
        $isLocked = $ent->getLocked();
        if (!$hasWriteAccess)
        {
            $edit_link .= makeLabel('noperm', lang('rr_nopermission'), lang('rr_nopermission'));
            $entmenu[0] = array('name' => '' . lang('rr_nopermission') . '', 'link' => '#', 'class' => 'alert');
        }
        elseif (!$isLocal)
        {
            $edit_link .= makeLabel('external', lang('rr_externalentity'), lang('rr_external'));
            $entmenu[0] = array('name' => '' . lang('rr_externalentity') . '', 'link' => '#', 'class' => 'alert');
        }
        elseif ($isLocked)
        {
            $edit_link .= makeLabel('locked', lang('rr_lockedentity'), lang('rr_lockedentity'));
            $entmenu[0] = array('name' => '' . lang('rr_lockedentity') . '', 'link' => '#', 'class' => 'alert');
        }
        else
        {
            $edit_link .= '<a href="' . base_url() . 'manage/entityedit/show/' . $id . '" class="editbutton editicon button small" id="editprovider" title="edit" >' . lang('rr_edit') . '</a>';
            $entmenu[0] = array('name' => '' . lang('rr_editentity') . '', 'link' => '' . base_url() . 'manage/entityedit/show/' . $id . '', 'class' => '');
            $data['showclearcache'] = TRUE;
        }
        $data['edit_link'] = $edit_link;
        $data['entmenu'] = &$entmenu;
        $extend = $ent->getExtendMetadata();
        /**
         * get first assinged logo to display on site 
         */
        $is_logo = false;
        foreach ($extend as $v)
        {
            if ($is_logo)
            {
                break;
            }
            if ($v->getElement() === 'Logo')
            {
                $providerlogourl = $v->getLogoValue();
                $is_logo = TRUE;
            }
        }
        if (!empty($providerlogourl))
        {
            $data['providerlogourl'] = $providerlogourl;
        }
        $data['entid'] = $ent->getId();
        $guiLang = MY_Controller::getLang();
        $data['name'] = $ent->getNameToWebInLang($guiLang, $type);
        $this->title = lang('rr_providerdetails') . ' :: ' . $data['name'];
        $b = $this->CI->session->userdata('board');
        if (!empty($b) && is_array($b))
        {
            if (($type == 'idp' || $type == 'both') && isset($b['idp'][$id]))
            {
                $data['bookmarked'] = true;
            }
            elseif (($type == 'sp' || $type == 'both') && isset($b['sp'][$id]))
            {
                $data['bookmarked'] = true;
            }
        }


        /**
         * BASIC
         */
        $d = array();
        $i = 0;
        $d[++$i]['name'] = lang('rr_status') . ' ' . showBubbleHelp('<ul class="no-bullet"><li><b>' . lang('lbl_enabled') . '</b>:' . lang('provinmeta') . '</li><li><b>' . lang('lbl_disabled') . '</b>:' . lang('provexclmeta') . ' </li><li><b>' . lang('rr_managedlocally') . '</b>: ' . lang('provmanlocal') . '</li><li><b>' . lang('rr_external') . '</b>: ' . lang('provexternal') . '</li></ul>') . '';
        $entStatus = $this->makeStatusLabels($ent);
        $d[$i]['value'] = '<b>' . $entStatus . '</b>';
        $d[++$i]['name'] = lang('rr_lastmodification');
        $d[$i]['value'] = '<b>' . date('Y-m-d H:i:s', $ent->getLastModified()->format('U') + j_auth::$timeOffset) . '</b>';
        $entityIdRecord = array('name' => lang('rr_entityid'), 'value' => $ent->getEntityId());
        $d[++$i] = &$entityIdRecord;

        $d[++$i]['name'] = lang('e_orgname');
        $lname = $ent->getMergedLocalName();
        $lvalues = '';
        if (count($lname) > 0)
        {
            foreach ($lname as $k => $v)
            {
                $lvalues .= '<b>' . $k . ':</b> ' . $v . '<br />';
            }
            $d[$i]['value'] = $lvalues;
        }
        else
        {
            $d[$i]['value'] = '';
        }
        $d[++$i]['name'] = lang('e_orgdisplayname');
        $ldisplayname = $ent->getMergedLocalDisplayName();
        $lvalues = '';
        if (count($ldisplayname) > 0)
        {
            foreach ($ldisplayname as $k => $v)
            {
                $lvalues .= '<b>' . $k . ':</b> ' . $v . '<br />';
            }
            $d[$i]['value'] = '<div id="selectme">' . $lvalues . '</div>';
        }
        else
        {
            $d[$i]['value'] = '<div id="selectme"></div>';
        }
        $d[++$i]['name'] = lang('e_orgurl');
        $localizedHelpdesk = $ent->getHelpdeskUrlLocalized();
        if (is_array($localizedHelpdesk) && count($localizedHelpdesk) > 0)
        {
            $lvalues = '';
            foreach ($localizedHelpdesk as $k => $v)
            {
                $lvalues .= '<div><b>' . $k . ':</b> <a href="' . $v . '"  target="_blank">' . $v . '</a></div>';
            }
            $d[$i]['value'] = $lvalues;
        }
        else
        {
            $d[$i]['value'] = '';
        }
        $d[++$i]['name'] = lang('rr_regauthority');
        $regauthority = $ent->getRegistrationAuthority();
        $confRegAuth = $this->CI->config->item('registrationAutority');
        $confRegLoad = $this->CI->config->item('load_registrationAutority');
        $confRegistrationPolicy = $this->CI->config->item('registrationPolicy');
        $regauthoritytext = null;
        if (empty($regauthority))
        {
            if ($isLocal && !empty($confRegLoad) && !empty($confRegAuth))
            {
                $regauthoritytext = lang('rr_regauthority_alt') . ' <b>' . $confRegAuth . '</b><br /><small><i>' . lang('loadedfromglobalcnf') . '</i></small>';
            }
            else
            {
                $regauthoritytext = lang('rr_notset');
            }
            $d[$i]['value'] = $regauthoritytext;
        }
        else
        {
            $d[$i]['value'] = $regauthority;
        }

        $d[++$i]['name'] = lang('rr_regdate');
        $regdate = $ent->getRegistrationDate();
        if (isset($regdate))
        {
            $d[$i]['value'] = '<span data-tooltip aria-haspopup="true" data-options="disable_for_touch:true" class="has-tip" title="' . date('Y-m-d H:i', $regdate->format('U')) . ' UTC">' . date('Y-m-d H:i', $regdate->format('U') + j_auth::$timeOffset) . '</span>';
        }
        else
        {
            $d[$i]['value'] = null;
        }
        $regpolicy = $ent->getCoc();
        $regpolicy_value = '';
        if (count($regpolicy) > 0)
        {
            foreach ($regpolicy as $v)
            {
                $vtype = $v->getType();
                $venabled = $v->getAvailable();
                $l = '';
                if (!$venabled)
                {
                    $l = '<span class="label alert">' . lang('rr_disabled') . '</span>';
                }
                if (strcasecmp($vtype, 'regpol') == 0)
                {
                    $regpolicy_value .='<div><b>' . $v->getLang() . '</b>: <a href="' . $v->getUrl() . '" target="_blank">' . $v->getName() . '</a> ' . $l . '</div>';
                }
            }
        }
        elseif (!empty($confRegistrationPolicy) && !empty($confRegLoad))
        {
            $regpolicy_value .= '<b>en:</b> ' . $confRegistrationPolicy . ' <div data-alert class="alert-box info">' . lang('loadedfromglobalcnf') . '</div>';
        }
        $d[++$i]['name'] = lang('rr_regpolicy');
        $d[$i]['value'] = $regpolicy_value;

        $defaultprivacyurl = $ent->getPrivacyUrl();
        if (!empty($defaultprivacyurl))
        {
            $d[++$i]['name'] = lang('rr_defaultprivacyurl');
            $d[$i]['value'] = $ent->getPrivacyUrl();
        }

        $entityCategories = array();
        $a = array();

        $d[++$i]['name'] = lang('rr_entcats');
        $coc = $ent->getCoc();
        if ($coc->count() > 0)
        {
            foreach ($coc as $k => $v)
            {
                $coctype = $v->getType();
                if ($coctype === 'entcat')
                {
                    $cocvalue = '<a href="' . $v->getUrl() . '"  target="_blank" title="' . $v->getDescription() . '">' . $v->getName() . '</a>';
                    if (!$v->getAvailable())
                    {
                        $cocvalue .= makeLabel('disabled', lang('rr_disabled'), lang('rr_disabled'));
                    }
                    $entityCategories[] = $v;
                    $a[] = $cocvalue;
                }
            }
            $d[$i]['value'] = implode('<br />', $a);
        }
        else
        {
            $d[$i]['value'] = lang('rr_notset');
        }

        $d[++$i]['name'] = lang('rr_validfromto') . ' <div class="dhelp">' . lang('d_validfromto') . '</div>';
        if ($ent->getValidFrom())
        {
            $validfrom = date('Y M d HH:MM', $ent->getValidFrom()->format('U') + j_auth::$timeOffset);
        }
        else
        {
            $validfrom = lang('rr_unlimited');
        }
        if ($ent->getValidTo())
        {
            $validto = date('Y M d H:i', $ent->getValidTo()->format('U') + j_auth::$timeOffset);
        }
        else
        {
            $validto = lang('rr_unlimited');
        }
        if ($isValidTime)
        {
            $d[$i]['value'] = $validfrom . ' <b>--</b> ' . $validto;
        }
        else
        {
            $d[$i]['value'] = '<span class="lbl lbl-alert">' . $validfrom . ' <b>--</b> ' . $validto . '</span>';
        }
        $result[] = array('section' => 'general', 'title' => '' . lang('tabGeneral') . '', 'data' => $d);


        /**
         * ORG tab
         */
        $d = array();
        $i = 0;
        $d[++$i]['name'] = lang('e_orgname');
        $lname = $ent->getMergedLocalName();
        $lvalues = '';
        if (count($lname) > 0)
        {
            foreach ($lname as $k => $v)
            {
                $lvalues .= '<b>' . $k . ':</b> ' . $v . '<br />';
            }
            $d[$i]['value'] = $lvalues;
        }
        else
        {
            $d[$i]['value'] = '<div id="selectme" data-alert class="alert-box alert">' . lang('rr_notset') . '</div>';
        }
        $d[++$i]['name'] = lang('e_orgdisplayname');
        $ldisplayname = $ent->getMergedLocalDisplayName();
        $lvalues = '';
        if (count($ldisplayname) > 0)
        {
            foreach ($ldisplayname as $k => $v)
            {
                $lvalues .= '<b>' . $k . ':</b> ' . $v . '<br />';
            }
            $d[$i]['value'] = '<div id="selectme">' . $lvalues . '</div>';
        }
        else
        {
            $d[$i]['value'] = '<div id="selectme" data-alert class="alert-box alert">' . lang('rr_notset') . '</div>';
        }
        $d[++$i]['name'] = lang('e_orgurl');
        $localizedHelpdesk = $ent->getHelpdeskUrlLocalized();
        if (is_array($localizedHelpdesk) && count($localizedHelpdesk) > 0)
        {
            $lvalues = '';
            foreach ($localizedHelpdesk as $k => $v)
            {
                $lvalues .= '<div><b>' . $k . ':</b> ' . $v . '</div>';
            }
            $d[$i]['value'] = $lvalues;
        }
        else
        {
            $d[$i]['value'] = '<div id="selectme" data-alert class="alert-box alert">' . lang('rr_notset') . '</div>';
        }


        $subresult[2] = array('section' => 'orgtab', 'title' => '' . lang('taborganization') . '', 'data' => $d);



        /**
         * Metadata urls
         */
        $d = array();
        $i = 0;


        $srv_metalink = base_url("metadata/service/" . base64url_encode($ent->getEntityId()) . "/metadata.xml");

        $disable_extcirclemeta = $this->CI->config->item('disable_extcirclemeta');
        $gearman_enabled = $this->CI->config->item('gearman');

        if (!(isset($feathide['metasonprov']) && $feathide['metasonprov'] === true))
        {
            $d[++$i]['header'] = lang('rr_metadata');
            $d[++$i]['name'] = '<a name="metadata"></a>' . lang('rr_servicemetadataurl');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . ':</span> <span class="accordionContent"><br />' . $srv_metalink . '&nbsp;</span>&nbsp; ' . anchor($srv_metalink, '<i class="fi-arrow-right"></i>', '');
        }
        $circleEnabled = !((isset($featdisable['circlemeta']) && $featdisable['circlemeta'] === TRUE) || (isset($feathide['circlemeta']) && $feathide['circlemeta'] === TRUE));

        if ($circleEnabled)
        {

            if (!$isLocal && !empty($disable_extcirclemeta) && $disable_extcirclemeta === TRUE)
            {
                $d[++$i]['name'] = lang('rr_circleoftrust');
                $d[$i]['value'] = lang('disableexternalcirclemeta');
                $d[++$i]['name'] = lang('rr_circleoftrust') . '<i>(' . lang('signed') . ')</i>';
                $d[$i]['value'] = lang('disableexternalcirclemeta');
            }
            else
            {
                $srv_circle_metalink = base_url() . 'metadata/circle/' . base64url_encode($ent->getEntityId()) . '/metadata.xml';
                $srv_circle_metalink_signed = base_url() . 'signedmetadata/provider/' . base64url_encode($ent->getEntityId()) . '/metadata.xml';

                $d[++$i]['name'] = lang('rr_circleoftrust');
                $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . ':</span> <span class="accordionContent"><br />' . $srv_circle_metalink . '&nbsp;</span>&nbsp; ' . anchor($srv_circle_metalink, '<i class="fi-arrow-right"></i>', 'class="showmetadata"');
                $d[++$i]['name'] = lang('rr_circleoftrust') . '<i>(' . lang('signed') . ')</i>';
                $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . ':</span> <span class="accordionContent"><br />' . $srv_circle_metalink_signed . '&nbsp;</span>&nbsp; ' . anchor_popup($srv_circle_metalink_signed, '<i class="fi-arrow-right"></i>');
            }
        }
        if ($isLocal && $hasWriteAccess && !empty($gearman_enabled) && $circleEnabled)
        {
            $d[++$i]['name'] = lang('signmetadata') . showBubbleHelp(lang('rhelp_signmetadata'));
            $d[$i]['value'] = '<a href="' . base_url() . 'msigner/signer/provider/' . $ent->getId() . '" id="providermetasigner" class="button tiny">' . lang('btn_signmetadata') . '</a>';
        }
        $wayfhide = false;

        if ((isset($feathide['discojuice']) && $feathide['discojuice'] === true) || (isset($featdisable['discojuice']) && $featdisable['discojuice'] === true))
        {
            $wayfhide = true;
        }
        if ($sppart && !$wayfhide)
        {
            $d[++$i]['header'] = 'WAYF';
            $d[++$i]['name'] = lang('rr_ds_json_url') . ' <div class="dhelp">' . lang('entdswayf') . '</div>';

            $d[$i]['value'] = anchor(base_url() . 'disco/circle/' . base64url_encode($ent->getEntityId()) . '/metadata.json?callback=dj_md_1', lang('rr_link'));

            $tmpwayflist = $ent->getWayfList();
            if (!empty($tmpwayflist) && is_array($tmpwayflist))
            {
                if (isset($tmpwayflist['white']))
                {
                    if (is_array($tmpwayflist['white']))
                    {
                        $discolist = implode('<br />', array_values($tmpwayflist['white']));
                        $d[++$i]['name'] = lang('rr_ds_white');
                        $d[$i]['value'] = $discolist;
                    }
                }
                elseif (isset($tmpwayflist['black']) && is_array($tmpwayflist['black']) && count($tmpwayflist['black']) > 0)
                {
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
        $federationsString = "";
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $membership = $ent->getMembership();
        $membershipNotLeft = array();
        $showMetalinks = TRUE;

        if (isset($feathide['metasonprov']) && $feathide['metasonprov'] === true)
        {
            $showMetalinks = FALSE;
        }
        if (!empty($membership))
        {
            $federationsString = '<ul class="no-bullet">';
            foreach ($membership as $f)
            {
                $joinstate = $f->getJoinState();
                if ($joinstate === 2)
                {
                    continue;
                }
                $membershipNotLeft[] = 1;
                $membershipDisabled = '';
                if ($f->isDisabled())
                {
                    $membershipDisabled = makeLabel('disabled', lang('membership_inactive'), lang('membership_inactive'));
                }
                $membershipBanned = '';
                if ($f->isBanned())
                {
                    $membershipBanned = makeLabel('disabled', lang('membership_banned'), lang('membership_banned'));
                }
                $fedActive = $f->getFederation()->getActive();

                $fedlink = base_url('federations/manage/show/' . base64url_encode($f->getFederation()->getName()));

                if ($showMetalinks)
                {
                    $metalink = base_url('metadata/federation/' . $f->getFederation()->getSysname() . '/metadata.xml');
                    if ($fedActive)
                    {
                        $federationsString .= '<li>' . $membershipDisabled . '  ' . $membershipBanned . ' ' . anchor($fedlink, $f->getFederation()->getName()) . ' <span class="accordionButton">' . lang('rr_metadataurl') . ':</span><span class="accordionContent"><br />' . $metalink . '&nbsp;</span> &nbsp;&nbsp;' . anchor($metalink, '<i class="fi-arrow-right"></i>', 'class="showmetadata"') . '</li>';
                    }
                    else
                    {
                        $federationsString .= '<li>' . $membershipDisabled . ' ' . $membershipBanned . ' ' . makeLabel('disabled', lang('rr_fed_inactive_full'), lang('rr_fed_inactive_full')) . ' ' . anchor($fedlink, $f->getFederation()->getName()) . ' <span class="accordionButton">' . lang('rr_metadataurl') . ':</span><span class="accordionContent"><br />' . $metalink . '&nbsp;</span> &nbsp;&nbsp;' . anchor($metalink, '<i class="fi-arrow-right"></i>', 'class="showmetadata"') . '</li>';
                    }
                }
                else
                {
                    if ($fedActive)
                    {
                        $federationsString .= '<li>' . $membershipDisabled . '  ' . $membershipBanned . ' ' . anchor($fedlink, $f->getFederation()->getName()) . ' </li>';
                    }
                    else
                    {
                        $federationsString .= '<li>' . $membershipDisabled . ' ' . $membershipBanned . ' ' . makeLabel('disabled', lang('rr_fed_inactive_full'), lang('rr_fed_inactive_full')) . ' ' . anchor($fedlink, $f->getFederation()->getName()) . '</li>';
                    }
                }
            }
            $federationsString .='</ul>';
            $manage_membership = '';
            $no_feds = $membership->count();
            if ($no_feds > 0 && $hasWriteAccess)
            {
                if (!$isLocked)
                {
                    $manage_membership .= '<div><a href="' . base_url() . 'manage/leavefed/leavefederation/' . $ent->getId() . '" class="button tiny alert">' . lang('rr_federationleave') . '</a></div>';
                    $entmenu[11] = array('name' => lang('rr_federationleave'), 'link' => '' . base_url() . 'manage/leavefed/leavefederation/' . $ent->getId() . '', 'class' => '');
                }
                else
                {
                    $manage_membership .= '<b>' . lang('rr_federationleave') . '</b> ' . $lockicon . ' <br />';
                }
            }
            if ($hasWriteAccess && (count($membershipNotLeft) < count($all_federations)))
            {
                if (!$isLocked)
                {
                    $manage_membership .= '<div><a href="' . base_url() . 'manage/joinfed/joinfederation/' . $ent->getId() . '" class="button tiny">' . lang('rr_federationjoin') . '</a></div>';
                    $entmenu[10] = array('name' => lang('rr_federationjoin'), 'link' => '' . base_url() . 'manage/joinfed/joinfederation/' . $ent->getId() . '', 'class' => '');
                }
                else
                {
                    $manage_membership .= '<b>' . lang('rr_federationjoin') . '</b> ' . $lockicon . '<br />';
                }
            }
        }
        $d[$i]['value'] = '<p>' . $federationsString . '</p>' . '<p>' . $manage_membership . '</p>';
        if ($no_feds > 0)
        {
            $d[++$i]['name'] = '';
            $d[$i]['value'] = '<a href="' . base_url() . 'providers/detail/showmembers/' . $id . '" id="getmembers"><button type="button" class="savebutton arrowdownicon small secondary">' . lang('showmemb_btn') . '</button></a>';

            $d[++$i]['2cols'] = '<div id="membership"></div>';
        }
        $result[] = array('section' => 'federation', 'title' => '' . lang('tabMembership') . '', 'data' => $d);


        $d = array();
        $i = 0;

        if ($is_static)
        {
            $tmp_st = $ent->getStaticMetadata();
            if (!empty($tmp_st))
            {
                $static_metadata = $tmp_st->getMetadata();
            }
            else
            {
                $static_metadata = null;
            }
            if (empty($static_metadata))
            {
                $d[++$i]['name'] = lang('rr_staticmetadataactive');
                $d[$i]['value'] = '<span class="alert">' . lang('rr_isempty') . '</span>';
            }
            else
            {
                $d[++$i]['header'] = lang('rr_staticmetadataactive');

                $d[++$i]['2cols'] = '<code>' . $this->CI->geshilib->highlight($static_metadata, 'xml', $params) . '</code>';
            }
            $subresult[20] = array('section' => 'staticmetadata', 'title' => '' . lang('tabStaticMeta') . '', 'data' => $d);
        }



        /**
         * SAMLTAB
         */
        $d = array();
        $i = 0;

        $services = array();
        $srvs = $ent->getServiceLocations();
        if ($srvs->count() > 0)
        {
            foreach ($srvs as $v)
            {
                $services[$v->getType()][] = $v;
            }
        }
        $d[++$i] = &$entityIdRecord;
        if ($idppart)
        {
            $d[++$i]['msection'] = 'IDPSSODescriptor';

            // protocols enumerations
            $d[++$i]['name'] = lang('rr_supportedprotocols');
            $v = implode('<br />', $ent->getProtocolSupport('idpsso'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_domainscope');
            $scopes = $ent->getScope('idpsso');
            $scopeString = '<ul class="no-bullet">';
            foreach ($scopes as $key => $value)
            {
                $scopeString .= '<li>' . $value . '</li>';
            }
            $scopeString .= '</ul>';
            $d[$i]['value'] = $scopeString;

            $d[++$i]['name'] = lang('rr_supportednameids');
            $nameids = '<ul class="no-bullet">';
            foreach ($ent->getNameIds('idpsso') as $r)
            {
                $nameids .= '<li>' . $r . '</li>';
            }
            $nameids .='</ul>';
            $d[$i]['value'] = trim($nameids);

            if (array_key_exists('SingleSignOnService', $services))
            {
                $ssovalues = '';
                $d[++$i]['name'] = 'SingleSignOnService';
                foreach ($services['SingleSignOnService'] as $s)
                {
                    $def = '';
                    if ($s->getDefault())
                    {
                        $def = '<i>(' . lang('rr_default') . ')</i>';
                    }
                    $ssovalues .= '<li data-jagger-checkurlalive="' . $s->getUrl() . '"><b>' . $def . ' ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $ssovalues . '</ul>';
            }
            if (array_key_exists('IDPSingleLogoutService', $services))
            {
                $d[++$i]['name'] = 'SingleLogoutService';
                $slvalues = '';
                foreach ($services['IDPSingleLogoutService'] as $s)
                {
                    $slvalues .= '<b> ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small><br />';
                }
                $d[$i]['value'] = $slvalues;
            }
            if (array_key_exists('IDPArtifactResolutionService', $services))
            {
                $d[++$i]['name'] = 'ArtifactResolutionService';
                $slvalues = '';
                foreach ($services['IDPArtifactResolutionService'] as $s)
                {
                    $slvalues .= '<b>' . $s->getUrl() . '</b> <small><i>index: ' . $s->getOrder() . '</i></small><br /><small>' . $s->getBindingName() . '</small><br />';
                }
                $d[$i]['value'] = $slvalues;
            }
            $d[++$i]['msection'] = 'AttributeAuthorityDescriptor';
            $d[++$i]['name'] = lang('rr_supportedprotocols') . '';
            $v = implode('<br />', $ent->getProtocolSupport('aa'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_domainscope') . '';
            $scopes = $ent->getScope('aa');
            $scopeString = '<ul class="no-bullet">';
            foreach ($scopes as $key => $value)
            {
                $scopeString .= '<li>' . $value . '</li>';
            }
            $scopeString .= '</ul>';
            $d[$i]['value'] = $scopeString;
            $aanameids = $ent->getNameIds('aa');
            if (count($aanameids) > 0)
            {
                $d[++$i]['name'] = lang('rr_supportednameids');
                $aanameid = '<ul class="no-bullet">';
                foreach ($aanameids as $r)
                {
                    $aanameid .= '<li>' . $r . '</li>';
                }
                $aanameid .= '</ul>';
                $d[$i]['value'] = trim($aanameid);
            }

            if (array_key_exists('IDPAttributeService', $services))
            {
                $d[++$i]['name'] = 'AttributeService';
                $slvalues = '';
                foreach ($services['IDPAttributeService'] as $s)
                {
                    $slvalues .= '<b>' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small><br />';
                }
                $d[$i]['value'] = $slvalues;
            }
        }
        if ($sppart)
        {
            $d[++$i]['msection'] = 'SPSSODescriptor';
            $d[++$i]['name'] = lang('rr_supportedprotocols');
            $v = implode('<br />', $ent->getProtocolSupport('spsso'));
            $d[$i]['value'] = $v;
            $nameids = '<ul class="no-bullet">';
            $d[++$i]['name'] = lang('rr_supportednameids');
            foreach ($ent->getNameIds('spsso') as $r)
            {
                $nameids .= '<li>' . $r . '</li>';
            }
            $nameids .='</ul>';
            $d[$i]['value'] = trim($nameids);
            if (array_key_exists('AssertionConsumerService', $services))
            {
                $acsvalues = '';
                $d[++$i]['name'] = 'AssertionConsumerService';
                foreach ($services['AssertionConsumerService'] as $s)
                {
                    $def = '';
                    if ($s->getDefault())
                    {
                        $def = '<i>(' . lang('rr_default') . ')</i>';
                    }
                    $acsvalues .= '<li><b>' . $def . ' ' . $s->getUrl() . '</b> <small><i>index: ' . $s->getOrder() . '</i></small><br /><small>' . $s->getBindingName() . ' </small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $acsvalues . '</ul>';
            }
            if (array_key_exists('SPArtifactResolutionService', $services))
            {
                $acsvalues = '';
                $d[++$i]['name'] = 'ArtifactResolutionService';
                foreach ($services['SPArtifactResolutionService'] as $s)
                {
                    $def = '';
                    if ($s->getDefault())
                    {
                        $def = '<i>(' . lang('rr_default') . ')</i>';
                    }
                    $acsvalues .= '<li><b>' . $def . ' ' . $s->getUrl() . '</b> <small><i>index: ' . $s->getOrder() . '</i></small><br /><small>' . $s->getBindingName() . ' </small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $acsvalues . '</ul>';
            }
            if (array_key_exists('SPSingleLogoutService', $services))
            {
                $d[++$i]['name'] = 'SingleLogoutService';
                $slvalues = '';
                foreach ($services['SPSingleLogoutService'] as $s)
                {
                    $slvalues .= '<li><b> ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
                }
                $d[$i]['value'] = '<ul class="no-bullet">' . $slvalues . '</ul>';
            }
            if (array_key_exists('RequestInitiator', $services) || array_key_exists('DiscoveryResponse', $services))
            {
                $d[++$i]['header'] = 'SPSSODescriptor/Extensions';
                if (array_key_exists('RequestInitiator', $services))
                {
                    $d[++$i]['name'] = 'RequestInitiator <br /><small>SPSSODescriptor/Extensions</small>';
                    $rivalues = '';
                    foreach ($services['RequestInitiator'] as $s)
                    {
                        $rivalues .= '<li><b>' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
                    }
                    $d[$i]['value'] = '<ul class="no-bullet">' . $rivalues . '</ul>';
                }
                if (array_key_exists('DiscoveryResponse', $services))
                {
                    $d[++$i]['name'] = 'DiscoveryResponse <br /><small>SPSSODescriptor/Extensions</small>';
                    $drvalues = '';
                    foreach ($services['DiscoveryResponse'] as $s)
                    {
                        $drvalues .= '<li><b>' . $s->getUrl() . '</b>&nbsp;&nbsp;<small><i>index:' . $s->getOrder() . '</i></small><br /><small>' . $s->getBindingName() . '</small></li>';
                    }
                    $d[$i]['value'] = '<ul class="no-bullet">' . $drvalues . '</ul>';
                }
            }
        }
        $subresult[6] = array('section' => 'samltab', 'title' => '' . lang('tabsaml') . '', 'data' => $d);
        $d = array();
        $i = 0;
        $tcerts = $ent->getCertificates();
        $certs = array();
        foreach ($tcerts as $c)
        {
            $certs[$c->getType()][] = $c;
        }
        if ($idppart)
        {
            $d[]['msection'] = 'IDPSSODescriptor';
            if (array_key_exists('idpsso', $certs))
            {
                foreach ($certs['idpsso'] as $v)
                {
                    $c = $this->_genCertView($v);
                    foreach ($c as $v)
                    {
                        $d[] = $v;
                    }
                }
            }
            // AA
            if (array_key_exists('aa', $certs))
            {
                $d[]['msection'] = 'AttributeAuthorityDescriptor';
                foreach ($certs['aa'] as $v)
                {
                    $c = $this->_genCertView($v);
                    foreach ($c as $v)
                    {
                        $d[] = $v;
                    }
                }
            }
        }
        if ($sppart)
        {
            $d[]['msection'] = 'SPSSODescriptor';
            if (array_key_exists('spsso', $certs))
            {
                foreach ($certs['spsso'] as $v)
                {
                    $c = $this->_genCertView($v);
                    foreach ($c as $v)
                    {
                        $d[] = $v;
                    }
                }
            }
        }
        /**
         * end certs
         */
        $subresult[11] = array('section' => 'certificates', 'title' => '' . lang('tabCerts') . '', 'data' => $d);
        $xmldata = $this->CI->providertoxml->entityConvertNewDocument($ent, array('attrs' => 1), TRUE);
        if (!empty($xmldata))
        {
            $xmlToHtml = $xmldata;
        }
        $xmlmetatitle = '<img src="' . base_url() . 'images/jicons/xml3.svg" style="height: 20px"/> ';
        $subresult[1] = array('section' => 'xmlmeta', 'title' => $xmlmetatitle, 'data' => '<code>' . $this->CI->geshilib->highlight($xmlToHtml, 'xml', $params) . '</code>');

        $d = array();
        if (count($entityCategories) == 0)
        {
            $d[]['2cols'] = '<div data-alert class="alert-box notice">' . lang('entcat_notdefined') . '</div>';
        }
        else
        {
            foreach ($entityCategories as $entcat)
            {
                $d[]['header'] = lang('title_entcat');
                $d[] = array('name' => lang('entcat_displayname'), 'value' => $entcat->getName());
                $d[] = array('name' => lang('rr_attr_name'), 'value' => $entcat->getSubtype());
                $d[] = array('name' => lang('entcat_value'), 'value' => $entcat->getUrl());
                $d[] = array('name' => lang('entcat_description'), 'value' => $entcat->getDescription());
                $entcatStatus = $entcat->getAvailable();
                if (!$entcatStatus)
                {
                    $d[] = array('name' => '', 'value' => '<div class="label alert">' . lang('rr_disabled') . '</div>');
                }
            }
        }
        $subresult[12] = array('section' => 'entcats', 'title' => '' . lang('tabEntcats') . '', 'data' => $d);



        $d = array();
        $i = 0;
        $contacts = $ent->getContacts();
        $contactsTypeToTranslate = array(
            'technical' => lang('rr_cnt_type_tech'),
            'administrative' => lang('rr_cnt_type_admin'),
            'support' => lang('rr_cnt_type_support'),
            'billing' => lang('rr_cnt_type_bill'),
            'other' => lang('rr_cnt_type_other')
        );
        if (count($contacts) > 0)
        {
            foreach ($contacts as $c)
            {
                $d[++$i]['header'] = lang('rr_contact');
                $d[++$i]['name'] = lang('type');
                $d[$i]['value'] = $contactsTypeToTranslate['' . strtolower($c->getType()) . ''];
                $d[++$i]['name'] = lang('rr_contactfirstname');
                $d[$i]['value'] = $c->getGivenname();
                $d[++$i]['name'] = lang('rr_contactlastname');
                $d[$i]['value'] = $c->getSurname();
                $d[++$i]['name'] = lang('rr_contactemail');
                $d[$i]['value'] = '<span data-jagger-contactmail="' . $c->getEmail() . '">' . $c->getEmail() . '</span>';
            }
        }
        else
        {
            $d[++$i]['2cols'] = '<div data-alert class="alert-box warning">' . lang('rr_notset') . '</div>';
        }
        $subresult[3] = array('section' => 'contacts', 'title' => '' . lang('tabContacts') . '', 'data' => $d);
        $d = array();
        $i = 0;
        if ($idppart)
        {
            $d[++$i]['header'] = '<a name="arp"></a>' . lang('rr_arp');
            $encoded_entityid = base64url_encode($ent->getEntityId());
            $arp_url = base_url() . 'arp/format2/' . $encoded_entityid . '/arp.xml';
            $d[++$i]['name'] = lang('rr_individualarpurl');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_arpurl') . '</span><span class="accordionContent"><br />' . $arp_url . '&nbsp;</span>&nbsp;' . anchor_popup($arp_url, '<i class="fi-arrow-right"></i>');
            //

            $exc = $ent->getExcarps();
            if (!$isLocked && $hasWriteAccess && $ent->getLocal())
            {

                $mlink = '';
                $entmenu[20] = array('label' => '' . lang('rr_attributes') . '');
                $entmenu[21] = array('name' => lang('rr_arpexclist_edit'), 'link' => '' . base_url() . 'manage/arpsexcl/idp/' . $ent->getId() . '', 'class' => '');
                $d[++$i]['name'] = lang('rr_arpexclist_title') . ' <br />' . $mlink;
                if (is_array($exc) && count($exc) > 0)
                {
                    $l = '<ul class="no-bullet">';
                    foreach ($exc as $e)
                    {
                        $l .= '<li>' . $e . '</li>';
                    }
                    $l .= '</ul>';
                    $d[$i]['value'] = $l;
                }
                else
                {
                    $d[$i]['value'] = '';
                }
            }
            $d[++$i]['name'] = lang('rr_arpoverview');
            $d[$i]['value'] = anchor(base_url('reports/idpmatrix/show/' . $ent->getId()), 'matrix', 'class="editbutton"');
        }
        /**
         * supported attributes by IDP part
         */
        if ($idppart)
        {
            $image_link = '<i class="fi-pencil"></i>';
            if ($hasWriteAccess)
            {
                $entmenu[20] = array('label' => '' . lang('rr_attributes') . '');
                $entmenu[22] = array('name' => '' . lang('rr_supportedattributes') . '', 'link' => '' . base_url() . 'manage/supported_attributes/idp/' . $id . '', 'class' => '');
                $entmenu[23] = array('name' => '' . lang('rr_attributepolicy') . '', 'link' => '' . base_url() . 'manage/attributepolicy/globals/' . $id . '', 'class' => '');
            }

            $d[++$i]['header'] = '<a name="attrs"></a>' . lang('rr_supportedattributes') . ' ' . $edit_attributes;
            $tmpAttrs = new models\AttributeReleasePolicies;
            $supportedAttributes = $tmpAttrs->getSupportedAttributes($ent);
            foreach ($supportedAttributes as $s)
            {
                $d[++$i]['name'] = $s->getAttribute()->getName();
                $d[$i]['value'] = $s->getAttribute()->getDescription();
            }

            $d[++$i]['header'] = lang('rr_defaultspecificarp') . $edit_policy;
            $disable_caption = true;
            $d[++$i]['2cols'] = $this->CI->show_element->generateTableDefaultArp($ent, $disable_caption);
        }
        /**
         * required attributes by SP part
         */
        if ($sppart)
        {
            $edit_req_attrs_link = '';

            if ($hasWriteAccess)
            {
                $entmenu[20] = array('label' => '' . lang('rr_attributes') . '');
                $d[++$i]['name'] = lang('rr_attrsoverview');
                $d[$i]['value'] = anchor(base_url() . 'reports/sp_matrix/show/' . $ent->getId(), lang('rr_attrsoverview'), 'class="button small editbutton"');

                $image_link = '<i class="fi-pencil"></i>';
                $edit_req_attrs_link = '<span style="float: right;"><a href="' . base_url() . 'manage/attribute_requirement/sp/' . $ent->getId() . '" class="editbutton editicon" title="edit" >' . lang('rr_edit') . '</a></span>';
                $entmenu[24] = array('name' => '' . lang('rr_requiredattributes') . '', 'link' => '' . base_url() . 'manage/attribute_requirement/sp/' . $ent->getId() . '', 'class' => '');
            }
            $requiredAttributes = $ent->getAttributesRequirement();
            if ($requiredAttributes->count() === 0)
            {
                $d[++$i]['name'] = '';
                $d[$i]['value'] = '<div data-alert class="alert-box warning">' . lang('rr_noregspecified_inherit_from_fed') . '</div>';
            }
            else
            {
                foreach ($requiredAttributes as $v)
                {
                    $d[++$i]['name'] = $v->getAttribute()->getName();
                    $d[$i]['value'] = '<b>' . $v->getStatus() . '</b>: <i>(' . $v->getReason() . ')</i>';
                }
            }
        }
        $subresult[13] = array('section' => 'attrs', 'title' => '' . lang('tabAttrs') . '', 'data' => $d);
        $d = array();
        $i = 0;


        if ($idppart)
        {
            $uiiarray = array();
            $d[++$i]['msection'] = lang('identityprovider');
            foreach ($extend as $e)
            {
                if ($e->getNamespace() == 'mdui' && $e->getType() == 'idp')
                {
                    $uiiarray[$e->getElement()][] = $e;
                }
            }
            $discohintsarray = &$uiiarray;
            $d[++$i]['name'] = lang('e_idpservicename');
            if (isset($uiiarray['DisplayName']))
            {
                $str = '';
                foreach ($uiiarray['DisplayName'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('e_idpservicedesc');
            if (isset($uiiarray['Description']))
            {
                $str = '';
                foreach ($uiiarray['Description'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('e_idpserviceinfourl');
            if (isset($uiiarray['InformationURL']))
            {
                $str = '';
                foreach ($uiiarray['InformationURL'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('e_idpserviceprivacyurl');
            if (isset($uiiarray['PrivacyStatementURL']))
            {
                $str = '';
                foreach ($uiiarray['PrivacyStatementURL'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $logoatts = array(
                'width' => '400',
                'height' => '200',
                'scrollbars' => 'yes',
                'status' => 'yes',
                'resizable' => 'yes',
                'screenx' => '0',
                'screeny' => '0'
            );
            $d[++$i]['name'] = lang('rr_logoofservice');
            if (isset($uiiarray['Logo']))
            {
                $str = '';
                foreach ($uiiarray['Logo'] as $v)
                {
                    $str .= @anchor_popup($v->getLogoValue(), $v->getLogoValue(), $logoatts) . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
        }
        if ($sppart)
        {
            $uiiarray = array();
            $d[++$i]['msection'] = lang('serviceprovider');
            foreach ($extend as $e)
            {
                if ($e->getNamespace() == 'mdui' && $e->getType() == 'sp')
                {
                    $uiiarray[$e->getElement()][] = $e;
                }
            }
            $d[++$i]['name'] = lang('e_spservicename');
            if (isset($uiiarray['DisplayName']))
            {
                $str = '';
                foreach ($uiiarray['DisplayName'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('e_spservicedesc');
            if (isset($uiiarray['Description']))
            {
                $str = '';
                foreach ($uiiarray['Description'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('e_spserviceprivacyurl');
            if (isset($uiiarray['PrivacyStatementURL']))
            {
                $str = '';
                foreach ($uiiarray['PrivacyStatementURL'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('e_spserviceinfourl');
            if (isset($uiiarray['InformationURL']))
            {
                $str = '';
                foreach ($uiiarray['InformationURL'] as $v)
                {
                    $attr = $v->getAttributes();
                    $str .= '<b>' . $attr['xml:lang'] . ':</b> ' . $v->getEvalue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = lang('rr_logos');
            if (isset($uiiarray['Logo']))
            {
                $str = '';
                foreach ($uiiarray['Logo'] as $v)
                {
                    $str .= anchor($v->getLogoValue()) . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
        }

        $subresult[4] = array('section' => 'uii', 'title' => '' . lang('tabUII') . '', 'data' => $d);

        if ($idppart)
        {
            $d = array();
            $i = 0;
            $d[++$i]['name'] = lang('rr_geolocation');
            if (isset($discohintsarray['GeolocationHint']))
            {
                $str = '';
                foreach ($discohintsarray['GeolocationHint'] as $v)
                {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = 'IPHint';
            if (isset($discohintsarray['IPHint']))
            {
                $str = '';
                foreach ($discohintsarray['IPHint'] as $v)
                {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
            $d[++$i]['name'] = 'DomainHint';
            if (isset($discohintsarray['DomainHint']))
            {
                $str = '';
                foreach ($discohintsarray['DomainHint'] as $v)
                {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }


            $subresult[5] = array('section' => 'uiihints', 'title' => 'UI Hints', 'data' => $d);
        }
        $d = array();
        $i = 0;
        if ($hasManageAccess)
        {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' ' . anchor(base_url() . 'manage/entitystate/modify/' . $id, '<i class="fi-arrow-right"></i>');
            if (!$isActive)
            {
                $d[$i]['value'] .= '<div>' . lang('rr_rmprovider') . ' ' . anchor(base_url() . 'manage/premoval/providertoremove/' . $id, '<i class="fi-arrow-right"></i>') . '</div>';
            }
            else
            {
                $d[$i]['value'] .= '<div>' . lang('rr_rmprovider') . '<span class="alert"><i class="fi-prohibited"></i></span> <div class="alert">' . lang('rmproviderdisablefirst') . '</div></div>';
            }
        }
        elseif ($hasWriteAccess)
        {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' <span class="alert"><i class="fi-prohibited"></i></span><div class="alert">' . lang('rerror_managepermneeded') . '</div>';
            $d[$i]['value'] .= '<div>' . lang('rr_rmprovider') . '<i class="fi-prohibited"></i>div class="alert">' . lang('rerror_managepermneeded') . '</div> </div>';
        }
        else
        {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' <span class="alert"><i class="fi-prohibited"></i></span>';
        }
        $d[++$i]['name'] = '';
        if ($hasManageAccess)
        {
            $d[$i]['value'] = lang('rr_displayaccess') . anchor(base_url() . 'manage/access_manage/entity/' . $id, '<i class="fi-arrow-right"></i>');
        }
        else
        {
            $d[$i]['value'] = lang('rr_displayaccess') . '';
        }
        if (($hasManageAccess || $hasWriteAccess) && $isLocal)
        {

            $d[++$i] = array('name' => lang('regpols_menulink'), 'value' => '<a href="' . base_url() . 'manage/entitystate/regpolicies/' . $ent->getId() . '" class="button tiny">' . lang('rr_edit') . '');
        }

        ksort($subresult);

        $finalsubtab = &$subresult;
        $result[] = array('section' => 'samlmetadata', 'title' => 'Metadata', 'subtab' => $finalsubtab);
        $result[] = array('section' => 'mngt', 'title' => '' . lang('tabMngt') . '', 'data' => $d);
        $d = array();
        $i = 0;



        $data['tabs'] = $result;
        Detail::$alerts = $alerts;
        return $data;
    }

}
