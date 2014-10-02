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
    public static $menuactive;
    private static $langs;

    public function __construct()
    {

        parent::__construct();
        $this->em = $this->doctrine->em;
        $this->title = "";
        $this->lang->load('rr_lang', 'english');
        
        self::$langs = array(
            'en' => array('path' => 'english', 'val' => 'english'),
            'cs' => array('path' => 'cs', 'val' => 'čeština'),
            'es' => array('path' => 'es', 'val' => 'español'),
            'fr-ca' => array('path' => 'fr-ca', 'val' => 'français'),
            'ga' => array('path' => 'ga', 'val' => 'gaeilge'),
            'it' => array('path' => 'it', 'val' => 'italiano'),
            'lt' => array('path' => 'lt', 'val' => 'lietuvos'),
            'pl' => array('path' => 'pl', 'val' => 'polski'),
            'pt' => array('path' => 'pt', 'val' => 'português'),
            'sr' => array('path' => 'sr', 'val' => 'srpski'),
        );
        $cookie_lang = $this->input->cookie('rrlang', TRUE);
        $cookdefaultlang = $this->config->item('rr_lang');
        $addlangs = $this->config->item('guilangs');
        if(!empty($addlangs) && is_array($addlangs))
        {
            foreach($addlangs as $k=>$v)
            {
                self::$langs[''.$k.''] = $v;
            }
        }
        if (empty($cookdefaultlang))
        {
            $cookdefaultlang = 'english';
        }
        else
        {
            $this->lang->load('rr_lang', '' . $cookdefaultlang . '');
            self::$current_language = '' . $cookdefaultlang . '';
        }
        $defaultlang_cookie = array(
            'name' => 'rrlang',
            'value' => '' . $cookdefaultlang . '',
            'expire' => '2600000',
            'secure' => TRUE
        );

        if (!empty($cookie_lang) && (strcmp($cookie_lang,'english') ==0 ||  array_key_exists($cookie_lang, self::$langs)))
        {
            $this->lang->load('rr_lang', $cookie_lang);
            if ($cookie_lang === 'english')
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
        self::$menuactive = '';

        if (file_exists(APPPATH . 'helpers/custom_helper.php'))
        {
            $this->load->helper('custom');
            log_message('debug', __METHOD__ . ' custom_helper loaded');
        }
    }

    public static function getLang()
    {
        return self::$current_language;
    }

    public static function guiLangs()
    {
        return self::$langs;
    }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
