<?php
if(!defined('BASEPATH'))
   exit('No direct script access allowed');

class Spage extends MY_Controller
{

    function __construct()
    {
       parent::__construct();
       $this->load->helper('form');
       $this->load->library('form_validation');
       
    }


   public function editArticle($pcode)
   {
      $this->load->library('j_auth');
      $loggedin = $this->j_auth->logged_in();
      if(!$loggedin)
      {
       redirect('auth/login', 'location');
      }
      $isAdmin = $this->j_auth->isAdministrator();
      if(!$isAdmin)
      {
         show_error('Permission denied',403);
         return;

      }

      $article = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode'=>$pcode));
      if(empty($article))
      {
         show_error('Not found',404);
         return;
      }

      if($this->submitValidate($pcode))
      {
             $content = $this->input->post('acontent');
             $contentTitle = $this->input->post('atitle');
             $isEnabled = $this->input->post('aenabled');
             $category = $this->input->post('acategory');
             if(!empty($isEnabled) && strcmp($isEnabled,'1')==0)
             {
                $article->setEnabled(true);
             }
             else
             {
                $article->setEnabled(false);
             }
             $isPublic = $this->input->post('apublic');
             if(!empty($isPublic) && strcmp($isPublic,'1')==0)
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
             try {
                $data['successmsg'] = 'Page '.$pcode.' has been updated';
                $data['content_view'] = 'manage/spageedit_success_view';
                $this->em->flush();
                $this->load->view('page',$data);
                
              }
             catch (Exception $e){
                show_error('Error',500);
                return;
             }

      }

      $data['textcontent'] = '';
      if(!empty($article))
      {
         $data['textcontent'] = $article->getContent();
         $data['titlecontent'] = $article->getTitle();
         $data['category'] = $article->getCategory();
         $data['enabled'] = $article->getEnabled();
         $data['public'] = $article->getPublic();
      }
      $data['attrname'] = 'acontent';
      $data['jsAddittionalFiles'][] = '//cdn.ckeditor.com/4.4.4/full/ckeditor.js'; 
      $data['rawJs'][] = 'CKEDITOR.replace(\'acontent\' );';

      $data['content_view'] = 'manage/spageedit_view';

      $this->load->view('page',$data);


   }


  private function submitValidate($pcode)
  {
       $this->form_validation->set_rules('acontent', 'contenti', 'required|trim|min_length[1]');
       $this->form_validation->set_rules('atitle', 'Title', 'trim|xss_clean|max_length[128]');
       $this->form_validation->set_rules('acategory', 'Category', 'trim|required|xss_clean|min_length[1]|max_length[25]');
       $this->form_validation->set_rules('aenabled', 'Enabled', 'trim|xss_clean');
       $this->form_validation->set_rules('apublic', 'Public', 'trim|xss_clean');
       return $this->form_validation->run();



  }

}
