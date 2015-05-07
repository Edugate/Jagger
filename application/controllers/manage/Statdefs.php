<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * Statdefs Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Statdefs extends MY_Controller
{

    protected $ispreworkers;

    function __construct()
    {
        parent::__construct();
        $this->ispreworkers = $this->config->item('predefinedstats');
        if (empty($this->ispreworkers) || !is_array($this->ispreworkers)) {
            $this->ispreworkers = array();
        }
        $this->load->library('form_validation');
    }


    private function isStats()
    {
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if (empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE)) {

            return false;
        }
        return true;
    }

    public function download($defid = null)
    {
        if (!$this->isStats()) {
            set_status_header(404);
            echo 'Feature not enabled';
            return null;
        }
        if (!$this->input->is_ajax_request() || empty($defid) || !ctype_digit($defid) || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'Access denied';
            return null;
        }
        /**
         * @var $statDefinition models\ProviderStatsDef
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
        if (empty($statDefinition)) {
            set_status_header(404);
            echo 'Not found';
            return null;
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $statDefinition->getProvider();
        if (empty($provider)) {
            set_status_header(404);
            echo 'Not found';
            return null;
        }
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess) {
            set_status_header(403);
            echo 'Access denied';
            return null;
        }
        $params = array(
            'defid' => $statDefinition->getId(),
            'entityid' => $provider->getEntityId(),
            'url' => $statDefinition->getSourceUrl(),
            'type' => $statDefinition->getType(),
            'sysdef' => $statDefinition->getSysDef(),
            'title' => $statDefinition->getTitle(),
            'httpmethod' => $statDefinition->getHttpMethod(),
            'format' => $statDefinition->getFormatType(),
            'accesstype' => $statDefinition->getAccessType(),
            'authuser' => $statDefinition->getAuthUser(),
            'authpass' => $statDefinition->getAuthPass(),
            'postoptions' => $statDefinition->getPostOptions(),
            'displayoptions' => $statDefinition->getDisplayOptions(),
            'overwrite' => $statDefinition->getOverwrite()
        );

        $gmclient = new GearmanClient();
        $jobservers = array();
        $gearmanConfig = $this->config->item('gearmanconf');
        foreach ($gearmanConfig['jobserver'] as $v) {
            $jobservers[] = '' . $v['ip'] . ':' . $v['port'] . '';
        }
        try {
            $gmclient->addServers('' . implode(",", $jobservers) . '');
        } catch (Exception $e) {
            log_message('error', 'GeamanClient cant add job-server');
            echo "Cant add  job-server(s)";
            return false;
        }
        $jobsSession = $this->session->userdata('jobs');
        if (!empty($jobsSession) || is_array($jobsSession) || isset($jobsSession['statdef']['' . $defid . ''])) {
            $stat = $gmclient->jobStatus($jobsSession['stadef']['' . $defid . '']);

            if (($stat['0'] === TRUE) && ($stat['1'] === FALSE)) {
                echo json_encode(array('status' => lang('rr_jobinqueue')));
                return false;
            } elseif ($stat[0] === $stat[1] && $stat[1] === true) {
                $percent = $stat[2] / $stat[3] * 100;
                echo json_encode(array('status' => lang('rr_jobdonein') . ' ' . $percent . ' %'));
                return false;
            }
        }

        if ($params['type'] === 'ext') {
            $jobHandle = $gmclient->doBackground("externalstatcollection", serialize($params));
            $_SESSION['jobs']['stadef']['' . $defid . ''] = $jobHandle;
            log_message('debug', 'GEARMAN: Job: ' . $jobHandle);
        } elseif (($params['type'] === 'sys') && !empty($params['sysdef'])) {
            if (array_key_exists($params['sysdef'], $this->ispreworkers)) {
                if (array_key_exists('worker', $this->ispreworkers['' . $params['sysdef'] . '']) && !empty($this->ispreworkers['' . $params['sysdef'] . '']['worker'])) {
                    $workername = $this->ispreworkers['' . $params['sysdef'] . '']['worker'];
                    $jobHandle = $gmclient->doBackground('' . $workername . '', serialize($params));
                    $_SESSION['jobs']['stadef']['' . $defid . ''] = $jobHandle;
                }
            }
        }
        echo json_encode(array('status' => lang('taskssent') . ' '));
    }

    public function show($providerId = null, $statDefId = null)
    {
        if (empty($providerId) || !ctype_digit($providerId) || !(empty($statDefId) || ctype_digit($statDefId)) || $this->isStats() !== TRUE) {
            show_error('Page not found', 404);
            return null;
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $myLang = MY_Controller::getLang();

        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerId . ''));
        if (empty($provider)) {
            show_error('Provider not found', 404);
        }
        $providerType = $provider->getType();
        $providerType = strtolower($providerType);
        if (strcasecmp($providerType, 'both') == 0) {
            $providerType = 'idp';
        }
        $this->load->library('zacl');

        $hasAccess = $this->zacl->check_acl('' . $providerId . '', 'write', 'entity', '');

        if (!$hasAccess) {
            show_error(lang('rr_noperm'), 403);
        }

        /**
         * @var $statDefinitions models\ProviderStatsDef[]
         */

        $statDefinitions = $this->getExistingStatsDefs($providerId);
        $providerNameInLang = $provider->getNameToWebInLang($myLang, $providerType);
        $data = array(
            'providerid' => $providerId,
            'providerentity' => $provider->getEntityId(),
            'providername' => $providerNameInLang,
            'titlepage' => '<a href="' . base_url() . 'providers/detail/show/' . $providerId . '">' . $providerNameInLang . '</a>',
            'subtitlepage' => lang('statsmngmt'),
        );
        $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        if (strcasecmp($providerType, 'sp') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        }
        $data['breadcrumbs'] = array(
            $plist,
            array('url' => base_url('providers/detail/show/' . $providerId . ''), 'name' => '' . $providerNameInLang . ''),
            array('url' => '#', 'name' => lang('statsmngmt'), 'type' => 'current'),

        );

        if (empty($statDefId)) {
            $this->title = lang('title_statdefs');
            $data['content_view'] = 'manage/statdefs_show_view';


            if (count($statDefinitions) > 0) {
                $res = array();
                foreach ($statDefinitions as $v) {
                    $is_sys = $v->getType();
                    $alert = FALSE;
                    if ($is_sys === 'sys') {
                        $sysmethod = $v->getSysDef();
                        if (empty($sysmethod) || !array_key_exists($sysmethod, $this->ispreworkers)) {
                            $alert = TRUE;
                        }
                    }
                    $res[] = array('title' => '' . $v->getTitle() . '',
                        'id' => '' . $v->getId() . '',
                        'desc' => '' . $v->getDescription() . '',
                        'alert' => $alert,
                    );
                }

                $data['existingStatDefs'] = $res;
            }
            return $this->load->view('page', $data);
        }
        /**
         * @var $statDefinition models\ProviderStatsDef
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => '' . $statDefId . '', 'provider' => '' . $providerId . ''));
        if (empty($statDefinition)) {
            show_error('detail for stat def not found');
        }
        $overwrite = $statDefinition->getOverwrite();
        $overwriteTxt = lang('rr_notoverwritestatfile');
        if ($overwrite) {
            $overwriteTxt = lang('rr_overwritestatfile');
        }
        $data['defid'] = $statDefId;
        $data2 = array(
            array(
                'name' => '' . lang('rr_statdefshortname') . '',
                'value' => '' . $statDefinition->getName() . '',
            ),
            array(
                'name' => '' . lang('rr_statdefshortname') . '',
                'value' => '' . $statDefinition->getName() . '',
            ),
            array(
                'name' => '' . lang('rr_title') . '',
                'value' => '' . $statDefinition->getTitle() . '',
            ),
            array(
                'name' => lang('rr_description'),
                'value' => '' . $statDefinition->getDescription() . ''
            ),
            array(
                'name' => '' . lang('rr_statfiles') . '',
                'value' => '' . $overwriteTxt . ''
            )
        );

        $type = $statDefinition->getType();
        if ($type === 'sys') {
            $data2[] = array('name' => '' . lang('typeofstaddef') . '', 'value' => '' . lang('builtinstatdef') . '');
            $sysdef = $statDefinition->getSysDef();
            if (empty($sysdef)) {
                $data2[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '<span class="alert">' . lang('rr_empty') . '</span>');
                log_message('error', 'StatDefinition with id:' . $statDefinition->getId() . ' is set to use predefined statcollection but name of worker not defined');
            } else {

                if (!array_key_exists($sysdef, $this->ispreworkers)) {
                    $data2[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '<span class="alert">' . lang('builtincolnovalid') . '</span>');
                } else {
                    $sysdefdesc = '';
                    if (isset($this->ispreworkers['' . $sysdef . '']['desc'])) {
                        $sysdefdesc = $this->ispreworkers['' . $sysdef . '']['desc'];
                    }
                    $data2[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '' . $sysdef . ':<br />' . $sysdefdesc . '');
                }
            }
        } else {
            $data2[] = array('name' => '' . lang('rr_statdefsourceurl') . '', 'value' => $statDefinition->getSourceUrl());
            $data2[] = array('name' => '' . lang('rr_statdefformat') . '', 'value' => $statDefinition->getFormatType());
            $method = $statDefinition->getHttpMethod();
            $data2[] = array('name' => '' . lang('rr_httpmethod') . '', 'value' => strtoupper($method));
            if ($method === 'post') {
                $params = $statDefinition->getPostOptions();
                $vparams = '';
                if (!empty($params) && is_array($params)) {
                    foreach ($params as $k => $v) {
                        $vparams .= '' . htmlentities($k) . ': ' . htmlentities($v) . '<br />';
                    }
                }
                $data2[] = array('name' => '' . lang('rr_postoptions') . '', 'value' => '' . $vparams . '');
            }
            $accesstype = $statDefinition->getAccessType();
            if ($accesstype === 'anon') {
                $vaccesstype = lang('rr_anon');
                $data2[] = array('name' => '' . lang('rr_typeaccess') . '', 'value' => '' . $vaccesstype . '');
            } else {
                $vaccesstype = 'Basic Authentication';
                $data2[] = array('name' => '' . lang('rr_typeaccess') . '', 'value' => '' . $vaccesstype . '');
                $data2[] = array('name' => '' . lang('rr_username') . '', 'value' => '' . htmlentities($statDefinition->getAuthUser()) . '');
                $data2[] = array('name' => '' . lang('rr_password') . '', 'value' => '***********');
            }
        }
        /**
         * @var $statfiles models\ProviderStatsCollection[]
         */
        $statfiles = $statDefinition->getStatistics();

        if (!empty($statfiles) && count($statfiles) > 0) {
            $statv = '<ul>';
            $downurl = base_url() . 'manage/statistics/show/';
            $dowinfo = lang('statfilegenerated');
            foreach ($statfiles as $st) {
                $createdAt = date('Y-m-d H:i:s', $st->getCreatedAt()->format("U") + j_auth::$timeOffset);
                $statv .= '<li><a href="' . $downurl . $st->getId() . '">' . $dowinfo . ': ' . $createdAt . '</a></li>';
            }
            $statv .= '</ul>';
            $data2[] = array('name' => '' . lang('generatedstatslist') . '', 'value' => '' . $statv . '');
        } else {
            $data2[] = array('name' => '' . lang('generatedstatslist') . '', 'value' => '' . lang('notfound') . '');
        }
        $data['details'] = $data2;
        $data['content_view'] = 'manage/statdef_detail.php';
        $this->load->view('page', $data);


    }

    public function statdefedit($providerid = null, $statdefid = null)
    {
        if (empty($statdefid) || empty($providerid) || !ctype_digit($statdefid) || !ctype_digit($providerid)) {
            show_error('Page not found', 404);
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $isStats = $this->isStats();
        if ($isStats !== TRUE) {
            show_error('not found', 404);
        }
        /**
         * @var $statDefinition models\ProviderStatsDef
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $statdefid, 'provider' => $providerid));
        if (empty($statDefinition)) {
            show_error('Statdef Page not found', 404);
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $statDefinition->getProvider();
        $myLang = MY_Controller::getLang();
        $providerType = $provider->getType();
        $providerLangName = $provider->getNameToWebInLang($myLang, $providerType);
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $providerid . '', 'write', 'entity', '');
        if (!$hasAccess) {
            show_error('no access', 403);
        }

        $data = array(
            'providerid' => $providerid,
            'providerentity' => $provider->getEntityId(),
            'providername' => $providerLangName,
            'statdeftitle' => $statDefinition->getTitle(),
            'statdefshortname' => $statDefinition->getName(),
            'statdefdesc' => $statDefinition->getDescription(),
            'statdefoverwrite' => (boolean)$statDefinition->getOverwrite(),
            'statdefid'=>$statDefinition->getId(),
            'statdefpredefworker'=> $statDefinition->getSysDef(),
            'statdefsourceurl'=>$statDefinition->getSourceUrl(),
            'statdefmethod'=> $statDefinition->getHttpMethod(),
            'statdefformattype'=>$statDefinition->getFormatType(),
            'statdefaccesstype'=>$statDefinition->getAccessType(),
            'statdefauthuser'=>$statDefinition->getAuthUser(),
            'statdefpass'=> $statDefinition->getAuthPass(),
            'content_view'=>'manage/statdefs_editform_view',
        );
        $workersdescriptions = '<ul>';
        if (count($this->ispreworkers) > 0) {
            $workerdropdown = array();
            foreach ($this->ispreworkers as $key => $value) {
                if (is_array($value) && array_key_exists('worker', $value)) {
                    $workerdropdown['' . $key . ''] = $key;
                    if (array_key_exists('desc', $value)) {
                        $workersdescriptions .= '<li><b>' . $key . '</b>: ' . html_escape($value['desc']) . '</li>';
                    }
                }
            }
            if (count($workerdropdown) > 0) {
                $data['showpredefined'] = TRUE;
                $data['workerdropdown'] = $workerdropdown;
            }
        }
        $workersdescriptions .= '</ul>';

        $data['workersdescriptions'] = $workersdescriptions;


        $presysdef = $statDefinition->getType();
        $data['statdefpredef'] = FALSE;
        if (!empty($presysdef) && $presysdef === 'sys') {
            $data['statdefpredef'] = TRUE;
        }

        $statdefpostparam = $statDefinition->getPostOptions();

        $data['statdefpostparam'] = '';
        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $data['providerid'] . '">' . $data['providername'] . '</a>';
        $data['subtitlepage'] = lang('statdefeditform');
        $data['submenupage'][] = array('name' => lang('statdeflist'), 'link' => '' . base_url() . 'manage/statdefs/show/' . $data['providerid'] . '');
        if (!empty($statdefpostparam)) {
            foreach ($statdefpostparam as $key => $value) {
                $data['statdefpostparam'] .= $key . '$:$' . $value . '$$';
            }
        }

        $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        if (strcasecmp($providerType, 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        }

        $data['breadcrumbs'] = array(
            $plist,
            array('url' => base_url('providers/detail/show/' . $providerid . ''), 'name' => '' . $providerLangName . ''),
            array('url' => base_url('manage/statdefs/show/' . $providerid . ''), 'name' => '' . lang('statsmngmt') . ''),
            array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),

        );


        if ($this->newStatDefSubmitValidate() === FALSE) {
            return $this->load->view('page', $data);
        }
        $accesstype = $this->input->post('accesstype');
        $overwrite = $this->input->post('overwrite');
        $usepredefined = $this->input->post('usepredefined');
        $prepostoptions = $this->input->post('postoptions');
        $p2 = explode('$$', $prepostoptions);
        $postoptions = array();
        if (!empty($p2) && is_array($p2) && count($p2) > 0) {
            foreach ($p2 as $k => $v) {
                if (empty($v)) {
                    unset($p2[$k]);
                    continue;
                }
                $y = preg_split('/(\$:\$)/', $v, 2);
                if (count($y) === 2) {
                    $postoptions['' . trim($y['0']) . ''] = trim($y['1']);
                }
            }
        }

        $statDefinition->setName($this->input->post('defname'));
        $statDefinition->setTitle($this->input->post('titlename'));
        $statDefinition->setDescription($this->input->post('description'));
        if (!empty($overwrite) && $overwrite === 'yes') {
            $statDefinition->setOverwriteOn();
        } else {
            $statDefinition->setOverwriteOff();
        }

        if (!empty($usepredefined) && $usepredefined === 'yes') {
            $statDefinition->setType('sys');
            $statDefinition->setSysDef($this->input->post('gworker'));
        } else {
            $statDefinition->setSysDef(NULL);
            $statDefinition->setType('ext');
            $statDefinition->setHttpMethod($this->input->post('httpmethod'));
            $statDefinition->setPostOptions($postoptions);
            $statDefinition->setUrl($this->input->post('sourceurl'));
            $statDefinition->setAccess($accesstype);
            $statDefinition->setFormatType($this->input->post('formattype'));
            if ($accesstype !== 'anon') {
                $statDefinition->setAuthuser($this->input->post('userauthn'));
                $statDefinition->setAuthpass($this->input->post('passauthn'));
            }
        }
        $this->em->persist($statDefinition);
        $this->em->flush();
        $data['message'] = lang('updated');
        $data['providerid'] = $provider->getId();
        $data['content_view'] = 'manage/updatestatdefsuccess';
        $this->load->view('page', $data);

    }

    public function newStatDef($providerid = null)
    {
        if (empty($providerid) || !ctype_digit($providerid) || $this->isStats() !== TRUE) {
            show_error('Page not found', 404);
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
        $myLang = MY_Controller::getLang();
        $this->title = lang('title_newstatdefs');
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
        if (empty($provider)) {
            show_error('Provider not found', 404);
        }
        $providerType = $provider->getType();
        $providerLangName = $provider->getNameToWebInLang($myLang, $providerType);
        $this->load->library('zacl');

        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

        if (!$hasAccess) {
            show_error(lang('rr_noperm'), 403);
        }
        if (strcasecmp($providerType, 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        } else {
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        }
        $data = array(
            'providerid' => $provider->getId(),
            'providerentity' => $provider->getEntityId(),
            'providername' => $providerLangName,
            'titlepage' => '<a href="' . base_url() . 'providers/detail/show/' . $provider->getId() . '">' . $providerLangName . '</a>',
            'subtitlepage' => lang('title_newstatdefs'),
            'submenupage' => array('name' => lang('statdeflist'), 'link' => '' . base_url() . 'manage/statdefs/show/' . $provider->getId() . ''),
            'breadcrumbs' => array(
                $plist,
                array('url' => base_url('providers/detail/show/' . $provider->getId() . ''), 'name' => '' . $providerLangName . ''),
                array('url' => base_url('manage/statdefs/show/' . $provider->getId() . ''), 'name' => '' . lang('statsmngmt') . ''),
                array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),

            ),
            'content_view' => 'manage/statdefs_newform_view'

        );
        $workersdescriptions = '<ul>';
        if (count($this->ispreworkers) > 0) {
            $workerdropdown = array();
            foreach ($this->ispreworkers as $key => $value) {
                if (is_array($value) && array_key_exists('worker', $value)) {
                    $workerdropdown['' . $key . ''] = $key;
                    if (array_key_exists('desc', $value)) {
                        $workersdescriptions .= '<li><b>' . $key . '</b>: ' . html_escape($value['desc']) . '</li>';
                    }
                }
            }
            if (count($workerdropdown) > 0) {
                $data['showpredefined'] = TRUE;
                $data['workerdropdown'] = $workerdropdown;
            }
        }
        $workersdescriptions .= '</ul>';
        $data['workersdescriptions'] = $workersdescriptions;

        if ($this->newStatDefSubmitValidate() === FALSE) {
            return $this->load->view('page', $data);
        }
        $formattype = $this->input->post('formattype');
        $overwrite = $this->input->post('overwrite');
        $usepredefined = $this->input->post('usepredefined');
        $prepostoptions = $this->input->post('postoptions');
        $p2 = explode('$$', $prepostoptions);
        $postoptions = array();
        if (!empty($p2) && is_array($p2) && count($p2) > 0) {
            foreach ($p2 as $v) {
                $y = preg_split('/(\$:\$)/', $v, 2);
                if (count($y) === 2) {
                    $postoptions['' . trim($y['0']) . ''] = trim($y['1']);
                }
            }
        }

        $nStatDef = new models\ProviderStatsDef;
        $nStatDef->setName($this->input->post('defname'));
        $nStatDef->setTitle($this->input->post('titlename'));
        $nStatDef->setDescription($this->input->post('description'));
        if (!empty($overwrite) && $overwrite === 'yes') {
            $nStatDef->setOverwriteOn();
        }

        if (!empty($usepredefined) && $usepredefined === 'yes') {
            $nStatDef->setType('sys');
            $nStatDef->setSysDef($this->input->post('gworker'));
        } else {
            $nStatDef->setType('ext');
            $nStatDef->setHttpMethod($this->input->post('httpmethod'));
            $nStatDef->setPostOptions($postoptions);
            $nStatDef->setUrl($this->input->post('sourceurl'));
            $nStatDef->setAccess($this->input->post('accesstype'));
            $nStatDef->setFormatType($formattype);
            $nStatDef->setAuthuser($this->input->post('userauthn'));
            $nStatDef->setAuthpass($this->input->post('passauthn'));

        }
        $provider->getStatDefinitions($nStatDef);
        $nStatDef->setProvider($provider);

        $this->em->persist($nStatDef);
        $this->em->persist($provider);
        $this->em->flush();
        $data['content_view'] = 'manage/newstatdefsuccess';
        $data['message'] = lang('stadefadded');
        $this->load->view('page', $data);


    }

    private function newStatDefSubmitValidate()
    {
        $this->form_validation->set_rules('defname', 'Short name', 'required|trim|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('titlename', 'Title name', 'required|trim|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('description', 'Description', 'required|trim|min_length[5]|max_length[1024]|xss_clean');
        $this->form_validation->set_rules('overwrite', 'Overwrite', 'trim|max_length[10]|xss_clean');
        $this->form_validation->set_rules('usepredefined', 'Predefined', 'trim|max_length[10]|xss_clean');
        $userpredefined = $this->input->post('usepredefined');
        if (empty($userpredefined) || $userpredefined !== 'yes') {
            $this->form_validation->set_rules('sourceurl', 'Source URL', 'required|trim|valid_extendedurl');
            $allowedmethods = serialize(array('post', 'get'));
            $this->form_validation->set_rules('httpmethod', 'Method', 'required|trim|matches_inarray[' . $allowedmethods . ']');
            $allowedformats = serialize(array('image', 'rrd', 'svg'));
            $this->form_validation->set_rules('formattype', 'Format', 'required|trim|matches_inarray[' . $allowedformats . ']');
            $allowedaccess = serialize(array('anon', 'basicauthn'));
            $this->form_validation->set_rules('accesstype', 'Access type', 'required|trim|xss_clean|matches_inarray[' . $allowedaccess . ']');
            if ($this->input->post('accesstype') === 'basicauthn') {
                $this->form_validation->set_rules('userauthn', 'Username', 'trim|required|xss_clean');
                $this->form_validation->set_rules('passauthn', 'Password', 'trim|required');
            } else {
                $this->form_validation->set_rules('userauthn', 'Username', 'trim|xss_clean');
                $this->form_validation->set_rules('passauthn', 'Password', 'trim');
            }
            $this->form_validation->set_rules('postoptions', 'Post options', 'trim');
        } else {
            $pworkers = array_keys($this->ispreworkers);
            $allowedworkers = serialize($pworkers);
            $this->form_validation->set_rules('gworker', 'Predefined stats', 'required|trim|xss_clean|matches_inarray[' . $allowedworkers . ']');
        }

        return $this->form_validation->run();
    }

    /**
     * @param $providerid
     * @return array
     */
    private function getExistingStatsDefs($providerid)
    {
        return $this->em->getRepository("models\ProviderStatsDef")->findBy(array('provider' => '' . $providerid . ''));
    }

    public function remove($defid = null)
    {
        $msg = null;
        /**
         * @var $def models\ProviderStatsDef
         */
        if (!$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
            $s = 403;
            $msg = 'Access denied';
        } elseif (empty($defid) || !ctype_digit($defid)) {
            $s = 404;
            $msg = 'not found';
        } else {
            $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
            if (empty($def)) {
                $s = 404;
                $msg = 'not found';
            }
        }
        if (!empty($s)) {
            set_status_header($s);
            echo $msg;
            return;
        }
        $inputProviderId = $this->input->post('prvid');
        $inputDefId = $this->input->post('defid');
        $provider = $def->getProvider();

        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess) {
            set_status_header(403);
            echo 'Access denied';
        } elseif (empty($inputProviderId) || empty($inputDefId) || !is_numeric($inputProviderId) || !is_numeric($inputDefId)) {
            log_message('debug', 'no prvid and defid or not numeric in post form');
            set_status_header(403);
            echo 'Access denied';
        } elseif ((strcmp($inputProviderId, $provider->getId()) != 0) || (strcmp($inputDefId, $defid) != 0)) {
            log_message('error', 'remove statdefid received inccorect params');
            set_status_header(403);
            echo 'Access denied';
        } else {
            $this->em->remove($def);
            try {
                $this->em->flush();
                echo "OK";
            } catch (Exception $e) {
                log_message('error', __METHOD__ . ': ' . $e);
                set_status_header(500);
                echo 'Internal server error';
            }
        }
    }

}
