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
 * Fededit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Fededit extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->load->library('zacl');
        $this->title = lang('title_fededit');
    }

    private function _submit_validate()
    {
        $this->form_validation->set_rules('urn', lang('fednameinmeta'), 'required|trim|min_length[5]|max_length[128]|xss_clean');
        $this->form_validation->set_rules('description', lang('rr_fed_desc'), 'trim|min_length[5]|max_length[500]|xss_clean');
        $this->form_validation->set_rules('tou', lang('rr_fed_tou'), 'trim|min_length[5]|max_length[1000]|xss_clean');
        $this->form_validation->set_rules('incattrs',lang('rr_include_attr_in_meta'),'trim|xss_clean|max_length[10]');
        $this->form_validation->set_rules('ispublic',lang('rr_isfedpublic'),'trim|xss_clean|max_length[10]');
        $this->form_validation->set_rules('lexport',lang('rr_lexport_enabled'),'trim|xss_clean|max_length[10]');
        $this->form_validation->set_rules('publisher',lang('rr_fed_publisher'),'trim|xss_clean|max_length[500]');
        return $this->form_validation->run();
    }

    public function show($fedid)
    {
        if (!is_numeric($fedid))
        {
            show_error(lang('wrongarggiven'), 403);
        }
        $fed_tmp = new models\Federations();
        $fed = $fed_tmp->getOneFederationById($fedid);
        if (empty($fed))
        {
            show_error(lang('error_fednotfound'), 404);
        }
        $this->load->library('form_element');
        $resource = $fed->getId();
        $fedname = $fed->getName();
        $fedurl= base64url_encode($fed->getName());
        $group = "federation";
        $has_write_access = $this->zacl->check_acl('f_' . $resource, 'write', $group, '');
        $has_manage_access = $this->zacl->check_acl('f_' . $resource, 'manage', $group, '');
        if (($has_write_access OR $has_manage_access) === FALSE)
        {
            show_error(lang('noperm_fededit'), 403);
        }
        if ($this->_submit_validate() === TRUE)
        {
            $inurn = $this->input->post('urn');
            $indesc = $this->input->post('description');
            $intou = $this->input->post('tou');
            $infedid = $this->input->post('fed');
            $incattrs = $this->input->post('incattrs');
            $lexport = $this->input->post('lexport');
            $ispublic = $this->input->post('ispublic');
            $publisher = $this->input->post('publisher');
            if ($infedid != $fedid)
            {
                show_error('Incorrect post', 403);
            }
            $fed->setUrn($inurn);
            if($incattrs == 'accept')
            {
                $fed->setAttrsInmeta(TRUE);
            }
            elseif(empty($incattrs))
            {
                $fed->setAttrsInmeta(FALSE);
            }
            if(empty($ispublic))
            {
                $fed->unPublish();
            }
            elseif($ispublic === 'accept')
            {
                $fed->publish();
            }

            if($lexport == 'accept')
            {
                $fed->setLocalExport(TRUE);
            }
            elseif(empty($lexport))
            {
                $fed->setLocalExport(FALSE);
            }
            $fed->setPublisher($publisher);
            $fed->setDescription($indesc);
            $fed->setTou($intou);
            $this->em->persist($fed);
            $this->em->flush();
            log_message('info','Basic information for federation '.$fedname.' has been updated');
            $data['success_message'] = sprintf(lang('rr_fedinfo_updated'),$fedname);
        }
        else
        {


            $attributes = array('id' => 'formver2', 'class' => 'span-16');
            $action = base_url() . "manage/fededit/show/" . $fedid;
            $hidden = array('fed' => '' . $fedid);
            $f = validation_errors('<p class="error">', '</p>');
            $f .= form_open($action, $attributes, $hidden);
            $f .= $this->form_element->generateFederationEditForm($fed);
            $tf = '<div class="buttons">';
            $tf .='<button type="reset" name="reset" value="reset" class="resetbutton reseticon">
                  ' . lang('rr_reset') . '</button> ';
            $tf .='<button type="submit" name="modify" value="submit" class="savebutton saveicon">
                  ' . lang('rr_save') . '</button>';
            $tf .= '</div>';

            $f .=$tf;
            $data['form'] = $f;
            $data['form'] .= form_close();
        
        }
            $data['pagetitle'] = lang('rr_fededitform');
            $data['subtitle'] = lang('rr_federation').': <a href="'.base_url().'federations/manage/show/'.$fedurl.'">'.htmlspecialchars($fedname).'</a>';
            $data['content_view'] = 'manage/fededit_view';
            $this->load->view('page', $data);
        
    }

}
