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
        $this->load->view(MY_Controller::$page, $data);
    }

    private function massmailValidate() {

        $this->load->library('form_validation');
        $this->form_validation->set_rules('subject', 'subject', 'trim|required|min_length[5]');
        $this->form_validation->set_rules('mailbody', 'mail body', 'trim|required|min_length[5]');

        return $this->form_validation->run();

    }

    public function massmail() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('Bad request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Session Lost');
        }
        if (!$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(401)->set_output('You need to have Administrator rights');
        }
        if ($this->massmailValidate() !== true) {

            return $this->output->set_status_header(401)->set_output(validation_errors());

        }
        $subject = $this->input->post('subject');
        $mailbody = $this->input->post('mailbody');
        /**
         * @var \models\User[] $users
         */
        $users = $this->em->getRepository('models\User')->findAll();
        $emailList = array();
        foreach ($users as $user) {
            $emailList[] = strtolower($user->getEmail());
        }
        $emailList = array_unique($emailList);
        foreach ($emailList as $mailto) {
            $m = new models\MailQueue();
            $m->setSubject($subject);
            $m->setBody($mailbody);
            $m->setDeliveryType('mail');
            $m->setRcptto($mailto);
            $this->em->persist($m);
        }
        try {
            $this->em->flush();

            return $this->output->set_output('Mail has be sent to the mailqueue');
        } catch (Exception $e) {
            log_message('error',__METHOD____.' '.$e);
            return $this->output->set_status_header(500)->set_output('Something went wrong... check logs');

        }

    }

    public function certificates($enttype = null, $checktype = null) {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('Bad request');
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

        /**
         * @var \models\Provider $provider
         */
        if ($enttype === 'localidp') {
            $providers = $tmpProviders->getLocalProvidersPartialWithCerts('IDP');
        } elseif ($enttype === 'localsp') {
            $providers = $tmpProviders->getLocalProvidersPartialWithCerts('SP');
        } elseif ($enttype === 'extsp') {
            $providers = $tmpProviders->getExtProvidersPartialWithCerts('SP');
        } elseif ($enttype === 'extidp') {
            $providers = $tmpProviders->getExtProvidersPartialWithCerts('IDP');
        } else {
            return $this->output->set_status_header(401)->set_output('The type of entities has not been specified');
        }
        $this->load->library('jalert');
        $result = array();
        if ($checktype === 'expired') {
            foreach ($providers as $provider) {
                $alert = $this->jalert->genCertsAlerts($provider);
                if (count($alert) > 0) {
                    $result[] = array(
                        'id'       => $provider->getId(),
                        'entityid' => html_escape($provider->getEntityId()),
                        'islocal'  => $provider->getLocal(),
                        'alerts'   => $alert
                    );
                }

            }
        } else if ($checktype === 'missingencryption') {

            foreach ($providers as $provider) {
                $certCounter = 0;
                $crts = $provider->getCertificates();
                if ($crts->count() == 0) {
                    $result[] = array(
                        'id'       => $provider->getId(),
                        'entityid' => html_escape($provider->getEntityId()),
                        'islocal'  => $provider->getLocal()
                    );
                    continue;
                }
                foreach ($crts as $crt) {
                    $use = $crt->getCertUse();
                    if ($use !== 'signing') {
                        $certCounter++;
                    }
                }

                if ($certCounter == 0) {
                    $result[] = array(
                        'id'       => $provider->getId(),
                        'entityid' => html_escape($provider->getEntityId()),
                        'islocal'  => $provider->getLocal()
                    );
                }
            }
        } else if ($checktype === 'missingsigning') {

            foreach ($providers as $provider) {
                $certCounter = 0;
                $crts = $provider->getCertificates();
                if ($crts->count() == 0) {
                    $result[] = array(
                        'id'       => $provider->getId(),
                        'entityid' => html_escape($provider->getEntityId()),
                        'islocal'  => $provider->getLocal()
                    );
                    continue;
                }
                foreach ($crts as $crt) {
                    $use = $crt->getCertUse();
                    if ($use !== 'encryption') {
                        $certCounter++;
                    }
                }

                if ($certCounter == 0) {
                    $result[] = array(
                        'id'       => $provider->getId(),
                        'entityid' => html_escape($provider->getEntityId()),
                        'islocal'  => $provider->getLocal()
                    );
                }
            }
        } else {
            return $this->output->set_status_header(401)->set_output('Incorrect request');
        }

        return $this->output->set_content_type('application/json')->set_output(json_encode(array('definitions' => array('baseurl' => base_url()), 'data' => $result)));

    }

    public function vormversion() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(401)->set_output('Bad request');
        }
        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Session Lost');
        }
        if (!$this->jauth->isAdministrator()) {
            return $this->output->set_status_header(401)->set_output('No permission');
        }

        $doctrineCurrentVer = Doctrine\ORM\Version::VERSION;
        $doctrineReqVer = '2.4.8';
        $doctrineCompared = Doctrine\ORM\Version::compare($doctrineReqVer);
        $phpMinVersion = version_compare(PHP_VERSION, '5.5.0', '>=');

        if ($doctrineCompared > 0 && $phpMinVersion) {
            echo '<div class="warning alert-box" data-alert>' . lang('rr_doctrinever') . ': ' . $doctrineCurrentVer . '</div>';
            echo '<div class="info alert-box" data-alert>' . lang('rr_mimumreqversion') . ': ' . $doctrineReqVer . ' - Please use <b>composer</b> tool to upgrade it to required version</div>';
        } else {
            echo '<div class="success alert-box" data-alert>' . lang('rr_doctrinever') . ': ' . $doctrineCurrentVer . ' : ' . lang('rr_meetsminimumreq') . '</div>';
        }
        if (!$phpMinVersion) {
            echo '<div class="alert alert-box" data-alert>Installed PHP VERSION: ' . PHP_VERSION . ' : ' . lang('rr_mimumreqversion') . ' 5.5.x.</div>';
        } else {
            echo '<div class="success alert-box" data-alert>Installed PHP VERSION: ' . PHP_VERSION . ' : ' . lang('rr_meetsminimumreq') . '</div>';
        }

        if (defined('CI_VERSION')) {
            $ciMinVersion = version_compare(CI_VERSION, '3.0.6', '>=');
            if (!$ciMinVersion) {
                echo '<div class="alert alert-box" data-alert>Installed CI VERSION: ' . CI_VERSION . ' : ' . lang('rr_mimumreqversion') . ' 3.0.6.</div>';
            } else {
                echo '<div class="success alert-box" data-alert>Installed CI VERSION: ' . CI_VERSION . ' : ' . lang('rr_meetsminimumreq') . '</div>';
            }
        } else {
            echo '<div class="alert alert-box" data-alert>Cannot recognize installed CI VERSION</div>';
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
