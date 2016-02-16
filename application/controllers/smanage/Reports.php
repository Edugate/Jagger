<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\Version;

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Reports extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        MY_Controller::$menuactive = 'admins';
    }

    public function index() {
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        if (!$this->jauth->isAdministrator()) {
            show_error('no perm', 403);
        }

        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        $this->title = lang('title_sysreports');
        $data['titlepage'] = lang('title_sysreports');
        $data['content_view'] = 'smanage/index_view';
        $data['breadcrumbs'] = array(
            array('url' => '#', 'name' => lang('rr_administration'), 'type' => 'unavailable'),
            array('url' => '#', 'name' => lang('title_sysreports'), 'type' => 'current'),
        );
        $this->load->view('page', $data);
    }

    public function expiredcerts() {
        if (!$this->input->is_ajax_request()) {
         //   return $this->output->set_status_header(401)->set_output('Bad request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Session Lost');
        }
        if (!$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(401)->set_output('You need to have Administrator rights');
        }
        /**
         * @var \models\Provider[] $providers
         */
        $tmpProviders = new models\Providers();
        $providers =  $tmpProviders->getProvidersPartialWithCerts('IDP');
        $this->load->library('jalert');
        $result = array();
        foreach($providers as $provider){
            $alert  = $this->jalert->genCertsAlerts($provider) ;
            if(count($alert) > 0) {
                $result[] = array(
                    'entityid' => html_escape($provider->getEntityId()),
                    'islocal' => $provider->getLocal(),
                    'alerts' => $alert
                );
            }

        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function vormversion() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('Bad request');
        }
        if (!$this->jauth->isLoggedIn()) {

            return $this->output->set_status_header(401)->set_output('Session Lost');
        }
        if (!$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(401)->set_output('No perm');
        }

        $currentVersion = Doctrine\ORM\Version::VERSION;
        $minRequiredVersion = '2.4.8';
        $compared = Doctrine\ORM\Version::compare($minRequiredVersion);
        $minphpversion = version_compare(PHP_VERSION, '5.5.0', '>=');
        if ($compared > 0 && $minphpversion) {
            echo '<div class="warning alert-box" data-alert>' . lang('rr_doctrinever') . ': ' . $currentVersion . '</div>';
            echo '<div class="info alert-box" data-alert>' . lang('rr_mimumreqversion') . ': ' . $minRequiredVersion . ' - Please use <b>composer</b> tool to upgrade it to required version</div>';
        } else {
            echo '<div class="success alert-box" data-alert>' . lang('rr_doctrinever') . ': ' . $currentVersion . ' : ' . lang('rr_meetsminimumreq') . '</div>';
        }
        if (!$minphpversion) {
            echo '<div class="alert alert-box" data-alert>Installed PHP VERSION: ' . PHP_VERSION . ' : ' . lang('rr_mimumreqversion') . ' 5.5.x.</div>';
        } else {
            echo '<div class="success alert-box" data-alert>Installed PHP VERSION: ' . PHP_VERSION . ' : ' . lang('rr_meetsminimumreq') . '</div>';
        }

    }


    public function vschema() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('Bad request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Lost session');
        }
        if (!$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $proxyDir = null; //to genearate to default proxy dir
        $proxyFactory = $this->em->getProxyFactory();
        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();
        $proxyFactory->generateProxyClasses($metadatas, $proxyDir);
        $validator = new SchemaValidator($this->em);
        $errors = $validator->validateMapping();
        if (count($errors) > 0) {
            $result = '<div class="waring alert-box" data-alert><ul>' . recurseTree($errors) . '</ul></div>';

        } else {
            $result = '<div class="success alert-box" data-alert>The mapping files are correct</div>';
        }

        return $this->output->set_status_header(200)->set_output($result);


    }


    public function vschemadb() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('Bad request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Unauthorized request');
        }
        if (!$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(403)->set_output('Unauthorized request');
        }

        $validator = new SchemaValidator($this->em);
        $result = $validator->schemaInSyncWithMetadata();
        if ($result) {
            $output = '<div class="success alert-box" data-alert>' . lang('rr_dbinsync') . '</div>';
        } else {
            $output = '<div class="warning alert-box" data-alert>' . lang('rerror_dbinsync') . '</div>';
        }
        return $this->output->set_status_header(200)->set_output($output);
    }


    public function vmigrate() {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn() || !$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(403)->set_output('Unauthorized request');
        }

        $validator = new SchemaValidator($this->em);
        $errors = $validator->validateMapping();
        $errors2 = $validator->schemaInSyncWithMetadata();
        if (count($errors) > 0 || !$errors2) {
            echo '<h5 class="error">' . lang('rerror_migrate1') . '</h5>';
            if (count($errors) > 0) {
                echo '<div class="warning alert-box" data-alert><ul>' . recurseTree($errors) . '</ul></div>';
            }
            if (!$errors2) {
                echo '<div class="warning alert-box" data-alert>' . lang('rerror_dbinsync') . '</div>';
            }
        } else {
            $i = $this->em->getRepository("models\Migration")->findAll();
            if (count($i) == 0) {
                $y = new models\Migration;
                $y->setVersion(0);
                $this->em->persist($y);
                $this->em->flush();
            }

            $this->load->library('migration');
            $t = $this->migration->current();
            if ($t === false) {
                echo $this->migration->error_string();
            } else {
                echo '<div class="success alert-box" data-alert>' . lang('rr_sysuptodate') . ' : ' . $t . '</div>';
            }
        }

    }


}
