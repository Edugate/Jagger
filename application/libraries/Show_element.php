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
 * Show_element Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Show_element {

    protected $ci;
    protected $em;
    protected $tmp_policies;
    protected $tmp_providers;
    protected $entitiesmaps;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->tmp_policies = new models\AttributeReleasePolicies;
        $this->tmp_providers = new models\Providers;
        $this->entitiesmaps = array();
    }

    /**
     * @todo fix it - not working
     */
    public function displaySpecificArp($provider)
    {
        $result = null;
        if ($provider instanceof models\Provider)
        {
            $id = $provider->getId();
            if (empty($id))
            {
                return null;
            }
        }
        elseif (is_integer($provider))
        {
            $id = $provider;
        }
        else
        {
            return null;
        }
        $arps = $this->tmp_policies->getSPPolicy($id);
        $no_arps = count($arps);
        if ($no_arps = 0)
        {
            return null;
        }

        $custom_arps = $this->tmp_policies->getCustomSpPolicyAttributes($provider);
        $c_arps = array();
        foreach ($custom_arps as $key)
        {
            $c_arps[$key->getRequester()][$key->getAttribute()->getName()]['custom'] = $key->getRawdata();
        }
        $tmp_reqs = new models\AttributeRequirements;
        foreach ($arps as $a)
        {
            $spid = $a->getRequester();
            $sp_requester = $this->tmp_providers->getOneSpById($spid);
            if (empty($sp_requester))
            {
                log_message('error', 'found orhaned arp with id:' . $a->getId() . ' removing from db ....');
            }
            else
            {
                $required_attrs = $tmp_reqs->getRequirementsBySP($sp_requester);

                $requesterName = $sp_requester->getName();
                if (empty($requesterName))
                {
                    $requesterName = $sp_requester->getEntityId();
                }
                $name = $sp_requester->getEntityId();
                $this->entitiesmaps[$sp_requester->getEntityId()] = $requesterName;


                $result['' . $sp_requester->getEntityId() . ''][$a->getAttribute()->getName()] = array(
                    'id' => $a->getId(),
                    'attr_id' => $a->getAttribute()->getId(),
                    'spid' => $spid,
                    'name' => $name,
                    'policy' => $a->getRelease(),
                    'status' => null,
                );
                foreach ($required_attrs as $r)
                {
                    $attr_name = $r->getAttribute()->getName();

                    $result2[$name][$attr_name]['attr_id'] = $r->getAttribute()->getId();
                    $result2[$name][$attr_name]['status'] = $r->getStatus();
                    $result2[$name][$attr_name]['spid'] = $r->getSP()->getId();
                    $result2[$name][$attr_name]['name'] = $r->getSP()->getName();
                }
                if (array_key_exists($spid, $c_arps) && array_key_exists($a->getAttribute()->getName(), $c_arps[$spid]))
                {
                    $result[$name][$a->getAttribute()->getName()]['custom'] = $c_arps[$spid][$a->getAttribute()->getName()]['custom'];
                }
            }
        }
        $result3 = array();
        if (!empty($result2) && is_array($result2) && count($result) > 0)
        {
            foreach ($result2 as $key => $value)
            {
                foreach ($value as $pkey => $pvalue)
                {
                    if (array_key_exists($pkey, $result[$key]))
                    {
                        $result[$key][$pkey]['status'] = $pvalue['status'];
                    }
                    else
                    {

                        log_message('debug', 'key not found for:' . $pkey);
                        $result[$key][$pkey]['id'] = null;
                        $result[$key][$pkey]['attr_id'] = $pvalue['attr_id'];
                        $result[$key][$pkey]['spid'] = $pvalue['spid'];
                        $result[$key][$pkey]['name'] = $pvalue['name'];
                        $result[$key][$pkey]['policy'] = 'not set';
                        $result[$key][$pkey]['status'] = $pvalue['status'];
                    }
                }
            }
        }
        return $result;
    }

    public function displayFederationsArp(models\Provider $provider)
    {
        $result = null;
        $id = $provider->getId();
        $arps = $this->tmp_policies->getFedPolicyAttributes($provider);
        if (empty($arps))
        {
            return null;
        }
        foreach ($arps as $a)
        {
            $req = $a->getRequester();
            if (!empty($req))
            {

                $result[$req]['attrs'][$a->getAttribute()->getId()]['attrid'] = $a->getAttribute()->getId();
                $result[$req]['attrs'][$a->getAttribute()->getId()]['name'] = $a->getAttribute()->getName();
                $result[$req]['attrs'][$a->getAttribute()->getId()]['release'] = $a->getRelease();
                $result[$req]['attrs'][$a->getAttribute()->getId()]['id'] = $a->getId();
            }
        }

        $fed_ids = array_keys($result);
        $tmp_feds = new models\Federations;
        $feds = $tmp_feds->getFederationsByIds($fed_ids);
        foreach ($feds as $f)
        {
            if (array_key_exists($f->getId(), $result))
            {
                $result[$f->getId()]['fedname'] = $f->getName();
                $result[$f->getId()]['fedid'] = $f->getId();
            }
        }

        return $result;
    }

    /**
     * $provider param can be id of idp or object of models\Provider class
     */
    public function displayDefaultArp($provider)
    {
        $result = null;
        if ($provider instanceof models\Provider)
        {
            $id = $provider->getId();
        }
        elseif (is_integer($provider))
        {
            $id = $provider;
        }
        else
        {
            return null;
        }
        $arps = $this->tmp_policies->getGlobalPolicyAttributes($provider);
        if (empty($arps))
        {
            return null;
        }

        $result = array();
        foreach ($arps as $a)
        {
            $result[] = array(
                'id' => $a->getId(),
                'attrid' => $a->getAttribute()->getId(),
                'name' => $a->getAttribute()->getName(),
                'release' => $a->getRelease(),
            );
        }


        return $result;
    }

    public function generateTableSpecificArp(models\Provider $provider)
    {
        $exluded_arps = $provider->getExcarps();
        if (empty($exluded_arps))
        {
            $exluded_arps = array();
        }
        $result = null;
        $supported = $this->tmp_policies->getSupportedAttributes($provider);
        $supported_attrs = array();
        foreach ($supported as $sa)
        {
            $supported_attrs[$sa->getAttribute()->getName()] = $sa->getAttribute()->getId();
        }
        $source = $this->displaySpecificArp($provider);
        $attributes = array();
        $supported_attrs_url = base_url() . "manage/supported_attributes/idp/" . $provider->getId();
        $prefix_url = base_url() . "manage/attribute_policy/detail/";
        $prefix_multi_url = base_url() . "manage/attribute_policy/multi/";
        $icon = base_url() . "images/icons/pencil-field.png";
        if (!empty($source))
        {
            foreach ($source as $key => $value)
            {
                $tmp_sp_array = end($value);
                if (!empty($tmp_sp_array))
                {
                    $tmp_spid = $tmp_sp_array['spid'];
                }
                $link_sp = '<a href="' . $prefix_multi_url . $provider->getId() . '/sp/' . $tmp_spid . '"><img src="' . $icon . '"/></a>';
                if (in_array($key, $exluded_arps))
                {
                    $lbl = '<span class="lbl lbl-disabled">excluded</span> ';
                }
                else
                {
                    $lbl = '';
                }

                $attributes[] = array('data' => array('data' => $lbl . $this->entitiesmaps[$key] . $link_sp . ' <small>' . $key . '</small>', 'colspan' => 3, 'class' => 'highlight'));

                foreach ($value as $attr_key => $attr_value)
                {
                    if (!array_key_exists($attr_key, $supported_attrs))
                    {

                        $attr_name = "<span class=\"alert\" title=\"attribute not supported\">" . $attr_key . "</span>";
                    }
                    else
                    {
                        $attr_name = $attr_key;
                    }

                    if (empty($attr_value['id']))
                    {
                        $policy_id = 0;
                    }
                    else
                    {
                        $policy_id = $attr_value['id'];
                    }
                    $link = anchor($prefix_url . "" . $provider->getId() . "/" . $attr_value['attr_id'] . "/sp/" . $attr_value['spid'], '<img src="' . $icon . '"/>');
                    $permited_values = '';
                    $denied_values = '';
                    $lng_permitedval = lang('rr_permvalues');
                    $lng_denyval = lang('rr_denvalues');

                    if (array_key_exists('custom', $attr_value) && !empty($attr_value['custom']))
                    {
                        if (array_key_exists('permit', $attr_value['custom']) && count($attr_value['custom']['permit']) > 0)
                        {
                            $permited_values .= '<dl><dt>'.$lng_permitedval.':</dt>';
                            foreach ($attr_value['custom']['permit'] as $per1)
                            {
                                $permited_values .= "<dd>" . $per1 . "</dd>";
                            }
                            $permited_values .= "</dl>";
                        }
                        if (array_key_exists('deny', $attr_value['custom']) && count($attr_value['custom']['deny']) > 0)
                        {
                            $denied_values .= '<dl><dt>'.$lng_denyval.':</dt>';
                            foreach ($attr_value['custom']['deny'] as $per2)
                            {
                                $denied_values .= "<dd>" . $per2 . "</dd>";
                            }
                            $denied_values .="</dl>";
                        }
                    }
                    $custom_link = anchor(base_url() . "manage/custom_policies/idp/" . $provider->getId() . "/" . $attr_value['spid'] . "/" . $attr_value['attr_id'], '<img src="' . $icon . '"/>');

                    $attributes[] = array($attr_name . $link, $attr_value['status'], $attr_value['policy'] . '<br /><div ><b>'.lang('custompolicy').'</b>' . $custom_link .  $permited_values  . $denied_values . '</div>');
                }
            }
            $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');

            $this->ci->table->set_template($tmpl);
            $this->ci->table->set_heading(''.lang('rr_attr_name').'', ''.lang('rr_currentstatus').'', ''.lang('policy').'');
            $this->ci->table->set_caption(''.lang('rr_specpolicies').'');
            $result = $this->ci->table->generate($attributes);
            $this->ci->table->clear();
            return $result;
        }
    }

    public function generateTableFederationsArp(models\Provider $provider)
    {
        $result = null;
        $supported = $this->tmp_policies->getSupportedAttributes($provider);
        $supported_attrs = array();
        foreach ($supported as $sa)
        {
            $supported_attrs[$sa->getAttribute()->getName()] = $sa->getAttribute()->getId();
        }
        $source = $this->displayFederationsArp($provider);
        $attributes = array();
        $prefix_url = base_url() . 'manage/attribute_policy/detail/';
        $icon = base_url() . 'images/icons/pencil-field.png';
        if (!empty($source))
        {
            $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
            $this->ci->table->set_template($tmpl);
            $this->ci->table->set_heading(''.lang('rr_attr_name').'', ''.lang('policy').'');
            $this->ci->table->set_caption(''.lang('rr_arpforfeds').'');
            foreach ($source as $s)
            {

                $attributes[] = array('data' => array('data' => ''.lang('rr_federation').': <b>' . $s['fedname'] . '</b>', 'colspan' => 2, 'class' => 'highlight'));
                foreach ($s['attrs'] as $attr_key => $attr_value)
                {
                    $edit_link = anchor($prefix_url . "" . $provider->getId() . "/" . $attr_value['attrid'] . "/fed/" . $s['fedid'], '<img src="' . $icon . '"/>');
                    if (!array_key_exists($attr_value['name'], $supported_attrs))
                    {
                        $attr_name = '<span class="alert" title="'.lang('attrnotsupported').'">' . $attr_value['name'] . '</span>';
                        $attributes[] = array('' . $attr_name . '' . $edit_link . '', $attr_value['release']);
                    }
                    else
                    {
                        $attributes[] = array('' . $attr_value['name'] . '' . $edit_link . '', $attr_value['release']);
                    }
                }
            }

            $result = $this->ci->table->generate($attributes);
        }
        return $result;
    }

    public function generateTableDefaultArp(models\Provider $provider, $disable_caption = null)
    {
        $source = $this->displayDefaultArp($provider);
        $attributes = array();
        $prefix_url = base_url() . 'manage/attribute_policy/detail/';
        $icon = base_url() . 'images/icons/pencil-field.png';
        $supported = $this->tmp_policies->getSupportedAttributes($provider);
        $supported_attrs = array();
        foreach ($supported as $sa)
        {
            $supported_attrs[$sa->getAttribute()->getName()] = $sa->getAttribute()->getId();
        }
        if (!empty($source))
        {

            foreach ($source as $s)
            {
                if (!array_key_exists($s['name'], $supported_attrs))
                {

                    $attr_name = '<span class="alert" title="'.lang('attrnotsupported').'">' . $s['name'] . '</span>';
                }
                else
                {
                    $attr_name = $s['name'];
                }
                $link = anchor($prefix_url . "" . $provider->getId() . "/" . $s['attrid'] . "/global/0", '<img src="' . $icon . '"/>');
                $attributes[] = array('' . $attr_name . '' . $link . '', $s['release']);
            }

            $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');

            $this->ci->table->set_template($tmpl);
            $this->ci->table->set_heading(''.lang('attrname').'', ''.lang('policy').'');
            if (empty($disable_caption))
            {
                $provname = $provider->getName();
                if(empty($provname))
                {
                     $provname = $provider->getEntityid();
                }
                $this->ci->table->set_caption(''.lang('rr_defaultarp').': <b>' . $provname . '</b>' . anchor(base_url() . "providers/detail/show/" . $provider->getId(), '<img src="' . base_url() . 'images/icons/home.png" />'));
            }
            $result = $this->ci->table->generate($attributes);
            $this->ci->table->clear();
            return $result;
        }
        else
        {
            $result = '<span class="notice">'.lang('nodefaultpolicysetyet').'</span>';
            return $result;
        }
    }

    /**
     * it's based on IdPMembersToTable 
     * @todo finish
     */
    public function membersToTable(array $members, $style = null)
    {
        $cell_with_idps = "";
        $cell_with_sps = "";
        $cell_with_both = "";



        $result['IDP'] = $cell_with_idps;
        $result['SP'] = $cell_with_sps;
        $result['BOTH'] = $cell_with_both;
        return $result;
    }

    /**
     * $members must be array of models\Provider objects 
     */
    public function IdPMembersToTable(array $members)
    {
        $cell_with_idp_members = '<div id="table2"><div class="firstLine"><span class="col1">&nbsp;</span><span class="col2">&nbsp;</span><span class="col3">&nbsp;</span></div>';
        $cell_with_sp_members = '<div id="table2"><div class="firstLine"><span class="col1">&nbsp;</span><span class="col2">&nbsp;</span><span class="col3">&nbsp;</span></div>';
        $cell_with_both_members = '<div id="table2"><div class="firstLine"><span class="col1">&nbsp;</span><span class="col2">&nbsp;</span><span class="col3">&nbsp;</span></div>';
        foreach ($members as $m)
        {
            $m_type = $m->getType();
            $m_id = $m->getId();

            $inactive = '';
            $alertclass = '';
            if (!($m->getAvailable()))
            {
                $inactive = '<span class"alert">inactive</span>';
                $alertclass = 'class="alert"';
            }
            $m_link = base_url() . 'providers/detail/show/' . $m_id;
            $m_entityid = $m->getEntityId();
            $m_displayname = $m->getName();
            if (empty($m_displayname))
            {
                $m_displayname = $m_entityid;
            }
            if ($m_type === 'IDP')
            {
                $cell_with_idp_members .= '<div ' . $alertclass . '><span class="col1"></span><span class="homeorg" class="col2">'. anchor($m_link, $m_displayname, 'title="' . $m->getDescription() . '"') . ' <small><i>'.$m->getEntityId().'</i></small></span><span class="col3">&nbsp;</span></div>';
            }
            if ($m_type === 'SP')
            {
                $cell_with_sp_members .= '<div ' . $alertclass . '> <span class="col1"></span><span class="col2">' . anchor($m_link, $m_displayname, 'title="' . $m->getDescription() . '"') . '  </span> <small><i>'.$m->getEntityId().'</i></small><span class="col3">&nbsp;</span></div>';
            }
            if ($m_type == 'BOTH')
            {
                $cell_with_both_members .= '<div ' . $alertclass . '><span class="col1"></span> <span class="col2">' . anchor($m_link, $m_displayname, 'title="' . $m->getDescription() . '"') . '  </span><span class="col3">&nbsp;</span></div>';
            }
        }
        $cell_with_idp_members .= '<div class="lastLine"></div></div><div class="cleaner"></div>';
        $cell_with_sp_members .= '<div class="lastLine"></div></div><div class="cleaner"></div>';
        $cell_with_both_members .= '<div class="lastLine"></div></div><div class="cleaner"></div>';

        $result['IDP'] = $cell_with_idp_members;
        $result['SP'] = $cell_with_sp_members;
        $result['BOTH'] = $cell_with_both_members;
        return $result;
    }

    public function generateModificationsList(models\Provider $idp, $count = null)
    {
        if (empty($count) or !is_numeric($count) or $count < 1)
        {
            $count = 5;
        }

        $tmp_tracks = new models\Trackers;
        $tracks = $tmp_tracks->getProviderModifications($idp, $count);
        if (empty($tracks))
        {
            return null;
        }
        $no_results = count($tracks);

        $result = '<ul>';
        foreach ($tracks as $t)
        {
            $modArray = unserialize($t->getDetail());
            $chng = array();
            $i = 0;
            foreach ($modArray as $ckey => $cvalue)
            {
                $chng[$ckey] = array(
                    0 => $ckey,
                    1 => $modArray[$ckey]['before'],
                    2 => $modArray[$ckey]['after']
                );
            }
            $this->ci->table->set_heading('Name', 'Before', 'After');
            $y = $this->ci->table->generate($chng);
            $user = $t->getUser();
            if (empty($user))
            {
                $user = 'unknown';
            }
            $result .= '<li><span class="accordionButton"><b>' . $t->getCreated()->format('Y-m-d H:i:s') . '</b> changes made by <b>' . $user . '</b> from <b>' . $t->getIp() . '</b> ... details</span><span class="accordionContent"><br />' . $y . '</span></li>';
        }
        $result .= '</ul>';
        return $result;
    }

}
