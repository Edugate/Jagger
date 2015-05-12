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
class Fededit extends MY_Controller
{
    public $fedid;

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->load->library('zacl');
        $this->title = lang('title_fededit');
        $this->fedid = null;
        MY_Controller::$menuactive = 'fed';
    }

    private function _submit_validate()
    {
        $fedid = null;
        if (!empty($this->fedid)) {
            $fedid = $this->fedid;
        }
        $allowedDigests = array('SHA-1', 'SHA-256');
        $ar1 = array('attr' => 'name', 'fedid' => '' . $fedid . '');
        $this->form_validation->set_rules('fedname', lang('rr_fed_name'), 'strip_tags|trim|required|min_length[5]|max_length[128]|federation_updateunique[' . serialize($ar1) . ']');
        $ar2 = array('attr' => 'urn', 'fedid' => '' . $fedid . '');
        $this->form_validation->set_rules('urn', lang('fednameinmeta'), 'strip_tags|trim|required|min_length[5]|max_length[128]|federation_updateunique[' . serialize($ar2) . ']');
        $this->form_validation->set_rules('descid', lang('rr_fed_descid'), 'trim|min_length[1]|max_length[128]|alpha_numeric');
        $this->form_validation->set_rules('description', lang('rr_fed_desc'), 'trim|min_length[5]|max_length[2000]');
        $this->form_validation->set_rules('tou', lang('rr_fed_tou'), 'strip_tags|trim|min_length[5]|max_length[1000]');
        $this->form_validation->set_rules('incattrs', lang('rr_include_attr_in_meta'), 'strip_tags|trim|max_length[10]');
        $this->form_validation->set_rules('ispublic', lang('rr_isfedpublic'), 'strip_tags|trim|max_length[10]');
        $this->form_validation->set_rules('lexport', lang('rr_lexport_enabled'), 'strip_tags|trim|max_length[10]');
        $this->form_validation->set_rules('publisher', lang('rr_fed_publisher'), 'strip_tags|trim|max_length[500]');
        $this->form_validation->set_rules('digestmethod', lang('digestmethodsign'), 'trim|matches_inarray[' . serialize($allowedDigests) . ']');
        $this->form_validation->set_rules('digestmethodext', lang('digestmethodexportsign'), 'trim|matches_inarray[' . serialize($allowedDigests) . ']');
        $this->form_validation->set_rules('usealtmeta', lang('metaalturlinput') . ' radio', 'strip_tags|trim|required');
        $usealtmeta = $this->input->post('usealtmeta');
        if (!empty($usealtmeta) && $usealtmeta === 'ext') {
            $this->form_validation->set_rules('altmetaurl', lang('metaalturlinput'), 'strip_tags|trim|required|valid_url');
        }

        return $this->form_validation->run();
    }

    public function show($fedid)
    {
        if (!ctype_digit($fedid)) {
            show_error(lang('wrongarggiven'), 403);
        }
        $fed_tmp = new models\Federations();
        /**
         * @var $fed models\Federation
         */
        $fed = $fed_tmp->getOneFederationById($fedid);
        if (empty($fed)) {
            show_error(lang('error_fednotfound'), 404);
        }
        $allowedDigests = array('SHA-1', 'SHA-256');
        $this->load->library('form_element');
        $resource = $fed->getId();
        $this->fedid = $resource;
        $fedname = $fed->getName();
        $fedurl = base64url_encode($fed->getName());
        $group = "federation";
        $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . $fedurl . ''), 'name' => '' . $fed->getName() . ''),
            array('url' => '#', 'type' => 'current', 'name' => lang('title_editform'))

        );
        $has_write_access = $this->zacl->check_acl('f_' . $resource, 'write', $group, '');
        $has_manage_access = $this->zacl->check_acl('f_' . $resource, 'manage', $group, '');
        if (($has_write_access || $has_manage_access) === FALSE) {
            show_error(lang('noperm_fededit'), 403);
        }
        if ($this->_submit_validate() === TRUE) {
            $inurn = $this->input->post('urn');
            $fedname = $this->input->post('fedname');
            $indesc = $this->input->post('description');
            $intou = $this->input->post('tou');
            $infedid = $this->input->post('fed');
            $incattrs = $this->input->post('incattrs');
            $lexport = $this->input->post('lexport');
            $ispublic = $this->input->post('ispublic');
            $publisher = $this->input->post('publisher');
            $digest = $this->input->post('digestmethod');
            $digestExport = $this->input->post('digestmethodext');
            $usealtmeta = $this->input->post('usealtmeta');
            $altMetaUrl = $this->input->post('altmetaurl');
            $descid = $this->input->post('descid');
            if ($infedid != $fedid) {
                show_error('Incorrect post', 403);
            }
            if (!empty($usealtmeta) && strcasecmp($usealtmeta, 'ext') == 0) {
                $fed->setAltMetaUrlEnabled(TRUE);
            } else {
                $fed->setAltMetaUrlEnabled(FALSE);
            }

            $fed->setDescriptorId($descid);

            $fed->setAltMetaUrl($altMetaUrl);
            $fed->setName($fedname);
            $fed->setUrn($inurn);
            if ($incattrs == 'accept') {
                $fed->setAttrsInmeta(TRUE);
            } elseif (empty($incattrs)) {
                $fed->setAttrsInmeta(FALSE);
            }
            if (empty($ispublic)) {
                $fed->unPublish();
            } elseif ($ispublic === 'accept') {
                $fed->publish();
            }

            if ($lexport == 'accept') {
                $fed->setLocalExport(TRUE);
            } elseif (empty($lexport)) {
                $fed->setLocalExport(FALSE);
            }
            $fed->setPublisher($publisher);
            $fed->setDescription($indesc);
            $fed->setTou($intou);
            $fed->setDigest($digest);
            $fed->setDigestExport($digestExport);
            $this->em->persist($fed);
            try {
                $fedurl = base64url_encode($fedname);
                $this->em->flush();
                log_message('info', 'Basic information for federation ' . $fedname . ' has been updated');
                $data['encodedfedname'] = $fedurl;
                $data['success_message'] = sprintf(lang('rr_fedinfo_updated'), $fedname);
                $data['titlepage'] = lang('rr_federation') . ': <a href="' . base_url() . 'federations/manage/show/' . $fedurl . '">' . html_escape($fedname) . '</a>';
            } catch (Exception $e) {
                log_message('error', $e);
                $data['error_message'] = 'Error occured';
            }
        } else {


            $attributes = array('id' => 'formver2', 'class' => 'span-16');
            $action = base_url() . "manage/fededit/show/" . $fedid;
            $hidden = array('fed' => '' . $fedid);
            $f = validation_errors('<div  data-alert class="alert-box alert">', '</div>');
            $f .= form_open($action, $attributes, $hidden);
            $f .= $this->form_element->generateFederationEditForm($fed);
            $tf = '<div class="buttons small-11 large-10 columns text-right">';
            $tf .= '<button type="reset" name="reset" value="reset" class="resetbutton reseticon button alert">
                  ' . lang('rr_reset') . '</button> ';
            $tf .= '<button type="submit" name="modify" value="submit" class="savebutton saveicon button">
                  ' . lang('rr_save') . '</button>';
            $tf .= '</div><div class="small-1 large-2 columns end"></div>';

            $tf .= '</div>';

            $f .= $tf;
            $data['form'] = $f . form_close();
            $data['titlepage'] = lang('rr_federation') . ': <a href="' . base_url() . 'federations/manage/show/' . $fedurl . '">' . html_escape($fedname) . '</a>';

        }
        $data['subtitlepage'] = lang('rr_fededitform') . '';
        $data['content_view'] = 'manage/fededit_view';
        $this->load->view('page', $data);

    }

}
