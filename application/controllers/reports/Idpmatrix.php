<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Idpmatrix Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Idpmatrix extends MY_Controller
{

    private $tmp_providers;

    function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('table');
        $this->load->library('arp_generator');
        $this->tmp_providers = new models\Providers;
        $this->current_site = current_url();
        $this->logo_basepath = $this->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;
    }


    public function getArpData($idpid)
    {
        if (!$this->input->is_ajax_request())
        {
            set_status_header(403);
            echo 'denied';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            set_status_header(403);
            echo 'no valid session';
            return;
        }
        $this->load->library('zacl');
        $idp = $this->tmp_providers->getOneIdpById($idpid);
        if (empty($idp))
        {
            set_status_header(404);
            echo 'IdP not found';
            return;
        }
        $has_read_access = $this->zacl->check_acl($idpid, 'read', 'entity', '');
        if (!$has_read_access)
        {
            set_status_header(403);
            echo 'no perms';
            return;
        }
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        foreach ($attrs as $a)
        {
            $attrdefs[$a->getName()] = $a->getId();
            $attrlist[$a->getName()] = 0;
        }
        $attrdedsCopy = $attrdefs;
        $returnArray = TRUE;
        $arparray['policies'] = $this->arp_generator->arpToXML($idp, $returnArray);

        if(isset($arparray['policies']) && is_array($arparray['policies']))
        {
       
           foreach($arparray['policies'] as $p)
           {
              foreach($p['attributes'] as $k => $v)
              {
                 unset($attrdedsCopy[''.$k.'']);
              }
              foreach($p['req'] as $k => $v)
              {
                 unset($attrdedsCopy[''.$k.'']);
              }
           }
        }
        $attrdefsLeft = array_diff_key($attrdefs,$attrdedsCopy);
        ksort($attrdefsLeft);
        ksort($attrlist);
        $arparray['total']= count($arparray['policies']);
        $arparray['attributes'] = $attrdefsLeft;
        $arparray['attrlist'] = $attrlist;
        echo json_encode($arparray);
        return;
    }

    public function show($idpid)
    {
        if (empty($idpid) || !is_numeric($idpid))
        {
            show_error('Wrong or empty id', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'awaiting'));
            $this->load->library('zacl');
        }
        else
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $idp = $this->tmp_providers->getOneIdpById($idpid);
        if (empty($idp))
        {
            show_error('Identity Provider not found', 404);
        }

        $has_read_access = $this->zacl->check_acl($idpid, 'read', 'entity', '');
        $has_write_access = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$has_read_access)
        {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noidpaccess');
            $this->load->view('page', $data);
            return;
        }
        $data['has_write_access'] = $has_write_access;

        $data['excluded'] = $idp->getExcarps();
        $lang = MY_Controller::getLang();

        $data['idpname'] = $idp->getNameToWebInLang($lang, 'IDP');
        ;
        $data['idpid'] = $idp->getId();
        $data['entityid'] = $idp->getEntityId();

       
        $extends = $idp->getExtendMetadata();
        if (count($extends) > 0)
        {
            foreach ($extends as $ex)
            {
                $el = $ex->getElement();
                if ($el === 'Logo')
                {
                    $data['providerlogourl'] = $ex->getLogoValue();
                    break;
                }
            }
        }

        $data['titlepage'] = lang('identityprovider') . ': ' . anchor('' . base_url() . 'providers/detail/show/' . $data['idpid'], $data['idpname']) . '<br />';
        $data['titlepage'] .= $data['entityid'];
        $data['subtitlepage'] = lang('rr_arpoverview');
        
   

   
    
     
        $data['entityid'] = $idp->getEntityId();
        $data['idpid'] = $idp->getId();
      

        $data['content_view'] = 'reports/idpmatrix_show_view';
        $this->load->view('page', $data);
    }

}
