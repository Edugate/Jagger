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
 * Dashboard Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Dashboard extends MY_Controller {

    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        $this->load->library('j_auth');
        $loggedin = $this->j_auth->logged_in();
        $baseurl = base_url();
        if (!$loggedin)
        {
            $data['content_view'] = 'staticpages_view' ;
            $frontpage = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode'=>'front_page','enabled'=>true, 'ispublic'=>true));
            if (!empty($frontpage))
            {
                $data['pcontent'] = $frontpage->getContent();
                $data['ptitle'] = $frontpage->getTitle();
            }
            $this->load->view('page',$data);
            return;  
        }
        
        $this->load->library('zacl');
        
        $this->load->library('table');
        $q = $this->em->getRepository("models\Queue")->findAll();
        $this->inqueue = count($q);

        $acc = FALSE;
        $acc = $this->zacl->check_acl('dashboard', 'read', 'default', '');
        $data['inqueue'] = $this->inqueue;

        if (empty($acc))
        {
            $this->title = lang('title_accessdenied');
            $data['error'] = lang('rerror_nopermviewpage');
            $data['content_view'] = 'nopermission';
            $this->load->view('page', $data);
        }
        else
        {
            $this->title = lang('dashboard');
            $board = $this->session->userdata('board');
            if(empty($board))
            {
                 $cur_user = $this->j_auth->current_user();
                 $u=$this->em->getRepository("models\User")->findOneBy(array('username'=>$cur_user));

                 $pref = $u->getUserpref();
                 
                 if(isset($pref['board']))
                 {
                    $board = $pref['board'];
                 }
            }
	    
            $idps = array();
            $sps = array();
            $feds = array();
            if(!empty($board)&& is_array($board))
            {
                if(array_key_exists('idp',$board) && is_array($board['idp']))
                {
                    foreach($board['idp'] as $key=>$value)
                    {
                        $idps[$key] = '<a href="'.$baseurl.'providers/detail/show/'.$key.'">'.$value['name'].'</a><br /> <small>'.$value['entity'].'</small>';
                    }
                }
                if(array_key_exists('sp',$board) && is_array($board['sp'])) 
                {
                    foreach($board['sp'] as $key=>$value)
                    {
                        $sps[$key] = '<a href="'.$baseurl.'providers/detail/show/'.$key.'">'.$value['name'].'</a><br /><small>'.$value['entity'].'</small>';
                    }
                }
                if(array_key_exists('fed',$board) && is_array($board['fed'])) 
                {
                    foreach($board['fed'] as $key=>$value)
                    {
                        $feds[$key] = '<a href="'.$baseurl.'federations/manage/show/'.$value['url'].'">'.$value['name'].'</a>';
                    }
                }
            }

            $data['idps'] = $idps;
            $data['sps'] = $sps;
            $data['feds'] = $feds;
            $data['titlepage'] = lang('quick_access');
            $data['content_view'] = 'default_body';
            $this->load->view('page', $data);
        }
    }

}
