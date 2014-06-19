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
 * Coc Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Coc extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->library('zacl');
    }
      
    public function show($id=null)
    {
       $this->title=lang('title_entcats');
       if(isset($id))
       {
           show_error('Argument passed to page  not allowed',403);
           return;
     
       }
       $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
       $obj_list = $this->em->getRepository("models\Coc")->findBy( array('type'=>'entcat'));
       $data['rows'] = array(); 
       if(is_array($obj_list) && count($obj_list)>0)
       {
          foreach($obj_list as $c)
          {
              if($has_write_access)
              {
                 $l = '<a href="'.base_url().'manage/coc/edit/'.$c->getId().'" class="button tiny">'.lang('rr_edit').'</a>';
              }
              else
              {
                 $l = '';
              }
              $en = $c->getAvailable();
              if($en)
              {
                  $lbl = '<span class="lbl lbl-active">'.lang('rr_enabled').'</span>';
              }
              else
              {
                  $lbl = '<span class="lbl lbl-disabled">'.lang('rr_disabled').'</span>';
              }
              $data['rows'][] = array($c->getName(),$lbl ,anchor($c->getUrl(),$c->getUrl(),array('target' => '_blank', 'class' => 'new_window')), $c->getDescription(),$l);
         
          } 
       }
       else
       {
          $data['error_message'] = lang('rr_noentcatsregistered');
       }
       $data['showaddbutton'] = FALSE;
       if($has_write_access)
       {
//         $data['rows'][] = array(anchor(base_url().'manage/coc/add','<button type="submit" class="addbutton addicon">'.lang('addentcat_btn').'</button>'), '','','');
         $data['showaddbutton'] = TRUE;
       }

       $data['titlepage'] = lang('ent_list_title');

       $data['content_view'] = 'manage/coc_show_view';
       $this->load->view('page',$data);
          
    }
    private function _add_submit_validate()
    {
        $this->form_validation->set_rules('name',lang('entcat_shortname'),'required|trim|cocname_unique');
        $this->form_validation->set_rules('url',lang('entcat_url'),'required|trim|valid_url|cocurl_unique');
        $this->form_validation->set_rules('description',lang('entcat_description'),'xss_clean');
        $this->form_validation->set_rules('cenabled','Enabled','xss_clean');
        return $this->form_validation->run();
    }
    private function _edit_submit_validate($id)
    {
        $this->form_validation->set_rules('name',lang('entcat_shortname'),'required|trim|cocname_unique_update['.$id.']');
        $this->form_validation->set_rules('url',lang('entcat_url'),'required|trim|valid_url|cocurl_unique_update['.$id.']');
        $this->form_validation->set_rules('description',lang('entcat_description'),'xss_clean');
        $this->form_validation->set_rules('cenabled','Enabled','xss_clean');
        return $this->form_validation->run();
    }
    public function add()
    {
        $this->title = lang('title_addentcat');
        $data['titlepage'] =  lang('title_addentcat');
        $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
        if(!$has_write_access)
        {
            show_error('No access',401);
            return;
        }

        if($this->_add_submit_validate() === TRUE)
        {
           $name = $this->input->post('name');
           $url = $this->input->post('url');
           $cenabled = $this->input->post('cenabled');
           $description = $this->input->post('description');
          
           $ncoc = new models\Coc;
           $ncoc->setName($name);
           $ncoc->setUrl($url);
           $ncoc->setType('entcat');
           if(!empty($description))
           {
               $ncoc->setDescription($description);
           }
           if(!empty($cenabled) && $cenabled == 'accept')
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
            $this->load->library('form_element');
            $f .= $this->form_element->generateAddCoc();
            $f .= '<div class="buttons small-12 medium-10 large-10 columns end text-right">';
            $f .= '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">'.lang('rr_reset').'</button> ';
            $f .= '<button type="submit" name="modify" value="submit" class="savebutton saveicon">'.lang('rr_save').'</button></div>';

            $f .= form_close();
            $data['form'] = $f;
        }
            $data['content_view'] = 'manage/coc_add_view';
            $this->load->view('page',$data);
    }
    public function edit($id)
    {
       $this->title = lang('title_entcatedit');
      
       if(empty($id) OR !is_numeric($id))
       {
          show_error('Not found',404);
          return;
       }
       $coc = $this->em->getRepository("models\Coc")->findOneBy(array('id'=>$id,'type'=>'entcat'));
       if(empty($coc))
       {
          show_error('Not found',404);
          return;
       }
       $has_write_access = $this->zacl->check_acl('coc', 'write', 'default', '');
       if(!$has_write_access)
       {
           show_error('No access',401);
           return;
       }
       $data['titlepage'] = lang('title_entcat').': '.htmlentities($coc->getName());
       $data['subtitlepage'] = lang('title_entcatedit');

       if($this->_edit_submit_validate($id) === TRUE)
       {
           $enable = $this->input->post('cenabled');
           if(!empty($enable) && $enable == 'accept')
           {
               $coc->setAvailable(TRUE);
           }
           else
           {
               $coc->setAvailable(FALSE);
           }
           $coc->setName($this->input->post('name'));
           $coc->setUrl($this->input->post('url'));
           $coc->setDescription($this->input->post('description'));
           $this->em->persist($coc);
           $this->em->flush();
           $data['success_message'] = lang('updated');
       }
       $data['coc_name'] = $coc->getName();
       $this->load->library('form_element');
       $f = form_open();
       $f .= $this->form_element->generateEditCoc($coc);
       $f .= '<div class="buttons large-10 medium-10 small-12 text-right columns end">';
       $f .= '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">'.lang('rr_reset').'</button> ';
       $f .= '<button type="submit" name="modify" value="submit" class="savebutton saveicon">'.lang('rr_save').'</button></div>';
       $f .= form_close();
       $data['form'] = $f;
       $data['content_view'] = 'manage/coc_edit_view';
       $this->load->view('page',$data);
       
    }
    
}
