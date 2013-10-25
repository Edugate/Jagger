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
