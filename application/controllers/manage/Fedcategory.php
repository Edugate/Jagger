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
 * Fedcategory Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Fedcategory extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $this->load->library('form_element');
        $this->load->library('zacl');
        $this->title = lang('title_fedcategory');
    }

    private function _submit_validate($id=null)
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('formsubmit', 'submit', 'required|trim|xss_clean');
        if ($this->input->post('formsubmit') === 'update' || $this->input->post('formsubmit') === 'add')
        {
            $this->form_validation->set_rules('fed[]', lang('rrfedcatmembers'), 'integer');
            $this->form_validation->set_rules('buttonname', lang('tbl_catbtnname'), 'required|trim|min_length[5]|max_length[50]|xss_clean|fedcategory_unique['.$id.']');
            $this->form_validation->set_rules('fullname', lang('tbl_catbtnititlename'), 'required|trim|min_length[5]|max_length[200]|xss_clean');
            $this->form_validation->set_rules('description', lang('rr_description'), 'required|trim|min_length[5]|max_length[500]|xss_clean');
        }
        return $this->form_validation->run();
    }

    public function addnew()
    {
        $isAdmin = $this->j_auth->isAdministrator();
        if (!$isAdmin)
        {
            show_error('perm denied', 403);
        }
        if($this->_submit_validate())
        {
            $submittype = $this->input->post('formsubmit');
            if (strcasecmp($submittype, 'add') == 0)
            {
                $cat = new models\FederationCategory;
                $name = $this->input->post('buttonname');
                $fullname = $this->input->post('fullname');
                $description = $this->input->post('description');
                $cat->populate($name,$fullname,$description);
                $this->em->persist($cat);
                $this->em->flush();
                $data['content_view'] = 'manage/fedcatnew_success';
                $data['success_message'] = lang('newfedcatadded');
                $this->load->view('page',$data);
                
            }
        }
        else
        {
           $data['titlepage'] = lang('newfedcategory');
           $data['content_view'] = 'manage/fedcatnew_view';
           $this->load->view('page',$data);

        }
 
    }

    public function edit($cat = null)
    {
        
        if (!empty($cat) && !ctype_digit($cat))
        {
            show_error('not found', 404);
        }

        $isAdmin = $this->j_auth->isAdministrator();
        if (!$isAdmin)
        {
            show_error('perm denied', 403);
        }
        $currentCategory = $this->em->getRepository("models\FederationCategory")->findOneBy(array('id' => $cat));
        if (empty($currentCategory))
        {
            show_error('not found', 404);
        }
        if ($this->_submit_validate($currentCategory->getId()))
        {
            $submittype = $this->input->post('formsubmit');
            if (strcasecmp($submittype, 'update') == 0)
            {


                $postedFeds = $this->input->post('fed');
                $buttonname = $this->input->post('buttonname');
                $fullname = $this->input->post('fullname');
                $description = $this->input->post('description');
                if (!empty($postedFeds) && is_array($postedFeds))
                {
                    log_message('debug', 'Fedcat: post received');
                    unset($postedFeds['controlkey']);
                    $members = $currentCategory->getFederations();
                    $federations = $this->em->getRepository("models\Federation")->findAll();
                    foreach ($federations as $value)
                    {
                        $g[$value->getId()] = $value;
                    }
                    // all federations
                    $federations = $g;

                    foreach ($members as $m)
                    {
                        $fedid = $m->getId();
                        $foundKey = array_search($fedid, $postedFeds);
                        if (is_null($foundKey) || $foundKey === FALSE)
                        {
                            $currentCategory->removeFederation($m);
                        }
                        else
                        {
                            unset($postedFeds[$foundKey]);
                        }
                    }
                    foreach ($postedFeds as $k => $v)
                    {
                        if (array_key_exists($v, $federations))
                        {
                            $members->add($federations[$v]);
                        }
                    }
                    $currentCategory->setName($buttonname);
                    $currentCategory->setFullName($fullname);
                    $currentCategory->setDescription($description);
                    $this->em->persist($currentCategory);
                    $this->em->flush();
                    $data['success_message'] = lang('updated');
                }
            }
            elseif (strcasecmp($submittype, 'remove') == 0)
            {
                $this->em->remove($currentCategory);
                $this->em->flush();
                $data['success_message'] = 'Federation category has been removed';
                $data['content_view'] = 'manage/fedcatremoved_view';
                $this->load->view('page',$data);
            }
        }

        $data['buttonname'] = $currentCategory->getName();
        $data['fullname'] = $currentCategory->getFullName();
        $data['description'] = $currentCategory->getDescription();
        $data['isdefault'] = $currentCategory->getIsDefault();
        $members = $currentCategory->getFederations();
        $federations = $this->em->getRepository("models\Federation")->findAll();
        $mult = array();
        foreach ($federations as $f)
        {
            if ($members->contains($f))
            {
                $mult[] = array('fedname' => '' . $f->getName() . '', 'fedid' => $f->getId(), 'member' => '1');
            }
            else
            {
                $mult[] = array('fedname' => '' . $f->getName() . '', 'fedid' => '' . $f->getId() . '', 'member' => '0');
            }
        }

        $data['multi'] = $mult;
        $data['content_view'] = 'manage/fedcatedit_view';
        $this->load->view('page', $data);
    }

    public function show($cat = NULL)
    {
        if (!empty($cat) && !ctype_digit($cat))
        {
            show_error('not found', 404);
        }
        /**
         * @todo ACL check
         */
        if (empty($cat))
        {
            $cats = $this->em->getRepository("models\FederationCategory")->findAll();
            $data['subtitle'] = '';
        }
        else
        {
            $cats = $this->em->getRepository("models\FederationCategory")->findBy(array('id' => $cat));
        }
        $result = array();
        $isAdmin = $this->j_auth->isAdministrator();
        if($isAdmin)
        {
           $data['showaddbtn'] = TRUE;
        }
        $editLinkLang = lang('rr_edit');
        $baseurl = base_url();
        foreach ($cats as $c)
        {
            $default = '';
            if ($c->getIsDefault())
            {
                $default = makeLabel('active', lang('rr_default'), lang('rr_default'));
            }
            $editlink = '';
            if ($isAdmin)
            {
                $editlink = '<a href="' . $baseurl . 'manage/fedcategory/edit/' . $c->getId() . '">' . $editLinkLang . '</a>';
            }
            $result[] = array(
                'name' => $c->getName() . ' ' . $editlink . ' ' . $default,
                'full' => $c->getFullName(),
                'desc' => $c->getDescription(),
            );
        }
        $data['titlepage'] = lang('rrfedcatslist');
        $data['result'] = $result;
        $data['content_view'] = 'manage/fedcategory_view';
        $this->load->view('page', $data);
    }

}
