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
 * Translator Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Translator extends MY_Controller
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
        $this->tmp_providers = new models\Providers;
        $this->load->helper('form');
        $this->load->library('table');
        $this->load->library('zacl');
        $this->load->library('form_validation');
    }
    
    private function _submit_validate()
    {
         $this->form_validation->set_rules('lang[]','lang','trim|xss_clean');
         return $this->form_validation->run();

    }
    public function tolanguage($l)
    {
       $this->lang->load('rr', 'english');
       $original = $this->lang->language;
       if($l === 'pt' or $l === 'pl' or $l === 'it' or $l === 'es')
       {
          $langto = $l;
       }
       else
       {
         show_error('wrong',404);
       }
       $username = $this->j_auth->current_user();
       $translator = $this->config->item('translator_access');
       if(empty($translator))
       {
           show_error('no perm 1',403);
       }
       if(empty($translator[$langto]))
       {
          show_error('no perm',403);
       }
       else
       {
          $config_username = $translator[$langto];
          if(strcasecmp($config_username, $username) != 0)
          {
              show_error('no perm',403);
          }
       }
       $this->lang->load('rr', $langto);
       $translatedto  = $this->lang->language;
       $merger = array();
       foreach($original as $key=>$value)
       {
         $merger[$key]['english'] = $value;
       }
       foreach($translatedto as $key=>$value)
       {
         $merger[$key]['to'] = $value;
       }
       $data['merger'] = $merger;
       
       $data['content_view'] = 'manage/translator_view';

       if($this->_submit_validate() === TRUE)
       { 
             $this->load->helper('file');
             $lang = $this->input->post('lang');
             $y = "<?php \n";
             foreach($lang as $k=>$v)
             {
                $y .= '$lang[\''.$k.'\'] ="'. htmlspecialchars($v)."\";\n";
             }
             $pathfile = APPPATH.'language/'.$langto.'/rr_lang.php';
             //echo $pathfile;
             if ( ! write_file($pathfile, $y))
             {
                 echo 'Unable to write the file';
             }
             else
             {
                  echo 'File written!';
             }
        }
       else
       {
           $this->load->view('page',$data);
       }
    }
}
