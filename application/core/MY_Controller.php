<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
    public $mid;
    protected $em;
    protected $authenticated;
    protected $current_language;
    public $title;
    protected $inqueue;

    public function __construct()
    {
		ini_set("session.cookie_secure","1");
		ini_set("session.cookie_httponly","1");

                session_name('_RR3_SESS');
                session_start();
		parent::__construct();
		$this->em = $this->doctrine->em;
		$this->title = "";
		$this->mid = "";
                $cookie_lang = $this->input->cookie('rr3_langugage', TRUE); 
                $this->current_language = 'en';
                $this->lang->load('rr_lang', $this->current_language);
                /*
                if(!empty($cookie_lang))
                {
                     if($cookie_lang == 'irish')
                     {
                         $this->current_language = 'irish';
                     }
                }
                */
                //$this->lang->load('rr_lang', 'pt');
          





      

    }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
