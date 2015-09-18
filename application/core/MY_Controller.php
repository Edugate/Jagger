<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * ResourceRegistry3
 *
 * @package   RR3
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2015, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

/**
 * @property CI_Config $config
 * @property CI_Email $email
 * @property CI_Encrypt $encrypt
 * @property CI_Form_validation $form_validation
 * @property CI_FTP $ftp
 * @property CI_Input $input
 * @property CI_Loader $load
 * @property CI_Parser $parser
 * @property CI_Session $session
 * @property CI_Table $table
 * @property CI_URI $uri
 * @property CI_Output $output
 * @property CI_Lang $lang
 * @property Zacl $zacl
 * @property J_cache $j_cache
 * @property J_ncache $j_ncache
 * @property J_queue $j_queue
 * @property Approval $approval
 * @property Tracker $tracker
 * @property Email_sender $email_sender
 * @property Curl $curl
 * @property Show_element $show_element
 * @property J_auth $j_auth
 * @property Arp_generator $arp_generator
 * @property Arpgen $arpgen
 * @property Providerdetails $providerdetails
 * @property Rrpreference $rrpreference
 * @property Jusermanage $jusermanage
 * @property Form_element $form_element
 * @property Doctrine $doctrine
 * @property CI_Cache $cache
 */
class MY_Controller extends CI_Controller
{

    public static $langselect = array();
    public static $menuactive;
    protected static $currLang = 'en';
    private static $langs;
    public $title;
    public $globalerrors = array();
    public $globalnotices = array();
    protected $em;
    protected $authenticated;
    protected $inqueue;

    public function __construct()
    {

        parent::__construct();
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header('X-Frame-Options: SAMEORIGIN');
        $this->output->set_header('Strict-Transport-Security: max-age=3153600');
        $this->em = $this->doctrine->em;
        $this->title = '';
        self::$langs = array(
            'en' => array('path' => 'english', 'val' => 'english'),
            'cs' => array('path' => 'cs', 'val' => 'čeština'),
            'es' => array('path' => 'es', 'val' => 'español'),
            'fr' => array('path' => 'fr', 'val' => 'français'),
            'ga' => array('path' => 'ga', 'val' => 'gaeilge'),
            'it' => array('path' => 'it', 'val' => 'italiano'),
            'lt' => array('path' => 'lt', 'val' => 'lietuvos'),
            'pl' => array('path' => 'pl', 'val' => 'polski'),
            'pt' => array('path' => 'pt', 'val' => 'português'),
            'sr' => array('path' => 'sr', 'val' => 'srpski')
        );
        /**
         * @var null|array $additionalLangs
         */
        $additionalLangs = $this->config->item('guilangs');
        if (is_array($additionalLangs)) {
            foreach ($additionalLangs as $k => $v) {
                self::$langs['' . $k . ''] = $v;
            }
        }


        $defaultLang = $this->config->item('rr_lang');
        if ($defaultLang === null) {
            $defaultLang = 'english';
        } else {
            self::$currLang = '' . $defaultLang . '';
        }


        $cookieLang = $this->input->cookie('rrlang', true);


        $defaultlangCookie = array(
            'name' => 'rrlang',
            'value' => '' . $defaultLang . '',
            'expire' => '2600000',
            'secure' => true
        );

        if ($cookieLang !== null && (($cookieLang === 'english') || array_key_exists($cookieLang, self::$langs))) {
            self::$currLang = $cookieLang;
        } else {
            $this->input->set_cookie($defaultlangCookie);
        }

        self::$langselect = languagesCodes($this->config->item('langselectlimit'));
        self::$menuactive = '';

        if (file_exists(APPPATH . 'helpers/custom_helper.php')) {
            $this->load->helper('custom');
            log_message('debug', __METHOD__ . ' custom_helper loaded');
        }

        $this->lang->load('rr_lang', 'english');
        if (self::$currLang === 'english' || self::$currLang === 'en') {
            self::$currLang = 'en';
        } else {
            $this->lang->load('rr_lang', self::$currLang);
        }
        spl_autoload_register('self::extPlugsAutoLoader');
  

    }

    /**
     * @return string
     */
    public static function getLang()
    {
        return self::$currLang;
    }

    /***
     * @return array
     */
    public static function guiLangs()
    {
        return self::$langs;
    }


   public static function extPlugsAutoLoader($className)
   {
      $path = APPPATH.'extplugins/';
      $className = ltrim($className, '\\');
      $fileName  = '';
      $namespace = '';
      if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = APPPATH.'extplugins/'.str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
      }
      $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
      require $fileName;
   }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
