<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Spage extends MY_Controller
{

    protected $isEnabled;

    function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library(array('form_validation','table','j_auth'));
        $t = $this->config->item('pageeditor');
        $this->isEnabled = TRUE;
        if ($t === FALSE)
        {
           $this->isEnabled = FALSE;
        }
    }

    public function showall()
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $isAdmin = $this->j_auth->isAdministrator();
        if (!$isAdmin)
        {
            show_error('Permission denied', 403);
            return;
        }
        if ($this->isEnabled)
        {
            $data['addbtn'] = '<a href="' . base_url() . 'manage/spage/editarticle/new" class="button small">' . lang('rr_add') . '</a>';
        }
        else
        {
            $data['addbtn'] = '';
        }
        $articles = $this->em->getRepository("models\Staticpage")->findAll();
        $rows = array();
        $yes = lang('rr_yes');
        $no = lang('rr_no');
        $data['titlepage'] = lang('title_listspages');
        $data['rowsHeading'] = array(lang('rr_title'), lang('rr_category'), lang('rr_enabled'), lang('lbl_spageanonaccess'), lang('rr_pagecode'), '');
        $frontpage = false;
        foreach ($articles as $a)
        {
            if ($a->getPublic())
            {
                $p = $yes;
            }
            else
            {
                $p = $no;
            }
            if ($a->getEnabled())
            {
                $e = $yes;
            }
            else
            {
                $e = $no;
            }
            if ($this->isEnabled)
            {
                $editlink = '<a href="' . base_url() . 'manage/spage/editarticle/' . $a->getName() . '" class="button tiny">' . lang('rr_edit') . '</a>';
            }
            else
            {
                $editlink = '';
            }
            $code = $a->getName();
            if(strcasecmp($code, 'front_page')==0)
            {
                $frontpage = true;
            }
            $stitle = $a->getTitle();
            if(empty($stitle))
            {
                $stitle = lang('rr_notitle');
            }
            $rows[] = array(
                '<a href="'.base_url().'p/page/'.$a->getName().'">'.$stitle.'</a>', $a->getCategory(), $e, $p, $a->getName(), $editlink
            );
        }
        $data['rows'] = &$rows;     
        if(!$frontpage)
        {
            $data['msg1'] = lang('missingfrontpage');
            $data['msg2'] = lang('createpcode');    
        }
        $data['content_view'] = 'manage/spageshowall_view';
        $this->load->view('page', $data);
    }

    public function editArticle($pcode)
    {
        $pcode = trim($pcode);
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $isAdmin = $this->j_auth->isAdministrator();
        if (!$isAdmin)
        {
            show_error('Permission denied', 403);
            return;
        }
        if (!$this->isEnabled)
        {
            show_error('Feature is not enabled', 403);
            return;
        }
        $newArticle = false;
        if (strcmp($pcode, 'new') != 0)
        {
            $article = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode' => $pcode));
            if (empty($article))
            {
                show_error('Not found', 404);
                return;
            }
        }
        else
        {
            $newArticle = true;
        }

        if ($this->submitValidate($pcode))
        {
            if ($newArticle)
            {
                $article = new models\Staticpage;
                $article->setName($this->input->post('acode'));
            }
            $content = strip_tags($this->input->post('acontent'),'<p><img><a><b><i><strong><table><tbody><th><tr><th><h1><h2><h3><h4><h5><h6><em><s><ol><ul><li><blockquote><pre><hr><div><span>');
            $contentTitle = $this->input->post('atitle');
            $isEnabled = $this->input->post('aenabled');
            $category = $this->input->post('acategory');
            if (!empty($isEnabled) && strcmp($isEnabled, '1') == 0)
            {
                $article->setEnabled(true);
            }
            else
            {
                $article->setEnabled(false);
            }
            $isPublic = $this->input->post('apublic');
            if (!empty($isPublic) && strcmp($isPublic, '1') == 0)
            {
                $article->setPublic(true);
            }
            else
            {
                $article->setPublic(false);
            }
            $article->setCategory($category);
            $article->setContent($content);
            $article->setTitle($contentTitle);
            $this->em->persist($article);
            try
            {
                if ($newArticle)
                {
                    $data['successmsg'] = 'Page ' . $pcode . ' has been created';
                }
                else
                {
                    $data['successmsg'] = 'Page ' . $pcode . ' has been updated';
                }
                $data['content_view'] = 'manage/spageedit_success_view';
                $this->em->flush();
                $this->load->view('page', $data);
                return;
            }
            catch (Exception $e)
            {
                show_error('Error', 500);
                return;
            }
        }
        if ($newArticle)
        {
            $data['newarticle'] = true;
            $data['titlecontent'] = '';
            $data['category'] = '';
            $data['public'] = false;
            $data['enabled'] = true;
        }

        $data['textcontent'] = '';
        if (!empty($article))
        {
            $data['textcontent'] = $article->getContent();
            $data['titlecontent'] = $article->getTitle();
            $data['category'] = $article->getCategory();
            $data['enabled'] = $article->getEnabled();
            $data['public'] = $article->getPublic();
        }
        $data['attrname'] = 'acontent';
        $data['jsAddittionalFiles'][] = '//cdn.ckeditor.com/4.4.4/full/ckeditor.js';
        $data['rawJs'][] = "

CKEDITOR.replace('acontent',{
 removeButtons: 'Flash,Smiley,Iframe',
 removePlugins: 'forms',

} );";

        $data['content_view'] = 'manage/spageedit_view';

        $this->load->view('page', $data);
    }

    private function submitValidate($pcode)
    {
        if (strcmp($pcode, 'new') == 0)
        {
            $this->form_validation->set_rules('acode', 'Article code', 'required|trim|xss_clean|min_length[1]|max_length[25]|no_white_spaces|spage_unique');
        }
        $this->form_validation->set_rules('acontent', 'contenti', 'required|trim|min_length[1]');
        $this->form_validation->set_rules('atitle', 'Title', 'trim|xss_clean|max_length[128]');
        $this->form_validation->set_rules('acategory', 'Category', 'trim|required|xss_clean|min_length[1]|max_length[25]');
        $this->form_validation->set_rules('aenabled', 'Enabled', 'trim|xss_clean');
        $this->form_validation->set_rules('apublic', 'Public', 'trim|xss_clean');
        return $this->form_validation->run();
    }

}
