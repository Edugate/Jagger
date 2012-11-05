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

}

?>
