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
 * Form_element Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Form_element {

    protected $ci;
    protected $em;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('form');
        $this->ci->load->helper('shortcodes');
        log_message('debug', 'lib/Form_element initialized');
    }

    public function NgenerateOtherFormLinks(models\Provider $ent)
    {
        $l = array();
        $base = base_url();
        $t = $ent->getType();
        $id = $ent->getId();
        if ($t === 'BOTH')
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/sp', 'Geolocations (ServiceProvider)');
            $l[] = anchor($base . 'geolocation/show/' . $id . '/idp', 'Geolocations (IdentityProvider)');
            $l[] = anchor($base . 'manage/logos/provider/idp/' . $id . '', 'Logos (IdentityProvider)');
            $l[] = anchor($base . 'manage/logos/provider/sp/' . $id . '', 'Logos (ServiceProvider)');
        }
        elseif ($t === 'IDP')
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/idp', 'Geolocations');
            $l[] = anchor($base . 'manage/logos/provider/idp/' . $id . '', 'Logos');
        }
        else
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/sp', 'Geolocations');
            $l[] = anchor($base . 'manage/logos/provider/sp/' . $id . '', 'Logos');
        }
        if ($t != 'IDP')
        {
            $l[] = anchor($base . 'manage/attribute_requirement/sp/' . $id . '', 'Attributes Requirement');
        }
        if ($t != 'SP')
        {
            $l[] = anchor($base . 'manage/supported_attributes/idp/' . $id . '', 'Supported Attributes');
            $l[] = anchor($base . 'manage/attribute_policy/globals/' . $id . '', 'Attributes Policies');
            $l[] = anchor($base . 'manage/arpsexcl/idp/' . $id . '', 'Services excluded from ARP');
        }
        return $l;
    }

    /**
     * new function with prefix N for generating forms elements will replace old ones
     */
    public function NgenerateEntityGeneral(models\Provider $ent, $ses = null)
    {
        $sessform = FALSE;
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $class_ent = '';
        $class_org = '';
        $t1 = set_value('f[entityid]', $ent->getEntityId());
        $t2 = set_value('f[orgname]', $ent->getName());
        $t_displayname = $ent->getDisplayName();
        $t_regauthority = $ent->getRegistrationAuthority();
        $t_regdate = '';
        $origregdate = '';
        $tmpregdate = $ent->getRegistrationDate();
        if (!empty($tmpregdate))
        {
            $t_regdate = $tmpregdate->format('Y-m-d');
            $origregdate = $tmpregdate->format('Y-m-d');
        }
        $t_homeurl = $ent->getHomeUrl();
        $t_helpdeskurl = $ent->getHelpdeskUrl();

        $t_validfrom = '';
        $origvalidfrom = '';
        $tmpvalidfrom = $ent->getValidFrom();
        if (!empty($tmpvalidfrom))
        {
            $t_validfrom = $tmpvalidfrom->format('Y-m-d');
            $origvalidfrom = $tmpvalidfrom->format('Y-m-d');
        }
        $t_validto = '';
        $origvalidto = '';
        $tmpvalidto = $ent->getValidTo();
        if (!empty($tmpvalidto))
        {
            $t_validto = $tmpvalidto->format('Y-m-d');
            $origvalidto = $tmpvalidto->format('Y-m-d');
        }
        $t_description = $ent->getDescription();

        if ($sessform)
        {
            if (array_key_exists('regauthority', $ses))
            {
                $t_regauthority = $ses['regauthority'];
            }
            if (array_key_exists('registrationdate', $ses))
            {
                $t_regdate = $ses['registrationdate'];
            }
            if (array_key_exists('homeurl', $ses))
            {
                $t_homeurl = $ses['homeurl'];
            }
            if (array_key_exists('displayname', $ses))
            {
                $t_displayname = $ses['displayname'];
            }
            if (array_key_exists('helpdeskurl', $ses))
            {
                $t_helpdeskurl = $ses['helpdeskurl'];
            }
            if (array_key_exists('validrom', $ses))
            {
                $t_validfrom = $ses['validfrom'];
            }
            if (array_key_exists('validto', $ses))
            {
                $t_validto = $ses['validto'];
            }
            if (array_key_exists('entityid', $ses))
            {
                if ($t1 != $ses['entityid'])
                {
                    $class_ent = 'notice';
                    $t1 = $ses['entityid'];
                }
            }
            if (array_key_exists('orgname', $ses))
            {
                if ($t2 != $ses['orgname'])
                {
                    $class_org = 'notice';
                    $t2 = $ses['orgname'];
                }
            }

            if (array_key_exists('description', $ses))
            {
                $t_description = $ses['description'];
            }
        }

        $f_regauthority = set_value('f[regauthority]', $t_regauthority);
        $f_regdate = set_value('f[registrationdate]', $t_regdate);
        $f_homeurl = set_value('f[homeurl]', $t_homeurl);
        $f_helpdeskurl = set_value('f[helpdeskurl]', $t_helpdeskurl);
        $f_validfrom = set_value('f[validfrom]', $t_validfrom);
        $f_validto = set_value('f[validto]', $t_validto);
        $f_displayname = set_value('f[displayname]', $t_displayname);
        $f_description = set_value('f[description]', $t_description);
        if ($f_regauthority != $ent->getRegistrationAuthority())
        {
            $regauthority_notice = 'notice';
        }
        else
        {
            $regauthority_notice = '';
        }
        if ($f_regdate != $origregdate)
        {
            $regdate_notice = 'notice';
        }
        else
        {
            $regdate_notice = '';
        }
        if ($f_homeurl != $ent->getHomeUrl())
        {
            $homeurl_notice = 'notice';
        }
        else
        {
            $homeurl_notice = '';
        }
        if ($f_helpdeskurl != $ent->getHelpdeskUrl())
        {
            $helpdeskurl_notice = 'notice';
        }
        else
        {
            $helpdeskurl_notice = '';
        }
        if ($f_validfrom != $origvalidfrom)
        {
            $validfrom_notice = 'notice';
        }
        else
        {
            $validfrom_notice = '';
        }
        if ($f_validto != $origvalidto)
        {
            $validto_notice = 'notice';
        }
        else
        {
            $validto_notice = '';
        }
        if ($f_description != form_prep($ent->getDescription()))
        {
            $description_notice = 'notice';
        }
        else
        {
            $description_notice = '';
        }
        if ($f_displayname != $ent->getDisplayName())
        {
            $class_displ = 'notice';
        }
        else
        {
            $class_displ = '';
        }
        $result = array();
        $result[] = form_label(lang('rr_entityid'), 'f[entityid]') . form_input(array('id' => 'f[entityid]', 'class' => $class_ent, 'name' => 'f[entityid]', 'required' => 'required', 'value' => $t1));
        $result[] = form_label(lang('rr_resource'), 'f[orgname]') . form_input(array('id' => 'f[orgname]', 'class' => $class_org, 'name' => 'f[orgname]', 'required' => 'required', 'value' => $t2));
        $result[] = form_label(lang('rr_displayname'), 'f[displayname]') . form_input(array('id' => 'f[displayname]', 'class' => $class_displ, 'name' => 'f[displayname]', 'required' => 'required', 'value' => $f_displayname));
        $result[] = form_label(lang('rr_regauthority'), 'f[regauthority]') . form_input(array('id' => 'f[regauthority]', 'class' => $regauthority_notice, 'name' => 'f[regauthority]', 'value' => $f_regauthority));
        $result[] = form_label(lang('rr_regdate'), 'f[registrationdate]') . form_input(array(
                    'name' => 'f[registrationdate]',
                    'id' => 'f[registrationdate]',
                    'value' => $f_regdate,
                    'class' => 'registrationdate ' . $regdate_notice,
        ));
        $result[] = form_label(lang('rr_homeurl'), 'f[homeurl]') . form_input(array('id' => 'f[homeurl]', 'class' => $homeurl_notice, 'name' => 'f[homeurl]', 'value' => $f_homeurl));
        $result[] = form_label(lang('rr_helpdeskurl'), 'f[helpdeskurl]') . form_input(array('id' => 'f[helpdeskurl]', 'class' => $helpdeskurl_notice, 'name' => 'f[helpdeskurl]', 'value' => $f_helpdeskurl));
        $result[] = form_label(lang('rr_validfrom'), 'f[validfrom]') . form_input(array(
                    'name' => 'f[validfrom]',
                    'id' => 'f[validfrom]',
                    'value' => $f_validfrom,
                    'class' => 'validfrom ' . $validfrom_notice,
        ));
        $result[] = form_label(lang('rr_validto'), 'f[validto]') . form_input(array(
                    'name' => 'f[validto]',
                    'id' => 'f[validto]',
                    'value' => $f_validto,
                    'class' => 'validto ' . $validto_notice,
        ));
        $result[] = form_label(lang('rr_description'), 'f[description]') . form_textarea(array(
                    'name' => 'f[description]',
                    'id' => 'f[description]',
                    'class' => $description_notice,
                    'value' => $f_description,
        ));
        $result[] = '<div style="width: 100%; text-align: center;"><h5>Localized general informations</h5>Included in Matadata inside &lt;md:Organization/&gt;</div>';
        /**
         * start lname
         */
        $result[] = '<h5>Localized Names</h5>';
        $lnames = $ent->getLocalName();
        $slname = array();
        $origlname = array();
        $lnamelangs = languagesCodes();
        if ($sessform && array_key_exists('lname', $ses) && is_array($ses['lname']))
        {
            $slname = $ses['lname'];
        }
        if (is_array($lnames))
        {
            $origlname = $lnames;
        }
        foreach ($slname as $key => $value)
        {
            $lnamenotice = '';
            $lvalue = set_value('f[lname][' . $key . ']', $value);
            if (array_key_exists($key, $origlname))
            {
                if ($origlname['' . $key . ''] != $value)
                {
                    $lnamenotice = 'notice';
                }
            }
            else
            {
                $lnamenotice = 'notice';
            }
            $result[] = form_label(lang('rr_providername') . ' <small>' . $lnamelangs['' . $key . ''] . '</small>', 'f[lname][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[lname][' . $key . ']',
                                'id' => 'f[lname][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $lnamenotice
                            )
            );
            unset($origlname['' . $key . '']);
            unset($lnamelangs['' . $key . '']);
        }
        foreach ($origlname as $key => $value)
        {
            $lnamenotice = '';
            $lvalue = set_value('f[lname][' . $key . ']', $value);
            if ($lvalue != $value)
            {
                $lnamenotice = 'notice';
            }
            $result[] = form_label(lang('rr_providername') . ' <small>' . $lnamelangs['' . $key . ''] . '</small>', 'f[lname][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[lname][' . $key . ']',
                                'id' => 'f[lname][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $lnamenotice
                            )
            );
            unset($lnamelangs['' . $key . '']);
        }
        $result[] = '<span class="lnameadd">' . form_dropdown('lnamelangcode', $lnamelangs, 'en') . '<button type="button" id="addlname" name="addlname" value="addlname" class="btn">Add localized Name</button></span>';

        $result[] = '';
        /**
         * end lname
         */
        /**
         * start ldisplayname
         */
        $result[] = '<h5>Localized DisplayNames</h5>';
        $ldisplaynames = $ent->getLocalDisplayName();
        $sldisplayname = array();
        $origldisplayname = array();
        $ldisplaynamelangs = languagesCodes();
        if ($sessform && array_key_exists('ldisplayname', $ses) && is_array($ses['ldisplayname']))
        {
            $slname = $ses['ldisplayname'];
        }
        if (is_array($ldisplaynames))
        {
            $origldisplayname = $ldisplaynames;
        }
        foreach ($sldisplayname as $key => $value)
        {
            $ldisplaynamenotice = '';
            $lvalue = set_value('f[lname][' . $key . ']', $value);
            if (array_key_exists($key, $origldisplayname))
            {
                if ($origldisplayname['' . $key . ''] != $value)
                {
                    $ldisplaynamenotice = 'notice';
                }
            }
            else
            {
                $ldisplaynamenotice = 'notice';
            }
            $result[] = form_label(lang('rr_displayname') . ' <small>' . $ldisplaynamelangs['' . $key . ''] . '</small>', 'f[ldisplayname][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[ldisplayname][' . $key . ']',
                                'id' => 'f[ldisplayname][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $ldisplaynamenotice
                            )
            );
            unset($origldisplayname['' . $key . '']);
            unset($ldisplaynamelangs['' . $key . '']);
        }
        foreach ($origldisplayname as $key => $value)
        {
            $ldisplaynamenotice = '';
            $lvalue = set_value('f[ldisplayname][' . $key . ']', $value);
            if ($lvalue != $value)
            {
                $ldisplaynamenotice = 'notice';
            }
            $result[] = form_label(lang('rr_displayname') . ' <small>' . $ldisplaynamelangs['' . $key . ''] . '</small>', 'f[ldisplayname][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[ldisplayname][' . $key . ']',
                                'id' => 'f[ldisplayname][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $ldisplaynamenotice
                            )
            );
            unset($ldisplaynamelangs['' . $key . '']);
        }
        $result[] = '<span class="ldisplaynameadd">' . form_dropdown('ldisplaynamelangcode', $ldisplaynamelangs, 'en') . '<button type="button" id="addldisplayname" name="addldisplayname" value="addldisplayname" class="btn">Add localized DisplayName</button></span>';

        $result[] = '';
        /**
         * end ldisplayname
         */
        /**
         * start regpolicy 
         */
        $result[] = '<h5>Localized RegistrationPolicy</h5>';
        $regpolicies = $ent->getRegistrationPolicy();
        $sregpolicies = array();
        $origrepolicies = array();
        $regpolicylangs = languagesCodes();
        if ($sessform && array_key_exists('regpolicy', $ses) && is_array($ses['regpolicy']))
        {
            $sregpolicies = $ses['regpolicy'];
        }
        $origregpolicies = $regpolicies;
        foreach ($sregpolicies as $key => $value)
        {
            $regpolicynotice = '';
            $lvalue = set_value('f[regpolicy][' . $key . ']', $value);
            if (array_key_exists($key, $origregpolicies))
            {
                if ($origregpolicies['' . $key . ''] != $value)
                {
                    $regpolicynotice = 'notice';
                }
            }
            else
            {
                $regpolicynotice = 'notice';
            }
            $result[] = form_label(lang('rr_regpolicy') . ' <small>' . $regpolicylangs['' . $key . ''] . '</small>', 'f[regpolicy][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[regpolicy][' . $key . ']',
                                'id' => 'f[regpolicy][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $regpolicynotice
                            )
            );
            unset($origregpolicies['' . $key . '']);
            unset($regpolicylangs['' . $key . '']);
        }
        foreach ($origregpolicies as $key => $value)
        {
            $regpolicynotice = '';
            $lvalue = set_value('f[regpolicy][' . $key . ']', $value);
            if ($lvalue != $value)
            {
                $regpolicynotice = 'notice';
            }
            $result[] = form_label(lang('rr_regpolicy') . ' <small>' . $regpolicylangs['' . $key . ''] . '</small>', 'f[regpolicy][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[regpolicy][' . $key . ']',
                                'id' => 'f[regpolicy][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $regpolicynotice
                            )
            );
            unset($regpolicylangs['' . $key . '']);
        }
        $result[] = '<span class="regpolicyadd">' . form_dropdown('regpolicylangcode', $regpolicylangs, 'en') . '<button type="button" id="addregpolicy" name="addregpolicy" value="addregpolicy" class="btn">Add localized RegistrationPolicy</button></span>';

        $result[] = '';
        /**
         * end regpolicy
         */
        /**
         * start lhelpdesk
         */
        $result[] = '<h5>Localized Helpdesk/Info Urls</h5>';
        $lhelpdesk = $ent->getLocalHelpdeskUrl();
        $slhelpdesk = array();
        $origlhelpdesk = array();
        $lhelpdesklangs = languagesCodes();
        if ($sessform && array_key_exists('lhelpdesk', $ses) && is_array($ses['lhelpdesk']))
        {
            $slname = $ses['lhelpdesk'];
        }
        if (is_array($lhelpdesk))
        {
            $origlhelpdesk = $lhelpdesk;
        }
        foreach ($slhelpdesk as $key => $value)
        {
            $lhelpdesknotice = '';
            $lvalue = set_value('f[lhelpdesk][' . $key . ']', $value);
            if (array_key_exists($key, $origlhelpdesk))
            {
                if ($origlhelpdesk['' . $key . ''] != $value)
                {
                    $lhelpdesknotice = 'notice';
                }
            }
            else
            {
                $lhelpdesknotice = 'notice';
            }
            $result[] = form_label(lang('rr_helpdeskurl') . ' <small>' . $lhelpdesklangs['' . $key . ''] . '</small>', 'f[lhelpdesk][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[lhelpdesk][' . $key . ']',
                                'id' => 'f[lhelpdesk][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $lhelpdesknotice
                            )
            );
            unset($origlhelpdesk['' . $key . '']);
            unset($lhelpdesklangs['' . $key . '']);
        }
        foreach ($origlhelpdesk as $key => $value)
        {
            $lhelpdesknotice = '';
            $lvalue = set_value('f[lhelpdesk][' . $key . ']', $value);
            if ($lvalue != $value)
            {
                $lhelpdesknotice = 'notice';
            }
            $result[] = form_label(lang('rr_helpdeskurl') . ' <small>' . $lhelpdesklangs['' . $key . ''] . '</small>', 'f[lhelpdesk][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[lhelpdesk][' . $key . ']',
                                'id' => 'f[lhelpdesk][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $lhelpdesknotice
                            )
            );
            unset($lhelpdesklangs['' . $key . '']);
        }
        $result[] = '<span class="lhelpdeskadd">' . form_dropdown('lhelpdesklangcode', $lhelpdesklangs, 'en') . '<button type="button" id="addlhelpdesk" name="addlhelpdesk" value="addlhelpdesk" class="btn">Add localized HelpdeskURL</button></span>';

        /**
         * end ldisplayname
         */
        /**
         * start ldesc
         */
        $result[] = '<h5>Localized Descriptions</h5>';
        $ldescriptions = $ent->getLocalDescription();
        $sldesc = array();
        $origldesc = array();
        $ldesclangs = languagesCodes();
        if ($sessform && array_key_exists('ldesc', $ses) && is_array($ses['ldesc']))
        {
            $sldesc = $ses['ldesc'];
        }
        if (is_array($ldescriptions))
        {
            $origldesc = $ldescriptions;
        }
        foreach ($sldesc as $key => $value)
        {
            $ldescnotice = '';
            $lvalue = set_value('f[ldesc][' . $key . ']', $value);
            if (array_key_exists($key, $origldesc))
            {
                if ($origldesc['' . $key . ''] !== $value)
                {
                    $ldescnotice = 'notice';
                }
            }
            else
            {
                $ldescnotice = 'notice';
            }
            $result[] = form_label(lang('rr_description') . ' <small>' . $ldesclangs['' . $key . ''] . '</small>', 'f[ldesc][' . $key . ']') . form_textarea(
                            array(
                                'name' => 'f[ldesc][' . $key . ']',
                                'id' => 'f[ldesc][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $ldescnotice
                            )
            );
            unset($origldesc['' . $key . '']);
            unset($ldesclangs['' . $key . '']);
        }
        foreach ($origldesc as $key => $value)
        {
            $ldescnotice = '';
            $lvalue = set_value('f[ldesc][' . $key . ']', $value);
            if ($lvalue != form_prep($value))
            {
                $ldescnotice = 'notice';
            }
            $result[] = form_label(lang('rr_description') . ' <small>' . $ldesclangs['' . $key . ''] . '</small>', 'f[ldesc][' . $key . ']') . form_textarea(
                            array(
                                'name' => 'f[ldesc][' . $key . ']',
                                'id' => 'f[ldesc][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $ldescnotice
                            )
            );
            unset($ldesclangs['' . $key . '']);
        }
        $result[] = '<span class="ldescadd">' . form_dropdown('ldesclangcode', $ldesclangs, 'en') . '<button type="button" id="addldescription" name="addldescription" value="addlldescription" class="btn">Add localized Description</button></span>';

        /**
         * end ldesc
         */
        return $result;
    }

    public function NgeneratePrivacy(models\Provider $ent, $ses = null)
    {
        $result = array();
        $sessform = FALSE;
        $enttype = $ent->getType();
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }

        $r = '<fieldset><legend>Privacy Statement URL <i>default</i>' . showHelp('The URL is used as english version if below is not set') . '</legend><ol><li>';
        $f_privacyurl = $ent->getPrivacyUrl();
        $p_privacyurl = $f_privacyurl;
        $privaceurlnotice = '';
        if ($sessform && array_key_exists('privacyurl', $ses))
        {
            $p_privacyurl = $ses['privacyurl'];
        }
        $t_privacyurl = set_value('f[privacyurl]', $p_privacyurl);
        if ($t_privacyurl != $f_privacyurl)
        {
            $privaceurlnotice = 'notice';
        }
        $r .= form_label(lang('rr_url', 'f[privacyurl]')) . form_input(array('name' => 'f[privacyurl]', 'id' => 'f[privacyurl]', 'value' => $t_privacyurl, 'class' => $privaceurlnotice));
        $r .= '</li></ol></fieldset>';
        $result[] = $r;

        $current_coc = $ent->getCoc();
        $current_coc_id = 0;
        $cocnotice = '';
        if (!empty($current_coc))
        {
            $current_coc_id = $current_coc->getId();
        }
        if ($sessform && isset($ses['coc']))
        {
            if ($ses['coc'] != $current_coc_id)
            {
                $cocnotice = 'notice';
                $current_coc_id = $ses['coc'];
            }
        }
        $coc_dropdown['0'] = lang('rr_select');
        $coccols = $this->em->getRepository("models\Coc")->findAll();
        $r = '<fieldset><legend>Code of Conduct</legend><ol>';
        if (is_array($coccols) and count($coccols) > 0)
        {
            $r .= '<li class="' . $cocnotice . '">';
            $r .= form_label('Code of Conduct' . showHelp('Please contact to us if required COC url is not listed'), 'f[coc]');
            foreach ($coccols as $c)
            {
                $coc_dropdown['' . $c->getId() . ''] = $c->getName() . ' (' . $c->getUrl() . ')';
            }
            // print_r($coc_dropdown);
            $r .= form_dropdown('f[coc]', $coc_dropdown, $current_coc_id);
            $r .= '</li>';
        }
        $r .= '</ol></fieldset>';
        $result[] = $r;




        $langscodes = languagesCodes();
        $e = $ent->getExtendMetadata();
        $extend = array();
        foreach ($e as $v)
        {
            $extend['' . $v->getType() . '']['' . $v->getNamespace() . '']['' . $v->getElement() . ''][] = $v;
        }
        if ($enttype != 'SP')
        {
            $r = '<fieldset><legend>Privacy Statement URLs <i>IDPSSODescriptor</i></legend><ol>';
            $origs = array();
            $sorig = array();
            if (isset($extend['idp']['mdui']['PrivacyStatementURL']))
            {
                foreach ($extend['idp']['mdui']['PrivacyStatementURL'] as $value)
                {
                    $l = $value->getAttributes();
                    $origs['' . $l['xml:lang'] . ''] = array('url' => $value->getEvalue());
                }
                $sorig = $origs;
            }
            if ($sessform && isset($ses['prvurl']['idpsso']))
            {
                foreach ($ses['prvurl']['idpsso'] as $k2 => $v2)
                {
                    $sorig['' . $k2 . ''] = array('url' => $v2);
                }
            }
            foreach ($sorig as $k3 => $v3)
            {
                if (array_key_exists($k3, $origs))
                {
                    if ($origs['' . $k3 . '']['url'] === $v3['url'])
                    {
                        $sorig['' . $k3 . '']['notice'] = '';
                    }
                    else
                    {
                        $sorig['' . $k3 . '']['notice'] = 'notice';
                    }
                }
                else
                {
                    $sorig['' . $k3 . '']['notice'] = 'notice';
                }
            }
            foreach ($sorig as $k4 => $v4)
            {
                $r .= '<li class="localized">';
                $r .= form_label(lang('rr_privacystatement') . ' <small>' . $langscodes['' . $k4 . ''] . '</small>', 'f[prvurl][idpsso][' . $k4 . ']');
                $r .= form_input(array('id' => 'f[prvurl][idpsso][' . $k4 . ']', 'name' => 'f[prvurl][idpsso][' . $k4 . ']', 'value' => $v4['url']));
                $r .='</li>';
            }
            $idpssolangcodes = array_diff_key($langscodes, $sorig);
            $r .= '<li class="addlprivacyurlidpsso localized">';

            $r .= form_dropdown('langcode', $idpssolangcodes, 'en');
            $r .= '<button type="button" id="addlprivacyurlidpsso" name="addlprivacyurlidpsso" value="addlprivacyurlidpsso" class="btn">Add localized ' . lang('rr_privacystatement') . '</button>';

            $r .= '</ol></fieldset>';
            $result[] = $r;
        }
        if ($enttype != 'IDP')
        {
            $r = '<fieldset><legend>Privacy Statement URLs <i>SPSSODescriptor</i></legend><ol>';
            $origs = array();
            $sorig = array();
            if (isset($extend['sp']['mdui']['PrivacyStatementURL']))
            {
                foreach ($extend['sp']['mdui']['PrivacyStatementURL'] as $value)
                {
                    $l = $value->getAttributes();
                    $origs['' . $l['xml:lang'] . ''] = array('url' => $value->getEvalue());
                }
                $sorig = $origs;
            }
            if ($sessform && isset($ses['prvurl']['spsso']))
            {
                foreach ($ses['prvurl']['spsso'] as $k2 => $v2)
                {
                    $sorig['' . $k2 . ''] = array('url' => $v2);
                }
            }
            foreach ($sorig as $k3 => $v3)
            {
                if (array_key_exists($k3, $origs))
                {
                    if ($origs['' . $k3 . '']['url'] === $v3['url'])
                    {
                        $sorig['' . $k3 . '']['notice'] = '';
                    }
                    else
                    {
                        $sorig['' . $k3 . '']['notice'] = 'notice';
                    }
                }
                else
                {
                    $sorig['' . $k3 . '']['notice'] = 'notice';
                }
            }
            foreach ($sorig as $k4 => $v4)
            {
                $r .= '<li class="localized">';
                $r .= form_label(lang('rr_privacystatement') . ' <small>' . $langscodes['' . $k4 . ''] . '</small>', 'f[prvurl][spsso][' . $k4 . ']');
                $r .= form_input(array('id' => 'f[prvurl][spsso][' . $k4 . ']', 'name' => 'f[prvurl][spsso][' . $k4 . ']', 'value' => $v4['url']));
                $r .='</li>';
            }
            $spssolangcodes = array_diff_key($langscodes, $sorig);
            $r .= '<li class="addlprivacyurlspsso localized">';

            $r .= form_dropdown('langcode', $spssolangcodes, 'en');
            $r .= '<button type="button" id="addlprivacyurlspsso" name="addlprivacyurlspsso" value="addlprivacyurlspsso" class="btn">Add localized ' . lang('rr_privacystatement') . '</button>';




            $r .= '</li>';


            $r .= '</ol></fieldset>';
            $result[] = $r;
        }

        return $result;
    }

    public function NgenerateContactsForm(models\Provider $ent, $ses = null)
    {
        $cnts = $ent->getContacts();
        $r = FALSE;
        $formtypes = array(
            'administrative' => lang('rr_cnt_type_admin'),
            'technical' => lang('rr_cnt_type_tech'),
            'support' => lang('rr_cnt_type_support'),
            'billing' => lang('rr_cnt_type_bill'),
            'other' => lang('rr_cnt_type_other')
        );
        if (!empty($ses) && is_array($ses))
        {
            $r = TRUE;
        }
        $result = array();
        foreach ($cnts as $cnt)
        {
            $sur = htmlspecialchars_decode($cnt->getSurname());
            $row = form_fieldset() . '<ol>';
            $class_cnt1 = '';
            $class_cnt2 = '';
            $class_cnt3 = '';
            $class_cnt4 = '';
            if ($r)
            {
                $t1 = set_value($ses['contact'][$cnt->getId()]['type'], $cnt->getType());
                $t2 = $ses['contact'][$cnt->getId()]['fname'];
                $t3 = $ses['contact'][$cnt->getId()]['sname'];
                $t4 = $ses['contact'][$cnt->getId()]['email'];
            }
            else
            {
                $t1 = $cnt->getType();
                $t2 = $cnt->getGivenname();
                $t3 = $cnt->getSurname();
                $t4 = $cnt->getEmail();
            }
            $t1 = set_value('f[contact][' . $cnt->getId() . '][type]', $t1);
            $t2 = set_value('f[contact][' . $cnt->getId() . '][fname]', $t2);
            $t3 = set_value('f[contact][' . $cnt->getId() . '][sname]', $t3);
            $t4 = set_value('f[contact][' . $cnt->getId() . '][email]', $t4);
            if ($r)
            {
                if (array_key_exists('type', $ses['contact'][$cnt->getId()]))
                {
                    if ($t1 != $cnt->getType())
                    {
                        $class_cnt1 = 'notice';
                    }
                }
                if (array_key_exists('fname', $ses['contact'][$cnt->getId()]))
                {
                    if ($t2 != $cnt->getGivenname())
                    {
                        $class_cnt2 = 'notice';
                    }
                }
                if (array_key_exists('sname', $ses['contact'][$cnt->getId()]))
                {
                    if ($t3 != $cnt->getSurname())
                    {
                        $class_cnt3 = 'notice';
                    }
                }
                if (array_key_exists('email', $ses['contact'][$cnt->getId()]))
                {
                    if ($t4 != $cnt->getEmail())
                    {
                        $class_cnt4 = 'notice';
                    }
                }
            }
            $row .= '<li>' . form_label(lang('rr_contacttype'), 'f[contact][' . $cnt->getId() . '][type]');
            $row .= '<span class="' . $class_cnt1 . '">' . form_dropdown('f[contact][' . $cnt->getId() . '][type]', $formtypes, $t1) . '</span></li>';
            $row .= '<li>' . form_label(lang('rr_contactfirstname'), 'f[contact][' . $cnt->getId() . '][fname]');
            $row .= '<span class="' . $class_cnt2 . '">' . form_input(array('name' => 'f[contact][' . $cnt->getId() . '][fname]', 'id' => 'f[contact][' . $cnt->getId() . '][fname]', 'value' => $t2)) . '</span></li>';
            $row .= '<li>' . form_label(lang('rr_contactlastname'), 'f[contact][' . $cnt->getId() . '][sname]');
            $row .= '<span class="' . $class_cnt3 . '">' . form_input(array('name' => 'f[contact][' . $cnt->getId() . '][sname]', 'id' => 'f[contact][' . $cnt->getId() . '][sname]', 'value' => $t3)) . '</span></li>';
            $row .= '<li>' . form_label(lang('rr_contactemail'), 'f[contact][' . $cnt->getId() . '][email]');
            $row .= '<span class="' . $class_cnt4 . '">' . form_input(array('name' => 'f[contact][' . $cnt->getId() . '][email]', 'id' => 'f[contact][' . $cnt->getId() . '][email]', 'value' => $t4)) . '</span></li>';
            $row .= '</ol>' . form_fieldset_close();
            $result[] = $row;
            if ($r)
            {
                unset($ses['contact']['' . $cnt->getId() . '']);
            }
        }
        if ($r && count($ses['contact'] > 0))
        {
            foreach ($ses['contact'] as $k => $v)
            {
                $n = '<fieldset class="newcontact"><legend>' . lang('rr_newcontact') . '</legend><ol>';
                $n .= '<li>' . form_label(lang('rr_contacttype'), 'f[contact][' . $k . '][type]');
                $n .= '<span>' . form_dropdown('f[contact][' . $k . '][type]', $formtypes, set_value('f[contact][' . $k . '][type]', $v['type'])) . '</span></li>';
                $n .= '<li>' . form_label(lang('rr_contactfirstname'), 'f[contact][' . $k . '][fname]');
                $n .= '<span>' . form_input(array('name' => 'f[contact][' . $k . '][fname]', 'id' => 'f[contact][' . $k . '][fname]', 'value' => set_value('f[contact][' . $k . '][fname]', $v['fname']))) . '</span></li>';
                $n .= '<li>' . form_label(lang('rr_contactlastname'), 'f[contact][' . $k . '][sname]');
                $n .= '<span>' . form_input(array('name' => 'f[contact][' . $k . '][sname]', 'id' => 'f[contact][' . $k . '][sname]', 'value' => set_value('f[contact][' . $k . '][sname]', $v['sname']))) . '</span></li>';
                $n .= '<li>' . form_label(lang('rr_contactemail'), 'f[contact][' . $k . '][email]');
                $n .= '<span>' . form_input(array('name' => 'f[contact][' . $k . '][email]', 'id' => 'f[contact][' . $k . '][email]', 'value' => set_value('f[contact][' . $k . '][email]', $v['email']))) . '</span></li>';
                $n .= '</ol>' . form_fieldset_close();
                $result[] = $n;
            }
        }
        $n = '<button class="btn" type="button" id="ncontactbtn">Add new Contact</button>';
        $result[] = $n;

        return $result;
    }

    public function NgenerateServiceLocationsForm(models\Provider $ent, $ses = null)
    {
        $ssotmpl = $this->ci->config->item('ssohandler_saml2');
        $ssotmpl = array_merge($ssotmpl, $this->ci->config->item('ssohandler_saml1'));

        $slotmpl = getBindSingleLogout();

        $result = array();
        $enttype = $ent->getType();
        $srvs = $ent->getServiceLocations();
        $g = array();
        $artifacts_binding = array(
            'urn:oasis:names:tc:SAML:2.0:bindings:SOAP' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding' => 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding',
        );
        foreach ($srvs as $s)
        {
            $g[$s->getType()][] = $s;
        }
        $sessform = FALSE;
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $sso = array();

        if ($enttype != 'SP')
        {
            /**
             * generate SSO part
             */
            $SSOPart = '<fieldset><legend>SingleSignOnService</legend><ol>';
            if (array_key_exists('SingleSignOnService', $g))
            {
                foreach ($g['SingleSignOnService'] as $k1 => $v1)
                {
                    if ($sessform && isset($ses['srv']['SingleSignOnService']['' . $v1->getId() . '']['url']))
                    {
                        $t1 = $ses['srv']['SingleSignOnService']['' . $v1->getId() . '']['url'];
                    }
                    else
                    {
                        $t1 = $v1->getUrl();
                    }
                    $t1 = set_value('f[srv][SingleSignOnService][' . $v1->getId() . '][url]', $t1);
                    $rnotice = '';
                    if ($t1 != $v1->getUrl())
                    {
                        $rnotice = 'notice';
                    }
                    $row = '<li>' . form_label($v1->getBindingName(), 'f[srv][SingleSignOnService][' . $v1->getId() . '][url]');
                    $row .= form_input(array(
                        'name' => 'f[srv][SingleSignOnService][' . $v1->getId() . '][bind]',
                        'id' => 'f[srv][SingleSignOnService][' . $v1->getId() . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][SingleSignOnService][' . $v1->getId() . '][bind]', $v1->getBindingName()),
                    ));
                    $row .= form_input(array(
                                'name' => 'f[srv][SingleSignOnService][' . $v1->getId() . '][url]',
                                'id' => 'f[srv][SingleSignOnService][' . $v1->getId() . '][url]',
                                'class' => $rnotice,
                                'value' => $t1,
                            )) . '</li>';
                    $sso[] = $row;
                    unset($ssotmpl[$v1->getBindingName()]);
                }
            }
            $i = 0;
            foreach ($ssotmpl as $km => $vm)
            {
                $rnotice = '';
                $value = set_value('f[srv][SingleSignOnService][n' . $i . '][url]');
                if (!empty($value))
                {
                    $rnotice = 'notice';
                }
                $r = '<li>' . form_label($km, 'f[srv][SingleSignOnService][n' . $i . '][url]');
                $r .= form_input(array(
                    'name' => 'f[srv][SingleSignOnService][n' . $i . '][bind]',
                    'id' => 'f[srv][SingleSignOnService][n' . $i . '][bind]',
                    'type' => 'hidden',
                    'value' => $vm,
                ));
                $r .= form_input(array(
                            'name' => 'f[srv][SingleSignOnService][n' . $i . '][url]',
                            'id' => 'f[srv][SingleSignOnService][n' . $i . '][url]',
                            'class' => $rnotice,
                            'value' => $value,
                        )) . '</li>';
                $sso[] = $r;
                ++$i;
            }
            // $result = array_merge($result,$sso);
            $SSOPart .= implode('', $sso);
            $SSOPart .= '</ol></fieldset>';
            $result[] = $SSOPart;
            // $slotmpl
            /**
             * IDP SingleLogoutService
             */
            $IDPSLOPart = '<fieldset><legend>IDP SingleLogoutService</legend><ol>';
            //$slotmpl = $this->ci->config->item('ssohandler_saml2');
            $slotmpl = getBindSingleLogout();
            $idpslo = array();
            if (array_key_exists('IDPSingleLogoutService', $g))
            {
                foreach ($g['IDPSingleLogoutService'] as $k2 => $v2)
                {
                    $row = '<li>' . form_label($v2->getBindingName(), 'f[srv][IDPSingleLogoutService][' . $v2->getId() . '][url]');
                    $row .= form_input(array(
                        'name' => 'f[srv][IDPSingleLogoutService][' . $v2->getId() . '][bind]',
                        'id' => 'f[srv][IDPSingleLogoutService][' . $v2->getId() . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][IDPSingleLogoutService][' . $v2->getId() . '][bind]', $v2->getBindingName()),
                    ));
                    $row .= form_input(array(
                                'name' => 'f[srv][IDPSingleLogoutService][' . $v2->getId() . '][url]',
                                'id' => 'f[srv][IDPSingleLogoutService][' . $v2->getId() . '][url]',
                                'value' => set_value('f[srv][IDPSingleLogoutService][' . $v2->getId() . '][url]', $v2->getUrl()),
                            )) . '</li>';
                    //unset($slotmpl[$v2->getBindingName()]);
                    unset($slotmpl[array_search($v2->getBindingName(), $slotmpl)]);
                    $idpslo[] = $row;
                }
            }
            $ni = 0;
            foreach ($slotmpl as $k3 => $v3)
            {
                $row = '<li>';
                $row .= form_label($v3, 'f[srv][IDPSingleLogoutService][n' . $ni . '][url]');
                $row .= form_input(array(
                    'name' => 'f[srv][IDPSingleLogoutService][n' . $ni . '][bind]',
                    'id' => 'f[srv][IDPSingleLogoutService][n' . $ni . '][bind]',
                    'type' => 'hidden',
                    'value' => $v3,));
                $row .= form_input(array(
                            'name' => 'f[srv][IDPSingleLogoutService][n' . $ni . '][url]',
                            'id' => 'f[srv][IDPSingleLogoutService][n' . $ni . '][url]',
                            'value' => set_value('f[srv][IDPSingleLogoutService][n' . $ni . '][url]'),
                        )) . '';
                $row .= '</li>';
                $idpslo[] = $row;
                ++$ni;
            }
            $IDPSLOPart .= implode('', $idpslo);
            $IDPSLOPart .= '</ol></fieldset>';
            $result[] = $IDPSLOPart;

            /**
             * generate IDP ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>ArtifactResolutionService <small><i>IDPSSODescriptor</i></small></legend><ol>';
            $acs = array();

            if (isset($g['IDPArtifactResolutionService']) && is_array($g['IDPArtifactResolutionService']))
            {
                foreach ($g['IDPArtifactResolutionService'] as $k3 => $v3)
                {
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    if ($sessform && isset($ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']))
                        {
                            $turl = $ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']['url'];
                        }
                        if (array_key_exists('order', $ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']))
                        {
                            $torder = $ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']['order'];
                        }
                        if (array_key_exists('bind', $ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']))
                        {
                            $tbind = $ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']['bind'];
                        }
                    }
                    $furl = set_value('f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][url]', $turl);
                    $forder = set_value('f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][order]', $torder);
                    $fbind = set_value('f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][bind]', $tbind);
                    $urlnotice = '';
                    $ordernotice = '';
                    $bindnotice = '';
                    if ($furl != $v3->getUrl())
                    {
                        $urlnotice = 'notice';
                    }
                    if ($forder != $v3->getOrder())
                    {
                        $ordernotice = 'notice';
                    }
                    if ($fbind != $v3->getBindingName())
                    {
                        $bindnotice = 'notice';
                    }


                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][bind]');
                    $r .= '<span class="' . $bindnotice . '">' . form_dropdown('f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][bind]', $artifacts_binding, $fbind) . '</span></li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][url]') . '';
                    $r .= form_input(array(
                                'name' => 'f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][url]',
                                'id' => 'f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][url]',
                                'value' => $furl,
                                'class' => 'acsurl ' . $urlnotice . '',
                            )) . '';
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][order]',
                                'id' => 'f[srv][IDPArtifactResolutionService][' . $v3->getId() . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex ' . $ordernotice,
                                'value' => $forder,
                    ));
                    $r .= '<br /></li>'; 

                    $r .='</ol></li>';
                    $acs[] = $r;
                    if ($sessform && isset($ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']))
                    {
                        unset($ses['srv']['IDPArtifactResolutionService']['' . $v3->getId() . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['IDPArtifactResolutionService']) && is_array($ses['srv']['IDPArtifactResolutionService']))
            {
                foreach ($ses['srv']['IDPArtifactResolutionService'] as $k4 => $v4)
                {


                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][IDPArtifactResolutionService][' . $k4 . '][bind]');
                    $r .= form_dropdown('f[srv][IDPArtifactResolutionService][' . $k4 . '][bind]', $artifacts_binding, $v4['bind']) . '</li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][IDPArtifactResolutionService][' . $k4 . '][url]');
                    $r .= form_input(array(
                        'name' => 'f[srv][IDPArtifactResolutionService][' . $k4 . '][url]',
                        'id' => 'f[srv][IDPArtifactResolutionService][' . $k4 . '][url]',
                        'value' => set_value('f[srv][IDPArtifactResolutionService][' . $k4 . '][url]', $ses['srv']['IDPArtifactResolutionService']['' . $k4 . '']['url']),
                        'class' => 'acsurl notice',
                    ));
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][IDPArtifactResolutionService][' . $k4 . '][order]',
                                'id' => 'f[srv][IDPArtifactResolutionService][' . $k4 . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex notice',
                                'value' => set_value('f[srv][IDPArtifactResolutionService][' . $k4 . '][order]', $ses['srv']['IDPArtifactResolutionService']['' . $k4 . '']['order']),
                            )) . '</li>';

                    $r .='</ol></li>';
                    $acs[] = $r;
                }
            }
            $ACSPart .= implode('', $acs);
            $newelement = '<li><button class="btn" type="button" id="nidpartifactbtn">Add new IDP ArtifactResolutionService</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end IDPArtifactResolutionService part
             */








            /**
             * start AttributeAuthorityDescriptor Locations
             */
            $aabinds = getAllowedSOAPBindings();
            $aalo = array();
            if (array_key_exists('IDPAttributeService', $g))
            {
                foreach ($g['IDPAttributeService'] as $k2 => $v2)
                {
                    $row = '<li>' . form_label($v2->getBindingName(), 'f[srv][IDPAttributeService][' . $v2->getId() . '][url]');
                    $row .= form_input(array(
                        'name' => 'f[srv][IDPAttributeService][' . $v2->getId() . '][bind]',
                        'id' => 'f[srv][IDPAttributeService][' . $v2->getId() . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][IDPAttributeService][' . $v2->getId() . '][bind]', $v2->getBindingName()),
                    ));
                    $row .= form_input(array(
                                'name' => 'f[srv][IDPAttributeService][' . $v2->getId() . '][url]',
                                'id' => 'f[srv][IDPAttributeService][' . $v2->getId() . '][url]',
                                'value' => set_value('f[srv][IDPAttributeService][' . $v2->getId() . '][url]', $v2->getUrl()),
                            )) . '</li>';
                    unset($aabinds[array_search($v2->getBindingName(), $aabinds)]);
                    $aalo[] = $row;
                }
            }
            $ni = 0;
            foreach ($aabinds as $k3 => $v3)
            {
                $row = '<li>';
                $row .= form_label($v3, 'f[srv][IDPAttributeService][n' . $ni . '][url]');
                $row .= form_input(array(
                    'name' => 'f[srv][IDPAttributeService][n' . $ni . '][bind]',
                    'id' => 'f[srv][IDPAttributeService][n' . $ni . '][bind]',
                    'type' => 'hidden',
                    'value' => $v3,));
                $row .= form_input(array(
                            'name' => 'f[srv][IDPAttributeService][n' . $ni . '][url]',
                            'id' => 'f[srv][IDPAttributeService][n' . $ni . '][url]',
                            'value' => set_value('f[srv][IDPAttributeService][n' . $ni . '][url]'),
                        )) . '';
                $row .= '</li>';
                $aalo[] = $row;
                ++$ni;
            }
            $result[] = '<fieldset><legend>AA</legend><ol>' . implode('', $aalo) . '</ol></fieldset>';

            /**
             * end AttributeAuthorityDescriptor Location
             */
        }
        if ($enttype != 'IDP')
        {
            /**
             * generate ACS part
             */
            $ACSPart = '<fieldset><legend>Assertion Consumer Service</legend><ol>';
            $acs = array();

            if (isset($g['AssertionConsumerService']) && is_array($g['AssertionConsumerService']))
            {
                foreach ($g['AssertionConsumerService'] as $k3 => $v3)
                {
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    if ($sessform && isset($ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']))
                        {
                            $turl = $ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']['url'];
                        }
                        if (array_key_exists('order', $ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']))
                        {
                            $torder = $ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']['order'];
                        }
                        if (array_key_exists('bind', $ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']))
                        {
                            $tbind = $ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']['bind'];
                        }
                    }
                    $furl = set_value('f[srv][AssertionConsumerService][' . $v3->getId() . '][url]', $turl);
                    $forder = set_value('f[srv][AssertionConsumerService][' . $v3->getId() . '][order]', $torder);
                    $fbind = set_value('f[srv][AssertionConsumerService][' . $v3->getId() . '][bind]', $tbind);
                    $urlnotice = '';
                    $ordernotice = '';
                    $bindnotice = '';
                    if ($furl != $v3->getUrl())
                    {
                        $urlnotice = 'notice';
                    }
                    if ($forder != $v3->getOrder())
                    {
                        $ordernotice = 'notice';
                    }
                    if ($fbind != $v3->getBindingName())
                    {
                        $bindnotice = 'notice';
                    }
                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][AssertionConsumerService][' . $v3->getId() . '][bind]');
                    $r .= '<span class="' . $bindnotice . '">' . form_dropdown('f[srv][AssertionConsumerService][' . $v3->getId() . '][bind]', $this->ci->config->item('acs_binding'), $fbind) . '</span></li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][AssertionConsumerService][' . $v3->getId() . '][url]') . '';
                    $r .= form_input(array(
                                'name' => 'f[srv][AssertionConsumerService][' . $v3->getId() . '][url]',
                                'id' => 'f[srv][AssertionConsumerService][' . $v3->getId() . '][url]',
                                'value' => $furl,
                                'class' => 'acsurl ' . $urlnotice . '',
                            )) . '';
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][AssertionConsumerService][' . $v3->getId() . '][order]',
                                'id' => 'f[srv][AssertionConsumerService][' . $v3->getId() . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex ' . $ordernotice,
                                'value' => $forder,
                    ));
                    $r .= '<br /></li><li>' . form_label(lang('rr_isdefault') . '?', 'f[srv][AssertionConsumerService][' . $v3->getId() . '][default]');
                    $ischecked = FALSE;
                    if ($sessform)
                    {
                        if (isset($ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']['default']))
                        {
                            $ischecked = TRUE;
                        }
                    }
                    else
                    {
                        if ($v3->getDefault())
                        {
                            $ischecked = TRUE;
                        }
                    }
                    $acsnotice = '';
                    if ($ischecked != $v3->getDefault())
                    {
                        $acsnotice = 'notice';
                    }
                    $r .= form_radio(array(
                        'name' => 'f[srv][AssertionConsumerService][' . $v3->getId() . '][default]',
                        'id' => 'f[srv][AssertionConsumerService][' . $v3->getId() . '][default]',
                        'value' => 1,
                        'class' => 'acsdefault ' . $acsnotice,
                        'checked' => $ischecked,
                    ));

                    $r .='</li></ol></li>';
                    $acs[] = $r;
                    if ($sessform && isset($ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']))
                    {
                        unset($ses['srv']['AssertionConsumerService']['' . $v3->getId() . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['AssertionConsumerService']) && is_array($ses['srv']['AssertionConsumerService']))
            {
                foreach ($ses['srv']['AssertionConsumerService'] as $k4 => $v4)
                {


                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][AssertionConsumerService][' . $k4 . '][bind]');
                    $r .= form_dropdown('f[srv][AssertionConsumerService][' . $k4 . '][bind]', $this->ci->config->item('acs_binding'), $v4['bind']) . '</li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][AssertionConsumerService][' . $k4 . '][url]');
                    $r .= form_input(array(
                        'name' => 'f[srv][AssertionConsumerService][' . $k4 . '][url]',
                        'id' => 'f[srv][AssertionConsumerService][' . $k4 . '][url]',
                        'value' => set_value('f[srv][AssertionConsumerService][' . $k4 . '][url]', $ses['srv']['AssertionConsumerService']['' . $k4 . '']['url']),
                        'class' => 'acsurl notice',
                    ));
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][AssertionConsumerService][' . $k4 . '][order]',
                                'id' => 'f[srv][AssertionConsumerService][' . $k4 . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex notice',
                                'value' => set_value('f[srv][AssertionConsumerService][' . $k4 . '][order]', $ses['srv']['AssertionConsumerService']['' . $k4 . '']['order']),
                            )) . '</li>';
                    $r .= '<li>' . form_label(lang('rr_isdefault'), 'f[srv][AssertionConsumerService][' . $k4 . '][default]');
                    $ischecked = FALSE;

                    if (isset($ses['srv']['AssertionConsumerService']['' . $k4 . '']['default']))
                    {
                        $ischecked = TRUE;
                    }

                    $r .= form_radio(array(
                                'name' => 'f[srv][AssertionConsumerService][' . $k4 . '][default]',
                                'id' => 'f[srv][AssertionConsumerService][' . $k4 . '][default]',
                                'value' => 1,
                                'class' => 'acsdefault notice',
                                'checked' => $ischecked,
                            )) . '';

                    $r .='</li></ol></li>';
                    $acs[] = $r;
                }
            }
            $ACSPart .= implode('', $acs);
            $newelement = '<li><button class="btn" type="button" id="nacsbtn">Add new ACS URL</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end ACS part
             */
            /**
             * generate ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>ArtifactResolutionService <small><i>SPSSODescriptor</i></small></legend><ol>';
            $acs = array();

            if (isset($g['SPArtifactResolutionService']) && is_array($g['SPArtifactResolutionService']))
            {
                foreach ($g['SPArtifactResolutionService'] as $k3 => $v3)
                {
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    if ($sessform && isset($ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']))
                        {
                            $turl = $ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']['url'];
                        }
                        if (array_key_exists('order', $ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']))
                        {
                            $torder = $ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']['order'];
                        }
                        if (array_key_exists('bind', $ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']))
                        {
                            $tbind = $ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']['bind'];
                        }
                    }
                    $furl = set_value('f[srv][SPArtifactResolutionService][' . $v3->getId() . '][url]', $turl);
                    $forder = set_value('f[srv][SPArtifactResolutionService][' . $v3->getId() . '][order]', $torder);
                    $fbind = set_value('f[srv][SPArtifactResolutionService][' . $v3->getId() . '][bind]', $tbind);
                    $urlnotice = '';
                    $ordernotice = '';
                    $bindnotice = '';
                    if ($furl != $v3->getUrl())
                    {
                        $urlnotice = 'notice';
                    }
                    if ($forder != $v3->getOrder())
                    {
                        $ordernotice = 'notice';
                    }
                    if ($fbind != $v3->getBindingName())
                    {
                        $bindnotice = 'notice';
                    }


                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][SPArtifactResolutionService][' . $v3->getId() . '][bind]');
                    $r .= '<span class="' . $bindnotice . '">' . form_dropdown('f[srv][SPArtifactResolutionService][' . $v3->getId() . '][bind]', $artifacts_binding, $fbind) . '</span></li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][SPArtifactResolutionService][' . $v3->getId() . '][url]') . '';
                    $r .= form_input(array(
                                'name' => 'f[srv][SPArtifactResolutionService][' . $v3->getId() . '][url]',
                                'id' => 'f[srv][SPArtifactResolutionService][' . $v3->getId() . '][url]',
                                'value' => $furl,
                                'class' => 'acsurl ' . $urlnotice . '',
                            )) . '';
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][SPArtifactResolutionService][' . $v3->getId() . '][order]',
                                'id' => 'f[srv][SPArtifactResolutionService][' . $v3->getId() . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex ' . $ordernotice,
                                'value' => $forder,
                    ));
                    $r .= '<br /></li>'; 

                    $r .='</ol></li>';
                    $acs[] = $r;
                    if ($sessform && isset($ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']))
                    {
                        unset($ses['srv']['SPArtifactResolutionService']['' . $v3->getId() . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['SPArtifactResolutionService']) && is_array($ses['srv']['SPArtifactResolutionService']))
            {
                foreach ($ses['srv']['SPArtifactResolutionService'] as $k4 => $v4)
                {


                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][SPArtifactResolutionService][' . $k4 . '][bind]');
                    $r .= form_dropdown('f[srv][SPArtifactResolutionService][' . $k4 . '][bind]', $artifacts_binding, $v4['bind']) . '</li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][SPArtifactResolutionService][' . $k4 . '][url]');
                    $r .= form_input(array(
                        'name' => 'f[srv][SPArtifactResolutionService][' . $k4 . '][url]',
                        'id' => 'f[srv][SPArtifactResolutionService][' . $k4 . '][url]',
                        'value' => set_value('f[srv][SPArtifactResolutionService][' . $k4 . '][url]', $ses['srv']['SPArtifactResolutionService']['' . $k4 . '']['url']),
                        'class' => 'acsurl notice',
                    ));
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][SPArtifactResolutionService][' . $k4 . '][order]',
                                'id' => 'f[srv][SPArtifactResolutionService][' . $k4 . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex notice',
                                'value' => set_value('f[srv][SPArtifactResolutionService][' . $k4 . '][order]', $ses['srv']['SPArtifactResolutionService']['' . $k4 . '']['order']),
                            )) . '</li>';

                    $r .='</ol></li>';
                    $acs[] = $r;
                }
            }
            $ACSPart .= implode('', $acs);
            $newelement = '<li><button class="btn" type="button" id="nspartifactbtn">Add new ArtifactResolutionService</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end SPArtifactResolutionService part
             */















            /**
             * start SP SingleLogoutService
             */
            $SPSLOPart = '<fieldset><legend>SP SingleLogoutService</legend><ol>';
            $spslotmpl = getBindSingleLogout();
            $spslo = array();
            if (array_key_exists('SPSingleLogoutService', $g))
            {
                foreach ($g['SPSingleLogoutService'] as $k2 => $v2)
                {
                    $row = '<li>' . form_label($v2->getBindingName(), 'f[srv][SPSingleLogoutService][' . $v2->getId() . '][url]');
                    $row .= form_input(array(
                        'name' => 'f[srv][SPSingleLogoutService][' . $v2->getId() . '][bind]',
                        'id' => 'f[srv][SPSingleLogoutService][' . $v2->getId() . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][SPSingleLogoutService][' . $v2->getId() . '][bind]', $v2->getBindingName()),
                    ));
                    $row .= form_input(array(
                                'name' => 'f[srv][SPSingleLogoutService][' . $v2->getId() . '][url]',
                                'id' => 'f[srv][SPSingleLogoutService][' . $v2->getId() . '][url]',
                                'value' => set_value('f[srv][SPSingleLogoutService][' . $v2->getId() . '][url]', $v2->getUrl()),
                            )) . '</li>';
                    unset($spslotmpl[array_search($v2->getBindingName(), $spslotmpl)]);
                    $spslo[] = $row;
                }
            }
            $ni = 0;
            foreach ($spslotmpl as $k3 => $v3)
            {
                $row = '<li>';
                $row .= form_label($v3, 'f[srv][SPSingleLogoutService][n' . $ni . '][url]');
                $row .= form_input(array(
                    'name' => 'f[srv][SPSingleLogoutService][n' . $ni . '][bind]',
                    'id' => 'f[srv][SPSingleLogoutService][n' . $ni . '][bind]',
                    'type' => 'hidden',
                    'value' => $v3,));
                $row .= form_input(array(
                            'name' => 'f[srv][SPSingleLogoutService][n' . $ni . '][url]',
                            'id' => 'f[srv][SPSingleLogoutService][n' . $ni . '][url]',
                            'value' => set_value('f[srv][SPSingleLogoutService][n' . $ni . '][url]'),
                        )) . '';
                $row .= '</li>';
                $spslo[] = $row;
                ++$ni;
            }
            $SPSLOPart .= implode('', $spslo);
            $SPSLOPart .= '</ol></fieldset>';
            $result[] = $SPSLOPart;
            /**
             * end SP SingleLogoutService
              /**
             * start RequestInitiator
             */
            $RequestInitiatorPart = '<fieldset><legend>RequestInitiator Locations</legend><ol>';
            $ri = array();
            if (array_key_exists('RequestInitiator', $g))
            {
                foreach ($g['RequestInitiator'] as $k3 => $v3)
                {
                    $turl = $v3->getUrl();
                    if ($sessform && isset($ses['srv']['RequestInitiator']['' . $v3->getId() . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['RequestInitiator']['' . $v3->getId() . '']))
                        {
                            $turl = $ses['srv']['RequestInitiator'][$v3->getId()]['url'];
                        }
                    }
                    $furl = set_value('f[srv][RequestInitiator][' . $v3->getId() . '][url]', $turl);
                    $urlnotice = '';
                    if ($furl != $v3->getUrl())
                    {
                        $urlnotice = 'notice';
                    }
                    $r = '<li>';
                    $r .= '' . form_label(lang('rr_url'), 'f[srv][RequestInitiator][' . $v3->getId() . '][url]');
                    $r .= form_input(array(
                        'name' => 'f[srv][RequestInitiator][' . $v3->getId() . '][url]',
                        'id' => 'f[srv][RequestInitiator][' . $v3->getId() . '][url]',
                        'value' => $furl,
                        'class' => 'acsurl ' . $urlnotice . '',
                    ));
                    $r .= '</li>';
                    $ri[] = $r;
                    if (isset($ses['srv']['RequestInitiator']['' . $v3->getId() . '']))
                    {
                        unset($ses['srv']['RequestInitiator']['' . $v3->getId() . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['RequestInitiator']) && is_array($ses['srv']['RequestInitiator']))
            {
                foreach ($ses['srv']['RequestInitiator'] as $k4 => $v4)
                {
                    $purl = '';
                    if (isset($ses['srv']['RequestInitiator']['' . $k4 . '']['url']))
                    {
                        $purl = $ses['srv']['RequestInitiator']['' . $k4 . '']['url'];
                    }

                    $r = '<li>' . form_label(lang('rr_url'), 'f[srv][RequestInitiator][' . $k4 . '][url]');
                    $r .= form_input(array(
                                'name' => 'f[srv][RequestInitiator][' . $k4 . '][url]',
                                'id' => 'f[srv][RequestInitiator][' . $k4 . '][url]',
                                'value' => set_value('f[srv][RequestInitiator][' . $k4 . '][url]', $purl),
                                'class' => 'acsurl notice',
                            )) . '</li>';
                    $ri[] = $r;
                    unset($ses['srv']['RequestInitiator']['' . $k4 . '']);
                }
            }
            $RequestInitiatorPart .= implode('', $ri);
            $newelement = '<li><button class="btn" type="button" id="nribtn">Add new RequestInitiator URL</button></li>';
            $RequestInitiatorPart .= $newelement . '</ol><fieldset>';
            $result[] = $RequestInitiatorPart;
            /**
             * end RequestInitiator
             */
            /**
             * start DiscoveryResponse
             */
            $DiscoverResponsePart = '<fieldset><legend>Discovery Response Locations</legend><ol>';
            $dr = array();
            /**
             * list existing DiscoveryResponse
             */
            if (array_key_exists('DiscoveryResponse', $g))
            {
                foreach ($g['DiscoveryResponse'] as $k3 => $v3)
                {
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    if ($sessform && isset($ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']))
                        {
                            $turl = $ses['srv']['DiscoveryResponse'][$v3->getId()]['url'];
                        }
                        if (array_key_exists('order', $ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']))
                        {
                            $torder = $ses['srv']['DiscoveryResponse'][$v3->getId()]['order'];
                        }
                        if (array_key_exists('bind', $ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']))
                        {
                            $tbind = $ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']['bind'];
                        }
                    }
                    $furl = set_value('f[srv][DiscoveryResponse][' . $v3->getId() . '][url]', $turl);
                    $forder = set_value('f[srv][DiscoveryResponse][' . $v3->getId() . '][order]', $torder);
                    $fbind = set_value('f[srv][DiscoveryResponse][' . $v3->getId() . '][bind]', $tbind);
                    $urlnotice = '';
                    $ordernotice = '';
                    $bindnotice = '';
                    if ($furl != $v3->getUrl())
                    {
                        $urlnotice = 'notice';
                    }
                    if ($forder != $v3->getOrder())
                    {
                        $ordernotice = 'notice';
                    }
                    if ($fbind != $v3->getBindingName())
                    {
                        $bindnotice = 'notice';
                    }
                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][DiscoveryResponse][' . $v3->getId() . '][bind]');
                    $r .= '<span class="' . $bindnotice . '">' . form_dropdown('f[srv][DiscoveryResponse][' . $v3->getId() . '][bind]', array('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol' => 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol'), $fbind) . '</span></li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][DiscoveryResponse][' . $v3->getId() . '][url]');
                    $r .= form_input(array(
                                'name' => 'f[srv][DiscoveryResponse][' . $v3->getId() . '][url]',
                                'id' => 'f[srv][DiscoveryResponse][' . $v3->getId() . '][url]',
                                'value' => $furl,
                                'class' => 'acsurl ' . $urlnotice . '',
                            )) . '';
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][DiscoveryResponse][' . $v3->getId() . '][order]',
                                'id' => 'f[srv][DiscoveryResponse][' . $v3->getId() . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex ' . $ordernotice,
                                'value' => $forder,
                    ));
                    $r .= '<br /></li>';





                    $r .='</ol></li>';
                    $dr[] = $r;
                    if (isset($ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']))
                    {
                        unset($ses['srv']['DiscoveryResponse']['' . $v3->getId() . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['DiscoveryResponse']) && is_array($ses['srv']['DiscoveryResponse']))
            {
                foreach ($ses['srv']['DiscoveryResponse'] as $k4 => $v4)
                {


                    $r = '<li><ol>';
                    $r .= '<li>' . form_label(lang('rr_bindingname'), 'f[srv][DiscoveryResponse][' . $k4 . '][bind]');
                    $r .= form_dropdown('f[srv][DiscoveryResponse][' . $k4 . '][bind]', array('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol' => 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol'), $v4['bind']) . '</li>';
                    $r .= '<li>' . form_label(lang('rr_url'), 'f[srv][DiscoveryResponse][' . $k4 . '][url]');
                    $r .= form_input(array(
                                'name' => 'f[srv][DiscoveryResponse][' . $k4 . '][url]',
                                'id' => 'f[srv][DiscoveryResponse][' . $k4 . '][url]',
                                'value' => set_value('f[srv][DiscoveryResponse][' . $k4 . '][url]', $ses['srv']['DiscoveryResponse']['' . $k4 . '']['url']),
                                'class' => 'acsurl notice',
                            )) . '';
                    $r .= 'index ' . form_input(array(
                                'name' => 'f[srv][DiscoveryResponse][' . $k4 . '][order]',
                                'id' => 'f[srv][DiscoveryResponse][' . $k4 . '][order]',
                                'size' => '3',
                                'maxlength' => '3',
                                'class' => 'acsindex notice',
                                'value' => set_value('f[srv][DiscoveryResponse][' . $k4 . '][order]', $ses['srv']['DiscoveryResponse']['' . $k4 . '']['order']),
                            )) . '</li>';

                    $r .='</ol></li>';
                    $dr[] = $r;
                    unset($ses['srv']['DiscoveryResponse']['' . $k4 . '']);
                }
            }
            $DiscoverResponsePart .= implode('', $dr);
            $newelement = '<li><button class="btn" type="button" id="ndrbtn">Add new DiscoveryResponse URL</button></li>';
            $DiscoverResponsePart .= $newelement . '</ol><fieldset>';
            $result[] = $DiscoverResponsePart;
        }

        foreach ($g as $k => $v)
        {
            
        }


        return $result;
    }

    public function NgenerateCertificatesForm(models\Provider $ent, $ses = null)
    {
        $result = array();
        $sessform = FALSE;
        $enttype = $ent->getType();
        $c = $ent->getCertificates();
        foreach ($c as $v)
        {
            $cert['' . $v->getType() . ''][] = $v;
        }
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        if ($enttype != 'SP')
        {
            /**
             * start CERTS IDPSSODescriptor
             */
            $Part = '<fieldset><legend>IDP Certificates <i>IDPSSODesciptor</i></legend><ol>';
            $idpssocerts = array();
            if (isset($cert['idpsso']) && is_array($cert['idpsso']))
            {
                foreach ($cert['idpsso'] as $k => $v)
                {
                    $crtid = $v->getId();
                    $tkeyname = $v->getKeyName();
                    $tusage = $v->getCertUse();
                    $tcertdata = $v->getPEM($v->getCertData());
                    $origcertdata = $tcertdata;
                    if (empty($tusage))
                    {
                        $tusage = 'both';
                    }
                    $origusage = $tusage;
                    if ($sessform & isset($ses['crt']['idpsso']['' . $crtid . '']))
                    {
                        if (array_key_exists('keyname', $ses['crt']['idpsso']['' . $crtid . '']))
                        {
                            $tkeyname = $ses['crt']['idpsso']['' . $crtid . '']['keyname'];
                        }
                        if (array_key_exists('usage', $ses['crt']['idpsso']['' . $crtid . '']))
                        {
                            $tusage = $ses['crt']['idpsso']['' . $crtid . '']['usage'];
                        }
                        if (array_key_exists('certdata', $ses['crt']['idpsso']['' . $crtid . '']))
                        {
                            $tcertdata = $ses['crt']['idpsso']['' . $crtid . '']['certdata'];
                        }
                    }
                    $fkeyname = set_value('f[crt][idpsso][' . $crtid . '][keyname]', $tkeyname);
                    $fusage = set_value('f[crt][idpsso][' . $crtid . '][usage]', $tusage);
                    $fcertdata = set_value('f[crt][idpsso][' . $crtid . '][certdata]', $tcertdata);

                    $keynamenotice = '';
                    $usagenotice = '';
                    $certdatanotice = '';
                    if ($fkeyname != $v->getKeyName())
                    {
                        $keynamenotice = 'notice';
                    }
                    if ($fusage != $origusage)
                    {
                        $usagenotice = 'notice';
                    }
                    if ($fcertdata != $origcertdata)
                    {
                        $certdatanotice = 'notice';
                    }


                    $row = '<li>';
                    $row .= form_label(lang('rr_pleaseremove'), 'f[crt][idpsso][' . $crtid . '][remove]');
                    $row .= form_dropdown('f[crt][idpsso][' . $crtid . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit'))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][idpsso][' . $crtid . '][type]');
                    $row .= form_dropdown('f[crt][idpsso][' . $crtid . '][type]', array('x509' => 'x509')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][idpsso][' . $crtid . '][usage]');
                    $row .= '<span class="' . $usagenotice . '">' . form_dropdown('f[crt][idpsso][' . $crtid . '][usage]', array('signing' => 'signing', 'encryption' => 'encryption', 'both' => 'signing and encryption'), $fusage) . '</span></li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'f[crt][idpsso][' . $crtid . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][idpsso][' . $crtid . '][keyname]',
                                'id' => 'f[crt][idpsso][' . $crtid . '][keyname]',
                                'class' => $keynamenotice,
                                'value' => $fkeyname)) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'f[crt][idpsso][' . $crtid . '][certdata]');
                    $row .= form_textarea(array(
                                'name' => 'f[crt][idpsso][' . $crtid . '][certdata]',
                                'id' => 'f[crt][idpsso][' . $crtid . '][certdata]',
                                'cols' => 65,
                                'rows' => 40,
                                'class' => 'certdata ' . $certdatanotice,
                                'value' => $fcertdata,
                            )) . '</li>';
                    $idpssocerts[] = $row;
                    if ($sessform && isset($ses['crt']['idpsso']['' . $crtid . '']))
                    {
                        unset($ses['crt']['idpsso']['' . $crtid . '']);
                    }
                }
            }
            if ($sessform && isset($ses['crt']['idpsso']) && is_array($ses['crt']['idpsso']))
            {
                foreach ($ses['crt']['idpsso'] as $k4 => $v4)
                {
                    $row = '<li>';
                    $row .= form_label(lang('rr_pleaseremove'), 'f[crt][idpsso][' . $k4 . '][remove]');
                    $row .= form_dropdown('f[crt][idpsso][' . $k4 . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit'))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][idpsso][' . $k4 . '][type]');
                    $row .= form_dropdown('f[crt][idpsso][' . $k4 . '][type]', array('x509' => 'x509')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][idpsso][' . $k4 . '][usage]');
                    $row .= form_dropdown('f[crt][idpsso][' . $k4 . '][usage]', array('signing' => 'signing', 'encryption' => 'encryption', 'both' => 'signing and encryption'), set_value('f[crt][idpsso][' . $k4 . '][usage]', $v4['usage'])) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'f[crt][idpsso][' . $k4 . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][idpsso][' . $k4 . '][keyname]',
                                'id' => 'f[crt][idpsso][' . $k4 . '][keyname]',
                                'class' => 'notice',
                                'value' => set_value('f[crt][idpsso][' . $k4 . '][keyname]', $v4['keyname']))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'f[crt][idpsso][' . $k4 . '][certdata]');
                    $row .= form_textarea(array(
                                'name' => 'f[crt][idpsso][' . $k4 . '][certdata]',
                                'id' => 'f[crt][idpsso][' . $k4 . '][certdata]',
                                'cols' => 65,
                                'rows' => 40,
                                'class' => 'certdata ' . $certdatanotice,
                                'value' => set_value('f[crt][idpsso][' . $k4 . '][certdata]', $v4['certdata']),
                            )) . '</li>';
                    $idpssocerts[] = $row;
                }
            }
            $Part .= implode('', $idpssocerts);
            $newelement = '<li><button class="btn" type="button" id="nidpssocert">Add new Certificate</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
            $Part = '';
            /**
             * end CERTs for IDPSSODescriptor
             */
            /**
             * start CERTs for AttributeAuthority
             */
            $Part = '<fieldset><legend>IDP Certificates <i>AttributeAuthorityDesciptor</i></legend><ol>';
            $aacerts = array();
            if (isset($cert['aa']) && is_array($cert['aa']))
            {
                foreach ($cert['aa'] as $k => $v)
                {
                    $crtid = $v->getId();
                    $tkeyname = $v->getKeyName();
                    $tusage = $v->getCertUse();
                    $tcertdata = $v->getPEM($v->getCertData());
                    $origcertdata = $tcertdata;
                    if (empty($tusage))
                    {
                        $tusage = 'both';
                    }
                    $origusage = $tusage;
                    if ($sessform & isset($ses['crt']['aa']['' . $crtid . '']))
                    {
                        if (array_key_exists('keyname', $ses['crt']['aa']['' . $crtid . '']))
                        {
                            $tkeyname = $ses['crt']['aa']['' . $crtid . '']['keyname'];
                        }
                        if (array_key_exists('usage', $ses['crt']['aa']['' . $crtid . '']))
                        {
                            $tusage = $ses['crt']['aa']['' . $crtid . '']['usage'];
                        }
                        if (array_key_exists('certdata', $ses['crt']['aa']['' . $crtid . '']))
                        {
                            $tcertdata = $ses['crt']['aa']['' . $crtid . '']['certdata'];
                        }
                    }
                    $fkeyname = set_value('f[crt][aa][' . $crtid . '][keyname]', $tkeyname);
                    $fusage = set_value('f[crt][aa][' . $crtid . '][usage]', $tusage);
                    $fcertdata = set_value('f[crt][aa][' . $crtid . '][certdata]', $tcertdata);

                    $keynamenotice = '';
                    $usagenotice = '';
                    $certdatanotice = '';
                    if ($fkeyname != $v->getKeyName())
                    {
                        $keynamenotice = 'notice';
                    }
                    if ($fusage != $origusage)
                    {
                        $usagenotice = 'notice';
                    }
                    if ($fcertdata != $origcertdata)
                    {
                        $certdatanotice = 'notice';
                    }


                    $row = '<li>';
                    $row .= form_label(lang('rr_pleaseremove'), 'f[crt][aa][' . $crtid . '][remove]');
                    $row .= form_dropdown('f[crt][aa][' . $crtid . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit'))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][aa][' . $crtid . '][type]');
                    $row .= form_dropdown('f[crt][aa][' . $crtid . '][type]', array('x509' => 'x509')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][aa][' . $crtid . '][usage]');
                    $row .= '<span class="' . $usagenotice . '">' . form_dropdown('f[crt][aa][' . $crtid . '][usage]', array('signing' => 'signing', 'encryption' => 'encryption', 'both' => 'signing and encryption'), $fusage) . '</span></li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'f[crt][aa][' . $crtid . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][aa][' . $crtid . '][keyname]',
                                'id' => 'f[crt][aa][' . $crtid . '][keyname]',
                                'class' => $keynamenotice,
                                'value' => $fkeyname)) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'f[crt][aa][' . $crtid . '][certdata]');
                    $row .= form_textarea(array(
                                'name' => 'f[crt][aa][' . $crtid . '][certdata]',
                                'id' => 'f[crt][aa][' . $crtid . '][certdata]',
                                'cols' => 65,
                                'rows' => 40,
                                'class' => 'certdata ' . $certdatanotice,
                                'value' => $fcertdata,
                            )) . '</li>';
                    $aacerts[] = $row;
                    if ($sessform && isset($ses['crt']['aa']['' . $crtid . '']))
                    {
                        unset($ses['crt']['aa']['' . $crtid . '']);
                    }
                }
            }
            if ($sessform && isset($ses['crt']['aa']) && is_array($ses['crt']['aa']))
            {
                foreach ($ses['crt']['aa'] as $k4 => $v4)
                {
                    $row = '<li>';
                    $row .= form_label(lang('rr_pleaseremove'), 'f[crt][aa][' . $k4 . '][remove]');
                    $row .= form_dropdown('f[crt][aa][' . $k4 . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit'))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][aa][' . $k4 . '][type]');
                    $row .= form_dropdown('f[crt][aa][' . $k4 . '][type]', array('x509' => 'x509')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][aa][' . $k4 . '][usage]');
                    $row .= form_dropdown('f[crt][aa][' . $k4 . '][usage]', array('signing' => 'signing', 'encryption' => 'encryption', 'both' => 'signing and encryption'), set_value('f[crt][aa][' . $k4 . '][usage]', $v4['usage'])) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'f[crt][aa][' . $k4 . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][aa][' . $k4 . '][keyname]',
                                'id' => 'f[crt][aa][' . $k4 . '][keyname]',
                                'class' => 'notice',
                                'value' => set_value('f[crt][aa][' . $k4 . '][keyname]', $v4['keyname']))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'f[crt][aa][' . $k4 . '][certdata]');
                    $row .= form_textarea(array(
                                'name' => 'f[crt][aa][' . $k4 . '][certdata]',
                                'id' => 'f[crt][aa][' . $k4 . '][certdata]',
                                'cols' => 65,
                                'rows' => 40,
                                'class' => 'certdata ' . $certdatanotice,
                                'value' => set_value('f[crt][aa][' . $k4 . '][certdata]', $v4['certdata']),
                            )) . '</li>';
                    $aacerts[] = $row;
                }
            }
            $Part .= implode('', $aacerts);
            $newelement = '<li><button class="btn" type="button" id="naacert">Add new Certificate for AttributeAuthorityDescriptor</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
            $Part = '';
        }
        if ($enttype != 'IDP')
        {
            $Part = '<fieldset><legend>SP Certs</legend><ol>';
            $spssocerts = array();
            if (isset($cert['spsso']) && is_array($cert['spsso']))
            {
                foreach ($cert['spsso'] as $k => $v)
                {
                    $crtid = $v->getId();
                    log_message('debug', 'GGG certid:' . $crtid);
                    $tkeyname = $v->getKeyName();
                    $tusage = $v->getCertUse();
                    $tcertdata = $v->getPEM($v->getCertData());
                    $origcertdata = $tcertdata;
                    if (empty($tusage))
                    {
                        $tusage = 'both';
                    }
                    $origusage = $tusage;
                    if ($sessform & isset($ses['crt']['spsso']['' . $crtid . '']))
                    {
                        if (array_key_exists('keyname', $ses['crt']['spsso']['' . $crtid . '']))
                        {
                            $tkeyname = $ses['crt']['spsso']['' . $crtid . '']['keyname'];
                        }
                        if (array_key_exists('usage', $ses['crt']['spsso']['' . $crtid . '']))
                        {
                            $tusage = $ses['crt']['spsso']['' . $crtid . '']['usage'];
                        }
                        if (array_key_exists('certdata', $ses['crt']['spsso']['' . $crtid . '']))
                        {
                            $tcertdata = $ses['crt']['spsso']['' . $crtid . '']['certdata'];
                        }
                    }
                    $fkeyname = set_value('f[crt][spsso][' . $crtid . '][keyname]', $tkeyname);
                    $fusage = set_value('f[crt][spsso][' . $crtid . '][usage]', $tusage);
                    $fcertdata = set_value('f[crt][spsso][' . $crtid . '][certdata]', $tcertdata);

                    $keynamenotice = '';
                    $usagenotice = '';
                    $certdatanotice = '';
                    if ($fkeyname != $v->getKeyName())
                    {
                        $keynamenotice = 'notice';
                    }
                    if ($fusage != $origusage)
                    {
                        $usagenotice = 'notice';
                    }
                    if ($fcertdata != $origcertdata)
                    {
                        $certdatanotice = 'notice';
                    }


                    $row = '<li>';
                    $row .= form_label(lang('rr_pleaseremove'), 'f[crt][spsso][' . $crtid . '][remove]');
                    $row .= form_dropdown('f[crt][spsso][' . $crtid . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit'))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][spsso][' . $crtid . '][type]');
                    $row .= form_dropdown('f[crt][spsso][' . $crtid . '][type]', array('x509' => 'x509')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][spsso][' . $crtid . '][usage]');
                    $row .= '<span class="' . $usagenotice . '">' . form_dropdown('f[crt][spsso][' . $crtid . '][usage]', array('signing' => 'signing', 'encryption' => 'encryption', 'both' => 'signing and encryption'), $fusage) . '</span></li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'f[crt][spsso][' . $crtid . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][spsso][' . $crtid . '][keyname]',
                                'id' => 'f[crt][spsso][' . $crtid . '][keyname]',
                                'class' => $keynamenotice,
                                'value' => $fkeyname)) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'f[crt][spsso][' . $crtid . '][certdata]');
                    $row .= form_textarea(array(
                                'name' => 'f[crt][spsso][' . $crtid . '][certdata]',
                                'id' => 'f[crt][spsso][' . $crtid . '][certdata]',
                                'cols' => 65,
                                'rows' => 40,
                                'class' => 'certdata ' . $certdatanotice,
                                'value' => $fcertdata,
                            )) . '</li>';
                    $spssocerts[] = $row;

                    if ($sessform && isset($ses['crt']['spsso']['' . $crtid . '']))
                    {
                        log_message('debug', 'GGG5: SESSFORM: removeing' . $k);
                        unset($ses['crt']['spsso']['' . $crtid . '']);
                    }
                }
            }
            if ($sessform && isset($ses['crt']['spsso']) && is_array($ses['crt']['spsso']))
            {
                foreach ($ses['crt']['spsso'] as $k4 => $v4)
                {
                    $row = '<li>';
                    $row .= form_label(lang('rr_pleaseremove'), 'f[crt][spsso][' . $k4 . '][remove]');
                    $row .= form_dropdown('f[crt][spsso][' . $k4 . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit'))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][spsso][' . $k4 . '][type]');
                    $row .= form_dropdown('f[crt][spsso][' . $k4 . '][type]', array('x509' => 'x509')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][spsso][' . $k4 . '][usage]');
                    $row .= form_dropdown('f[crt][spsso][' . $k4 . '][usage]', array('signing' => 'signing', 'encryption' => 'encryption', 'both' => 'signing and encryption'), set_value('f[crt][spsso][' . $k4 . '][usage]', $v4['usage'])) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'f[crt][spsso][' . $k4 . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][spsso][' . $k4 . '][keyname]',
                                'id' => 'f[crt][spsso][' . $k4 . '][keyname]',
                                'class' => 'notice',
                                'value' => set_value('f[crt][spsso][' . $k4 . '][keyname]', $v4['keyname']))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'f[crt][spsso][' . $k4 . '][certdata]');
                    $row .= form_textarea(array(
                                'name' => 'f[crt][spsso][' . $k4 . '][certdata]',
                                'id' => 'f[crt][spsso][' . $k4 . '][certdata]',
                                'cols' => 65,
                                'rows' => 40,
                                'class' => 'certdata ' . $certdatanotice,
                                'value' => set_value('f[crt][spsso][' . $k4 . '][certdata]', $v4['certdata']),
                            )) . '</li>';
                    $spssocerts[] = $row;
                }
            }
            $Part .= implode('', $spssocerts);
            $newelement = '<li><button class="btn" type="button" id="nspssocert">Add new Certificate</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
        }
        return $result;
    }

    public function NgenerateProtocols($ent, $entsession)
    {
        $result = array();
        $sessform = FALSE;
        if (!empty($entsession) && is_array($entsession))
        {
            $sessform = TRUE;
        }
        $enttype = $ent->getType();
        $allowedproto = getAllowedProtocolEnum();
        $allowednameids = getAllowedNameId();
        $scopes = array();
        if ($enttype != 'SP')
        {
            $scopes = array('idpsso' => $ent->getScope('idpsso'), 'aa' => $ent->getScope('aa'));
        }

        $allowedoptions = array();
        foreach ($allowedproto as $v)
        {
            $allowedoptions['' . $v . ''] = $v;
        }

        if ($enttype != 'SP')
        {


            /**
             * Scopes
             */
            if ($sessform && isset($entsession['scopes']['idpsso']))
            {
                $sesscope['idpsso'] = $entsession['scopes']['idpsso'];
            }
            else
            {
                $sesscope['idpsso'] = implode(',', $scopes['idpsso']);
            }
            if ($sessform && isset($entsession['scopes']['aa']))
            {
                $sesscope['aa'] = $entsession['scopes']['aa'];
            }
            else
            {
                $sesscope['aa'] = implode(',', $scopes['aa']);
            }
            $scopeidpssonotice = '';
            $scopeaanotice = '';
            $scopessovalue = set_value('f[scopes][idpsso]', $sesscope['idpsso']);
            $scopeaavalue = set_value('f[scopes][aa]', $sesscope['aa']);
            if ($scopessovalue !== implode(',', $scopes['idpsso']))
            {
                $scopeidpssonotice = 'notice';
            }
            if ($scopeaavalue !== implode(',', $scopes['aa']))
            {
                $scopeaanotice = 'notice';
            }

            $r = '<fieldset><legend>Scopes</legend><ol>';
            $r .= '<li>' . form_label('Scopes IDPSSO', 'f[scopes][idpsso]') . form_input(array(
                        'name' => 'f[scopes][idpsso]',
                        'id' => 'f[scopes][idpsso]',
                        'value' => $scopessovalue,
                        'class' => $scopeidpssonotice,
                    )) . '</li>';
            $r .= '<li>' . form_label('Scopes AttributeAuthority', 'f[scopes][aa]') . form_input(array(
                        'name' => 'f[scopes][aa]',
                        'id' => 'f[scopes][aa]',
                        'value' => $scopeaavalue,
                        'class' => $scopeaanotice,
                    )) . '</li>';
            $r .= '</ol></fieldset>';
            $result[] = $r;


            /**
             * IDP protocols 
             */
            $r = '<fieldset><legend>Supported protocols <i>IDPSSODescriptor</i></legend><ol>';
            $idpssoprotocols = $ent->getProtocolSupport('idpsso');
            $selected_options = array();
            $idpssonotice = '';
            if ($sessform && isset($entsession['prot']['idpsso']) && is_array($entsession['prot']['idpsso']))
            {
                #$selected_options = $entsession['prot']['idpsso'];
                if (count(array_diff($entsession['prot']['idpsso'], $idpssoprotocols)) > 0 || count(array_diff($idpssoprotocols, $entsession['prot']['idpsso'])) > 0)
                {
                    $idpssonotice = 'notice';
                }
                foreach ($entsession['prot']['idpsso'] as $v)
                {
                    $selected_options[$v] = $v;
                }
            }
            else
            {
                foreach ($idpssoprotocols as $p)
                {
                    $selected_options[$p] = $p;
                }
            }
            $r .= '<li class="' . $idpssonotice . '">' . form_multiselect('f[prot][idpsso][]', $allowedoptions, $selected_options) . '</li>';
            $r .= '</ol></fieldset>';
            $result[] = $r;

            $r = '<fieldset><legend>Supported protocols <i>AttributeAuthorityDescriptor</i></legend><ol>';
            $aaprotocols = $ent->getProtocolSupport('aa');
            $selected_options = array();
            $aanotice = '';
            if ($sessform && isset($entsession['prot']['aa']) && is_array($entsession['prot']['aa']))
            {
                #$selected_options = $entsession['prot']['idpsso'];
                if (count(array_diff($entsession['prot']['aa'], $aaprotocols)) > 0 || count(array_diff($aaprotocols, $entsession['prot']['aa'])) > 0)
                {
                    $aanotice = 'notice';
                }
                foreach ($entsession['prot']['aa'] as $v)
                {
                    $selected_options[$v] = $v;
                }
            }
            else
            {
                foreach ($aaprotocols as $p)
                {
                    $selected_options[$p] = $p;
                }
            }
            $r .= '<li class="' . $aanotice . '">' . form_multiselect('f[prot][aa][]', $allowedoptions, $selected_options) . '</li>';
            $r .= '</ol></fieldset>';
            $result[] = $r;
        }
        if ($enttype != 'IDP')
        {
            $r = '<fieldset><legend>Supported protocols <i>SPSSODescriptor</i></legend><ol>';
            $spssoprotocols = $ent->getProtocolSupport('spsso');
            $selected_options = array();
            $spssonotice = '';
            if ($sessform && isset($entsession['prot']['spsso']) && is_array($entsession['prot']['spsso']))
            {
                #$selected_options = $entsession['prot']['idpsso'];
                if (count(array_diff($entsession['prot']['spsso'], $spssoprotocols)) > 0 || count(array_diff($spssoprotocols, $entsession['prot']['spsso'])) > 0)
                {
                    $spssonotice = 'notice';
                }
                foreach ($entsession['prot']['spsso'] as $v)
                {
                    $selected_options[$v] = $v;
                }
            }
            else
            {
                foreach ($spssoprotocols as $p)
                {
                    $selected_options[$p] = $p;
                }
            }
            $r .= '<li class="' . $spssonotice . '">' . form_multiselect('f[prot][spsso][]', $allowedoptions, $selected_options) . '</li>';
            $r .= '</ol></fieldset>';
            $result[] = $r;
        }

        /**
         * nameids
         */
        if ($enttype != 'SP')
        {
            /**
             * start nameids for IDPSSODescriptor
             */
            $r = '<fieldset><legend>Supported nameIDs <i>IDPSSODescriptor</i></legend><ol>';
            $idpssonameids = $ent->getNameIds('idpsso');
            $idpssonameidnotice = '';
            $supportednameids = array();
            $chp = array();
            if ($sessform && is_array($entsession))
            {
                if (isset($entsession['nameids']['idpsso']) && is_array($entsession['nameids']['idpsso']))
                {
                    foreach ($entsession['nameids']['idpsso'] as $pv)
                    {
                        $supportednameids[] = $pv;
                        $chp[] = array('name' => 'f[nameids][idpsso][]', 'id' => 'f[nameids][idpsso][]', 'value' => $pv, 'checked' => TRUE);
                    }
                }
            }
            else
            {
                foreach ($idpssonameids as $v)
                {
                    $supportednameids[] = $v;
                    $chp[] = array(
                        'name' => 'f[nameids][idpsso][]', 'id' => 'f[nameids][idpsso][]', 'value' => $v, 'checked' => TRUE);
                }
            }
            foreach ($allowednameids as $v)
            {
                if (!in_array($v, $supportednameids))
                {
                    $chp[] = array('name' => 'f[nameids][idpsso][]', 'id' => 'f[nameids][idpsso][]', 'value' => $v, 'checked' => FALSE);
                }
            }
            if (count(array_diff($supportednameids, $idpssonameids)) > 0 or count(array_diff($idpssonameids, $supportednameids)) > 0)
            {
                $idpssonameidnotice = 'notice';
            }
            $r .= '<li>' . form_label(lang('rr_supportednameids'), 'f[nameids][idpsso][]') . '<div class="nsortable ' . $idpssonameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
            }
            $r .= '</div></li></ol></fieldset>';
            $result[] = $r;
            /**
             * end nameids for IDPSSODescriptor
             */
            /**
             * start nameids for AttributeAuthorityDescriptor 
             */
            $r = '<fieldset><legend>Supported nameIDs <i>AttributeAuthorityDescriptor</i></legend><ol>';
            $idpaanameids = $ent->getNameIds('aa');
            $idpaanameidnotice = '';
            $supportednameids = array();
            $chp = array();
            if ($sessform && is_array($entsession))
            {
                if (isset($entsession['nameids']['idpaa']) && is_array($entsession['nameids']['idpaa']))
                {
                    foreach ($entsession['nameids']['idpaa'] as $pv)
                    {
                        $supportednameids[] = $pv;
                        $chp[] = array('name' => 'f[nameids][idpaa][]', 'id' => 'f[nameids][idpaa][]', 'value' => $pv, 'checked' => TRUE);
                    }
                }
            }
            else
            {
                foreach ($idpaanameids as $v)
                {
                    $supportednameids[] = $v;
                    $chp[] = array(
                        'name' => 'f[nameids][idpaa][]', 'id' => 'f[nameids][idpaa][]', 'value' => $v, 'checked' => TRUE);
                }
            }
            foreach ($allowednameids as $v)
            {
                if (!in_array($v, $supportednameids))
                {
                    $chp[] = array('name' => 'f[nameids][idpaa][]', 'id' => 'f[nameids][idpaa][]', 'value' => $v, 'checked' => FALSE);
                }
            }
            if (count(array_diff($supportednameids, $idpaanameids)) > 0 or count(array_diff($idpaanameids, $supportednameids)) > 0)
            {
                $idpaanameidnotice = 'notice';
            }
            $r .= '<li>' . form_label(lang('rr_supportednameids'), 'f[nameids][idpaa][]') . '<div class="nsortable ' . $idpaanameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
            }
            $r .= '</div></li></ol></fieldset>';
            $result[] = $r;
            /**
             * end nameids for IDPSSODescriptor
             */
        }
        if ($enttype != 'IDP')
        {
            $r = '<fieldset><legend>Supported nameIDs <i>SPSSODescriptor</i></legend><ol>';
            $spssonameids = $ent->getNameIds('spsso');
            $spssonameidnotice = '';
            $supportednameids = array();
            $chp = array();
            if ($sessform && is_array($entsession))
            {
                if (isset($entsession['nameids']['spsso']) && is_array($entsession['nameids']['spsso']))
                {
                    foreach ($entsession['nameids']['spsso'] as $pv)
                    {
                        $supportednameids[] = $pv;
                        $chp[] = array('name' => 'f[nameids][spsso][]', 'id' => 'f[nameids][spsso][]', 'value' => $pv, 'checked' => TRUE);
                    }
                }
            }
            else
            {
                foreach ($spssonameids as $v)
                {
                    $supportednameids[] = $v;
                    $chp[] = array(
                        'name' => 'f[nameids][spsso][]', 'id' => 'f[nameids][spsso][]', 'value' => $v, 'checked' => TRUE);
                }
            }
            foreach ($allowednameids as $v)
            {
                if (!in_array($v, $supportednameids))
                {
                    $chp[] = array('name' => 'f[nameids][spsso][]', 'id' => 'f[nameids][spsso][]', 'value' => $v, 'checked' => FALSE);
                }
            }
            if (count(array_diff($supportednameids, $spssonameids)) > 0 or count(array_diff($spssonameids, $supportednameids)) > 0)
            {
                $spssonameidnotice = 'notice';
            }
            $r .= '<li>' . form_label(lang('rr_supportednameids'), 'f[nameids][spsso][]') . '<div class="nsortable ' . $spssonameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
            }
            $r .= '</div></li></ol></fieldset>';
            $result[] = $r;
        }
        return $result;
    }

    public function NgenerateStaticMetadataForm($ent, $entsession = null)
    {
        $sessform = FALSE;
        if (!empty($entsession) && is_array($entsession))
        {
            $sessform = TRUE;
        }
        $is_static = $ent->getStatic();
        $static_mid = $ent->getStaticMetadata();
        $static_metadata = '';
        if ($static_mid)
        {
            $static_metadata = $static_mid->getMetadataToDecoded();
        }
        if ($sessform)
        {
            if (array_key_exists('static', $entsession))
            {
                $svalue = $entsession['static'];
            }
            else
            {
                $svalue = $static_metadata;
            }

            if (array_key_exists('usestatic', $entsession) && $entsession['usestatic'] === 'accept')
            {
                $susestatic = TRUE;
            }
            else
            {
                $susestatic = $is_static;
            }
        }
        else
        {
            $susestatic = $is_static;

            $svalue = $static_metadata;
        }
        $value = set_value('f[static]', $svalue);
        $result = array();

        $result[] = form_label(lang('rr_usestaticmetadata'), 'f[usestatic]') . form_checkbox(array(
                    'name' => 'f[usestatic]',
                    'id' => 'f[usestatic]',
                    'value' => 'accept',
                    'checked' => set_value('f[usestatic]', $susestatic)
        ));

        $result[] = form_label(lang('rr_staticmetadataxml'), 'f[static]') . form_textarea(array(
                    'name' => 'f[static]',
                    'id' => 'f[static]',
                    'cols' => 65,
                    'rows' => 40,
                    'class' => 'metadata',
                    'value' => $value
        ));


        return $result;
    }

    public function NgenerateUiiForm(models\Provider $ent, $ses = null)
    {
        $langs = languagesCodes();
        $type = $ent->getType();
        $e = $ent->getExtendMetadata();
        $ext = array();
        if (!empty($e))
        {
            foreach ($e as $value)
            {

                $ext['' . $value->getType() . '']['' . $value->getNamespace() . '']['' . $value->getElement() . ''][] = $value;
            }
        }
        $sessform = FALSE;
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $result = array();
        if ($type != 'SP')
        {
            $result[] = '<div class="section">Identity Provider</div>';

            /**
             * start display
             */
            $r = form_fieldset('DisplayName');
            $langsdisplaynames = $langs;
            if (isset($ext['idp']['mdui']['DisplayName']))
            {
                foreach ($ext['idp']['mdui']['DisplayName'] as $v1)
                {
                    $l = $v1->getAttributes();
                    if (isset($l['xml:lang']))
                    {
                        $lang = $l['xml:lang'];
                        if (!array_key_exists($lang, $langs))
                        {
                            log_message('error', 'Language code ' . $lang . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                            $langtxt = $lang;
                        }
                        else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    }
                    else
                    {
                        log_message('error', 'Language not set for extendmetada row with id:' . $v1->getId());
                        continue;
                    }
                    $origval = $v1->getEvalue();
                    $nval = $origval;
                    if ($sessform && isset($ses['uii']['idpsso']['displayname']['' . $lang . '']))
                    {
                        $nval = $ses['uii']['idpsso']['displayname']['' . $lang . ''];
                        unset($ses['uii']['idpsso']['displayname']['' . $lang . '']);
                    }
                    $currval = set_value('f[uii][idpsso][displayname][' . $lang . ']', $nval);
                    $displaynotice = '';
                    if ($currval != $origval)
                    {
                        $displaynotice = 'notice';
                    }
                    $r .= '<li>';
                    $r .= form_label(lang('rr_displayname') . ' <small>' . $langtxt . '</small>', 'f[uii][idpsso][displayname][' . $lang . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][displayname][' . $lang . ']',
                                        'id' => 'f[uii][idpsso][displayname][' . $lang . ']',
                                        'value' => $currval,
                                        'class' => $displaynotice,
                                    )
                    );

                    $r .= '</li>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['displayname']) && is_array($ses['uii']['idpsso']['displayname']))
            {
                foreach ($ses['uii']['idpsso']['displayname'] as $key => $value)
                {
                    $r .= '<li>';
                    $r .= form_label(lang('rr_displayname') . ' <small>' . $key . '</small>', 'f[uii][idpsso][displayname][' . $key . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][displayname][' . $key . ']',
                                        'id' => 'f[uii][idpsso][displayname][' . $key . ']',
                                        'value' => set_value('f[uii][idpsso][displayname][' . $key . ']', $value),
                                        'class' => 'notice',
                                    )
                    );

                    $r .= '</li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiidisplayadd">' . form_dropdown('idpuiidisplaylangcode', $langsdisplaynames, 'en') . '<button type="button" id="idpadduiidisplay" name="idpadduiidisplay" value="idpadduiidisplay" class="btn">Add localized UII DisplayName</button></span></li>';
            $r .= form_fieldset_close();
            $result[] = $r;

            /**
             * end display
             */
            /**
             * start helpdesk 
             */
            $r = form_fieldset('HelpdeskURL/InformationURL');
            $langsdisplaynames = $langs;
            if (isset($ext['idp']['mdui']['InformationURL']))
            {
                foreach ($ext['idp']['mdui']['InformationURL'] as $v1)
                {
                    $l = $v1->getAttributes();
                    if (isset($l['xml:lang']))
                    {
                        $lang = $l['xml:lang'];
                        if (!array_key_exists($lang, $langs))
                        {
                            log_message('error', 'Language code ' . $lang . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                            $langtxt = $lang;
                        }
                        else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    }
                    else
                    {
                        log_message('error', 'Language not set for extendmetada row with id:' . $v1->getId());
                        continue;
                    }
                    $origval = $v1->getEvalue();
                    $nval = $origval;
                    if ($sessform && isset($ses['uii']['idpsso']['helpdesk']['' . $lang . '']))
                    {
                        $nval = $ses['uii']['idpsso']['helpdesk']['' . $lang . ''];
                        unset($ses['uii']['idpsso']['helpdesk']['' . $lang . '']);
                    }
                    $currval = set_value('f[uii][idpsso][helpdesk][' . $lang . ']', $nval);
                    $displaynotice = '';
                    if ($currval != $origval)
                    {
                        $displaynotice = 'notice';
                    }
                    $r .= '<li>';
                    $r .= form_label(lang('rr_helpdeskurl') . ' <small>' . $langtxt . '</small>', 'f[uii][idpsso][helpdesk][' . $lang . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][helpdesk][' . $lang . ']',
                                        'id' => 'f[uii][idpsso][helpdesk][' . $lang . ']',
                                        'value' => $currval,
                                        'class' => $displaynotice,
                                    )
                    );

                    $r .= '</li>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['helpdesk']) && is_array($ses['uii']['idpsso']['helpdesk']))
            {
                foreach ($ses['uii']['idpsso']['helpdesk'] as $key => $value)
                {
                    $r .= '<li>';
                    $r .= form_label(lang('rr_helpdeskurl') . ' <small>' . $key . '</small>', 'f[uii][idpsso][helpdesk][' . $key . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][helpdesk][' . $key . ']',
                                        'id' => 'f[uii][idpsso][helpdesk][' . $key . ']',
                                        'value' => set_value('f[uii][idpsso][helpdesk][' . $key . ']', $value),
                                        'class' => 'notice',
                                    )
                    );

                    $r .= '</li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiihelpdeskadd">' . form_dropdown('idpuiihelpdesklangcode', $langsdisplaynames, 'en') . '<button type="button" id="idpadduiihelpdesk" name="idpadduiihelpdesk" value="idpadduiihelpdesk" class="btn">Add localized ImformationURL</button></span></li>';
            $r .= form_fieldset_close();
            $result[] = $r;

            /**
             * end helpdesk
             */
            /**
             * start description
             */
            $r = form_fieldset('Provider Description');
            $langsdisplaynames = $langs;
            if (isset($ext['idp']['mdui']['Description']))
            {
                foreach ($ext['idp']['mdui']['Description'] as $v1)
                {
                    $l = $v1->getAttributes();
                    if (isset($l['xml:lang']))
                    {
                        $lang = $l['xml:lang'];
                        if (!array_key_exists($lang, $langs))
                        {
                            log_message('error', 'Language code ' . $lang . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                            $langtxt = $lang;
                        }
                        else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    }
                    else
                    {
                        log_message('error', 'Language not set for extendmetada row with id:' . $v1->getId());
                        continue;
                    }
                    $origval = $v1->getEvalue();
                    $nval = $origval;
                    if ($sessform && isset($ses['uii']['idpsso']['desc']['' . $lang . '']))
                    {
                        $nval = $ses['uii']['idpsso']['desc']['' . $lang . ''];
                        unset($ses['uii']['idpsso']['desc']['' . $lang . '']);
                    }
                    $currval = set_value('f[uii][idpsso][desc][' . $lang . ']', $nval);
                    $displaynotice = '';
                    if ($currval != $origval)
                    {
                        $displaynotice = 'notice';
                    }
                    $r .= '<li>';
                    $r .= form_label(lang('rr_description') . ' <small>' . $langtxt . '</small>', 'f[uii][idpsso][desc][' . $lang . ']') . form_textarea(
                                    array(
                                        'name' => 'f[uii][idpsso][desc][' . $lang . ']',
                                        'id' => 'f[uii][idpsso][desc][' . $lang . ']',
                                        'value' => $currval,
                                        'class' => $displaynotice,
                                    )
                    );

                    $r .= '</li>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['desc']) && is_array($ses['uii']['idpsso']['desc']))
            {
                foreach ($ses['uii']['idpsso']['desc'] as $key => $value)
                {
                    $r .= '<li>';
                    $r .= form_label(lang('rr_description') . ' <small>' . $key . '</small>', 'f[uii][idpsso][desc][' . $key . ']') . form_textarea(
                                    array(
                                        'name' => 'f[uii][idpsso][desc][' . $key . ']',
                                        'id' => 'f[uii][idpsso][desc][' . $key . ']',
                                        'value' => set_value('f[uii][idpsso][desc][' . $key . ']', $value),
                                        'class' => 'notice',
                                    )
                    );

                    $r .= '</li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiidescadd">' . form_dropdown('idpuiidesclangcode', $langsdisplaynames, 'en') . '<button type="button" id="idpadduiidesc" name="idpadduiidesc" value="idpadduiidesc" class="btn">Add localized Description</button></span></li>';
            $r .= form_fieldset_close();
            $result[] = $r;

            /**
             * end description 
             */
        }
        if ($type != 'IDP')
        {
            $result[] = '<div class="section">Service Provider</div>'; {


                /**
                 * start display
                 */
                $r = form_fieldset('DisplayName');
                $langsdisplaynames = $langs;
                if (isset($ext['sp']['mdui']['DisplayName']))
                {
                    foreach ($ext['sp']['mdui']['DisplayName'] as $v1)
                    {
                        $l = $v1->getAttributes();
                        if (isset($l['xml:lang']))
                        {
                            $lang = $l['xml:lang'];
                            if (!array_key_exists($lang, $langs))
                            {
                                log_message('error', 'Language code ' . $lang . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                                $langtxt = $lang;
                            }
                            else
                            {
                                $langtxt = $langs['' . $lang . ''];
                                unset($langsdisplaynames['' . $lang . '']);
                            }
                        }
                        else
                        {
                            log_message('error', 'Language not set for extendmetada row with id:' . $v1->getId());
                            continue;
                        }
                        $origval = $v1->getEvalue();
                        $nval = $origval;
                        if ($sessform && isset($ses['uii']['spsso']['displayname']['' . $lang . '']))
                        {
                            $nval = $ses['uii']['spsso']['displayname']['' . $lang . ''];
                            unset($ses['uii']['spsso']['displayname']['' . $lang . '']);
                        }
                        $currval = set_value('f[uii][spsso][displayname][' . $lang . ']', $nval);
                        $displaynotice = '';
                        if ($currval != $origval)
                        {
                            $displaynotice = 'notice';
                        }
                        $r .= '<li>';
                        $r .= form_label(lang('rr_displayname') . ' <small>' . $langtxt . '</small>', 'f[uii][spsso][displayname][' . $lang . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][displayname][' . $lang . ']',
                                            'id' => 'f[uii][spsso][displayname][' . $lang . ']',
                                            'value' => $currval,
                                            'class' => $displaynotice,
                                        )
                        );

                        $r .= '</li>';
                    }
                }
                if ($sessform && isset($ses['uii']['spsso']['displayname']) && is_array($ses['uii']['spsso']['displayname']))
                {
                    foreach ($ses['uii']['spsso']['displayname'] as $key => $value)
                    {
                        $r .= '<li>';
                        $r .= form_label(lang('rr_displayname') . ' <small>' . $key . '</small>', 'f[uii][spsso][displayname][' . $key . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][displayname][' . $key . ']',
                                            'id' => 'f[uii][spsso][displayname][' . $key . ']',
                                            'value' => set_value('f[uii][spsso][displayname][' . $key . ']', $value),
                                            'class' => 'notice',
                                        )
                        );

                        $r .= '</li>';
                        unset($langsdisplaynames['' . $key . '']);
                    }
                }
                $r .= '<li><span class="spuiidisplayadd">' . form_dropdown('spuiidisplaylangcode', $langsdisplaynames, 'en') . '<button type="button" id="spadduiidisplay" name="spadduiidisplay" value="spadduiidisplay" class="btn">Add localized UII DisplayName</button></span></li>';
                $r .= form_fieldset_close();
                $result[] = $r;

                /**
                 * end display
                 */
                /**
                 * start helpdesk 
                 */
                $r = form_fieldset('HelpdeskURL/InformationURL');
                $langsdisplaynames = $langs;
                if (isset($ext['sp']['mdui']['InformationURL']))
                {
                    foreach ($ext['sp']['mdui']['InformationURL'] as $v1)
                    {
                        $l = $v1->getAttributes();
                        if (isset($l['xml:lang']))
                        {
                            $lang = $l['xml:lang'];
                            if (!array_key_exists($lang, $langs))
                            {
                                log_message('error', 'Language code ' . $lang . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                                $langtxt = $lang;
                            }
                            else
                            {
                                $langtxt = $langs['' . $lang . ''];
                                unset($langsdisplaynames['' . $lang . '']);
                            }
                        }
                        else
                        {
                            log_message('error', 'Language not set for extendmetada row with id:' . $v1->getId());
                            continue;
                        }
                        $origval = $v1->getEvalue();
                        $nval = $origval;
                        if ($sessform && isset($ses['uii']['spsso']['helpdesk']['' . $lang . '']))
                        {
                            $nval = $ses['uii']['spsso']['helpdesk']['' . $lang . ''];
                            unset($ses['uii']['spsso']['helpdesk']['' . $lang . '']);
                        }
                        $currval = set_value('f[uii][spsso][helpdesk][' . $lang . ']', $nval);
                        $displaynotice = '';
                        if ($currval != $origval)
                        {
                            $displaynotice = 'notice';
                        }
                        $r .= '<li>';
                        $r .= form_label(lang('rr_helpdeskurl') . ' <small>' . $langtxt . '</small>', 'f[uii][spsso][helpdesk][' . $lang . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][helpdesk][' . $lang . ']',
                                            'id' => 'f[uii][spsso][helpdesk][' . $lang . ']',
                                            'value' => $currval,
                                            'class' => $displaynotice,
                                        )
                        );

                        $r .= '</li>';
                    }
                }
                if ($sessform && isset($ses['uii']['spsso']['helpdesk']) && is_array($ses['uii']['spsso']['helpdesk']))
                {
                    foreach ($ses['uii']['spsso']['helpdesk'] as $key => $value)
                    {
                        $r .= '<li>';
                        $r .= form_label(lang('rr_helpdeskurl') . ' <small>' . $key . '</small>', 'f[uii][spsso][helpdesk][' . $key . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][helpdesk][' . $key . ']',
                                            'id' => 'f[uii][spsso][helpdesk][' . $key . ']',
                                            'value' => set_value('f[uii][spsso][helpdesk][' . $key . ']', $value),
                                            'class' => 'notice',
                                        )
                        );

                        $r .= '</li>';
                        unset($langsdisplaynames['' . $key . '']);
                    }
                }
                $r .= '<li><span class="spuiihelpdeskadd">' . form_dropdown('spuiihelpdesklangcode', $langsdisplaynames, 'en') . '<button type="button" id="spadduiihelpdesk" name="spadduiihelpdesk" value="spadduiihelpdesk" class="btn">Add localized ImformationURL</button></span></li>';
                $r .= form_fieldset_close();
                $result[] = $r;

                /**
                 * end helpdesk
                 */
                /**
                 * start description
                 */
                $r = form_fieldset('Provider Description');
                $langsdisplaynames = $langs;
                if (isset($ext['sp']['mdui']['Description']))
                {
                    foreach ($ext['sp']['mdui']['Description'] as $v1)
                    {
                        $l = $v1->getAttributes();
                        if (isset($l['xml:lang']))
                        {
                            $lang = $l['xml:lang'];
                            if (!array_key_exists($lang, $langs))
                            {
                                log_message('error', 'Language code ' . $lang . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                                $langtxt = $lang;
                            }
                            else
                            {
                                $langtxt = $langs['' . $lang . ''];
                                unset($langsdisplaynames['' . $lang . '']);
                            }
                        }
                        else
                        {
                            log_message('error', 'Language not set for extendmetada row with id:' . $v1->getId());
                            continue;
                        }
                        $origval = $v1->getEvalue();
                        $nval = $origval;
                        if ($sessform && isset($ses['uii']['spsso']['desc']['' . $lang . '']))
                        {
                            $nval = $ses['uii']['spsso']['desc']['' . $lang . ''];
                            unset($ses['uii']['spsso']['desc']['' . $lang . '']);
                        }
                        $currval = set_value('f[uii][spsso][desc][' . $lang . ']', $nval);
                        $displaynotice = '';
                        if ($currval != $origval)
                        {
                            $displaynotice = 'notice';
                        }
                        $r .= '<li>';
                        $r .= form_label(lang('rr_description') . ' <small>' . $langtxt . '</small>', 'f[uii][spsso][desc][' . $lang . ']') . form_textarea(
                                        array(
                                            'name' => 'f[uii][spsso][desc][' . $lang . ']',
                                            'id' => 'f[uii][spsso][desc][' . $lang . ']',
                                            'value' => $currval,
                                            'class' => $displaynotice,
                                        )
                        );

                        $r .= '</li>';
                    }
                }
                if ($sessform && isset($ses['uii']['spsso']['desc']) && is_array($ses['uii']['spsso']['desc']))
                {
                    foreach ($ses['uii']['spsso']['desc'] as $key => $value)
                    {
                        $r .= '<li>';
                        $r .= form_label(lang('rr_description') . ' <small>' . $key . '</small>', 'f[uii][spsso][desc][' . $key . ']') . form_textarea(
                                        array(
                                            'name' => 'f[uii][spsso][desc][' . $key . ']',
                                            'id' => 'f[uii][spsso][desc][' . $key . ']',
                                            'value' => set_value('f[uii][spsso][desc][' . $key . ']', $value),
                                            'class' => 'notice',
                                        )
                        );

                        $r .= '</li>';
                        unset($langsdisplaynames['' . $key . '']);
                    }
                }
                $r .= '<li><span class="spuiidescadd">' . form_dropdown('spuiidesclangcode', $langsdisplaynames, 'en') . '<button type="button" id="spadduiidesc" name="spadduiidesc" value="spadduiidesc" class="btn">Add localized Description</button></span></li>';
                $r .= form_fieldset_close();
                $result[] = $r;

                /**
                 * end description 
                 */
            }
        }
        return $result;
    }

    /**
     * by default we just get all federations
     * @todo add more if conditions are set in array
     */
    public function getFederation($conditions = null)
    {
        $result = array();
        $feds = new models\Federations;
        $fedCollection = $feds->getFederations();
        if (!empty($fedCollection))
        {
            $result[''] = lang('rr_pleaseselect');
            foreach ($fedCollection as $key)
            {
                $value = "";
                $is_activ = $key->getActive();
                if (!($is_activ))
                {
                    $value .="inactive";
                }

                if (!empty($value))
                {
                    $value = "(" . $value . ")";
                }
                $result[$key->getName()] = $key->getName() . " " . $value;
            }
        }
        else
        {
            $result[''] = lang('rr_nofedfound');
            ;
        }
        return $result;
    }

    /**
     * make dropdown list of type of entities
     */
    public function buildTypeOfEntities()
    {
        $types = array('' => lang('rr_pleaseselect'), 'idp' => lang('identityproviders'), 'sp' => lang('serviceproviders'), 'all' => lang('allentities'));
        return $types;
    }

    public function generateFederationsElement($federations)
    {
        $result = "";
        $list = array();
        foreach ($federations as $f)
        {
            $list[$f->getId()] = $f->getName();
        }
        $result .= form_dropdown('fedid', $list);
        return $result;
    }

    private function generateServiceLocationsSpForm(models\Provider $provider, $action = null)
    {
        log_message('debug', $this->ci->mid . 'Form_element::generateServiceLocationsSpForm method started');
        $locations = array();
        foreach ($provider->getServiceLocations() as $srv)
        {
            if ($srv->getType() != 'SingleSignOnService')
            {
                $locations[$srv->getType()][] = array(
                    'id' => $srv->getId(),
                    'type' => $srv->getType(),
                    'binding' => $srv->getBindingName(),
                    'url' => $srv->getUrl(),
                    'index_number' => $srv->getOrder(),
                    'is_default' => $srv->getDefault()
                );
            }
        }
        /**
         * ad one field for new ACS service
         */
        $locations['AssertionConsumerService'][] = array(
            'id' => 'n',
            'type' => 'AssertionConsumerService',
            'binding' => 'none',
            'url' => '',
            'index_number' => '',
            'is_default' => null,);

        $locations['DiscoveryResponse'][] = array(
            'id' => 'n',
            'type' => 'DiscoveryResponse',
            'binding' => 'none',
            'url' => '',
            'index_number' => '',
            'is_default' => null,);






        $s_input = '';

        if (array_key_exists('AssertionConsumerService', $locations))
        {
            log_message('debug', $this->ci->mid . "found ACS for sp: " . $provider->getEntityId());
            $i = 0;
            $s_input .=form_fieldset(lang('rr_acs_fieldset'));


            foreach ($locations['AssertionConsumerService'] as $acs)
            {
                $name = 'srv_' . $acs['id'];
                $srvid = $acs['id'];

                $select_label = form_label(lang('rr_bindingname'), 'acs_bind[' . $srvid . ']');

                $select_binding = form_dropdown('acs_bind[' . $srvid . ']', $this->ci->config->item('acs_binding'), $acs['binding']);

                $s_row = "" . $select_label . $select_binding . "<br />";

                $url_data = array(
                    'name' => 'acs_url[' . $srvid . ']',
                    'id' => 'acs_url[' . $srvid . ']',
                    'value' => set_value('acs_url', $acs['url']),
                    'class' => 'acsurl',
                );
                $url_label = form_label(lang('rr_url'), 'acs_url[' . $srvid . ']');

                $url_input = form_input($url_data);

                $s_row .="" . $url_label . $url_input;

                $order_data = array(
                    'name' => 'acs_index[' . $srvid . ']',
                    'id' => 'acs_index[' . $srvid . ']',
                    'size' => 3,
                    'maxlength' => 3,
                    'class' => 'acsindex',
                    'value' => set_value('acs_index', $acs['index_number']),
                );

                $index_input = form_input($order_data);
                $indexrow = 'index ' . $index_input;

                $is_default_data = array(
                    'name' => 'acs_default',
                    'id' => 'acs_default',
                    'value' => $acs['id'],
                    'checked' => set_value('acs_default', $acs['is_default'])
                );

                $is_default_label = form_label(lang('rr_isdefault'), $name . '_default');
                $is_default_checkbox = form_radio($is_default_data);
                $isdefaulrow = ' ' . lang('rr_isdefault') . ' ' . $is_default_checkbox;
                $s_row .= '<span style="white-space: nowrap;">' . $indexrow . $isdefaulrow . '</span><br />';
                if ($srvid == 'n')
                {
                    $s_input .= '<li>' . form_fieldset(lang('rr_addnewacs')) . $s_row . form_fieldset_close() . '</li>';
                }
                else
                {
                    $s_input .= '<li>' . $s_row . '</li>';
                }
            }
            $s_input .= form_fieldset_close();
        }

        if (array_key_exists('DiscoveryResponse', $locations))
        {
            $s_input .=form_fieldset('Discovery Service Locations');
            foreach ($locations['DiscoveryResponse'] as $discins)
            {
                $discid = $discins['id'];
                $name = 'disc[' . $discid . ']';
                $s_row = '';
                $url_data = array(
                    'name' => 'disc[' . $discid . ']',
                    'id' => 'disc[' . $discid . ']',
                    'value' => set_value('disc', $discins['url']),
                );
                $order_data = array(
                    'name' => 'discindex[' . $discid . ']',
                    'id' => 'discindex[' . $discid . ']',
                    'size' => 3,
                    'maxlength' => 3,
                    'class' => 'acsindex',
                    'value' => set_value('discindex', $discins['index_number']),
                );
                $index_input = form_input($order_data);
                $indexrow = 'index ' . $index_input;
                $url_label = form_label(lang('rr_url'), 'disc[' . $discid . ']');
                $url_input = form_input($url_data);
                $s_row .= $url_label . $url_input . $indexrow;
                $s_input .= '<li>' . $s_row . '</li>';
            }
            $s_input .= form_fieldset_close();
        }
        if (!array_key_exists('RequestInitiator', $locations))
        {
            $locations['RequestInitiator'][] = array(
                'id' => 'n',
                'type' => 'RequestInitiator',
                'binding' => 'none',
                'url' => '',
                'index_number' => '',
                'is_default' => null,);
        }
        $s_input .=form_fieldset('RequestInitiator Location');
        foreach ($locations['RequestInitiator'] as $discins)
        {
            $discid = $discins['id'];
            $name = 'initdisc[' . $discid . ']';
            $s_row = '';
            $url_data = array(
                'name' => 'initdisc[' . $discid . ']',
                'id' => 'initdisc[' . $discid . ']',
                'value' => set_value('initdisc', $discins['url']),
            );
            $url_label = form_label(lang('rr_url'), 'initdisc[' . $discid . ']');
            $url_input = form_input($url_data);
            $s_row .= $url_label . $url_input;
            $s_input .= '<li>' . $s_row . '</li>';
        }
        $s_input .= form_fieldset_close();


        $srvform = form_fieldset(lang('rr_servicelocations'));
        $srvform = '<fieldset><legend class="accordionButton">' . lang('rr_servicelocations') . '</legend>';
        $srvform .='<ol class="accordionContent">';
        $srvform .= $s_input;

        $srvform .='</ol>';
        $srvform .=form_fieldset_close();
        return $srvform;
    }

    private function generateServiceLocationsIdpForm(models\Provider $provider, $action = null)
    {
        $ssotmpl = $this->ci->config->item('ssohandler_saml2');
        $ssotmpl = array_merge($ssotmpl, $this->ci->config->item('ssohandler_saml1'));
        $locations = array();
        $locations['SingleSignOnService'] = array();
        $slocations = $provider->getServiceLocations();
        $i = $provider->getServiceLocations()->getValues();
        if (!empty($slocations))
        {
            /**
             * mapping collection into array
             */
            foreach ($slocations->getValues() as $s)
            {
                $s_id = $s->getId();
                $s_type = $s->getType();
                $s_bindingname = $s->getBindingName();
                $s_url = $s->getUrl();
                $s_order = $s->getOrder();
                $s_default = $s->getDefault();


                $locations[$s_type][$s_bindingname] = array(
                    'url' => $s_url,
                    'default' => $s_default,
                    'order' => $s_order,
                    'id' => $s_id);
            }
        }
        /**
         * generate inputs and fill with values
         */
        $s_input = form_fieldset(lang('rr_singlesignon_fieldset'));

        $i = 0;
        foreach ($ssotmpl as $m)
        {

            /**
             * if locations is set
             */
            if (array_key_exists($m, $locations['SingleSignOnService']))
            {
                $name = 'srvsso_' . $locations['SingleSignOnService'][$m]['id'] . '_url';
                $url = $locations['SingleSignOnService'][$m]['url'];
                $labelname = $m;
                $s_input .="<li>";
                $s_input .= form_label($labelname, $name) . "\n";
                $s_input .= form_input(array(
                    'name' => $name,
                    'id' => $name,
                    'value' => set_value($name, $url)));
                $s_input .='</li>';
            }
            else
            {
                $i++;
                $name = 'srvsso_' . $i . 'n_url';
                $hiddenname = 'srvsso_' . $i . 'n_type';
                $labelname = $m;
                $s_input .='<li>';
                $s_input .= form_label($labelname, $name) . "\n";
                $s_input .= '<div style="display:none">';
                $s_input .= form_input(array(
                    'name' => $hiddenname,
                    'type' => 'hidden',
                    'value' => $m));
                $s_input .= '</div>';
                $s_input .= form_input(array(
                    'name' => $name,
                    'id' => $name,
                    'value' => set_value($name)));
                $s_input .='</li>';
            }
        }
        $s_input .= form_input(array(
            'name' => 'nosrvs',
            'type' => 'hidden',
            'value' => $i
        ));

        $s_input .= form_fieldset_close();

        $srvform = '<fieldset><legend class="accordionButton">' . lang('rr_servicelocations') . '</legend><ol class="accordionContent">';
        $srvform .= $s_input . '</ol>' . form_fieldset_close();
        return $srvform;
    }

    private function generateServiceLocationsForm(models\Provider $provider, $action = null)
    {
        $type = $provider->getType();
        $s = null;
        if ($type == 'IDP')
        {
            $s = $this->generateServiceLocationsIdpForm($provider);
        }
        elseif ($type == 'SP')
        {
            $s = $this->generateServiceLocationsSpForm($provider);
        }
        elseif (!empty($action))
        {
            if ($action == 'SP')
            {
                $s = $this->generateServiceLocationsSpForm($provider);
            }
            elseif ($action == 'IDP')
            {
                $s = $this->generateServiceLocationsIdpForm($provider);
            }
        }

        $t = $s;
        return $t;
    }

    private function generateContactsForm(models\Provider $provider, $action = null, $template = null)
    {
        $cntform = '<fieldset><legend class="accordionButton">' . lang('rr_contacts') . '</legend>';
        $cntform .='<ol class="accordionContent">';
        $formtypes = array(
            'administrative' => lang('rr_cnt_type_admin'),
            'technical' => lang('rr_cnt_type_tech'),
            'support' => lang('rr_cnt_type_support'),
            'billing' => lang('rr_cnt_type_bill'),
            'other' => lang('rr_cnt_type_other')
        );
        $no_contacts = 0;

        $cntcollection = $provider->getContacts();
        $no_contacts = $cntcollection->count();
        if (!empty($cntcollection))
        {
            foreach ($cntcollection->getValues() as $cnt)
            {

                $cntform .= form_fieldset(lang('rr_contacts')) . '<li>';
                $cntform .= form_label(lang('rr_contacttype'), 'contact_' . $cnt->getId() . '_type');
                $cntform .= form_dropdown('contact_' . $cnt->getId() . '_type', $formtypes, set_value('contact_' . $cnt->getId() . '_type', $cnt->getType()));
                $cntform .= '</li><li>' . form_label(lang('rr_contactfirstname'), 'contact_' . $cnt->getId() . '_fname');
                $cntform .= form_input(array('name' => 'contact_' . $cnt->getId() . '_fname', 'id' => 'contact_' . $cnt->getId() . '_fname',
                    'value' => set_value('contact_' . $cnt->getId() . '_fname', htmlentities($cnt->getGivenname()))));
                $cntform .= '</li><li>' . form_label(lang('rr_contactlastname'), 'contact_' . $cnt->getId() . '_sname');
                $sur = htmlspecialchars_decode($cnt->getSurname());
                $cntform .= form_input(array('name' => 'contact_' . $cnt->getId() . '_sname', 'id' => 'contact_' . $cnt->getId() . '_sname',
                    'value' => set_value('contact_' . $cnt->getId() . '_sname', $sur)));
                $cntform .= '</li><li>' . form_label(lang('rr_contactemail'), 'contact_' . $cnt->getId() . '_email');
                $cntform .= form_input(array('name' => 'contact_' . $cnt->getId() . '_email', 'id' => 'contact_' . $cnt->getId() . '_email',
                    'value' => set_value('contact_' . $cnt->getId() . '_email', $cnt->getEmail())));
                $cntform .= '</li>' . form_fieldset_close();
            }
            $no_contacts++;

            $cntform .= '<fieldset class="newcontact"><legend>' . lang('rr_newcontact') . '</legend>';
            $cntform .= '<li>';
            $cntform .= form_label(lang('rr_contacttype'), 'contact_0n_type');
            $cntform .= form_dropdown('contact_0n_type', $formtypes, set_value('contact_0n_type'));
            $cntform .= '<div style="display:none">';
            $cntform .= form_input(array('name' => 'no_contacts', 'type' => 'hidden', 'value' => $no_contacts));
            $cntform .= '</div>';
            $cntform .= '</li><li>';
            $cntform .= form_label(lang('rr_contactfirstname'), 'contact_0n_fname');
            $cntform .= form_input(array('name' => 'contact_0n_fname', 'id' => 'contact_0n_fname', 'value' => set_value('contact_0n_fname')));
            $cntform .= '</li><li>';
            $cntform .= form_label(lang('rr_contactlastname'), 'contact_0n_sname');
            $cntform .= form_input(array('name' => 'contact_0n_sname', 'id' => 'contact_0n_sname', 'value' => set_value('contact_0n_sname')));
            $cntform .= '</li><li>';
            $cntform .= form_label(lang('rr_contactemail'), 'contact_0n_email');
            $cntform .= form_input(array('name' => 'contact_0n_email', 'id' => 'contact_0n_email', 'value' => set_value('contact_0n_email')));
            $cntform .= '</li>' . form_fieldset_close();
        }
        $cntform .='</ol>' . form_fieldset_close();
        return $cntform;
    }

    private function generateCertificatesForm(models\Provider $provider, $options = null)
    {
        if (is_array($options) && array_key_exists('type', $options))
        {
            $type = $options['type'];
        }
        $crtform = form_fieldset('Certificates');
        $crtform = '<fieldset><legend class="accordionButton">' . lang('rr_certificates') . '</legend>';
        $crtform .='<ol class="accordionContent">';

        $crtcollection = $provider->getCertificates();
        $finalcertcollection = array();
        if (!empty($type))
        {
            foreach ($crtcollection as $c)
            {
                if ($c->getType() == $type)
                {
                    $finalcertcollection[] = $c;
                }
            }
        }
        else
        {
            $finalcertcollection = $crtcollection;
        }
        $no_certs = count($finalcertcollection);
        if ($no_certs > 0)
        {
            foreach ($finalcertcollection as $crt)
            {
                $i = $crt->getId();
                $crtform .='<li>';
                $crtform .=form_label(lang('rr_pleaseremove'), 'cert_' . $i . '_remove');
                $crtform .=form_dropdown('cert_' . $i . '_remove', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')));
                $crtform .='</li><li>';
                $crtform .=form_label(lang('rr_certificatetype'), 'cert_' . $i . '_type');
                $crtform .=form_dropdown('cert_' . $i . '_type', array('x509' => 'x509'), set_value('cert_' . $i . '_type', 'x509'));
                $crtform .='</li><li>';
                $crtform .=form_label(lang('rr_certificateuse'), 'cert_' . $i . '_use[]');
                $m = array('signing' => 'signing', 'encryption' => 'encryption');
                $mselected = $crt->getCertUse();
                if (empty($mselected))
                {
                    $n = $m;
                }
                else
                {
                    $n = array($mselected = $mselected);
                }
                $crtform .=form_multiselect('cert_' . $i . '_use[]', $m, $n);
                $crtform .='</li><li>';
                $crtform .=form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'cert_' . $i . '_keyname');
                $crtform .=form_input(array('name' => 'cert_' . $i . '_keyname', 'id' => 'cert_' . $i . '_keyname', 'value' => set_value('cert_' . $i . '_keyname', $crt->getKeyName())));

                $crtform .='</li><li>';
                $crtform .=form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'cert_' . $i . '_data');
                $crtform .=form_textarea(array(
                    'name' => 'cert_' . $i . '_data', 'id' => 'cert_' . $i . '_data',
                    'value' => set_value('cert_' . $i . '_data', $crt->getPEM($crt->getCertData())), 'cols' => 70, 'rows' => 40
                ));
                $crtform .='</li>';
            }
        }
        $crtform .= '<div class="ncert">';
        $crtform .='<li><b>' . lang('rr_newcertificate') . '</b><small>(' . lang('rr_optional') . ')</small></li>';
        $crtform .='<li>';
        $crtform .=form_label(lang('rr_certificatetype'), 'cert_0n_type');
        $crtform .=form_dropdown('cert_0n_type', array('x509' => 'x509'), set_value('cert_0n_type', 'x509'));
        $crtform .='</li><li>';
        $crtform .=form_label(lang('rr_certificateuse'), 'cert_0n_use[]');
        $m = array(
            'signing' => 'signing',
            'encryption' => 'encryption'
        );
        $crtform .=form_multiselect('cert_0n_use[]', $m, $m);
        $crtform .='</li><li>';
        $crtform .=form_label(lang('rr_keyname') . showHelp(lang('rhelp_multikeynames')), 'cert_0n_keyname');
        $crtform .=form_input(array('name' => 'cert_0n_keyname', 'id' => 'cert_0n_keyname', 'value' => set_value('cert_0n_keyname')));
        $crtform .='</li><li>';
        $crtform .=form_label(lang('rr_certificate') . showHelp(lang('rhelp_cert')), 'cert_0n_data');
        $crtform .=form_textarea(array(
            'name' => 'cert_0n_data',
            'id' => 'cert_0n_data',
            'value' => set_value('cert_0n_data'),
            'cols' => 65,
        ));
        $crtform .='</li></div></ol>' . form_fieldset_close();
        return $crtform;
    }

    /**
     * return form elements:
     * select box if you want to use static metadata
     * textarea for metadata
     */

    /**
     * @todo add to main function generating form
     */
    private function staticMetadata(models\Provider $provider)
    {
        $is_static = $provider->getStatic();
        $static_mid = $provider->getStaticMetadata();
        $static_metadata = '';
        if ($static_mid)
        {
            $static_metadata = $static_mid->getMetadataToDecoded();
        }

        $tform = '<fieldset><legend class="accordionButton">' . lang('rr_staticmetadata') . '</legend><ol class="accordionContent"><li>';
        $tform .= form_label(lang('rr_usestaticmetadata'), 'usestatic');
        $tform .= form_checkbox(array(
            'name' => 'usestatic',
            'id' => 'usestatic',
            'value' => 'accept',
            'checked' => set_value('usestatic', $is_static)
        ));
        $tform .='</li><li>';
        $tform .= form_label(lang('rr_staticmetadataxml'), 'staticmetadatabody');
        $tform .= form_textarea(array(
            'name' => 'staticmetadatabody',
            'id' => 'staticmetadatabody',
            'value' => set_value('staticmetadatabody', $static_metadata)
        ));
        $tform .='</li></ol>' . form_fieldset_close();
        return $tform;
    }

    private function supportedProtocols(models\Provider $provider, $action = null)
    {
        $tform = '';
        $t_protocols = $provider->getProtocol();
        $selected_options = array();
        foreach ($t_protocols as $p)
        {
            $selected_options[$p] = $p;
        }
        // $tform .= form_fieldset('Protocols');
        $tform .= '<fieldset><legend class="accordionButton">' . lang('rr_protocols') . '</legend><ol class="accordionContent"><li>';
        $tform .= form_label(lang('rr_supportedprotocols') . showHelp(lang('rhelp_supportedprotocols')), 'protocols[]');
        $options = $this->ci->config->item('supported_protocols');
        $tform .= form_multiselect('protocols[]', $options, $selected_options) . '</li>';
        $tform .= $this->supportedNameIds($provider) . '</ol>' . form_fieldset_close();
        return $tform;
    }

    /**
     * @todo add javascript ordering
     */
    private function supportedNameIds(models\Provider $provider)
    {
        $tform = '';
        $supported_nameids = array();
        $tmpl_nameids = $this->ci->config->item('nameids');


        $s_nameids = $provider->getNameId();
        foreach ($s_nameids->getValues() as $n)
        {
            $supported_nameids[$n] = $n;
            $chb[] = array(
                'name' => 'nameids[]',
                'id' => 'nameids[]',
                'value' => $n,
                'checked' => TRUE);
        }
        foreach ($tmpl_nameids as $t)
        {
            if (!array_key_exists($t, $supported_nameids))
            {
                $chb[] = array(
                    'name' => 'nameids[]',
                    'id' => 'nameids[]',
                    'value' => $t,
                    'checked' => FALSE);
            }
        }
        $tform .='<li>';
        $tform .= form_label(lang('rr_supportednameids'), 'nameids[]') . '<div class="nsortable">';
        foreach ($chb as $n)
        {
            $tform .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
        }
        $tform .= '</div></li>';
        return $tform;
    }

    private function generateSpForm(models\Provider $provider, $action = null, $template = null)
    {
        log_message('debug', $this->ci->mid . 'Form_element::generateSpForm method started');
        $langscodes = languagesCodes();
        $lnames = $provider->getLocalName();
        $tmp = '<div id="mojtest">';
        $tmp .='<div id="accordion">';
        $tmp .='<fieldset><legend class="accordionButton">' . lang('rr_generalinformation') . '</legend>';
        $tmp .= '<ol class="accordionContent"><li>';
        $tmp .= form_label(lang('rr_entityid') . showHelp(lang('rhelp_entityid')) . '<br /><small><span class="notice">' . lang('rr_noticechangearp') . '</span></small>', 'entityid');
        $f_en = array('id' => 'entityid', 'name' => 'entityid', 'required' => 'required', 'value' => $provider->getEntityid());
        $tmp .= form_input($f_en) . '</li><li>';
        $tmp .= form_label(lang('rr_resource') . showHelp(lang('rhelp_resourcename')), 'homeorgname');
        $tmp .= form_input('homeorgname', set_value('homeorgname', $provider->getName())) . '</li>';
        if (is_array($lnames))
        {
            foreach ($lnames as $k => $v)
            {
                $tmp .='<li class="localized">';
                $tmp .= form_label(lang('rr_homeorganisationname') . ' <small>' . $langscodes[$k] . '</small>', 'lname[' . $k . ']');
                $tmp .= form_input(array('id' => 'lname[' . $k . ']', 'name' => 'lname[' . $k . ']', 'value' => set_value('lname[' . $k . ']', $v)));
                $tmp .= '</li>';
            }
        }
        else
        {
            $lnames = array();
        }
        $tmp .= '<li class="addlname localized">';
        $langscodes2 = array_diff_key($langscodes, $lnames);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addlname" name="addlname" value="addlname" class="btn">Add localized name</button>';
        $tmp .= '</li>';

        $tmp .= '<li>' . form_label(lang('rr_displayname'), 'displayname');
        $tmp .= form_input('displayname', set_value('displayname', $provider->getDisplayName())) . '</li>';

        $ldisplaynames = $provider->getLocalDisplayName();
        if (is_array($ldisplaynames))
        {
            foreach ($ldisplaynames as $k => $v)
            {
                $tmp .= '<li class="localized">';
                $tmp .= form_label(lang('rr_displayname') . ' <small>' . $langscodes[$k] . '</small>', 'ldisplayname[' . $k . ']');
                $tmp .= form_input(array('id' => 'ldisplayname[' . $k . ']', 'name' => 'ldisplayname[' . $k . ']', 'value' => set_value('ldisplayname[' . $k . ']', $v)));
            }
        }
        else
        {
            $ldisplaynames = array();
        }
        $tmp .= '<li class="addldisplayname localized">';
        $langscodes2 = array_diff_key($langscodes, $ldisplaynames);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addldisplayname" name="addldisplayname" value="addldisplayname" class="btn">Add localized display name</button>';
        $tmp .= '</li>';


        $tmp .='<li>';
        $configRegAuth = $this->ci->config->item('registrationAutority');

        if (!empty($configRegAuth))
        {
            $tmp .= form_label(lang('rr_regauthority') . '<br /><small>' . lang('rr_default') . ': ' . $configRegAuth . '</small>', 'registrar');
        }
        else
        {
            $tmp .= form_label(lang('rr_regauthority'), 'registrar');
        }
        $in = array('id' => 'registrar', 'name' => 'registrar', 'value' => set_value('registrar', $provider->getRegistrationAuthority()));
        $tmp .= form_input($in) . '</li><li>';
        $tmp .= form_label(lang('rr_regdate'), 'registerdate');
        $ptm = $provider->getRegistrationDate();
        if (!empty($ptm))
        {
            $tmp .= form_input(array(
                'name' => 'registerdate',
                'id' => 'registerdate',
                'value' => set_value('registerdate', $provider->getRegistrationDate()->format('Y-m-d'))
            ));
        }
        else
        {
            $tmp .= form_input(array(
                'name' => 'registerdate',
                'id' => 'registerdate',
                'value' => set_value('registerdate')
            ));
        }
        $tmp .= '</li><li>' . form_label(lang('rr_resourceurl'), 'homeurl');
        $tmp .= form_input('homeurl', set_value('homeurl', $provider->getHomeUrl()));
        $tmp .= '</li><li>' . form_label(lang('rr_helpdeskurl') . showHelp(lang('rhelp_helpdeskurl')), 'helpdeskeurl');
        $tmp .= form_input('helpdeskurl', set_value('helpdeskurl', $provider->getHelpdeskUrl())) . '</li>';


        $lurls = $provider->getLocalHelpdeskURL();
        if (is_array($lurls))
        {
            foreach ($lurls as $k => $v)
            {
                $tmp .= '<li class="localized">';
                $tmp .= form_label(lang('rr_helpdeskurl') . ' <small>' . $langscodes[$k] . '</small>', 'lhelpdeskurl[' . $k . ']');
                $tmp .= form_input(array('id' => 'lhelpdeskurl[' . $k . ']', 'name' => 'lhelpdeskurl[' . $k . ']', 'value' => set_value('lhelpdeskurl[' . $k . ']', $v)));
            }
        }
        else
        {
            $lurls = array();
        }
        $tmp .= '<li class="addlhelpdeskurl localized">';
        $langscodes2 = array_diff_key($langscodes, $lurls);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addlhelpdeskurl" name="addlhelpdeskurl" value="addlhelpdeskurl" class="btn">Add localized URL</button>';
        $tmp .= '</li>';




        $tmp .= '<li>' . form_label(lang('rr_validfrom'), 'validfrom');
        $ptm = $provider->getValidFrom();
        if (!empty($ptm))
        {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom', $provider->getValidFrom()->format('Y-m-d'))
            ));
        }
        else
        {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom')
            ));
        }

        $tmp .= '</li><li>' . form_label(lang('rr_validto'), 'validto');
        $vtm = $provider->getValidTo();
        if (!empty($vtm))
        {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto', $provider->getValidTo()->format('Y-m-d'))
            ));
        }
        else
        {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto')
            ));
        }
        $tmp .= '</li><li>' . form_label(lang('rr_description'), 'description') . form_textarea('description', set_value('description', $provider->getDescription())) . '</li>';

        $ldescriptions = $provider->getLocalDescription();
        if (is_array($ldescriptions))
        {
            foreach ($ldescriptions as $k => $v)
            {
                $tmp .='<li class="localized">';
                $tmp .= form_label(lang('rr_description') . ' <small>' . $langscodes[$k] . '</small>', 'ldescription[' . $k . ']');
                $tmp .= form_textarea('ldescription[' . $k . ']', set_value('ldescription[' . $k . ']', $v));
                $tmp .= '</li>';
            }
        }
        else
        {
            $ldescriptions = array();
        }
        $tmp .= '<li class="addldescription localized">';
        $langscodes2 = array_diff_key($langscodes, $ldescriptions);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addldescription" name="addldescription" value="addlldescription" class="btn">Add localized Description</button>';
        $tmp .= '</li>';



        $tmp .= '</ol>' . form_fieldset_close() . '</div>';

        $tmp .='<fieldset><legend class="accordionButton">DataProtection/Privacy</legend>';
        $tmp .= '<ol class="accordionContent">';
        $current_coc = $provider->getCoc();
        if (!empty($current_coc))
        {
            $current_coc_id = $current_coc->getId();
        }
        else
        {
            $current_coc_id = 0;
        }
        $coc_dropdown['0'] = lang('rr_select');
        $coccols = $this->em->getRepository("models\Coc")->findAll();
        if (is_array($coccols) and count($coccols) > 0)
        {
            $tmp .= '<li>';
            $tmp .= form_label('Code of Conduct' . showHelp('Please contact to us if required COC url is not listed'), 'coc');
            foreach ($coccols as $c)
            {
                $coc_dropdown[$c->getId()] = $c->getName() . ' (' . $c->getUrl() . ')';
            }
            $tmp .= form_dropdown('coc', $coc_dropdown, $current_coc_id, array('id' => 'coc'));
            $tmp .= '</li>';
        }

        $tmp .= '<li>' . form_label(lang('rr_privacystatement'), 'privacyurl') . form_input('privacyurl', set_value('privacyurl', $provider->getPrivacyUrl())) . '</li>';

        $lprivacyurls = $provider->getLocalPrivacyUrl();
        if (is_array($lprivacyurls))
        {
            foreach ($lprivacyurls as $k => $v)
            {
                $tmp .= '<li class="localized">';
                $tmp .= form_label(lang('rr_privacystatement') . ' <small>' . $langscodes[$k] . '</small>', 'lprivacyurl[' . $k . ']');
                $tmp .= form_input(array('id' => 'lprivacyurl[' . $k . ']', 'name' => 'lprivacyurl[' . $k . ']', 'value' => set_value('lprivacyurl[' . $k . ']', $v)));
            }
        }
        else
        {
            $lprivacyurls = array();
        }
        $tmp .= '<li class="addlprivacyurl localized">';
        $langscodes2 = array_diff_key($langscodes, $lprivacyurls);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addlprivacyurl" name="addlprivacyurl" value="addlprivacyurl" class="btn">Add localized ' . lang('rr_privacystatement') . '</button>';
        $tmp .= '</li>';

        $tmp .= '</ol>';
        $tmp .= form_fieldset_close();

        $tmp .= $this->staticMetadata($provider) . $this->supportedProtocols($provider);
        $tmp .= $this->generateCertificatesForm($provider, array('type' => 'spsso'));
        /**
         * @todo add  service locations for sp
         */
        $tmp .= $this->generateServiceLocationsForm($provider, 'SP') . $this->generateContactsForm($provider);
        $tmp .= '<fieldset><legend class="accordionButton"><a href="' . base_url() . 'manage/attribute_requirement/sp/' . $provider->getId() . '">' . lang('rr_requiredattributes') . '</a></legend></fieldset>';
        $tmp .= '<fieldset><legend class="accordionButton"><a href="' . base_url() . 'manage/logos/provider/sp/' . $provider->getId() . '">' . lang('rr_logo') . '</a></legend></fieldset>';
        $tmp .= '<fieldset><legend class="accordionButton"><a href="' . base_url() . 'geolocation/show/' . $provider->getId() . '/sp">' . lang('rr_geolocation') . '</a></legend></fieldset></div>';
        return $tmp;
    }

    private function generateIdpForm(models\Provider $provider, $action = null, $template = null)
    {
        $langscodes = languagesCodes();
        $lnames = $provider->getLocalName();

        $tmp = '';
        $tmp .='<div id="accordion"><fieldset><legend class="accordionButton"  >' . lang('rr_generalinformation') . '</legend>';
        $tmp .= '<ol class="accordionContent"><li>';
        $tmp .= form_label(lang('rr_entityid') . showHelp(lang('rhelp_entityid')) . '<br /><small><span class="notice">' . lang('rr_noticechangearp') . '</span></small>', 'entityid');
        $f_en = array('id' => 'entityid', 'name' => 'entityid', 'required' => 'required', 'value' => $provider->getEntityid());
        $tmp .= form_input($f_en);
        $tmp .= '</li><li>';
        $tmp .= form_label(lang('rr_homeorganisationname') . ' <small>(default)</small>', 'homeorgname');
        $in = array('id' => 'homeorgname', 'name' => 'homeorgname', 'required' => 'required', 'value' => set_value('homeorgname', $provider->getName()));
        $tmp .= form_input($in) . '</li>';
        if (is_array($lnames))
        {
            foreach ($lnames as $k => $v)
            {
                $tmp .='<li class="localized">';
                $tmp .= form_label(lang('rr_homeorganisationname') . ' <small>' . $langscodes[$k] . '</small>', 'lname[' . $k . ']');
                $tmp .= form_input(array('id' => 'lname[' . $k . ']', 'name' => 'lname[' . $k . ']', 'value' => set_value('lname[' . $k . ']', $v)));
                $tmp .= '</li>';
            }
        }
        else
        {
            $lnames = array();
        }
        $tmp .= '<li class="addlname localized">';
        $langscodes2 = array_diff_key($langscodes, $lnames);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addlname" name="addlname" value="addlname" class="btn">Add localized name</button>';
        $tmp .= '</li>';

        $tmp .='<li>' . form_label(lang('rr_displayname') . ' <small>default</small>', 'displayname');
        $in = array('id' => 'displayname', 'name' => 'displayname', 'required' => 'required', 'value' => set_value('displayname', $provider->getDisplayName()));
        $tmp .= form_input($in);
        $tmp .= '</li>';

        $ldisplaynames = $provider->getLocalDisplayName();
        if (is_array($ldisplaynames))
        {
            foreach ($ldisplaynames as $k => $v)
            {
                $tmp .= '<li class="localized">';
                $tmp .= form_label(lang('rr_displayname') . ' <small>' . $langscodes[$k] . '</small>', 'ldisplayname[' . $k . ']');
                $tmp .= form_input(array('id' => 'ldisplayname[' . $k . ']', 'name' => 'ldisplayname[' . $k . ']', 'value' => set_value('ldisplayname[' . $k . ']', $v)));
            }
        }
        else
        {
            $ldisplaynames = array();
        }
        $tmp .= '<li class="addldisplayname localized">';
        $langscodes2 = array_diff_key($langscodes, $ldisplaynames);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addldisplayname" name="addldisplayname" value="addldisplayname" class="btn">Add localized display name</button>';
        $tmp .= '</li>';

        $tmp .='<li>';
        $configRegAuth = $this->ci->config->item('registrationAutority');

        if (!empty($configRegAuth))
        {
            $tmp .= form_label(lang('rr_regauthority') . '<br /><small>' . lang('rr_default') . ': ' . $configRegAuth . '</small>', 'registrar');
        }
        else
        {
            $tmp .= form_label(lang('rr_regauthority'), 'registrar');
        }
        $in = array('id' => 'registrar', 'name' => 'registrar', 'value' => set_value('registrar', $provider->getRegistrationAuthority()));
        $tmp .= form_input($in);
        $tmp .= '</li><li>' . form_label(lang('rr_regdate'), 'registerdate');
        $ptm = $provider->getRegistrationDate();
        if (!empty($ptm))
        {
            $tmp .= form_input(array(
                'name' => 'registerdate',
                'id' => 'registerdate',
                'value' => set_value('registerdate', $provider->getRegistrationDate()->format('Y-m-d'))
            ));
        }
        else
        {
            $tmp .= form_input(array(
                'name' => 'registerdate',
                'id' => 'registerdate',
                'value' => set_value('registerdate')
            ));
        }

        $tmp .= '</li><li>' . form_label(lang('rr_homeorganisationurl'), 'homeurl');
        $tmp .= form_input('homeurl', set_value('homeurl', $provider->getHomeUrl()));
        $tmp .= '</li><li>' . form_label(lang('rr_helpdeskurl') . showHelp(lang('rhelp_helpdeskurl')), 'helpdeskeurl');
        $in = array(
            'name' => 'helpdeskurl',
            'id' => 'helpdeskurl',
            'required' => 'required',
            'value' => set_value('helpdeskurl', $provider->getHelpdeskUrl()),
        );
        $tmp .= form_input($in);
        $tmp .= '</li>';

        $lurls = $provider->getLocalHelpdeskURL();
        if (is_array($lurls))
        {
            foreach ($lurls as $k => $v)
            {
                $tmp .= '<li class="localized">';
                $tmp .= form_label(lang('rr_helpdeskurl') . ' <small>' . $langscodes[$k] . '</small>', 'lhelpdeskurl[' . $k . ']');
                $tmp .= form_input(array('id' => 'lhelpdeskurl[' . $k . ']', 'name' => 'lhelpdeskurl[' . $k . ']', 'value' => set_value('lhelpdeskurl[' . $k . ']', $v)));
            }
        }
        else
        {
            $lurls = array();
        }
        $tmp .= '<li class="addlhelpdeskurl localized">';
        $langscodes2 = array_diff_key($langscodes, $lurls);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addlhelpdeskurl" name="addlhelpdeskurl" value="addlhelpdeskurl" class="btn">Add localized URL</button>';
        $tmp .= '</li>';


        $tmp .='<li>' . form_label(lang('rr_privacystatement') . ' <small>default</small>', 'privacyurl');
        $tmp .= form_input('privacyurl', set_value('privacyurl', $provider->getPrivacyUrl()));
        $tmp .= '</li>';

        $lprivacyurls = $provider->getLocalPrivacyUrl();
        if (is_array($lprivacyurls))
        {
            foreach ($lprivacyurls as $k => $v)
            {
                $tmp .= '<li class="localized">';
                $tmp .= form_label(lang('rr_privacystatement') . ' <small>' . $langscodes[$k] . '</small>', 'lprivacyurl[' . $k . ']');
                $tmp .= form_input(array('id' => 'lprivacyurl[' . $k . ']', 'name' => 'lprivacyurl[' . $k . ']', 'value' => set_value('lprivacyurl[' . $k . ']', $v)));
            }
        }
        else
        {
            $lprivacyurls = array();
        }
        $tmp .= '<li class="addlprivacyurl localized">';
        $langscodes2 = array_diff_key($langscodes, $lprivacyurls);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addlprivacyurl" name="addlprivacyurl" value="addlprivacyurl" class="btn">Add localized ' . lang('rr_privacystatement') . '</button>';
        $tmp .= '</li>';

        $tmp .= '<li>' . form_label(lang('rr_validfrom'), 'validfrom');
        $ptm = $provider->getValidFrom();
        if (!empty($ptm))
        {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom', $provider->getValidFrom()->format('Y-m-d'))
            ));
        }
        else
        {
            $tmp .= form_input(array(
                'name' => 'validfrom',
                'id' => 'validfrom',
                'value' => set_value('validfrom')
            ));
        }

        $tmp .= '</li><li>' . form_label(lang('rr_validto'), 'validto');
        $vtm = $provider->getValidTo();
        if (!empty($vtm))
        {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto', $provider->getValidTo()->format('Y-m-d'))
            ));
        }
        else
        {
            $tmp .= form_input(array(
                'name' => 'validto',
                'id' => 'validto',
                'value' => set_value('validto')
            ));
        }
        $tmp .= '</li><li>' . form_label(lang('rr_scope'), 'scope');
        $tmp .= form_input('scope', set_value('scope', implode(',', $provider->getScope('idpsso'))));
        $tmp .= '</li><li>' . form_label(lang('rr_description'), 'description');
        $tmp .= form_textarea('description', set_value('description', $provider->getDescription()));
        $tmp .= '</li>';
        $ldescriptions = $provider->getLocalDescription();
        if (is_array($ldescriptions))
        {
            foreach ($ldescriptions as $k => $v)
            {
                $tmp .='<li class="localized">';
                $tmp .= form_label(lang('rr_description') . ' <small>' . $langscodes[$k] . '</small>', 'ldescription[' . $k . ']');
                $tmp .= form_textarea('ldescription[' . $k . ']', set_value('ldescription[' . $k . ']', $v));
                $tmp .= '</li>';
            }
        }
        else
        {
            $ldescriptions = array();
        }
        $tmp .= '<li class="addldescription localized">';
        $langscodes2 = array_diff_key($langscodes, $ldescriptions);
        $tmp .= form_dropdown('langcode', $langscodes2, 'en', array('id' => 'langcode'));
        $tmp .= '<button type="button" id="addldescription" name="addldescription" value="addlldescription" class="btn">Add localized Description</button>';
        $tmp .= '</li>';

        $tmp .='</ol>' . form_fieldset_close() . '</div>';
        $tmp .= $this->staticMetadata($provider) . $this->supportedProtocols($provider);

        /**
         * certificates section
         */
        $tmp .= $this->generateCertificatesForm($provider, array('type' => 'idpsso'));
        /**
         * servicelocations section
         */
        $tmp .= $this->generateServiceLocationsForm($provider);
        /**
         * contacts section
         */
        $tmp .= $this->generateContactsForm($provider);
        $tmp .= '<fieldset><legend class="accordionButton"><a href="' . base_url() . 'manage/attribute_policy/globals/' . $provider->getId() . '">' . lang('rr_attributes') . '</a></legend></fieldset>';
        $tmp .= '<fieldset><legend class="accordionButton"><a href="' . base_url() . 'manage/logos/provider/idp/' . $provider->getId() . '">' . lang('rr_logo') . '</a></legend></fieldset>';
        $tmp .= '<fieldset><legend class="accordionButton"><a href="' . base_url() . 'geolocation/show/' . $provider->getId() . '/idp">' . lang('rr_geolocation') . '</a></legend></fieldset>';
        return $tmp;
    }

    public function generateEntityForm(models\Provider $provider, $action = null, $forcetype = null)
    {
        log_message('debug', $this->ci->mid . 'Form_element::generateEntityForm method started');
        $tform = null;
        $p_type = $provider->getType();
        if ($p_type == 'IDP')
        {
            $tform = $this->generateIdpForm($provider, $action);
        }
        elseif ($p_type == 'SP')
        {

            $tform = $this->generateSpForm($provider, $action);
        }
        else
        {
            if (!empty($forcetype) && $forcetype == 'idp')
            {
                $tform = $this->generateIdpForm($provider, $action);
            }
            elseif (!empty($forcetype) && $forcetype == 'sp')
            {
                $tform = $this->generateSpForm($provider, $action);
            }

            /**
             * @todo display form if type is BOTH
             */
            // $tform = $this->generateBothForm($provider, $action);
        }

        return $tform;
    }

    public function generateFederationEditForm(models\Federation $federation)
    {
        $f = null;
        $f .= form_fieldset(lang('rr_basicinformation'));
        $f .='<ol><li>' . form_label(lang('rr_fed_urn'), 'urn');
        $f .= form_input('urn', set_value('urn', $federation->getUrn())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_include_attr_in_meta'), 'incattrs') . form_checkbox('incattrs', 'accept', set_value('incattrs', $federation->getAttrsInmeta())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_lexport_enabled'), 'lexport') . form_checkbox('lexport', 'accept', set_value('lexport', $federation->getLocalExport())) . '</li>';
        $f .='<li>' . form_label(lang('rr_description'), 'description');
        $f .=form_textarea('description', set_value('description', $federation->getDescription())) . '</li>';
        $f .='<li>' . form_label(lang('rr_fed_tou'), 'tou');
        $f .= form_textarea('tou', set_value('tou', $federation->getTou())) . '</li>';
        $f .='</ol>' . form_fieldset_close();
        return $f;
    }

    /**
     * function return html of form elements from attributes like:
     * homeorgname,displayname,homeurl,helpdeskurl,validfrom,validto
     */
    public function generateIdpBasicForm($provider)
    {
        if (!$provider instanceof models\Provider)
        {
            return false;
        }
        $tmp = form_fieldset(lang('rr_basicinformation'));
        $tmp .= '<ol><li>' . form_label(lang('rr_homeorganisationname'), 'homeorgname');
        $in = array('id' => 'homeorgname', 'name' => 'homeorgname', 'required' => 'required', 'value' => set_value('homeorgname', $provider->getName()));
        $tmp .= form_input($in);
        $tmp .= '</li><li>' . form_label(lang('rr_displayname'), 'displayname');
        $tmp .= form_input('displayname', set_value('displayname', $provider->getDisplayName()));
        $tmp .= '</li><li>' . form_label(lang('rr_homeorganisationurl'), 'homeurl');
        $tmp .= form_input('homeurl', set_value('homeurl', $provider->getHomeUrl()));
        $tmp .= '</li><li>' . form_label(lang('rr_helpdeskurl'), 'helpdeskeurl');
        $tmp .= form_input('helpdeskurl', set_value('helpdeskurl', $provider->getHelpdeskUrl()));
        $tmp .= '</li><li>' . form_label(lang('rr_validfrom'), 'validfrom');
        $tmp .= form_input(array('name' => 'validfrom', 'id' => 'validfrom', 'value' => set_value('validfrom', $provider->getValidFrom()->format('Y-m-d'))));
        $tmp .= '</li><li>' . form_label(lang('rr_validto'), 'validto');
        $vtm = $provider->getValidTo();
        if (!empty($vtm))
        {
            $tmp .= form_input(array('name' => 'validto', 'id' => 'validto', 'value' => set_value('validto', $provider->getValidTo()->format('Y-m-d'))));
        }
        else
        {
            $tmp .= form_input(array('name' => 'validto', 'id' => 'validto', 'value' => set_value('validto')));
        }
        $tmp .= '</li><li>' . form_label(lang('rr_description'), 'description');
        $tmp .= form_textarea('description', set_value('description', $provider->getDescription()));
        $tmp .= '</li></ol>' . form_fieldset_close();
        return $tmp;
    }

    public function excludedArpsForm(models\Provider $idp)
    {
        $tmp_providers = new models\Providers();
        $excluded = $idp->getExcarps();
        $members = $tmp_providers->getCircleMembersSP($idp);
        if (is_array($excluded))
            $rows = array();
        foreach ($excluded as $v)
        {
            $members->remove($v);
            $rows[] = '<input type="checkbox" name="exc[]" id="' . $v . '" value="' . $v . '" checked="checked" /><label for="' . $v . '">' . $v . '</label>';
        }
        foreach ($members as $v)
        {
            $rows[] = '<input type="checkbox" name="exc[]" id="' . $v->getEntityId() . '" value="' . $v->getEntityId() . '"  /><label for="' . $v->getEntityId() . '">' . $v->getEntityId() . '</label>';
        }

        return $rows;
    }

    public function supportedAttributesForm(models\Provider $idp)
    {
        $tmp = new models\Attributes();
        $attributes_defs = $tmp->getAttributes();
        if (empty($attributes_defs))
        {
            log_message('error', 'There is no attributes definitions');
            return null;
        }
        $tmp1 = new models\AttributeReleasePolicies();
        $supported = $tmp1->getSupportedAttributes($idp);
        $data = array();
        foreach ($attributes_defs as $a)
        {
            $data[$a->getId()] = array('s' => 0, 'name' => $a->getName(), 'attrid' => $a->getId());
        }
        if (!empty($supported))
        {

            foreach ($supported as $s)
            {
                $data[$s->getAttribute()->getId()]['s'] = 1;
            }
        }

        $result_top = "";
        $result_bottom = "";
        $result = '<table id="details">';
        $result .= '<thead><tr><th>' . lang('rr_attr_name') . '</th><th>' . lang('rr_supported') . '</th></tr></thead>';
        foreach ($data as $d => $value)
        {
            if ($value['s'] == 1)
            {
                $f = form_checkbox('attr[' . $value['attrid'] . ']', '1', true);
                $result_top .= '<tr><td>' . $value['name'] . '</td><td>' . $f . '</td></tr>';
            }
            else
            {
                $f = form_checkbox('attr[' . $value['attrid'] . ']', '1', false);
                $result_bottom .='<tr><td>' . $value['name'] . '</td><td>' . $f . '</td></tr>';
            }
        }
        if (!empty($result_top))
        {
            $result_top = '<tbody class="attr_supported">' . $result_top . '</tbody>';
        }
        if (!empty($result_bottom))
        {
            $result_bottom = '<tbody>' . $result_bottom . '</tbody>';
        }
        $result .= $result_top . $result_bottom . '</table>';
        return $result;
    }

    public function generateEditPolicyForm(models\AttributeReleasePolicy $arp, $action = null, $submit_type = null)
    {
        $result = '';
        $attributes = array('id' => 'formver2');
        $type = $arp->getType();
        $hidden = array('idpid' => $arp->getProvider()->getId(), 'attribute' => $arp->getAttribute()->getId(), 'requester' => $arp->getRequester());
        if ($type == 'fed')
        {
            $hidden['fedid'] = $arp->getRequester();
        }
        if (empty($action))
        {
            $action = base_url() . 'manage/attribute_policy/submit';
        }
        $result .= form_open($action, $attributes, $hidden);
        $result .= $this->generateEditPolicyFormElement($arp);
        //$result .= form_fieldset('');
        $result .='<div class="buttons">';
        if (!empty($submit_type) && $submit_type == 'create')
        {
            $cancel_value = 'cancel';
            $save_value = 'create';
        }
        else
        {
            $save_value = 'modify';
            $cancel_value = 'delete';
        }
        $result .= '<button name="submit" type="submit" value="' . $cancel_value . '" class="btn negative"><span class="cancel">' . $cancel_value . '</span></button>';
        $result .= '<button name="submit" type="submit" value="' . $save_value . '" class="btn positive"><span class="save">' . $save_value . '</span></button>';
        $result .='</div>';

        //$result .= form_fieldset_close();

        $result .=form_close();
        return $result;
    }

    public function generateEditPolicyFormElement(models\AttributeReleasePolicy $arp)
    {
        $result = '';
        $result .= form_fieldset(lang('rr_attr_name') . ': ' . $arp->getAttribute()->getFullName() . ' (' . $arp->getAttribute()->getName() . ')');
        $result .= '<ol><li>' . form_label(lang('rr_setpolicy'), 'policy');
        $result .= form_dropdown('policy', $this->ci->config->item('policy_dropdown'), $arp->getPolicy());
        $result .= '</li></ol>' . form_fieldset_close();
        return $result;
    }

    public function generateAddCoc()
    {
        $r = form_fieldset('');
        $r .= '<ol>';
        $r .= '<li>' . form_label(lang('coc_enabled'), 'cenabled') . form_checkbox('cenabled', 'accept') . '</li>';
        $r .= '<li>' . form_label(lang('coc_shortname'), 'name') . form_input('name', set_value('name')) . '</li>';
        $r .= '<li>' . form_label(lang('coc_url'), 'url') . form_input('url', set_value('url')) . '</li>';
        $r .= '<li>' . form_label(lang('coc_description'), 'description') . form_textarea('description', set_value('description')) . '</li>';
        $r .= '</ol>';
        $r .= form_fieldset_close();
        return $r;
    }

    public function generateEditCoc(models\Coc $coc)
    {
        $r = form_fieldset('');
        $r .= '<ol>';
        $r .= '<li>' . form_label(lang('coc_enabled'), 'cenabled') . form_checkbox('cenabled', 'accept', set_value('cenabled', $coc->getAvailable())) . '</li>';
        $r .= '<li>' . form_label(lang('coc_shortname'), 'name') . form_input('name', set_value('name', $coc->getName())) . '</li>';
        $r .= '<li>' . form_label(lang('coc_url'), 'url') . form_input('url', set_value('url', $coc->getUrl())) . '</li>';
        $r .= '<li>' . form_label(lang('coc_description'), 'description') . form_textarea('description', set_value('description', $coc->getDescription())) . '</li>';
        $r .= '</ol>';
        $r .= form_fieldset_close();
        return $r;
    }

}

