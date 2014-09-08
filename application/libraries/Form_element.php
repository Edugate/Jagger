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
        $this->ci->load->helper(array('form', 'shortcodes'));
        $a = $this->ci->config->item('langselectdefault');
        if (!empty($a))
        {
            $this->defaultlangselect = $a;
        }
        log_message('debug', 'lib/Form_element initialized');
        $isAdmin = $this->ci->j_auth->isAdministrator();
        if ($isAdmin)
        {
            $disallowedparts = array();
        } else
        {
            $disallowedparts = $this->ci->config->item('entpartschangesdisallowed');
            if (empty($disallowedparts) || !is_array($disallowedparts))
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
            $l[] = anchor($base . 'geolocation/show/' . $id . '/sp', '' . lang('rr_geolocation') . ' (' . lang('serviceprovider') . ')');
            $l[] = anchor($base . 'geolocation/show/' . $id . '/idp', '' . lang('rr_geolocation') . ' (' . lang('identityprovider') . ')');
            $l[] = anchor($base . 'manage/logomngmt/provider/idp/' . $id . '', '' . lang('rr_logos') . ' (' . lang('identityprovider') . ')');
            $l[] = anchor($base . 'manage/logomngmt/provider/sp/' . $id . '', '' . lang('rr_logos') . ' (' . lang('serviceprovider') . ')');
        } elseif ($t === 'IDP')
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/idp', '' . lang('rr_geolocation') . '');
            $l[] = anchor($base . 'manage/logomngmt/provider/idp/' . $id . '', '' . lang('rr_logos') . '');
        } else
        {
            $l[] = anchor($base . 'geolocation/show/' . $id . '/sp', '' . lang('rr_geolocation') . '');
            $l[] = anchor($base . 'manage/logomngmt/provider/sp/' . $id . '', '' . lang('rr_logos') . '');
        }
        if ($t != 'IDP')
        {
            $l[] = anchor($base . 'manage/attribute_requirement/sp/' . $id . '', '' . lang('rr_requiredattributes') . '');
        }
        if ($t != 'SP')
        {
            $l[] = anchor($base . 'manage/supported_attributes/idp/' . $id . '', '' . lang('rr_supportedattributes') . '');
            $l[] = anchor($base . 'manage/attribute_policy/globals/' . $id . '', '' . lang('rr_attributepolicy') . '');
            $l[] = anchor($base . 'manage/arpsexcl/idp/' . $id . '', '' . lang('srvs_excluded_from_arp') . '');
        }
        return $l;
    }

    /**
     * new function with prefix N for generating forms elements will replace old ones
     */
    public function NgenerateEntityGeneral(models\Provider $ent, $ses = null)
    {
        $entid = $ent->getId();

        $isAdmin = $this->ci->j_auth->isAdministrator();
        $sessform = FALSE;
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $class_ent = '';
        $class_org = '';
        $t_regauthority = $ent->getRegistrationAuthority();
        $t_regdate = '';
        $origregdate = '';
        $tmpregdate = $ent->getRegistrationDate();
        if (!empty($tmpregdate))
        {
            $t_regdate = date('Y-m-d', $tmpregdate->format('U') + j_auth::$timeOffset);
            $origregdate = date('Y-m-d', $tmpregdate->format('U') + j_auth::$timeOffset);
        }

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
        }
        $f_regauthority = set_value('f[regauthority]', $t_regauthority);
        $f_regdate = set_value('f[registrationdate]', $t_regdate);
        if ($f_regauthority != $ent->getRegistrationAuthority())
        {
            $regauthority_notice = 'notice';
        } else
        {
            $regauthority_notice = '';
        }
        if ($f_regdate != $origregdate)
        {
            $regdate_notice = 'notice';
        } else
        {
            $regdate_notice = '';
        }
        $result = array();


        $tmprows = '';
        // providername group 
        $result[] = '';
        $tmprows .= '<fieldset><legend>' . lang('e_orgname') . '</legend>';
        /**
         * start lname
         */
        $lnames = $ent->getMergedLocalName();
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
        $btnlangs = MY_Controller::$langselect;
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
            } else
            {
                $lnamenotice = 'notice';
            }

            $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($lnamelangs[$key], 'f[lname][' . $key . ']', 'lname', $key, $lvalue, '' . $lnamenotice . '') . '</div>';
            unset($origlname['' . $key . '']);
            unset($lnamelangs['' . $key . '']);
            unset($btnlangs['' . $key . '']);
        }
        if (!$sessform)
        {
            foreach ($origlname as $key => $value)
            {
                $lnamenotice = '';
                $lvalue = set_value('f[lname][' . $key . ']', $value);
                if (empty($lvalue))
                {
                    continue;
                }
                if ($lvalue != $value)
                {
                    $lnamenotice = 'notice';
                }

                $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($lnamelangs[$key], 'f[lname][' . $key . ']', 'lname', $key, $lvalue, $lnamenotice) . '</div>';
                unset($lnamelangs['' . $key . '']);
                unset($btnlangs['' . $key . '']);
            }
        }

        $tmprows .= '<div class="small-12 columns">' . $this->_generateLangAddButton('lnameadd', 'lnamelangcode', $btnlangs, 'addlname', '' . lang('e_orgname') . '') . '</div>';

        $tmprows .= '</fieldset>';
        $result[] = $tmprows;

        $result[] = '';
        /**
         * end lname
         */
        $result[] = '';
        /**
         * start ldisplayname
         */
        $tmprows = '';
        $tmprows .= '<fieldset><legend>' . lang('e_orgdisplayname') . '</legend>';
        // $result[] = '<div class="langgroup">' . lang('e_orgdisplayname') . '</div>';
        $origldisplayname = $ent->getMergedLocalDisplayName();
        $sldisplayname = array();
        $ldisplaynamelangs = languagesCodes();
        $btnlangs = MY_Controller::$langselect;
        if ($sessform && array_key_exists('ldisplayname', $ses) && is_array($ses['ldisplayname']))
        {
            $sldisplayname = $ses['ldisplayname'];
        }
        foreach ($sldisplayname as $key => $value)
        {
            if (empty($value))
            {
                continue;
            }
            $ldisplaynamenotice = '';

            $lvalue = set_value('f[ldisplayname][' . $key . ']', $value);
            if (array_key_exists($key, $origldisplayname))
            {
                if ($origldisplayname['' . $key . ''] != $value)
                {
                    $ldisplaynamenotice = 'notice';
                }
            } else
            {
                $ldisplaynamenotice = 'notice';
            }
            if (isset($ldisplaynamelangs['' . $key . '']))
            {

                $tmprows .='<div class="small-12 columns">' . $this->_generateLangInputWithRemove($ldisplaynamelangs['' . $key . ''], 'f[ldisplayname][' . $key . ']', 'ldisplayname', $key, $lvalue, $ldisplaynamenotice) . '</div>';
                unset($origldisplayname['' . $key . '']);
                unset($ldisplaynamelangs['' . $key . '']);
                unset($btnlangs['' . $key . '']);
            }
        }
        if (!$sessform)
        {
            foreach ($origldisplayname as $key => $value)
            {
                $ldisplaynamenotice = '';
                $lvalue = set_value('f[ldisplayname][' . $key . ']', $value);
                if ($lvalue != $value)
                {
                    $ldisplaynamenotice = 'notice';
                }
                $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($ldisplaynamelangs['' . $key . ''], 'f[ldisplayname][' . $key . ']', 'ldisplayname', $key, $lvalue, $ldisplaynamenotice) . '</div>';
                unset($ldisplaynamelangs['' . $key . '']);
                unset($btnlangs['' . $key . '']);
            }
        }

        $tmprows .= '<div class="small-12 columns">' . $this->_generateLangAddButton('ldisplaynameadd', 'ldisplaynamelangcode', $btnlangs, 'addldisplayname', '' . lang('rr_displayname') . '') . '</div>';


        $tmprows .='</fieldset>';
        $result[] = $tmprows;
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
        /**
         * start lhelpdesk
         */
        $tmprows = '';
        $tmprows .= '<fieldset><legend>' . lang('e_orgurl') . '</legend>';
        $lhelpdesk = $ent->getHelpdeskUrlLocalized();
        $slhelpdesk = array();
        $origlhelpdesk = array();
        $btnlangs = MY_Controller::$langselect;
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
            if (empty($value))
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
            } else
            {
                $lhelpdesknotice = 'notice';
            }
            $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($lhelpdesklangs['' . $key . ''], 'f[lhelpdesk][' . $key . ']', 'lhelpdesk', $key, $lvalue, $lhelpdesknotice) . '</div>';
            unset($origlhelpdesk['' . $key . '']);
            unset($lhelpdesklangs['' . $key . '']);
            unset($btnlangs['' . $key . '']);
        }
        if (!$sessform)
        {
            foreach ($origlhelpdesk as $key => $value)
            {
                $lhelpdesknotice = '';
                $lvalue = set_value('f[lhelpdesk][' . $key . ']', $value);
                if ($lvalue != $value)
                {
                    $lhelpdesknotice = 'notice';
                }
                $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($lhelpdesklangs['' . $key . ''], 'f[lhelpdesk][' . $key . ']', 'lhelpdesk', $key, $lvalue, $lhelpdesknotice) . '</div>';
                unset($lhelpdesklangs['' . $key . '']);
                unset($btnlangs['' . $key . '']);
            }
        }
        $tmprows .= '<div class="small-12 columns">' . $this->_generateLangAddButton('lhelpdeskadd', 'lhelpdesklangcode', $btnlangs, 'addlhelpdesk', '' . lang('rr_helpdeskurl') . '') . '</div>';
        $result[] = $tmprows;
        $result[] = '';

        if ($isAdmin && !empty($entid))
        {
            /**
             * end organizatiourl/helpdesk
             */
            $result[] = '';

            $result[] = $this->_generateLabelInput(lang('rr_regauthority'), 'f[regauthority]', $f_regauthority, $regauthority_notice, FALSE);

            $result[] = $this->_generateLabelInput(lang('rr_regdate'), 'f[registrationdate]', $f_regdate, 'registrationdate' . $regdate_notice . '', FALSE);
            $result[] = '';
        }

        /**
         * new regpolicy start
         */
        $result[] = '';
        $result[] = '<div class="langgroup">' . lang('rr_regpolicy') . ' ' . showBubbleHelp('' . lang('entregpolicy_expl') . '') . '</div>';
        if (!empty($entid))
        {
            $entRegPolicies = $this->em->getRepository("models\Coc")->findBy(array('type' => 'regpol'));
        } else
        {
            $entRegPolicies = $this->em->getRepository("models\Coc")->findBy(array('type' => 'regpol', 'is_enabled' => TRUE));
        }
        $entRegPoliciesArray = array();
        foreach ($entRegPolicies as $v)
        {
            $entRegPoliciesArray['' . $v->getId() . ''] = array('name' => $v->getName(), 'enabled' => $v->getAvailable(), 'lang' => $v->getLang(), 'link' => $v->getUrl(), 'desc' => $v->getDescription());
        }
        $isAdmin = $this->ci->j_auth->isAdministrator();

        if (count($entRegPolicies) == 0)
        {
            $result[] = '<div class="small-12 columns"><div data-alert class="alert-box warning">' . lang('noregpolsavalabletoapply') . '</div></div>';
        } elseif (!$isAdmin && !empty($entid))
        {
            $result[] = '<div class="small-12 columns"><div data-alert class="alert-box info">' . lang('approval_required') . '</div></div>';
        }
        $assignedRegPolicies = $ent->getCoc();
        $assignedRegPoliciesArray = array();
        if ($sessform && isset($ses['regpol']))
        {
            foreach ($ses['regpol'] as $k => $v)
            {
                if (isset($entRegPoliciesArray['' . $v . '']))
                {
                    $entRegPoliciesArray['' . $v . '']['sel'] = TRUE;
                }
            }
        } else
        {
            foreach ($assignedRegPolicies as $k => $v)
            {
                $vtype = $v->getType();
                if (strcmp($vtype, 'regpol') == 0)
                {
                    $entRegPoliciesArray['' . $v->getId() . '']['sel'] = true;
                }
            }
        }
        $r = '<div class="large-8 small-offset-0 large-offset-3 end columns"><div class="checkboxlist">';
        foreach ($entRegPoliciesArray as $k => $v)
        {
            if (isset($v['sel']))
            {
                $is = true;
            } else
            {
                $is = false;
            }
            if (empty($v['enabled']))
            {
                $lbl = '<span class="label alert">' . lang('rr_disabled') . '</span>';
            } else
            {
                $lbl = '';
            }
            $r .= '<div>' . form_checkbox(array('name' => 'f[regpol][]', 'id' => 'f[regpol][]', 'value' => $k, 'checked' => $is)) . '<span class="label secondary"><b>' . $v['lang'] . '</b></span>  <span data-tooltip class="has-tip" title="' . $v['desc'] . '">' . $v['link'] . '</span> ' . $lbl . '</div>';
        }
        $r .= '</div></div>';

        $result[] = $r;
        $result[] = '';

        /**
         * new regpolicy end
         */
        return $result;
    }

    public function NgenerateRegPolicies(models\Provider $ent, $ses = null)
    {
        /**
         * new regpolicy start
         */
        $result[] = '';
        $result[] = '<div class="langgroup">' . lang('rr_regpolicy') . ' ' . showBubbleHelp('' . lang('entregpolicy_expl') . '') . '</div>';
        if (!empty($entid))
        {
            $entRegPolicies = $this->em->getRepository("models\Coc")->findBy(array('type' => 'regpol'));
        } else
        {
            $entRegPolicies = $this->em->getRepository("models\Coc")->findBy(array('type' => 'regpol', 'is_enabled' => TRUE));
        }
        $entRegPoliciesArray = array();
        foreach ($entRegPolicies as $v)
        {
            $entRegPoliciesArray['' . $v->getId() . ''] = array('name' => $v->getName(), 'enabled' => $v->getAvailable(), 'lang' => $v->getLang(), 'link' => $v->getUrl(), 'desc' => $v->getDescription());
        }
        $isAdmin = $this->ci->j_auth->isAdministrator();

        if (count($entRegPolicies) == 0)
        {
            $result[] = '<div class="small-12 columns"><div data-alert class="alert-box warning">' . lang('noregpolsavalabletoapply') . '</div></div>';
        } elseif (!$isAdmin && !empty($entid))
        {
            $result[] = '<div class="small-12 columns"><div data-alert class="alert-box info">' . lang('approval_required') . '</div></div>';
        }
        $assignedRegPolicies = $ent->getCoc();
        $assignedRegPoliciesArray = array();
        if ($sessform && isset($ses['regpol']))
        {
            foreach ($ses['regpol'] as $k => $v)
            {
                if (isset($entRegPoliciesArray['' . $v . '']))
                {
                    $entRegPoliciesArray['' . $v . '']['sel'] = TRUE;
                }
            }
        } else
        {
            foreach ($assignedRegPolicies as $k => $v)
            {
                $vtype = $v->getType();
                if (strcmp($vtype, 'regpol') == 0)
                {
                    $entRegPoliciesArray['' . $v->getId() . '']['sel'] = true;
                }
            }
        }
        $r = '<div class="small-12 large-8 small-offset-0 large-offset-3 end columns"><div class="checkboxlist">';
        foreach ($entRegPoliciesArray as $k => $v)
        {
            if (isset($v['sel']))
            {
                $is = true;
            } else
            {
                $is = false;
            }
            if (empty($v['enabled']))
            {
                $lbl = '<span class="label alert">' . lang('rr_disabled') . '</span>';
            } else
            {
                $lbl = '';
            }
            $r .= '<div>' . form_checkbox(array('name' => 'f[regpol][]', 'id' => 'f[regpol][]', 'value' => $k, 'checked' => $is)) . '<span class="label secondary"><b>' . $v['lang'] . '</b></span>  <span data-tooltip class="has-tip" title="' . $v['desc'] . '">' . $v['link'] . '</span> ' . $lbl . '</div>';
        }
        $r .= '</div></div>';

        $result[] = $r;
        $result[] = '';

        /**
         * new regpolicy end
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

        $r = '<fieldset><legend>' . lang('PrivacyStatementURL') . ' <i>' . lang('rr_default') . '</i>' . showBubbleHelp('' . lang('rhelp_privacydefault1') . '') . '</legend><div>';
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
        $r .= '</div></fieldset>';
        $result[] = $r;





        $langscodes = languagesCodes();
        $e = $ent->getExtendMetadata();
        $extend = array();
        foreach ($e as $v)
        {
            $extend['' . $v->getType() . '']['' . $v->getNamespace() . '']['' . $v->getElement() . ''][] = $v;
        }

        return $result;
    }

    public function NgenerateEntityCategoriesForm(models\Provider $ent, $ses = null)
    {
        $result = array();
        $sessform = FALSE;
        $enttype = $ent->getType();
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $entCategories = $this->em->getRepository("models\Coc")->findBy(array('type' => 'entcat'));
        $entCategoriesArray = array();
        foreach ($entCategories as $v)
        {
            $entCategoriesArray['' . $v->getId() . ''] = array('name' => $v->getName(), 'enabled' => $v->getAvailable());
        }
        $assignedEntCategories = $ent->getCoc();
        $assignedEntCategoriesArray = array();
        if ($sessform && isset($ses['coc']))
        {
            foreach ($ses['coc'] as $k => $v)
            {
                if (isset($entCategoriesArray['' . $v . '']))
                {
                    $entCategoriesArray['' . $v . '']['sel'] = TRUE;
                }
            }
        } else
        {
            foreach ($assignedEntCategories as $k => $v)
            {
                $vtype = $v->getType();
                if (strcmp($vtype, 'entcat') == 0)
                {
                    $entCategoriesArray['' . $v->getId() . '']['sel'] = true;
                }
            }
        }
        $isAdmin = $this->ci->j_auth->isAdministrator();
        $r = '';
        if (!$isAdmin)
        {
            $r .= '<div class="small-12 columns"><div data-alert class="alert-box info">' . lang('approval_required') . '</div></div>';
        }
        $r .= '<div class="large-8 small-offset-0 large-offset-3 end columns"><ul class="checkboxlist">';
        foreach ($entCategoriesArray as $k => $v)
        {
            if (isset($v['sel']))
            {
                $is = true;
            } else
            {
                $is = false;
            }
            if (empty($v['enabled']))
            {
                $lbl = '<span class="label alert">' . lang('rr_disabled') . '</span>';
            } else
            {
                $lbl = '<span class="label">' . lang('rr_enabled') . '</span>';
            }
            $r .= '<li>' . form_checkbox(array('name' => 'f[coc][]', 'id' => 'f[coc][]', 'value' => $k, 'checked' => $is)) . $v['name'] . ' ' . $lbl . '</li>';
        }
        $r .= '</ul></div>';
        $result[] = $r;
        return $result;
    }

    public function NgenerateContactsForm(models\Provider $ent, $ses = null)
    {
        $origcnts = $ent->getContacts();
        $sesscnts = array();
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
        $tmpid = 100;
        foreach ($origcnts as $cnt)
        {
            $tid = $cnt->getId();
            if (empty($tid))
            {
                $tid = 'x' . $tmpid++;
            }
            //$row = form_fieldset() . '<ol>';
            $row = '';
            $class_cnt1 = '';
            $class_cnt2 = '';
            $class_cnt3 = '';
            $class_cnt4 = '';
            if ($r)
            {
                if (isset($ses['contact']['' . $tid . '']))
                {
                    $t1 = set_value($ses['contact'][$tid]['type'], $cnt->getType());
                    $t2 = $ses['contact'][$tid]['fname'];
                    $t3 = $ses['contact'][$tid]['sname'];
                    $t4 = $ses['contact'][$tid]['email'];
                } else
                {
                    continue;
                }
            } else
            {
                $t1 = $cnt->getType();
                $t2 = $cnt->getGivenname();
                $t3 = $cnt->getSurname();
                $t4 = $cnt->getEmail();
            }
            $t1 = set_value('f[contact][' . $tid . '][type]', $t1);
            $t2 = set_value('f[contact][' . $tid . '][fname]', $t2);
            $t3 = set_value('f[contact][' . $tid . '][sname]', $t3);
            $t4 = set_value('f[contact][' . $tid . '][email]', $t4);
            if ($r)
            {
                if (array_key_exists('type', $ses['contact'][$tid]))
                {
                    if ($t1 != $cnt->getType())
                    {
                        $class_cnt1 = 'notice';
                    }
                }
                if (array_key_exists('fname', $ses['contact'][$tid]))
                {
                    if ($t2 != $cnt->getGivenname())
                    {
                        $class_cnt2 = 'notice';
                    }
                }
                if (array_key_exists('sname', $ses['contact'][$tid]))
                {
                    if ($t3 != $cnt->getSurname())
                    {
                        $class_cnt3 = 'notice';
                    }
                }
                if (array_key_exists('email', $ses['contact'][$tid]))
                {
                    if ($t4 != $cnt->getEmail())
                    {
                        $class_cnt4 = 'notice';
                    }
                }
            }


            $row .='<div class="small-12 columns">';
            $row .= $this->_generateLabelSelect(lang('rr_contacttype'), 'f[contact][' . $tid . '][type]', $formtypes, $t1, $class_cnt1, FALSE);
            $row .= '</div>';

            $row .= '<div class="small-12 columns">';
            $row .= $this->_generateLabelInput(lang('rr_contactfirstname'), 'f[contact][' . $tid . '][fname]', $t2, $class_cnt2, FALSE);
            $row .= '</div>';


            $row .= '<div class="small-12 columns">';
            $row .= $this->_generateLabelInput(lang('rr_contactlastname'), 'f[contact][' . $tid . '][sname]', $t3, $class_cnt3, FALSE);
            $row .= '</div>';


            $row .= '<div class="small-12 columns">';
            $row .= $this->_generateLabelInput(lang('rr_contactemail'), 'f[contact][' . $tid . '][email]', $t4, $class_cnt4, FALSE);
            $row .= '</div>';
            $row .= '<div class="small-12 columns"><div class="small-9 large-10 columns"><button type="button" class="contactrm button tiny alert inline right" name="contact" value="' . $cnt->getId() . '">' . lang('btn_removecontact') . ' </button></div><div class="small-3 large-2 columns"></div></div>';
            //  $row .= '</ol>' . form_fieldset_close();
            $result[] = '';
            $result[] = form_fieldset(lang('rr_contact')) . '<div>' . $row . '</div>' . form_fieldset_close();
            $result[] = '';
            if ($r)
            {
                unset($ses['contact']['' . $tid . '']);
            }
        }
        if ($r && count($ses['contact'] > 0))
        {
            foreach ($ses['contact'] as $k => $v)
            {
                $n = '<fieldset class="newcontact"><legend>' . lang('rr_contact') . '</legend><div>';

                $n .= '<div class="small-12 columns">';
                $n .= $this->_generateLabelSelect(lang('rr_contacttype'), 'f[contact][' . $k . '][type]', $formtypes, set_value('f[contact][' . $k . '][type]', $v['type']), '', FALSE);
                $n .= '</div>';

                $n .= '<div class="small-12 columns">';
                $n .= $this->_generateLabelInput(lang('rr_contactfirstname'), 'f[contact][' . $k . '][fname]', set_value('f[contact][' . $k . '][fname]', $v['fname']), '', FALSE);
                $n .= '</div>';

                $n .= '<div class="small-12 columns">';
                $n .= $this->_generateLabelInput(lang('rr_contactlastname'), 'f[contact][' . $k . '][sname]', set_value('f[contact][' . $k . '][sname]', $v['sname']), '', FALSE);
                $n .= '</div>';

                $n .= '<div class="small-12 columns">';
                $n .= $this->_generateLabelInput(lang('rr_contactemail'), 'f[contact][' . $k . '][email]', set_value('f[contact][' . $k . '][email]', $v['email']), '', FALSE);
                $n .= '</div>';
                $n .= '<div class="rmelbtn fromprevtoright small-12 columns"><div class="small-9 large-10 columns"><button type="button" class="btn contactrm button alert tiny inline right" name="contact" value="' . $k . '">' . lang('btn_removecontact') . '</button></div><div class="small-3 large-2 columns"></div></div>';
                $n .= '</div>' . form_fieldset_close();
                $result[] = '';
                $result[] = $n;
                $result[] = '';
            }
        }
        $n = '<button class="editbutton addicon smallerbtn button tiny" type="button" id="ncontactbtn" value="' . lang('btn_removecontact') . '|' . lang('rr_contacttype') . '|' . lang('rr_contactfirstname') . '|' . lang('rr_contactlastname') . '|' . lang('rr_contactemail') . '|' . lang('rr_contact') . '">' . lang('rr_addnewcoontact') . '</button>';
        $result[] = '';
        $result[] = $n;
        $result[] = '';

        return $result;
    }

    public function NgenerateCertificatesForm(models\Provider $ent, $ses = null)
    {
        $result = array();
        $sessform = FALSE;
        $enttype = $ent->getType();
        $c = $ent->getCertificates();
        $origcerts = array();
        foreach ($c as $v)
        {
            $origcerts['' . $v->getType() . '']['' . $v->getId() . ''] = $v;
        }
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }

        if (strcmp($enttype, 'SP') != 0)
        {
            $Part = '<fieldset><legend>' . lang('idpcerts') . ' <small><i>IDPSSODesciptor</i></small></legend><div>';
            $idpssocerts = array();
            // start CERTS IDPSSODescriptor
            if ($sessform)
            {
                if (isset($ses['crt']['idpsso']))
                {
                    foreach ($ses['crt']['idpsso'] as $key => $value)
                    {
                        $idpssocerts[] = $this->_genCertFieldFromSession($certObj = null, $key, $value, "f[crt][idpsso]", 'idpsso', TRUE);
                    }
                }
            } else
            {
                if (isset($origcerts['idpsso']))
                {
                    foreach ($origcerts['idpsso'] as $k => $v)
                    {
                        $idpssocerts[] = $this->_genCertFieldFromObj($v, "f[crt][idpsso]", TRUE);
                    }
                }
            }
            $Part .= implode('', $idpssocerts);
            $newelement = '<div><button class="editbutton addicon small" type="button" id="nidpssocert">' . lang('addnewcert') . ' ' . lang('for') . ' IDPSSODescriptor</button></div>';
            $Part .= $newelement . '</div></fieldset>';
            $result[] = $Part;

            // end CERTS IDPSSODescriptor
            $Part = '<fieldset><legend>' . lang('idpcerts') . ' <small><i>AttributeAuthorityDesciptor</i></small></legend><div>';
            $aacerts = array();
            // start CERTS AttributeAuthorityDescriptor
            if ($sessform)
            {
                if (isset($ses['crt']['aa']))
                {
                    foreach ($ses['crt']['aa'] as $key => $value)
                    {
                        $aacerts[] = $this->_genCertFieldFromSession($certObj = null, $key, $value, "f[crt][aa]", 'aa', TRUE);
                    }
                }
            } else
            {
                if (isset($origcerts['aa']))
                {
                    foreach ($origcerts['aa'] as $k => $v)
                    {
                        $aacerts[] = $this->_genCertFieldFromObj($v, "f[crt][aa]", TRUE);
                    }
                }
            }
            $Part .= implode('', $aacerts);
            $newelement = '<div><button class="editbutton addicon small" type="button" id="naacert">' . lang('addnewcert') . ' ' . lang('for') . ' AttributeAuthorityDescriptor</button></div>';
            $Part .= $newelement . '</div></fieldset>';
            $result[] = $Part;
            $Part = '';

            // end CERTS AttributeAuthorityDescriptor
        }
        if (strcmp($enttype, 'IDP') != 0)
        {
            $Part = '<fieldset><legend>' . lang('rr_certificates') . ' <small><i>' . lang('serviceprovider') . '</i></small></legend><div>';
            $spssocerts = array();
            if ($sessform)
            {
                if (isset($ses['crt']['spsso']))
                {
                    foreach ($ses['crt']['spsso'] as $key => $value)
                    {
                        $spssocerts[] = $this->_genCertFieldFromSession($certObj = null, $key, $value, "f[crt][spsso]", 'spsso', TRUE);
                    }
                }
            } else
            {
                if (isset($origcerts['spsso']))
                {
                    foreach ($origcerts['spsso'] as $k => $v)
                    {
                        $spssocerts[] = $this->_genCertFieldFromObj($v, "f[crt][spsso]", TRUE);
                    }
                }
            }
            $Part .= implode('', $spssocerts);
            $newelement = '<div><button class="editbutton addicon  button small" type="button" id="nspssocert">' . lang('addnewcert') . '</button></div>';
            $Part .= $newelement . '</div></fieldset>';
            $result[] = $Part;
        }


        return $result;
    }

    private function _generateLangAddButton($spanclass, $dropname, $langs, $buttonname, $buttonvalue)
    {
        $r = '<span class="' . $spanclass . '"><div class="small-6 medium-3 large-3 columns">' . form_dropdown('' . $dropname . '', $langs, $this->defaultlangselect) . '</div><div class="small-6 large-4 end columns"><button type="button" id="' . $buttonname . '" name="' . $buttonname . '" value="' . $buttonvalue . '" class="editbutton addicon smallerbtn button inline left tiny">' . lang('btnaddinlang') . '</button></div></span>';
        return $r;
    }

    private function _generateLabelSelect($label, $name, $dropdowns, $value, $classes, $showremovebutton = false)
    {
        if ($showremovebutton)
        {
            $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-6 large-7 columns inline ">' .
                    form_dropdown($name, $dropdowns, $value)
                    . '</div>';
            $result .='<div class="small-3 large-2 columns"><button type="button" class="inline left button tiny" name="rmfield" value="' . $name . '">' . lang('rr_remove') . '</button></div>';
        } else
        {
            $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-8 large-7 columns inline ">' .
                    form_dropdown($name, $dropdowns, $value)
                    . '</div>';
            $result .='<div class="small-1 large-2 columns"></div>';
        }

        return $result;
    }

    private function _generateLabelInput($label, $name, $value, $classes, $showremovebutton = false, $readonly = NULL)
    {
        $arg = array(
            'name' => '' . $name . '',
            'id' => '' . $name . '',
            'value' => '' . $value . '',
            'class' => $classes . ' right inline'
        );
        if (!empty($readonly) && is_array($readonly))
        {
            foreach ($readonly as $k => $v)
            {
                $arg['' . $k . ''] = $v;
            }
        }
        if ($showremovebutton)
        {
            $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-6 large-7 columns">' . form_input(
                            $arg
                    ) . '</div>';
            $result .='<div class="small-3 large-2 columns"><button type="button" class="inline left button tiny alert rmfield" name="rmfield" value="' . $name . '">' . lang('rr_remove') . '</button></div>';
        } else
        {
            $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-8 large-7 columns">' . form_input(
                            $arg
                    ) . '</div>';
            $result .='<div class="small-1 large-2 columns"></div>';
        }

        return $result;
    }

    private function _generateLangInputWithRemove($label, $name, $buttonname, $buttonvalue, $value = '', $classes = '')
    {
        $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-6 large-7 columns">' . form_input(
                        array(
                            'name' => '' . $name . '',
                            'id' => '' . $name . '',
                            'value' => '' . $value . '',
                            'class' => $classes . ' right inline'
                        )
                ) . '</div><div class="small-3 large-2 columns"><button type="button" class="btn langinputrm inline left button tiny" name="lname" value="' . $buttonvalue . '">' . lang('rr_remove') . '</button></div>';

        return $result;
    }

    private function _generateLangTextareaWithRemove($label, $name, $buttonname, $buttonvalue, $value = '', $classes = '', $hideremove = FALSE)
    {
        $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-6 large-7 columns"><textarea name="' . $name . '" id="' . $name . '" cols="40" rows="5" class="' . $classes . ' right inline">' . $value . '</textarea>
                        </div><div class="small-3 large-2 columns">';
        if (empty($hideremove))
        {
            $result .='<button type="button" class="btn langinputrm inline left button tiny" name="lname" value="' . $buttonvalue . '">' . lang('rr_remove') . '</button>';
        }
        $result .='</div>';

        return $result;
    }

    private function _genCertFieldFromSession($certObj = null, $idCert, $sessionCert, $sessionNamePart, $type, $showremove = false)
    {
        $name = $sessionNamePart;
        $certuse = $sessionCert['usage'];
        if (empty($certuse))
        {
            $certuse = 'both';
        }
        $crtid = $idCert;
        $readonly = false;
        if (is_numeric($crtid))
        {
            $readonly = true;
        }
        $certdata = set_value('' . $name . '[' . $crtid . '][certdata]', getPEM($sessionCert['certdata']));
        if (!empty($certdata))
        {
            $keysize = getKeysize($certdata);
        }
        if (empty($keysize))
        {
            $keysize = lang('unknown');
        }
        $row = '<div class="certgroup small-12 columns">';

        $row .= '<div class="small-12 columns hidden">';
        $row .= $this->_generateLabelSelect(lang('rr_certificatetype'), '' . $name . '[' . $crtid . '][type]', array('x509' => 'x509'), set_value($sessionCert['type']), '', FALSE);
        $row .= '</div>';


        $row .= '<div class="small-12 columns">';
        $row .= $this->_generateLabelSelect(lang('rr_certificateuse'), '' . $name . '[' . $crtid . '][usage]', array('signing' => '' . lang('rr_certsigning') . '', 'encryption' => '' . lang('rr_certencryption') . '', 'both' => '' . lang('rr_certsignandencr') . ''), $certuse, '', FALSE);
        $row .= '</div>';



        if (empty($sessionCert['keyname']))
        {
            $row .= '<div class="small-12 columns hidden">';
        } else
        {
            $row .= '<div class="small-12 columns">';
        }
        $row .= $this->_generateLabelInput(lang('rr_keyname') . ' ' . showBubbleHelp(lang('rhelp_multikeynames')), '' . $name . '[' . $crtid . '][keyname]', $sessionCert['keyname'], '', FALSE, NULL);
        $row .= '</div>';
        $row .= '<div class="small-12 columns">';
        $row .= $this->_generateLabelInput(lang('rr_computedkeysize'), 'keysize', $keysize, '', FALSE, array('disabled' => 'disabled'));
        $row .= '</div>';


        $row .= '<div class="small-12 columns"><div class="small-3 columns"><label for="' . $name . '[' . $crtid . '][certdata]" class="right inline">' . lang('rr_certificate') . '</label></div>';
        $row .= '<div class="small-6 large-7 columns">';
        $textarea = array(
            'name' => '' . $name . '[' . $crtid . '][certdata]',
            'id' => '' . $name . '[' . $crtid . '][certdata]',
            'cols' => 55,
            'rows' => 20,
            'class' => 'certdata ',
            'value' => '' . $certdata . '',
        );
        if ($readonly)
        {
            $textarea['readonly'] = 'true';
        }

        $row .= form_textarea($textarea) . '</div><div class="small-3 large-2 columns"></div></div>';
        if ($showremove)
        {
            $row .= '<div class="small-12 columns"><div class="small-3 columns">&nbsp</div><div class="small-6 large-7 columns"><button type="button" class="certificaterm button alert tiny right" name="certificate" value="' . $crtid . '">' . lang('btn_removecert') . '</button></div><div class="small-3 large-2 columns"></div></div>';
        }


        $row .='</div>';
        return $row;
    }

    private function _genCertFieldFromObj($cert, $name, $showremove = FALSE)
    {
        $certuse = $cert->getCertUse();
        if (empty($certuse))
        {
            $certuse = 'both';
        }
        $certdata = getPEM($cert->getCertData());
        if (!empty($certdata))
        {
            $keysize = getKeysize($certdata);
        }
        if (empty($keysize))
        {
            $keysize = lang('unknown');
        }

        $crtid = $cert->getId();
        if (empty($crtid))
        {
            $crtid = 'x' . rand();
        }
        $readonly = false;
        if (is_numeric($crtid))
        {
            $readonly = true;
        }
        $row = '<div class="certgroup small-12 columns">';

        $row .= '<div class="small-12 columns hidden">';
        $row .= $this->_generateLabelSelect(lang('rr_certificatetype'), '' . $name . '[' . $crtid . '][type]', array('x509' => 'x509'), set_value($cert->getType()), '', FALSE);
        $row .= '</div>';


        $row .= '<div class="small-12 columns">';
        $row .= $this->_generateLabelSelect(lang('rr_certificateuse'), '' . $name . '[' . $crtid . '][usage]', array('signing' => '' . lang('rr_certsigning') . '', 'encryption' => '' . lang('rr_certencryption') . '', 'both' => '' . lang('rr_certsignandencr') . ''), $certuse, '', FALSE);
        $row .= '</div>';

        $tmpkeyname = $cert->getKeyname();

        if (empty($tmpkeyname))
        {
            $row .= '<div class="small-12 columns hidden">';
        } else
        {
            $row .= '<div class="small-12 columns">';
        }
        $row .= $this->_generateLabelInput(lang('rr_keyname') . ' ' . showBubbleHelp(lang('rhelp_multikeynames')), '' . $name . '[' . $crtid . '][keyname]', $cert->getKeyname(), '', FALSE, NULL);
        $row .= '</div>';


        $row .= '<div class="small-12 columns">';
        $row .= $this->_generateLabelInput(lang('rr_computedkeysize'), 'keysize', $keysize, '', FALSE, array('disabled' => 'disabled'));
        $row .= '</div>';

//$name . '[' . $crtid . '][certdata]
        $row .= '<div class="small-12 columns"><div class="small-3 columns"><label for="' . $name . '[' . $crtid . '][certdata]" class="right inline">' . lang('rr_certificate') . '</label></div>';
        $row .= '<div class="small-6 large-7 columns">';
        $textarea = array(
            'name' => '' . $name . '[' . $crtid . '][certdata]',
            'id' => '' . $name . '[' . $crtid . '][certdata]',
            'cols' => 55,
            'rows' => 20,
            'class' => 'certdata ',
            'value' => '' . $certdata . '',
        );
        if ($readonly)
        {
            $textarea['readonly'] = 'true';
        }

        $row .= form_textarea($textarea) . '</div><div class="small-3 large-2 columns"></div></div>';
        if ($showremove)
        {
            $row .= '<div class="small-12 columns"><div class="small-3 columns">&nbsp</div><div class="small-6 large-7 columns"><button type="button" class="certificaterm button alert tiny right" name="certificate" value="' . $crtid . '">' . lang('btn_removecert') . '</button></div><div class="small-3 large-2 columns"></div></div>';
        }
        $row .='</div>';

        return $row;
    }

//    public function NgenerateProtocols($ent, $entsession)

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
            } else
            {
                $svalue = $static_metadata;
            }

            if (array_key_exists('usestatic', $entsession) && $entsession['usestatic'] === 'accept')
            {
                $susestatic = TRUE;
            } else
            {
                $susestatic = $is_static;
            }
        } else
        {
            $susestatic = $is_static;

            $svalue = $static_metadata;
        }
        $value = set_value('f[static]', $svalue);
        $result = array();

        $result[] = '<div class="small-3 columns"><label for="f[usestatic]" class="right">' . lang('rr_usestaticmetadata') . '</label></div><div class="small-6 large-7 columns">' . form_checkbox(array(
                    'name' => 'f[usestatic]',
                    'id' => 'f[usestatic]',
                    'value' => 'accept',
                    'checked' => set_value('f[usestatic]', $susestatic)
                )) . '</div><div class="small-3 large-2 columns"></div>';

        $result[] = '<div class="small-3 columns"><label for="f[static]" class="right">' . lang('rr_staticmetadataxml') . '</label></div><div class="small-6 large-7 columns">' . form_textarea(array(
                    'name' => 'f[static]',
                    'id' => 'f[static]',
                    'cols' => 65,
                    'rows' => 20,
                    'class' => 'metadata',
                    'value' => $value
                )) . '</div><div class="small-3 large-2 columns"></div>';


        return $result;
    }

    public function NgenerateSAMLTab(models\Provider $ent, $ses = null)
    {
        $entid = $ent->getId();
        $enttype = $ent->getType();
        $idppart = FALSE;
        $sppart = FALSE;
        if (strcasecmp($enttype, 'BOTH') == 0)
        {
            $idppart = TRUE;
            $sppart = TRUE;
        } elseif (strcasecmp($enttype, 'IDP') == 0)
        {
            $idppart = TRUE;
        } else
        {
            $sppart = TRUE;
        }
        $sessform = FALSE;
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $allowednameids = getAllowedNameId();
        $class_ent = '';

        $t1 = set_value('f[entityid]', $ent->getEntityId());
        if ($sessform)
        {
            if (array_key_exists('entityid', $ses) && (strcmp($ses['entityid'], $t1) != 0))
            {
                $class_ent = 'notice';
                $t1 = $ses['entityid'];
            }
        }
        $result = array();
        $result[] = '';
        $addargs = array();
        if (in_array('entityid', $this->disallowedparts) && !empty($entid))
        {
            $addargs = array('readonly' => 'readonly');
        } elseif (!empty($entid))
        {
            $class_ent .=' alertonchange ';
        }
        $result[] = $this->_generateLabelInput(lang('rr_entityid'), 'f[entityid]', $t1, $class_ent, FALSE, $addargs);

        $result[] = '';

        $ssotmpl = array();
        $acsbindprotocols = array();
        $ssobindprotocols = getBindSingleSignOn();

        $tmpacsprotocols = getBindACS();
        foreach ($tmpacsprotocols as $v)
        {
            $acsbindprotocols['' . $v . ''] = $v;
        }

        foreach ($ssobindprotocols as $v)
        {
            $ssotmpl['' . $v . ''] = $v;
        }

        $slotmpl = getBindSingleLogout();


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
        $sso = array();


        if ($idppart)
        {
            if ($sppart)
            {
                $result[] = '<div class="section">' . lang('identityprovider') . '</div>';
            }
            /**
             * generate SSO part
             */
            $SSOPart = '';

            if (array_key_exists('SingleSignOnService', $g))
            {

                $tmpid = 100;
                foreach ($g['SingleSignOnService'] as $k1 => $v1)
                {
                    $tid = $v1->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    if ($sessform && isset($ses['srv']['SingleSignOnService']['' . $tid . '']['url']))
                    {
                        $t1 = $ses['srv']['SingleSignOnService']['' . $tid . '']['url'];
                    } else
                    {
                        $t1 = $v1->getUrl();
                    }
                    $t1 = set_value('f[srv][SingleSignOnService][' . $tid . '][url]', $t1);
                    $rnotice = '';
                    if ($t1 != $v1->getUrl())
                    {
                        $rnotice = 'notice';
                    }
                    $row = '<div class="small-12 columns">';
                    $row .= form_input(array(
                        'name' => 'f[srv][SingleSignOnService][' . $tid . '][bind]',
                        'id' => 'f[srv][SingleSignOnService][' . $tid . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][SingleSignOnService][' . $tid . '][bind]', $v1->getBindingName()),
                    ));
                    $row .= $this->_generateLabelInput($v1->getBindingName(), 'f[srv][SingleSignOnService][' . $tid . '][url]', $t1, $rnotice, FALSE, NULL);
                    $row .= '</div>';
                    $sso[] = $row;
                    unset($ssotmpl[$v1->getBindingName()]);
                }
            } elseif (isset($ses['srv']['SingleSignOnService']))
            {
                foreach ($ses['srv']['SingleSignOnService'] as $k => $v)
                {
                    $row = '<div class="small-12 columns">';
                    $row .= form_input(array(
                        'name' => 'f[srv][SingleSignOnService][' . $k . '][bind]',
                        'id' => 'f[srv][SingleSignOnService][' . $k . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][SingleSignOnService][' . $k . '][bind]', $v['bind']),
                    ));
                    $row .= $this->_generateLabelInput($v['bind'], 'f[srv][SingleSignOnService][' . $k . '][url]', $v['url'], '', FALSE, NULL);
                    $row .= '</div>';
                    $sso[] = $row;
                    unset($ssotmpl[$v['bind']]);
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
                $r = '<div class="small-12 columns">';
                $r .= form_input(array(
                    'name' => 'f[srv][SingleSignOnService][n' . $i . '][bind]',
                    'id' => 'f[srv][SingleSignOnService][n' . $i . '][bind]',
                    'type' => 'hidden',
                    'value' => $vm,
                ));
                $r .= $this->_generateLabelInput($km, 'f[srv][SingleSignOnService][n' . $i . '][url]', $value, $rnotice, FALSE, NULL);

                $r .= '</div>';
                $sso[] = $r;
                ++$i;
            }
            // $result = array_merge($result,$sso);
            $SSOPart .= implode('', $sso);
            $result[] = '';
            //$result[] = '<div class="langgroup">' . lang('rr_srvssoends') . '</div>';
            $result[] = '<fieldset><legend>' . lang('rr_srvssoends') . '</legend>' . $SSOPart . '</fieldset>';
            //  $result[] = $SSOPart;
            $result[] = '';
            // $slotmpl
            /**
             * IDP SingleLogoutService
             */
            $IDPSLOPart = '';
            $slotmpl = getBindSingleLogout();
            $idpslo = array();
            if (array_key_exists('IDPSingleLogoutService', $g))
            {
                $tmpid = 100;
                foreach ($g['IDPSingleLogoutService'] as $k2 => $v2)
                {
                    $tid = $v2->getId();
                    if (empty($tid))
                    {

                        $tid = 'x' . $tmpid++;
                    }
                    if ($sessform && isset($ses['srv']['IDPSingleLogoutService']['' . $tid . '']['url']))
                    {
                        $t1 = $ses['srv']['IDPSingleLogoutService']['' . $tid . '']['url'];
                    } else
                    {
                        $t1 = $v2->getUrl();
                    }
                    $t1 = set_value('f[srv][IDPSingleLogoutService][' . $tid . '][url]', $t1);
                    $row = '<div class="small-12 columns">';
                    $row .= form_input(array(
                        'name' => 'f[srv][IDPSingleLogoutService][' . $tid . '][bind]',
                        'id' => 'f[srv][IDPSingleLogoutService][' . $tid . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][IDPSingleLogoutService][' . $tid . '][bind]', $v2->getBindingName()),
                    ));
                    $row .= $this->_generateLabelInput($v2->getBindingName(), 'f[srv][IDPSingleLogoutService][' . $tid . '][url]', $t1, '', FALSE, NULL);
                    $row .= '</div>';
                    unset($slotmpl[array_search($v2->getBindingName(), $slotmpl)]);
                    $idpslo[] = $row;
                }
            } elseif (isset($ses['srv']['IDPSingleLogoutService']))
            {

                foreach ($ses['srv']['IDPSingleLogoutService'] as $k => $v)
                {
                    log_message('debug', 'GKS IDPSingleLogoutService: ' . $k . ' : ' . $v['bind']);
                    $row = '<div class="small-12 columns">';
                    $row .= form_input(array(
                        'name' => 'f[srv][IDPSingleLogoutService][' . $k . '][bind]',
                        'id' => 'f[srv][IDPSingleLogoutService][' . $k . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][IDPSingleLogoutService][' . $k . '][bind]', $v['bind']),
                    ));
                    $row .= $this->_generateLabelInput($v['bind'], 'f[srv][IDPSingleLogoutService][' . $k . '][url]', $v['url'], '', FALSE, NULL);
                    $row .= '</div>';
                    $idpslo[] = $row;
                    unset($slotmpl[array_search($v['bind'], $slotmpl)]);
                }
            }

            $ni = 0;
            foreach ($slotmpl as $k3 => $v3)
            {

                $row = '<div class="small-12 columns">';
                $row .= form_input(array(
                    'name' => 'f[srv][IDPSingleLogoutService][n' . $ni . '][bind]',
                    'id' => 'f[srv][IDPSingleLogoutService][n' . $ni . '][bind]',
                    'type' => 'hidden',
                    'value' => $v3,));
                $row .= $this->_generateLabelInput($v3, 'f[srv][IDPSingleLogoutService][n' . $ni . '][url]', set_value('f[srv][IDPSingleLogoutService][n' . $ni . '][url]'), '', FALSE, NULL);
                $row .= '</div>';
                $idpslo[] = $row;
                ++$ni;
            }
            $IDPSLOPart .= implode('', $idpslo);
            $result[] = '';
            // $result[] = '<div class="langgroup">' . lang('rr_srvsloends') . '</div>';
            $result[] = '<fieldset><legend>' . lang('rr_srvsloends') . '</legend>' . $IDPSLOPart . '</fieldset>';
            $result[] = '';

            /**
             * generate IDP ArtifactResolutionService part
             */
            $ACSPart = '';
            $acs = array();

            if (!$sessform && isset($g['IDPArtifactResolutionService']) && is_array($g['IDPArtifactResolutionService']))
            {
                $tmpid = 100;
                foreach ($g['IDPArtifactResolutionService'] as $k3 => $v3)
                {
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    $tid = $v3->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    if ($sessform && isset($ses['srv']['IDPArtifactResolutionService']['' . $tid . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['IDPArtifactResolutionService']['' . $tid . '']))
                        {
                            $turl = $ses['srv']['IDPArtifactResolutionService']['' . $tid . '']['url'];
                        }
                        if (array_key_exists('order', $ses['srv']['IDPArtifactResolutionService']['' . $tid . '']))
                        {
                            $torder = $ses['srv']['IDPArtifactResolutionService']['' . $tid . '']['order'];
                        }
                        if (array_key_exists('bind', $ses['srv']['IDPArtifactResolutionService']['' . $tid . '']))
                        {
                            $tbind = $ses['srv']['IDPArtifactResolutionService']['' . $tid . '']['bind'];
                        }
                    }
                    $furl = set_value('f[srv][IDPArtifactResolutionService][' . $tid . '][url]', $turl);
                    $forder = set_value('f[srv][IDPArtifactResolutionService][' . $tid . '][order]', $torder);
                    $fbind = set_value('f[srv][IDPArtifactResolutionService][' . $tid . '][bind]', $tbind);
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

                    $r = '<div class="srvgroup">';
                    $r .= '<div class="small-12 columns">';
                    $r .= generateSelectInputFields(lang('rr_bindingname'), 'f[srv][IDPArtifactResolutionService][' . $tid . '][bind]', $artifacts_binding, $fbind, '', 'f[srv][IDPArtifactResolutionService][' . $tid . '][order]', $forder, NULL);
                    $r .= '</div>';
                    $r .= '<div class="small-12 columns">';
                    $r .= generateInputWithRemove(lang('rr_url'), 'f[srv][IDPArtifactResolutionService][' . $tid . '][url]', 'rmfield', '', $furl, 'acsurl ' . $urlnotice, 'rmfield');
                    $r .= '</div>';

                    $r .= '</div>';
                    $acs[] = $r;
                }
            } elseif ($sessform && isset($ses['srv']['IDPArtifactResolutionService']) && is_array($ses['srv']['IDPArtifactResolutionService']))
            {
                foreach ($ses['srv']['IDPArtifactResolutionService'] as $k4 => $v4)
                {


                    $r = '<div><div>';
                    $r .= '<div>' . form_label(lang('rr_bindingname'), 'f[srv][IDPArtifactResolutionService][' . $k4 . '][bind]');
                    $r .= form_dropdown('f[srv][IDPArtifactResolutionService][' . $k4 . '][bind]', $artifacts_binding, $v4['bind']) . '</div>';
                    $r .= '<div>' . form_label(lang('rr_url'), 'f[srv][IDPArtifactResolutionService][' . $k4 . '][url]');
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
                            )) . '</div>';

                    $r .='</div></div>';
                    $furl = set_value('f[srv][IDPArtifactResolutionService][' . $k4 . '][url]', $ses['srv']['IDPArtifactResolutionService']['' . $k4 . '']['url']);
                    $forder = set_value('f[srv][IDPArtifactResolutionService][' . $k4 . '][order]', $ses['srv']['IDPArtifactResolutionService']['' . $k4 . '']['order']);
                    $r = '<div class="srvgroup">';
                    $r .= '<div class="small-12 columns">';
                    $r .= generateSelectInputFields(lang('rr_bindingname'), 'f[srv][IDPArtifactResolutionService][' . $k4 . '][bind]', $artifacts_binding, '' . $v4['bind'] . '', '', 'f[srv][IDPArtifactResolutionService][' . $k4 . '][order]', $forder, NULL);
                    $r .= '</div>';
                    $r .= '<div class="small-12 columns">';
                    $r .= generateInputWithRemove(lang('rr_url'), 'f[srv][IDPArtifactResolutionService][' . $k4 . '][url]', 'rmfield', '', $furl, 'acsurl', 'rmfield');
                    $r .= '</div>';

                    $r .= '</div>';
                    $acs[] = $r;
                }
            }
            $ACSPart .= implode('', $acs);
            $newelement = '<div><button class="editbutton addicon small" type="button" id="nidpartifactbtn">' . lang('rr_addnewidpartifactres') . '</button></div>';
            $ACSPart .= $newelement . '';
            $result[] = '';
            //$result[] = '<div class="langgroup">' . lang('rr_srvartresends') . '</div>';
            $result[] = '<fieldset><legend>' . lang('rr_srvartresends') . '</legend>' . $ACSPart . '</fieldset>';
            $result[] = '';

            /**
             * start protocols enumeration
             */
            $allowedproto = getAllowedProtocolEnum();
            $allowedoptions = array();
            foreach ($allowedproto as $v)
            {
                $allowedoptions['' . $v . ''] = $v;
            }

            $idpssoprotocols = $ent->getProtocolSupport('idpsso');
            $selected_options = array();
            $idpssonotice = '';
            if ($sessform && isset($ses['prot']['idpsso']) && is_array($ses['prot']['idpsso']))
            {
                if (count(array_diff($ses['prot']['idpsso'], $idpssoprotocols)) > 0 || count(array_diff($idpssoprotocols, $ses['prot']['idpsso'])) > 0)
                {
                    $idpssonotice = 'notice';
                }
                foreach ($ses['prot']['idpsso'] as $v)
                {
                    $selected_options[$v] = $v;
                }
            } else
            {
                foreach ($idpssoprotocols as $p)
                {
                    $selected_options[$p] = $p;
                }
            }
            $r = '<div class="small-12 columns">';
            $r .= '<div class="small-12 medium-6 large-7  medium-push-3 large-push-3 columns inline end">';
            $r .= '<div class="checkboxlist">';
            foreach ($allowedoptions as $a)
            {
                $is = FALSE;
                if (in_array($a, $selected_options))
                {
                    $is = TRUE;
                }
                $r .= '<div>' . form_checkbox(array('name' => 'f[prot][idpsso][]', 'id' => 'f[prot][idpsso][]', 'value' => $a, 'checked' => $is)) . $a . '</div>';
            }
            $r .= '</div></div></div>';
            $result[] = '';
            $result[] = '<fieldset><legend>' . lang('rr_protenums') . '</legend>' . $r . '</fieldset>';
            $result[] = '';


            /**
             * end protocols enumeration
             */
            /**
             * start nameids
             */
            $idpssonameids = $ent->getNameIds('idpsso');
            $idpssonameidnotice = '';
            $supportednameids = array();
            $chp = array();
            if ($sessform && is_array($ses))
            {
                if (isset($ses['nameids']['idpsso']) && is_array($ses['nameids']['idpsso']))
                {
                    foreach ($ses['nameids']['idpsso'] as $pv)
                    {
                        $supportednameids[] = $pv;
                        $chp[] = array('name' => 'f[nameids][idpsso][]', 'id' => 'f[nameids][idpsso][]', 'value' => $pv, 'checked' => TRUE);
                    }
                }
            } else
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
            $r = '';
            $r .= '<div class="small-12 columns">';
            $r .= '<div class="small-3 large-3 columns">&nbsp;</div>';
            $r .= '<div class="small-8 large-7 columns nsortable end ' . $idpssonameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<div>' . form_checkbox($n) . $n['value'] . '</div>';
            }
            $r .= '</div>';
            $r .= '</div>';
            ///////////////
            $result[] = '';
            $result[] = '<fieldset><legend>' . lang('rr_supnameids') . '</legend>' . $r . '</fieldset>';
            $result[] = '';
            /**
             * end nameids
             */
            $scopes = array();
            $scopes = array('idpsso' => $ent->getScope('idpsso'), 'aa' => $ent->getScope('aa'));

            if ($sessform && isset($ses['scopes']['idpsso']))
            {
                $sesscope['idpsso'] = $ses['scopes']['idpsso'];
            } else
            {
                $sesscope['idpsso'] = implode(',', $scopes['idpsso']);
            }
            $scopeidpssonotice = '';
            $scopessovalue = set_value('f[scopes][idpsso]', $sesscope['idpsso']);
            if ($scopessovalue !== implode(',', $scopes['idpsso']))
            {
                $scopeidpssonotice = 'notice';
            }
            $result[] = '';
            if (in_array('scope', $this->disallowedparts))
            {
                $result[] = $this->_generateLabelInput(lang('rr_scope'), 'f[scopes][idpsso]', $scopessovalue, $scopeidpssonotice, FALSE, array('readonly' => 'readonly'));
            } else
            {
                $result[] = $this->_generateLabelInput(lang('rr_scope'), 'f[scopes][idpsso]', $scopessovalue, $scopeidpssonotice, FALSE, NULL);
            }
            $result[] = '';


            /**
             * end IDPArtifactResolutionService part
             */
            /**
             * start AttributeAuthorityDescriptor Locations
             */
            $result[] = '<div class="section">Attribute Authority</div>';
            $aabinds = getAllowedSOAPBindings();
            $aalo = array();
            if (!$sessform && array_key_exists('IDPAttributeService', $g))
            {
                $tmpid = 100;
                foreach ($g['IDPAttributeService'] as $k2 => $v2)
                {
                    $tid = $v2->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    $row = '<div class="srvgroup">';
                    $row .= '<div class="small-12 columns">';
                    $row .= form_input(array(
                        'name' => 'f[srv][IDPAttributeService][' . $tid . '][bind]',
                        'id' => 'f[srv][IDPAttributeService][' . $tid . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][IDPAttributeService][' . $tid . '][bind]', $v2->getBindingName()),
                    ));
                    $row .= $this->_generateLabelInput($v2->getBindingName(), 'f[srv][IDPAttributeService][' . $tid . '][url]', set_value('f[srv][IDPAttributeService][' . $tid . '][url]', $v2->getUrl()), '', TRUE, NULL);
                    $row .='</div>';
                    unset($aabinds[array_search($v2->getBindingName(), $aabinds)]);
                    $row .= '</div>';
                    $aalo[] = $row;
                }
            }
            $ni = 0;
            foreach ($aabinds as $k3 => $v3)
            {
                $row = '<div class="srvgroup"><div class="small-12 columns">';
                $row .= form_input(array(
                    'name' => 'f[srv][IDPAttributeService][n' . $ni . '][bind]',
                    'id' => 'f[srv][IDPAttributeService][n' . $ni . '][bind]',
                    'type' => 'hidden',
                    'value' => $v3,));
                $row .= $this->_generateLabelInput($v3, 'f[srv][IDPAttributeService][n' . $ni . '][url]', set_value('f[srv][IDPAttributeService][n' . $ni . '][url]'), TRUE, NULL);
                $row .= '</div></div>';
                $aalo[] = $row;
                ++$ni;
            }

            $result[] = '';
            //$result[] = '<div class="langgroup">' . lang('atributeauthoritydescriptor') . '</div>';
            $result[] = '<fieldset><legend>' . lang('atributeauthoritydescriptor') . '</legend>' . implode('', $aalo) . '</fieldset>';
            $result[] = '';
            /**
             * end AttributeAuthorityDescriptor Location
             */
            $aaprotocols = $ent->getProtocolSupport('aa');
            $selected_options = array();
            $aanotice = '';
            if ($sessform && isset($ses['prot']['aa']) && is_array($ses['prot']['aa']))
            {
                if (count(array_diff($ses['prot']['aa'], $aaprotocols)) > 0 || count(array_diff($aaprotocols, $ses['prot']['aa'])) > 0)
                {
                    $aanotice = 'notice';
                }
                foreach ($ses['prot']['aa'] as $v)
                {
                    $selected_options[$v] = $v;
                }
            } else
            {
                foreach ($aaprotocols as $p)
                {
                    $selected_options[$p] = $p;
                }
            }
            $r = '<div class="small-12 columns">';
            $r .= '<div class="' . $aanotice . ' small-12 medium-6 large-7  medium-push-3 large-push-3 columns inline end">';
            $r .= '<div class="checkboxlist">';
            foreach ($allowedoptions as $a)
            {
                $is = FALSE;
                if (in_array($a, $selected_options))
                {
                    $is = TRUE;
                }
                $r .= '<div>' . form_checkbox(array('name' => 'f[prot][aa][]', 'id' => 'f[prot][aa][]', 'value' => $a, 'checked' => $is)) . $a . '</div>';
            }
            $r .= '</div>';
            $r .= '</div>';
            $r .= '</div>';

            $r .= '';
            $result[] = '';
            $result[] = '<fieldset><legend>' . lang('rr_protenums') . '</legend>' . $r . '</fieldset>';
            $result[] = '';


            /**
             * start nameids for AttributeAuthorityDescriptor 
             */
            $idpaanameids = $ent->getNameIds('aa');
            $idpaanameidnotice = '';
            $supportednameids = array();
            $chp = array();
            if ($sessform && is_array($ses))
            {
                if (isset($ses['nameids']['idpaa']) && is_array($ses['nameids']['idpaa']))
                {
                    foreach ($ses['nameids']['idpaa'] as $pv)
                    {
                        $supportednameids[] = $pv;
                        $chp[] = array('name' => 'f[nameids][idpaa][]', 'id' => 'f[nameids][idpaa][]', 'value' => $pv, 'checked' => TRUE);
                    }
                }
            } else
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
            $r = '<div class="small-12 columns">';
            $r .= '<div class="small-3 large-3 columns">&nbsp;</div>';
            $r .= '<div class="small-8 large-7 columns nsortable end' . $idpaanameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<div>' . form_checkbox($n) . $n['value'] . '</div>';
            }
            $r .= '</div></div>';
            $result[] = '';
            $result[] = '<fieldset><legend>' . lang('rr_supnameids') . '</legend>' . $r . '</fieldset>';
            $result[] = '';
            /**
             * end nameids for IDPSSODescriptor
             */
            /**
             * Scopes
             */
            $result[] = '';
            if ($sessform && isset($ses['scopes']['aa']))
            {
                $sesscope['aa'] = $ses['scopes']['aa'];
            } else
            {
                $sesscope['aa'] = implode(',', $scopes['aa']);
            }
            $scopeaanotice = '';
            $scopeaavalue = set_value('f[scopes][aa]', $sesscope['aa']);
            if ($scopeaavalue !== implode(',', $scopes['aa']))
            {
                $scopeaanotice = 'notice';
            }
            if (in_array('scope', $this->disallowedparts))
            {
                $result[] = $this->_generateLabelInput(lang('rr_scope'), 'f[scopes][aa]', $scopeaavalue, $scopeaanotice, FALSE, array('readonly' => 'readonly'));
            } else
            {
                $result[] = $this->_generateLabelInput(lang('rr_scope'), 'f[scopes][aa]', $scopeaavalue, $scopeaanotice, FALSE, NULL);
            }
            $result[] = '';
        }
        if ($sppart)
        {
            if ($idppart)
            {
                $result[] = '<div class="section">' . lang('serviceprovider') . '</div>';
            }
            // return $result;

            /**
             * generate ACS part
             */
            $ACSPart = '<fieldset><legend>' . lang('assertionconsumerservice') . '</legend>';
            $acs = array();

            if (!$sessform && isset($g['AssertionConsumerService']) && is_array($g['AssertionConsumerService']))
            {
                $tmpid = 100;
                foreach ($g['AssertionConsumerService'] as $k3 => $v3)
                {
                    $tid = $v3->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    $furl = set_value('f[srv][AssertionConsumerService][' . $tid . '][url]', $turl);
                    $forder = set_value('f[srv][AssertionConsumerService][' . $tid . '][order]', $torder);
                    $fbind = set_value('f[srv][AssertionConsumerService][' . $tid . '][bind]', $tbind);
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
                    $r = '<div class="srvgroup">';
                    $ischecked = FALSE;
                    if ($sessform)
                    {
                        if (isset($ses['srv']['AssertionConsumerService']['' . $tid . '']['default']))
                        {
                            $ischecked = TRUE;
                        }
                    } else
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


                    $r .= '<div class="srvgroup"><div class="small-12 columns">' . generateSelectInputCheckboxFields(lang('rr_bindingname'), 'f[srv][AssertionConsumerService][' . $tid . '][bind]', $acsbindprotocols, $fbind, '', 'f[srv][AssertionConsumerService][' . $tid . '][order]', $forder, lang('rr_isdefault'), 'f[srv][AssertionConsumerService][' . $tid . '][default]', 1, $ischecked, NULL) . '</div>';

                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLabelInput(lang('rr_url'), 'f[srv][AssertionConsumerService][' . $tid . '][url]', $furl, 'acsurl ' . $urlnotice . '', TRUE, NULL);
                    $r .='</div></div>';

                    $acs[] = $r;
                }
            }
            if ($sessform && isset($ses['srv']['AssertionConsumerService']) && is_array($ses['srv']['AssertionConsumerService']))
            {
                foreach ($ses['srv']['AssertionConsumerService'] as $k4 => $v4)
                {
                    $ischecked = FALSE;
                    if ($sessform && isset($ses['srv']['AssertionConsumerService']['' . $k4 . '']['default']))
                    {
                        $ischecked = TRUE;
                    }


                    $r = '<div class="srvgroup">';



                    $r .='<div class="small-12 columns">';
                    $ordervalue = set_value('f[srv][AssertionConsumerService][' . $k4 . '][order]', $ses['srv']['AssertionConsumerService']['' . $k4 . '']['order']);
                    $r .= generateSelectInputCheckboxFields(lang('rr_bindingname'), 'f[srv][AssertionConsumerService][' . $k4 . '][bind]', $acsbindprotocols, '' . $v4['bind'] . '', '', 'f[srv][AssertionConsumerService][' . $k4 . '][order]', $ordervalue, lang('rr_isdefault'), 'f[srv][AssertionConsumerService][' . $k4 . '][default]', 1, $ischecked, NULL);
                    $r .='</div>';

                    $r .= '<div class="small-12 columns">';
                    $r .= generateInputWithRemove(lang('rr_url'), 'f[srv][AssertionConsumerService][' . $k4 . '][url]', 'rmfield', '', set_value('f[srv][AssertionConsumerService][' . $k4 . '][url]', $ses['srv']['AssertionConsumerService']['' . $k4 . '']['url']), 'acsurl notice ', 'rmfield');

                    $r .= '</div>'; //row
                    $r.='</div>'; // end srvgroup

                    $acs[] = $r;
                }
            }
            $ACSPart .= implode('', $acs);
            $newelement = '<div><button class="editbutton addicon smallerbtn button tiny" type="button" id="nacsbtn">' . lang('addnewacs') . '</button></div>';
            $ACSPart .= $newelement . '';
            $result[] = $ACSPart . '</fieldset>';
            /**
             * end ACS part
             */
            /**
             * generate ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>' . lang('artifactresolutionservice') . ' <small><i>SPSSODescriptor</i></small></legend>';
            $acs = array();

            if (!$sessform && isset($g['SPArtifactResolutionService']) && is_array($g['SPArtifactResolutionService']))
            {
                $tmpid = 100;
                foreach ($g['SPArtifactResolutionService'] as $k3 => $v3)
                {
                    $tid = $v3->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    if ($sessform && isset($ses['srv']['SPArtifactResolutionService']['' . $tid . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['SPArtifactResolutionService']['' . $tid . '']))
                        {
                            $turl = $ses['srv']['SPArtifactResolutionService']['' . $tid . '']['url'];
                        }
                        if (array_key_exists('order', $ses['srv']['SPArtifactResolutionService']['' . $tid . '']))
                        {
                            $torder = $ses['srv']['SPArtifactResolutionService']['' . $tid . '']['order'];
                        }
                        if (array_key_exists('bind', $ses['srv']['SPArtifactResolutionService']['' . $tid . '']))
                        {
                            $tbind = $ses['srv']['SPArtifactResolutionService']['' . $tid . '']['bind'];
                        }
                    }
                    $furl = set_value('f[srv][SPArtifactResolutionService][' . $tid . '][url]', $turl);
                    $forder = set_value('f[srv][SPArtifactResolutionService][' . $tid . '][order]', $torder);
                    $fbind = set_value('f[srv][SPArtifactResolutionService][' . $tid . '][bind]', $tbind);
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


                    $r = '<div class="srvgroup">';
                    ////
                    $r .= '<div class="small-12 columns">';
                    $r .= generateSelectInputFields(lang('rr_bindingname'), 'f[srv][SPArtifactResolutionService][' . $tid . '][bind]', $artifacts_binding, $fbind, '', 'f[srv][SPArtifactResolutionService][' . $tid . '][order]', $forder, NULL);
                    $r .= '</div>';
                    ////

                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLabelInput(lang('rr_url'), 'f[srv][SPArtifactResolutionService][' . $tid . '][url]', $furl, 'acsurl ' . $urlnotice . '', TRUE, FALSE);

                    $r .='</div></div>';
                    $acs[] = $r;
                    if ($sessform && isset($ses['srv']['SPArtifactResolutionService']['' . $tid . '']))
                    {
                        unset($ses['srv']['SPArtifactResolutionService']['' . $tid . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['SPArtifactResolutionService']) && is_array($ses['srv']['SPArtifactResolutionService']))
            {
                foreach ($ses['srv']['SPArtifactResolutionService'] as $k4 => $v4)
                {


                    $r = '<div class="srvgroup">';
                    /////
                    $r .= '<div class="small-12 columns">';
                    $forder = set_value('f[srv][SPArtifactResolutionService][' . $k4 . '][order]', $ses['srv']['SPArtifactResolutionService']['' . $k4 . '']['order']);
                    $r .= generateSelectInputFields(lang('rr_bindingname'), 'f[srv][SPArtifactResolutionService][' . $k4 . '][bind]', $artifacts_binding, '' . $v4['bind'] . '', '', 'f[srv][SPArtifactResolutionService][' . $k4 . '][order]', $forder, NULL);
                    $r .= '</div>';

                    $r .= '<div class="small-12 columns">';

                    $r .= generateInputWithRemove(lang('rr_url'), 'f[srv][SPArtifactResolutionService][' . $k4 . '][url]', 'rmfield', '', set_value('f[srv][SPArtifactResolutionService][' . $k4 . '][url]', $ses['srv']['SPArtifactResolutionService']['' . $k4 . '']['url']), 'acsurl notice', 'rmfield');
                    $r .= '</div>';
                    ////
                    $r .='</div>';
                    $acs[] = $r;
                }
            }
            $ACSPart .= implode('', $acs);
            $newelement = '<div><button class="editbutton addicon smallerbtn button tiny" type="button" id="nspartifactbtn">' . lang('addnewartresservice') . '</button></div>';
            $ACSPart .= $newelement . '</fieldset>';
            $result[] = $ACSPart;
            /**
             * end SPArtifactResolutionService part
             */
            /**
             * start SP SingleLogoutService
             */
            $SPSLOPart = '<fieldset><legend>' . lang('singlelogoutservice') . ' <small><i>' . lang('serviceprovider') . '</i></small></legend><div>';
            $spslotmpl = getBindSingleLogout();
            $spslo = array();
            if (array_key_exists('SPSingleLogoutService', $g))
            {
                $tmpid = 100;
                foreach ($g['SPSingleLogoutService'] as $k2 => $v2)
                {
                    $tid = $v2->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    $row = '<div class="small-12 columns">';
                    $row .= form_input(array(
                        'name' => 'f[srv][SPSingleLogoutService][' . $tid . '][bind]',
                        'id' => 'f[srv][SPSingleLogoutService][' . $tid . '][bind]',
                        'type' => 'hidden',
                        'value' => set_value('f[srv][SPSingleLogoutService][' . $tid . '][bind]', $v2->getBindingName()),
                    ));
                    $row .= $this->_generateLabelInput($v2->getBindingName(), 'f[srv][SPSingleLogoutService][' . $tid . '][url]', set_value('f[srv][SPSingleLogoutService][' . $v2->getId() . '][url]', $v2->getUrl()), '', FALSE, NULL);
                    $row .= '</div>';
                    unset($spslotmpl[array_search($v2->getBindingName(), $spslotmpl)]);
                    $spslo[] = $row;
                }
            }
            $ni = 0;
            foreach ($spslotmpl as $k3 => $v3)
            {
                $row = '<div class="small-12 columns">';
                $row .= form_input(array(
                    'name' => 'f[srv][SPSingleLogoutService][n' . $ni . '][bind]',
                    'id' => 'f[srv][SPSingleLogoutService][n' . $ni . '][bind]',
                    'type' => 'hidden',
                    'value' => $v3,));
                $row .= $this->_generateLabelInput($v3, 'f[srv][SPSingleLogoutService][n' . $ni . '][url]', set_value('f[srv][SPSingleLogoutService][n' . $ni . '][url]'), '', FALSE, NULL);
                $row .= '</div>';
                $spslo[] = $row;
                ++$ni;
            }
            $SPSLOPart .= implode('', $spslo);
            $SPSLOPart .= '</div></fieldset>';
            $result[] = $SPSLOPart;
            /**
             * end SP SingleLogoutService
              /**
             * start RequestInitiator
             */
            $RequestInitiatorPart = '<fieldset><legend>' . lang('requestinitatorlocations') . '</legend>';
            $ri = array();
            if (!$sessform && array_key_exists('RequestInitiator', $g))
            {
                $tmpid = 100;
                foreach ($g['RequestInitiator'] as $k3 => $v3)
                {
                    $tid = $v3->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    $turl = $v3->getUrl();
                    if ($sessform && isset($ses['srv']['RequestInitiator']['' . $tid . '']))
                    {
                        if (array_key_exists('url', $ses['srv']['RequestInitiator']['' . $tid . '']))
                        {
                            $turl = $ses['srv']['RequestInitiator'][$tid]['url'];
                        }
                    }
                    $furl = set_value('f[srv][RequestInitiator][' . $tid . '][url]', $turl);
                    $urlnotice = '';
                    if ($furl != $v3->getUrl())
                    {
                        $urlnotice = 'notice';
                    }
                    $r = '<div class="small-12 columns srvgroup">';
                    $r .= $this->_generateLabelInput(lang('rr_url'), 'f[srv][RequestInitiator][' . $tid . '][url]', $furl, 'acsurl ' . $urlnotice . '', TRUE, NULL);
                    $r .= '</div>';
                    $ri[] = $r;
                    if (isset($ses['srv']['RequestInitiator']['' . $tid . '']))
                    {
                        unset($ses['srv']['RequestInitiator']['' . $tid . '']);
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

                    $r = '<div class="small-12 columns srvgroup">';
                    $r .= $this->_generateLabelInput(lang('rr_url'), 'f[srv][RequestInitiator][' . $k4 . '][url]', set_value('f[srv][RequestInitiator][' . $k4 . '][url]', $purl), 'acsurl notice', TRUE, NULL);
                    $r .= '</div>';

                    $ri[] = $r;
                    unset($ses['srv']['RequestInitiator']['' . $k4 . '']);
                }
            }
            $RequestInitiatorPart .= implode('', $ri);
            $newelement = '<div><button class="editbutton addicon smallerbtn button tiny" type="button" id="nribtn" value="' . lang('rr_remove') . '">' . lang('addnewreqinit') . '</button></div>';
            $RequestInitiatorPart .= $newelement . '</fieldset>';
            $result[] = $RequestInitiatorPart;
            /**
             * end RequestInitiator
             */
            /**
             * start DiscoveryResponse
             */
            $DiscoverResponsePart = '<fieldset><legend>' . lang('discoveryresponselocations') . '</legend><div>';
            $dr = array();
            /**
             * list existing DiscoveryResponse
             */
            if (!$sessform && array_key_exists('DiscoveryResponse', $g))
            {
                $tmpid = 100;
                foreach ($g['DiscoveryResponse'] as $k3 => $v3)
                {
                    $tid = $v3->getId();
                    if (empty($tid))
                    {
                        $tid = 'x' . $tmpid++;
                    }
                    $turl = $v3->getUrl();
                    $torder = $v3->getOrder();
                    $tbind = $v3->getBindingName();
                    $furl = set_value('f[srv][DiscoveryResponse][' . $tid . '][url]', $turl);
                    $forder = set_value('f[srv][DiscoveryResponse][' . $tid . '][order]', $torder);
                    $fbind = set_value('f[srv][DiscoveryResponse][' . $tid . '][bind]', $tbind);
                    $urlnotice = '';
                    $ordernotice = '';
                    $bindnotice = '';
                    $r = '<div class="srvgroup">';

                    $r .= '<div class="small-12 columns">';
                    $r .= generateSelectInputFields(lang('rr_bindingname'), 'f[srv][DiscoveryResponse][' . $tid . '][bind]', array('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol' => 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol'), $fbind, '', 'f[srv][DiscoveryResponse][' . $tid . '][order]', $forder, NULL);
                    $r .='</div>';

                    $r .= '<div class="small-12 columns">';
                    $r .= generateInputWithRemove(lang('rr_url'), 'f[srv][DiscoveryResponse][' . $tid . '][url]', 'rmfield', '', $furl, 'acsurl', 'rmfield');
                    $r .='</div>';


                    $r .='</div>';
                    $dr[] = $r;
                    if (isset($ses['srv']['DiscoveryResponse']['' . $tid . '']))
                    {
                        unset($ses['srv']['DiscoveryResponse']['' . $tid . '']);
                    }
                }
            }
            if ($sessform && isset($ses['srv']['DiscoveryResponse']) && is_array($ses['srv']['DiscoveryResponse']))
            {
                foreach ($ses['srv']['DiscoveryResponse'] as $k4 => $v4)
                {



                    ///////////
                    $forder = set_value('f[srv][DiscoveryResponse][' . $k4 . '][order]', $ses['srv']['DiscoveryResponse']['' . $k4 . '']['order']);
                    $furl = set_value('f[srv][DiscoveryResponse][' . $k4 . '][url]', $ses['srv']['DiscoveryResponse']['' . $k4 . '']['url']);
                    $r = '<div class="small-12 columns">';
                    $r .= generateSelectInputFields(lang('rr_bindingname'), 'f[srv][DiscoveryResponse][' . $k4 . '][bind]', array('urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol' => 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol'), '' . $v4['bind'] . '', '', 'f[srv][DiscoveryResponse][' . $k4 . '][order]', $forder, NULL);
                    $r .= '</div>';
                    $r .= '<div class="small-12 columns">';
                    $r .= generateInputWithRemove(lang('rr_url'), 'f[srv][DiscoveryResponse][' . $k4 . '][url]', 'rmfield', '', $furl, 'acsurl', 'rmfield');
                    $r .= '</div>';
                    $dr[] = '<div class="srvgroup">' . $r . '</div>';
                    unset($ses['srv']['DiscoveryResponse']['' . $k4 . '']);
                }
            }
            $DiscoverResponsePart .= implode('', $dr);
            $newelement = '<div><button class="editbutton addicon smallerbtn button tiny" type="button" id="ndrbtn">' . lang('addnewds') . '</button></div>';
            $DiscoverResponsePart .= $newelement . '';
            $result[] = $DiscoverResponsePart . '</fieldset>';

            /**
             * start protocol enumeration
             */
            $allowedproto = getAllowedProtocolEnum();
            $allowednameids = getAllowedNameId();

            $allowedoptions = array();
            foreach ($allowedproto as $v)
            {
                $allowedoptions['' . $v . ''] = $v;
            }
            $spssoprotocols = $ent->getProtocolSupport('spsso');
            $selected_options = array();
            $spssonotice = '';
            if ($sessform && isset($ses['prot']['spsso']) && is_array($ses['prot']['spsso']))
            {
                if (count(array_diff($ses['prot']['spsso'], $spssoprotocols)) > 0 || count(array_diff($spssoprotocols, $ses['prot']['spsso'])) > 0)
                {
                    $spssonotice = 'notice';
                }
                foreach ($ses['prot']['spsso'] as $v)
                {
                    $selected_options[$v] = $v;
                }
            } else
            {
                foreach ($spssoprotocols as $p)
                {
                    $selected_options[$p] = $p;
                }
            }
            $r = '<div class="small-12 columns">';
            $r .= '<div class="small-12 medium-6 large-7  medium-push-3 large-push-3 columns inline end">';
            $r .= '<div class="checkboxlist">';
            foreach ($allowedoptions as $a)
            {
                $is = FALSE;
                if (in_array($a, $selected_options))
                {
                    $is = TRUE;
                }
                $r .= '<div>' . form_checkbox(array('name' => 'f[prot][spsso][]', 'id' => 'f[prot][spsso][]', 'value' => $a, 'checked' => $is)) . $a . '</div>';
            }
            $r .= '</div>';
            $r .='</div>';
            $r .= '</div>'; //row
            $result[] = '';
            $result[] = '<fieldset><legend>' . lang('rr_protenums') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end protocol enumerations
             */
            /**
             * start nameids 
             */
            $r = '';
            $spssonameids = $ent->getNameIds('spsso');
            $spssonameidnotice = '';
            $supportednameids = array();
            $chp = array();
            if ($sessform && is_array($ses))
            {
                if (isset($ses['nameids']['spsso']) && is_array($ses['nameids']['spsso']))
                {
                    foreach ($ses['nameids']['spsso'] as $pv)
                    {
                        $supportednameids[] = $pv;
                        $chp[] = array('name' => 'f[nameids][spsso][]', 'id' => 'f[nameids][spsso][]', 'value' => $pv, 'checked' => TRUE);
                    }
                }
            } else
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
            $r .= '<div class="small-12 columns">';
            $r .= '<div class="small-3 large-3 columns">&nbsp;</div>';
            $r .='<div class="small-8 large-7 columns nsortable ' . $spssonameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<div>' . form_checkbox($n) . $n['value'] . '</div>';
            }
            $r .='</div>';
            $r .= '<div class="columns"></div>';

            $r .= '</div>'; //row
            $result[] = '';
            $result[] = '<fieldset><legend>' . lang('rr_supnameids') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end nameids
             */
        }



        return $result;
    }

    private function NgenerateLogoForm(models\Provider $ent, $ses = null)
    {
        $langs = languagesCodes();
        $btnlangs = MY_Controller::$langselect;
        $btnlangs = array('0' => lang('rr_unspecified')) + $btnlangs;
        $type = $ent->getType();
        $sessform = FALSE;
        if (is_array($ses))
        {
            $sessform = TRUE;
        }

        if ($type === 'BOTH')
        {
            $logos = array('idp' => array(), 'sp' => array());
        } else
        {
            $logos = array('' . strtolower($type) . '' => array());
        }
        if (!$sessform)
        {
            $metaext = $ent->getExtendMetadata();
            foreach ($metaext as $v)
            {
                $velement = $v->getElement();
                if (strcmp($velement, 'Logo') != 0)
                {
                    continue;
                }
                $attrs = $v->getAttributes();
                if (isset($attrs['xml:lang']))
                {
                    $lang = $attrs['xml:lang'];
                } else
                {
                    $lang = 0;
                }
                $logos[$v->getType()]['' . $v->getId() . ''] = array(
                    'url' => '' . $v->getLogoValue() . '',
                    'lang' => '' . $lang . '',
                    'width' => $attrs['width'],
                    'height' => $attrs['height'],
                );
            }
        } else
        {
            if (strcasecmp('SP', $type) != 0 && isset($ses['uii']['idpsso']['logo']))
            {
                log_message('debug', 'IDP POLO');
                foreach ($ses['uii']['idpsso']['logo'] as $k => $v)
                {
                    $size = explode('x', $v['size']);
                    $logos['idp']['' . $k . ''] = array(
                        'url' => $v['url'],
                        'lang' => $v['lang'],
                        'width' => $size[0],
                        'height' => $size[1],
                    );
                }
            }
            if (strcasecmp('IDP', $type) != 0 && isset($ses['uii']['spsso']['logo']))
            {
                log_message('debug', 'SP POLO');
                foreach ($ses['uii']['spsso']['logo'] as $k => $v)
                {
                    $size = explode('x', $v['size']);
                    $logos['sp']['' . $k . ''] = array(
                        'url' => $v['url'],
                        'lang' => $v['lang'],
                        'width' => $size[0],
                        'height' => $size[1],
                    );
                }
            }
        }

        $result = array();


        foreach ($logos as $k1 => $v1)
        {
            $result[$k1][] = '';
            if (strcmp($k1, 'idp') == 0)
            {

                $t = 'idp';
            } elseif (strcmp($k1, 'sp') == 0)
            {

                $t = 'sp';
            }
            $p = '<ul class="small-block-grid-1">';
            foreach ($v1 as $k2 => $v2)
            {
                $p .= '<li class="small-12 columns">';
                $p .= '<div class="medium-3 columns"><img src="' . $v2['url'] . '" style="max-height: 100px;"/>';

                $p .= form_input(array(
                    'id' => 'f[uii][' . $t . 'sso][logo][' . $k2 . '][url]',
                    'name' => 'f[uii][' . $t . 'sso][logo][' . $k2 . '][url]',
                    'value' => $v2['url'],
                    'type' => 'hidden',
                ));
                $p .= form_input(array(
                    'id' => 'f[uii][' . $t . 'sso][logo][' . $k2 . '][lang]',
                    'name' => 'f[uii][' . $t . 'sso][logo][' . $k2 . '][lang]',
                    'value' => $v2['lang'],
                    'type' => 'hidden',
                ));
                $p .= form_input(array(
                    'id' => 'f[uii][' . $t . 'sso][logo][' . $k2 . '][size]',
                    'name' => 'f[uii][' . $t . 'sso][logo][' . $k2 . '][size]',
                    'value' => $v2['width'] . 'x' . $v2['height'],
                    'type' => 'hidden',
                ));
                $p .= '</div>';
                $p .= '<div class="medium-6 columns">';
                $p .= lang('rr_url') . ': ' . $v2['url'] . '<br />';
                if (empty($v2['lang']))
                {
                    $l = lang('rr_unspecified');
                } else
                {
                    $l = $v2['lang'];
                }
                $p .= lang('rr_lang') . ': ' . $l . '<br />';
                $p .= lang('rr_size') . ': ' . $v2['width'] . 'x' . $v2['height'] . '';
                $p .= '</div>';
                $p .= '<div class="medium-3 columns"><button class="btn langinputrm inline left button tiny alert">' . lang('rr_remove') . '</button></div>';
                $p .= '</li>';
            }

            $z = '<li id="nlogo' . $t . 'row" class="small-12 columns" style="display: none;">';
            $z .= '<div class="medium-3 columns"><img src="" style="max-height: 100px;"/></div>';
            $z .= '<div class="medium-6 columns logoinfo"></div>';
            $z .= '<div class="medium-3 columns"><button class="btn langinputrm inline left button tiny alert">' . lang('rr_remove') . '</button></div>';
            $z .= '</li>';
            $p .= $z;
            $p .= '</ul>';
            //$result[$k1][] = $p;
            $inlabel = '<div class="small-12 column"><div class="small-3 columns"><label class="right inline" for="logoretrieve">' . lang('rr_url') . '</label></div>';
            $in = '<div class="small-6 columns">' . form_input(array('name' => '' . $t . 'logoretrieve')) . '<small class="' . $t . 'logoretrieve error" style="display:none;"></small></div>';
            $in2 = '<div class="small-3 columns"><button type="button" name="' . $t . 'getlogo" class="button tiny getlogo" value="' . base_url() . 'ajax/checklogourl">' . lang('btngetlogo') . '</button></div></div>';

            $langselection = form_dropdown($t . 'logolang', $btnlangs);
            $reviewlogo = '<div class="small-3 column"><div class="logolangselect">' . $langselection . '</div><div class="logosizeinfo"></div></div><div class="small-6 column imgsource"></div><div class="small-3 column"><button class="button tiny addnewlogo" type="button" name="addnewlogo">' . lang('rr_add') . '</button></div>';
            $dupa = '<fieldset><legend>' . lang('rr_newlogosection') . '</legend>' . $inlabel . $in . $in2 . '<div id="' . $t . 'reviewlogo" class="small-12 column reviewlogo" style="display: none; max-height: 100px">' . $reviewlogo . '</div></fieldset>';

            $result[$k1][] = '<fieldset><legend>' . lang('rr_logoofservice') . '</legend>' . $p . ' ' . $dupa . '</fieldset>';
            $result[$k1][] = '';
        }


        return $result;
    }

    public function NgenerateUiiForm(models\Provider $ent, $ses = null)
    {
        $logopart = $this->NgenerateLogoForm($ent, $ses);

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

        if (!empty($t_privacyurl))
        {
            $r = $this->_generateLabelInput(lang('rr_url'), 'f[privacyurl]', $t_privacyurl, $privaceurlnotice, FALSE, NULL);
            $result = array();
            $result[] = '';
            $result[] = '<div class="langgroup">' . lang('e_globalprivacyurl') . '<i><small> (' . lang('rr_default') . ') ' . lang('rr_optional') . '</small></i>' . showBubbleHelp('' . lang('rhelp_privacydefault1') . '') . '</div>';
            $result[] = $r;
            $result[] = '';
        }



        if ($type != 'SP')
        {
            /**
             * start display
             */
            if (strcasecmp($type, 'BOTH') == 0)
            {
                $result[] = '<div class="section">' . lang('identityprovider') . '</div>';
            }
            $result[] = '';
            //$result[] = '<div class="langgroup">' . lang('e_idpservicename') . '</div>';
            $r = '';
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['idp']['mdui']['DisplayName']))
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
                        } else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    } else
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
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][idpsso][displayname][' . $lang . ']', 'uiiidpssodisplayname', '' . $lang . '', $currval, $displaynotice);
                    $r .= '</div>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['displayname']) && is_array($ses['uii']['idpsso']['displayname']))
            {

                foreach ($ses['uii']['idpsso']['displayname'] as $key => $value)
                {
                    if (!array_key_exists($key, $langs))
                    {
                        log_message('error', 'Language code ' . $key . ' is not allowed for row (extendmetadaa)');
                        $langtxt = $key;
                    } else
                    {
                        $langtxt = $langs['' . $key . ''];
                    }
                    $r .= '<div class="small-12 columns">';
                    $tmpvalue = set_value('f[uii][idpsso][displayname][' . $key . ']', $value);
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][idpsso][displayname][' . $key . ']', 'uiiidpssodisplayname', $key, $tmpvalue, 'notice');
                    $r .= '</div>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<div class="small-12 columns">';
            $r.= $this->_generateLangAddButton('idpuiidisplayadd', 'idpuiidisplaylangcode', MY_Controller::$langselect, 'idpadduiidisplay', 'idpadduiidisplay');
            $r .='</div>';
            //  $r .= form_fieldset_close();
            $result[] = '<fieldset><legend>' . lang('e_idpservicename') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end display
             */
            /**
             * start helpdesk 
             */
            $result[] = '';
            //$r = form_fieldset('' . lang('e_idpserviceinfourl') . '');
            //$result[] = '<div class="langgroup">' . lang('e_idpserviceinfourl') . '</div>';
            $r = '';
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['idp']['mdui']['InformationURL']))
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
                        } else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    } else
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
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][idpsso][helpdesk][' . $lang . ']', 'uiiidpssohelpdesk', $lang, $currval, $displaynotice);
                    $r .= '</div>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['helpdesk']) && is_array($ses['uii']['idpsso']['helpdesk']))
            {
                foreach ($ses['uii']['idpsso']['helpdesk'] as $key => $value)
                {
                    if (!array_key_exists($key, $langs))
                    {
                        log_message('error', 'Language code ' . $key . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                        $langtxt = $key;
                    } else
                    {
                        $langtxt = $langs['' . $key . ''];
                    }
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][idpsso][helpdesk][' . $key . ']', 'uiiidpssohelpdesk', $key, set_value('f[uii][idpsso][helpdesk][' . $key . ']', $value), 'notice');
                    $r .= '</div>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<div class="small-12 columns">';
            $r .= $this->_generateLangAddButton('idpuiihelpdeskadd', 'idpuiihelpdesklangcode', MY_Controller::$langselect, 'idpadduiihelpdesk', 'idpadduiihelpdesk');
            $r .= '</div>';
            $result[] = '<fieldset><legend>' . lang('e_idpserviceinfourl') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end helpdesk
             */
            /**
             * start description
             */
            $result[] = '';
            //$r = form_fieldset('' . lang('e_idpservicedesc') . '');
            $r = '';
            //$result[] = '<div class="langgroup">' . lang('e_idpservicedesc') . '</div>';
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
                        } else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    } else
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
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangTextareaWithRemove($langtxt, 'f[uii][idpsso][desc][' . $lang . ']', 'lhelpdesk', $lang, $currval, $displaynotice);
                    $r .= '</div>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['desc']) && is_array($ses['uii']['idpsso']['desc']))
            {
                foreach ($ses['uii']['idpsso']['desc'] as $key => $value)
                {
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangTextareaWithRemove($langs['' . $key . ''], 'f[uii][idpsso][desc][' . $key . ']', 'lhelpdesk', $key, set_value('f[uii][idpsso][desc][' . $key . ']', $value), 'notice');
                    $r .= '</div>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .='<div class="small-12 columns">';
            $r .= $this->_generateLangAddButton('idpuiidescadd', 'idpuiidesclangcode', MY_Controller::$langselect, 'idpadduiidesc', '' . lang('rr_description') . '');
            $r .='</div>';
            //$r .= form_fieldset_close();
            $result[] = '<fieldset><legend>' . lang('e_idpservicedesc') . '</legend>' . $r . '</fieldset>';
            $result[] = '';
            /**
             * end description 
             */
            /**
             * start privacy url
             */
            $result[] = '';
            $r = '';
            //$result[] = '<div class="langgroup">' . lang('e_idpserviceprivacyurl') . ' </div>';
            $origs = array();
            $sorig = array();
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['idp']['mdui']['PrivacyStatementURL']))
            {
                foreach ($ext['idp']['mdui']['PrivacyStatementURL'] as $value)
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
                    } else
                    {
                        $sorig['' . $k3 . '']['notice'] = 'notice';
                    }
                } else
                {
                    $sorig['' . $k3 . '']['notice'] = 'notice';
                }
            }
            foreach ($sorig as $k4 => $v4)
            {
                $r .= '<div class="small-12 columns">';
                $r .= $this->_generateLangInputWithRemove($langsdisplaynames['' . $k4 . ''], 'f[prvurl][idpsso][' . $k4 . ']', 'prvurlidpsso', $k4, $v4['url'], '');
                $r .= '</div>';
            }
            $idpssolangcodes = array_diff_key($langsdisplaynames, $sorig);
            $r .='<div class="small-12 columns">';
            $r .= $this->_generateLangAddButton('addlprivacyurlidpsso localized', 'langcode', MY_Controller::$langselect, 'addlprivacyurlidpsso', 'addlprivacyurlidpsso');
            $r .= '</div>';

            $result[] = '<fieldset><legend>' . lang('e_idpserviceprivacyurl') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end privacy url
             */
            foreach ($logopart['idp'] as $v)
            {
                $result[] = $v;
            }
        }
        if ($type != 'IDP')
        {


            /**
             * start display
             */
            if (strcasecmp($type, 'BOTH') == 0)
            {
                $result[] = '<div class="section">' . lang('serviceprovider') . '</div>';
            }
            $result[] = '';
            //$result[] = '<div class="langgroup">' . lang('e_spservicename') . '</div>';
            $r = '';
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['sp']['mdui']['DisplayName']))
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
                        } else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    } else
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
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][spsso][displayname][' . $lang . ']', 'uiispssodisplayname', $lang, $currval, $displaynotice);
                    $r .= '</div>';
                }
            }
            if ($sessform && isset($ses['uii']['spsso']['displayname']) && is_array($ses['uii']['spsso']['displayname']))
            {
                foreach ($ses['uii']['spsso']['displayname'] as $key => $value)
                {
                    $r .= '<div class="small-12 columns">';
                    if (isset($langs['' . $key . '']))
                    {
                        $langtxt = $langs['' . $key . ''];
                    } else
                    {
                        $langtxt = $key;
                    }
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][spsso][displayname][' . $key . ']', 'uiispssodisplayname', $key, set_value('f[uii][spsso][displayname][' . $key . ']', $value), 'notice');
                    $r .= '</div>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }

            $r .= '<div class="small-12 columns">';
            $r .= $this->_generateLangAddButton('spuiidisplayadd', 'spuiidisplaylangcode', MY_Controller::$langselect, 'spadduiidisplay', 'spadduiidisplay');
            $r .= '</div>';
            $result[] = '<fieldset><legend>' . lang('e_spservicename') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end display
             */
            /**
             * start helpdesk 
             */
            $result[] = '';
            //$r = form_fieldset('' . lang('e_spserviceinfourl') . '');
            //$result[] = '<div class="langgroup">' . lang('e_spserviceinfourl') . '</div>';
            $r = '';
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['sp']['mdui']['InformationURL']))
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
                        } else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    } else
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
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][spsso][helpdesk][' . $lang . ']', 'uiispssohelpdesk', $lang, $currval, $displaynotice);
                    $r .= '</div>';
                }
            }
            if ($sessform && isset($ses['uii']['spsso']['helpdesk']) && is_array($ses['uii']['spsso']['helpdesk']))
            {
                foreach ($ses['uii']['spsso']['helpdesk'] as $key => $value)
                {
                    $r .= '<div class="small-12 columns">';
                    if (isset($langs['' . $key . '']))
                    {
                        $langtxt = $langs['' . $key . ''];
                    } else
                    {
                        $langtxt = $key;
                    }
                    $r .= $this->_generateLangInputWithRemove($langtxt, 'f[uii][spsso][helpdesk][' . $key . ']', 'uiispssohelpdesk', $key, set_value('f[uii][spsso][helpdesk][' . $key . ']', $value), 'notice');
                    $r .= '</div>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .='<div class="small-12 columns">';
            $r .= $this->_generateLangAddButton('spuiihelpdeskadd', 'spuiihelpdesklangcode', MY_Controller::$langselect, 'spadduiihelpdesk', 'spadduiihelpdesk');
            $r .='</div>';

            //$r .= form_fieldset_close();
            $result[] = '<fieldset><legend>' . lang('e_spserviceinfourl') . '</legend>' . $r . '</fieldset>';
            $result[] = '';
            /**
             * end helpdesk
             */
            /**
             * start description
             */
            $result[] = '';
            //$r = form_fieldset('' . lang('e_spservicedesc') . '');
            //$result[] = '<div class="langgroup">' . lang('e_spservicedesc') . '</div>';
            $r = '';
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['sp']['mdui']['Description']))
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
                        } else
                        {
                            $langtxt = $langs['' . $lang . ''];
                            unset($langsdisplaynames['' . $lang . '']);
                        }
                    } else
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
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangTextareaWithRemove($langtxt, 'f[uii][spsso][desc][' . $lang . ']', 'uiispssodesc', $lang, $currval, $displaynotice);
                    $r .= '</div>';
                }
            }
            if ($sessform && isset($ses['uii']['spsso']['desc']) && is_array($ses['uii']['spsso']['desc']))
            {
                foreach ($ses['uii']['spsso']['desc'] as $key => $value)
                {
                    $r .= '<div class="small-12 columns">';
                    $r .= $this->_generateLangTextareaWithRemove($langs['' . $key . ''], 'f[uii][spsso][desc][' . $key . ']', 'uiispssodesc', $key, set_value('f[uii][spsso][desc][' . $key . ']', $value), 'notice');
                    $r .= '</div>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<div class="small-12 columns">';
            $r .= $this->_generateLangAddButton('spuiidescadd', 'spuiidesclangcode', MY_Controller::$langselect, 'spadduiidesc', 'spadduiidesc');
            $r .= '</div>';
            //$r .= form_fieldset_close();
            $result[] = '<fieldset><legend>' . lang('e_spservicedesc') . '</legend>' . $r . '</fieldset>';
            $result[] = '';
            /**
             * end description 
             */
            /**
             * start privacy url
             */
            $result[] = '';
            $r = '';
            //$result[] = '<div class="langgroup">' . lang('e_spserviceprivacyurl') . ' </div>';
            $origs = array();
            $sorig = array();
            $langsdisplaynames = $langs;
            if (!$sessform && isset($ext['sp']['mdui']['PrivacyStatementURL']))
            {
                foreach ($ext['sp']['mdui']['PrivacyStatementURL'] as $value)
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
                    } else
                    {
                        $sorig['' . $k3 . '']['notice'] = 'notice';
                    }
                } else
                {
                    $sorig['' . $k3 . '']['notice'] = 'notice';
                }
            }
            foreach ($sorig as $k4 => $v4)
            {
                $r .= '<div class="small-12 columns">';
                $r .= $this->_generateLangInputWithRemove($langsdisplaynames['' . $k4 . ''], 'f[prvurl][spsso][' . $k4 . ']', 'prvurlspsso', $k4, $v4['url'], '');
                $r .= '</div>';
            }
            $idpssolangcodes = array_diff_key($langsdisplaynames, $sorig);
            $r .= '<div class="small-12 columns">';


            $r .= $this->_generateLangAddButton('addlprivacyurlspsso localized', 'langcode', MY_Controller::$langselect, 'addlprivacyurlspsso', 'addlprivacyurlspsso');
            $r .= '</div>';

            $result[] = '<fieldset><legend>' . lang('e_spserviceprivacyurl') . '</legend>' . $r . '</fieldset>';
            $result[] = '';

            /**
             * end privacy url
             */
            foreach ($logopart['sp'] as $v)
            {
                $result[] = $v;
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
        } else
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
        $result .= form_dropdown('fedid', $list, set_value('fedid'));
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
        $f .= '<div class="small-12 columns">';
        $f .='<div class="small-3 columns">' . form_label(lang('rr_fed_name'), 'fedname') . '</div>';
        $f .= '<div class="small-8 large-7 columns">' . form_input('fedname', set_value('fedname', $federation->getName())) . '</div><div></div>';
        $f .= '</div>';
        $f .= '<div class="small-12 columns">';
        $f .='<div class="small-3 columns">' . form_label(lang('fednameinmeta'), 'urn') . '</div>';
        $f .= '<div class="small-8 large-7 columns">' . form_input('urn', set_value('urn', $federation->getUrn())) . '</div><div></div>';
        $f .= '</div>';
        $f .= '<div class="small-12 columns">';
        $f .= '<div class="small-3 columns">' . form_label(lang('rr_fed_publisher'), 'publisher') . '</div><div class="small-8 large-7 columns">' . form_input('publisher', set_value('publisher', $federation->getPublisher())) . '</div><div class="small-1 large-2 "></div>';
        $f .= '</div>';
        $f .= '<div class="small-12 columns">';
        $f .= '<div class="small-3 columns">' . form_label(lang('rr_isfedpublic') . ' ' . showBubbleHelp(lang('rhelppublicfed')), 'ispublic') . '</div><div class="small-8 large-7 columns end">' . form_checkbox('ispublic', 'accept', set_value('ispublic', $federation->getPublic())) . '</div>';
        $f .= '</div>';
        $f .= '<div class="small-12 columns">';
        $f .= '<div class="small-3 columns">' . form_label(lang('rr_include_attr_in_meta'), 'incattrs') . '</div><div class="small-8 large-7 columns">' . form_checkbox('incattrs', 'accept', set_value('incattrs', $federation->getAttrsInmeta())) . '</div><div class="small-1 large-2 "></div></div>';
        $f .= '</div>';

        $f .= '<div class="small-12 columns">';
        $f .= '<div class="small-3 columns">' . form_label(lang('rr_lexport_enabled'), 'lexport') . '</div><div class="small-8 large-7 columns">' . form_checkbox('lexport', 'accept', set_value('lexport', $federation->getLocalExport())) . '</div><div class="small-1 large-2 "></div>';
        $f .= '</div>';

        $f .= '<div class="small-12 columns">';
        $f .= '<div class="small-3 columns">' . form_label(lang('digestmethodsign'), 'digestmethod') . '</div><div class="small-8 large-7 columns">' . form_dropdown('digestmethod', array('SHA-1' => 'SHA-1', 'SHA-256' => 'SHA-256'), set_value('digestmethod', $federation->getDigest())) . '</div><div class="small-1 large-2 "></div>';
        $f .= '</div>';

        $f .= '<div class="small-12 columns">';
        $f .= '<div class="small-3 columns">' . form_label(lang('digestmethodexportsign'), 'digestmethodext') . '</div><div class="small-8 large-7 columns">' . form_dropdown('digestmethodext', array('SHA-1' => 'SHA-1', 'SHA-256' => 'SHA-256'), set_value('digestmethodext', $federation->getDigestExport())) . '</div><div class="small-1 large-2 "></div>';
        $f .= '</div>';

        $f .= '<div class="small-12 columns">';
        $f .='<div class="small-3 columns">' . form_label(lang('rr_description'), 'description') . '</div>';
        $f .='<div class="small-8 large-7 columns">' . form_textarea('description', set_value('description', $federation->getDescription())) . '</div>';
        $f .= '<div class="small-1 large-2 "></div>';
        $f .= '</div>';
        $f .= '<div class="small-12 columns">';
        $f .='<div class="small-3 columns">' . form_label(lang('rr_fed_tou'), 'tou') . '</div>';
        $f .= '<div class="small-8 large-7 columns">' . form_textarea('tou', set_value('tou', $federation->getTou())) . '</div>';
        $f .= '<div class="small-1 large-2 "></div>';
        $f .= '</div>';
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
            } else
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
            $result .= '<button name="submit" type="submit" value="cancel" class="resetbutton reseticon">' . lang('rr_cancel') . '</button>';
            $result .= '<button name="submit" type="submit" value="create" class="savebutton saveicon">' . lang('rr_create') . '</button>';
        } else
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
        $result .= '<div class="small-12 columns"><div class="small-3 columns">' . form_label(lang('rr_setpolicy'), 'policy') . '</div><div class="small-6 large-7 columns end">';
        $result .= form_dropdown('policy', $this->ci->config->item('policy_dropdown'), $arp->getPolicy()) . '</div></div>';
        $result .= form_fieldset_close();
        return $result;
    }

    public function generateAddRegpol()
    {
        $langs = languagesCodes();
        $langselected = set_value('regpollang', $this->defaultlangselect);
        $r = '<div class="small-12 columns"><div class="small-3 columns"><label for="cenabled" class="inline right">' . lang('entcat_enabled') . '</label></div><div class="small-6 large-7 columns end">' . form_checkbox('cenabled', 'accept') . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="regpollang" class="inline right">' . lang('regpol_language') . '</label></div><div class="small-6 large-7 columns end">' . form_dropdown('regpollang', $langs, $langselected) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="name" class="inline right">' . lang('entcat_shortname') . '</label></div><div class="small-6 large-7 columns end">' . form_input('name', set_value('name')) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="url" class="inline right">' . lang('entcat_url') . '</label></div><div class="small-6 large-7 columns end">' . form_input('url', set_value('url')) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="description" class="inline right">' . lang('entcat_description') . '</label></div><div class="small-6 large-7 columns end">' . form_textarea('description', set_value('description')) . '</div></div>';
        return $r;
    }

    public function generateEditRegpol(models\Coc $coc)
    {
        $langs = languagesCodes();
        $langset = $coc->getLang();
        if (!empty($langset))
        {
            if (!array_key_exists($langset, $langs))
            {
                $langs['' . $langset . ''] = $langset;
            }
        }
        $langselected = set_value($langset, $this->defaultlangselect);
        $r = '<div class="small-12 columns"><div class="small-3 columns"><label for="cenabled" class="inline right">' . lang('entcat_enabled') . '</label></div><div class="small-6 large-7 columns end">' . form_checkbox('cenabled', 'accept', set_value('cenabled', $coc->getAvailable())) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="regpollang" class="inline right">' . lang('regpol_language') . '</label></div><div class="small-6 large-7 columns end">' . form_dropdown('regpollang', $langs, $langselected) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="name" class="inline right">' . lang('entcat_shortname') . '</label></div><div class="small-6 large-7 columns end">' . form_input('name', set_value('name', $coc->getName())) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="url" class="inline right">' . lang('entcat_url') . '</label></div><div class="small-6 large-7 columns end">' . form_input('url', set_value('url', $coc->getUrl())) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="description" class="inline right">' . lang('entcat_description') . '</label></div><div class="small-6 large-7 columns end">' . form_textarea('description', set_value('description', $coc->getDescription())) . '</div></div>';
        return $r;
    }

    public function generateAddCoc()
    {
        $r = '<div class="small-12 columns"><div class="small-3 columns"><label for="cenabled" class="inline right">' . lang('entcat_enabled') . '</label></div><div class="small-6 large-7 columns end">' . form_checkbox('cenabled', 'accept') . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="name" class="inline right">' . lang('entcat_shortname') . '</label></div><div class="small-6 large-7 columns end">' . form_input('name', set_value('name')) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="url" class="inline right">' . lang('entcat_url') . '</label></div><div class="small-6 large-7 columns end">' . form_input('url', set_value('url')) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="description" class="inline right">' . lang('entcat_description') . '</label></div><div class="small-6 large-7 columns end">' . form_textarea('description', set_value('description')) . '</div></div>';
        return $r;
    }

    public function generateEditCoc(models\Coc $coc)
    {
        $r = '<div class="small-12 columns"><div class="small-3 columns"><label for="cenabled" class="inline right">' . lang('entcat_enabled') . '</label></div><div class="small-6 large-7 columns end">' . form_checkbox('cenabled', 'accept', set_value('cenabled', $coc->getAvailable())) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="name" class="inline right">' . lang('entcat_shortname') . '</label></div><div class="small-6 large-7 columns end">' . form_input('name', set_value('name', $coc->getName())) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="url" class="inline right">' . lang('entcat_url') . '</label></div><div class="small-6 large-7 columns end">' . form_input('url', set_value('url', $coc->getUrl())) . '</div></div>';
        $r .= '<div class="small-12 columns"><div class="small-3 columns"><label for="description" class="inline right">' . lang('entcat_description') . '</label></div><div class="small-6 large-7 columns end">' . form_textarea('description', set_value('description', $coc->getDescription())) . '</div></div>';
        return $r;
    }

}
