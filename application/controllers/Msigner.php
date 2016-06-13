<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2013 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Msigner extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->tmp_providers = new models\Providers;
    }

    public function signer()
    {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }


        $digestmethod = $this->config->item('signdigest');
        if ($digestmethod === null) {
            log_message('debug', __METHOD__ . ' signdigest empty or not found in config file, using system default: SHA-1');
            $digestmethod = 'SHA-1';
        }

        $type = $this->uri->segment(3);
        $id = $this->uri->segment(4);
        if (empty($type) || empty($id) || !ctype_digit($id)) {
            return $this->output->set_status_header(404)->set_output(lang('error404'));
        }

        $this->load->library('zacl');


        if(!class_exists('GearmanClient')) {
            return $this->output->set_status_header(500)->set_output( 'Gearman is not supported by the system');
        }
        $gearmanenabled = $this->config->item('gearman');
        if ($gearmanenabled !== true) {
            return $this->output->set_status_header(404)->set_output( 'gearman is not enabled ' . lang('error404').'');
        }
        $client = new GearmanClient();
        $jobservers = array();
        $gearmanConf = $this->config->item('gearmanconf');
        foreach ($gearmanConf['jobserver'] as $v) {
            $jobservers[] = '' . $v['ip'] . ':' . $v['port'] . '';
        }
        try {
            $client->addServers('' . implode(',', $jobservers) . '');
        } catch (Exception $e) {
            log_message('error', __METHOD__.' GeamanClient couldnt add job-server');
            return $this->output->set_status_header(403)->set_output('Cant connect/add to job-server(s)');
        }


        $options = array();
        if ($type === 'federation') {
            $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => '' . $id . ''));
            if ($fed === null) {
                return $this->output->set_status_header(404)->set_output(''.lang('error_fednotfound').'');
            }
            $hasWriteAccess = $this->zacl->check_acl('f_' . $fed->getId(), 'write', 'federation', '');
            if (!$hasWriteAccess) {
                return $this->output->set_status_header(403)->set_output(''.lang('error403').'');
            }
            $digest1 = $fed->getDigest();
            if (empty($digest1)) {
                $digest1 = $digestmethod;
            }
            $digest2 = $fed->getDigestExport();
            if (empty($digest2)) {
                $digest2 = $digestmethod;
            }
            log_message('debug', __METHOD__ . ' final digestsign is set to: ' . $digest1 . 'and for export-federation if enabled set to: ' . $digest2);
            $encfedname = $fed->getSysname();
            $sourceurl = base_url('metadata/federation/' . $encfedname . '/metadata.xml');
            $options[] = array(
                'src' => '' . $sourceurl . '',
                'type' => 'federation',
                'encname' => '' . $encfedname . '',
                'digest' => '' . $digest1 . '');
            $localexport = $fed->getLocalExport();
            if (!empty($localexport)) {
                $options[] = array('src' => '' . base_url() . 'metadata/federationexport/' . $encfedname . '/metadata.xml', 'type' => 'federationexport', 'encname' => '' . $encfedname . '', 'digest' => '' . $digest2 . '');
            }

            foreach ($options as $opt) {
                $client->doBackground('metadatasigner', '' . json_encode($opt) . '');
            }
        } elseif ($type === 'provider') {
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => '' . $id . ''));
            if (empty($provider)) {
                return $this->output->set_status_header(404)->set_output(lang('rerror_provnotfound'));
            }
            $isLocal = $provider->getLocal();
            $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity');
            if ($isLocal !== true || !$hasWriteAccess) {
                return $this->output->set_status_header(403)->set_output(lang('error403'));
            }
            $digest1 = $provider->getDigest();
            if (empty($digest1)) {
                $digest1 = $digestmethod;
            }
            $encodedentity = base64url_encode($provider->getEntityId());
            $sourceurl = base_url('metadata/circle/' . $encodedentity . '/metadata.xml');
            $options[] = array(
                'src' => '' . $sourceurl . '',
                'type' => 'provider',
                'encname' => '' . $encodedentity . '',
                'digest' => '' . $digest1 . '');
            foreach ($options as $opt) {
                try {
                    $client->doBackground('metadatasigner', '' . json_encode($opt) . '');
                } catch (GearmanException $e) {
                    log_message('errror', __METHOD__ . ' ' . $e);
                    return $this->output->set_status_header(500)->set_output('Error occured during senfing task to Job serve');
                }
            }

        } else {
            return $this->output->set_status_header(404)->set_output('Unknown request');
        }
        return $this->output->set_status_header(200)->set_output(lang('taskssent'));

    }

}
