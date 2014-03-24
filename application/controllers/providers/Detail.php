<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
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
 * Provider_detail Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Detail extends MY_Controller {

    private
            $logo_url;
    private
            $tmp_attributes;

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if ($loggedin)
        {
            $this->load->library(array('table', 'geshilib', 'zacl', 'show_element'));
            $this->logo_basepath = $this->config->item('rr_logouriprefix');
            $this->logo_baseurl = $this->config->item('rr_logobaseurl');
            if (empty($this->logo_baseurl))
            {
                $this->logo_baseurl = base_url();
            }
            $this->logo_url = $this->logo_baseurl . $this->logo_basepath;
            $this->tmp_attributes = new models\Attributes;
            $this->tmp_attributes->getAttributes();
        }
        elseif (!$this->input->is_ajax_request())
        {
            redirect('auth/login', 'location');
        }
    }

    function refreshentity($id)
    {
       if ($this->input->is_ajax_request())
       {
           if (!$this->j_auth->logged_in())
           {
              set_status_header(403);
              echo 'no user session';
              return;
           }
           if(!is_numeric($id))
           {
              set_status_header(403);
              echo 'received incorrect params';
              return;
           }
           $has_write_access = $this->zacl->check_acl($id, 'write', 'entity', '');
           log_message('debug','TEST access '.$has_write_access);
           if ($has_write_access === TRUE)
           {
               log_message('debug','TEST access '.$has_write_access);
               $id=trim($id);
               $keyPrefix = getCachePrefix();
               $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
               $cache1 = 'mcircle_' . $id;
               $this->cache->delete($cache1);
               $arpByInherit = $this->config->item('arpbyinherit');
               if(!empty($arpByInherit))
               {
                   $cache2 = 'arp2_'.$id;
                   $this->cache->delete($cache2);
                   $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($id), -1);

               }
               else
               {
                   $cache2 = 'arp_'.$id;
                   $this->cache->delete($cache2);
                   $this->j_cache->library('arp_generator', 'arpToArray', array($id), -1);
               }
               echo 'OK';
               return TRUE;
           }
           else
           {
              set_status_header(403);
              echo 'access denied';
              return;
           } 
       }
       else
       {
           show_error('Access denied', 403);
       }
    }

    function showlogs($id)
    {
        if ($this->input->is_ajax_request())
        {
            if (!$this->j_auth->logged_in())
            {
               set_status_header(403);
               echo 'no session';
               return;
            }
            $d = array();
            $group = 'entity';
            $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
            if (!empty($ent))
            {
                $has_write_access = $this->zacl->check_acl($id, 'write', $group, '');
                if ($has_write_access === TRUE)
                {

                    $i = 0;
                    /**
                     * @todo remove stats link later
                     */
                    $isactive = $ent->getActive();
                    $islocal = $ent->getLocal();
                    $isgearman = $this->config->item('gearman');
                    $isstats = $this->config->item('statistics');
                    if (($isactive === TRUE) && ($islocal === TRUE) && !empty($isgearman) && ($isgearman === TRUE) && !empty($isstats))
                    {
                        $d[++$i]['header'] = 'Statistics';
                        $d[++$i]['name'] = anchor(base_url() . 'manage/statdefs/show/' . $ent->getId() . '', lang('statsmngmt'));
                        $d[$i]['value'] = anchor(base_url() . 'manage/statdefs/show/' . $ent->getId() . '', '<img src="'.base_url().'images/stats_bars.png">');
                    }
                    $d[++$i]['header'] = lang('rr_logs');
                    $d[++$i]['name'] = lang('rr_variousreq');
                    $d[$i]['value'] =$this->show_element->generateRequestsList($ent, 10);
                    $d[++$i]['name'] = lang('rr_modifications');
                    $d[$i]['value'] = $this->show_element->generateModificationsList($ent, 10);
                    if ((strcasecmp($ent->getType(), 'IDP') == 0) OR (strcasecmp($ent->getType(), 'BOTH') == 0))
                    {
                        $tmp_logs = new models\Trackers;
                        $arp_logs = $tmp_logs->getArpDownloaded($ent);
                        $logg_tmp = '<ul>';
                        if (!empty($arp_logs))
                        {
                            foreach ($arp_logs as $l)
                            {
                                $logg_tmp .= '<li><b>' . date('Y-m-d H:i:s',$l->getCreated()->format('U')+j_auth::$timeOffset) . '</b> - ' . $l->getIp() . ' <small><i>(' . $l->getAgent() . ')</i></small></li>';
                            }
                        }
                        $logg_tmp .= '</ul>';
                        $d[++$i]['name'] = lang('rr_recentarpdownload');
                        $d[$i]['value'] = $logg_tmp;
                    }
                }
                else
                {
                    log_message('debug', 'no access to load logs tab');
                }
            }
            $data['d'] = $d;
            $this->load->view('providers/showlogs_view.php', $data);
        }
        else
        {
            echo '';
        }
    }

    function show($id)
    {
        
        if (empty($id) or !ctype_digit($id))
        {
            show_error(lang('error404'), 404);
            return;
        }
        $tmp_providers = new models\Providers();
        $ent = $tmp_providers->getOneById($id);
        if (empty($ent))
        {
            show_error(lang('error404'), 404);
            return;
        }
        $alerts = array();
        $is_static = $ent->getStatic();
        
        $params = array(
            'enable_classes' => true,
        );
        $sppart = FALSE;
        $idppart = FALSE;
        $type = strtolower($ent->getType());
        $data['type'] = $type;
        $group = 'entity';
        $entstatus = '';
        $edit_attributes = '';
        $edit_policy = '';


        if ($type === 'idp')
        {
            $idppart = TRUE;
            $data['presubtitle'] = lang('identityprovider');
        }
        elseif ($type === 'sp')
        {
            $sppart = TRUE;
            $data['presubtitle'] = lang('serviceprovider');
        }
        elseif ($type === 'both')
        {
            $sppart = TRUE;
            $idppart = TRUE;
            $data['presubtitle'] = lang('rr_asboth');
        }
        $has_read_access = $this->zacl->check_acl($id, 'read', $group, '');
        $has_write_access = $this->zacl->check_acl($id, 'write', $group, '');
        $has_manage_access = $this->zacl->check_acl($id, 'manage', $group, '');
        if (!$has_read_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang("rr_nospaccess");
            $this->load->view('page', $data);
            return;
        }
        $is_validtime = $ent->getIsValidFromTo();
        $is_active = $ent->getActive();
        $is_local = $ent->getLocal();
        $is_publiclisted = $ent->getPublicVisible();
        $locked = $ent->getLocked();
        $lockicon = genIcon('locked', lang('rr_locked'));
        $edit_link = '';
        if(empty($is_publiclisted))
        {
            $entstatus .= ' ' . makeLabel('disabled', lang('lbl_publichidden'), lang('lbl_publichidden'));
        }
        if (empty($is_active))
        {
            $entstatus .= ' ' . makeLabel('disabled', lang('lbl_disabled'), lang('lbl_disabled'));
        }
        else
        {
            $entstatus .= ' ' . makeLabel('active', lang('lbl_enabled'), lang('lbl_enabled'));
        }
        if (!$is_validtime)
        {
            $entstatus .= ' ' . makeLabel('alert', lang('rr_validfromto_notmatched1'), strtolower(lang('rr_metadata')) . ' ' . lang('rr_expired'));
        }
        if ($locked)
        {
            $entstatus .= ' ' . makeLabel('locked', lang('rr_locked'), lang('rr_locked'));
        }
        if ($is_local)
        {
            $entstatus .= ' ' . makeLabel('local', lang('rr_managedlocally'), lang('rr_managedlocally'));
        }
        else
        {
            $entstatus .= ' ' . makeLabel('local', lang('rr_external'), lang('rr_external'));
        }
        if ($is_static)
        {
            $entstatus .= ' ' . makeLabel('static', lang('lbl_static'), lang('lbl_static'));
            $alerts[] = lang('staticmeta_info');
            
        }

        if (!$has_write_access)
        {
            $edit_link .= makeLabel('noperm', lang('rr_nopermission'), lang('rr_nopermission'));
        }
        elseif (!$is_local)
        {
            $edit_link .= makeLabel('external', lang('rr_externalentity'), lang('rr_external'));
        }
        elseif ($locked)
        {
            $edit_link .= makeLabel('locked', lang('rr_lockedentity'), lang('rr_lockedentity'));
        }
        else
        {
            $edit_link .= '<a href="' . base_url() . 'manage/entityedit/show/' . $id . '" class="editbutton editicon" id="editprovider" title="edit" >' . lang('rr_edit') . '</a>';
            $data['showclearcache'] = TRUE;
        }
        $data['edit_link'] = $edit_link;


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
                $data['provider_logo_url'] = $v->getLogoValue();
                $is_logo = TRUE;
            }
        }


        $data['entid'] = $ent->getId();
        $lang = MY_Controller::getLang();
        $data['name'] = $ent->getNameToWebInLang($lang,$type);
        if (empty($data['name']))
        {
            $data['name'] = $ent->getEntityId();
        }
        $this->title = lang('rr_providerdetails') . ' :: ' . $data['name'];
        $b = $this->session->userdata('board');
        if (!empty($b) && is_array($b))
        {
            if (($type == 'idp' or $type == 'both') && isset($b['idp'][$id]))
            {
                $data['bookmarked'] = true;
            }
            elseif (($type == 'sp' or $type == 'both') && isset($b['sp'][$id]))
            {
                $data['bookmarked'] = true;
            }
        }


        /**
         * BASIC
         */
        $d = array();
        $i = 0;
        $d[++$i]['header'] = '<span id="basic"></span>' . lang('rr_basicinformation');
        $d[++$i]['name'] = lang('rr_status') . ' ' . showBubbleHelp('<ul><li><b>' . lang('lbl_enabled') . '</b>:' . lang('provinmeta') . '</li><li><b>' . lang('lbl_disabled') . '</b>:' . lang('provexclmeta') . ' </li><li><b>' . lang('rr_managedlocally') . '</b>: ' . lang('provmanlocal') . '</li><li><b>' . lang('rr_external') . '</b>: ' . lang('provexternal') . '</li></ul>') . '';

        $d[$i]['value'] = '<b>' . $entstatus . '</b>';
        $d[++$i]['name'] = lang('rr_lastmodification');
        $d[$i]['value'] = '<b>' . date('Y-m-d H:i:s',$ent->getLastModified()->format('U')+j_auth::$timeOffset) . '</b>';
        $d[++$i]['name'] = lang('rr_entityid');
        $d[$i]['value'] = $ent->getEntityId();
        $d[++$i]['name'] = lang('e_orgname');
        $d[$i]['value'] = $ent->getName();
        $lname = $ent->getLocalName();
        $lvalues = '';
        if (count($lname)>0)
        {
            $d[++$i]['name'] = '';
            foreach ($lname as $k => $v)
            {
                $lvalues .= '<b>' . $k . ':</b> ' . $v . '<br />';
            }
            $d[$i]['value'] = $lvalues;
        }
        $d[++$i]['name'] = lang('e_orgdisplayname');
        $d[$i]['value'] = '<div id="selectme">' . $ent->getDisplayName() . '</div>';
        $ldisplayname = $ent->getLocalDisplayName();
        $lvalues = '';
        if (count($ldisplayname)>0)
        {
            $d[++$i]['name'] = '';
            foreach ($ldisplayname as $k => $v)
            {
                $lvalues .= '<b>' . $k . ':</b> ' . $v . '<br />';
            }
            $d[$i]['value'] = $lvalues;
        }
        $d[++$i]['name'] = lang('e_orgurl');
        $d[$i]['value'] = $ent->getHelpdeskUrl();
        $localizedHelpdesk = $ent->getLocalHelpdeskUrl();
        if(is_array($localizedHelpdesk) && count($localizedHelpdesk)>0)
        {
           $d[++$i]['name'] = '';;
           $lvalues = '';
           foreach($localizedHelpdesk as $k=>$v)
           {
                $lvalues .= '<b>' . $k . ':</b> <div>' . $v . '</div>';
              
           }
           $d[$i]['value'] = $lvalues;
        }
        $d[++$i]['name'] = lang('rr_regauthority');
        $regauthority = $ent->getRegistrationAuthority();
        $confRegAuth = $this->config->item('registrationAutority');
        $confRegLoad = $this->config->item('load_registrationAutority');
        $confRegistrationPolicy = $this->config->item('registrationPolicy');
        $regauthoritytext = null;
        if (empty($regauthority))
        {
            if ($is_local && !empty($confRegLoad) && !empty($confRegAuth))
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
            $d[$i]['value'] = date('Y-m-d',$regdate->format('U')+j_auth::$timeOffset);
        }
        else
        {
            $d[$i]['value'] = null;
        }
        $regpolicy = $ent->getRegistrationPolicy();
        $regpolicy_value = '';
        if (count($regpolicy) > 0)
        {
            foreach ($regpolicy as $rkey => $rvalue)
            {
                $regpolicy_value .= '<b>' . $rkey . ':</b> ' . $rvalue . '<br />';
            }
        }
        elseif (!empty($confRegistrationPolicy) && !empty($confRegLoad))
        {
            $regpolicy_value .= '<b>en:</b> ' . $confRegistrationPolicy . ' <br /><small><i>' . lang('loadedfromglobalcnf') . '</i></small>';
        }
        $d[++$i]['name'] = lang('rr_regpolicy');
        $d[$i]['value'] = $regpolicy_value;
        $d[++$i]['name'] = lang('rr_description'). ' <div class="dhelp">'.lang('defaultdesc').'</div>';
        $d[$i]['value'] = $ent->getDescription();

        $d[++$i]['name'] = lang('rr_defaultprivacyurl');
        $d[$i]['value'] = $ent->getPrivacyUrl();
        $d[++$i]['name'] = lang('rr_coc');
        $coc = $ent->getCoc();
        if (!empty($coc))
        {
            $cocvalue = $coc->getName() . '<br />' . anchor($coc->getUrl());
            if (!$coc->getAvailable())
            {
                $cocvalue .= makeLabel('disabled', lang('rr_disabled'), lang('rr_disabled'));
            }
        }
        else
        {
            $cocvalue = lang('rr_notset');
        }
        $d[$i]['value'] = $cocvalue;

        $d[++$i]['name'] = lang('rr_validfromto'). ' <div class="dhelp">'.lang('d_validfromto').'</div>';
        if ($ent->getValidFrom())
        {
            $validfrom = date('Y M d',$ent->getValidFrom()->format('U')+j_auth::$timeOffset);
        }
        else
        {
            $validfrom = lang('rr_unlimited');
        }
        if ($ent->getValidTo())
        {
            $validto = date('Y M d',$ent->getValidTo()->format('U')+j_auth::$timeOffset);
        }
        else
        {
            $validto = lang('rr_unlimited');
        }
        if ($is_validtime)
        {
            $d[$i]['value'] = $validfrom . ' <b>--</b> ' . $validto;
        }
        else
        {
            $d[$i]['value'] = '<span class="lbl lbl-alert">' . $validfrom . ' <b>--</b> ' . $validto . '</span>';
        }
        $d[++$i]['name'] = lang('rr_homeurl').' <div class="dhelp">'.lang('optinforpurposeonly').'</div>';

        $d[$i]['value'] = $ent->getHomeUrl() . ' <br /><small>' . lang('rr_notincludedmetadata') . '</small>';
        $result[] = array('section' => 'general', 'title' => '' . lang('tabGeneral') . '', 'data' => $d);




        /**
         * Metadata urls
         */
        $d = array();
        $i = 0;

        $d[++$i]['header'] = lang('rr_metadata');
        $srv_metalink = base_url("metadata/service/" . base64url_encode($ent->getEntityId()) . "/metadata.xml");

        $disable_extcirclemeta = $this->config->item('disable_extcirclemeta');
        $gearman_enabled = $this->config->item('gearman');

        if (!$is_local && !empty($disable_extcirclemeta) && $disable_extcirclemeta === TRUE)
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
            $d[++$i]['name'] = '<a name="metadata"></a>' . lang('rr_servicemetadataurl');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . '</span><span class="accordionContent"><br />' . $srv_metalink . '&nbsp;</span>&nbsp; ' . anchor($srv_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>','class="showmetadata"');

            $d[++$i]['name'] = lang('rr_circleoftrust');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . '</span><span class="accordionContent"><br />' . $srv_circle_metalink . '&nbsp;</span>&nbsp; ' . anchor($srv_circle_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>', 'class="showmetadata"');
            $d[++$i]['name'] = lang('rr_circleoftrust') . '<i>(' . lang('signed') . ')</i>';
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . '</span><span class="accordionContent"><br />' . $srv_circle_metalink_signed . '&nbsp;</span>&nbsp; ' . anchor_popup($srv_circle_metalink_signed, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        }
        if ($is_local && $has_write_access && !empty($gearman_enabled))
        {
            $d[++$i]['name'] = lang('signmetadata') . showBubbleHelp(lang('rhelp_signmetadata'));
            $d[$i]['value'] = '<a href="' . base_url() . 'msigner/signer/provider/' . $ent->getId() . '" id="providermetasigner"/><button type="button" class="savebutton staricon">' . lang('btn_signmetadata') . '</button></a>';
        }
        if ($sppart)
        {
            $d[++$i]['header'] = 'WAYF';
            $d[++$i]['name'] = lang('rr_ds_json_url'). ' <div class="dhelp">'.lang('entdswayf').'</div>';
            
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
        $d[++$i]['header'] = '<span id="federation"></span>' . lang('rr_federation');
        $d[++$i]['name'] = lang('rr_memberof');
        $federationsString = "";
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $membership = $ent->getMembership();
        $membershipNotLeft = array();
        if (!empty($membership))
        {
            $federationsString = '<ul>';
            foreach ($membership as $f)
            {
                $joinstate = $f->getJoinState();
                if($joinstate === 2)
                {
                   continue;
                }
                $membershipNotLeft[] = 1;
                $membershipDisabled = '';
                if($f->getIsDisabled())
                {
                    $membershipDisabled = makeLabel('disabled',lang('membership_inactive'),lang('membership_inactive'));
                }
                $membershipBanned = '';
                if($f->getIsBanned())
                {
                    $membershipBanned = makeLabel('disabled',lang('membership_banned'),lang('membership_banned'));
                }
                $fedlink = base_url('federations/manage/show/' . base64url_encode($f->getFederation()->getName()));
                $metalink = base_url('metadata/federation/' . base64url_encode($f->getFederation()->getName()) . '/metadata.xml');
                $fedActive = $f->getFederation()->getActive();
                if($fedActive)
                {
                    $federationsString .= '<li>'. $membershipDisabled .'  '.$membershipBanned . ' ' .anchor($fedlink, $f->getFederation()->getName()) . ' <span class="accordionButton">' . lang('rr_metadataurl') . ':</span><span class="accordionContent"><br />' . $metalink . '&nbsp;</span> &nbsp;&nbsp;' . anchor($metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>','class="showmetadata"') . '</li>';
                }
                else
                {
                    $federationsString .= '<li>'.$membershipDisabled .' '.$membershipBanned.' ' .makeLabel('disabled',lang('rr_fed_inactive_full'),lang('rr_fed_inactive_full')). ' '.anchor($fedlink, $f->getFederation()->getName()) . ' <span class="accordionButton">' . lang('rr_metadataurl') . ':</span><span class="accordionContent"><br />' . $metalink . '&nbsp;</span> &nbsp;&nbsp;' . anchor($metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>','class="showmetadata"') . '</li>';

                 }
            }
            $federationsString .='</ul>';
            $manage_membership = '';
            $no_feds = $membership->count();
            if ($no_feds > 0 && $has_write_access)
            {
                if (!$locked)
                {
                    $manage_membership .= '<b>' . lang('rr_federationleave') . '</b> ' . anchor(base_url() . 'manage/leavefed/leavefederation/' . $ent->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
                }
                else
                {
                    $manage_membership .= '<b>' . lang('rr_federationleave') . '</b> ' . $lockicon . ' <br />';
                }
            }
            if ($has_write_access && (count($membershipNotLeft) < count($all_federations)))
            {
                if (!$locked)
                {
                    $manage_membership .= '<b>' . lang('rr_federationjoin') . '</b> ' . anchor(base_url() . 'manage/joinfed/joinfederation/' . $ent->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
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
            $d[$i]['value'] = '<a href="' . base_url() . 'providers/detail/showmembers/' . $id . '" id="getmembers"><button type="button" class="savebutton arrowdownicon">' . lang('showmemb_btn') . '</button></a>';

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

                $d[++$i]['2cols'] = '<code>' . $this->geshilib->highlight($static_metadata, 'xml', $params) . '</code>';
            }
            $result[] = array('section' => 'staticmetadata', 'title' => '' . lang('tabStaticMeta') . '', 'data' => $d);

        }
        $d = array();
        $i = 0;

      //  $d[++$i]['header'] = '<span id="technical"></span>' . lang('rr_technicalinformation');
        if ($idppart)
        {
            $d[++$i]['header'] = '<span id="idpssoproto"></span>IDPSSODescriptor'; 
            $d[++$i]['name'] = lang('rr_supportedprotocols')  ;
            $v = implode('<br />', $ent->getProtocolSupport('idpsso'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_domainscope');
            $scopes = $ent->getScope('idpsso');
            $scopeString = '<ul>';
            foreach ($scopes as $key => $value)
            {
                $scopeString .= '<li>' . $value . '</li>';
            }
            $scopeString .= '</ul>';
            $d[$i]['value'] = $scopeString;
            $d[++$i]['name'] = lang('rr_supportednameids') ;
            $nameids = '';
            foreach ($ent->getNameIds('idpsso') as $r)
            {
                $nameids .= '<li>' . $r . '</li>';
            }
            $nameids .='</ul>';
            $d[$i]['value'] = trim($nameids);
        
            // AttributeAuthorityDescriptor
            $d[++$i]['header'] = '<span id="idpaaproto"></span>AttributeAuthorityDescriptor';
            $d[++$i]['name'] = lang('rr_supportedprotocols') . '';
            $v = implode('<br />', $ent->getProtocolSupport('aa'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_domainscope') . '';
            $scopes = $ent->getScope('aa');
            $scopeString = '<ul>';
            foreach ($scopes as $key => $value)
            {
                $scopeString .= '<li>' . $value . '</li>';
            }
            $scopeString .= '</ul>';
            $d[$i]['value'] = $scopeString;
            $aanameids = $ent->getNameIds('aa');
            $aanameid = '';
            if (count($aanameids) > 0)
            {
                $d[++$i]['name'] = lang('rr_supportednameids') ;
                foreach ($aanameids as $r)
                {
                    $aanameid .= '<li>' . $r . '</li>';
                }
                $aanameid .= '</ul>';
                $d[$i]['value'] = trim($aanameid);
            }

        }


        if($sppart)
        {
            $d[++$i]['header'] = 'SPSSODescriptor';
            $d[++$i]['name'] = lang('rr_supportedprotocols') ;
            $v = implode('<br />', $ent->getProtocolSupport('spsso'));
            $d[$i]['value'] = $v;
            $nameids = '';
            $d[++$i]['name'] = lang('rr_supportednameids');
            foreach ($ent->getNameIds('spsso') as $r)
            {
                $nameids .= '<li>' . $r . '</li>';
            }
            $nameids .='</ul>';
            $d[$i]['value'] = trim($nameids);
        }

        $result[] = array('section' => 'protocols', 'title' => '' . lang('tabprotonameid') . '', 'data' => $d);


        /**
         * ServiceLocations
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
        if ($idppart)
        {
            $d[++$i]['header'] = 'IDPSSODescriptor';
            if (array_key_exists('SingleSignOnService', $services))
            {
                $ssovalues = '';
                $d[++$i]['name'] = 'SingleSignOnService';
                foreach ($services['SingleSignOnService'] as $s)
                {
                    $def = '';
                    if ($s->getDefault())
                    {
                        $def = '<i>('.lang('rr_default').')</i>';
                    }
                    $ssovalues .= '<li><b>' . $def . ' ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
                }
                $d[$i]['value'] = '<ul>' . $ssovalues . '</ul>';
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
            if (array_key_exists('IDPAttributeService', $services))
            {
                $d[++$i]['header'] = 'AttributeAuthorityDescriptor';
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
            $d[++$i]['header'] = 'SPSSODescriptor';
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
                $d[$i]['value'] = '<ul>' . $acsvalues . '</ul>';
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
                $d[$i]['value'] = '<ul>' . $acsvalues . '</ul>';
            }
            if (array_key_exists('SPSingleLogoutService', $services))
            {
                $d[++$i]['name'] = 'SingleLogoutService';
                $slvalues = '';
                foreach ($services['SPSingleLogoutService'] as $s)
                {
                    $slvalues .= '<li><b> ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
                }
                $d[$i]['value'] = '<ul>' . $slvalues . '</ul>';
            }
            if(array_key_exists('RequestInitiator', $services) || array_key_exists('DiscoveryResponse', $services))
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
                    $d[$i]['value'] = '<ul>' . $rivalues . '</ul>';
                }
                if (array_key_exists('DiscoveryResponse', $services))
                {
                    $d[++$i]['name'] = 'DiscoveryResponse <br /><small>SPSSODescriptor/Extensions</small>';
                    $drvalues = '';
                    foreach ($services['DiscoveryResponse'] as $s)
                    {
                        $drvalues .= '<li><b>' . $s->getUrl() . '</b>&nbsp;&nbsp;<small><i>index:' . $s->getOrder() . '</i></small><br /><small>' . $s->getBindingName() . '</small></li>';
                    }
                    $d[$i]['value'] = '<ul>' . $drvalues . '</ul>';
                }

            }
        }
        $result[] = array('section' => 'services', 'title' => '' . lang('tabsrvs') . '', 'data' => $d);
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
            $d[++$i]['header'] = 'IDPSSODescriptor';
            $cString = '';
            if (array_key_exists('idpsso', $certs))
            {
                foreach ($certs['idpsso'] as $v)
                {
                    $cString = '';
                    $certusage = $v->getCertuse();
                    $langcertusage = '';
                    if (empty($certusage))
                    {
                        $langcertusage = lang('certsign') . '/' . lang('certenc');
                    }
                    elseif ($certusage === 'signing')
                    {
                        $langcertusage = lang('certsign');
                    }
                    elseif ($certusage === 'encryption')
                    {
                        $langcertusage =  lang('certenc');
                    }
                    $kname = $v->getKeyname();
                    $c_certData = $v->getCertData();
                    if (!empty($kname))
                    {
                        $cString .='<b>' . lang('rr_keyname') . ':</b><br /> ' . str_replace(',', '<br />', $kname) . '<br />';
                    }
                    if (!empty($c_certData))
                    {
                        $c_certtype = $v->getCertType();
                        if ($c_certtype == 'X509Certificate')
                        {
                            $c_fingerprint = $v->getFingerprint();
                            $c_certValid = validateX509($c_certData);
                            if (!$c_certValid)
                            {
                                $cString .='<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                            }
                        }
                        if (!empty($c_fingerprint))
                        {
                            $cString .='<b>' . lang('rr_fingerprint') . ':</b> <span>' . $c_fingerprint . '</span><br />';
                        }
                        $cString .= '<span class="accordionButton"><b>' . lang('rr_certbody') . '</b><br /></span><code class="accordionContent">' . trim($c_certData) . '</code>';
                    }
                    $cString .= '<br />';
                    $d[++$i]['name'] = $langcertusage;
                    $d[$i]['value'] = $cString;
                }
            }
            // AA
            if (array_key_exists('aa', $certs))
            {
                $d[++$i]['header'] = 'AttributeAuthorityDescriptor';
                foreach ($certs['aa'] as $v)
                {
                    $cString = '';
                    $langcertusage = '';
                    $certusage = $v->getCertuse();
                    if (empty($certusage))
                    {
                        $langcertusage = lang('certsign') . '/' . lang('certenc');
                    }
                    elseif ($certusage === 'signing')
                    {
                        $langcertusage = lang('certsign');
                    }
                    elseif ($certusage === 'encryption')
                    {
                        $langcertusage = lang('certenc');
                    }
                    $kname = $v->getKeyname();
                    $c_certData = $v->getCertData();
                    if (!empty($kname))
                    {
                        $cString .='<b>' . lang('rr_keyname') . ':</b><br /> ' . str_replace(',', '<br />', $kname) . '<br />';
                    }
                    if (!empty($c_certData))
                    {
                        $c_certtype = $v->getCertType();
                        if ($c_certtype == 'X509Certificate')
                        {
                            $c_fingerprint = $v->getFingerprint();
                            $c_certValid = validateX509($c_certData);
                            if (!$c_certValid)
                            {
                                $cString .='<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                            }
                        }
                        if (!empty($c_fingerprint))
                        {
                            $cString .='<b>' . lang('rr_fingerprint') . ':</b> <span>' . $c_fingerprint . '</span><br />';
                        }
                        $cString .= '<span class="accordionButton"><b>' . lang('rr_certbody') . '</b><br /></span><code class="accordionContent">' . trim($c_certData) . '</code>';
                    }
                    $cString .= '<br />';
                   $d[++$i]['name'] = $langcertusage ;
                   $d[$i]['value'] = $cString;
                }
            }
        }
        if ($sppart)
        {
            $d[++$i]['header'] = 'SPSSODescriptor';
            if (array_key_exists('spsso', $certs))
            {
                foreach ($certs['spsso'] as $v)
                {
                    $cString = '';
                    $langcertusage ='';
                    $certusage = $v->getCertuse();
                    if (empty($certusage))
                    {
                        $langcertusage = lang('certsign') . '/' . lang('certenc');
                    }
                    elseif ($certusage === 'signing')
                    {
                        $langcertusage =  lang('certsign');
                    }
                    elseif ($certusage === 'encryption')
                    {
                        $langcertusage =  lang('certenc');
                    }
                    $kname = $v->getKeyname();
                    $c_certData = $v->getCertData();
                    if (!empty($kname))
                    {
                        $cString .='<b>' . lang('rr_keyname') . ':</b><br /> ' . str_replace(',', '<br />', $kname) . '<br />';
                    }
                    if (!empty($c_certData))
                    {
                        $c_certtype = $v->getCertType();
                        if ($c_certtype == 'X509Certificate')
                        {
                            $c_fingerprint = $v->getFingerprint();
                            $c_certValid = validateX509($c_certData);
                            if (!$c_certValid)
                            {
                                $cString .='<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                                $alerts[] = lang('rr_certificatenotvalid');
                             
                            }
                        }
                        if (!empty($c_fingerprint))
                        {
                            $cString .='<b>' . lang('rr_fingerprint') . ':</b> <span>' . $c_fingerprint . '</span><br />';
                        }
                        $cString .= '<span class="accordionButton"><b>' . lang('rr_certbody') . '</b><br /></span><code class="accordionContent">' . trim($c_certData) . '</code>';
                    }
                    $cString .= '<br />';
                    $d[++$i]['name'] = $langcertusage;
                    $d[$i]['value'] = $cString;
                }
            }
        }
        /**
         * end certs
         */
        $result[] = array('section' => 'certificates', 'title' => '' . lang('tabCerts') . '', 'data' => $d);
        $d = array();
        $i = 0;
        $d[++$i]['header'] = lang("rr_contacts");
        $contacts = $ent->getContacts();
        $contactsTypeToTranslate = array(
             'technical' => lang('rr_cnt_type_tech'),
             'administrative' => lang('rr_cnt_type_admin'),
             'support' => lang('rr_cnt_type_support'),
             'billing' => lang('rr_cnt_type_bill'),
             'other' => lang('rr_cnt_type_other')
         );
        foreach ($contacts as $c)
        {
            $d[++$i]['name'] = $contactsTypeToTranslate[''.strtolower($c->getType()).''];
            $d[$i]['value'] = $c->getFullName() . " " . safe_mailto($c->getEmail());
        }
        $result[] = array('section' => 'contacts', 'title' => '' . lang('tabContacts') . '', 'data' => $d);
        $d = array();
        $i = 0;
        if ($idppart)
        {
            $d[++$i]['header'] = '<a name="arp"></a>' . lang('rr_arp');
            $encoded_entityid = base64url_encode($ent->getEntityId());
            $arp_url = base_url() . 'arp/format2/' . $encoded_entityid . '/arp.xml';
            $d[++$i]['name'] = lang('rr_individualarpurl');
            $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_arpurl') . '</span><span class="accordionContent"><br />' . $arp_url . '&nbsp;</span>&nbsp;' . anchor_popup($arp_url, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
            //

            $exc = $ent->getExcarps();
            if (!$locked && $has_write_access && $ent->getLocal())
            {
                $mlink = '<a href="' . base_url() . 'manage/arpsexcl/idp/' . $ent->getId() . '" class="editbutton editicon">' . lang('rr_editarpexc') . '</a>';
                $d[++$i]['name'] = lang('rr_arpexclist_title') . ' <br />' . $mlink;
                if (is_array($exc) && count($exc) > 0)
                {
                    $l = '<ul>';
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
            $d[$i]['value'] = anchor(base_url('reports/idp_matrix/show/' . $ent->getId()), 'matrix', 'class="editbutton"');
        }
        /**
         * supported attributes by IDP part
         */
        if ($idppart)
        {
            $image_link = '<img src="' . base_url() . 'images/icons/pencil-field.png"/>';
            if ($has_write_access)
            {
                $edit_attributes = '<span style="float: right;"><a href="' . base_url() . 'manage/supported_attributes/idp/' . $id . ' " class="editbutton editicon">'.  lang('rr_edit') . '</a></span>';
                $edit_policy = '<span style="float: right;"><a href="' . base_url() . 'manage/attribute_policy/globals/' . $id . ' " id="editattributesbutton" class="editbutton editicon">' .  lang('rr_edit') . '</a></span>';
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
            $d[++$i]['2cols'] = $this->show_element->generateTableDefaultArp($ent, $disable_caption);
        }
        /**
         * required attributes by SP part
         */
        if ($sppart)
        {
            $edit_req_attrs_link = '';

            if ($has_write_access)
            {
                $d[++$i]['name'] = lang('rr_attrsoverview');
                $d[$i]['value'] = anchor(base_url() . 'reports/sp_matrix/show/' . $ent->getId(), lang('rr_attrsoverview'),'class="editbutton"');

                $image_link = '<img src="' . base_url('images/icons/pencil-field.png') . '"/>';
                $edit_req_attrs_link = '<span style="float: right;"><a href="' . base_url() . 'manage/attribute_requirement/sp/' . $ent->getId() . '" class="editbutton editicon" title="edit" >' .  lang('rr_edit') . '</a></span>';
            }
            $d[++$i]['header'] = '<span id="reqattrs"></span>' . lang('rr_requiredattributes') . $edit_req_attrs_link;
            $requiredAttributes = $ent->getAttributesRequirement();
            if ($requiredAttributes->count() === 0)
            {
                $d[++$i]['name'] = '';
                $d[$i]['value'] = '<span class="notice">' . lang('rr_noregspecified_inherit_from_fed') . '</span>';
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
        $result[] = array('section' => 'attrs', 'title' => '' . lang('tabAttrs') . '', 'data' => $d);
        $d = array();
        $i = 0;

        $d[++$i]['header'] = lang('rr_uii');

        if ($idppart)
        {
            $uiiarray = array();
            $d[++$i]['2cols'] = lang('rr_uii') . ' '.lang('forIDPpart');
            foreach ($extend as $e)
            {
                if ($e->getNamespace() == 'mdui' && $e->getType() == 'idp')
                {
                    $uiiarray[$e->getElement()][] = $e;
                }
            }
            $d[++$i]['name'] = lang('DisplayName');
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
            $d[++$i]['name'] = lang('Description');
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
            $d[++$i]['name'] = lang('PrivacyStatementURL');
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
            $d[++$i]['name'] = lang('InformationURL');
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
            $logoatts = array(
                'width' => '400',
                'height' => '200',
                'scrollbars' => 'yes',
                'status' => 'yes',
                'resizable' => 'yes',
                'screenx' => '0',
                'screeny' => '0'
            );
            $d[++$i]['name'] = lang('rr_logos');
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
            $d[++$i]['name'] = lang('rr_geolocation');
            if (isset($uiiarray['GeolocationHint']))
            {
                $str = '';
                foreach ($uiiarray['GeolocationHint'] as $v)
                {
                    $str .= $v->getElementValue() . '<br />';
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
            $d[++$i]['2cols'] = lang('rr_uii') . ' ' . lang('forSPpart');
            foreach ($extend as $e)
            {
                if ($e->getNamespace() == 'mdui' && $e->getType() == 'sp')
                {
                    $uiiarray[$e->getElement()][] = $e;
                }
            }
            $d[++$i]['name'] = lang('DisplayName');
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
            $d[++$i]['name'] = lang('Description');
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
            $d[++$i]['name'] = lang('PrivacyStatementURL');
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
            $d[++$i]['name'] = lang('InformationURL');
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
            $d[++$i]['name'] = lang('rr_geolocation');
            if (isset($uiiarray['GeolocationHint']))
            {
                $str = '';
                foreach ($uiiarray['GeolocationHint'] as $v)
                {
                    $str .= $v->getElementValue() . '<br />';
                }
                $d[$i]['value'] = $str;
            }
            else
            {
                $d[$i]['value'] = lang('rr_notset');
            }
        }

        $result[] = array('section' => 'uii', 'title' => '' . lang('tabUII') . '', 'data' => $d);
        $d = array();
        $i = 0;
        $d[++$i]['header'] = lang('rr_management');
        if ($has_manage_access)
        {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' ' . anchor(base_url() . 'manage/entitystate/modify/' . $id, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
            if(!$is_active)
            {
              $d[$i]['value'] .= '<div>'.lang('rr_rmprovider').' '. anchor(base_url() . 'manage/premoval/providertoremove/' . $id, '<img src="' . base_url() . 'images/icons/arrow.png"/>').'</div>';
            }
            else
            {
              $d[$i]['value'] .= '<div>'.lang('rr_rmprovider').'<img src="' . base_url() . 'images/icons/prohibition.png"/> <div class="alert">'.lang('rmproviderdisablefirst').'</div></div>';


            }
        }
        elseif($has_write_access)
        {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' <img src="' . base_url() . 'images/icons/prohibition.png"/><div class="alert">'.lang('rerror_managepermneeded').'</div>';
            $d[$i]['value'] .= '<div>'.lang('rr_rmprovider').'<img src="' . base_url() . 'images/icons/prohibition.png"/> <div class="alert">'.lang('rerror_managepermneeded').'</div> </div>';
            
        }
        else
        {
            $d[++$i]['name'] = lang('rr_managestatus');
            $d[$i]['value'] = lang('rr_lock') . '/' . lang('rr_unlock') . ' ' . lang('rr_enable') . '/' . lang('rr_disable') . ' <img src="' . base_url() . 'images/icons/prohibition.png"/>';
        }
        $d[++$i]['name'] = '';
        if ($has_manage_access)
        {
            $d[$i]['value'] = lang('rr_displayaccess') . anchor(base_url() . 'manage/access_manage/entity/' . $id, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        }
        else
        {
            $d[$i]['value'] = lang('rr_displayaccess') . '<img src="' . base_url() . 'images/icons/prohibition.png"/>';
        }
        $result[] = array('section' => 'mngt', 'title' => '' . lang('tabMngt') . '', 'data' => $d);
        $d = array();
        $i = 0;



        $data['tabs'] = $result;
        /**
         * @todo finish show alert block if some warnings realted to entity 
         */
        //$data['alerts'] = $alerts;
        $data['content_view'] = 'providers/detail_view.php';
        $this->load->view('page', $data);
    }

    function showmembers($providerid)
    {
        if (!$this->input->is_ajax_request())
        {
           set_status_header(403);
           echo 'unsupported request';
           return;
        }
        if (!$this->j_auth->logged_in())
        {
            
           set_status_header(403);
           echo 'no session';
           return;

        }
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid));
        if (empty($ent))
        {
           set_status_header(404);
           echo lang('error404');
           return;
        }
        $has_read_access = $this->zacl->check_acl($providerid, 'read', 'entity', '');
        if (!$has_read_access)
        {
           set_status_header(403);
           echo 'no access';
           return;
        }

        $tmp_providers = new models\Providers;
        $members = $tmp_providers->getTrustedServicesWithFeds($ent);
        if (empty($members))
        {
           $l[] = array('entityid' => '' . lang('nomembers') . '', 'name' => '', 'url' => '');
        }
        $preurl = base_url() . 'providers/detail/show/';
        foreach ($members as $m)
        {
           $feds = array();
           $name = $m->getName();
           if (empty($name))
           {
               $name = $m->getEntityId();
           }
           $y = $m->getFederations();
           foreach($y as $yv)
           {
              $feds[] = $yv->getName();
           }
           $l[] = array('entityid' => $m->getEntityId(), 'name' => $name, 'url' => $preurl . $m->getId(),'feds'=>$feds);
        }
        echo json_encode($l);
    }

}
