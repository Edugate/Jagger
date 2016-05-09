<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet <middleware-noc@heanet.ie>
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Dashboard extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->title = lang('dashboard');
    }


    /**
     * @return bool
     */
    private function isMigrationUptodate() {
        $this->load->config('migration');
        $targetVersion = config_item('migration_version');
        /**
         * @var models\Migration[] $migrations
         */
        $migrations = $this->em->getRepository("models\Migration")->findAll();
        $currentVersion = 0;
        foreach ($migrations as $v) {
            $currentVersion = $v->getVersion();
        }
        if ($currentVersion < $targetVersion) {
            return false;
        }

        return true;

    }


    private function showFrontPage() {
        /**
         * @var models\Staticpage $frontpage
         */
        try {
            $frontpage = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode' => 'front_page', 'enabled' => true, 'ispublic' => true));
        } catch (Exception $e) {

            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }
        if ($frontpage !== null) {
            $data = array(
                'pcontent' => jaggerTagsReplacer($frontpage->getContent()),
                'ptitle'   => $frontpage->getTitle(),
            );
            $this->title = $frontpage->getTitle();
        }
        $data['content_view'] = 'staticpages_view';

        return $this->load->view(MY_Controller::$page, $data);
    }

    public function index() {
        if (!$this->jauth->isLoggedIn()) {
            /**
             * @var $frontpage models\Staticpage
             */
            return $this->showFrontPage();
        }
        try {
            $this->load->library('zacl');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal server error', 500);
        }
        /**
         * @var models\Queue[] $queues
         */
        $queues = $this->em->getRepository("models\Queue")->findAll();
        $this->inqueue = count($queues);
        $acc = $this->zacl->check_acl('dashboard', 'read', 'default', '');

        $data['inqueue'] = $this->inqueue;
        if ($acc !== true) {
            $this->title = lang('title_accessdenied');
            $data['error'] = lang('rerror_nopermviewpage');
            $data['content_view'] = 'nopermission';

            return $this->load->view(MY_Controller::$page, $data);
        }
        if ($this->jauth->isAdministrator()) {
            $isnotified = $this->session->userdata('alertnotified');
            if ($isnotified !== true) {
                $data['alertdashboard'] = array();
                $isSetupOn = $this->config->item('rr_setup_allowed');
                $isMigrationUptodate = $this->isMigrationUptodate();
                if ($isMigrationUptodate !== true) {
                    $data['alertdashboard'][] = 'Administration/System (migration) steps are required to make system uptodate';
                }
                if ($isSetupOn === true) {
                    $data['alertdashboard'][] = 'Jagger setup is still enabled. Please disable it in config file';
                }
                $this->session->set_userdata(array('alertnotified' => true));
            }
        }


        $bookmarList = $this->genBookmarkList();
        $data2 = array(
            'idp'          => $bookmarList['idp'],
            'sp'           => $bookmarList['sp'],
            'feds'         => $bookmarList['feds'],
            'titlepage'    => lang('quick_access'),
            'content_view' => 'default_body'
        );
        $data = array_merge($data, $data2);

        $this->load->view(MY_Controller::$page, $data);

    }

    private function genBookmarkList() {
        $board = $this->session->userdata('board');
        if (!is_array($board)) {
            $curUser = $this->jauth->getLoggedinUsername();
            /**
             * @var models\User $userObj
             */
            $userObj = $this->em->getRepository("models\User")->findOneBy(array('username' => $curUser));
            $board = $userObj->getBookmarks();
        }
        $result = array('idp' => array(), 'sp' => array(), 'feds' => array());
        $feds = array();
        $baseurl = base_url();
        foreach (array('idp', 'sp') as $part) {
            if (array_key_exists($part, $board) && is_array($board[$part])) {
                foreach ($board['' . $part . ''] as $key => $value) {
                    $result[$part][$key] = '<a href="' . $baseurl . 'providers/detail/show/' . $key . '">' . $value['name'] . '</a><br /> <small>' . $value['entity'] . '</small>';
                }
            }
        }

        if (array_key_exists('fed', $board) && is_array($board['fed'])) {
            foreach ($board['fed'] as $key => $value) {
                $feds[$key] = '<a href="' . $baseurl . 'federations/manage/show/' . $value['url'] . '">' . $value['name'] . '</a>';
            }
        }

        $result['feds'] = $feds;

        return $result;
    }

}
