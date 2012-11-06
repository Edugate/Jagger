<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Ajax extends MY_Controller {

    public function __construct()
    {

        parent::__construct();
    }

    public function changelanguage($language)
    {

        if ($this->input->is_ajax_request())
        {
            log_message('debug', 'ajax');
            $language = substr($language, 0,2);
            if ($language == 'pl')
            {
                $cookie_value = 'pl';
            }
            elseif ($language == 'pt')
            {
                $cookie_value = 'pt';
            }
            else
            {
                $cookie_value = 'english';
            }
            $lang_cookie = array(
                'name' => 'rrlang',
                'value' => $cookie_value,
                'expire' => '2600000',
                'secure' => TRUE
            );
            $this->input->set_cookie($lang_cookie);
            return true;
        }
        
        else {
            log_message('debug', 'noajax');
        }
    }
    public function bookentity($id)
    {
        if ($this->input->is_ajax_request())
        {
           log_message('debug','bookentity: got ajax request');
           $this->load->library('j_auth');
           $loggedin = $this->j_auth->logged_in();
           if($loggedin)
           {
              log_message('debug','bookentity: loggedin');
              $username = $this->j_auth->current_user();
              $u = $this->em->getRepository("models\User")->findOneBy(array('username'=>$username));
              $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$id));
              if(!empty($u) && !empty($ent))
              {
                 $enttype = $ent->getType();
                 $entname = $ent->getName();
                 $entid = $ent->getId();
                 $u->addEntityToBookmark($entid,$entname,$enttype);
                 $this->em->persist($u);
                 $userprefs = $u->getUserpref();
                 $this->session->set_userdata(array('board'=> $userprefs['board']));
                 $this->em->flush();
              } 
           }
           else
           {
              log_message('debug','bookentity: not  loggedin');
           }
        }
    }

    public function delbookentity($id)
    {
       if ($this->input->is_ajax_request())
       {
           $this->load->library('j_auth');
           $loggedin = $this->j_auth->logged_in();
           if($loggedin && is_numeric($id))
           {
                $username = $this->j_auth->current_user();
                $u = $this->em->getRepository("models\User")->findOneBy(array('username'=>$username));
                if(!empty($u))
                {
                    $u->delEntityFromBookmark($id);
                    $this->em->persist($u);
                    $userprefs = $u->getUserpref();
                    $this->session->set_userdata(array('board'=> $userprefs['board']));
                    $this->em->flush();
                }
           }
       }
    }

}

?>
