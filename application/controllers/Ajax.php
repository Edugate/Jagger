<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Ajax extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();
        $this->load->library(array('form_validation','j_auth','curl'));
    }

    public function consentCookies()
    {
        if ($this->input->is_ajax_request())
        {
            $lc = array(
                'name' => 'cookieAccept',
                'value' => 'accepted',
                'secure' => TRUE,
                'expire' => '2600000',
            );
            $this->input->set_cookie($lc);
            return true;
        }
    }

    public function getproviders()
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
            echo 'denied';
            return;
        }

        $p = new models\Providers();
        $providers = $p->getLocalIdsEntities();
        $result = array();
        foreach ($providers as $k)
        {
            $result[] = array('key' => $k['id'], 'value' => $k['entityid'], 'label' => $k['name']);
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function checklogourl()
    {
        if (!($this->input->is_ajax_request() && $this->j_auth->logged_in()))
        {
            set_status_header(403);
            echo 'denied';
            return;
        }   
        $result = array();
        $this->form_validation->set_rules('logourl', 'URL Logo', 'trim|required|min_length[5]|max_length[500]|no_white_spaces|valid_url_ssl');
        $isvalid = $this->form_validation->run();
        $v_errors = validation_errors('<span>', '</span>');
        if (!$isvalid)
        {
            $result['error'] = $v_errors;
            $this->output->set_content_type('application/json')->set_output(json_encode($result));
            return;
        }
        $logourl = trim($this->input->post('logourl'));
        $configlogossl = $this->config->item('addlogocheckssl');
        if (isset($configlogossl) && $configlogossl === FALSE)
        {
            $sslvalidate = FALSE;
            $sslvalidatehost = 0;
        }
        else
        {
            $sslvalidate = TRUE;
            $sslvalidatehost = 2;
        }
        $image = $this->curl->simple_get('' . $logourl . '', array(), array(
            CURLOPT_SSL_VERIFYPEER => $sslvalidate,
            CURLOPT_SSL_VERIFYHOST => $sslvalidatehost,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_BUFFERSIZE => 128,
            CURLOPT_NOPROGRESS => FALSE,
            CURLOPT_PROGRESSFUNCTION => function($DownloadSize, $Downloaded, $UploadSize, $Uploaded) {
                return ($Downloaded > (1000 * 1024)) ? 1 : 0;
            }
        ));

        if (empty($image))
        {
            $result['error'] = $this->curl->error_string;
            echo json_encode($result);
            return;
        }
        $img_mimes = array(
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/gif',
        );
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($image);
        if (!in_array($mimeType, $img_mimes))
        {
            $result['error'] = 'Incorrect mime type ' . $mimeType;
            echo json_encode($result);
            return;
        }
        if (!function_exists('getimagesizefromstring'))
        {
            $uri = 'data://application/octet-stream;base64,' . base64_encode($image);
            $image_details = getimagesize($uri);
        }
        else
        {
            $image_details = getimagesizefromstring($image);
        }
        $result['data'] = array(
            'width' => $image_details[0],
            'height' => $image_details[1],
            'mime' => $mimeType,
            'url' => $logourl,
        );
        echo json_encode($result);

        return;
    }

    public function getfeds()
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
            echo 'denied';
            return;
        }
        $p = new models\Federations();
        $feds = $p->getAllIdNames();
        $this->output->set_content_type('application/json')->set_output(json_encode($feds));

    }

    public function changelanguage($language)
    {
        if ($this->input->is_ajax_request())
        {
            $language = substr($language, 0, 7);
            
            $langs = MY_Controller::guiLangs();
            
            if (array_key_exists($language, $langs))
            {
                log_message('info',__METHOD__.'changed gui lang to:'.$language);
                $cookie_value = $language;
            }
            else
            {
                log_message('warning',__METHOD__.' '.$language.' not found in allowed langs, setting english');
                $cookie_value = 'english';
            }
            $lang_cookie = array(
                'name' => 'rrlang',
                'value' => $cookie_value,
                'expire' => '2600000',
                'secure' => TRUE
            );
            $this->input->set_cookie($lang_cookie);
            return true;
        }
        else
        {
            log_message('debug', 'noajax');
        }
    }

    public function fedcat($id = null)
    {
        if (!$this->input->is_ajax_request())
        {
            show_error('invalid method', 403);
        }
        if (!empty($id) && !is_numeric($id))
        {
            show_error('not found', 404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin)
        {
            show_error('permission denied', 403);
        }
        if (!empty($id))
        {
            $fedcat = $this->em->getRepository("models\FederationCategory")->findOneBy(array('id' => $id));
            if (empty($fedcat))
            {
                show_error('Federation category not found', 404);
            }
            $federations = $fedcat->getFederations();
        }
        else
        {
            $federations = $this->em->getRepository("models\Federation")->findAll();
        }

        $result = array();
        $imgtoggle = '<img class="toggle" src="' . base_url() . 'images/icons/control-270.png" />';
        foreach ($federations as $v)
        {
            $lbs = '';
            if ($v->getPublic())
            {
                $lbs .=makeLabel('public', '', lang('rr_fed_public')) . ' ';
            }
            else
            {
                $lbs .=makeLabel('notpublic', '', lang('rr_fed_notpublic')) . ' ';
            }
            if ($v->getActive())
            {
                $lbs .=makeLabel('active', '', lang('rr_fed_active')) . ' ';
            }
            else
            {
                $lbs .=makeLabel('disabled', '', lang('rr_fed_inactive')) . ' ';
            }
            if ($v->getLocal())
            {
                $lbs .=makeLabel('local', '', lang('rr_fed_local')) . ' ';
            }
            else
            {
                $lbs .=makeLabel('external', '', lang('rr_fed_external')) . ' ';
            }
            $members = ' <a href="' . base_url() . 'federations/manage/showmembers/' . $v->getId() . '" class="fmembers" id="' . $v->getId() . '">' . $imgtoggle . '</a>';
            $result[] = array(
                'name' => anchor(base_url() . "federations/manage/show/" . base64url_encode($v->getName()), $v->getName()),
                'urn' => $v->getUrn(),
                'desc' => $v->getDescription(),
                'members' => $members,
                'labels' => $lbs,
            );
        }
        echo json_encode($result);
    }

    public function showhelpstatus($n = null)
    {
        if (!$this->input->is_ajax_request())
        {
            show_error('denied', 403);
        }
        if (empty($n))
        {
            set_status_header(403);
            echo 'empty param';
            return;
        }

        $char = substr($n, 0, 1);
        if (!($char === 'y' || $char === 'n'))
        {
            set_status_header(403);
            echo 'incorrect param';
            return;
        }
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin)
        {
            $username = $this->j_auth->current_user();
            $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
            if ($char === 'y')
            {
                $u->setShowHelp(true);
                $this->session->set_userdata('showhelp', TRUE);
                echo "set showhelp to true";
            }
            else
            {
                $u->setShowHelp(false);
                $this->session->set_userdata('showhelp', FALSE);
                echo "set showhelp to false";
            }
            $this->em->persist($u);
            try
            {
                $this->em->flush();
            }
            catch (Exception $e)
            {
                log_message('error', __METHOD__ . ' ' . $e);
                set_status_header(500);
                echo 'problem with saving in db';
                return;
            }
            return "OK";
        }
        set_status_header(403);
        echo "permission denied";
        return;
    }

    public function bookentity($id)
    {
        if ($this->input->is_ajax_request())
        {
            log_message('debug', 'bookentity: got ajax request');
            $loggedin = $this->j_auth->logged_in();
            if ($loggedin)
            {
                $lang = MY_Controller::getLang();
                log_message('debug', 'bookentity: loggedin');
                $username = $this->j_auth->current_user();
                $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
                $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
                if (!empty($u) && !empty($ent))
                {
                    $enttype = $ent->getType();
                    $entname = $ent->getNameToWebInLang($lang, $enttype);
                    $entid = $ent->getId();
                    $entityid = $ent->getEntityId();
                    $u->addEntityToBookmark($entid, $entname, $enttype, $entityid);
                    $this->em->persist($u);
                    $userprefs = $u->getUserpref();
                    $this->session->set_userdata(array('board' => $userprefs['board']));
                    $this->em->flush();
                    echo 'added';
                }
            }
            else
            {
                log_message('debug', 'bookentity: not  loggedin');
            }
        }
    }

    public function delbookentity($id)
    {
        if ($this->input->is_ajax_request())
        {
            $loggedin = $this->j_auth->logged_in();
            if ($loggedin && is_numeric($id))
            {
                $username = $this->j_auth->current_user();
                $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
                if (!empty($u))
                {
                    $u->delEntityFromBookmark($id);
                    $this->em->persist($u);
                    $userprefs = $u->getUserpref();
                    $this->session->set_userdata(array('board' => $userprefs['board']));
                    $this->em->flush();
                    echo 'deleted';
                }
            }
        }
    }

    public function bookfed($id)
    {
        if ($this->input->is_ajax_request())
        {
            log_message('debug', 'bookfed: got ajax request');
            $loggedin = $this->j_auth->logged_in();
            if ($loggedin)
            {
                log_message('debug', 'bookfed: loggedin');
                $username = $this->j_auth->current_user();
                $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
                $ent = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $id));
                if (!empty($u) && !empty($ent))
                {
                    $fedid = $ent->getId();
                    $fedname = $ent->getName();
                    $fedencoded = base64url_encode($fedname);

                    $u->addFedToBookmark($fedid, $fedname, $fedencoded);
                    $this->em->persist($u);
                    $userprefs = $u->getUserpref();
                    $this->session->set_userdata(array('board' => $userprefs['board']));
                    $this->em->flush();
                }
            }
            else
            {
                log_message('debug', 'bookentity: not  loggedin');
            }
        }
    }

    public function delbookfed($id)
    {
        if ($this->input->is_ajax_request())
        {
            $loggedin = $this->j_auth->logged_in();
            if ($loggedin && is_numeric($id))
            {
                $username = $this->j_auth->current_user();
                $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
                if (!empty($u))
                {
                    $u->delFedFromBookmark($id);
                    $this->em->persist($u);
                    $userprefs = $u->getUserpref();
                    $this->session->set_userdata(array('board' => $userprefs['board']));
                    $this->em->flush();
                }
            }
        }
    }

}

?>
