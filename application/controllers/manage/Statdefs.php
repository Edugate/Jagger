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
        $isStats = $this->isStats();
        if (!$isStats) {
            set_status_header(404);
            echo 'Feature not enabled';
            return;
        }
        if (!$this->input->is_ajax_request() || empty($defid) || !ctype_digit($defid) || !$this->j_auth->logged_in()) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        /**
         * @var $statDefinition models\ProviderStatsDef
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
        if (empty($statDefinition)) {
            set_status_header(404);
            echo 'Not found';
            return;
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $statDefinition->getProvider();
        if (empty($provider)) {
            set_status_header(404);
            echo 'Not found';
            return;
        }
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess) {
            set_status_header(403);
            echo 'Access denied';
            return;
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
            $job_handle = $gmclient->doBackground("externalstatcollection", serialize($params));
            $_SESSION['jobs']['stadef']['' . $defid . ''] = $job_handle;
            log_message('debug', 'GEARMAN: Job: ' . $job_handle);
        } elseif (($params['type'] === 'sys') && !empty($params['sysdef'])) {
            $ispredefined = $this->config->item('predefinedstats');
            if (!empty($ispredefined) && is_array($ispredefined) && array_key_exists($params['sysdef'], $ispredefined)) {
                if (array_key_exists('worker', $ispredefined['' . $params['sysdef'] . '']) && !empty($ispredefined['' . $params['sysdef'] . '']['worker'])) {
                    $workername = $ispredefined['' . $params['sysdef'] . '']['worker'];
                    $job_handle = $gmclient->doBackground('' . $workername . '', serialize($params));
                    $_SESSION['jobs']['stadef']['' . $defid . ''] = $job_handle;
                }
            }
        }
        echo json_encode(array('status' => lang('taskssent') . ' '));
    }

    public function show($providerid = null, $defid = null)
    {
        if (empty($providerid) || !ctype_digit($providerid)) {
            show_error('Page not found', 404);
            return null;
        }
        $isStats = $this->isStats();

        if ($isStats !== TRUE) {
            show_error('not found', 404);
            return null;
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        } else {
            $jLang = MY_Controller::getLang();

            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
            if (empty($provider)) {
                show_error('Provider not found', 404);
            }
            $providerType = $provider->getType();
            $providerType = strtolower($providerType);
            if (strcasecmp($providerType, 'both') == 0) {
                $providerType = 'idp';
            }
            $this->load->library('zacl');

            $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

            if (!$hasAccess) {
                show_error(lang('rr_noperm'), 403);
            }

            /**
             * @var $ed models\ProviderStatsDef[]
             */

            $ed = $this->getExistingStatsDefs($provider->getId());
            $langname = $provider->getNameToWebInLang($jLang, $providerType);
            $data = array(
                'providerid' => $provider->getId(),
                'providerentity' => $provider->getEntityId(),
                'providername' => $langname,
                'titlepage' => '<a href="' . base_url() . 'providers/detail/show/' . $provider->getId() . '">' . $langname . '</a>',
                'subtitlepage' => lang('statsmngmt'),
            );

            if (strcasecmp($providerType, 'SP') == 0) {
                $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
            } else {
                $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
            }
            $data['breadcrumbs'] = array(
                $plist,
                array('url' => base_url('providers/detail/show/' . $provider->getId() . ''), 'name' => '' . $langname . ''),
                array('url' => '#', 'name' => lang('statsmngmt'), 'type' => 'current'),

            );

            if (empty($defid)) {
                $this->title = lang('title_statdefs');
                $data['content_view'] = 'manage/statdefs_show_view';


                if (!empty($ed) && is_array($ed) && count($ed) > 0) {
                    $res = array();
                    $predefinedstats = array();
                    $temppred = $this->config->item('predefinedstats');
                    if (!empty($temppred) && is_array($temppred)) {
                        $predefinedstats = $temppred;
                    }
                    foreach ($ed as $v) {
                        $is_sys = $v->getType();
                        $alert = FALSE;
                        if ($is_sys === 'sys') {
                            $sysmethod = $v->getSysDef();
                            if (empty($sysmethod) || !array_key_exists($sysmethod, $predefinedstats)) {
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
                $this->load->view('page', $data);
            } else {
                if (!ctype_digit($defid)) {
                    show_error('incorrect fedid', 404);
                }
                /**
                 * @var $statdef models\ProviderStatsDef
                 */
                $statdef = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => '' . $defid . '', 'provider' => '' . $providerid . ''));
                if (empty($statdef)) {
                    show_error('detail for stat def not found');
                } else {
                    $data['defid'] = $defid;
                    $d = array();
                    $d[] = array(
                        'name' => '' . lang('rr_statdefshortname') . '',
                        'value' => '' . $statdef->getName() . '',
                    );
                    $d[] = array(
                        'name' => '' . lang('rr_statdefshortname') . '',
                        'value' => '' . $statdef->getName() . '',
                    );
                    $d[] = array(
                        'name' => '' . lang('rr_title') . '',
                        'value' => '' . $statdef->getTitle() . '',
                    );
                    $d[] = array('name' => lang('rr_description'), 'value' => '' . $statdef->getDescription() . '');
                    $overwrite = $statdef->getOverwrite();
                    if ($overwrite) {
                        $d[] = array('name' => '' . lang('rr_statfiles') . '', 'value' => '' . lang('rr_overwritestatfile') . '');
                    } else {
                        $d[] = array('name' => '' . lang('rr_statfiles') . '', 'value' => '' . lang('rr_notoverwritestatfile') . '');
                    }
                    $type = $statdef->getType();
                    if ($type === 'sys') {
                        $d[] = array('name' => '' . lang('typeofstaddef') . '', 'value' => '' . lang('builtinstatdef') . '');
                        $sysdef = $statdef->getSysDef();
                        if (empty($sysdef)) {
                            $d[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '<span class="alert">' . lang('rr_empty') . '</span>');
                            log_message('error', 'StatDefinition with id:' . $statdef->getId() . ' is set to use predefined statcollection but name of worker not defined');
                        } else {

                            if (empty($this->ispreworkers) || !is_array($this->ispreworkers) || !array_key_exists($sysdef, $this->ispreworkers)) {
                                $d[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '<span class="alert">' . lang('builtincolnovalid') . '</span>');
                            } else {
                                $sysdefdesc = '';
                                if (isset($this->ispreworkers['' . $sysdef . '']['desc'])) {
                                    $sysdefdesc = $this->ispreworkers['' . $sysdef . '']['desc'];
                                }
                                $d[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '' . $sysdef . ':<br />' . $sysdefdesc . '');
                            }
                        }
                    } else {
                        $d[] = array('name' => '' . lang('rr_statdefsourceurl') . '', 'value' => $statdef->getSourceUrl());
                        $d[] = array('name' => '' . lang('rr_statdefformat') . '', 'value' => $statdef->getFormatType());
                        $method = $statdef->getHttpMethod();
                        $d[] = array('name' => '' . lang('rr_httpmethod') . '', 'value' => strtoupper($method));
                        if ($method === 'post') {
                            $params = $statdef->getPostOptions();
                            $vparams = '';
                            if (!empty($params) && is_array($params)) {
                                foreach ($params as $k => $v) {
                                    $vparams .= '' . htmlentities($k) . ': ' . htmlentities($v) . '<br />';
                                }
                            }
                            $d[] = array('name' => '' . lang('rr_postoptions') . '', 'value' => '' . $vparams . '');
                        }
                        $accesstype = $statdef->getAccessType();
                        if ($accesstype === 'anon') {
                            $vaccesstype = lang('rr_anon');
                            $d[] = array('name' => '' . lang('rr_typeaccess') . '', 'value' => '' . $vaccesstype . '');
                        } else {
                            $vaccesstype = 'Basic Authentication';
                            $d[] = array('name' => '' . lang('rr_typeaccess') . '', 'value' => '' . $vaccesstype . '');
                            $d[] = array('name' => '' . lang('rr_username') . '', 'value' => '' . htmlentities($statdef->getAuthUser()) . '');
                            $d[] = array('name' => '' . lang('rr_password') . '', 'value' => '***********');
                        }
                    }
                    /**
                     * @var $statfiles models\ProviderStatsCollection[]
                     */
                    $statfiles = $statdef->getStatistics();

                    if (!empty($statfiles) && count($statfiles) > 0) {
                        $statv = '<ul>';
                        $downurl = base_url() . 'manage/statistics/show/';
                        $dowinfo = lang('statfilegenerated');
                        foreach ($statfiles as $st) {
                            $createdAt = date('Y-m-d H:i:s', $st->getCreatedAt()->format("U") + j_auth::$timeOffset);
                            $statv .= '<li><a href="' . $downurl . $st->getId() . '">' . $dowinfo . ': ' . $createdAt . '</a></li>';
                        }
                        $statv .= '</ul>';
                        $d[] = array('name' => '' . lang('generatedstatslist') . '', 'value' => '' . $statv . '');
                    } else {
                        $d[] = array('name' => '' . lang('generatedstatslist') . '', 'value' => '' . lang('notfound') . '');
                    }
                    $data['details'] = $d;
                    $data['content_view'] = 'manage/statdef_detail.php';
                    $this->load->view('page', $data);
                }
            }
        }
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
         * @var $statdef models\ProviderStatsDef
         */
        $statdef = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $statdefid, 'provider' => $providerid));
        if (empty($statdef)) {
            show_error('Statdef Page not found', 404);
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $statdef->getProvider();
        $myLang = MY_Controller::getLang();
        $providerType = $provider->getType();
        $providerLangName = $provider->getNameToWebInLang($myLang, $providerType);
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $providerid . '', 'write', 'entity', '');
        if (!$hasAccess) {
            show_error('no access', 403);
        }

        $data['providerid'] = $providerid;
        $data['providerentity'] = $provider->getEntityId();
        $data['providername'] = $provider->getName();

        $workersdescriptions = '<ul>';
        if (!empty($this->ispreworkers) && is_array($this->ispreworkers) && count($this->ispreworkers) > 0) {
            $workerdropdown = array();
            foreach ($this->ispreworkers as $key => $value) {
                if (is_array($value) && array_key_exists('worker', $value)) {
                    $workerdropdown['' . $key . ''] = $key;
                    if (array_key_exists('desc', $value)) {
                        $workersdescriptions .= '<li><b>' . $key . '</b>: ' . htmlentities($value['desc']) . '</li>';
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

        if (empty($data['providername'])) {
            $data['providername'] = $data['providerentity'];
        }

        $data['statdeftitle'] = $statdef->getTitle();
        $data['statdefshortname'] = $statdef->getName();
        $data['statdefdesc'] = $statdef->getDescription();
        $data['statdefoverwrite'] = (boolean)$statdef->getOverwrite();
        $presysdef = $statdef->getType();
        if (!empty($presysdef) && $presysdef === 'sys') {
            $data['statdefpredef'] = TRUE;
        } else {
            $data['statdefpredef'] = FALSE;
        }
        $data['statdefid'] = $statdef->getId();
        $data['statdefpredefworker'] = $statdef->getSysDef();
        $data['statdefsourceurl'] = $statdef->getSourceUrl();
        $data['statdefmethod'] = $statdef->getHttpMethod();
        $statdefpostparam = $statdef->getPostOptions();
        $data['statdefformattype'] = $statdef->getFormatType();
        $data['statdefaccesstype'] = $statdef->getAccessType();
        $data['statdefauthuser'] = $statdef->getAuthUser();
        $data['statdefpass'] = $statdef->getAuthPass();
        $data['statdefpostparam'] = '';
        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $data['providerid'] . '">' . $data['providername'] . '</a>';
        $data['subtitlepage'] = lang('statdefeditform');
        $data['submenupage'][] = array('name' => lang('statdeflist'), 'link' => '' . base_url() . 'manage/statdefs/show/' . $data['providerid'] . '');
        if (!empty($statdefpostparam)) {
            foreach ($statdefpostparam as $key => $value) {
                $data['statdefpostparam'] .= $key . '$:$' . $value . '$$';
            }
        }
        if (strcasecmp($providerType, 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        } else {
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        }

        $data['breadcrumbs'] = array(
            $plist,
            array('url' => base_url('providers/detail/show/' . $provider->getId() . ''), 'name' => '' . $providerLangName . ''),
            array('url' => base_url('manage/statdefs/show/' . $provider->getId() . ''), 'name' => '' . lang('statsmngmt') . ''),
            array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),

        );


        $data['content_view'] = 'manage/statdefs_editform_view';
        if ($this->newStatDefSubmitValidate() === FALSE) {
            return $this->load->view('page', $data);
        } else {
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

            $statdef->setName($this->input->post('defname'));
            $statdef->setTitle($this->input->post('titlename'));
            $statdef->setDescription($this->input->post('description'));
            if (!empty($overwrite) && $overwrite === 'yes') {
                $statdef->setOverwriteOn();
            } else {
                $statdef->setOverwriteOff();
            }

            if (!empty($usepredefined) && $usepredefined === 'yes') {
                $statdef->setType('sys');
                $statdef->setSysDef($this->input->post('gworker'));
            } else {
                $statdef->setSysDef(NULL);
                $statdef->setType('ext');
                $statdef->setHttpMethod($this->input->post('httpmethod'));
                $statdef->setPostOptions($postoptions);
                $statdef->setUrl($this->input->post('sourceurl'));
                $statdef->setAccess($accesstype);
                $statdef->setFormatType($this->input->post('formattype'));
                if ($accesstype !== 'anon') {
                    $statdef->setAuthuser($this->input->post('userauthn'));
                    $statdef->setAuthpass($this->input->post('passauthn'));
                }
            }
            $this->em->persist($statdef);
            $this->em->flush();
            $data['message'] = lang('updated');
            $data['providerid'] = $provider->getId();
            $data['content_view'] = 'manage/updatestatdefsuccess';
            $this->load->view('page', $data);
        }
    }

    public function newStatDef($providerid = null)
    {

        if (empty($providerid) || !ctype_digit($providerid)) {
            show_error('Page not found', 404);
        }
        $myLang = MY_Controller::getLang();
        $isStats = $this->isStats();
        if ($isStats !== TRUE) {
            show_error('Feature disabled', 404);
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }
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
        if (!empty($this->ispreworkers) && is_array($this->ispreworkers) && count($this->ispreworkers) > 0) {
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

        $s = new models\ProviderStatsDef;
        $s->setName($this->input->post('defname'));
        $s->setTitle($this->input->post('titlename'));
        $s->setDescription($this->input->post('description'));
        if (!empty($overwrite) && $overwrite === 'yes') {
            $s->setOverwriteOn();
        }

        if (!empty($usepredefined) && $usepredefined === 'yes') {
            $s->setType('sys');
            $s->setSysDef($this->input->post('gworker'));
        } else {
            $s->setType('ext');
            $s->setHttpMethod($this->input->post('httpmethod'));
            $s->setPostOptions($postoptions);
            $s->setUrl($this->input->post('sourceurl'));
            $s->setAccess($this->input->post('accesstype'));
            $s->setFormatType($formattype);
            $s->setAuthuser($this->input->post('userauthn'));
            $s->setAuthpass($this->input->post('passauthn'));

        }
        $provider->getStatDefinitions($s);
        $s->setProvider($provider);

        $this->em->persist($s);
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
            $p = $this->config->item('predefinedstats');
            $pworkers = array();
            if (!empty($p) && is_array($p)) {
                foreach ($p as $key => $value) {
                    $pworkers[] = $key;
                }
            }
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
        $r = $this->em->getRepository("models\ProviderStatsDef")->findBy(array('provider' => '' . $providerid . ''));
        return $r;
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
