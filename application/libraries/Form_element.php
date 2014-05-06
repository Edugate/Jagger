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
        $t_helpdeskurl = $ent->getHelpdeskUrl();

       // $t_validfrom = '';
       // $origvalidfrom = '';
       // $tmpvalidfrom = $ent->getValidFrom();
       // if (!empty($tmpvalidfrom))
       // {
       //     $t_validfrom = date('Y-m-d', $tmpvalidfrom->format('U') + j_auth::$timeOffset);
       //     $origvalidfrom = date('Y-m-d', $tmpvalidfrom->format('U') + j_auth::$timeOffset);
       // }
      //  $t_validto = '';
      //  $origvalidto = '';
      //  $tmpvalidto = $ent->getValidTo();
      //  if (!empty($tmpvalidto))
      //  {
      //      $t_validto = date('Y-m-d', $tmpvalidto->format('U') + j_auth::$timeOffset);
      //      $origvalidto = date('Y-m-d', $tmpvalidto->format('U') + j_auth::$timeOffset);
      //  }
       // $t_description = $ent->getDescription();

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
         /**
            if (array_key_exists('validrom', $ses))
            {
                $t_validfrom = $ses['validfrom'];
            }
            if (array_key_exists('validto', $ses))
            {
                $t_validto = $ses['validto'];
            }
         */
          /**
            if (array_key_exists('description', $ses))
            {
                $t_description = $ses['description'];
            }
          */
        }

        $f_regauthority = set_value('f[regauthority]', $t_regauthority);
        $f_regdate = set_value('f[registrationdate]', $t_regdate);
      //  $f_validfrom = set_value('f[validfrom]', $t_validfrom);
      //  $f_validto = set_value('f[validto]', $t_validto);
       // $f_description = set_value('f[description]', $t_description);
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
      /**
        if ($f_validfrom != $origvalidfrom)
        {
            $validfrom_notice = 'notice';
        } else
        {
            $validfrom_notice = '';
        }
        if ($f_validto != $origvalidto)
        {
            $validto_notice = 'notice';
        } else
        {
            $validto_notice = '';
        }
       */
        /**
        if ($f_description != form_prep($ent->getDescription()))
        {
            $description_notice = 'notice';
        } else
        {
            $description_notice = '';
        }
        */
        $result = array();

        // providername group 
        $result[] = '';
        $result[] = '<div class="langgroup">'.lang('e_orgname').'</div>';
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
            $result[] = form_label( $lnamelangs['' . $key . ''] , 'f[lname][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[lname][' . $key . ']',
                                'id' => 'f[lname][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $lnamenotice
                            )
                    ) . '<button type="button" class="btn langinputrm" name="lname" value="' . $key . '">'.lang('rr_remove').'</button>';
            unset($origlname['' . $key . '']);
            unset($lnamelangs['' . $key . '']);
            unset($btnlangs[''.$key.'']);
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
                $result[] = form_label( $lnamelangs['' . $key . ''] , 'f[lname][' . $key . ']') . form_input(
                                array(
                                    'name' => 'f[lname][' . $key . ']',
                                    'id' => 'f[lname][' . $key . ']',
                                    'value' => $lvalue,
                                    'class' => $lnamenotice
                                )
                        ) . '<button type="button" class="btn langinputrm" name="lname" value="' . $key . '">'.lang('rr_remove').'</button>';
                unset($lnamelangs['' . $key . '']);
                unset($btnlangs[''.$key.'']);
            }
        }
        $result[] = '<span class="lnameadd">' . form_dropdown('lnamelangcode', $btnlangs, $this->defaultlangselect) . '<button type="button" id="addlname" name="addlname" value="' . lang('e_orgname') . '" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span>';

        $result[] = '';
        /**
         * end lname
         */
        $result[] = '';
        /**
         * start ldisplayname
         */
        $result[] = '<div class="langgroup">'.lang('e_orgdisplayname').'</div>';
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
                $result[] = form_label($ldisplaynamelangs['' . $key . ''] , 'f[ldisplayname][' . $key . ']') . form_input(
                                array(
                                    'name' => 'f[ldisplayname][' . $key . ']',
                                    'id' => 'f[ldisplayname][' . $key . ']',
                                    'value' => $lvalue,
                                    'class' => $ldisplaynamenotice
                                )
                        ) . '<button type="button" class="btn langinputrm" name="ldisplayname" value="' . $key . '">'.lang('rr_remove').'</button>';
                unset($origldisplayname['' . $key . '']);
                unset($ldisplaynamelangs['' . $key . '']);
                unset($btnlangs[''.$key.'']);
                
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
                $result[] = form_label( $ldisplaynamelangs['' . $key . ''] , 'f[ldisplayname][' . $key . ']') . form_input(
                                array(
                                    'name' => 'f[ldisplayname][' . $key . ']',
                                    'id' => 'f[ldisplayname][' . $key . ']',
                                    'value' => $lvalue,
                                    'class' => $ldisplaynamenotice
                                )
                        ) . '<button type="button" class="btn langinputrm" name="ldisplayname" value="' . $key . '">'.lang('rr_remove').'</button>';
                unset($ldisplaynamelangs['' . $key . '']);
                unset($btnlangs[''.$key.'']);
            }
        }
        $result[] = '<span class="ldisplaynameadd">' . form_dropdown('ldisplaynamelangcode', $btnlangs, $this->defaultlangselect) . '<button type="button" id="addldisplayname" name="addldisplayname" value="' . lang('rr_displayname') . '" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span>';

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
        $result[] = '<div class="langgroup">'.lang('e_orgurl').'</div>';
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
            $result[] = form_label( $lhelpdesklangs['' . $key . ''] , 'f[lhelpdesk][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[lhelpdesk][' . $key . ']',
                                'id' => 'f[lhelpdesk][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $lhelpdesknotice
                            )
                    ) . '<button type="button" class="btn langinputrm" name="lhelpdesk" value="' . $key . '">'.lang('rr_remove').'</button>';
            unset($origlhelpdesk['' . $key . '']);
            unset($lhelpdesklangs['' . $key . '']);
            unset($btnlangs[''.$key.'']);
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
                $result[] = form_label( $lhelpdesklangs['' . $key . ''] , 'f[lhelpdesk][' . $key . ']') . form_input(
                                array(
                                    'name' => 'f[lhelpdesk][' . $key . ']',
                                    'id' => 'f[lhelpdesk][' . $key . ']',
                                    'value' => $lvalue,
                                    'class' => $lhelpdesknotice
                                )
                        ) . '<button type="button" class="btn langinputrm" name="lhelpdesk" value="' . $key . '">'.lang('rr_remove').'</button>';
                unset($lhelpdesklangs['' . $key . '']);
                unset($btnlangs[''.$key.'']);
            }
        }
        $result[] = '<span class="lhelpdeskadd">' . form_dropdown('lhelpdesklangcode', $btnlangs, $this->defaultlangselect) . '<button type="button" id="addlhelpdesk" name="addlhelpdesk" value="' . lang('rr_helpdeskurl') . '" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span>';


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
        $result[] = '';
        /**
         * start regpolicy 
         */
        $result[] = '';
        $result[] = '<div class="langgroup">' . lang('rr_regpolicy') . ' ' . showBubbleHelp('' . lang('entregpolicy_expl') . '') . '</div>';
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
            } else
            {
                $regpolicynotice = 'notice';
            }
            $result[] = form_label( $regpolicylangs['' . $key . ''] , 'f[regpolicy][' . $key . ']') . form_input(
                            array(
                                'name' => 'f[regpolicy][' . $key . ']',
                                'id' => 'f[regpolicy][' . $key . ']',
                                'value' => $lvalue,
                                'class' => $regpolicynotice
                            )
                    ) . '<button type="button" class="btn langinputrm" name="lname" value="' . $key . '">'.lang('rr_remove').'</button>';
            unset($origregpolicies['' . $key . '']);
            unset($regpolicylangs['' . $key . '']);
        }
        if (!$sessform)
        {
            foreach ($origregpolicies as $key => $value)
            {
                $regpolicynotice = '';
                $lvalue = set_value('f[regpolicy][' . $key . ']', $value);
                if ($lvalue != $value)
                {
                    $regpolicynotice = 'notice';
                }
                $result[] = form_label($regpolicylangs['' . $key . ''] , 'f[regpolicy][' . $key . ']') . form_input(
                                array(
                                    'name' => 'f[regpolicy][' . $key . ']',
                                    'id' => 'f[regpolicy][' . $key . ']',
                                    'value' => $lvalue,
                                    'class' => $regpolicynotice
                                )
                        ) . ' <button type="button" class="btn langinputrm" name="regpolicy" value="' . $key . '">'.lang('rr_remove').'</button>';
                unset($regpolicylangs['' . $key . '']);
            }
        }
        $result[] = '<span class="regpolicyadd">' . form_dropdown('regpolicylangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="addregpolicy" name="addregpolicy" value="' . lang('rr_regpolicy') . '" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span>';

        $result[] = '';
        /**
         * end regpolicy
         */
/**
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
*/
       /**
        $result[] = form_label(lang('rr_description'), 'f[description]') . form_textarea(array(
                    'name' => 'f[description]',
                    'id' => 'f[description]',
                    'class' => $description_notice,
                    'value' => $f_description,
        ));
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

        $r = '<fieldset><legend>' . lang('PrivacyStatementURL') . ' <i>' . lang('rr_default') . '</i>' . showBubbleHelp('' . lang('rhelp_privacydefault1') . '') . '</legend><ol><li>';
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
        $entCategories = $this->em->getRepository("models\Coc")->findAll();
        $entCategoriesArray = array();
        foreach($entCategories as $v)
        {
           $entCategoriesArray[''.$v->getId().''] = array('name'=>$v->getName(),'enabled'=>$v->getAvailable());
        }
        $assignedEntCategories = $ent->getCoc();
        $assignedEntCategoriesArray = array();  
        if ($sessform && isset($ses['coc']))
        {
            foreach($ses['coc'] as $k => $v)
            {
                if(isset($entCategoriesArray[''.$v.'']))
                {
                    $entCategoriesArray[''.$v.'']['sel'] = TRUE;
                }

            }
        }
        else
        {
           foreach($assignedEntCategories as $k=>$v)
           {
              $entCategoriesArray[''.$v->getId().'']['sel'] = true;
           }
        }
        $r = '';
        $r = '<ul class="checkboxlist">';
        foreach($entCategoriesArray as $k=>$v)
        {
           if(isset($v['sel']))
           {
                 $is = true;
           }
           else
           {
                 $is=false;
           }
           $r .= '<li>'.form_checkbox(array('name' =>'f[coc][]','id'=>'f[coc][]', 'value'=>$k,'checked'=>$is)). $v['name'].'</li>';
        }
        $r .= '</ul>';
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
        foreach ($origcnts as $cnt)
        {
            //$row = form_fieldset() . '<ol>';
            $row = '';
            $class_cnt1 = '';
            $class_cnt2 = '';
            $class_cnt3 = '';
            $class_cnt4 = '';
            if ($r)
            {
                if (isset($ses['contact']['' . $cnt->getId() . '']))
                {
                    $t1 = set_value($ses['contact'][$cnt->getId()]['type'], $cnt->getType());
                    $t2 = $ses['contact'][$cnt->getId()]['fname'];
                    $t3 = $ses['contact'][$cnt->getId()]['sname'];
                    $t4 = $ses['contact'][$cnt->getId()]['email'];
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
            $row .= '<li class="rmelbtn fromprevtoright"><button type="button" class="btn contactrm" name="contact" value="' . $cnt->getId() . '">' . lang('btn_removecontact') . '</button></li>';
          //  $row .= '</ol>' . form_fieldset_close();
            $result[] = '';
            $result[] = $row;
            $result[] = '';
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
                $n .= '<li class="rmelbtn fromprevtoright"><button type="button" class="btn contactrm" name="contact" value="' . $k . '">' . lang('btn_removecontact') . '</button></li>';
                $n .= '</ol>' . form_fieldset_close();
                $result[] = $n;
            }
        }
        $n = '<button class="editbutton addicon smallerbtn" type="button" id="ncontactbtn" value="'.lang('btn_removecontact').'|'.lang('rr_contacttype').'|'.lang('rr_contactfirstname').'|'.lang('rr_contactlastname').'|'.lang('rr_contactemail').'">' . lang('rr_addnewcoontact') . '</button>';
            $result[] = '';
        $result[] = $n;
            $result[] = '';

        return $result;
    }

    public function NgenerateServiceLocationsForm(models\Provider $ent, $ses = null)
    {
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
            $SSOPart = '';
            if (array_key_exists('SingleSignOnService', $g))
            {
                foreach ($g['SingleSignOnService'] as $k1 => $v1)
                {
                    if ($sessform && isset($ses['srv']['SingleSignOnService']['' . $v1->getId() . '']['url']))
                    {
                        $t1 = $ses['srv']['SingleSignOnService']['' . $v1->getId() . '']['url'];
                    } else
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
            $result[] = '';
            $result[] = '<div class="langgroup">SingleSignOn Service endpoints</div>';
            $result[] = $SSOPart;
            $result[] = '';
            // $slotmpl
            /**
             * IDP SingleLogoutService
             */
            //$IDPSLOPart = '<fieldset><legend>' . lang('IdPSLO') . '</legend><ol>';
            $IDPSLOPart = '';
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
            $result[] = '';
            $result[] = '<div class="langgroup">Single Logout Service endpoints</div>';
            $result[] = $IDPSLOPart;
            $result[] = '';

            /**
             * generate IDP ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>' . lang('ArtifactResolutionService') . ' <small><i>IDPSSODescriptor</i></small></legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nidpartifactbtn">' . lang('rr_addnewidpartifactres') . '</button></li>';
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
            $result[] = '<fieldset><legend>' . lang('atributeauthoritydescriptor') . '</legend><ol>' . implode('', $aalo) . '</ol></fieldset>';

            /**
             * end AttributeAuthorityDescriptor Location
             */
        }
        if ($enttype != 'IDP')
        {
            /**
             * generate ACS part
             */
            $ACSPart = '<fieldset><legend>' . lang('assertionconsumerservice') . '</legend><ol>';
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
                    $r .= '<li>' . form_label('' . lang('rr_bindingname') . '', 'f[srv][AssertionConsumerService][' . $k4 . '][bind]');
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nacsbtn">' . lang('addnewacs') . '</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end ACS part
             */
            /**
             * generate ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>' . lang('artifactresolutionservice') . ' <small><i>SPSSODescriptor</i></small></legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nspartifactbtn">' . lang('addnewartresservice') . '</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end SPArtifactResolutionService part
             */
            /**
             * start SP SingleLogoutService
             */
            $SPSLOPart = '<fieldset><legend>' . lang('singlelogoutservice') . ' <small><i>' . lang('serviceprovider') . '</i></small></legend><ol>';
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
            $RequestInitiatorPart = '<fieldset><legend>' . lang('requestinitatorlocations') . '</legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nribtn">' . lang('addnewreqinit') . '</button></li>';
            $RequestInitiatorPart .= $newelement . '</ol><fieldset>';
            $result[] = $RequestInitiatorPart;
            /**
             * end RequestInitiator
             */
            /**
             * start DiscoveryResponse
             */
            $DiscoverResponsePart = '<fieldset><legend>' . lang('discoveryresponselocations') . '</legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="ndrbtn">' . lang('addnewds') . '</button></li>';
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
            $Part = '<fieldset><legend>' . lang('idpcerts') . ' <small><i>IDPSSODesciptor</i></small></legend><ol>';
            $idpssocerts = array();
            // start CERTS IDPSSODescriptor
            if($sessform)
            {
               if(isset($ses['crt']['idpsso']))
               {
                   foreach($ses['crt']['idpsso'] as $key=>$value)
                   {
                        $idpssocerts[] = $this->_genCertFieldFromSession($certObj = null, $key, $value, "f[crt][idpsso]", 'idpsso',TRUE);
                   }
               }
              
            }
            else
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nidpssocert">' . lang('addnewcert') . ' ' . lang('for') . ' IDPSSODescriptor</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;

            // end CERTS IDPSSODescriptor
            $Part = '<fieldset><legend>' . lang('idpcerts') . ' <small><i>AttributeAuthorityDesciptor</i></small></legend><ol>';
            $aacerts = array();
            // start CERTS AttributeAuthorityDescriptor
            if($sessform)
            {
               if(isset($ses['crt']['aa']))
               {
                   foreach($ses['crt']['aa'] as $key=>$value)
                   {
                        $aacerts[] = $this->_genCertFieldFromSession($certObj = null, $key, $value, "f[crt][aa]", 'aa',TRUE);
                   }
               }

            }
            else
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="naacert">' . lang('addnewcert') . ' ' . lang('for') . ' AttributeAuthorityDescriptor</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
            $Part = '';

            // end CERTS AttributeAuthorityDescriptor
        }
        if (strcmp($enttype, 'IDP') != 0)
        {
            $Part = '<fieldset><legend>' . lang('rr_certificates') . ' <small><i>' . lang('serviceprovider') . '</i></small></legend><ol>';
            $spssocerts = array();
            if($sessform)
            {
               if(isset($ses['crt']['spsso']))
               {
                   foreach($ses['crt']['spsso'] as $key=>$value)
                   {
                        $spssocerts[] = $this->_genCertFieldFromSession($certObj = null, $key, $value, "f[crt][spsso]", 'spsso',TRUE);
                   }
               }

            }
            else
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nspssocert">' . lang('addnewcert') . '</button></li>';
            $Part .= $newelement . '</ol></fieldset>';
            $result[] = $Part;
        }


        return $result;
    }

    private function _genCertFieldFromSession($certObj = null, $idCert, $sessionCert, $sessionNamePart, $type, $showremove=false)
    {
        $name = $sessionNamePart;
        $certuse = $sessionCert['usage'];
        if (empty($certuse))
        {
            $certuse = 'both';
        }
        $crtid = $idCert;
        $readonly = false;
        if(is_numeric($crtid))
        {
           $readonly = true;
        }
        $certdata = set_value( ''.$name . '[' . $crtid . '][certdata]', getPEM($sessionCert['certdata']));
        if(!empty($certdata))
        {
           $keysize= getKeysize($certdata);
        }
        if(empty($keysize))
        {
            $keysize = lang('unknown');
        }
        $row = '<div class="certgroup">';
        $row .= '<li>' . form_label(lang('rr_certificatetype'), '' . $name . '[' . $crtid . '][type]');
        $row .= form_dropdown('' . $name . '[' . $crtid . '][type]', array('x509' => 'x509'), set_value($sessionCert['type'])) . '</li>';
        $row .= '<li>' . form_label(lang('rr_certificateuse'), '' . $name . '[' . $crtid . '][usage]');
        $row .= '<span>' . form_dropdown('' . $name . '[' . $crtid . '][usage]', array('signing' => '' . lang('rr_certsigning') . '', 'encryption' => '' . lang('rr_certencryption') . '', 'both' => '' . lang('rr_certsignandencr') . ''), $certuse) . '</li>';

        $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), '' . $name . '[' . $crtid . '][keyname]');
        $row .= form_input(array(
            'name' => '' . $name . '[' . $crtid . '][keyname]',
            'id' => '' . $name . '[' . $crtid . '][keyname]',
            'value' => '' . $sessionCert['keyname'] . ''));
        $row .='</li>';
        $row .= '<li>'.form_label(lang('rr_computedkeysize') , 'keysize').'<input type="text" name="keysize" value="'.$keysize.'" disabled="disabled" style="font-weight: bold;background-color: transparent;min-width: 50px"></li>';
        $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), '' . $name . '[' . $crtid . '][certdata]');

        $textarea = array(
                    'name' => '' . $name . '[' . $crtid . '][certdata]',
                    'id' => '' . $name . '[' . $crtid . '][certdata]',
                    'cols' => 55,
                    'rows' => 30,
                    'class' => 'certdata ',
                    'value' => '' . $certdata . '',
                );
        if($readonly)
        {
            $textarea['readonly'] = 'true';
        }

        $row .= form_textarea($textarea) . '</li>';
        if ($showremove)
        {
            $row .= '<li class="rmelbtn fromprevtoright"> <button type="button" class="btn certificaterm" name="certificate" value="' . $crtid . '">' . lang('btn_removecert') . '</button></li>';
        }
        $row .= '<li><br /></li>';

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
        if(!empty($certdata))
        {
           $keysize= getKeysize($certdata);
        }
        if(empty($keysize))
        {
            $keysize = lang('unknown');
        }

        $crtid = $cert->getId();
        $readonly = false;
        if(is_numeric($crtid))
        {
           $readonly =true;
        }
        $row = '<div class="certgroup">';
        $row .= '<li>' . form_label(lang('rr_certificatetype'), '' . $name . '[' . $crtid . '][type]');
        $row .= form_dropdown('' . $name . '[' . $crtid . '][type]', array('x509' => 'x509'), set_value($cert->getType())) . '</li>';
        $row .= '<li>' . form_label(lang('rr_certificateuse'), '' . $name . '[' . $crtid . '][usage]');
        $row .= '<span>' . form_dropdown('' . $name . '[' . $crtid . '][usage]', array('signing' => '' . lang('rr_certsigning') . '', 'encryption' => '' . lang('rr_certencryption') . '', 'both' => '' . lang('rr_certsignandencr') . ''), $certuse) . '</li>';

        $row .= '<li>' . form_label(lang('rr_keyname') . showBubbleHelp(lang('rhelp_multikeynames')), '' . $name . '[' . $crtid . '][keyname]');
        $row .= form_input(array(
            'name' => '' . $name . '[' . $crtid . '][keyname]',
            'id' => '' . $name . '[' . $crtid . '][keyname]',
            'value' => '' . $cert->getKeyname() . ''));
        $row .='</li>';
        $row .= '<li>'.form_label(lang('rr_computedkeysize') , 'keysize').'<input type="text" name="keysize" value="'.$keysize.'" disabled="disabled" style="font-weight: bold;background-color: transparent;min-width: 50px"></li>';
        $row .= '<li>' . form_label(lang('rr_certificate') . showBubbleHelp(lang('rhelp_cert')), '' . $name . '[' . $crtid . '][certdata]');
        $textarea = array(
                    'name' => '' . $name . '[' . $crtid . '][certdata]',
                    'id' => '' . $name . '[' . $crtid . '][certdata]',
                    'cols' => 55,
                    'rows' => 30,
                    'class' => 'certdata ',
                    'value' => '' . $certdata . '',
                );
        if($readonly)
        {
            $textarea['readonly']='true';
        }
        
        $row .= form_textarea($textarea) . '</li>';
        if ($showremove)
        {
            $row .= '<li class="rmelbtn fromprevtoright"> <button type="button" class="btn certificaterm" name="certificate" value="' . $crtid . '">' . lang('btn_removecert') . '</button></li>';
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

    public function NgenerateSAMLTab(models\Provider $ent, $ses = null)
    {
        $sessform = FALSE;
        $allowednameids = getAllowedNameId();
        $class_ent = '';
        if (!empty($ses) && is_array($ses))
        {
            $sessform = TRUE;
        }
        $t1 = set_value('f[entityid]', $ent->getEntityId());
        if ($sessform)
        {
            if (array_key_exists('entityid', $ses))
            {
                if ($t1 != $ses['entityid'])
                {
                    $class_ent = 'notice';
                    $t1 = $ses['entityid'];
                }
            }
        }
        $result = array();
        $result[] = '';
        if (!in_array('entityid', $this->disallowedparts))
        {
            $result[] = form_label(lang('rr_entityid'), 'f[entityid]') . form_input(array('id' => 'f[entityid]', 'class' => $class_ent, 'name' => 'f[entityid]', 'required' => 'required', 'value' => $t1));
        } else
        {
            $result[] = form_label(lang('rr_entityid'), 'f[entityid]') . form_input(array('id' => 'f[entityid]', 'class' => $class_ent, 'name' => 'f[entityid]', 'required' => 'required', 'readonly' => 'readonly', 'value' => $t1));
        }
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
            $result[] = '<div class="section">'.lang('identityprovider').'</div>';
            /**
             * generate SSO part
             */
            $SSOPart = '';
            if (array_key_exists('SingleSignOnService', $g))
            {
                foreach ($g['SingleSignOnService'] as $k1 => $v1)
                {
                    if ($sessform && isset($ses['srv']['SingleSignOnService']['' . $v1->getId() . '']['url']))
                    {
                        $t1 = $ses['srv']['SingleSignOnService']['' . $v1->getId() . '']['url'];
                    } else
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
            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('rr_srvssoends').'</div>';
            $result[] = $SSOPart;
            $result[] = '';
            // $slotmpl
            /**
             * IDP SingleLogoutService
             */
            //$IDPSLOPart = '<fieldset><legend>' . lang('IdPSLO') . '</legend><ol>';
            $IDPSLOPart = '';
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
            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('rr_srvsloends').'</div>';
            $result[] = $IDPSLOPart;
            $result[] = '';

            /**
             * generate IDP ArtifactResolutionService part
             */
            $ACSPart = '';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nidpartifactbtn">' . lang('rr_addnewidpartifactres') . '</button></li>';
            $ACSPart .= $newelement . '';
            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('rr_srvartresends').'</div>';
            $result[] = $ACSPart;
            $result[] ='';

            /**
             * start protocols enumeration
             */
            $r = '';
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
            $r .= '<li class="' . $idpssonotice . '">';
            $r .= '<ul class="checkboxlist">';
            foreach ($allowedoptions as $a)
            {
                $is = FALSE;
                if (in_array($a, $selected_options))
                {
                    $is = TRUE;
                }
                $r .= '<li>' . form_checkbox(array('name' => 'f[prot][idpsso][]', 'id' => 'f[prot][idpsso][]', 'value' => $a, 'checked' => $is)) . $a . '</li>';
            }
            $r .= '</ul></li>';
            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('rr_protenums').'</div>';
            $result[] = $r;
            $result[] = '';


             /**
              * end protocols enumeration
              */

            /**
             * start nameids
             */
            $r = '';
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
            $r .= '<li>' . form_label('', 'f[nameids][idpsso][]') . '<div class="nsortable ' . $idpssonameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
            }
            $r .= '</div></li>';
            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('rr_supnameids').'</div>';
            $result[] = $r;
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
                $result[] = '' . form_label( lang('rr_scope') , 'f[scopes][idpsso]') . form_input(array(
                            'name' => 'f[scopes][idpsso]',
                            'id' => 'f[scopes][idpsso]',
                            'readonly' => 'readonly',
                            'value' => $scopessovalue,
                            'class' => $scopeidpssonotice,
                        )) . '';

            }
            else
            {
                $result[] = '' . form_label(lang('rr_scope') , 'f[scopes][idpsso]') . form_input(array(
                            'name' => 'f[scopes][idpsso]',
                            'id' => 'f[scopes][idpsso]',
                            'value' => $scopessovalue,
                            'class' => $scopeidpssonotice,
                        )) . '';
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

            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('atributeauthoritydescriptor').'</div>';
            $result[] = implode('', $aalo);
            $result[] = '';
            /**
             * end AttributeAuthorityDescriptor Location
             */
            $r = '';
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
            $r .= '<li class="' . $aanotice . '">';
            $r .= '<ul class="checkboxlist">';
            foreach ($allowedoptions as $a)
            {
                $is = FALSE;
                if (in_array($a, $selected_options))
                {
                    $is = TRUE;
                }
                $r .= '<li>' . form_checkbox(array('name' => 'f[prot][aa][]', 'id' => 'f[prot][aa][]', 'value' => $a, 'checked' => $is)) . $a . '</li>';
            }
            $r .= '</ul>';
            $r .= '</li>';

            $r .= '';
            $result[] = '';
            $result[] = '<div class="langgroup">'.lang('rr_protenums').'</div>';
            $result[] = $r;
            $result[] = '';


            /**
             * start nameids for AttributeAuthorityDescriptor 
             */
            $r = '';
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
            $r .= '<li>' . form_label('', 'f[nameids][idpaa][]') . '<div class="nsortable ' . $idpaanameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
            }
            $r .= '</div></li>';
            $result[] = '';
            $result[] = '<div class="langgroup">Supported name identifiers</div>';
            $result[] = $r;
            $result[] = '';
            /**
             * end nameids for IDPSSODescriptor
             */



            /**
             * Scopes
             */
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
                $result[] = '' . form_label( lang('rr_scope') , 'f[scopes][aa]') . form_input(array(
                            'name' => 'f[scopes][aa]',
                            'id' => 'f[scopes][aa]',
                            'readonly' => 'readonly',
                            'value' => $scopeaavalue,
                            'class' => $scopeaanotice,
                        )) . '';
            } else
            {
                $result[] = '' . form_label(lang('rr_scope') , 'f[scopes][aa]') . form_input(array(
                            'name' => 'f[scopes][aa]',
                            'id' => 'f[scopes][aa]',
                            'value' => $scopeaavalue,
                            'class' => $scopeaanotice,
                        )) . '';
            }
        }
        if ($enttype != 'IDP')
        {
            $result[] = '<div class="section">Service Provider</div>';
            /**
             * generate ACS part
             */
            $ACSPart = '<fieldset><legend>' . lang('assertionconsumerservice') . '</legend><ol>';
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
                    $r .= '<li>' . form_label('' . lang('rr_bindingname') . '', 'f[srv][AssertionConsumerService][' . $k4 . '][bind]');
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nacsbtn">' . lang('addnewacs') . '</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end ACS part
             */
            /**
             * generate ArtifactResolutionService part
             */
            $ACSPart = '<fieldset><legend>' . lang('artifactresolutionservice') . ' <small><i>SPSSODescriptor</i></small></legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nspartifactbtn">' . lang('addnewartresservice') . '</button></li>';
            $ACSPart .= $newelement . '</ol></fieldset>';
            $result[] = $ACSPart;
            /**
             * end SPArtifactResolutionService part
             */
            /**
             * start SP SingleLogoutService
             */
            $SPSLOPart = '<fieldset><legend>' . lang('singlelogoutservice') . ' <small><i>' . lang('serviceprovider') . '</i></small></legend><ol>';
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
            $RequestInitiatorPart = '<fieldset><legend>' . lang('requestinitatorlocations') . '</legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="nribtn">' . lang('addnewreqinit') . '</button></li>';
            $RequestInitiatorPart .= $newelement . '</ol><fieldset>';
            $result[] = $RequestInitiatorPart;
            /**
             * end RequestInitiator
             */
            /**
             * start DiscoveryResponse
             */
            $DiscoverResponsePart = '<fieldset><legend>' . lang('discoveryresponselocations') . '</legend><ol>';
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
            $newelement = '<li><button class="editbutton addicon smallerbtn" type="button" id="ndrbtn">' . lang('addnewds') . '</button></li>';
            $DiscoverResponsePart .= $newelement . '</ol><fieldset>';
            $result[] = $DiscoverResponsePart;

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
            $r = '';
            $spssoprotocols = $ent->getProtocolSupport('spsso');
            $selected_options = array();
            $spssonotice = '';
            if ($sessform && isset($entsession['prot']['spsso']) && is_array($entsession['prot']['spsso']))
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
            $r .= '<li class="' . $spssonotice . '">';
            $r .= '<ul class="checkboxlist">';
            foreach ($allowedoptions as $a)
            {
                $is = FALSE;
                if (in_array($a, $selected_options))
                {
                    $is = TRUE;
                }
                $r .= '<li>' . form_checkbox(array('name' => 'f[prot][spsso][]', 'id' => 'f[prot][spsso][]', 'value' => $a, 'checked' => $is)) . $a . '</li>';
            }
            $r .= '</ul>';
            $r .= '</li>';
            $result[] = '';
            $result[] = '<div class="langgroup">Supported protocol enumeration</div>';
            $result[] = $r;
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
            $r .= '<li>' . form_label('', 'f[nameids][spsso][]') . '<div class="nsortable ' . $spssonameidnotice . '">';
            foreach ($chp as $n)
            {
                $r .= '<span>' . form_checkbox($n) . $n['value'] . '</span>';
            }
            $r .= '</div></li>';
            $result[] = '';
            $result[] = '<div class="langgroup">Supported name identifiers</div>';
            $result[] = $r;
            $result[] = '';
            
            /**
             * end nameids
             */

        }

        foreach ($g as $k => $v)
        {
            
        }


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




        $result[] = '';
        $result[] = '<div class="langgroup">'.lang('e_globalprivacyurl').'<i><small> ('.lang('rr_default').') '.lang('rr_optional').'</small></i>'.showBubbleHelp('' . lang('rhelp_privacydefault1') . '').'</div>';
        $r = '';
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
        $r .= '';
        $result[] = $r;
        $result[] = '';



        if ($type != 'SP')
        {
            $result[] = '<div class="section">' . lang('identityprovider') . '</div>';
            /**
             * start display
             */
            $result[] = '';
            $result[] = '<div class="langgroup">'. lang('e_idpservicename').'</div>';
            $r ='';
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
                    $r .= '<li>';
                    $r .= form_label( $langtxt , 'f[uii][idpsso][displayname][' . $lang . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][displayname][' . $lang . ']',
                                        'id' => 'f[uii][idpsso][displayname][' . $lang . ']',
                                        'value' => $currval,
                                        'class' => $displaynotice,
                                    )
                    );

                    $r .= ' <button type="button" class="btn langinputrm" name="uiiidpssodisplayname" value="' . $lang . '">'.lang('rr_remove').'</button></li>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['displayname']) && is_array($ses['uii']['idpsso']['displayname']))
            {
                foreach ($ses['uii']['idpsso']['displayname'] as $key => $value)
                {
                    if (!array_key_exists($key, $langs))
                    {
                        log_message('error', 'Language code ' . $key . ' is not allowed for row (extendmetadaa) with id:' . $v1->getId());
                         $langtxt = $key;
                    } else
                    {
                        $langtxt = $langs['' . $key . ''];
                    }
                    $r .= '<li>';
                    $r .= form_label( $langtxt , 'f[uii][idpsso][displayname][' . $key . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][displayname][' . $key . ']',
                                        'id' => 'f[uii][idpsso][displayname][' . $key . ']',
                                        'value' => set_value('f[uii][idpsso][displayname][' . $key . ']', $value),
                                        'class' => 'notice',
                                    )
                    );

                    $r .= ' <button type="button" class="btn langinputrm" name="uiiidpssodisplayname" value="' . $key . '">'.lang('rr_remove').'</button></li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiidisplayadd">' . form_dropdown('idpuiidisplaylangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="idpadduiidisplay" name="idpadduiidisplay" value="idpadduiidisplay" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span></li>';
          //  $r .= form_fieldset_close();
            $result[] = $r;
            $result[] = '';

            /**
             * end display
             */
            /**
             * start helpdesk 
             */
            $result[] = '';
            //$r = form_fieldset('' . lang('e_idpserviceinfourl') . '');
            $result[] = '<div class="langgroup">'.lang('e_idpserviceinfourl').'</div>';
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
                    $r .= '<li>';
                    $r .= form_label($langtxt, 'f[uii][idpsso][helpdesk][' . $lang . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][helpdesk][' . $lang . ']',
                                        'id' => 'f[uii][idpsso][helpdesk][' . $lang . ']',
                                        'value' => $currval,
                                        'class' => $displaynotice,
                                    )
                    );

                    $r .= ' <button type="button" class="btn langinputrm" name="uiiidpssohelpdesk" value="' . $lang . '">'.lang('rr_remove').'</button></li>';
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
                    $r .= '<li>';
                    $r .= form_label( $langtxt, 'f[uii][idpsso][helpdesk][' . $key . ']') . form_input(
                                    array(
                                        'name' => 'f[uii][idpsso][helpdesk][' . $key . ']',
                                        'id' => 'f[uii][idpsso][helpdesk][' . $key . ']',
                                        'value' => set_value('f[uii][idpsso][helpdesk][' . $key . ']', $value),
                                        'class' => 'notice',
                                    )
                    );

                    $r .= '  <button type="button" class="btn langinputrm" name="uiiidpssohelpdesk" value="' . $key . '">'.lang('rr_remove').'</button></li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiihelpdeskadd">' . form_dropdown('idpuiihelpdesklangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="idpadduiihelpdesk" name="idpadduiihelpdesk" value="idpadduiihelpdesk" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span></li>';
         //   $r .= form_fieldset_close();
            $result[] = $r;
            $result[] ='';

            /**
             * end helpdesk
             */
            /**
             * start description
             */
            $result[] = '';
            //$r = form_fieldset('' . lang('e_idpservicedesc') . '');
            $r = '';
            $result[] = '<div class="langgroup">'.lang('e_idpservicedesc').'</div>';
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
                    $r .= '<li>';
                    $r .= form_label($langtxt, 'f[uii][idpsso][desc][' . $lang . ']') . form_textarea(
                                    array(
                                        'name' => 'f[uii][idpsso][desc][' . $lang . ']',
                                        'id' => 'f[uii][idpsso][desc][' . $lang . ']',
                                        'value' => $currval,
                                        'class' => $displaynotice,
                                    )
                    );

                    $r .= ' <button type="button" class="btn langinputrm" name="lhelpdesk" value="' . $lang . '">'.lang('rr_remove').'</button></li>';
                }
            }
            if ($sessform && isset($ses['uii']['idpsso']['desc']) && is_array($ses['uii']['idpsso']['desc']))
            {
                foreach ($ses['uii']['idpsso']['desc'] as $key => $value)
                {
                    $r .= '<li>';
                    $r .= form_label( $key, 'f[uii][idpsso][desc][' . $key . ']') . form_textarea(
                                    array(
                                        'name' => 'f[uii][idpsso][desc][' . $key . ']',
                                        'id' => 'f[uii][idpsso][desc][' . $key . ']',
                                        'value' => set_value('f[uii][idpsso][desc][' . $key . ']', $value),
                                        'class' => 'notice',
                                    )
                    );

                    $r .= ' <button type="button" class="btn langinputrm" name="lhelpdesk" value="' . $key . '">'.lang('rr_remove').'</button></li>';
                    unset($langsdisplaynames['' . $key . '']);
                }
            }
            $r .= '<li><span class="idpuiidescadd">' . form_dropdown('idpuiidesclangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="idpadduiidesc" name="idpadduiidesc" value="' . lang('rr_description') . '" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span></li>';
            //$r .= form_fieldset_close();
            $result[] = $r;
            $result[] ='';
            /**
             * end description 
             */

            /**
             * start privacy url
             */
            $result[] = '';
            $r = '';
            $result[] = '<div class="langgroup">' . lang('e_idpserviceprivacyurl') . ' </div>';
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
                $r .= '<li class="localized">';
                $r .= form_label( $langsdisplaynames['' . $k4 . ''] , 'f[prvurl][idpsso][' . $k4 . ']');
                $r .= form_input(array('id' => 'f[prvurl][idpsso][' . $k4 . ']', 'name' => 'f[prvurl][idpsso][' . $k4 . ']', 'value' => $v4['url']));
                $r .=' <button type="button" class="btn langinputrm" name="prvurlidpsso" value="' . $k4 . '">'.lang('rr_remove').'</button></li>';
            }
            $idpssolangcodes = array_diff_key($langsdisplaynames, $sorig);
            $r .= '<li class="addlprivacyurlidpsso localized">';

            $r .= form_dropdown('langcode', MY_Controller::$langselect, $this->defaultlangselect);
            $r .= '<button type="button" id="addlprivacyurlidpsso" name="addlprivacyurlidpsso" value="addlprivacyurlidpsso" class="editbutton addicon smallerbtn">'  . lang('btnaddinlang') . '</button>';
            $r .= '</li>';

            $result[] = $r;
            $result[] = '';

            /**
             * end privacy url
             */
           
        }
        if ($type != 'IDP')
        {
            $result[] = '<div class="section">' . lang('serviceprovider') . '</div>';
            {


                /**
                 * start display
                 */
                $result[] = '';
                //$r = form_fieldset('' . lang('e_spservicename') . '');
                $result[] = '<div class="langgroup">'.lang('e_spservicename').'</div>';
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
                        $r .= '<li>';
                        $r .= form_label( $langtxt, 'f[uii][spsso][displayname][' . $lang . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][displayname][' . $lang . ']',
                                            'id' => 'f[uii][spsso][displayname][' . $lang . ']',
                                            'value' => $currval,
                                            'class' => $displaynotice,
                                        )
                        );

                        $r .= ' <button type="button" class="btn langinputrm" name="uiispssodisplayname" value="' . $lang . '">'.lang('rr_remove').'</button></li>';
                    }
                }
                if ($sessform && isset($ses['uii']['spsso']['displayname']) && is_array($ses['uii']['spsso']['displayname']))
                {
                    foreach ($ses['uii']['spsso']['displayname'] as $key => $value)
                    {
                        $r .= '<li>';
                        $r .= form_label( $key, 'f[uii][spsso][displayname][' . $key . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][displayname][' . $key . ']',
                                            'id' => 'f[uii][spsso][displayname][' . $key . ']',
                                            'value' => set_value('f[uii][spsso][displayname][' . $key . ']', $value),
                                            'class' => 'notice',
                                        )
                        );

                        $r .= ' <button type="button" class="btn langinputrm" name="uiispssodisplayname" value="' . $key . '">'.lang('rr_remove').'</button></li>';
                        unset($langsdisplaynames['' . $key . '']);
                    }
                }
                $r .= '<li><span class="spuiidisplayadd">' . form_dropdown('spuiidisplaylangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="spadduiidisplay" name="spadduiidisplay" value="spadduiidisplay" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span></li>';
                //$r .= form_fieldset_close();
                $result[] = $r;
                $result[] = '';

                /**
                 * end display
                 */
                /**
                 * start helpdesk 
                 */
                $result[] = '';
                //$r = form_fieldset('' . lang('e_spserviceinfourl') . '');
                $result[] = '<div class="langgroup">'.lang('e_spserviceinfourl').'</div>';
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
                        $r .= '<li>';
                        $r .= form_label( $langtxt , 'f[uii][spsso][helpdesk][' . $lang . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][helpdesk][' . $lang . ']',
                                            'id' => 'f[uii][spsso][helpdesk][' . $lang . ']',
                                            'value' => $currval,
                                            'class' => $displaynotice,
                                        )
                        );

                        $r .= '  <button type="button" class="btn langinputrm" name="uiispssohelpdesk" value="' . $lang . '">'.lang('rr_remove').'</button></li>';
                    }
                }
                if ($sessform && isset($ses['uii']['spsso']['helpdesk']) && is_array($ses['uii']['spsso']['helpdesk']))
                {
                    foreach ($ses['uii']['spsso']['helpdesk'] as $key => $value)
                    {
                        $r .= '<li>';
                        $r .= form_label( $key , 'f[uii][spsso][helpdesk][' . $key . ']') . form_input(
                                        array(
                                            'name' => 'f[uii][spsso][helpdesk][' . $key . ']',
                                            'id' => 'f[uii][spsso][helpdesk][' . $key . ']',
                                            'value' => set_value('f[uii][spsso][helpdesk][' . $key . ']', $value),
                                            'class' => 'notice',
                                        )
                        );

                        $r .= ' <button type="button" class="btn langinputrm" name="uiispssohelpdesk" value="' . $key . '">'.lang('rr_remove').'</button></li>';
                        unset($langsdisplaynames['' . $key . '']);
                    }
                }
                $r .= '<li><span class="spuiihelpdeskadd">' . form_dropdown('spuiihelpdesklangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="spadduiihelpdesk" name="spadduiihelpdesk" value="spadduiihelpdesk" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span></li>';
                //$r .= form_fieldset_close();
                $result[] = $r;
                $result[] = '';
                /**
                 * end helpdesk
                 */
                /**
                 * start description
                 */
                $result[] = '';
                //$r = form_fieldset('' . lang('e_spservicedesc') . '');
                $result[] = '<div class="langgroup">'.lang('e_spservicedesc').'</div>';
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
                        $r .= '<li>';
                        $r .= form_label( $langtxt , 'f[uii][spsso][desc][' . $lang . ']') . form_textarea(
                                        array(
                                            'name' => 'f[uii][spsso][desc][' . $lang . ']',
                                            'id' => 'f[uii][spsso][desc][' . $lang . ']',
                                            'value' => $currval,
                                            'class' => $displaynotice,
                                        )
                        );

                        $r .= ' <button type="button" class="btn langinputrm" name="uiispssodesc" value="' . $lang . '">'.lang('rr_remove').'</button></li>';
                    }
                }
                if ($sessform && isset($ses['uii']['spsso']['desc']) && is_array($ses['uii']['spsso']['desc']))
                {
                    foreach ($ses['uii']['spsso']['desc'] as $key => $value)
                    {
                        $r .= '<li>';
                        $r .= form_label( $key, 'f[uii][spsso][desc][' . $key . ']') . form_textarea(
                                        array(
                                            'name' => 'f[uii][spsso][desc][' . $key . ']',
                                            'id' => 'f[uii][spsso][desc][' . $key . ']',
                                            'value' => set_value('f[uii][spsso][desc][' . $key . ']', $value),
                                            'class' => 'notice',
                                        )
                        );

                        $r .= ' <button type="button" class="btn langinputrm" name="uiispssodesc" value="' . $key . '">'.lang('rr_remove').'</button></li>';
                        unset($langsdisplaynames['' . $key . '']);
                    }
                }
                $r .= '<li><span class="spuiidescadd">' . form_dropdown('spuiidesclangcode', MY_Controller::$langselect, $this->defaultlangselect) . '<button type="button" id="spadduiidesc" name="spadduiidesc" value="spadduiidesc" class="editbutton addicon smallerbtn">' . lang('btnaddinlang') . '</button></span></li>';
                //$r .= form_fieldset_close();
                $result[] = $r;
                $result[] = '';
                /**
                 * end description 
                 */
            /**
             * start privacy url
             */
            $result[] = '';
            $r = '';
            $result[] = '<div class="langgroup">' . lang('e_spserviceprivacyurl') . ' </div>';
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
                $r .= '<li class="localized">';
                $r .= form_label( $langsdisplaynames['' . $k4 . ''] , 'f[prvurl][spsso][' . $k4 . ']');
                $r .= form_input(array('id' => 'f[prvurl][spsso][' . $k4 . ']', 'name' => 'f[prvurl][spsso][' . $k4 . ']', 'value' => $v4['url']));
                $r .=' <button type="button" class="btn langinputrm" name="prvurlspsso" value="' . $k4 . '">'.lang('rr_remove').'</button></li>';
            }
            $idpssolangcodes = array_diff_key($langsdisplaynames, $sorig);
            $r .= '<li class="addlprivacyurlspsso localized">';

            $r .= form_dropdown('langcode', MY_Controller::$langselect, $this->defaultlangselect);
            $r .= '<button type="button" id="addlprivacyurlspsso" name="addlprivacyurlspsso" value="addlprivacyurlspsso" class="editbutton addicon smallerbtn">'  . lang('btnaddinlang') . '</button>';
            $r .= '</li>';

            $result[] = $r;
            $result[] = '';

            /**
             * end privacy url
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
        $f .= form_fieldset(lang('rr_basicinformation'));
        $f .='<ol><li>' . form_label(lang('rr_fed_urn'), 'urn');
        $f .= form_input('urn', set_value('urn', $federation->getUrn())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_fed_publisher'), 'publisher'). form_input('publisher', set_value('publisher', $federation->getPublisher())) . '</li>';
        $f .= '<li>' . form_label(lang('rr_isfedpublic') . ' ' . showBubbleHelp(lang('rhelppublicfed')), 'ispublic') . form_checkbox('ispublic', 'accept', set_value('ispublic', $federation->getPublic())) . '</li>';
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
        $result .= '<ol><li>' . form_label(lang('rr_setpolicy'), 'policy');
        $result .= form_dropdown('policy', $this->ci->config->item('policy_dropdown'), $arp->getPolicy());
        $result .= '</li></ol>' . form_fieldset_close();
        return $result;
    }

    public function generateAddCoc()
    {
        $r = form_fieldset('');
        $r .= '<ol>';
        $r .= '<li>' . form_label(lang('entcat_enabled'), 'cenabled') . form_checkbox('cenabled', 'accept') . '</li>';
        $r .= '<li>' . form_label(lang('entcat_shortname'), 'name') . form_input('name', set_value('name')) . '</li>';
        $r .= '<li>' . form_label(lang('entcat_url'), 'url') . form_input('url', set_value('url')) . '</li>';
        $r .= '<li>' . form_label(lang('entcat_description'), 'description') . form_textarea('description', set_value('description')) . '</li>';
        $r .= '</ol>';
        $r .= form_fieldset_close();
        return $r;
    }

    public function generateEditCoc(models\Coc $coc)
    {
        $r = form_fieldset('');
        $r .= '<ol>';
        $r .= '<li>' . form_label(lang('entcat_enabled'), 'cenabled') . form_checkbox('cenabled', 'accept', set_value('cenabled', $coc->getAvailable())) . '</li>';
        $r .= '<li>' . form_label(lang('entcat_shortname'), 'name') . form_input('name', set_value('name', $coc->getName())) . '</li>';
        $r .= '<li>' . form_label(lang('entcat_url'), 'url') . form_input('url', set_value('url', $coc->getUrl())) . '</li>';
        $r .= '<li>' . form_label(lang('entcat_description'), 'description') . form_textarea('description', set_value('description', $coc->getDescription())) . '</li>';
        $r .= '</ol>';
        $r .= form_fieldset_close();
        return $r;
    }

}
