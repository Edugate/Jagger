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

    protected  $ispreworkers;
    function __construct()
    {
        parent::__construct();
        $this->ispreworkers = $this->config->item('predefinedstats');
        $this->load->library('form_validation');
    }

    public function download($defid = null)
    {
        if (!$this->input->is_ajax_request())
        {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        if (empty($defid) || !ctype_digit($defid))
        {
            set_status_header(404);
            echo 'Not found';
            return;
        }
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if (empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
        {
            set_status_header(404);
            echo 'Not found';
            return;

        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        /**
         * @var $def models\ProviderStatsDef
         */
        $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
        if (empty($def))
        {
            set_status_header(404);
            echo 'Not found';
            return;
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $def->getProvider();
        if (empty($provider))
        {

            set_status_header(404);
            echo 'Not found';
            return;
        }
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess)
        {

            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $params = array(
            'defid' => $def->getId(),
            'entityid' => $provider->getEntityId(),
            'url' => $def->getSourceUrl(),
            'type' => $def->getType(),
            'sysdef' => $def->getSysDef(),
            'title' => $def->getTitle(),
            'httpmethod' => $def->getHttpMethod(),
            'format' => $def->getFormatType(),
            'accesstype' => $def->getAccessType(),
            'authuser' => $def->getAuthUser(),
            'authpass' => $def->getAuthPass(),
            'postoptions' => $def->getPostOptions(),
            'displayoptions' => $def->getDisplayOptions(),
            'overwrite' => $def->getOverwrite()
        );

        $gmclient = new GearmanClient();
        $jobservers = array();
        $j = $this->config->item('gearmanconf');
        foreach ($j['jobserver'] as $v)
        {
            $jobservers[] = '' . $v['ip'] . ':' . $v['port'] . '';
        }
        try
        {
            $gmclient->addServers('' . implode(",", $jobservers) . '');
        }
        catch (Exception $e)
        {
            log_message('error', 'GeamanClient cant add job-server');
            echo "Cant add  job-server(s)";
            return false;
        }
        if (!empty($_SESSION['jobs']['stadef']['' . $defid . '']))
        {
            $stat = $gmclient->jobStatus($_SESSION['jobs']['stadef']['' . $defid . '']);

            if (($stat['0'] === TRUE) && ($stat['1'] === FALSE))
            {

                echo json_encode(array('status' => lang('rr_jobinqueue')));
                return false;
            }
            elseif ($stat[0] === $stat[1] && $stat[1] === true)
            {
                $percent = $stat[2] / $stat[3] * 100;
                echo json_encode(array('status' => lang('rr_jobdonein') . ' ' . $percent . ' %'));
                return false;
            }
        }

        if ($params['type'] === 'ext')
        {
            $job_handle = $gmclient->doBackground("externalstatcollection", serialize($params));
            $_SESSION['jobs']['stadef']['' . $defid . ''] = $job_handle;
            log_message('debug', 'GEARMAN: Job: ' . $job_handle);
        }
        elseif (($params['type'] === 'sys') && !empty($params['sysdef']))
        {
            $ispredefined = $this->config->item('predefinedstats');
            if (!empty($ispredefined) && is_array($ispredefined) && array_key_exists($params['sysdef'], $ispredefined))
            {
                if (array_key_exists('worker', $ispredefined['' . $params['sysdef'] . '']) && !empty($ispredefined['' . $params['sysdef'] . '']['worker']))
                {
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
        if (empty($providerid) || !is_numeric($providerid))
        {
            show_error('Page not found', 404);
            return null;
        }
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if (empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
        {
            show_error('not found', 404);
            return null;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        else
        {
            $jLang = MY_Controller::getLang();

            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
            if (empty($provider))
            {
                show_error('Provider not found', 404);
            }
            $providerType = $provider->getType();
            $providerType = strtolower($providerType);
            if (strcasecmp($providerType, 'both') == 0)
            {
                $providerType = 'idp';
            }
            $this->load->library('zacl');

            $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

            if (!$hasAccess)
            {
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

            if(strcasecmp($providerType,'SP')==0)
            {
                $plist = array('url'=>base_url('providers/sp_list/showlist'),'name'=>lang('serviceproviders'));
            }
            else
            {
                $plist = array('url'=>base_url('providers/idp_list/showlist'),'name'=>lang('identityproviders'));
            }
            $data['breadcrumbs'] = array(
                $plist,
                array('url'=>base_url('providers/detail/show/'.$provider->getId().''),'name'=>''.$langname.''),
                array('url'=>'#','name'=>lang('statsmngmt'),'type'=>'current'),

            );

            if (empty($defid))
            {
                $this->title = lang('title_statdefs');
                $data['content_view'] = 'manage/statdefs_show_view';


                if (!empty($ed) && is_array($ed) && count($ed) > 0)
                {
                    $res = array();
                    $predefinedstats = array();
                    $temppred = $this->config->item('predefinedstats');
                    if (!empty($temppred) && is_array($temppred))
                    {
                        $predefinedstats = $temppred;
                    }
                    foreach ($ed as $v)
                    {
                        $is_sys = $v->getType();
                        $alert = FALSE;
                        if ($is_sys === 'sys')
                        {
                            $sysmethod = $v->getSysDef();
                            if (empty($sysmethod) || !array_key_exists($sysmethod, $predefinedstats))
                            {
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
            }
            else
            {
                if (!is_numeric($defid))
                {
                    show_error('incorrect fedid', 404);
                }
                /**
                 * @var $statdef models\ProviderStatsDef
                 */
                $statdef = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => '' . $defid . '', 'provider' => '' . $providerid . ''));
                if (empty($statdef))
                {
                    show_error('detail for stat def not found');
                }
                else
                {
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
                    if ($overwrite)
                    {
                        $d[] = array('name' => '' . lang('rr_statfiles') . '', 'value' => '' . lang('rr_overwritestatfile') . '');
                    }
                    else
                    {
                        $d[] = array('name' => '' . lang('rr_statfiles') . '', 'value' => '' . lang('rr_notoverwritestatfile') . '');
                    }
                    $type = $statdef->getType();
                    if ($type === 'sys')
                    {
                        $d[] = array('name' => '' . lang('typeofstaddef') . '', 'value' => '' . lang('builtinstatdef') . '');
                        $sysdef = $statdef->getSysDef();
                        if (empty($sysdef))
                        {
                            $d[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '<span class="alert">' . lang('rr_empty') . '</span>');
                            log_message('error', 'StatDefinition with id:' . $statdef->getId() . ' is set to use predefined statcollection but name of worker not defined');
                        }
                        else
                        {

                            if (empty($this->ispreworkers) || !is_array($this->ispreworkers) || !array_key_exists($sysdef, $this->ispreworkers))
                            {
                                $d[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '<span class="alert">' . lang('builtincolnovalid') . '</span>');
                            }
                            else
                            {
                                $sysdefdesc = '';
                                if (isset($this->ispreworkers['' . $sysdef . '']['desc']))
                                {
                                    $sysdefdesc = $this->ispreworkers['' . $sysdef . '']['desc'];
                                }
                                $d[] = array('name' => '' . lang('nameofbuiltinstatdef') . '', 'value' => '' . $sysdef . ':<br />' . $sysdefdesc . '');
                            }
                        }
                    }
                    else
                    {
                        $d[] = array('name' => '' . lang('rr_statdefsourceurl') . '', 'value' => $statdef->getSourceUrl());
                        $d[] = array('name' => '' . lang('rr_statdefformat') . '', 'value' => $statdef->getFormatType());
                        $method = $statdef->getHttpMethod();
                        $d[] = array('name' => '' . lang('rr_httpmethod') . '', 'value' => strtoupper($method));
                        if ($method === 'post')
                        {
                            $params = $statdef->getPostOptions();
                            $vparams = '';
                            if (!empty($params) && is_array($params))
                            {
                                foreach ($params as $k => $v)
                                {
                                    $vparams .='' . htmlentities($k) . ': ' . htmlentities($v) . '<br />';
                                }
                            }
                            $d[] = array('name' => '' . lang('rr_postoptions') . '', 'value' => '' . $vparams . '');
                        }
                        $accesstype = $statdef->getAccessType();
                        if ($accesstype === 'anon')
                        {
                            $vaccesstype = lang('rr_anon');
                            $d[] = array('name' => '' . lang('rr_typeaccess') . '', 'value' => '' . $vaccesstype . '');
                        }
                        else
                        {
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

                    if (!empty($statfiles) && count($statfiles) > 0)
                    {
                        $statv = '<ul>';
                        $downurl = base_url() . 'manage/statistics/show/';
                        $dowinfo = lang('statfilegenerated');
                        foreach ($statfiles as $st)
                        {
                            $createdAt = date('Y-m-d H:i:s',$st->getCreatedAt()->format("U")+j_auth::$timeOffset);
                            $statv .= '<li><a href="'.$downurl.$st->getId().'">'. $dowinfo.': '.$createdAt.'</a></li>';
                        }
                        $statv .= '</ul>';
                        $d[] = array('name' => '' . lang('generatedstatslist') . '', 'value' => '' . $statv . '');
                    }
                    else
                    {
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
        if (empty($statdefid) || empty($providerid) || !ctype_digit($statdefid) || !ctype_digit($providerid))
        {
            show_error('Page not found', 404);
        }
        if (!$this->j_auth->logged_in())
        {
            redirect('auth/login', 'location');
        }
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if (empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
        {
            show_error('not found', 404);
        }
        /**
         * @var $statdef models\ProviderStatsDef
         */
        $statdef = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $statdefid, 'provider' => $providerid));
        if (empty($statdef))
        {
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
        if (!$hasAccess)
        {
            show_error('no access', 403);
        }

        $data['providerid'] = $providerid;
        $data['providerentity'] = $provider->getEntityId();
        $data['providername'] = $provider->getName();

        $workersdescriptions = '<ul>';
        if (!empty($this->ispreworkers) && is_array($this->ispreworkers) && count($this->ispreworkers) > 0)
        {
            $workerdropdown = array();
            foreach ($this->ispreworkers as $key => $value)
            {
                if (is_array($value) && array_key_exists('worker', $value))
                {
                    $workerdropdown['' . $key . ''] = $key;
                    if (array_key_exists('desc', $value))
                    {
                        $workersdescriptions .= '<li><b>' . $key . '</b>: ' . htmlentities($value['desc']) . '</li>';
                    }
                }
            }
            if (count($workerdropdown) > 0)
            {
                $data['showpredefined'] = TRUE;
                $data['workerdropdown'] = $workerdropdown;
            }
        }
        $workersdescriptions .='</ul>';
        $data['workersdescriptions'] = $workersdescriptions;

        if (empty($data['providername']))
        {
            $data['providername'] = $data['providerentity'];
        }

        $data['statdeftitle'] = $statdef->getTitle();
        $data['statdefshortname'] = $statdef->getName();
        $data['statdefdesc'] = $statdef->getDescription();
        $data['statdefoverwrite'] = (boolean) $statdef->getOverwrite();
        $presysdef = $statdef->getType();
        if (!empty($presysdef) && $presysdef === 'sys')
        {
            $data['statdefpredef'] = TRUE;
        }
        else
        {
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
        if (!empty($statdefpostparam))
        {
            foreach ($statdefpostparam as $key => $value)
            {
                $data['statdefpostparam'] .= $key . '$:$' . $value . '$$';
            }
        }
        if(strcasecmp($providerType,'SP')==0)
        {
            $plist = array('url'=>base_url('providers/sp_list/showlist'),'name'=>lang('serviceproviders'));
        }
        else
        {
            $plist = array('url'=>base_url('providers/idp_list/showlist'),'name'=>lang('identityproviders'));
        }

        $data['breadcrumbs'] = array(
            $plist,
            array('url'=>base_url('providers/detail/show/'.$provider->getId().''),'name'=>''.$providerLangName.''),
            array('url'=>base_url('manage/statdefs/show/'.$provider->getId().''),'name'=>''.lang('statsmngmt').''),
            array('url'=>'#','name'=>lang('title_editform'),'type'=>'current'),

        );


        $data['content_view'] = 'manage/statdefs_editform_view';
        if ($this->newStatDefSubmitValidate() === FALSE)
        {
            $this->load->view('page', $data);
        }
        else
        {
            $defname = $this->input->post('defname');
            $titlename = $this->input->post('titlename');
            $accesstype = $this->input->post('accesstype');
            $description = $this->input->post('description');
            $sourceurl = $this->input->post('sourceurl');
            $userauthn = $this->input->post('userauthn');
            $passauthn = $this->input->post('passauthn');
            $formattype = $this->input->post('formattype');
            $method = $this->input->post('httpmethod');
            $gworker = $this->input->post('gworker');
            $overwrite = $this->input->post('overwrite');
            $usepredefined = $this->input->post('usepredefined');
            $prepostoptions = $this->input->post('postoptions');
            $p2 = explode('$$', $prepostoptions);
            $postoptions = array();
            if (!empty($p2) && is_array($p2) && count($p2) > 0)
            {
                foreach ($p2 as $k => $v)
                {
                    if (empty($v))
                    {
                        unset($p2[$k]);
                        continue;
                    }
                    $y = preg_split('/(\$:\$)/', $v, 2);
                    if (count($y) === 2)
                    {
                        $postoptions['' . trim($y['0']) . ''] = trim($y['1']);
                    }
                }
            }

            $statdef->setName($defname);
            $statdef->setTitle($titlename);
            $statdef->setDescription($description);
            if (!empty($overwrite) && $overwrite === 'yes')
            {
                $statdef->setOverwriteOn();
            }
            else
            {
                $statdef->setOverwriteOff();
            }

            if (!empty($usepredefined) && $usepredefined === 'yes')
            {
                $statdef->setType('sys');
                $statdef->setSysDef($gworker);
            }
            else
            {
                $statdef->setSysDef(NULL);
                $statdef->setType('ext');
                $statdef->setHttpMethod($method);
                $statdef->setPostOptions($postoptions);
                $statdef->setUrl($sourceurl);
                $statdef->setAccess($accesstype);
                $statdef->setFormatType($formattype);
                if ($accesstype !== 'anon')
                {
                    $statdef->setAuthuser($userauthn);
                    $statdef->setAuthpass($passauthn);
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

        if (empty($providerid) || !is_numeric($providerid))
        {
            show_error('Page not found', 404);
        }
        $myLang = MY_Controller::getLang();
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if (empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
        {
            show_error('not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        else
        {

            /**
             * @var $provider models\Provider
             */
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
            if (empty($provider))
            {
                show_error('Provider not found', 404);
            }
            $providerType = $provider->getType();
            $providerLangName = $provider->getNameToWebInLang($myLang, $providerType);
            $this->load->library('zacl');

            $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

            if (!$hasAccess)
            {
                show_error(lang('rr_noperm'), 403);
            }

            $this->title = lang('title_newstatdefs');
            $data['providerid'] = $provider->getId();
            $data['providerentity'] = $provider->getEntityId();
            $data['providername'] = $providerLangName;
            $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $data['providerid'] . '">' . $providerLangName . '</a>';
            $data['subtitlepage'] = lang('title_newstatdefs');
            $data['submenupage'][] = array('name' => lang('statdeflist'), 'link' => '' . base_url() . 'manage/statdefs/show/' . $data['providerid'] . '');
            $workersdescriptions = '<ul>';
            if (!empty($this->ispreworkers) && is_array($this->ispreworkers) && count($this->ispreworkers) > 0)
            {
                $workerdropdown = array();
                foreach ($this->ispreworkers as $key => $value)
                {
                    if (is_array($value) && array_key_exists('worker', $value))
                    {
                        $workerdropdown['' . $key . ''] = $key;
                        if (array_key_exists('desc', $value))
                        {
                            $workersdescriptions .= '<li><b>' . $key . '</b>: ' . htmlentities($value['desc']) . '</li>';
                        }
                    }
                }
                if (count($workerdropdown) > 0)
                {
                    $data['showpredefined'] = TRUE;
                    $data['workerdropdown'] = $workerdropdown;
                }
            }
            $workersdescriptions .='</ul>';
            $data['workersdescriptions'] = $workersdescriptions;

            if (empty($data['providername']))
            {
                $data['providername'] = $data['providerentity'];
            }
            if(strcasecmp($providerType,'SP')==0)
            {
                $plist = array('url'=>base_url('providers/sp_list/showlist'),'name'=>lang('serviceproviders'));
            }
            else
            {
                $plist = array('url'=>base_url('providers/idp_list/showlist'),'name'=>lang('identityproviders'));
            }
            $data['breadcrumbs'] = array(
                $plist,
                array('url'=>base_url('providers/detail/show/'.$provider->getId().''),'name'=>''.$providerLangName.''),
                array('url'=>base_url('manage/statdefs/show/'.$provider->getId().''),'name'=>''.lang('statsmngmt').''),
                array('url'=>'#','name'=>lang('title_editform'),'type'=>'current'),

            );
            $data['content_view'] = 'manage/statdefs_newform_view';

            if ($this->newStatDefSubmitValidate() === FALSE)
            {
                $this->load->view('page', $data);
            }
            else
            {
                $defname = $this->input->post('defname');
                $titlename = $this->input->post('titlename');
                $sourceurl = $this->input->post('sourceurl');
                $accesstype = $this->input->post('accesstype');
                $description = $this->input->post('description');
                $userauthn = $this->input->post('userauthn');
                $passauthn = $this->input->post('passauthn');
                $formattype = $this->input->post('formattype');
                $method = $this->input->post('httpmethod');
                $gworker = $this->input->post('gworker');
                $overwrite = $this->input->post('overwrite');
                $usepredefined = $this->input->post('usepredefined');
                $prepostoptions = $this->input->post('postoptions');
                $p2 = explode('$$', $prepostoptions);
                $postoptions = array();
                if (!empty($p2) && is_array($p2) && count($p2) > 0)
                {
                    foreach ($p2 as $v)
                    {
                        $y = preg_split('/(\$:\$)/', $v, 2);
                        if (count($y) === 2)
                        {
                            $postoptions['' . trim($y['0']) . ''] = trim($y['1']);
                        }
                    }
                }

                $s = new models\ProviderStatsDef;
                $s->setName($defname);
                $s->setTitle($titlename);
                $s->setDescription($description);
                if (!empty($overwrite) && $overwrite === 'yes')
                {
                    $s->setOverwriteOn();
                }

                if (!empty($usepredefined) && $usepredefined === 'yes')
                {
                    $s->setType('sys');
                    $s->setSysDef($gworker);
                }
                else
                {
                    $s->setType('ext');
                    $s->setHttpMethod($method);
                    $s->setPostOptions($postoptions);
                    $s->setUrl($sourceurl);
                    $s->setAccess($accesstype);
                    $s->setFormatType($formattype);
                    if ($accesstype !== 'anon')
                    {
                        $s->setAuthuser($userauthn);
                        $s->setAuthpass($passauthn);
                    }
                }
                $provider->getStatDefinitions($s);
                $s->setProvider($provider);

                $this->em->persist($s);
                $this->em->persist($provider);
                $this->em->flush();

                if(strcasecmp($providerType,'SP')==0)
                {
                    $plist = array('url'=>base_url('providers/sp_list/showlist'),'name'=>lang('serviceproviders'));
                }
                else
                {
                    $plist = array('url'=>base_url('providers/idp_list/showlist'),'name'=>lang('identityproviders'));
                }

                $data['breadcrumbs'] = array(
                    $plist,
                    array('url'=>base_url('providers/detail/show/'.$provider->getId().''),'name'=>''.$providerLangName.''),
                    array('url'=>base_url('manage/statdefs/show/'.$provider->getId().''),'name'=>''.lang('statsmngmt').''),
                    array('url'=>'#','name'=>lang('title_editform'),'type'=>'current'),

                );
                $data['providerid'] = $provider->getId();

                $data['content_view'] = 'manage/newstatdefsuccess';
                $data['message'] = lang('stadefadded');
                $this->load->view('page', $data);
            }
        }
    }

    private function newStatDefSubmitValidate()
    {
        $this->form_validation->set_rules('defname', 'Short name', 'required|trim|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('titlename', 'Title name', 'required|trim|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('description', 'Description', 'required|trim|min_length[5]|max_length[1024]|xss_clean');
        $this->form_validation->set_rules('overwrite', 'Overwrite', 'trim|max_length[10]|xss_clean');

        $this->form_validation->set_rules('usepredefined', 'Predefined', 'trim|max_length[10]|xss_clean');
        $userpredefined = $this->input->post('usepredefined');
        if (empty($userpredefined) or $userpredefined !== 'yes')
        {
            $this->form_validation->set_rules('sourceurl', 'Source URL', 'required|trim|valid_extendedurl');
            $allowedmethods = serialize(array('post', 'get'));
            $this->form_validation->set_rules('httpmethod', 'Method', 'required|trim|matches_inarray[' . $allowedmethods . ']');
            $allowedformats = serialize(array('image', 'rrd', 'svg'));
            $this->form_validation->set_rules('formattype', 'Format', 'required|trim|matches_inarray[' . $allowedformats . ']');
            $allowedaccess = serialize(array('anon', 'basicauthn'));
            $this->form_validation->set_rules('accesstype', 'Access type', 'required|trim|xss_clean|matches_inarray[' . $allowedaccess . ']');
            if ($this->input->post('accesstype') === 'basicauthn')
            {
                $this->form_validation->set_rules('userauthn', 'Username', 'trim|required|xss_clean');
                $this->form_validation->set_rules('passauthn', 'Password', 'trim|required');
            }
            else
            {
                $this->form_validation->set_rules('userauthn', 'Username', 'trim|xss_clean');
                $this->form_validation->set_rules('passauthn', 'Password', 'trim');
            }
            $this->form_validation->set_rules('postoptions', 'Post options', 'trim');
        }
        else
        {
            $p = $this->config->item('predefinedstats');
            $pworkers = array();
            if (!empty($p) && is_array($p))
            {
                foreach ($p as $key => $value)
                {
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
        if (!$this->input->is_ajax_request())
        {
            show_error('method not allowed', 401);
        }
        $loggedin = $this->j_auth->logged_in();
        $msg ='';
        if (!$loggedin)
        {
            $s = 403;
            $msg = 'Access denied';
        }
        elseif(empty($defid) || !is_numeric($defid))
        {
            $s = 404;
            $msg ='not found';
        }
        if(!empty($s))
        {
            set_status_header($s);
            echo $msg;
            return;
        }
        /**
         * @var $def models\ProviderStatsDef
         */
        $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
        if (empty($def))
        {
            set_status_header(404);
            echo 'not found';
            return;
        }

        $provider = $def->getProvider();

        if (empty($provider))
        {
            log_message('error', 'Found orphaned statdefinition with id: ' . $def->getId());
            set_status_header(404);
            echo 'not found';
            return;
        }
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess)
        {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $inputProviderId = $this->input->post('prvid');
        $inputDefId = $this->input->post('defid');
        if (empty($inputProviderId) || empty($inputDefId) || !is_numeric($inputProviderId) || !is_numeric($inputDefId))
        {
            log_message('debug', 'no prvid and defid or not numeric in post form');
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        if ((strcmp($inputProviderId, $provider->getId()) != 0) || (strcmp($inputDefId, $defid) != 0))
        {
            log_message('error', 'remove statdefid received inccorect params');
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $this->em->remove($def);
        try
        {
            $this->em->flush();
            echo "OK";
        }
        catch (Exception $e)
        {
            log_message('error', __METHOD__ . ': ' . $e);
            set_status_header(500);
            echo 'Internal server error';
            return;
        }
    }

}
