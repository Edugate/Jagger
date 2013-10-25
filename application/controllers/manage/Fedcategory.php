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
        $this->load->library('form_validation');
        $this->load->library('zacl');
        $this->title = lang('title_fedcategory');
    }
   
    private function _submit_validate()
    {
        return TRUE;
    }
    public function edit($cat=null)
    {
         show_error('denied',403);
         if(!empty($cat) && !is_numeric($cat))
         {
             show_error('not found',404);
         }
         $c=$this->em->getRepository("models\FederationCategory")->findOneBy(array('id'=>$cat));
         if(empty($c))
         {
             show_error('not found',404);
         }
         if($this->_submit_validate())
         {
            echo '<pre>';
            print_r($this->input->post('fed'));
            echo '</pre>';
            $s =  $this->input->post('fed');
            if(!empty($s) && is_array($s))
            {
               unset($s[0]);
               $members = $c->getFederations();
               $federations = $this->em->getRepository("models\Federation")->findAll();
               foreach($federations as $ff)
               {
                  $g[$ff->getId()] = $ff;
               }
               $federations = $g;
               foreach($members as $m)
               {
                  $fedid = $m->getId();
                  if(!array_key_exists($fedid,$s))
                  {
                     $c->removeFederation($m);
                  }
                  unset($s[$fedid]);
               }
               foreach($s as $k=>$v)
               {
                    if(array_key_exists($k,$federations))
                    {
                       $members->add($federations[$k]);
                    }
               }
               $this->em->persist($c);
               $this->em->flush();
                
            }
         }
         $data['buttonname'] = $c->getName();
         $data['fullname'] = $c->getFullName();
         $data['description'] = $c->getDescription();
         $data['isdefault'] = $c->getIsDefault();
         $members = $c->getFederations();
         $federations = $this->em->getRepository("models\Federation")->findAll();
         $mult = array();
         foreach($federations as $f)
         {
            if($members->contains($f))
            {
               $mult[] = array('fedname'=>$f->getName(),'fedid'=>$f->getId(),'member'=>'1');
            }
            else
            {
               $mult[] = array('fedname'=>$f->getName(),'fedid'=>$f->getId(),'member'=>'0');

            }
         }
       
      //  echo '<pre>';
      //  print_r($mult);
      //  echo '</pre>';
        $data['multi'] = $mult;
        $data['content_view'] = 'manage/fedcatedit_view';
        $this->load->view('page',$data);


    }

    public function show($cat=NULL)
    {
         if(!empty($cat) && !is_numeric($cat))
         {
             show_error('not found',404);
         }
         /**
          * @todo ACL check
          */
         if(empty($cat))
         {
            $cats = $this->em->getRepository("models\FederationCategory")->findAll();
            $data['subtitle'] = '';
         }
         else
         {
            $cats = $this->em->getRepository("models\FederationCategory")->findBy(array('id'=>$cat));
         }
         $result = array();
         foreach($cats as $c)
         {
            $default = '';
            if($c->getIsDefault())
            {
                $default = makeLabel('active',lang('rr_default'),lang('rr_default'));
            }
            $result[] = array(
                    'name'=>$c->getName(). ' '.$default,
                    'full'=>$c->getFullName(),
                    'desc'=>$c->getDescription(),
              );
         }
        
         $data['result']= $result;
         $data['content_view'] = 'manage/fedcategory_view';
         $this->load->view('page',$data);

        

    }
}
