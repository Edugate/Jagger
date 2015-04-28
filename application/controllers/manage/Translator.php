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
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->tmp_providers = new models\Providers;
        $this->load->helper(array('form','file'));
        $this->load->library(array('table', 'zacl', 'form_validation'));
        $this->lang->load('rr', 'english');
    }

    private function _submit_validate($inputs)
    {
        foreach ($inputs as $val) {
            $this->form_validation->set_rules('lang[' . $val . ']', 'input "' . $val . '"', 'required|trim');
        }
        return $this->form_validation->run();
    }

    private function checkPermission($langto)
    {
        $username = $this->j_auth->current_user();
        $translator = $this->config->item('translator_access');
        if (!empty($translator) && is_array($translator) && isset($translator[$langto]) && strcasecmp($translator[$langto], $username) == 0) {
            return true;
        }
        log_message('warning', __METHOD__ . ' no acccess to translate to lang:' . $langto . '. Possible reason: no entry in config file: translator_access[' . $langto . '][' . $username . ']');
        return false;
    }

    public function tolanguage($l)
    {
        $this->title = lang('title_translator');
        $this->lang->is_loaded = array();
        $this->lang->language = array();
        $this->lang->load('rr_lang', 'english');
        $original = $this->lang->language;
        $data['titlepage'] = lang('title_translator') . '';
        $inputs = array_keys($original);
        $noinputs = (int)count($inputs) + 10;
        $systempost = (int)ini_get(max_input_vars);
        if ($noinputs > $systempost) {
            $data['syswarning'] = 'The number of input vars is (>' . $noinputs . ') higher that system allows (' . $systempost . '). Please increase max_input_vars in php settings';
        }
        $allowedlangs = MY_Controller::guiLangs();
        unset($allowedlangs['en']);
        if (array_key_exists($l, $allowedlangs)) {
            $langto = $l;
        } else {
            show_error('The language code is not allowed', 404);
        }
        $data['subtitlepage'] = 'en => ' . $langto;


        $isAccess = $this->checkPermission($langto);
        if (!$isAccess) {
            show_error('No access', 403);
            return;
        }
        $this->lang->load('rr', $langto);
        $translatedTo = $this->lang->language;
        $merger = array();
        foreach ($original as $key => $value) {
            $merger[$key]['english'] = $value;
        }
        foreach ($translatedTo as $key => $value) {
            $merger[$key]['to'] = $value;
        }
        $data['merger'] = $merger;
        $data['content_view'] = 'manage/translator_view';
        if ($this->_submit_validate($inputs) === TRUE) {
            $lang = $this->input->post('lang');
            $output = '<?php ' . PHP_EOL;
            $y = '';
            foreach ($lang as $k => $v) {
                $y .= '$lang[\'' . $k . '\'] ="' . html_escape($v) . "\";" . PHP_EOL;
            }
            $output .= $y;
            $pathfile = APPPATH . 'language/' . $langto . '/rr_lang.php';
            if (!write_file($pathfile, $output)) {
                echo 'Unable to write the file';
            } else {
                echo 'File written!';
            }
            return;
        }
        $data['error_message'] = validation_errors('<div>', '</div>');
        $this->load->view('page', $data);
    }

}
