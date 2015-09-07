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
class Show_element
{

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

    public function displaySpecificArp(models\Provider $provider)
    {
        $result = null;
        $providerId = $provider->getId();
        $myLang = MY_Controller::getLang();
        $arps = $this->tmp_policies->getSPPolicy($providerId);
        if (count($arps) == 0) {
            return null;
        }

        $customArps = $this->tmp_policies->getCustomSpPolicyAttributes($provider);
        $c_arps = array();
        foreach ($customArps as $cArp) {
            $c_arps[$cArp->getRequester()][$cArp->getAttribute()->getName()] = array(
                'id' => $cArp->getId(),
                'custom' => $cArp->getRawdata(),
                'attr_id' => $cArp->getAttribute()->getId(),
                'status' => null
            );
            $spid = $cArp->getRequester();
            $sp_requester = $this->tmp_providers->getOneSpById($spid);
            $requesterName = $sp_requester->getNameToWebInLang($myLang, 'sp');
            $this->entitiesmaps[$sp_requester->getEntityId()] = $requesterName;
        }
        $tmp_reqs = new models\AttributeRequirements;
        foreach ($arps as $a) {
            $spid = $a->getRequester();
            $sp_requester = $this->tmp_providers->getOneSpById($spid);
            if (empty($sp_requester)) {
                log_message('error', 'found orhaned arp with id:' . $a->getId() . ' removing from db ....');
            } else {
                $required_attrs = $tmp_reqs->getRequirementsBySP($sp_requester);

                $requesterName = $sp_requester->getNameToWebInLang($myLang, 'sp');
                if (empty($requesterName)) {
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
                foreach ($required_attrs as $r) {
                    $attr_name = $r->getAttribute()->getName();

                    $result2[$name][$attr_name]['attr_id'] = $r->getAttribute()->getId();
                    $result2[$name][$attr_name]['status'] = $r->getStatus();
                    $result2[$name][$attr_name]['spid'] = $r->getSP()->getId();
                    $result2[$name][$attr_name]['name'] = $r->getSP()->getName();
                }
                if (isset($c_arps[$spid][$a->getAttribute()->getName()]['custom'])) {
                    $result[$name][$a->getAttribute()->getName()]['custom'] = $c_arps[$spid][$a->getAttribute()->getName()]['custom'];
                    unset($c_arps[$spid]);
                }
            }
        }
        foreach ($c_arps as $k => $v) {
            $sp_requester = $this->tmp_providers->getOneSpById($k);
            $required_attrs = $tmp_reqs->getRequirementsBySP($sp_requester);

            foreach ($v as $k1 => $v1) {
                $result[$sp_requester->getEntityId()][$k1] = array(
                    'id' => $v1['id'],
                    'name' => $sp_requester->getNameToWebInLang($myLang, 'sp'),
                    'custom' => $v1['custom'],
                    'attr_id' => $v1['attr_id'],
                    'spid' => $k,
                    'policy' => 'not set',
                    'status' => $v1['status'],
                );
            }
            foreach ($required_attrs as $p) {
                $pattrname = $p->getAttribute()->getName();
                if (array_key_exists($pattrname, $result[$sp_requester->getEntityId()])) {
                    $result[$sp_requester->getEntityId()][$pattrname]['status'] = $p->getStatus();
                }
            }
        }
        if (!empty($result2) && is_array($result2) && count($result) > 0) {
            foreach ($result2 as $key => $value) {
                foreach ($value as $pkey => $pvalue) {
                    if (array_key_exists($pkey, $result[$key])) {
                        $result[$key][$pkey]['status'] = $pvalue['status'];
                    } else {

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
        log_message('debug','JANUSZ');
        $result = null;
        $tmpProviders = new models\Providers();
        $trustedFeds = $tmpProviders->getTrustedActiveFeds($provider);
        $supportedAttrs = $this->tmp_policies->getSupportedAttributes($provider);
        $attrs = array();
        foreach ($supportedAttrs as $sattr) {
            $attrs[$sattr->getAttribute()->getId()] = array(
                'attrid' => $sattr->getAttribute()->getId(),
                'name' => $sattr->getAttribute()->getName(),
                'release' => lang('rr_notset'),
                'id' => null,
            );
        }
        foreach ($trustedFeds as $f) {
            $result['' . $f->getId() . '']['attrs'] = $attrs;
        }
        $arps = $this->tmp_policies->getFedPolicyAttributes($provider);

        foreach ($arps as $a) {
            $req = $a->getRequester();
            if (!empty($req)) {
                $result[$req]['attrs'][$a->getAttribute()->getId()] = array(
                    'attrid' => $a->getAttribute()->getId(),
                    'name' => $a->getAttribute()->getName(),
                    'release' => $a->getRelease(),
                    'id' => $a->getId()
                );

            }
        }
        $fed_ids = array_keys($result);
        $tmp_feds = new models\Federations;
        $feds = $tmp_feds->getFederationsByIds($fed_ids);
        foreach ($feds as $f) {
            if (array_key_exists($f->getId(), $result)) {
                $result[$f->getId()]['fedname'] = $f->getName();
                $result[$f->getId()]['fedid'] = $f->getId();
            }
        }
        return $result;
    }



    public function generateTableFederationsArp(models\Provider $provider, $disabledcaption = NULL)
    {
        $result = null;
        /**
         * @var models\SupportedAttributes[] $tmpSupAttrs
         */
        $tmpSupAttrs = $this->tmp_policies->getSupportedAttributes($provider);
        $supportedAttrs = array();
        foreach ($tmpSupAttrs as $sa) {
            $supportedAttrs[$sa->getAttribute()->getName()] = $sa->getAttribute()->getId();
        }
        $source = $this->displayFederationsArp($provider);
        $attributes = array();
        $prefix_url = base_url() . 'manage/attributepolicy/detail/';
        $icon = '<i class="fi-pencil"></i>';
        if (!empty($source)) {
            $tmpl = array('table_open' => '<table  id="detailsnosort">');
            $this->ci->table->set_template($tmpl);
            $this->ci->table->set_heading('' . lang('rr_attr_name') . '', '' . lang('policy') . '', lang('rr_action'));
            if (empty($disabledcaption)) {
                $this->ci->table->set_caption('' . lang('rr_arpforfeds') . '');
            }
            foreach ($source as $s) {

                $attributes[] = array('data' => array('data' => '' . lang('rr_federation') . ': <b>' . $s['fedname'] . '</b>', 'colspan' => 3, 'class' => 'highlight'));
                $workingSupAttrs = $supportedAttrs;
                if (array_key_exists('attrs', $s)) {
                    foreach ($s['attrs'] as $attr_key => $attr_value) {
                        $edit_link = anchor($prefix_url . "" . $provider->getId() . "/" . $attr_value['attrid'] . "/fed/" . $s['fedid'], $icon, array('data-jagger-attrid' => $attr_value['attrid'], 'data-jagger-fedid' => $s['fedid'], 'data-jagger-attrname' => $attr_value['name']));
                        if (!array_key_exists($attr_value['name'], $supportedAttrs)) {
                            $attr_name = '<span class="alert" title="' . lang('attrnotsupported') . '">' . $attr_value['name'] . '</span>';
                            $attributes[] = array('' . $attr_name . '', $attr_value['release'], $edit_link);
                        } else {
                            $attributes[] = array('' . $attr_value['name'] . '', '<span class="dynstate">' . $attr_value['release'] . '</span>', $edit_link);
                            unset($workingSupAttrs['' . $attr_value['name'] . '']);

                        }

                    }
                }
                foreach ($workingSupAttrs as $k => $v) {
                    $edit_link = anchor($prefix_url . "" . $provider->getId() . "/" . $v . "/fed/" . $s['fedid'], $icon, array('data-jagger-attrid' => $v, 'data-jagger-fedid' => $s['fedid'], 'data-jagger-attrname' => $k));
                    $attributes[] = array('' . $k . '', '<span class="dynstate">' . lang('rr_notset') . '</span>', $edit_link);
                }

            }

            $result = $this->ci->table->generate($attributes);
        }
        return $result;
    }


    public function generateRequestsList(models\Provider $idp, $count = null)
    {
        if (empty($count) || !is_numeric($count) || $count < 1) {
            $count = 5;
        }

        $tmp_tracks = new models\Trackers;
        $tracks = $tmp_tracks->getProviderRequests($idp, $count);
        if (empty($tracks)) {
            return null;
        }
        $mcounter = 0;
        $result = '<dl class="accordion" data-accordion="requestsList">';
        foreach ($tracks as $t) {
            $det = $t->getDetail();
            $this->ci->table->set_heading('Request');
            $this->ci->table->add_row($det);
            $y = $this->ci->table->generate();
            $user = $t->getUser();
            if (empty($user)) {
                $user = lang('unknown');
            }
            $result .= '<dd class="accordion-navigation">';
            $result .= '<a href="#rmod' . $mcounter . '">' . date('Y-m-d H:i:s', $t->getCreated()->format('U') + j_auth::$timeOffset) . ' ' . lang('made_by') . ' <b>' . $user . '</b> ' . lang('from') . ' ' . $t->getIp() . '</a><div id="rmod' . $mcounter . '" class="content">' . $y . '</div>';
            $result .= '</dd>';
            $mcounter++;
            $this->ci->table->clear();
        }
        $result .= '</dl>';
        return $result;
    }

    public function generateModificationsList(models\Provider $idp, $count = null)
    {
        if (empty($count) || !is_numeric($count) || $count < 1) {
            $count = 5;
        }

        $tmp_tracks = new models\Trackers;
        $tracks = $tmp_tracks->getProviderModifications($idp, $count);
        if (empty($tracks)) {
            return null;
        }


        $result = '<dl class="accordion" data-accordion="modificationsList">';
        $mcounter = 0;
        foreach ($tracks as $t) {
            $modArray = unserialize($t->getDetail());
            $chng = array();
            foreach ($modArray as $ckey => $cvalue) {
                $chng[$ckey] = array(
                    0 => $ckey,
                    1 => $modArray[$ckey]['before'],
                    2 => $modArray[$ckey]['after']
                );
            }
            $this->ci->table->set_heading('Name', 'Before', 'After');
            $y = $this->ci->table->generate($chng);
            $user = $t->getUser();
            if (empty($user)) {
                $user = lang('unknown');
            }
            $result .= '<dd class="accordion-navigation">';
            $result .= '<a href="#mod' . $mcounter . '" class="accordion-icon">' . date('Y-m-d H:i:s', $t->getCreated()->format('U') + j_auth::$timeOffset) . ' ' . lang('chng_made_by') . ' <b>' . $user . '</b> ' . lang('from') . ' ' . $t->getIp() . '</a><div id="mod' . $mcounter . '" class="content">' . $y . '</div>';
            $result .= '</dd>';
            $mcounter++;
        }
        $result .= '</dl>';
        return $result;
    }

}
