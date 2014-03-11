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
    protected $disallowedparts = array();
    protected $defaultlangselect = 'en';

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('form');
        $this->ci->load->helper('shortcodes');
        $a = $this->ci->config->item('langselectdefault');
        if(!empty($a))
        {
            $this->defaultlangselect = $a;
        }
        log_message('debug', 'lib/Form_element initialized');
        $isAdmin = $this->ci->j_auth->isAdministrator();
        if($isAdmin)
        {
            $disallowedparts = array();
        } 
        else
        {
            $disallowedparts = $this->ci->config->item('entpartschangesdisallowed');
            if(empty($disallowedparts) || !is_array($disallowedparts))
            {
                $disallowedparts = array();
            } 
        }
        $this->disallowedparts = $disallowedparts;
    }

    public function NgenerateOtherFormLinks(models\Provider $ent)
    {
        $l = array();
        $base = base_url();
        $t = $ent->getType();
        $id = $ent->getId();
        if ($t === 'BOTH')
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/sp', ''.lang('rr_geolocation').' ('.lang('serviceprovider').')');
            $l[] = anchor($base . 'geolocation/show/' . $id . '/idp', ''.lang('rr_geolocation').' ('.lang('identityprovider').')');
            $l[] = anchor($base . 'manage/logomngmt/provider/idp/' . $id . '', ''.lang('rr_logos').' ('.lang('identityprovider').')');
            $l[] = anchor($base . 'manage/logomngmt/provider/sp/' . $id . '', ''.lang('rr_logos').' ('.lang('serviceprovider').')');
        }
        elseif ($t === 'IDP')
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/idp', ''.lang('rr_geolocation').'');
            $l[] = anchor($base . 'manage/logomngmt/provider/idp/' . $id . '', ''.lang('rr_logos').'');
        }
        else
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/sp', ''.lang('rr_geolocation').'');
            $l[] = anchor($base . 'manage/logomngmt/provider/sp/' . $id . '', ''.lang('rr_logos').'');
        }
        if ($t != 'IDP')
        {
            $l[] = anchor($base . 'manage/attribute_requirement/sp/' . $id . '', ''.lang('rr_requiredattributes').'');
        }
        if ($t != 'SP')
        {
            $l[] = anchor($base . 'manage/supported_attributes/idp/' . $id . '', ''.lang('rr_supportedattributes').'');
            $l[] = anchor($base . 'manage/attribute_policy/globals/' . $id . '', ''.lang('rr_attributepolicy').'');
            $l[] = anchor($base . 'manage/arpsexcl/idp/' . $id . '', ''.lang('srvs_excluded_from_arp').'');
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
            $t_regdate = date('Y-m-d',$tmpregdate->format('U')+j_auth::$timeOffset);
            $origregdate = date('Y-m-d',$tmpregdate->format('U')+j_auth::$timeOffset);
        }
        $t_homeurl = $ent->getHomeUrl();
        $t_helpdeskurl = $ent->getHelpdeskUrl();
        if(empty($t_homeurl))
        {
            $t_homeurl = $t_helpdeskurl;
        }

        $t_validfrom = '';
        $origvalidfrom = '';
        $tmpvalidfrom = $ent->getValidFrom();
        if (!empty($tmpvalidfrom))
        {
            $t_validfrom = date('Y-m-d',$tmpvalidfrom->format('U')+j_auth::$timeOffset);
            $origvalidfrom = date('Y-m-d',$tmpvalidfrom->format('U')+j_auth::$timeOffset);
        }
        $t_validto = '';
        $origvalidto = '';
        $tmpvalidto = $ent->getValidTo();
        if (!empty($tmpvalidto))
        {
            $t_validto = date('Y-m-d',$tmpvalidto->format('U')+j_auth::$timeOffset);
            $origvalidto = date('Y-m-d',$tmpvalidto->format('U')+j_auth::$timeOffset);
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
        $result[] = '';
        if(!in_array('entityid',$this->disallowedparts))
        {
            $result[] = form_label(lang('rr_entityid'), 'f[entityid]') . form_input(array('id' => 'f[entityid]', 'class' => $class_ent, 'name' => 'f[entityid]', 'required' => 'required', 'value' => $t1));
        }
        else
        {
            $result[] = form_label(lang('rr_entityid'), 'f[entityid]') . form_input(array('id' => 'f[entityid]', 'class' => $class_ent, 'name' => 'f[entityid]', 'required' => 'required','readonly'=>'readonly', 'value' => $t1));

        }
        $result[] = '';

        // providername group 
        $result[] = '';
        /**
         * @todo add explanation for default provider name
         */
        //$result[] = '<div class="notice">'.lang('rr_providername').' <small>&#91;'.lang('rr_default').'&#93;</small> </div>';
       // $result[] = '';
        $result[] = form_label(lang('rr_providername').' '.showBubbleHelp(''.lang('entname_default_expl').''), 'f[orgname]') . form_input(array('id' => 'f[orgname]', 'class' => $class_org, 'name' => 'f[orgname]', 'required' => 'required', 'value' => $t2));
        /**
         * start lname
         */
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
            ).'<button type="button" class="btn langinputrm" name="lname" value="'.$key.'">X</button>';
            unset($origlname['' . $key . '']);
            unset($lnamelangs['' . $key . '']);
        }
        if(!$sessform)
        {
        foreach ($origlname as $key => $value)
        {
            $lnamenotice = '';
            $lvalue = set_value('f[lname][' . $key . ']', $value);
            if(empty($lvalue))
            {
               continue;
            }
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
            ).'<button type="button" class="btn langinputrm" name="lname" value="'.$key.'">X</button>';
            unset($lnamelangs['' . $key . '']);
        }
        }
        $result[] = '<span class="lnameadd">' . form_dropdown('lnamelangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="addlname" name="addlname" value="'.lang('rr_providername').'" class="editbutton addicon smallerbtn">'.lang('btnaddinlang').'</button></span>';

        $result[] = '';
        /**
         * end lname
         */



        $result[] = '';
        $result[] = form_label(lang('rr_displayname').' '.showBubbleHelp(''.lang('entdisplname_default_expl').''), 'f[displayname]') . form_input(array('id' => 'f[displayname]', 'class' => $class_displ, 'name' => 'f[displayname]', 'required' => 'required', 'value' => $f_displayname));
        /**
         * start ldisplayname
         */
        $ldisplaynames = $ent->getLocalDisplayName();
        $sldisplayname = array();
        $origldisplayname = array();
        $ldisplaynamelangs = languagesCodes();
        if ($sessform && array_key_exists('ldisplayname', $ses) && is_array($ses['ldisplayname']))
        {
            $sldisplayname = $ses['ldisplayname'];
        }
        if (is_array($ldisplaynames))
        {
            $origldisplayname = $ldisplaynames;
        }
        foreach ($sldisplayname as $key => $value)
        {
            if(empty($value))
            {
               continue;
            }
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
            ).'<button type="button" class="btn langinputrm" name="ldisplayname" value="'.$key.'">X</button>';
            unset($origldisplayname['' . $key . '']);
            unset($ldisplaynamelangs['' . $key . '']);
        }
        if(!$sessform)
        {
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
            ).'<button type="button" class="btn langinputrm" name="ldisplayname" value="'.$key.'">X</button>';
            unset($ldisplaynamelangs['' . $key . '']);
        }
        }
        $result[] = '<span class="ldisplaynameadd">' . form_dropdown('ldisplaynamelangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="addldisplayname" name="addldisplayname" value="'.lang('rr_displayname').'" class="editbutton addicon smallerbtn">'.lang('btnaddinlang').'</button></span>';

        /**
         * end ldisplayname
         */
        $result[] = '';
        /**
         * END displayname
         */
        /**
         * start organizatiourl/helpdesk
         */
        $result[] = '';
        $result[] = form_label(lang('rr_helpdeskurl').' <small>&#91;'.lang('rr_default').'&#93;</small>', 'f[helpdeskurl]') . form_input(array('id' => 'f[helpdeskurl]', 'class' => $helpdeskurl_notice, 'name' => 'f[helpdeskurl]', 'value' => $f_helpdeskurl));
        /**
         * start lhelpdesk
         */
        $lhelpdesk = $ent->getLocalHelpdeskUrl();
        $slhelpdesk = array();
        $origlhelpdesk = array();
        $lhelpdesklangs = languagesCodes();
        if ($sessform && array_key_exists('lhelpdesk', $ses) && is_array($ses['lhelpdesk']))
        {
            $slhelpdesk = $ses['lhelpdesk'];
        }
        if (is_array($lhelpdesk))
        {
            $origlhelpdesk = $lhelpdesk;
        }
        foreach ($slhelpdesk as $key => $value)
        {
            if(empty($value))
            {
               continue;
            }
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
            ).'<button type="button" class="btn langinputrm" name="lhelpdesk" value="'.$key.'">X</button>';
            unset($origlhelpdesk['' . $key . '']);
            unset($lhelpdesklangs['' . $key . '']);
        }
        if(!$sessform)
        {
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
            ).'<button type="button" class="btn langinputrm" name="lhelpdesk" value="'.$key.'">X</button>';
            unset($lhelpdesklangs['' . $key . '']);
        }
        }
        $result[] = '<span class="lhelpdeskadd">' . form_dropdown('lhelpdesklangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="addlhelpdesk" name="addlhelpdesk" value="'.lang('rr_helpdeskurl').'" class="editbutton addicon smallerbtn">'.lang('btnaddinlang').'</button></span>';


        $result[] = '';
        /**
         * end organizatiourl/helpdesk
         */
        
        $result[] = '';
        $result[] = form_label(lang('rr_regauthority'), 'f[regauthority]') . form_input(array('id' => 'f[regauthority]', 'class' => $regauthority_notice, 'name' => 'f[regauthority]', 'value' => $f_regauthority));

        $result[] = form_label(lang('rr_regdate'), 'f[registrationdate]') . form_input(array(
                    'name' => 'f[registrationdate]',
                    'id' => 'f[registrationdate]',
                    'value' => $f_regdate,
                    'class' => 'registrationdate ' . $regdate_notice,
        ));
        /**
         * start regpolicy 
         */
        //$result[] = '';
        $result[] = '<b>'.lang('localizedregpolicyfield').' '.showBubbleHelp(''.lang('entregpolicy_expl').'').'</b>';
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
            ).'<button type="button" class="btn langinputrm" name="lname" value="'.$key.'">X</button>';
            unset($origregpolicies['' . $key . '']);
            unset($regpolicylangs['' . $key . '']);
        }
        if(!$sessform)
        {
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
            ).'<button type="button" class="btn langinputrm" name="lname" value="'.$key.'">X</button>';
            unset($regpolicylangs['' . $key . '']);
        }
        }
        $result[] = '<span class="regpolicyadd">' . form_dropdown('regpolicylangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="addregpolicy" name="addregpolicy" value="'.lang('rr_regpolicy').'" class="editbutton addicon smallerbtn">'.lang('btnaddinlang').'</button></span>';

        //$result[] = '';
        /**
         * end regpolicy
         */
        $result[] = '';
        $result[] = form_label(lang('rr_homeurl'), 'f[homeurl]') . form_input(array('id' => 'f[homeurl]', 'class' => $homeurl_notice, 'name' => 'f[homeurl]', 'value' => $f_homeurl));
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


        $result[] = '';
        $result[] = form_label(lang('rr_description'), 'f[description]') . form_textarea(array(
                    'name' => 'f[description]',
                    'id' => 'f[description]',
                    'class' => $description_notice,
                    'value' => $f_description,
        ));
        /**
         * start ldesc
         */
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
            if(empty($value))
            {
               continue;
            }
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
            ).'<button type="button" class="btn langinputrm" name="lhelpdesk" value="'.$key.'">X</button>';
            unset($origldesc['' . $key . '']);
            unset($ldesclangs['' . $key . '']);
        }
        if(!$sessform)
        {
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
            ).'<button type="button" class="btn langinputrm" name="lhelpdesk" value="'.$key.'">X</button>';
            unset($ldesclangs['' . $key . '']);
        }
        }
        $result[] = '<span class="ldescadd">' . form_dropdown('ldesclangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="addldescription" name="addldescription" value="'.lang('rr_description').'" class="editbutton addicon smallerbtn">'.lang('btnaddinlang').'</button></span>';
        $result[] = '';

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

        $r = '<fieldset><legend>'.lang('PrivacyStatementURL').' <i>'.lang('rr_default').'</i>' . showBubbleHelp(''.lang('rhelp_privacydefault1').'') . '</legend><ol><li>';
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
        $r = '<fieldset><legend>'.lang('rr_coc').'</legend><ol>';
        if (is_array($coccols) and count($coccols) > 0)
        {
            $r .= '<li class="' . $cocnotice . '">';
            $r .= form_label(''.lang('rr_cocurl').' ' . showBubbleHelp(''.lang('rrhelp_contactifnococ').''), 'f[coc]');
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
            $r = '<fieldset><legend>'.lang('PrivacyStatementURL').' <i>IDPSSODescriptor</i></legend><ol>';
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

            $r .= form_dropdown('langcode', MY_Controller::$langselect, $this->defaultlangselect);
            $r .= '<button type="button" id="addlprivacyurlidpsso" name="addlprivacyurlidpsso" value="addlprivacyurlidpsso" class="editbutton addicon smallerbtn">'.lang('addlocalized').' ' . lang('rr_privacystatement') . '</button>';

            $r .= '</ol></fieldset>';
            $result[] = $r;
        }
        if (strcmp($enttype,'IDP') != 0)
        {
            $r = '<fieldset><legend>'.lang('PrivacyStatementURL').' <i>SPSSODescriptor</i></legend><ol>';
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
            $r .= form_dropdown('langcode', MY_Controller::$langselect, $this->defaultlangselect);
            $r .= '<button type="button" id="addlprivacyurlspsso" name="addlprivacyurlspsso" value="addlprivacyurlspsso" class="editbutton addicon smallerbtn">'.lang('addlocalized') .' ' . lang('rr_privacystatement') . '</button>';
            $r .= '</li></ol></fieldset>';
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
        $n = '<button class="editbutton addicon smallerbtn" type="button" id="ncontactbtn">'.lang('rr_addnewcoontact').'</button>';
        $result[] = $n;

        return $result;
    }

    public function NgenerateServiceLocationsForm(models\Provider $ent, $ses = null)
    {
        $ssotmpl = array(); 
        $acsbindprotocols = array();
        $ssobindprotocols = getBindSingleSignOn();
        
        $tmpacsprotocols = getBindACS();
        foreach($tmpacsprotocols as $v)
        {
           $acsbindprotocols[''.$v.''] = $v;
        }
       
        foreach($ssobindprotocols as $v)
        {
           $ssotmpl[''.$v.''] = $v;
        }
       
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
            $SSOPart = '<fieldset><legend>'.lang('SingleSignOnService').'</legend><ol>';
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
            $IDPSLOPart = '<fieldset><legend>'.lang('IdPSLO').'</legend><ol>';
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
            $ACSPart = '<fieldset><legend>'.lang('ArtifactResolutionService').' <small><i>IDPSSODescriptor</i></small></legend><ol>';
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
                    $r .= '<br /></li></ol></li>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nidpartifactbtn">'.lang('rr_addnewidpartifactres').'</button></li>';
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
            $result[] = '<fieldset><legend>'.lang('atributeauthoritydescriptor').'</legend><ol>' . implode('', $aalo) . '</ol></fieldset>';

            /**
             * end AttributeAuthorityDescriptor Location
             */
        }
        if ($enttype != 'IDP')
        {
            /**
             * generate ACS part
             */
            $ACSPart = '<fieldset><legend>'.lang('assertionconsumerservice').'</legend><ol>';
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
                    $r .= '<span class="' . $bindnotice . '">' . form_dropdown('f[srv][AssertionConsumerService][' . $v3->getId() . '][bind]', $acsbindprotocols, $fbind) . '</span></li>';
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
                    $r .= '<li>' . form_label(''.lang('rr_bindingname').'', 'f[srv][AssertionConsumerService][' . $k4 . '][bind]');
                    $r .= form_dropdown('f[srv][AssertionConsumerService][' . $k4 . '][bind]', $acsbindprotocols, $v4['bind']) . '</li>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nacsbtn">'.lang('addnewacs').'</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end ACS part
             */
            /**
             * generate ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>'.lang('artifactresolutionservice').' <small><i>SPSSODescriptor</i></small></legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nspartifactbtn">'.lang('addnewartresservice').'</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end SPArtifactResolutionService part
             */












            


            /**
             * start SP SingleLogoutService
             */
            $SPSLOPart = '<fieldset><legend>'.lang('singlelogoutservice').' <small><i>'.lang('serviceprovider').'</i></small></legend><ol>';
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
            $RequestInitiatorPart = '<fieldset><legend>'.lang('requestinitatorlocations').'</legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nribtn">'.lang('addnewreqinit').'</button></li>';
            $RequestInitiatorPart .= $newelement . '</ol><fieldset>';
            $result[] = $RequestInitiatorPart;
            /**
             * end RequestInitiator
             */
            /**
             * start DiscoveryResponse
             */
            $DiscoverResponsePart = '<fieldset><legend>'.lang('discoveryresponselocations').'</legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="ndrbtn">'.lang('addnewds').'</button></li>';
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
            $Part = '<fieldset><legend>'.lang('idpcerts').' <small><i>IDPSSODesciptor</i></small></legend><ol>';
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
                    $row .= form_dropdown('f[crt][idpsso][' . $crtid . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')),set_value('f[crt][idpsso][' . $crtid . '][remove]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][idpsso][' . $crtid . '][type]');
                    $row .= form_dropdown('f[crt][idpsso][' . $crtid . '][type]', array('x509' => 'x509'),set_value('f[crt][idpsso][' . $crtid . '][type]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][idpsso][' . $crtid . '][usage]');
                    $row .= '<span class="' . $usagenotice . '">' . form_dropdown('f[crt][idpsso][' . $crtid . '][usage]', array('signing' => ''.lang('rr_certsigning').'', 'encryption' => ''.lang('rr_certencryption').'', 'both' => ''.lang('rr_certsignandencr').''), $fusage) . '</span></li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), 'f[crt][idpsso][' . $crtid . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][idpsso][' . $crtid . '][keyname]',
                                'id' => 'f[crt][idpsso][' . $crtid . '][keyname]',
                                'class' => $keynamenotice,
                                'value' => $fkeyname)) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), 'f[crt][idpsso][' . $crtid . '][certdata]');
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
                    $row .= form_dropdown('f[crt][idpsso][' . $k4 . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')),set_value('f[crt][idpsso][' . $k4 . '][remove]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][idpsso][' . $k4 . '][type]');
                    $row .= form_dropdown('f[crt][idpsso][' . $k4 . '][type]', array('x509' => 'x509'),set_value('f[crt][idpsso][' . $k4 . '][type]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][idpsso][' . $k4 . '][usage]');
                    $row .= form_dropdown('f[crt][idpsso][' . $k4 . '][usage]', array('signing' => ''.lang('rr_certsigning').'', 'encryption' => ''.lang('rr_certencryption').'', 'both' => ''.lang('rr_certsignandencr').''), set_value('f[crt][idpsso][' . $k4 . '][usage]', $v4['usage'])) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), 'f[crt][idpsso][' . $k4 . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][idpsso][' . $k4 . '][keyname]',
                                'id' => 'f[crt][idpsso][' . $k4 . '][keyname]',
                                'class' => 'notice',
                                'value' => set_value('f[crt][idpsso][' . $k4 . '][keyname]', $v4['keyname']))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), 'f[crt][idpsso][' . $k4 . '][certdata]');
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nidpssocert">'.lang('addnewcert').' '.lang('for').' IDPSSODescriptor</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
            $Part = '';
            /**
             * end CERTs for IDPSSODescriptor
             */
            /**
             * start CERTs for AttributeAuthority
             */
            $Part = '<fieldset><legend>'.lang('idpcerts').' <i><small>'.lang('atributeauthoritydescriptor').'</small></i></legend><ol>';
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
                    $row .= form_dropdown('f[crt][aa][' . $crtid . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')),set_value('f[crt][aa][' . $crtid . '][remove]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][aa][' . $crtid . '][type]');
                    $row .= form_dropdown('f[crt][aa][' . $crtid . '][type]', array('x509' => 'x509'),set_value('f[crt][aa][' . $crtid . '][type]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][aa][' . $crtid . '][usage]');
                    $row .= '<span class="' . $usagenotice . '">' . form_dropdown('f[crt][aa][' . $crtid . '][usage]', array('signing' => ''.lang('rr_certsigning').'', 'encryption' => ''.lang('rr_certencryption').'', 'both' => ''.lang('rr_certsignandencr').''), $fusage) . '</span></li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), 'f[crt][aa][' . $crtid . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][aa][' . $crtid . '][keyname]',
                                'id' => 'f[crt][aa][' . $crtid . '][keyname]',
                                'class' => $keynamenotice,
                                'value' => $fkeyname)) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), 'f[crt][aa][' . $crtid . '][certdata]');
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
                    $row .= form_dropdown('f[crt][aa][' . $k4 . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')),set_value('f[crt][aa][' . $k4 . '][remove]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][aa][' . $k4 . '][type]');
                    $row .= form_dropdown('f[crt][aa][' . $k4 . '][type]', array('x509' => 'x509'),set_value('f[crt][aa][' . $k4 . '][type]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][aa][' . $k4 . '][usage]');
                    $row .= form_dropdown('f[crt][aa][' . $k4 . '][usage]', array('signing' => ''.lang('rr_certsigning').'', 'encryption' => ''.lang('rr_certencryption').'', 'both' => ''.lang('rr_certsignandencr').''), set_value('f[crt][aa][' . $k4 . '][usage]', $v4['usage'])) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), 'f[crt][aa][' . $k4 . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][aa][' . $k4 . '][keyname]',
                                'id' => 'f[crt][aa][' . $k4 . '][keyname]',
                                'class' => 'notice',
                                'value' => set_value('f[crt][aa][' . $k4 . '][keyname]', $v4['keyname']))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), 'f[crt][aa][' . $k4 . '][certdata]');
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="naacert">'.lang('addnewcert').' '.lang('for').' '.lang('atributeauthoritydescriptor').'</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
            $Part = '';
        }
        if ($enttype != 'IDP')
        {
            $Part = '<fieldset><legend>'.lang('rr_certificates').' <small><i>'.lang('serviceprovider').'</i></small></legend><ol>';
            $spssocerts = array();
            if (isset($cert['spsso']) && is_array($cert['spsso']))
            {
                foreach ($cert['spsso'] as $k => $v)
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
                    $row .= form_dropdown('f[crt][spsso][' . $crtid . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')),set_value('f[crt][spsso][' . $crtid . '][remove]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][spsso][' . $crtid . '][type]');
                    $row .= form_dropdown('f[crt][spsso][' . $crtid . '][type]', array('x509' => 'x509'),set_value('f[crt][spsso][' . $crtid . '][type]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][spsso][' . $crtid . '][usage]');
                    $row .= '<span class="' . $usagenotice . '">' . form_dropdown('f[crt][spsso][' . $crtid . '][usage]', array('signing' => ''.lang('rr_certsigning').'', 'encryption' => ''.lang('rr_certencryption').'', 'both' => ''.lang('rr_certsignandencr').''), $fusage) . '</span></li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), 'f[crt][spsso][' . $crtid . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][spsso][' . $crtid . '][keyname]',
                                'id' => 'f[crt][spsso][' . $crtid . '][keyname]',
                                'class' => $keynamenotice,
                                'value' => $fkeyname)) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), 'f[crt][spsso][' . $crtid . '][certdata]');
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
                    $row .= form_dropdown('f[crt][spsso][' . $k4 . '][remove]', array('none' => lang('rr_keepit'), 'yes' => lang('rr_yesremoveit')),set_value('f[crt][spsso][' . $k4 . '][remove]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificatetype'), 'f[crt][spsso][' . $k4 . '][type]');
                    $row .= form_dropdown('f[crt][spsso][' . $k4 . '][type]', array('x509' => 'x509'),set_value('f[crt][spsso][' . $k4 . '][type]')) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificateuse'), 'f[crt][spsso][' . $k4 . '][usage]');
                    $row .= form_dropdown('f[crt][spsso][' . $k4 . '][usage]', array('signing' => ''.lang('rr_certsigning').'', 'encryption' => ''.lang('rr_certencryption').'', 'both' => ''.lang('rr_certsignandencr').''), set_value('f[crt][spsso][' . $k4 . '][usage]', $v4['usage'])) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), 'f[crt][spsso][' . $k4 . '][keyname]');
                    $row .= form_input(array(
                                'name' => 'f[crt][spsso][' . $k4 . '][keyname]',
                                'id' => 'f[crt][spsso][' . $k4 . '][keyname]',
                                'class' => 'notice',
                                'value' => set_value('f[crt][spsso][' . $k4 . '][keyname]', $v4['keyname']))) . '</li>';
                    $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), 'f[crt][spsso][' . $k4 . '][certdata]');
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nspssocert">'.lang('addnewcert').'</button></li>';
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


            $r = '<fieldset><legend>'.lang('rr_scope').' '.showBubbleHelp(''.lang('rhelp_scopemultivalues').'').'</legend><ol>';

            if(in_array('scope',$this->disallowedparts))
            {
                $r .= '<li>' . form_label(''.lang('rr_scope').' '.lang('idpssodescriptor').'', 'f[scopes][idpsso]') . form_input(array(
                        'name' => 'f[scopes][idpsso]',
                        'id' => 'f[scopes][idpsso]',
                        'readonly'=>'readonly',
                        'value' => $scopessovalue,
                        'class' => $scopeidpssonotice,
                    )) . '</li>';
                $r .= '<li>' . form_label(''.lang('rr_scope').' '.lang('atributeauthoritydescriptor').'', 'f[scopes][aa]') . form_input(array(
                        'name' => 'f[scopes][aa]',
                        'id' => 'f[scopes][aa]',
                        'readonly'=>'readonly',
                        'value' => $scopeaavalue,
                        'class' => $scopeaanotice,
                    )) . '</li>';
            }
            else
            {
                $r .= '<li>' . form_label(''.lang('rr_scope').' '.lang('idpssodescriptor').'', 'f[scopes][idpsso]') . form_input(array(
                        'name' => 'f[scopes][idpsso]',
                        'id' => 'f[scopes][idpsso]',
                        'value' => $scopessovalue,
                        'class' => $scopeidpssonotice,
                    )) . '</li>';
                $r .= '<li>' . form_label(''.lang('rr_scope').' '.lang('atributeauthoritydescriptor').'', 'f[scopes][aa]') . form_input(array(
                        'name' => 'f[scopes][aa]',
                        'id' => 'f[scopes][aa]',
                        'value' => $scopeaavalue,
                        'class' => $scopeaanotice,
                    )) . '</li>';
            }
            $r .= '</ol></fieldset>';
            $result[] = $r;


            /**
             * IDP protocols 
             */
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
            $r = '<fieldset><legend>'.lang('rr_supportedprotocols').' <i>'.lang('idpssodescriptor').'</i></legend><ol>';
            $r .= '<li class="' . $idpssonotice . '">';
            $r .= '<ul class="checkboxlist">';
            foreach($allowedoptions as $a )
            {
                  $is = FALSE;
                  if(in_array($a,$selected_options))
                  {
                      $is = TRUE;
                  }
                  $r .= '<li>'.form_checkbox(array('name'=>'f[prot][idpsso][]','id'=>'f[prot][idpsso][]','value'=>$a,'checked'=>$is)).$a.'</li>';
            }
            $r .= '</ul>';
            $r .='</li>';
            $r .= '</ol></fieldset>';
            $result[] = $r;

            $r = '<fieldset><legend>'.lang('rr_supportedprotocols').' <i>'.lang('atributeauthoritydescriptor').'</i></legend><ol>';
            $aaprotocols = $ent->getProtocolSupport('aa');
            $selected_options = array();
            $aanotice = '';
            if ($sessform && isset($entsession['prot']['aa']) && is_array($entsession['prot']['aa']))
            {
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
            $r .= '<li class="' . $aanotice . '">';
            $r .=  '<ul class="checkboxlist">';
            foreach($allowedoptions as $a )
            {
                  $is = FALSE;
                  if(in_array($a,$selected_options))
                  {
                      $is = TRUE;
                  }
                  $r .= '<li>'.form_checkbox(array('name'=>'f[prot][aa][]','id'=>'f[prot][aa][]','value'=>$a,'checked'=>$is)).$a.'</li>';
            }
            $r .= '</ul>';
            $r .= '</li>';
           
            $r .= '</ol></fieldset>';
            $result[] = $r;
        }
        if ($enttype != 'IDP')
        {
            $r = '<fieldset><legend>'.lang('rr_supportedprotocols').' <i>'.lang('spssodescriptor').'</i></legend><ol>';
            $spssoprotocols = $ent->getProtocolSupport('spsso');
            $selected_options = array();
            $spssonotice = '';
            if ($sessform && isset($entsession['prot']['spsso']) && is_array($entsession['prot']['spsso']))
            {
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
            //$r .= '<li class="' . $spssonotice . '">' . form_multiselect('f[prot][spsso][]', $allowedoptions, $selected_options) . '</li>';
            $r .= '<li class="' . $spssonotice . '">';
            $r .=  '<ul class="checkboxlist">';
            foreach($allowedoptions as $a )
            {
                  $is = FALSE;
                  if(in_array($a,$selected_options))
                  {
                      $is = TRUE;
                  }
                  $r .= '<li>'.form_checkbox(array('name'=>'f[prot][spsso][]','id'=>'f[prot][spsso][]','value'=>$a,'checked'=>$is)).$a.'</li>';
            }
            $r .= '</ul>';
            $r .= '</li>';
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
            $r = '<fieldset><legend>'.lang('rr_supportednameids').' <i>'.lang('idpssodescriptor').'</i></legend><ol>';
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
            $r = '<fieldset><legend>'.lang('rr_supportednameids').' <i>'.lang('atributeauthoritydescriptor').'</i></legend><ol>';
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
            $r = '<fieldset><legend>'.lang('rr_supportednameids').' <i>'.lang('spssodescriptor').'</i></legend><ol>';
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
            $result[] = '<div class="section">'.lang('identityprovider').'</div>';

            /**
             * start display
             */
            $r = form_fieldset(''.lang('uiiidpdisplayname').'');
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
            $r .= '<li><span class="idpuiidisplayadd">' . form_dropdown('idpuiidisplaylangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="idpadduiidisplay" name="idpadduiidisplay" value="idpadduiidisplay" class="editbutton addicon smallerbtn">'.lang('addlocalizeduiidisplayname').'</button></span></li>';
            $r .= form_fieldset_close();
            $result[] = $r;

            /**
             * end display
             */
            /**
             * start helpdesk 
             */
            $r = form_fieldset(''.lang('uiiinformationurl').'');
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
            $r .= '<li><span class="idpuiihelpdeskadd">' . form_dropdown('idpuiihelpdesklangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="idpadduiihelpdesk" name="idpadduiihelpdesk" value="idpadduiihelpdesk" class="editbutton addicon smallerbtn">'.lang('addlocalizedhelpdesk').'</button></span></li>';
            $r .= form_fieldset_close();
            $result[] = $r;

            /**
             * end helpdesk
             */

            /**
             * start description
             */
            $r = form_fieldset(''.lang('rr_provdesc').'');
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['idp']['mdui']['Description']))
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

                    $r .= '<button type="button" class="btn langinputrm" name="lhelpdesk" value="'.$lang.'">X</button></li></li>';
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

                    $r .= '<button type="button" class="btn langinputrm" name="lhelpdesk" value="'.$key.'">X</button></li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiidescadd">' . form_dropdown('idpuiidesclangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="idpadduiidesc" name="idpadduiidesc" value="'.lang('rr_description').'" class="editbutton addicon smallerbtn">'.lang('btnaddinlang').'</button></span></li>';
            $r .= form_fieldset_close();
            $result[] = $r;

            /**
             * end description 
             */
        }
        if ($type != 'IDP')
        {
            $result[] = '<div class="section">'.lang('serviceprovider').'</div>'; {


                /**
                 * start display
                 */
                $r = form_fieldset(''.lang('uiispdisplayname').'');
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
                $r .= '<li><span class="spuiidisplayadd">' . form_dropdown('spuiidisplaylangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="spadduiidisplay" name="spadduiidisplay" value="spadduiidisplay" class="editbutton addicon smallerbtn">'.lang('addlocalizeduiidisplayname').'</button></span></li>';
                $r .= form_fieldset_close();
                $result[] = $r;

                /**
                 * end display
                 */
                /**
                 * start helpdesk 
                 */
                $r = form_fieldset(''.lang('uiiinformationurl').'');
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
                $r .= '<li><span class="spuiihelpdeskadd">' . form_dropdown('spuiihelpdesklangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="spadduiihelpdesk" name="spadduiihelpdesk" value="spadduiihelpdesk" class="editbutton addicon smallerbtn">'.lang('addlocalizeinformationurl').'</button></span></li>';
                $r .= form_fieldset_close();
                $result[] = $r;

                /**
                 * end helpdesk
                 */
                /**
                 * start description
                 */
                $r = form_fieldset(''.lang('rr_provdesc').'');
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
                $r .= '<li><span class="spuiidescadd">' . form_dropdown('spuiidesclangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="spadduiidesc" name="spadduiidesc" value="spadduiidesc" class="editbutton addicon smallerbtn">'.lang('addlocalizeddesc').'</button></span></li>';
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
        $result .= form_dropdown('fedid', $list,set_value('fedid'));
        return $result;
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


    public function generateFederationEditForm(models\Federation $federation)
    {
        $f = null;
        $f .= form_fieldset(lang('rr_basicinformation'));
        $f .='<ol><li>' . form_label(lang('rr_fed_urn'), 'urn');
        $f .= form_input('urn', set_value('urn', $federation->getUrn())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_isfedpublic').' '.showBubbleHelp(lang('rhelppublicfed')), 'ispublic') . form_checkbox('ispublic', 'accept', set_value('ispublic', $federation->getPublic())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_include_attr_in_meta'), 'incattrs') . form_checkbox('incattrs', 'accept', set_value('incattrs', $federation->getAttrsInmeta())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_lexport_enabled'), 'lexport') . form_checkbox('lexport', 'accept', set_value('lexport', $federation->getLocalExport())) . '</li>';
        $f .='<li>' . form_label(lang('rr_description'), 'description');
        $f .=form_textarea('description', set_value('description', $federation->getDescription())) . '</li>';
        $f .='<li>' . form_label(lang('rr_fed_tou'), 'tou');
        $f .= form_textarea('tou', set_value('tou', $federation->getTou())) . '</li>';
        $f .='</ol>' . form_fieldset_close();
        return $f;
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
        $result .='<div class="buttons">';
        if (!empty($submit_type) && $submit_type == 'create')
        {
            $result .= '<button name="submit" type="submit" value="cancel" class="resetbutton reseticon">' .lang('rr_cancel') . '</button>';
            $result .= '<button name="submit" type="submit" value="create" class="savebutton saveicon">' . lang('rr_create') . '</button>';
        }
        else
        {
            $result .= '<button name="submit" type="submit" value="delete" class="resetbutton reseticon">' . lang('rr_remove') . '</button>';
            $result .= '<button name="submit" type="submit" value="modify" class="savebutton saveicon">' . lang('rr_modify') . '</button>';
        }
        $result .='</div>';
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

