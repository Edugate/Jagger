<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Coc Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Ec extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->library('zacl');
    }

    public function show($id = null)
    {
        $this->title = lang('title_entcats');
        if (isset($id))
        {
            show_error('Argument passed to page  not allowed', 403);
            return;
        }
        $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
        $obj_list = $this->em->getRepository("models\Coc")->findBy(array('type' => 'entcat'));
        $data['rows'] = array();
        if (is_array($obj_list) && count($obj_list) > 0)
        {
            foreach ($obj_list as $c)
            {
                $countProviders = $c->getProvidersCount();
                $isEnabled = $c->getAvailable();
                if ($has_write_access)
                {
                    $l = '<a href="' . base_url() . 'manage/ec/edit/' . $c->getId() . '" ><i class="fi-pencil"></i></a>';
                    if (!$isEnabled)
                    {
                        $l .= '&nbsp;&nbsp;<a href="' . base_url() . 'manage/ec/remove/' . $c->getId() . '" class="withconfirm" data-jagger-fieldname="'.$c->getName().'" data-jagger-ec="' . $c->getId() . '" data-jagger-counter="'.$countProviders.'"><i class="fi-trash"></i></a>';
                    }
                }
                else
                {
                    $l = '';
                }

                if ($isEnabled)
                {
                    $lbl = '<span class="lbl lbl-active">' . lang('rr_enabled') . '</span>';
                }
                else
                {
                    $lbl = '<span class="lbl lbl-disabled">' . lang('rr_disabled') . '</span>';
                }
                $lbl .= '<span class="label secondary ecmembers" data-jagger-jsource="'.base_url('manage/regpolicy/getmembers/'.$c->getId().'').'">' . $countProviders . '</span> ';
                $subtype = $c->getSubtype();
                if (empty($subtype))
                {
                    $subtype = '<span class="label alert">' . lang('lbl_missing') . '</span>';
                }
                $data['rows'][] = array($c->getName(), $subtype, anchor($c->getUrl(), $c->getUrl(), array('target' => '_blank', 'class' => 'new_window')), $c->getDescription(), $lbl, $l);
            }
        }
        else
        {
            $data['error_message'] = lang('rr_noentcatsregistered');
        }
        $data['showaddbutton'] = FALSE;
        if ($has_write_access)
        {
            $data['showaddbutton'] = TRUE;
        }

        $data['titlepage'] = lang('ent_list_title');

        $data['breadcrumbs'] = array(
            array('url'=>'#','name'=>lang('entcats_menulink'),'type'=>'current'),
        );
        $data['content_view'] = 'manage/coc_show_view';
        $this->load->view('page', $data);
    }
    function getMembers($ecid)
    {
        if(!$this->input->is_ajax_request() || !$this->j_auth->logged_in())
        {
            set_status_header(403);
            echo 'Access denied';
            return;
        }

        $myLang = MY_Controller::getLang();
        /**
         * @var $regPolicy models\Coc
         */
        $entCategory = $this->em->getRepository("models\Coc")->findOneBy(array('id'=>$ecid));
        if(empty($entCategory))
        {
            set_status_header(404);
            echo 'no members found';
            return;
        }
        /**
         * @var $policyMembers models\Provider[]
         */
        $ecMembers = $entCategory->getProviders();
        $result = array();
        foreach($ecMembers as $member)
        {
            $result[] = array(
                'entityid'=>$member->getEntityId(),
                'provid'=>$member->getId(),
                'name'=>$member->getNameToWebInLang($myLang),
            );
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    private function _add_submit_validate()
    {
        $this->form_validation->set_rules('name', lang('entcat_displayname'), 'required|trim|cocname_unique');
        $this->form_validation->set_rules('attrname', lang('rr_attr_name'), 'required|trim|xss_clean');
        $attrname = $this->input->post('attrname');
        $this->form_validation->set_rules('url', lang('entcat_value'), 'required|trim|valid_url|ecUrlInsert[' . $attrname . ']');
        $this->form_validation->set_rules('description', lang('entcat_description'), 'xss_clean');
        $this->form_validation->set_rules('cenabled', lang('entcat_enabled'), 'xss_clean');
        return $this->form_validation->run();
    }

    private function _edit_submit_validate($id)
    {
        $attrname = $this->input->post('attrname');
        $this->form_validation->set_rules('name', lang('entcat_displayname'), 'required|trim|cocname_unique_update[' . $id . ']');
        $this->form_validation->set_rules('attrname', lang('rr_attr_name'), 'required|trim');
        $ecUrlUpdateParams = serialize(array('id' => $id, 'subtype' => $attrname));
        $this->form_validation->set_rules('url', lang('entcat_value'), 'required|trim|valid_url|ecUrlUpdate[' . $ecUrlUpdateParams . ']');
        $this->form_validation->set_rules('description', lang('entcat_description'), 'xss_clean');
        $this->form_validation->set_rules('cenabled', lang('entcat_enabled'), 'xss_clean');
        return $this->form_validation->run();
    }

    public function add()
    {
        $this->title = lang('title_addentcat');
        $data['titlepage'] = lang('title_addentcat');
        $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
        if (!$has_write_access)
        {
            show_error('No access', 401);
            return;
        }

        if ($this->_add_submit_validate() === TRUE)
        {
            $name = $this->input->post('name');
            $url = $this->input->post('url');
            $cenabled = $this->input->post('cenabled');
            $description = $this->input->post('description');

            $ncoc = new models\Coc;
            $ncoc->setName($name);
            $ncoc->setUrl($url);
            $ncoc->setType('entcat');
            $allowedattrs = attrsEntCategoryList();
            $inputAttrname = $this->input->post('attrname');
            if (in_array($inputAttrname, $allowedattrs))
            {
                $ncoc->setSubtype($inputAttrname);
            }
            if (!empty($description))
            {
                $ncoc->setDescription($description);
            }
            if (!empty($cenabled) && $cenabled == 'accept')
            {
                $ncoc->setAvailable(TRUE);
            }
            else
            {
                $ncoc->setAvailable(FALSE);
            }
            $this->em->persist($ncoc);
            $this->em->flush();

            $data['success_message'] = lang('rr_entcatadded');
        }
        else
        {
            $f = form_open();
            $this->load->library('formelement');
            $f .= $this->formelement->generateAddCoc();
            $f .= '<div class="buttons small-12 medium-10 large-10 columns end text-right">';
            $f .= '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">' . lang('rr_reset') . '</button> ';
            $f .= '<button type="submit" name="modify" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div>';

            $f .= form_close();
            $data['form'] = $f;
        }
        $data['breadcrumbs'] = array(
            array('url'=>base_url('manage/ec/show'),'name'=>lang('title_entcats')),
            array('url'=>'#','name'=>lang('title_addentcat'),'type'=>'current'),
        );
        $data['content_view'] = 'manage/coc_add_view';
        $this->load->view('page', $data);
    }

    public function edit($id)
    {
        $this->title = lang('title_entcatedit');

        if (!(!empty($id) && ctype_digit($id)))
        {
            show_error('Not found', 404);
            return;
        }
        $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id' => $id, 'type' => 'entcat'));
        if (empty($coc))
        {
            show_error('Not found', 404);
            return;
        }
        $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
        if (!$has_write_access)
        {
            show_error('No access', 401);
            return;
        }
        $data['titlepage'] = lang('title_entcat') . ': ' . htmlentities($coc->getName());
        $data['subtitlepage'] = lang('title_entcatedit');

        if ($this->_edit_submit_validate($id) === TRUE)
        {
            $enable = $this->input->post('cenabled');
            if (!empty($enable) && $enable == 'accept')
            {
                $coc->setAvailable(TRUE);
            }
            else
            {
                $coc->setAvailable(FALSE);
            }
            $coc->setName($this->input->post('name'));
            $coc->setUrl($this->input->post('url'));
            $allowedattrs = attrsEntCategoryList();
            $inputAttrname = $this->input->post('attrname');
            if (in_array($inputAttrname, $allowedattrs))
            {
                $coc->setSubtype($inputAttrname);
            }
            $coc->setDescription($this->input->post('description'));
            $this->em->persist($coc);
            $this->em->flush();
            $data['success_message'] = lang('updated');
        }
        $data['coc_name'] = $coc->getName();
        $this->load->library('formelement');
        $f = form_open();
        $f .= $this->formelement->generateEditCoc($coc);
        $f .= '<div class="buttons large-10 medium-10 small-12 text-right columns end">';
        $f .= '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">' . lang('rr_reset') . '</button> ';
        $f .= '<button type="submit" name="modify" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button></div>';
        $f .= form_close();
        $data['form'] = $f;
        $data['breadcrumbs'] = array(
            array('url'=>base_url('manage/ec/show'),'name'=>lang('title_entcats')),
            array('url'=>'#','name'=>lang('title_editform'),'type'=>'current'),
        );
        $data['content_view'] = 'manage/coc_edit_view';
        $this->load->view('page', $data);
    }

     function remove($id=null)
    {
        if(empty($id) || !ctype_digit($id))
        {
            set_status_header(404);
            echo 'incorrect id or id not provided';
            return;
        }
        if(!$this->input->is_ajax_request())
        {
            set_status_header(403);
            echo 'access denied';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if(!$loggedin)
        {
            set_status_header(403);
            echo 'access denied';
            return;
        }
        $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
        if(!$has_write_access)
        {
            set_status_header(403);
            echo 'access denied';
            return;
        }
        $ec = $this->em->getRepository("models\Coc")->findOneBy( array('id'=>''.$id.'','type'=>'entcat','is_enabled'=>false));
        if(empty($ec))
        {
            set_status_header(403);
            echo 'Registration policy doesnt exist or is not disabled';
            return;
        }
        $this->em->remove($ec);
        $this->em->flush();
        echo "OK";
        return;
    }
}
