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
 * Disco Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Disco extends MY_Controller {

    protected $logo_url, $logo_basepath, $logo_baseurl, $wayflist;

    function __construct()
    {
        parent::__construct();
        parse_str($_SERVER['QUERY_STRING'], $_GET);
        $this->output->set_content_type('application/json');
        $this->logo_basepath = $this->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;
        $this->wayflist = array();
    }

    private function providerToDisco(models\Provider $ent)
    {
        $r['entityID'] = $ent->getEntityId();
        $r['title'] = $ent->getNameToWebInLang('en');
        $doFilter = array('t' => array('idp'), 'n' => array('mdui'), 'e' => array('GeolocationHint', 'Logo'));
        $extend = $ent->getExtendMetadata()->filter(
                function($entry) use ($doFilter)
        {
            return in_array($entry->getType(), $doFilter['t']) && in_array($entry->getNamespace(), $doFilter['n']) && in_array($entry->getElement(), $doFilter['e']);
        });
        $logoSet = FALSE;
        $geoSet = FALSE;
        foreach ($extend as $ex)
        {
            $eElement = $ex->getElement();
            if ($eElement === 'GeolocationHint')
            {
                if ($geoSet === TRUE)
                {
                    continue;
                }
                $eValue = explode(',', $ex->getEvalue());
                $r['geo'] = array('lat' => $eValue[0], 'lon' => $eValue[1]);
                $geoSet = true;
            }
            elseif ($eElement === 'Logo')
            {
                if($logoSet === TRUE)
                {
                    continue;
                }
                if (!(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $ex->getEvalue(), $matches)))
                {
                    $ElementValue = $this->logo_url . $ex->getEvalue();
                }
                else
                {
                    $ElementValue = $ex->getEvalue();
                }

                $r['icon'] = $ElementValue;
                $logoSet = true;
            }
        }

        return $r;
    }

    function circle($entityId, $m = NULL)
    {

        $cnf = $this->config->item('featdisable');
        if (isset($cnf['discojuice']) && $cnf['discojuice'] === true)
        {
            set_status_header(404);
            echo 'The feature no enabled';
            return;
        }
        if (!empty($m) && $m != 'metadata.json')
        {
            show_error('Request not allowed', 403);
        }
        if (!empty($_GET['callback']))
        {
            $call = $_GET['callback'];
        }
        if (!empty($call))
        {
            $call_array = explode("_", $call);
        }
        $data = array();
        $name = base64url_decode($entityId);
        $tmp = new models\Providers;
        $me = $tmp->getOneSpByEntityId($name);
        if (empty($me))
        {
            log_message('error', 'Failed generating json  for provided entity:' . $name);
            show_error('unknown provider', 404);
            return;
        }
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));
        $cacheid = 'disco_' . $me->getId();
        $cachedDisco = $this->cache->get($cacheid);
        if (empty($cachedDisco))
        {
            log_message('debug', 'Cache: discojuice for entity:' . $me->getId() . ' with cacheid ' . $cacheid . ' not found in cache, generating...');
            $overwayf = $me->getWayfList();
            $white = false;

            if (!empty($overwayf) && is_array($overwayf))
            {
                if (array_key_exists('white', $overwayf) && count($overwayf['white']) > 0)
                {
                    $white = true;
                    $this->wayflist = $overwayf['white'];
                }
            }
            $p = new models\Providers;
            $p1 = $p->getIdPsForWayf($me);
            if (empty($p1))
            {
                show_error('empty', 404);
                return;
            }
            $output = array();
            $oi = 0;
            foreach ($p1 as $ents)
            {
                $allowed = true;
                if ($ents->getAvailable())
                {
                    if ($white)
                    {
                        if (!in_array($ents->getEntityId(), $this->wayflist))
                        {
                            $allowed = false;
                        }
                    }
                    if ($allowed)
                    {

                        $output[$oi] = $this->providerToDisco($ents);
                        $oi++;
                    }
                }
            }
            if (!empty($call_array) && is_array($call_array) && count($call_array) == 3 && $call_array['0'] == 'dj' && $call_array['1'] == 'md' && is_numeric($call_array['2']))
            {
                $jsonoutput = $call . '(' . json_encode($output) . ')';
            }
            else
            {
                $jsonoutput = json_encode($output);
            }
            $data['result'] = $jsonoutput;

            $this->cache->save($cacheid, $jsonoutput, 600);
        }
        else
        {
            log_message('debug', 'Cache: Discojoice for entity ' . $me->getId() . ' found in cache id:' . $cacheid . ', retrieving...');
            $data['result'] = $cachedDisco;
        }
        $this->load->view('disco_view', $data);
    }

}
