<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @author    Middleware Team HEAnet
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @property Curl $curl
 */

/**
 * @todo add permission to check for public or private perms
 */
class Fvalidator extends MY_Controller
{

    function __construct() {
        parent::__construct();
    }


    public function detailjson($fid = null, $fvid = null) {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(401)->set_output('Acces Denied');
        }
        if (!empty($fid) && !empty($fvid)) {
            /**
             * @var $fvalidator models\FederationValidator
             */

            $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id' => $fvid, 'federation' => $fid, 'isEnabled' => TRUE));

            if (empty($fvalidator)) {
                return $this->output->set_status_header(404)->set_output('not found');

            } else {
                $result = array('id' => $fvalidator->getId(), 'fedid' => $fid, 'name' => $fvalidator->getName(), 'desc' => $fvalidator->getDescription());
                return $this->output->set_status_header(200)->set_content_type('application/json')->set_output(json_encode($result));
            }


        }
        $fedid = $this->input->post('fedid');
        if (!ctype_digit($fedid)) {
            return $this->output->set_status_header(404)->set_output('not found');
        }

        /**
         * @var $fed models\Federation
         */

        $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if ($fed === null) {
            return $this->output->set_status_header(404)->set_output('Federation not found');
        }
        $validators = $fed->getValidators();
        $validator = array();

        foreach ($validators as $v) {
            $venabled = $v->getEnabled();
            if ($venabled) {
                $validator['' . $v->getId() . ''] = array('id' => $v->getId(), 'fedid' => $fedid, 'name' => html_escape($v->getName()), 'desc' => html_escape($v->getDescription()));
            }
        }
        if (count($validator) > 0) {
            return $this->output->set_status_header(200)->set_content_type('application/json')->set_output(json_encode($validator));
        }
        return $this->output->set_status_header(404)->set_output('not found');


    }

    public function validate() {
        if (!($this->input->is_ajax_request() && $this->jauth->isLoggedIn())) {
            return $this->output->set_status_header(401)->set_output('Access Denied');
        }
        $inputArgs = array(
            'providerid' => trim($this->input->post('provid')),
            'federationid' => trim($this->input->post('fedid')),
            'fvalidatorid' => trim($this->input->post('fvid')),
            'queuetoken' => trim($this->input->post('qtoken')),
            'tmpprovid' => trim($this->input->post('tmpprovid'))
        );
        if (empty($inputArgs['federationid']) || !ctype_digit($inputArgs['federationid']) || empty($inputArgs['fvalidatorid']) || !ctype_digit($inputArgs['fvalidatorid'])) {
            return $this->output->set_status_header(403)->set_output('incorrect/missing paramters  passed');
        }

        /**
         * @var $federation models\Federation
         * @var $fvalidator models\FederationValidator
         */

        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $inputArgs['federationid']));
        $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id' => $inputArgs['fvalidatorid']));

        $reqAttrPassed = FALSE;
        if (!empty($inputArgs['queuetoken']) && ctype_alnum($inputArgs['queuetoken'])) {
            $providerMetadataUrl = base_url() . 'metadata/queue/' . $inputArgs['queuetoken'];
            $reqAttrPassed = TRUE;
        } elseif (!empty($inputArgs['providerid']) && ctype_digit($inputArgs['providerid'])) {
            /**
             * @var $provider models\Provider
             */
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $inputArgs['providerid']));
            if ($provider !== null) {
                $providerMetadataUrl = base_url() . 'metadata/service/' . base64url_encode($provider->getEntityId()) . '/metadata.xml';
                $reqAttrPassed = TRUE;
            }
        }
        if (!$reqAttrPassed || empty($fvalidator) || empty($federation)) {
            return $this->output->set_status_header(404)->set_output('One of following not found: fedValidator, federation, requesttype');
        }
        $validators = $federation->getValidators();

        if (!$validators->contains($fvalidator)) {
            return $this->output->set_status_header(404)->set_output('federation does not match validator');

        }
        $method = $fvalidator->getMethod();
        $remoteUrl = $fvalidator->getUrl();
        $entityParam = $fvalidator->getEntityParam();
        $optArgs = $fvalidator->getOptargs();
        if (empty($providerMetadataUrl)) {
            return $this->output->set_status_header(404)->set_output('missing params');
        }
        if (strcmp($method, 'GET') == 0) {
            $separator = $fvalidator->getSeparator();
            $optArgsStr = '';
            foreach ($optArgs as $k => $v) {
                if ($v === null) {
                    $optArgsStr .= $k . $separator;
                } else {
                    $optArgsStr .= $k . '=' . $v . '' . $separator;
                }
            }
            $optArgsStr .= $entityParam . '=' . urlencode($providerMetadataUrl);
            $remoteUrl = $remoteUrl . $optArgsStr;
            $this->curl->create('' . $remoteUrl . '');
        } else {
            $params = $optArgs;
            $params['' . $entityParam . ''] = $providerMetadataUrl;
            $this->curl->create('' . $remoteUrl . '');
            $this->curl->post($params);
        }

        $addoptions = array();
        $this->curl->options($addoptions);
        $data = $this->curl->execute();
        if (empty($data)) {
            return $this->output->set_status_header(404)->set_output('No data received from external validator');
        }
        log_message('debug', __METHOD__ . ' data received: ' . $data);
        $expectedDocumentType = $fvalidator->getDocutmentType();
        if (strcmp($expectedDocumentType, 'xml') != 0) {
            return $this->output->set_status_header(403)->set_output('Other than xml not supported yet');
        } else {
            libxml_use_internal_errors(true);
            $sxe = simplexml_load_string($data);
            if (!$sxe) {
                return $this->output->set_status_header(403)->set_output('Received invalid xml document');
            }
            $docxml = new \DomDocument();
            $docxml->loadXML($data);
            $returncodeElements = $fvalidator->getReturnCodeElement();
            if (count($returncodeElements) == 0) {
                return $this->output->set_status_header(500)->set_output('Returncode not defined');
            }
            foreach ($returncodeElements as $v) {
                $codeDoms = $docxml->getElementsByTagName($v);
                if (!empty($codeDoms->length)) {
                    break;
                }
            }
            $codeDomeValue = null;
            if (empty($codeDoms->length)) {
                return $this->output->set_status_header(404)->set_output('Expected return code not received');
            }
            $codeDomeValue = trim($codeDoms->item(0)->nodeValue);
            log_message('debug', __METHOD__ . ' found expected value ' . $codeDomeValue);
            $expectedReturnValues = $fvalidator->getReturnCodeValues();
            $elementWithMessage = $fvalidator->getMessageCodeElements();
            $result = array(
                'returncode' => 'unknown',
                'message' => array()
            );
            foreach ($expectedReturnValues as $k => $v) {

                if (is_array($v)) {

                    foreach ($v as $v1) {
                        if (strcasecmp($codeDomeValue, $v1) == 0) {
                            $result['returncode'] = $k;
                            break;
                        }
                    }
                }
            }
            foreach ($elementWithMessage as $v) {
                log_message('debug', __METHOD__ . ' searching for ' . $v . ' element');
                $o = $docxml->getElementsByTagName($v);
                if ($o->length > 0) {
                    $result['message'][$v] = array();
                    for ($i = 0; $i < $o->length; $i++) {
                        $g = trim($o->item($i)->nodeValue);
                        log_message('debug', __METHOD__ . ' value for ' . $v . ' element: ' . $g);
                        if (!empty($g)) {
                            $result['message'][$v][] = html_escape($g);
                        }
                    }
                }
            }
            if (count($result['message']) == 0) {
                $result['message']['unknown'] = 'no response message';
            }
           return $this->output->set_status_header(200)->set_content_type('application/json')->set_output(json_encode($result));
        }
    }

}
