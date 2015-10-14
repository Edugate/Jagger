<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Providerformelements
{
    protected $ci;
    protected $em;
    protected $disallowedparts = array();
    protected $defaultlangselect = 'en';
    protected $langs;
    protected $isAdmin;
    protected $ent;
    protected $ses;

    function __construct($params)
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        if (!is_array($params) || !isset($params['provider'])) {
            throw new Exception('Missing paramss');
        }
        $this->ent = $params['provider'];
        $this->ses = $params['session'];
        $this->ci->load->helper(array('form', 'shortcodes'));
        $this->langs = languagesCodes();
        $a = $this->ci->config->item('langselectdefault');
        if ($a !== null) {
            $this->defaultlangselect = $a;
        }
        $this->isAdmin = $this->ci->jauth->isAdministrator();
        $disallowedparts = $this->ci->config->item('entpartschangesdisallowed');
        if ($this->isAdmin || empty($disallowedparts) || !is_array($disallowedparts)) {
            $disallowedparts = array();
        }
        $this->disallowedparts = $disallowedparts;
    }

    private function _generateLangAddButton($spanclass, $dropname, $langs, $buttonvalue)
    {
        $result = '<span class="' . $spanclass . '"><div class="small-6 medium-3 large-3 columns">' . form_dropdown('langcodes', $langs, $this->defaultlangselect) . '</div><div class="small-6 large-4 end columns"><button type="button" name="addinnewlang" value="' . $buttonvalue . '" class="editbutton addicon smallerbtn button inline left tiny">' . lang('btnaddinlang') . '</button></div></span>';
        return $result;
    }

    private function _generateLangInputWithRemove($label, $name, $buttonname, $buttonvalue, $value = '', $classes = '')
    {
        $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div>' .
            '<div class="small-6 large-7 columns">' .
            form_input(array(
                'name' => '' . $name . '',
                'id' => '' . $name . '',
                'value' => '' . $value . '',
                'class' => $classes . ' right inline'
            )) . '</div>' .
            '<div class="small-3 large-2 columns"><button type="button" class="btn langinputrm inline left button tiny alert" name="lname" value="' . $buttonvalue . '">' . lang('rr_remove') .
            '</button></div>';
        return $result;
    }


    public function generateGeneral()
    {
        $ent = &$this->ent;
        $entid = $ent->getId();
        $sessform = is_array($this->ses);

        $t_regauthority = $ent->getRegistrationAuthority();
        $t_regdate = '';
        $t_regtime = '';
        $tmpregdate = $ent->getRegistrationDate();
        if (!empty($tmpregdate)) {
            $t_regdate = date('Y-m-d', $tmpregdate->format('U') + jauth::$timeOffset);
            $t_regtime = date('H:i', $tmpregdate->format('U') + jauth::$timeOffset);
        }
        if ($sessform) {
            if (array_key_exists('regauthority', $this->ses)) {
                $t_regauthority = $this->ses['regauthority'];
            }
            if (array_key_exists('registrationdate', $this->ses)) {
                $t_regdate = $this->ses['registrationdate'];
            }
            if (array_key_exists('registrationtime', $this->ses)) {
                $t_regtime = $this->ses['registrationtime'];
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
                    'a3' => 'f[lname][XXX]',
                    'a4' => lang('e_orgname')
                )
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
                )
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
                )
            )
        );

        foreach ($group1 as $g) {
            $result[] = '';
            $tmprows = '<fieldset><legend>' . $g['fieldset'] . '</legend>';
            $origValues = array();
            if (is_array($g['origs'])) {
                $origValues = $g['origs'];
            }
            $gnamesLang = languagesCodes();
            $btnlangs = MY_Controller::$langselect;

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
            elseif(array_key_exists($g['attrname'], $this->ses) && is_array($this->ses['' . $g['attrname'] . ''])){
                $sessValues = $this->ses['' . $g['attrname'] . ''];
                foreach ($sessValues as $key => $value) {
                    $lvalue = set_value('f[' . $g['attrname'] . '][' . $key . ']', $value, FALSE);
                    $tmprows .= '<div class="small-12 columns">' . $this->_generateLangInputWithRemove($gnamesLang[$key], 'f[' . $g['attrname'] . '][' . $key . ']', '' . $g['attrname'] . '', $key, $lvalue, '') . '</div>';
                    unset($origValues['' . $key . ''], $gnamesLang['' . $key . '']);
                }
            }
            $tmprows .= '<div class="small-12 columns">' . $this->_generateLangAddButton('' . $g['addbtn']['a1'] . '', '' . $g['addbtn']['a2'], $btnlangs, '' . $g['addbtn']['a3'] . '') . '</div>';
            $tmprows .= '</fieldset>';
            $result[] = $tmprows;
            $result[] = '';
        }

        if ($this->isAdmin && !empty($entid)) {
            $result[] = '';
            $result[] = jGenerateInput(lang('rr_regauthority'), 'f[regauthority]', $f_regauthority, '');
            $result[] = '<div class="medium-3 column medium-text-right"><label for="f[registrationdate]" class="inline">' . lang('rr_regdate') . '</label></div>' .
                '<div class="medium-3 large-2 column"><input id="f[registrationdate]" name="f[registrationdate]" type="text" class="datepicker" value="' . $f_regdate . '"></div>' .
                '<div class="medium-2 large-1 column end"><input id="f[registrationtime]" name="f[registrationtime]" type="text" value="' . $f_regtime . '" placeholder="HH:mm"> <span class="inline"></div>';
            $result[] = '';
        }

        return $result;
    }
}
