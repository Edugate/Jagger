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
 * Memberslist Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Memberslist extends MY_Controller {

    protected $tmp_providers;

    function __construct()
    {
        parent::__construct();
        $this->tmp_providers = new models\Providers;
    }

    private function displayidps()
    {
        $tmp_providers = new models\Providers;
        $allidps = $tmp_providers->getIdps_inNative();
        return $allidps;
    }

    private function displaysps()
    {
        $tmp_providers = new models\Providers;
        $allsps = $tmp_providers->getSps_inNative();
        return $allsps;
    }

    public function currentmembers()
    {
        $tmp_feds = new models\Federations;
        $defaultfedname = $this->config->item('defaultfedname');
        if(!empty($defaultfedname))
        {
           $fed = $tmp_feds->getOneByName(''.$defaultfedname.'');    
           if(!empty($fed))
           {
             $fedmembers = $fed->getActiveMembers();
             
           }
        }
        else
        {
           $defaultfedname = '';
        }
        $localProviders = $this->tmp_providers->getLocalPublicVisibleProviders();

        if(!empty($fed))
        {
           $providers = array('idp' => array(''.$defaultfedname.'' => array(), 'others' => array()), 'sp' => array(''.$defaultfedname.'' => array(), 'others' => array()));
        }
        else
        {
           $providers = array('idp' => array('others' => array()), 'sp' => array('edugate' => array(), 'others' => array()));

        }

        foreach ($localProviders as $p)
        {
            if (!empty($fedmembers) && $fedmembers->contains($p))
            {
                $group = ''.$defaultfedname.'';
            }
            else
            {
                $group = 'others';
            }
            $type = strtolower($p->getType());
            $pelement = array('name' => $p->getNameToWebInLang('en'), 'entityid' => $p->getEntityId(), 'url' => $p->getHelpdeskUrl(), 'desc' => htmlspecialchars($p->getDescription()));
            $providers[$type][$group][] = $pelement;
        }



        $display = '<script language="javascript">
                    function toggleDiv(divid){
                     if(document.getElementById(divid).style.display == \'none\'){
                     document.getElementById(divid).style.display = \'block\';
                      }else{
                     document.getElementById(divid).style.display = \'none\';
                      }
                    }</script>';
        $display .="\n";
        $display .= '<h2>Service Providers</h2>';
        $i = 1;
        $y = 0;
        $baseurl = base_url();
        if(!empty($defaultfedname) && array_key_exists($defaultfedname,$providers['sp']))
        {
            $display .= '<h3>'.$defaultfedname.'</h3>';
            foreach ($providers['sp'][''.$defaultfedname.''] as $p)
            {
                $display .= '<img src="'.$baseurl.'/images/resource.png"/>' . $i . '. ' . $p['name'] . '<a href="javascript:;" onmousedown="toggleDiv(\'mydiv' . $y . '\');"> <img src="'.$baseurl.'/images/list.png"/></a><div id="mydiv' . $y . '" style="display:none"><em>' . $p['desc'] . '<a href="' . $p['url'] . '"> Go...</a></em>';
                $display .= '<a href="' . $p['url'] . '" title="Contact helpdesk"><img src="'.$baseurl.'/images/help.png" alt="Contact Helpdesk"/></a></div><br />';
                $display .="\n";
                $y++;
                $i++;
            }
        }
        $display .= '<h3>Others</h3>';
        foreach ($providers['sp']['others'] as $p)
        {
            $display .= '<img src="'.$baseurl.'/images/resource.png"/>' . $i . '. ' . $p['name'] . '<a href="javascript:;" onmousedown="toggleDiv(\'mydiv' . $y . '\');"> <img src="'.$baseurl.'/images/list.png"/></a><div id="mydiv' . $y . '" style="display:none"><em>' . $p['desc'] . '<a href="' . $p['url'] . '"> Go...</a></em>';
            $display .= '<a href="' . $p['url'] . '" title="Contact helpdesk"><img src="'.$baseurl.'/images/help.png" alt="Contact Helpdesk"/></a></div><br />';
            $display .="\n";
            $y++;
            $i++;
        }

        $i = 1;
        $display .= '<br /><br /><h2>Identity Providers</h2>';
        if(!empty($defaultfedname) && array_key_exists($defaultfedname,$providers['idp']))
        {
            $display .= '<h3>'.$defaultfedname.'</h3>';
            foreach ($providers['idp'][''.$defaultfedname.''] as $p)
            {
                $display .= '<img src="'.$baseurl.'/images/homeorg.png"/>' . $i . '. ' . $p['name'] . '<a href="javascript:;" onmousedown="toggleDiv(\'mydiv' . $y . '\');"> <img src="'.$baseurl.'/images/list.png"/></a><div id="mydiv' . $y . '" style="display:none"><em>' . $p['desc'] . '<a href="' . $p['url'] . '"> Go...</a></em>';
                $display .= '<a href="' . $p['url'] . '" title="Contact helpdesk"><img src="'.$baseurl.'/images/help.png" alt="Contact Helpdesk"/></a></div><br />';
                $display .="\n";
                $y++;
                $i++;
            }
        }

        $display .= '<h3>Others</h3>';
        foreach ($providers['idp']['others'] as $p)
        {
            $display .= '<img src="'.$baseurl.'/images/homeorg.png"/>' . $i . '. ' . $p['name'] . '<a href="javascript:;" onmousedown="toggleDiv(\'mydiv' . $y . '\');"> <img src="'.$baseurl.'/images/list.png"/></a><div id="mydiv' . $y . '" style="display:none"><em>' . $p['desc'] . '<a href="' . $p['url'] . '"> Go...</a></em>';
            $display .= '<a href="' . $p['url'] . '" title="Contact helpdesk"><img src="'.$baseurl.'/images/help.png" alt="Contact Helpdesk"/></a></div><br />';
            $display .="\n";
            $y++;
            $i++;
        }

        echo $display;
    }

}

