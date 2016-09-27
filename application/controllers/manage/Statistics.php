<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2013, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

class Statistics extends MY_Controller
{

    public function __construct() {
        parent::__construct();
    }

    public function latest($id = null) {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('medthod denied');
        }
        if (!ctype_digit($id)) {
            return $this->output->set_status_header(404)->set_output('nod found');
        }
        $datastorage = $this->config->item('datastorage_path');
        if ($datastorage === null) {
            return $this->output->set_status_header(500)->set_output('data storage not defined');
        }
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            return $this->output->set_status_header(403)->set_output('access denied');
        }
        $this->load->library('zacl');

        $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $id));
        if ($def === null) {
            return $this->output->set_status_header(404)->set_output('nod found');
        }
        $provider = $def->getProvider();
        if (empty($provider)) {
            return $this->output->set_status_header(404)->set_output('nod found');
        }
        $hasAccess = $this->zacl->check_acl('' . $provider->getId() . '', 'write', 'entity', '');
        if (!$hasAccess) {
            return $this->output->set_status_header(403)->set_output('access denied');
        }
        $s = null;
        $stats = $this->em->getRepository("models\ProviderStatsCollection")->findBy(array('provider' => $provider->getId(), 'statdefinition' => $def->getId()), array('id' => 'DESC'));
        if (count($stats) > 0) {
            $s = $stats['0'];
        } else {
            return $this->output->set_status_header(404)->set_output('nod found');
        }

        $r = array();
        $r[] = array('url' => base_url() . 'manage/statistics/show/' . $s->getId() . '/' . md5(uniqid(rand(), true)) . '', 'title' => $def->getTitle(), 'subtitle' => 'created: ' . $s->getCreatedAt()->format('Y-m-d H:i:s') . '');

        return $this->output->set_content_type('application/json')->set_output(json_encode($r));
    }


    public function show($id = null) {

        if (!ctype_digit($id)) {
            show_error('Not found');
        }
        $datastorage = $this->config->item('datastorage_path');
        if (empty($datastorage)) {
            log_message('error', 'Missing datastorage_path in config');
            show_error('not found', 404);
        }
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            show_error('Access denied', 403);
        }
        $this->load->library('zacl');

        $s = $this->em->getRepository('models\ProviderStatsCollection')->findOneBy(array('id' => $id));
        if ($s === null) {
            log_message('debug', 'requested stat not found');
            show_error('Not found', 404);
        }
        $p = $s->getProvider();
        if (empty($p)) {
            log_message('error', 'Found orphaned statists with id:' . $s->getId());
            show_error('Not found', 404);
        }
        $hasAccess = $this->zacl->check_acl('' . $p->getId() . '', 'write', 'entity', '');
        if (!$hasAccess) {
            show_error(lang('rr_noperm'), 403);
        }

        $statstorage = $datastorage . 'stats/';
        if (!is_dir($statstorage)) {
            log_message('debug', 'directory ' . $statstorage . 'not exist');
            show_error('dddd', 404);
        }

        $filename = $s->getFilename();
        $fullpath = $statstorage . $filename;
        if (!is_file($fullpath)) {
            log_message('error', 'Stat record id:' . $s->getId() . ' : file doesnt exist in datastorage');
            show_error('not found', 404);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileContents = file_get_contents($fullpath);
        $mimeType = $finfo->buffer($fileContents);
        $this->output->set_content_type($mimeType)->set_output($fileContents);
    }

}
