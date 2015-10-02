<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet <noc-middleware@heanet.ie>
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Statdefs extends MY_Controller
{
    protected $ispreworkers;
    protected $myLang;
    protected $breadHelpList;

    public function __construct() {
        parent::__construct();
        $this->ispreworkers = $this->config->item('predefinedstats');
        if (!is_array($this->ispreworkers)) {
            $this->ispreworkers = array();
        }
        $this->load->library('form_validation');
        $this->myLang = MY_Controller::getLang();
        $this->breadHelpList = array(
            'idp' => array('name' => lang('identityproviders'), 'url' => base_url('providers/idp_list/showlist')),
            'sp' => array('name' => lang('serviceproviders'), 'url' => base_url('providers/sp_list/showlist')),
            'IDP' => array('name' => lang('identityproviders'), 'url' => base_url('providers/idp_list/showlist')),
            'SP' => array('name' => lang('serviceproviders'), 'url' => base_url('providers/sp_list/showlist')),
            'both' => array('name' => lang('identityproviders'), 'url' => base_url('providers/idp_list/showlist')),
            'BOTH' => array('name' => lang('identityproviders'), 'url' => base_url('providers/idp_list/showlist'))
        );
    }

    /**
     * @return bool
     */
    private function isStats() {
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if ($isgearman === true && $isstatistics === true) {
            return true;
        }
        return false;
    }


    public function download($defid = null) {
        if (!$this->input->is_ajax_request() || !ctype_digit($defid) || $this->isStats() !== true || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var models\ProviderStatsDef $statDefinition
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
        if ($statDefinition === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $statDefinition->getProvider();
        if ($provider === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess) {
            return $this->output->set_status_header(403)->set_output('Access denied');
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
            $gmclient->addServers('' . implode(',', $jobservers) . '');
        } catch (Exception $e) {
            log_message('error', 'GeamanClient cant add job-server');
            return $this->output->set_status_header(500)->set_output('Cannot add job-server(s)');
        }
        $jobsSession = $this->session->userdata('jobs');
        if (is_array($jobsSession) || isset($jobsSession['statdef']['' . $defid . ''])) {
            $stat = $gmclient->jobStatus($jobsSession['stadef']['' . $defid . '']);

            if (($stat['0'] === true) && ($stat['1'] === false)) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => lang('rr_jobinqueue'))));

            } elseif ($stat[0] === $stat[1] && $stat[1] === true) {
                $percent = $stat[2] / $stat[3] * 100;
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => lang('rr_jobdonein') . ' ' . $percent . ' %')));
            }
        }
        if ($params['type'] === 'ext') {
            $jobHandle = $gmclient->doBackground("externalstatcollection", serialize($params));
            $_SESSION['jobs']['stadef']['' . $defid . ''] = $jobHandle;
            log_message('debug', 'GEARMAN: Job: ' . $jobHandle);
        } elseif (($params['type'] === 'sys') && !empty($params['sysdef']) && array_key_exists($params['sysdef'], $this->ispreworkers) && array_key_exists('worker', $this->ispreworkers['' . $params['sysdef'] . '']) && !empty($this->ispreworkers['' . $params['sysdef'] . '']['worker'])) {
            $workername = $this->ispreworkers['' . $params['sysdef'] . '']['worker'];
            $jobHandle = $gmclient->doBackground('' . $workername . '', serialize($params));
            $_SESSION['jobs']['stadef']['' . $defid . ''] = $jobHandle;
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => lang('taskssent') . ' ')));
    }

    /**
     * @param null $providerId
     * @param null $statDefId
     * @return bool
     */
    private function validateShowGets($providerId = null, $statDefId = null) {
        if (!ctype_digit($providerId) || $this->isStats() !== true || !($statDefId === null || ctype_digit($statDefId))) {
            return false;
        }
        return true;
    }


    public function show($providerId = null, $statDefId = null) {
        if ($this->validateShowGets($providerId, $statDefId) !== true) {
            show_404();
        }
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->title = lang('title_statdefs');
        /**
         * @var models\Provider $provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerId . ''));
        if ($provider === null) {
            show_error('Provider not found', 404);
        }
        $providerType = $provider->getType();
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
        $providerNameInLang = $provider->getNameToWebInLang($this->myLang, strtolower($providerType));
        $data = array(
            'providerid' => $providerId,
            'providerentity' => $provider->getEntityId(),
            'providername' => $providerNameInLang,
            'titlepage' => '<a href="' . base_url('providers/detail/show/' . $providerId . '') . '">' . $providerNameInLang . '</a>',
            'subtitlepage' => lang('statsmngmt'),
            'breadcrumbs' => array(
                $this->breadHelpList['' . $provider->getType() . ''],
                array('url' => base_url('providers/detail/show/' . $providerId . ''), 'name' => '' . $providerNameInLang . ''),
                array('url' => '#', 'name' => lang('statsmngmt'), 'type' => 'current')
            )
        );

        if ($statDefId === null) {
            $res = array();
            foreach ($statDefinitions as $v) {
                $statdefType = $v->getType();
                $alert = false;
                if ($statdefType === 'sys') {
                    $sysmethod = $v->getSysDef();
                    $alert = (bool)(empty($sysmethod) || !array_key_exists($sysmethod, $this->ispreworkers));
                }
                $res[] = array('title' => '' . $v->getTitle() . '',
                    'id' => '' . $v->getId() . '',
                    'desc' => '' . $v->getDescription() . '',
                    'alert' => $alert,
                );
            }
            $data['content_view'] = 'manage/statdefs_show_view';
            $data['existingStatDefs'] = $res;

        } else {
            /**
             * @var $statDefinition models\ProviderStatsDef
             */
            $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => '' . $statDefId . '', 'provider' => '' . $providerId . ''));
            if ($statDefinition === null) {
                show_error('detail for stat def not found');
            }
            $data['defid'] = $statDefId;
            $data['details'] = $this->genStatDetail($statDefinition);
            $data['content_view'] = 'manage/statdef_detail.php';

        }
        $this->load->view('page', $data);

    }

    /**
     * @param \models\ProviderStatsDef $statDefinition
     * @return array
     */
    private function stadefGeneralInfo(models\ProviderStatsDef $statDefinition) {
        $statdefType = $statDefinition->getType();
        $data2 = array();
        if ($statdefType === 'sys') {
            $data2[] = array(
                'name' => '' . lang('typeofstaddef') . '',
                'value' => '' . lang('builtinstatdef') . '');
            $sysdef = $statDefinition->getSysDef();
            if (empty($sysdef)) {
                $data2[] = array(
                    'name' => '' . lang('nameofbuiltinstatdef') . '',
                    'value' => '<span class="alert">' . lang('rr_empty') . '</span>');
                log_message('error', 'StatDefinition with id:' . $statDefinition->getId() . ' is set to use predefined statcollection but name of worker not defined');
            } else {

                if (!array_key_exists($sysdef, $this->ispreworkers)) {
                    $data2[] = array(
                        'name' => '' . lang('nameofbuiltinstatdef') . '',
                        'value' => '<span class="alert">' . lang('builtincolnovalid') . '</span>');
                } else {
                    $sysdefdesc = '';
                    if (isset($this->ispreworkers['' . $sysdef . '']['desc'])) {
                        $sysdefdesc = $this->ispreworkers['' . $sysdef . '']['desc'];
                    }
                    $data2[] = array(
                        'name' => '' . lang('nameofbuiltinstatdef') . '',
                        'value' => '' . $sysdef . ':<br />' . $sysdefdesc . '');
                }
            }
        } else {
            $method = $statDefinition->getHttpMethod();

            array_push($data2,
                array(
                    'name' => '' . lang('rr_statdefsourceurl') . '',
                    'value' => $statDefinition->getSourceUrl()),
                array(
                    'name' => '' . lang('rr_statdefformat') . '',
                    'value' => $statDefinition->getFormatType()),
                array(
                    'name' => '' . lang('rr_httpmethod') . '',
                    'value' => strtoupper($method))
            );
            if ($method === 'post') {
                $params = $statDefinition->getPostOptions();
                $vparams = '';
                if (is_array($params)) {
                    foreach ($params as $k => $v) {
                        $vparams .= '' . html_escape($k) . ': ' . html_escape($v) . '<br />';
                    }
                }
                $data2[] = array(
                    'name' => '' . lang('rr_postoptions') . '',
                    'value' => '' . $vparams . '');
            }
            $accesstype = $statDefinition->getAccessType();
            if ($accesstype === 'anon') {
                $vaccesstype = lang('rr_anon');
                $data2[] = array(
                    'name' => '' . lang('rr_typeaccess') . '',
                    'value' => '' . $vaccesstype . '');
            } else {
                $vaccesstype = 'Basic Authentication';
                array_push($data2,
                    array(
                        'name' => '' . lang('rr_typeaccess') . '',
                        'value' => '' . $vaccesstype . ''),
                    array(
                        'name' => '' . lang('rr_username') . '',
                        'value' => '' . html_escape($statDefinition->getAuthUser()) . ''),
                    array(
                        'name' => '' . lang('rr_password') . '',
                        'value' => '***********')
                );

            }
        }
        return $data2;
    }

    private function genStatDetail(models\ProviderStatsDef $statDefinition) {
        $overwrite = $statDefinition->getOverwrite();
        $overwriteTxt = lang('rr_notoverwritestatfile');
        if ($overwrite) {
            $overwriteTxt = lang('rr_overwritestatfile');
        }

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

        $generalInfo = $this->stadefGeneralInfo($statDefinition);
        array_push($data2, array_values($generalInfo));

        /**
         * @var $statfiles models\ProviderStatsCollection[]
         */
        $statfiles = $statDefinition->getStatistics();

        $statv = lang('notfound');
        if (!empty($statfiles) && count($statfiles) > 0) {
            $statv = '<ul>';
            $downurl = base_url('manage/statistics/show/');
            $dowinfo = lang('statfilegenerated');
            foreach ($statfiles as $st) {
                $createdAt = date('Y-m-d H:i:s', $st->getCreatedAt()->format("U") + jauth::$timeOffset);
                $statv .= '<li><a href="' . $downurl . '/' . $st->getId() . '">' . $dowinfo . ': ' . $createdAt . '</a></li>';
            }
            $statv .= '</ul>';
        }
        $data2[] = array('name' => '' . lang('generatedstatslist') . '', 'value' => '' . $statv . '');
        return $data2;
    }

    public function statdefedit($providerid = null, $statdefid = null) {
        if (empty($statdefid) || !ctype_digit($providerid) || !ctype_digit($statdefid)) {
            show_error('Page not found', 404);
        }
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        if ($this->isStats() !== true) {
            show_error('not found', 404);
        }
        /**
         * @var $statDefinition models\ProviderStatsDef
         */
        $statDefinition = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $statdefid, 'provider' => $providerid));
        if ($statDefinition === null) {
            show_error('Statdef Page not found', 404);
        }
        /**
         * @var $provider models\Provider
         */
        $provider = $statDefinition->getProvider();
        $providerType = $provider->getType();
        $providerLangName = $provider->getNameToWebInLang($this->myLang, $providerType);
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
            'statdefid' => $statDefinition->getId(),
            'statdefpredefworker' => $statDefinition->getSysDef(),
            'statdefsourceurl' => $statDefinition->getSourceUrl(),
            'statdefmethod' => $statDefinition->getHttpMethod(),
            'statdefformattype' => $statDefinition->getFormatType(),
            'statdefaccesstype' => $statDefinition->getAccessType(),
            'statdefauthuser' => $statDefinition->getAuthUser(),
            'statdefpass' => $statDefinition->getAuthPass(),
            'content_view' => 'manage/statdefs_editform_view',
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
                $data['showpredefined'] = true;
                $data['workerdropdown'] = $workerdropdown;
            }
        }
        $workersdescriptions .= '</ul>';

        $data['workersdescriptions'] = $workersdescriptions;


        $presysdef = $statDefinition->getType();
        $data['statdefpredef'] = false;
        if ($presysdef === 'sys') {
            $data['statdefpredef'] = true;
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


        $data['breadcrumbs'] = array(
            $this->breadHelpList['' . $provider->getType() . ''],
            array('url' => base_url('providers/detail/show/' . $providerid . ''), 'name' => '' . $providerLangName . ''),
            array('url' => base_url('manage/statdefs/show/' . $providerid . ''), 'name' => '' . lang('statsmngmt') . ''),
            array('url' => '#', 'name' => lang('title_editform'), 'type' => 'current'),

        );


        if ($this->newStatDefSubmitValidate() !== true) {
            return $this->load->view('page', $data);
        }
        $this->updateStatDefinition($provider, $statDefinition);
        $this->em->persist($statDefinition);
        $this->em->flush();
        $data['message'] = lang('updated');
        $data['providerid'] = $provider->getId();
        $data['content_view'] = 'manage/updatestatdefsuccess';
        $this->load->view('page', $data);

    }


    /**
     * @param \models\Provider $provider
     * @param \models\ProviderStatsDef $statsDef
     */
    private function updateStatDefinition(\models\Provider $provider, \models\ProviderStatsDef $statsDef) {
        $statsDef->setName($this->input->post('defname'));
        $statsDef->setTitle($this->input->post('titlename'));
        $statsDef->setDescription($this->input->post('description'));
        $overwrite = $this->input->post('overwrite');
        if (!empty($overwrite) && $overwrite === 'yes') {
            $statsDef->setOverwriteOn();
        }
        else{
            $statsDef->setOverwriteOff();
        }
        $formattype = $this->input->post('formattype');
        $usepredefined = $this->input->post('usepredefined');
        $prepostoptions = $this->input->post('postoptions');
        $proptionsArray = explode('$$', $prepostoptions);
        $postoptions = array();
        if (!empty($proptionsArray) && is_array($proptionsArray) && count($proptionsArray) > 0) {
            foreach ($proptionsArray as $v) {
                $y = preg_split('/(\$:\$)/', $v, 2);
                if (count($y) === 2) {
                    $postoptions['' . trim($y['0']) . ''] = trim($y['1']);
                }
            }
        }
        if (!empty($usepredefined) && $usepredefined === 'yes') {
            $statsDef->setType('sys');
            $statsDef->setSysDef($this->input->post('gworker'));
        } else {
            $statsDef->setSysDef(null);
            $statsDef->setType('ext');
            $statsDef->setHttpMethod($this->input->post('httpmethod'));
            $statsDef->setPostOptions($postoptions);
            $statsDef->setUrl($this->input->post('sourceurl'));
            $statsDef->setAccess($this->input->post('accesstype'));
            $statsDef->setFormatType($formattype);
            $statsDef->setAuthuser($this->input->post('userauthn'));
            $statsDef->setAuthpass($this->input->post('passauthn'));

        }
        $provider->setStatDefinition($statsDef);
        $statsDef->setProvider($provider);

    }

    public function newStatDef($providerid = null) {
        if (!ctype_digit($providerid) || $this->isStats() !== true) {
            show_error('Page not found', 404);
        }
        if (!$this->jauth->isLoggedIn()) {
            redirect('auth/login', 'location');
        }
        $this->title = lang('title_newstatdefs');
        /**
         * @var $provider models\Provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
        if ($provider === null) {
            show_error('Provider not found', 404);
        }
        $providerType = $provider->getType();
        $providerLangName = $provider->getNameToWebInLang($this->myLang, $providerType);
        $this->load->library('zacl');

        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

        if (!$hasAccess) {
            show_error(lang('rr_noperm'), 403);
        }

        $data = array(
            'providerid' => $provider->getId(),
            'providerentity' => $provider->getEntityId(),
            'providername' => $providerLangName,
            'titlepage' => '<a href="' . base_url() . 'providers/detail/show/' . $provider->getId() . '">' . $providerLangName . '</a>',
            'subtitlepage' => lang('title_newstatdefs'),
            'submenupage' => array('name' => lang('statdeflist'), 'link' => '' . base_url() . 'manage/statdefs/show/' . $provider->getId() . ''),
            'breadcrumbs' => array(
                $this->breadHelpList['' . $provider->getType() . ''],
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
                $data['showpredefined'] = true;
                $data['workerdropdown'] = $workerdropdown;
            }
        }
        $workersdescriptions .= '</ul>';
        $data['workersdescriptions'] = $workersdescriptions;

        if ($this->newStatDefSubmitValidate() !== true) {
            return $this->load->view('page', $data);
        }
        $nStatDef = new models\ProviderStatsDef;
        $this->updateStatDefinition($provider,$nStatDef);
        $this->em->persist($nStatDef);
        $this->em->persist($provider);
        $this->em->flush();
        $data['content_view'] = 'manage/newstatdefsuccess';
        $data['message'] = lang('stadefadded');
        $this->load->view('page', $data);


    }

    private function newStatDefSubmitValidate() {
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
    private function getExistingStatsDefs($providerid) {
        return $this->em->getRepository("models\ProviderStatsDef")->findBy(array('provider' => '' . $providerid . ''));
    }

    public function remove($defid = null) {
        $msg = null;
        $statusCode = null;
        /**
         * @var models\ProviderStatsDef $def
         */
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            $statusCode = 403;
            $msg = 'Access denied';
        } elseif (empty($defid) || !ctype_digit($defid)) {
            $statusCode = 404;
            $msg = 'not found';
        } else {
            $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
            if (empty($def)) {
                $statusCode = 404;
                $msg = 'not found';
            }
        }
        if ($statusCode !== null) {
            return $this->output->set_header($statusCode)->set_output($msg);
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
