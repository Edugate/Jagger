<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * Statistics Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Statistics extends MY_Controller {

    function __construct()
    {
        parent::__construct();
    }

    function latest($id)
    {

        if (empty($id) or !is_numeric($id))
        {
            show_error('Not found');
        }
        $datastorage = $this->config->item('datastorage_path');
        if (empty($datastorage))
        {
            log_message('error', 'Missing datastorage_path in config');
            show_error('not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            show_error('Access denied', 403);
        }
        $this->load->library('zacl');

        $def = $this->em->getRepository("models\ProviderStatsDef")->findOneBy(array('id' => $id));
        if (empty($def))
        {
            show_error('not found', 404);
        }
        $p = $def->getProvider();
        if (empty($p))
        {
            show_error('not found', 404);
        }
        $hasAccess = $this->zacl->check_acl('' . $p->getId() . '', 'write', 'entity', '');
        if (!$hasAccess)
        {
            show_error(lang('rr_noperm'), 403);
        }
        $s=null;
        $stats = $this->em->getRepository("models\ProviderStatsCollection")->findBy(array('provider'=>$p->getId(),'statdefinition'=>$def->getId()), array('id'=>'DESC'));
        if(count($stats)>0)
        {
            $s = $stats['0'];
        }
            
        $statstorage = $datastorage . 'stats/';
        if (!is_dir($statstorage))
        {
            log_message('debug', 'directory ' . $statstorage . 'not exist');
            show_error('dddd', 404);
        }
        $filename = $s->getFilename();
        $fullpath = $statstorage . $filename;
        if (!is_file($fullpath))
        {
            log_message('error', 'Stat record id:' . $s->getId() . ' : file doesnt exist in datastorage');
            show_error('not found', 404);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileContents = file_get_contents($fullpath);
        $mimeType = $finfo->buffer($fileContents);
        $this->output
                ->set_content_type($mimeType)
                ->set_output($fileContents);
    }
    

    function show($id = null)
    {

        if (empty($id) or !is_numeric($id))
        {
            show_error('Not found');
        }
        $datastorage = $this->config->item('datastorage_path');
        if (empty($datastorage))
        {
            log_message('error', 'Missing datastorage_path in config');
            show_error('not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            show_error('Access denied', 403);
        }
        $this->load->library('zacl');

        $s = $this->em->getRepository('models\ProviderStatsCollection')->findOneBy(array('id' => $id));
        if (empty($s))
        {
            log_message('debug', 'requested stat not found');
            show_error('Not found', 404);
        }
        $p = $s->getProvider();
        if (empty($p))
        {
            log_message('error', 'Found orphaned statists with id:' . $s->getId());
            show_error('Not found', 404);
        }
        $hasAccess = $this->zacl->check_acl('' . $p->getId() . '', 'write', 'entity', '');
        if (!$hasAccess)
        {
            show_error(lang('rr_noperm'), 403);
        }

        $statstorage = $datastorage . 'stats/';
        if (!is_dir($statstorage))
        {
            log_message('debug', 'directory ' . $statstorage . 'not exist');
            show_error('dddd', 404);
        }

        $filename = $s->getFilename();
        $fullpath = $statstorage . $filename;
        if (!is_file($fullpath))
        {
            log_message('error', 'Stat record id:' . $s->getId() . ' : file doesnt exist in datastorage');
            show_error('not found', 404);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileContents = file_get_contents($fullpath);
        $mimeType = $finfo->buffer($fileContents);
        $this->output
                ->set_content_type($mimeType)
                ->set_output($fileContents);
    }

}
