<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */
/**
 * Fvalidator Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

/**
 * @todo add permission to check for public or private perms
 */
class Fvalidator extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    public function detail($fedid, $validatorid)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            redirect('auth/login', 'location');
        }
    }

    public function detailjson($fid = null, $fvid = null)
    {
        $is_ajax = $this->input->is_ajax_request();
        if (!$is_ajax)
        {
            show_error('request not allowed', 401);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {

            set_status_header(401);
            echo 'Not authorized';
            return;
        }
        if (!empty($fid) && !empty($fvid))
        {
            $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id' => $fvid, 'federation' => $fid, 'isEnabled' => TRUE));
            if (empty($fvalidator))
            {
                set_status_header(404);
                echo 'Not found';
                return;
            }
            else
            {
                $result = array('id' => $fvalidator->getId(), 'fedid' => $fid, 'name' => $fvalidator->getName(), 'desc' => $fvalidator->getDescription());
                echo json_encode($result);
                return;
            }
        }
        $fedid = $this->input->post('fedid');
        if (empty($fedid) || !is_numeric($fedid))
        {

            set_status_header(404);
            echo 'Not found';
            return;
        }
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if (empty($fed))
        {

            set_status_header(404);
            echo 'Federation not found';
            return;
        }
        $validators = $fed->getValidators();
        $validator = array();

        foreach ($validators as $v)
        {
            $venabled = $v->getEnabled();
            if ($venabled)
            {
                $validator['' . $v->getId() . ''] = array('id' => $v->getId(), 'fedid' => $fedid, 'name' => htmlspecialchars($v->getName()), 'desc' => htmlspecialchars($v->getDescription()));
            }
        }
        if (count($validator) > 0)
        {
            echo json_encode($validator);
        }
        else
        {
            show_error('fed not found', 404);
        }
    }

    public function validate()
    {
        if (!($this->input->is_ajax_request() && $this->j_auth->logged_in()))
        {
            show_error('access denied', 403);
        }
        $inputArgs = array(
            'providerid' => trim($this->input->post('provid')),
            'federationid' => trim($this->input->post('fedid')),
            'fvalidatorid' => trim($this->input->post('fvid')),
            'queuetoken' => trim($this->input->post('qtoken')),
            'tmpprovid' => trim($this->input->post('tmpprovid'))
        );
        if (empty($inputArgs['federationid']) || !ctype_digit($inputArgs['federationid']) || empty($inputArgs['fvalidatorid']) || !ctype_digit($inputArgs['fvalidatorid']))
        {
            set_status_header(403);
            echo 'incorrect/missing paramters  passed';
            return;
        }
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $inputArgs['federationid']));
        $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id' => $inputArgs['fvalidatorid']));

        $reqAttrPassed = FALSE;
        if(!empty($inputArgs['queuetoken']) && ctype_alnum($inputArgs['queuetoken']))
        {
            $providerMetadataUrl = base_url().'metadata/queue/'.$inputArgs['queuetoken'];
            $reqAttrPassed = TRUE;
        }
        elseif(!empty($inputArgs['providerid']) && ctype_digit($inputArgs['providerid']))
        {
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $inputArgs['providerid']));
            if(!empty($provider))
            {
                $providerMetadataUrl = base_url() . '/metadata/service/' . base64url_encode($provider->getEntityId()) . '/metadata.xml';
                $reqAttrPassed = TRUE;
            }
        }      
        if (!$reqAttrPassed || empty($fvalidator) || empty($federation))
        {
            set_status_header(404);
            echo 'One of following not found: fedValidator, federation, requesttype';
            return;
        }
        $validators = $federation->getValidators();
        if (!$validators->contains($fvalidator))
        {
            
            set_status_header(404);
            echo 'federation desnt match validator';
        }
        $method = $fvalidator->getMethod();
        $remoteUrl = $fvalidator->getUrl();
        $entityParam = $fvalidator->getEntityParam();
        $optArgs = $fvalidator->getOptargs();
        $params = array();
        if (strcmp($method, 'GET') == 0)
        {
            $separator = $fvalidator->getSeparator();
            $optArgsStr = '';
            foreach ($optArgs as $k => $v)
            {
                if ($v === null)
                {
                    $optArgsStr .=$k . $separator;
                }
                else
                {
                    $optArgsStr .= $k . '=' . $v . '' . $separator;
                }
            }
            $optArgsStr .=$entityParam . '=' . urlencode($providerMetadataUrl);
            $remoteUrl = $remoteUrl . $optArgsStr;
            $this->curl->create('' . $remoteUrl . '');
        }
        else
        {
            $params = $optArgs;
            $params['' . $entityParam . ''] = $providerMetadataUrl;
            $this->curl->create('' . $remoteUrl . '');
            $this->curl->post($params);
        }

        $addoptions = array();
        $this->curl->options($addoptions);
        $data = $this->curl->execute();
        if (empty($data))
        {
            show_error('No data received from external validator', 500);
        }
        log_message('debug', __METHOD__ . ' data received: ' . $data);
        $expectedDocumentType = $fvalidator->getDocutmentType();
        if (strcmp($expectedDocumentType, 'xml') != 0)
        {
            show_error('Other than xml not supported yet', 403);
        }
        else
        {
            libxml_use_internal_errors(true);
            $sxe = simplexml_load_string($data);
            if (!$sxe)
            {
                show_error('Received invalid xml document', 403);
            }
            $docxml = new \DomDocument();
            $docxml->loadXML($data);
            $returncodeElements = $fvalidator->getReturnCodeElement();
            if (count($returncodeElements) == 0)
            {
                show_error('Returncode not define', 500);
            }
            foreach ($returncodeElements as $v)
            {
                $codeDoms = $docxml->getElementsByTagName($v);
                if (!empty($codeDoms->length))
                {
                    break;
                }
            }
            $codeDomeValue = null;
            if (empty($codeDoms->length))
            {
                show_error('Expected return code not received', 404);
            }
            $codeDomeValue = trim($codeDoms->item(0)->nodeValue);
            log_message('debug', __METHOD__ . ' found expected value ' . $codeDomeValue);
            $expectedReturnValues = $fvalidator->getReturnCodeValues();
            $elementWithMessage = $fvalidator->getMessageCodeElements();
            $result = array();
            foreach ($expectedReturnValues as $k => $v)
            {

                if (is_array($v))
                {

                    foreach ($v as $v1)
                    {
                        if (strcasecmp($codeDomeValue, $v1) == 0)
                        {
                            $result['returncode'] = $k;
                            break;
                        }
                    }
                }
            }
            if (!isset($result['returncode']))
            {
                $result['returncode'] = 'unknown';
            }
            $result['message'] = array();
            foreach ($elementWithMessage as $v)
            {
                log_message('debug', __METHOD__ . ' searching for ' . $v . ' element');
                $o = $docxml->getElementsByTagName($v);
                if ($o->length > 0)
                {
                    $result['message'][$v] = array();
                    for ($i = 0; $i < $o->length; $i++)
                    {
                        $g = trim($o->item($i)->nodeValue);
                        log_message('debug', __METHOD__ . ' value for ' . $v . ' element: ' . $g);
                        if (!empty($g))
                        {
                            $result['message'][$v][] = htmlspecialchars($g);
                        }
                    }
                }
            }
            if (count($result['message']) == 0)
            {
                $result['message']['unknown'] = 'no response message';
            }
            echo json_encode($result);
        }
    }

}
