<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <januszu.ulanowski@heanet.ie>
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class P extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
    }

    public function page($code) {
        $page = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode' => $code));
        if ($page === null) {
            show_error('Page not found', 404);
        }
        $isEnabled = $page->getEnabled();
        $publicAccess = $page->getPublic();
        $loggedin = $this->jauth->isLoggedIn();
        if ($isEnabled !== true) {
            show_error('Page not found', 404);
        }
        if (!$publicAccess && !$loggedin) {
            $data['content_view'] = 'auth/notloggedin';
            $this->load->view('page', $data);
        }
        else {
            $this->title = $page->getTitle();
            $data['ptitle'] = $page->getTitle();
            $data['pcontent'] = jaggerTagsReplacer($page->getContent());
            $data['content_view'] = 'staticpages_view';
            $this->load->view('page', $data);
        }
    }

    public function documentfile($docfile) {
        $datastorage = $this->config->item('datastorage_path');
        if (trim($datastorage) === '') {
            show_error('Not found ', 404);
        }
        $documentfilesdit = $datastorage . 'docs/';
        $isValidName = preg_match('/^[a-zA-Z0-9_.\-]+$/i', $docfile);
        if (!$isValidName) {
            show_error('Not found ', 404);
        }
        $this->load->helper('download');
        $data = file_get_contents($documentfilesdit . $docfile);
        force_download($docfile, $data);

    }


}
