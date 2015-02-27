<?php

class Providerformelements
{
    protected $ci;
    protected $em;
    protected $disallowedparts = array();
    protected $defaultlangselect = 'en';
    protected $langs;
    protected $isAdmin;
    protected $ent;
    protected $ses = null;

    function __construct($params)
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        if (!is_array($params) || !isset($params['provider'])) {
            return null;
        }
        $this->ent = $params['provider'];
        $this->ses = $params['session'];
        $this->ci->load->helper(array('form', 'shortcodes'));
        $this->langs = languagesCodes();
        $a = $this->ci->config->item('langselectdefault');
        if (!empty($a)) {
            $this->defaultlangselect = $a;
        }
        $this->isAdmin = $this->ci->j_auth->isAdministrator();
        $disallowedparts = $this->ci->config->item('entpartschangesdisallowed');
        if ($this->isAdmin || empty($disallowedparts) || !is_array($disallowedparts)) {
            $disallowedparts = array();
        }
        $this->disallowedparts = $disallowedparts;
    }

    private function _generateLangAddButton($spanclass, $dropname, $langs, $buttonvalue)
    {
        $r = '<span class="' . $spanclass . '"><div class="small-6 medium-3 large-3 columns">' . form_dropdown('langcodes', $langs, $this->defaultlangselect) . '</div><div class="small-6 large-4 end columns"><button type="button" name="addinnewlang" value="' . $buttonvalue . '" class="editbutton addicon smallerbtn button inline left tiny">' . lang('btnaddinlang') . '</button></div></span>';
        return $r;
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
            ) . '</div><div class="small-3 large-2 columns"><button type="button" class="btn langinputrm inline left button tiny alert" name="lname" value="' . $buttonvalue . '">' . lang('rr_remove') . '</button></div>';

        return $result;
    }





    public function generateOtherLinksTab()
    {
        $result = array();
        $base = base_url();
        $t = $this->ent->getType();
        $id = $this->ent->getId();
        if ($t === 'IDP') {
            $result = array(
                anchor($base . 'manage/logomngmt/provider/idp/' . $id . '', '' . lang('rr_logos') . ''),
                anchor($base . 'manage/supported_attributes/idp/' . $id . '', '' . lang('rr_supportedattributes') . ''),
                anchor($base . 'manage/attributepolicy/globals/' . $id . '', '' . lang('rr_attributepolicy') . ''),
                anchor($base . 'manage/arpsexcl/idp/' . $id . '', '' . lang('srvs_excluded_from_arp') . '')
            );
        } elseif ($t === 'SP') {
            $result = array(
                anchor($base . 'manage/logomngmt/provider/sp/' . $id . '', '' . lang('rr_logos') . ''),
          //      anchor($base . 'manage/attribute_requirement/sp/' . $id . '', '' . lang('rr_requiredattributes') . '')
            );
        } else {
            $result = array(
                anchor($base . 'manage/logomngmt/provider/idp/' . $id . '', '' . lang('rr_logos') . ' (' . lang('identityprovider') . ')'),
                anchor($base . 'manage/logomngmt/provider/sp/' . $id . '', '' . lang('rr_logos') . ' (' . lang('serviceprovider') . ')'),
                anchor($base . 'manage/supported_attributes/idp/' . $id . '', '' . lang('rr_supportedattributes') . ''),
                anchor($base . 'manage/attributepolicy/globals/' . $id . '', '' . lang('rr_attributepolicy') . ''),
                anchor($base . 'manage/arpsexcl/idp/' . $id . '', '' . lang('srvs_excluded_from_arp') . ''),
           //     anchor($base . 'manage/attribute_requirement/sp/' . $id . '', '' . lang('rr_requiredattributes') . '')
            );
        }

        return $result;
    }

    public function generateGeneral()
    {
        $ent = &$this->ent;
        $entid = $ent->getId();
        $ses = &$this->ses;

        $sessform = FALSE;
        if (!empty($ses) && is_array($ses)) {
            $sessform = TRUE;
        }
        $t_regauthority = $ent->getRegistrationAuthority();
        $t_regdate = '';
        $t_regtime = '';
        $tmpregdate = $ent->getRegistrationDate();
        if (!empty($tmpregdate)) {
            $t_regdate = date('Y-m-d', $tmpregdate->format('U') + j_auth::$timeOffset);
            $t_regtime = date('H:i', $tmpregdate->format('U') + j_auth::$timeOffset);
        }
        if ($sessform) {
            if (array_key_exists('regauthority', $ses)) {
                $t_regauthority = $ses['regauthority'];
            }
            if (array_key_exists('registrationdate', $ses)) {
                $t_regdate = $ses['registrationdate'];
            }
            if (array_key_exists('registrationtime', $ses)) {
                $t_regtime = $ses['registrationtime'];
            }
        }
        $f_regauthority = set_value('f[regauthority]', $t_regauthority, FALSE);
        $f_regdate = set_value('f[registrationdate]', $t_regdate);
        $f_regtime = set_value('f[registrationtime]', $t_regtime);
        $result = array();


// providername group

        $group1 = array(
            array(
                'fieldset' => lang('e_orgname'),
                'attrname' => 'lname',
                'origs' => $ent->getMergedLocalName(),
                'addbtn' => array(
                    'a1' => 'lnameadd',
                    'a2' => 'lnamelangcode',
                    'a3'=>'f[lname][XXX]',

                    'a4' => lang('e_orgname')
                ),
            ),
            array(
                'fieldset' => lang('e_orgdisplayname'),
                'attrname' => 'ldisplayname',
                'origs' => $ent->getMergedLocalDisplayName(),
                'addbtn' => array(
                    'a1' => 'ldisplaynameadd',
                    'a2' => 'ldisplaynamelangcode',
                    'a3' => 'f[ldisplayname][XXX]',
                    'a4' => lang('rr_displayname')
                ),
            ),
            array(
                'fieldset' => lang('e_orgurl'),
                'attrname' => 'lhelpdesk',
                'origs' => $ent->getHelpdeskUrlLocalized(),
                'addbtn' => array(
                    'a1' => 'lhelpdeskadd',
                    'a2' => 'lhelpdesklangcode',
                    'a3' => 'f[lhelpdesk][XXX]',

                    'a4' => lang('rr_helpdeskurl')
                ),
            ),
        );

        foreach ($group1 as $g) {
            $result[] = '';
            $tmprows = '<fieldset><legend>' . $g['fieldset'] . '</legend>';
            $sessValues = array();
            $origValues = array();
            $gnamesLang = languagesCodes();
            if ($sessform && array_key_exists($g['attrname'], $ses) && is_array($ses['' . $g['attrname'] . ''])) {
                $sessValues = $ses['' . $g['attrname'] . ''];
            }
            if (is_array($g['origs'])) {
                $origValues = $g['origs'];
            }
            $btnlangs = MY_Controller::$langselect;
            foreach ($sessValues as $key => $value) {
                $lvalue = set_value('f[' . $g['attrname'] . '][' . $key . ']', $value, FALSE);
                $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($gnamesLang[$key], 'f[' . $g['attrname'] . '][' . $key . ']', '' . $g['attrname'] . '', $key, $lvalue, '') . '</div>';
                unset($origValues['' . $key . '']);
                unset($gnamesLang['' . $key . '']);
            }
            if (!$sessform) {
                foreach ($origValues as $key => $value) {
                    $lvalue = set_value('f[' . $g['attrname'] . '][' . $key . ']', $value, FALSE);
                    if (empty($lvalue)) {
                        continue;
                    }
                    $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($gnamesLang[$key], 'f[' . $g['attrname'] . '][' . $key . ']', '' . $g['attrname'] . '', $key, $lvalue, '') . '</div>';
                    unset($gnamesLang['' . $key . '']);
                }
            }
            $tmprows .= '<div class="small-12 columns">' . $this->_generateLangAddButton('' . $g['addbtn']['a1'] . '', '' . $g['addbtn']['a2'], $btnlangs,'' . $g['addbtn']['a3'] . '') . '</div>';
            $tmprows .= '</fieldset>';
            $result[] = $tmprows;
            $result[] = '';
        }

        if ($this->isAdmin && !empty($entid)) {
            $result[] = '';
            $result[] = jGenerateInput(lang('rr_regauthority'), 'f[regauthority]', $f_regauthority, '');
            $tr = '<div class="medium-3 column medium-text-right"><label for="f[registrationdate]" class="inline">' . lang('rr_regdate') . '</label></div><div class="medium-3 large-2 column">';
            $tr .= '<input id="f[registrationdate]" name="f[registrationdate]" type="text" class="datepicker" value="' . $f_regdate . '">';
            $tr .= '</div>';
            $tr .= '<div class="medium-2 large-1 column end"><input id="f[registrationtime]" name="f[registrationtime]" type="text" value="' . $f_regtime . '" placeholder="HH:mm"> <span class="inline"></div>';
            $result[] = $tr;
            $result[] = '';
        }



        return $result;
    }




}