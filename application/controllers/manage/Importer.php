<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * Importer Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Importer extends MY_Controller {

    private $tmp_providers;
    private $tmp_attributes;
    private $tmp_arps;
    protected $other_error = array();
    private $access;
    protected $xmlbody;
    protected $curl_maxsize;
    protected $xmlDOM;

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        } else
        {
            $this->load->helper(array('cert', 'form'));
            $this->load->library(array('form_validation', 'curl', 'metadata2import', 'form_element'));
            $this->tmp_providers = new models\Providers;
            $this->tmp_attributes = new models\Attributes;
            $this->tmp_arps = new models\AttributeReleasePolicies;
            $this->load->library('zacl');
            $this->access = $this->zacl->check_acl('importer', 'create', '', '');
        }
    }

    /**
     * display form
     */
    function index()
    {
        $access = $this->access;
        if (!$access)
        {
            $data['content_view'] = "nopermission";
            $data['error'] = lang('error403');
            $this->load->view('page', $data);
        } else
        {

            $data['title'] = "import metadata";
            $data['content_view'] = "manage/import_metadata_form";
            $data['other_error'] = $this->other_error;
            $data['global_erros'] = $this->globalerrors;
            $data['federations'] = $this->form_element->getFederation();
            $data['types'] = $this->form_element->buildTypeOfEntities();
            $this->load->view('page', $data);
        }
    }

    function submit()
    {
        $this->globalerrors = array();
        $this->other_error = array();
        $access = $this->access;
        if (!$access)
        {
            show_error('no access', 403);
        }

        log_message('debug', "importer submited");
        $data = array();
        if ($this->_submit_validate() !== TRUE)
        {
            return $this->index();
        }

        $arg['metadataurl'] = $this->input->post('metadataurl');
        $arg['certurl'] = trim($this->input->post('certurl'));
        $arg['cert'] = trim($this->input->post('cert'));
        $arg['validate'] = $this->input->post('validate');
        $arg['sslcheck'] = trim($this->input->post('sslcheck'));

        if(!empty($arg['sslcheck']) && $arg['sslcheck'] === 'ignore')
        {
            $sslvalidate = FALSE;
        }
        else
        {
            $sslvalidate = TRUE;
        }

        if($arg['validate'] === 'accept')
        {
           $mvalidate = TRUE;
           if(!empty($arg['cert']))
           {
              $mcerturl = FALSE;
              $mcert = $arg['cert'];
           }
           elseif(!empty($arg['certurl']))
           {
              $mcerturl = $arg['certurl'];
              $mcert = FALSE;

           }
           else
           {
               $this->other_error[] = lang('certsignerurlbodymissing');
               return $this->index();
           }
        }
        else
        {
           $mvalidate = FALSE;
           $mcerturl = FALSE;
           $mcert = FALSE;
        }

        if ($this->_metadatasigner_validate($arg['metadataurl'], $sslvalidate, $mvalidate, $mcerturl, $mcert) !== TRUE)
        {
            return $this->index();
        }
        $arg['type'] = $this->input->post('type');
        $arg['extorint'] = $this->input->post('extorint');
        $arg['active'] = $this->input->post('active');
        $arg['static'] = $this->input->post('static');
        $arg['overwrite'] = $this->input->post('overwrite');
        $arg['federation'] = $this->input->post('federation');
        $arg['fullinformation'] = trim($this->input->post('fullinformation'));

        /**
         * @todo  check if you have permission to add entities to this federation
         */
        $tmp = new models\Federations();

        $fed = $tmp->getOneByName($arg['federation']);
        if (empty($fed))
        {
            $this->other_error[] = 'No permission to add entities to selected federation';
            return $this->index();
        }


        /**
         * replace below if calling function
         * check if metadata_body if xml and valid against schema
         */

        if ($arg['extorint'] == 'int')
        {
            $local = true;
        } else
        {
            $local = false;
        }
        if ($arg['active'] == 'yes')
        {
            $active = true;
        } else
        {
            $active = false;
        }
        if ($arg['static'] == 'yes')
        {
            $static = true;
        } else
        {
            $static = false;
        }
        if ($arg['overwrite'] == 'yes')
        {
            $overwrite = true;
        } else
        {
            $overwrite = false;
        }
        if ($arg['fullinformation'] == 'yes')
        {
            $full = true;
        } else
        {
            $full = false;
        }
        if (!($arg['type'] == 'idp' OR $arg['type'] == 'sp' OR $arg['type'] == 'all'))
        {
            log_message('error', 'Cannot import metadata because type of entities is not set correctly');
            return $this->index();
        }
        $defaults = array(
            'overwritelocal' => $overwrite,
            'active' => $active,
            'static' => $static,
            'local' => $local,
            'federations' => array($fed->getName())
        );
        foreach ($defaults as $key => $value)
        {
            if (!is_array($value))
            {
                log_message('debug', 'importer: defaults:' . $key . '=' . $value);
            }
        }
        $other = null;
        $type_of_entities = strtoupper($arg['type']);
        //$result = $this->metadata2import->import($metadata_body, $type_of_entities, $full, $defaults, $other);
        $result = $this->metadata2import->import($this->xmlDOM, $type_of_entities, $full, $defaults, $other);
        if ($result)
        {
            $data['title'] = lang('titleimportmeta');
            $data['success_message'] = lang('okmetaimported');
            $data['content_view'] = "manage/import_metadata_success_view";
            $this->load->view('page', $data);
        } else
        {
            return $this->index();
        }
    }

    /**
     * @todo more validation rules
     */
    private function _submit_validate()
    {
        $this->form_validation->set_rules('metadataurl', 'Metadata URL', 'trim|required|valid_url');
        $this->form_validation->set_rules('sslcheck', 'SSL check', 'trim');
        $this->form_validation->set_rules('validate', 'verify', 'trim');
        $this->form_validation->set_rules('cert', 'cert verify', 'trim|verify_cert');
        $this->form_validation->set_rules('certurl', 'cert url', 'trim|valid_url');
        $this->form_validation->set_rules('type', lang('typeofents'), 'trim|required');
        $this->form_validation->set_rules('extorint', 'Internal/External', 'trim|required');
        $this->form_validation->set_rules('federation', lang('rr_federation'), 'trim|required');
        $this->form_validation->set_rules('static', 'Static metadata by default', 'trim|required');
        $this->form_validation->set_rules('active', 'Decide if enabled by default', 'trim|required');
        $this->form_validation->set_rules('overwrite', 'Decide if enabled by default', 'trim|required');
        $this->form_validation->set_rules('fullinformation', 'Populate full information', 'trim|required');
        return $this->form_validation->run();
    }

    /**
     * @todo finish this function  if validate is set then check certbody or cerurl, certbody has higher priority
     */
    private function _metadatasigner_validate($metadataurl,$sslvalidate=FALSE, $signed=FALSE,$certurl=FALSE,$certbody=FALSE)
    {
         $curl_timeout =  $this->config->item('curl_timeout');
         $this->curl_maxsize = $this->config->item('curl_metadata_maxsize');
         if (!isset($curl_timeout))
         {
            $curl_timeout = 30;
         }
         if(!isset($this->curl_maxsize))
         {
             $this->curl_maxsize = 20000;
         }
         $maxsize = $this->curl_maxsize;
         if($sslvalidate)
         {
             $this->xmlbody = $this->curl->simple_get(''.$metadataurl.'', array(), array(
                                  CURLOPT_TIMEOUT => $curl_timeout,
                                  CURLOPT_BUFFERSIZE=>128,
                                  CURLOPT_NOPROGRESS=>FALSE,
                                  CURLOPT_PROGRESSFUNCTION=>function($DownloadSize, $Downloaded, $UploadSize, $Uploaded)  use ($maxsize)
                                                         {
                                                             return ($Downloaded > ($maxsize * 1024)) ? 1 : 0;
                                                         }
                          ));

         }
         else
         {
             $this->xmlbody = $this->curl->simple_get(''.$metadataurl.'', array(), array(
                           CURLOPT_SSL_VERIFYPEER => $sslvalidate,
                           CURLOPT_SSL_VERIFYHOST => $sslvalidate,
                                  CURLOPT_TIMEOUT => $curl_timeout,
                                  CURLOPT_BUFFERSIZE=>128,
                                  CURLOPT_NOPROGRESS=>FALSE,
                                  CURLOPT_PROGRESSFUNCTION=>function($DownloadSize, $Downloaded, $UploadSize, $Uploaded)  use ($maxsize)
                                                         {
                                                             return ($Downloaded > ($maxsize * 1024)) ? 1 : 0;
                                                         }
                          ));
          }
          if(empty($this->xmlbody))
          {
              $this->other_error[] = $this->curl->error_string;
              return FALSE;
          }
          $this->load->library('xmlvalidator');
          libxml_use_internal_errors(true);
          $this->xmlDOM = new \DOMDocument();
          $this->xmlDOM->strictErrorChecking = FALSE;
          $this->xmlDOM->WarningChecking = FALSE;
          
          $this->xmlDOM->loadXML($this->xmlbody);
          log_message('debug',__METHOD__.' metadata xml loaded into DOMDocument - elements: '.$this->xmlDOM->childNodes->length);
          $valid_metadata = FALSE;
          if($signed === FALSE)
          {
             $valid_metadata = $this->xmlvalidator->validateMetadata($this->xmlDOM,FALSE,FALSE); 
          }
          else
          {
             if(!empty($certbody))
             {
                  if(validateX509($certbody))
                  {
                      $valid_metadata = $this->xmlvalidator->validateMetadata($this->xmlDOM,TRUE,$certbody); 
                  }
                  else
                  {
                        $this->other_error[] = lang('einvalidcertsignerdata');
                        return FALSE;

                  }
             }
             elseif(!empty($certurl))
             {
                   if($sslvalidate)
                   {
                   $certdata = $this->curl->simple_get(''.$certurl.'', array(), array(
                                  CURLOPT_TIMEOUT => $curl_timeout,
                               CURLOPT_BUFFERSIZE => 128,
                               CURLOPT_NOPROGRESS => FALSE,
                         CURLOPT_PROGRESSFUNCTION => function($DownloadSize, $Downloaded, $UploadSize, $Uploaded)
                                                     {
                                                        return ($Downloaded > (1000 * 1024)) ? 1 : 0;
                                                     }
                          ));
                     }
                     else
                     {
                          $certdata = $this->curl->simple_get(''.$certurl.'', array(), array(
                              CURLOPT_SSL_VERIFYPEER => $sslvalidate,
                              CURLOPT_SSL_VERIFYHOST => $sslvalidate,
                                     CURLOPT_TIMEOUT => $curl_timeout,
                                  CURLOPT_BUFFERSIZE => 128,
                                  CURLOPT_NOPROGRESS => FALSE,
                             CURLOPT_PROGRESSFUNCTION => function($DownloadSize, $Downloaded, $UploadSize, $Uploaded)
                                                     {
                                                        return ($Downloaded > (1000 * 1024)) ? 1 : 0;
                                                     }
                          ));

                     }

                    if(!empty($certdata) && validateX509($certdata))
                    {
                        $valid_metadata = $this->xmlvalidator->validateMetadata($this->xmlDOM,TRUE,$certdata); 
                    }
                    else
                    {
                        $this->other_error[] = lang('einvalidcertsignerurl');
                        return FALSE;
                    }
                  
             }
            
          }
          return $valid_metadata;
          

                  
    }

}
