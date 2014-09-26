<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
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
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
        $this->tmp_providers = new models\Providers;
        $this->load->helper('form');
        $this->load->library(array('table', 'zacl', 'form_validation'));
        $this->lang->load('rr', 'english');
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('lang[]', 'lang', 'trim|xss_clean');
        return $this->form_validation->run();
    }

    private function checkPermission($langto)
    {
        $username = $this->j_auth->current_user();
        $translator = $this->config->item('translator_access');
        if(!empty($translator) && is_array($translator) && isset($translator[$langto]) && strcasecmp($translator[$langto],$username)==0)
        {
            return true;
        }
        else
        {
            log_message('warning',__METHOD__.' no acccess to translate to lang:'.$langto.'. Possible reason: no entry in config file: translator_access['.$langto.']['.$username.']');
            return false;
        }
        
    }

    public function tolanguage($l)
    {
        $original = $this->lang->language;
        $allowedlangs = array('pt', 'pl', 'it', 'es', 'lt', 'fr', 'de', 'ar', 'cs', 'fr-ca', 'sr', 'ga');
        if (in_array($l, $allowedlangs))
        {
            $langto = $l;
        }
        else
        {
            show_error('wrong', 404);
        }
        
        $isAccess = $this->checkPermission($langto);
        if(!$isAccess)
        {
            show_error('No access',403);
            return;
        }
        $this->lang->load('rr', $langto);
        $translatedTo = $this->lang->language;
        $merger = array();
        foreach ($original as $key => $value)
        {
            $merger[$key]['english'] = $value;
        }
        foreach ($translatedTo as $key => $value)
        {
            $merger[$key]['to'] = $value;
        }
        $data['merger'] = $merger;
        $data['content_view'] = 'manage/translator_view';
        if ($this->_submit_validate() === TRUE)
        {
            $this->load->helper('file');
            $lang = $this->input->post('lang');
            $y = "<?php \n";
            foreach ($lang as $k => $v)
            {
                $y .= '$lang[\'' . $k . '\'] ="' . htmlspecialchars($v) . "\";\n";
            }
            $pathfile = APPPATH . 'language/' . $langto . '/rr_lang.php';
            if (!write_file($pathfile, $y))
            {
                echo 'Unable to write the file';
            }
            else
            {
                echo 'File written!';
            }
            return;
        }

        $this->load->view('page', $data);
    }

}
