<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Jagger
 * 
 * @package     Jagger
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Logo Class
 * 
 * @package     Jagger
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Logomngmt extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('logo');
        $this->load->library('form_validation');
    }

    public function getAssignedLogosInGrid($type = null, $id = null)
    {
        if (!$this->input->is_ajax_request() || empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo lang('errsess');
            return;
        }

        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if (empty($provider)) {
            set_status_header(404);
            echo lang('rerror_provnotfound');
            return;
        }
        $this->load->library('zacl');
        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $unlocked = !($provider->getLocked());
        $local = $provider->getLocal();
        $canEdit = (boolean) ($has_write_access && $unlocked && $local);
        $attributes = array('class' => 'span-16', 'id' => 'assignedlogos');
        $target_url = base_url() . 'manage/logomngmt/unsign/' . $type . '/' . $id;
        $existing_logos = $this->em->getRepository("models\ExtendMetadata")->findBy(array('etype' => $type, 'namespace' => 'mdui', 'element' => 'Logo', 'provider' => $id));

        $count_existing_logos = count($existing_logos);
        if ($count_existing_logos > 0) {
            $form1 = '<span>';
            $form1 .= form_open(base_url() . 'manage/logomngmt/unsign/' . $type . '/' . $id, $attributes);
            $form1 .= $this->logo->displayCurrentInGridForm($provider, $type, $canEdit);
            $form1 .= '<div class="buttons" id="unsignlogosbtn" >';
            $form1 .= '<button name="remove" type="submit" value="Remove selected" class="resetbutton reseticon alert">' . lang('rr_unsignselectedlogo') . '</button> ';
            $form1 .= '</div>';
            $form1 .= form_close();
            $form1 .= '</span>';
            echo $form1;
        }
        else {

            echo 'No assigned Logos';
        }
    }

    public function getAvailableLogosInGrid($type = null, $id = null)
    {
        if ($this->input->is_ajax_request() && $this->j_auth->logged_in() && !empty($type) && !empty($id)) {
            $this->load->library('logo');
            $attributes = array('class' => 'span-20', 'id' => 'availablelogos');
            $availableImages = $this->logo->displayAvailableInGridForm('filename', 3);

            $form1 = form_open(base_url() . 'manage/logomngmt/assign/' . $type . '/' . $id, $attributes);
            $form1 .= form_fieldset('' . lang('rr_selectimagetoassign') . '');
            if (!empty($availableImages)) {
                $form1 .= '<div class="buttons" style="display: none"><button name="submit" type="submit" value="submit" class="savebutton saveicon">
                      ' . lang('rr_btn_assignselecetedlogo') . '</button></div>';
                $form1 .= $availableImages;
            }
            else {
                $form1 .= '<div class="alert">' . lang('rr_nolocalimages') . '</div>';
            }
            $form1 .= form_fieldset_close();
            $form1 .= form_close();
            echo $form1;
        }
        else {
            set_status_header(403);
            echo lang('error403');
            return;
        }
    }

    public function assign($type = null, $id = null)
    {
        if (!$this->input->is_ajax_request() || ($_SERVER['REQUEST_METHOD'] !== 'POST')) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        if (empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            set_status_header(404);
            echo lang('error404');
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo lang('errsess');
            return;
        }
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if (empty($provider)) {
            set_status_header(404);
            echo lang('rerror_provnotfound');
            return;
        }
        $this->load->library('zacl');
        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $unlocked = !($provider->getLocked());
        $local = $provider->getLocal();
        $canEdit = (boolean) ($has_write_access && $unlocked && $local);
        if (!$canEdit) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        $logopost = $this->input->post('filename');
        if (empty($logopost)) {
            set_status_header(403);
            echo lang('error403') .' ';
            return;
        }
        $explodedLogopost = explode('_size_', $logopost);
        if (count($explodedLogopost) != 2) {
            log_message('error', 'incorrect  value given:' . $this->input->post('filename') . ' , must be in format: filename_size_widthxheight');
            set_status_header(403);
            echo lang('error403') . ': '.lang('error_incorrectinput');
            return;
        }
        $new_logoname = $explodedLogopost['0'];
        $original_sizes = explode('x', $explodedLogopost['1']);
        $logo_attr = array();
        if (!empty($new_logoname)) {
            $width = $this->input->post('width');
            $height = $this->input->post('height');
            if (!empty($width)) {
                $logo_attr['width'] = $width;
            }
            if (!empty($height)) {
                $logo_attr['height'] = $height;
            }
            if (empty($logo_attr['width']) && empty($logo_attr['height'])) {
                $logo_attr['width'] = $original_sizes['0'];
                $logo_attr['height'] = $original_sizes['1'];
            }
            $parent = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('element' => 'UIInfo', 'provider' => $provider->getId(), 'namespace' => 'mdui', 'etype' => $type));
            if (empty($parent)) {
                $parent = new models\ExtendMetadata;
                $parent->setElement('UIInfo');
                $parent->setProvider($provider);
                $parent->setParent(null);
                $parent->setNamespace('mdui');
                $parent->setType($type);
                $this->em->persist($parent);
            }
            $logo = new models\ExtendMetadata;
            $logo->setLogo($new_logoname, $provider, $parent, $logo_attr, $type);
            $this->em->persist($logo);
            $this->em->flush();
            echo lang('rr_logoisassigned');
        }
        else {
            set_status_header(403);
            echo lang('error403');
            return;
        }
    }

    public function unsign($type = null, $id = null)
    {
        if (!$this->input->is_ajax_request() || ($_SERVER['REQUEST_METHOD'] !== 'POST') || !$this->j_auth->logged_in()) {
            $s=403;
            $msg =  lang('error403');
        }
        elseif (empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            $s=404;
            $msg =lang('error403');
        }
        if($s)
        {
            set_status_header($s);
            echo $msg;
            return;
        }
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if (empty($provider)) {
            set_status_header(404);
            echo lang('error404');
            return;
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $unlocked = !($provider->getLocked());
        $local = $provider->getLocal();
        $canEdit = (boolean) ($hasWriteAccess && $unlocked && $local);
        if (!$canEdit) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        $logoidPost = $this->input->post('logoid');
        if (empty($logoidPost) || !ctype_digit($logoidPost)) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        $existingLogo = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('id' => $logoidPost, 'etype' => $type, 'namespace' => 'mdui', 'element' => 'Logo', 'provider' => $id));
        if (empty($existingLogo)) {
            set_status_header(404);
            echo lang('logo404');
            return;
        }
        $this->em->remove($existingLogo);
        try
        {   
            $this->em->flush();
            echo lang('rr_logoisunsigned');      
        }
        catch (Exception $e)
        {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            echo 'Server Error occured';        
        }
    }

    private function _submit_validate_extlogo()
    {
        $this->form_validation->set_rules('extlogourl', 'External source', 'valid_url');
        $result = $this->form_validation->run();

        return $result;
    }

    public function uploadlogos()
    {
        if (!$this->input->is_ajax_request()) {
            set_status_header(403);
            echo 'Method not allowed';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo lang('errsess');
            return;
        }
        $upload_enabled = $this->config->item('rr_logoupload');
        $upload_logos_path = trim($this->config->item('rr_logoupload_relpath'));
        $extlogourl = $this->input->post('extlogourl');
        $logofile = $this->input->post('upload');
        $providerid = $this->input->post('prvid');
        $provtype = $this->input->post('prvtype');
        if (!(!empty($providerid) &&
                is_integer($providerid) && !empty($provtype) &&
                (strcmp($provtype, 'idp') == 0 || strcmp($provtype, 'sp') == 0))) {
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid, 'type' => array('' . strtoupper($provtype) . '', 'BOTH')));
        }
        if (empty($provider)) {
            set_status_header(404);
            echo lang('rerror_provnotfound');
            return;
        }
        $this->load->library('zacl');
        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $local = $provider->getLocal();
        $locked = $provider->getLocked();
        $canEdit = (boolean) ($has_write_access && !$locked && $local);
        if (!$canEdit) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        if (!empty($extlogourl)) {
            if (!$this->_submit_validate_extlogo()) {
                set_status_header(403);
                echo lang('rr_errextlogourl');
                return;
            }
            $this->load->library('curl');
            $datafile = $this->curl->simple_get($extlogourl);
            if (!empty($datafile)) {
                $img_mimes = array(
                    'image/jpeg' => 'jpg',
                    'image/pjpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/x-png' => 'png',
                    'image/gif' => 'gif',
                );

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($datafile);
                if (!array_key_exists($mimeType, $img_mimes)) {
                    set_status_header(403);
                    echo lang('rr_errextlogourlformat');
                    return;
                }
                if (!function_exists('getimagesizefromstring')) 
                {
                    function getimagesizefromstring($string_data)
                    {
                      $uri = 'data://application/octet-stream;base64,'  . base64_encode($string_data);
                      return getimagesize($uri);
                    }
                }
                $imagesize = getimagesizefromstring($datafile);
                if (!empty($imagesize) && is_array($imagesize) && isset($imagesize['0']) && isset($imagesize['1'])) {
                    
                }
                $imagewidth = $imagesize['0'];
                $imageheight = $imagesize['1'];
                $imagelocation = $extlogourl;
                $parent = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('element' => 'UIInfo', 'provider' => $provider->getId(), 'namespace' => 'mdui', 'etype' => $provtype));
                if (empty($parent)) {
                    $parent = new models\ExtendMetadata;
                    $parent->setElement('UIInfo');
                    $parent->setProvider($provider);
                    $parent->setParent(null);
                    $parent->setNamespace('mdui');
                    $parent->setType($provtype);
                    $this->em->persist($parent);
                }
                $logo = new models\ExtendMetadata;
                $logo_attr = array('width' => $imagewidth, 'height' => $imageheight);
                $logo->setLogo($imagelocation, $provider, $parent, $logo_attr, $provtype);
                $this->em->persist($logo);
                $this->em->flush();
                echo lang('rr_logoisassigned');
                return;
            }
            else {
                set_status_header(403);
                echo $this->curl->error_string;
                return;
            }
        }
        elseif (!empty($logofile)) {
            if (empty($upload_enabled) || empty($upload_logos_path)) {
                set_status_header(403);
                echo 'Upload images feature is disabled';
                return;
            }
            if (substr($upload_logos_path, 0, 1) == '/') {
                log_message('error', 'upload_logos_path in you config must not begin with forward slash');
                set_status_header(500);
                echo 'System error ocurred';
                return;
            }
            $path = realpath(APPPATH . '../' . $upload_logos_path);
            $config = array(
                'allowed_types' => '' . $this->config->item('rr_logo_types') . '',
                'upload_path' => $path,
                'max_size' => $this->config->item('rr_logo_maxsize'),
                'max_width' => $this->config->item('rr_logo_maxwidth'),
                'max_height' => $this->config->item('rr_logo_maxheight'),
            );
            $this->load->library('upload', $config);
            if ($this->input->post('upload')) {
                if ($this->upload->do_upload()) {
                    echo lang('rr_imguploaded');
                    return;
                }
                else {
                    set_status_header(403);
                    echo $this->upload->display_errors();
                    return;
                }
            }
            else {
                set_status_header(403);
                echo "missing upload";
                return;
            }
        }
        else {
            set_status_header(500);
            echo 'Unknown error';
            return;
        }
    }

    public function provider($type = null, $id = null)
    {
        if (empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            show_error('Not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }

        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if (empty($provider)) {
            show_error(lang('rerror_provnotfound'), 404);
        }
        $this->load->library('zacl');
        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $unlocked = !($provider->getLocked());
        $local = $provider->getLocal();
        $canEdit = (boolean) ($has_write_access && $unlocked && $local);

        if ($canEdit) {
            $data['canEdit'] = TRUE;
            $data['showavailable'] = TRUE;
        }
        else {
            $data['canEdit'] = FALSE;
            $data['showavailable'] = FALSE;
        }
        $data['upload_enabled'] = $this->config->item('rr_logoupload');
        if ($canEdit) {
            $data['infomessage'] = '<b>' . lang('hfilloptions') . ':</b><br />';
            $data['infomessage'] .= '<ol><li>' . lang('hfillurlimage') . '. ' . lang('hfillurlimage2') . '. ' . lang('hfillurlimage3') . '</li>';
            if (!empty($data['upload_enabled'])) {
                $data['infomessage'] .= '<li>Upload image to local storage and then select it to assign to your provider<br /> ';
                $data['infomessage'] .= lang('maxupimgdim') . ': ' . $this->config->item('rr_logo_maxwidth') . 'x' . $this->config->item('rr_logo_maxheight') . '<br />' . lang('rr_uploadinformat') . ': png. Then you need to assign uploaded logo</li>';
            }

            $data['infomessage'] .= '<li>Assign logo from logos available in local storage</li>';
            $data['infomessage'] .= '</ol>';
        }
        $attributes = array('class' => 'span-16', 'id' => 'assignedlogos');
        $target_url = base_url() . 'manage/logomngmt/unsign/' . $type . '/' . $id;
        $data['targeturl'] = $target_url;
        $existing_logos = $this->em->getRepository("models\ExtendMetadata")->findBy(array('etype' => $type, 'namespace' => 'mdui', 'element' => 'Logo', 'provider' => $id));

        $count_existing_logos = count($existing_logos);
        if ($count_existing_logos > 0) {
            $form1 = '<span>';
            $form1 .= form_open(base_url() . 'manage/logomngmt/unsign/' . $type . '/' . $id, $attributes);
            $form1 .= $this->logo->displayCurrentInGridForm($provider, $type, $canEdit);
            $form1 .= '<div class="buttons" id="unsignlogosbtn" >';
            $form1 .= '<button name="remove" type="submit" value="Remove selected" class="resetbutton reseticon alert">' . lang('rr_unsignselectedlogo') . '</button> ';
            $form1 .= '</div>';
            $form1 .= form_close();
            $form1 .= '</span>';
            $data['assignedlogos'] = $form1;
        }
        $data['addnewlogobtn'] = true;
        $data['content_view'] = 'manage/logomngmt_view';
        $data['sub'] = lang('assignedlogoslistfor') . ' ';
        $lang = MY_Controller::getLang();
        $displayname = $provider->getNameToWebInLang($lang,$type);
        if(empty($displayname))
        {
            $displayname = $provider->getEntityId();
        }

        $data['titlepage'] = '<a href="'.base_url().'providers/detail/show/'.$provider->getId().'">'.$displayname.'</a>';
        $data['subtitlepage'] = lang('rr_logosmngt');
        $data['provider_detail']['name'] = $provider->getName();
        $data['provider_detail']['id'] = $provider->getId();
        $data['provider_detail']['entityid'] = $provider->getEntityId();
        $data['provider_detail']['type'] = $type;
        $this->load->view('page', $data);
    }

}
