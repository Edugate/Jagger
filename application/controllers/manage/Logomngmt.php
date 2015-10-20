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

    public function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('logo');
        $this->load->library('form_validation');
    }

    public function getAssignedLogosInGrid($type = null, $id = null) {
        if (!$this->input->is_ajax_request() || empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            set_status_header(403);
            echo lang('error403');
            return;
        }
        if (!$this->jauth->isLoggedIn()) {
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
        $canEdit = (boolean)($has_write_access && $unlocked && $local);
        $attributes = array('class' => 'span-16', 'id' => 'assignedlogos');
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
        } else {

            echo 'No assigned Logos';
        }
    }

    public function getAvailableLogosInGrid($type = null, $id = null) {
        if ($this->input->is_ajax_request() && $this->jauth->isLoggedIn() && !empty($type) && !empty($id)) {
            $this->load->library('logo');
            $attributes = array('class' => 'span-20', 'id' => 'availablelogos');
            $availableImages = $this->logo->displayAvailableInGridForm('filename', 3);

            $form1 = form_open(base_url() . 'manage/logomngmt/assign/' . $type . '/' . $id, $attributes);
            $form1 .= form_fieldset('' . lang('rr_selectimagetoassign') . '');
            if (!empty($availableImages)) {
                $form1 .= '<div class="buttons" style="display: none"><button name="submit" type="submit" value="submit" class="savebutton saveicon">
                      ' . lang('rr_btn_assignselecetedlogo') . '</button></div>';
                $form1 .= $availableImages;
            } else {
                $form1 .= '<div class="alert">' . lang('rr_nolocalimages') . '</div>';
            }
            $form1 .= form_fieldset_close();
            $form1 .= form_close();
            echo $form1;
        } else {
            set_status_header(403);
            echo lang('error403');
            return;
        }
    }

    public function assign($type = null, $id = null) {
        if (!$this->input->is_ajax_request() || ($_SERVER['REQUEST_METHOD'] !== 'POST') || empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0) || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output(lang('error403'));
        }
        /**
         * @var models\Provider $provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if ($provider === null) {
            return $this->output->set_status_header(404)->set_output(lang('rerror_provnotfound'));
        }
        $this->load->library('zacl');
        $canEdit = (boolean)($this->zacl->check_acl($provider->getId(), 'write', 'entity', '') && !($provider->getLocked()) && $provider->getLocal());
        $logopost = $this->input->post('filename');
        if (!$canEdit || empty($logopost)) {
            return $this->output->set_status_header(403)->set_output(lang('error403'));
        }
        $explodedLogopost = explode('_size_', $logopost);
        if (count($explodedLogopost) != 2) {
            log_message('error', 'incorrect  value given:' . $this->input->post('filename') . ' , must be in format: filename_size_widthxheight');
            return $this->output->set_status_header(403)->set_output(lang('error403') . ' : ' . lang('error_incorrectinput'));
        }
        $new_logoname = $explodedLogopost['0'];
        $original_sizes = explode('x', $explodedLogopost['1']);
        if (empty($new_logoname)) {
            return $this->output->set_status_header(403)->set_output(lang('error403'));
        }
        $logo_attr = array(
            'width' => $this->input->post('width'),
            'height' => $this->input->post('height')
        );

        if (empty($logo_attr['width']) && empty($logo_attr['height'])) {
            $logo_attr = array(
                'width' => $original_sizes['0'],
                'height' => $original_sizes['1']
            );
        }
        /**
         * @var models\ExtendMetadata $parent
         */
        $parent = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('element' => 'UIInfo', 'provider' => $provider->getId(), 'namespace' => 'mdui', 'etype' => $type));
        if ($parent === null) {
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
        return $this->output->set_status_header(200)->set_output(lang('rr_logoisassigned'));
    }

    public function unsign($type = null, $id = null) {
        if (!$this->input->is_ajax_request() || ($_SERVER['REQUEST_METHOD'] !== 'POST') || !$this->jauth->isLoggedIn()) {
            $s = 403;
            $msg = lang('error403');
        } elseif (empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            $s = 404;
            $msg = lang('error403');
        }
        if (!empty($s)) {
            return $this->output->set_status_header($s)->set_output($msg);
        }
        /**
         * @var models\Provider $provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if ($provider === null) {
            return $this->output->set_status_header(404)->set_output(lang('error404'));
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $unlocked = !($provider->getLocked());
        $local = $provider->getLocal();
        $canEdit = (boolean)($hasWriteAccess && $unlocked && $local);
        if (!$canEdit) {
            return $this->output->set_status_header(403)->set_output(lang('error403'));
        }
        $logoidPost = $this->input->post('logoid');
        if (empty($logoidPost) || !ctype_digit($logoidPost)) {
            return $this->output->set_status_header(403)->set_output(lang('error403'));
        }
        /**
         * @var models\ExtendMetadata $existingLogo
         */
        $existingLogo = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('id' => $logoidPost, 'etype' => $type, 'namespace' => 'mdui', 'element' => 'Logo', 'provider' => $id));
        if ($existingLogo === null) {
            return $this->output->set_status_header(404)->set_output(lang('logo404'));
        }
        $this->em->remove($existingLogo);
        try {
            $this->em->flush();
            return $this->output->set_status_header(200)->set_output(lang('rr_logoisunsigned'));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Server Error occured');
        }
    }

    private function _submit_validate_extlogo() {
        $this->form_validation->set_rules('extlogourl', 'External source', 'valid_url');
        $result = $this->form_validation->run();
        return $result;
    }

    public function uploadlogos() {
        if (!$this->input->is_ajax_request()|| !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        $upload_enabled = $this->config->item('rr_logoupload');
        $upload_logos_path = trim($this->config->item('rr_logoupload_relpath'));
        $extlogourl = $this->input->post('extlogourl');
        $logofile = $this->input->post('upload');
        $providerid = $this->input->post('prvid');
        $provtype = $this->input->post('prvtype');
        /**
         * @var models\Provider $provider
         */
        $provider = null;
        if (!(!empty($providerid) &&
            is_integer($providerid) && !empty($provtype) &&
            (strcmp($provtype, 'idp') == 0 || strcmp($provtype, 'sp') == 0))
        ) {
            $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid, 'type' => array('' . strtoupper($provtype) . '', 'BOTH')));
        }
        if ($provider === null) {
            return $this->output->set_status_header(404)->set_output(lang('rerror_provnotfound'));
        }
        $this->load->library('zacl');
        if ($upload_enabled !== true || empty($upload_logos_path)) {
            return $this->output->set_status_header(403)->set_output('Upload images feature is disabled');
        }
        $canProceed = (boolean)($this->zacl->check_acl($provider->getId(), 'write', 'entity', '') && !$provider->getLocked() && $provider->getLocal());
        if ($canProceed !== true) {
            return $this->output->set_status_header(403)->set_output(lang('error403'));
        }
        if (!empty($extlogourl)) {
            if (!$this->_submit_validate_extlogo()) {
                return $this->output->set_status_header(403)->set_output(lang('rr_errextlogourl'));
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
                    return $this->output->set_status_header(403)->set_output(lang('rr_errextlogourlformat'));
                }
                if (!function_exists('getimagesizefromstring')) {
                    function getimagesizefromstring($string_data) {
                        $uri = 'data://application/octet-stream;base64,' . base64_encode($string_data);
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
                return $this->output->set_status_header(200)->set_output(lang('rr_logoisassigned'));
            } else {
                return $this->output->set_status_header(403)->set_output($this->curl->error_string);
            }
        } elseif (!empty($logofile)) {

            if (substr($upload_logos_path, 0, 1) == '/') {
                log_message('error', 'upload_logos_path in you config must not begin with forward slash');
                return $this->output->set_status_header(500)->set_output('System error ocurred');
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
                    return $this->output->set_status_header(200)->set_output(lang('rr_imguploaded'));
                } else {
                    return $this->output->set_status_header(403)->set_output($this->upload->display_errors());
                }
            } else {
                set_status_header(403);
                echo "missing upload";
                return;
            }
        } else {
            set_status_header(500);
            echo 'Unknown error';
            return;
        }
    }

    public function provider($type = null, $id = null) {
        if (empty($type) || empty($id) || !ctype_digit($id) || !(strcmp($type, 'idp') == 0 || strcmp($type, 'sp') == 0)) {
            show_error('Not found', 404);
        }
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }

        /**
         * @var models\Provider $provider
         */
        $provider = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id, 'type' => array('BOTH', '' . strtoupper($type) . '')));
        if ($provider === null) {
            show_error(lang('rerror_provnotfound'), 404);
        }
        $this->load->library('zacl');
        $canEdit = (boolean)($this->zacl->check_acl($provider->getId(), 'write', 'entity', '') && !($provider->getLocked()) && $provider->getLocal());

        if ($canEdit) {
            $data['canEdit'] = true;
            $data['showavailable'] = true;
        } else {
            $data['canEdit'] = false;
            $data['showavailable'] = false;
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
        $attributes = array('id' => 'assignedlogos');
        $data['targeturl'] = base_url('manage/logomngmt/unsign/' . $type . '/' . $id);
        $existing_logos = $this->em->getRepository("models\ExtendMetadata")->findBy(array('etype' => $type, 'namespace' => 'mdui', 'element' => 'Logo', 'provider' => $id));
        if (count($existing_logos) > 0) {
            $data['assignedlogos'] = '<span>' .
                form_open(base_url() . 'manage/logomngmt/unsign/' . $type . '/' . $id, $attributes) .
                $this->logo->displayCurrentInGridForm($provider, $type, $canEdit) .
                '<div class="buttons" id="unsignlogosbtn" >' .
                '<button name="remove" type="submit" value="Remove selected" class="resetbutton reseticon alert">' . lang('rr_unsignselectedlogo') . '</button> ' .
                '</div>' .
                form_close() . '</span>';
        }
        $data['addnewlogobtn'] = true;
        $data['content_view'] = 'manage/logomngmt_view';
        $data['sub'] = lang('assignedlogoslistfor') . ' ';
        $myLang = MY_Controller::getLang();
        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $provider->getId() . '">' . $provider->getNameToWebInLang($myLang, $type) . '</a>';
        $data['subtitlepage'] = lang('rr_logosmngt');
        $data['provider_detail']['name'] = $provider->getName();
        $data['provider_detail']['id'] = $provider->getId();
        $data['provider_detail']['entityid'] = $provider->getEntityId();
        $data['provider_detail']['type'] = $type;
        $this->load->view('page', $data);
    }

}
