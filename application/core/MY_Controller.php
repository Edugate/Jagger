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
    protected static $current_language = 'en';
    public $title;
    protected $inqueue;
    public $globalerrors = array();
    public $globalnotices = array();
    public static $langselect = array();

    public function __construct()
    {

        parent::__construct();
        $this->em = $this->doctrine->em;
        $this->title = "";
        $this->lang->load('rr_lang', 'english');
        $langs = array('pl','pt','it','lt','es','cs','fr-ca','english');
        $cookie_lang = $this->input->cookie('rrlang', TRUE);
        $cookdefaultlang = $this->config->item('rr_lang');
        if(empty($cookdefaultlang))
        {
           $cookdefaultlang = 'english';
        }
        else
        {
           $this->lang->load('rr_lang', ''.$cookdefaultlang.'');
           self::$current_language = ''.$cookdefaultlang.'';

        }
        $defaultlang_cookie = array(
            'name' => 'rrlang',
            'value' => ''.$cookdefaultlang.'',
            'expire' => '2600000',
            'secure' => TRUE
        );

        if (!empty($cookie_lang) && in_array($cookie_lang, $langs))
        {
            $this->lang->load('rr_lang', $cookie_lang);
            if($cookie_lang === 'english')
            {
               self::$current_language = 'en';
            }
            else
            {
               self::$current_language = $cookie_lang;
            }
        }
        else
        {
            $this->input->set_cookie($defaultlang_cookie);
        }

        self::$langselect = languagesCodes($this->config->item('langselectlimit')); 

        if(file_exists(APPPATH.'helpers/custom_helper.php'))
        {
          $this->load->helper('custom');
          log_message('debug',__METHOD__.' custom_helper loaded');
        }
    }
    public static function getLang()
    {
        return self::$current_language;
    }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
