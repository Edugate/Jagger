<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
class Statdefs extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function download($defid = null)
    {
        if (empty($defid) or !is_numeric($defid))
        {
            show_error('not found', 404);
        }
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if(empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
        {
            show_error('not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            show_error('denied', 403);
        }
        $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $defid));
        if (empty($def))
        {
            show_error('not found', 404);
        }
        $provider = $def->getProvider();
        if (empty($provider))
        {
            show_error('not found', 404);
        }
        $islocal = $provider->getLocal();
        if(!$islocal)
        {
            show_error('no stats allowed for this entity', 403);
        }
        $this->load->library('zacl');
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess)
        {
            show_error('denied', 403);
        }
        $params['defid'] = $def->getId();
        $params['entityid'] = $provider->getEntityId();
        $params['url'] = $def->getSourceUrl();
        $params['type'] = $def->getType();
        $params['sysdef'] = $def->getSysDef();
        $params['title'] = $def->getTitle();
        $params['httpmethod'] = $def->getHttpMethod();
        $params['format'] = $def->getFormatType();
        $params['accesstype'] = $def->getAccessType();
        $params['authuser'] = $def->getAuthUser();
        $params['authpass'] = $def->getAuthPass();
        $params['postoptions'] = $def->getPostOptions();
        $params['displayoptions'] = $def->getDisplayOptions();
        $params['overwrite'] = $def->getOverwrite();

        if ($params['type'] === 'ext')
        {
            $gmclient = new GearmanClient();
            $gmclient->addServer('127.0.0.1', 4730);
            $job_handle = $gmclient->doBackground("externalstatcollection", serialize($params));
        }
    }

    public function show($providerid = null, $defid = null)
    {
        if (empty($providerid) or !is_numeric($providerid))
        {
            show_error('Page not found', 404);
            reurn;
        }
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if(empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
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
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
            if (empty($provider))
            {
                show_error('Provider not found', 404);
            }
            $islocal = $provider->getLocal();
            if(!$islocal)
            {
                show_error('No stats allowed for this entity',403);
            }
            $this->load->library('zacl');

            $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

            if (!$hasAccess)
            {
                show_error(lang('rr_noperm'), 403);
            }
            $data['providerid'] = $provider->getId();
            $data['providerentity'] = $provider->getEntityId();
            $data['providername'] = $provider->getName();
            $ed = $this->getExistingStatsDefs($provider->getId());
            if (empty($data['providername']))
            {
                $data['providername'] = $data['providerentity'];
            }
            if (empty($defid))
            {
                $this->title = lang('title_statdefs');
                $data['content_view'] = 'manage/statdefs_show_view';


                if (!empty($ed) && is_array($ed) && count($ed) > 0)
                {
                    $res = array();
                    foreach ($ed as $v)
                    {
                        $res[] = array('title' => '' . $v->getTitle() . '',
                            'id' => '' . $v->getId() . '',
                            'desc'=>''.$v->getDescription().'',
                       
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
                $statdef = null;
                foreach ($ed as $vk)
                {
                    if (!empty($statdef))
                    {
                        break;
                    }
                    $vkid = $vk->getId();
                    if ($vkid === $defid)
                    {
                        $statdef = $vk;
                    }
                }
                if (empty($statdef))
                {
                    show_error('detail for stat def not found');
                }
                else
                {
                    $d = array();

                    $data['content_view'] = 'manage/statdef_detail.php';
                    $this->load->view('page', $data);
                }
            }
        }
    }

    /**
     * @todo finish
     */
    public function stadefedit($providerid, $statdefid)
    {
        
    }

    public function newStatDef($providerid = null)
    {

        if (empty($providerid) or !is_numeric($providerid))
        {
            show_error('Page not found', 404);
        }
        $isgearman = $this->config->item('gearman');
        $isstatistics = $this->config->item('statistics');
        if(empty($isgearman) || ($isgearman !== TRUE) || empty($isstatistics) || ($isstatistics !== TRUE))
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

            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $providerid . ''));
            if (empty($provider))
            {
                show_error('Provider not found', 404);
            }
            $islocal = $provider->getLocal();
            if(!$islocal)
            {
                show_error('stats are allowed only for local entities', 403);
            }
            $this->load->library('zacl');

            $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');

            if (!$hasAccess)
            {
                show_error(lang('rr_noperm'), 403);
            }
            $this->title = lang('title_newstatdefs');
            $data['providerid'] = $provider->getId();
            $data['providerentity'] = $provider->getEntityId();
            $data['providername'] = $provider->getName();

            if (empty($data['providername']))
            {
                $data['providername'] = $data['providerentity'];
            }
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
                $sourceurl = $this->input->post('sourceurl');
                $userauthn = $this->input->post('userauthn');
                $passauthn = $this->input->post('passauthn');
                $formattype = $this->input->post('formattype');
                $method = $this->input->post('httpmethod');
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
                $s->setHttpMethod($method);
                $s->setPostOptions($postoptions);
                $s->setName($defname);
                $s->setTitle($titlename);
                $s->setDescription($description);
                $s->setUrl($sourceurl);
                $s->setAccess($accesstype);
                $s->setType('ext');
                $s->setFormatType($formattype);
                if ($accesstype !== 'anon')
                {
                    $s->setAuthuser($userauthn);
                    $s->setAuthpass($passauthn);
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
        }
    }

    private function newStatDefSubmitValidate()
    {
        $this->form_validation->set_rules('defname', 'Short name', 'required|trim|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('titlename', 'Title name', 'required|trim|min_length[3]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('description', 'Description', 'required|trim|min_length[5]|max_length[1024]|xss_clean');
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
        return $this->form_validation->run();
    }

    private function getExistingStatsDefs($providerid)
    {
        $r = $this->em->getRepository("models\ProviderStatsDef")->findBy(array('provider' => '' . $providerid . ''));
        return $r;
    }

}
