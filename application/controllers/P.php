<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * P Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class P extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->library('j_auth');
        $this->load->helper('url');
    }
    public function page($code)
    {
       $page = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode'=>$code));
       if(empty($page))
       {
          show_error('Page not found',404);
       }
       $is_enabled = $page->getEnabled();
       if(empty($is_enabled))
       {
          show_error('Page not found',404);
       }
       $is_public = $page->getPublic();
       if(!$is_public)
       {
          $loggedin = $this->j_auth->logged_in();
          if(!$loggedin)
          {        
              $data['content_view'] = 'auth/notloggedin';
              $this->load->view('page',$data);
              return;
          }
       }
       $this->title = $page->getTitle(); 
       $data['ptitle'] = $page->getTitle();
       $data['pcontent'] = $page->getContent();
       $data['content_view'] = 'staticpages_view';
       $this->load->view('page',$data);
    }


}
