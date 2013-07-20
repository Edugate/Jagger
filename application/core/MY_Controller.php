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
 * MY_Controller Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class MY_Controller extends CI_Controller {

    private $current_user;

    /**
     * Doctrine entity manager
     *
     * @var EntityManager
     */
    protected $em;
    protected $authenticated;
    public $current_language;
    public $title;
    protected $inqueue;

    public function __construct()
    {

        parent::__construct();
        $this->em = $this->doctrine->em;
        $this->title = "";
        $this->lang->load('rr_lang', 'english');
        $this->current_language = 'en';
        $langs = array('pl','pt','it','lt');
        $cookie_lang = $this->input->cookie('rrlang', TRUE);
        $defaultlang_cookie = array(
            'name' => 'rrlang',
            'value' => 'english',
            'expire' => '2600000',
            'secure' => TRUE
        );

        if (!empty($cookie_lang) && in_array($cookie_lang, $langs))
        {
            $this->lang->load('rr_lang', $cookie_lang);
            $this->current_language = $cookie_lang;
        }
        else
        {
            $this->input->set_cookie($defaultlang_cookie);
        }
    }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
