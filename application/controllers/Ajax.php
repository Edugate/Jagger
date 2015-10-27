<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


/**
 * @property Curl $curl;
 */
class Ajax extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();
        $this->load->library(array('form_validation', 'curl'));
    }

    /**
     * @return bool
     */
    public function consentCookies()
    {
        if ($this->input->is_ajax_request()) {
            $cookieVal = array(
                'name' => 'cookieAccept',
                'value' => 'accepted',
                'secure' => TRUE,
                'expire' => '2600000',
            );
            $this->input->set_cookie($cookieVal);
            return true;
        }
        return false;

    }

    public function getproviders()
    {
        if (!($this->input->is_ajax_request() && $this->jauth->isLoggedIn())) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $tmpProviders = new models\Providers();

        $providers = $tmpProviders->getLocalIdsEntities();
        $result = array();
        foreach ($providers as $k) {
            $result[] = array('key' => $k['id'], 'value' => $k['entityid'], 'label' => $k['name']);
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function checklogourl()
    {
        if (!($this->input->is_ajax_request())) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $result = array();
        $this->form_validation->set_rules('logourl', 'URL Logo', 'trim|required|min_length[5]|max_length[500]|no_white_spaces|valid_url_ssl');
        $isvalid = $this->form_validation->run();
        $vErrors = validation_errors('<span>', '</span>');
        if (!$isvalid) {
            $result['error'] = $vErrors;
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));
        }
        $logourl = trim($this->input->post('logourl'));
        $configlogossl = $this->config->item('addlogocheckssl');
        $sslvalidate = true;
        $sslvalidatehost = 2;
        if ($configlogossl === false) {
            $sslvalidate = false;
            $sslvalidatehost = 0;
        }

        $image = $this->curl->simple_get('' . $logourl . '', array(), array(
            CURLOPT_SSL_VERIFYPEER => $sslvalidate,
            CURLOPT_SSL_VERIFYHOST => $sslvalidatehost,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_BUFFERSIZE => 128,
            CURLOPT_NOPROGRESS => FALSE,
            CURLOPT_PROGRESSFUNCTION => function ($DownloadSize, $Downloaded, $UploadSize, $Uploaded) {
                return ($Downloaded > (1000 * 1024)) ? 1 : 0;
            }
        ));

        if (empty($image)) {
            $result['error'] = $this->curl->error_string;
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));
        }
        $imgMimes = array(
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/gif',
        );
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($image);
        if (!in_array($mimeType, $imgMimes, true)) {
            $result['error'] = 'Incorrect mime type ' . $mimeType;
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));
        }
        if (!function_exists('getimagesizefromstring')) {
            $uri = 'data://application/octet-stream;base64,' . base64_encode($image);
            $imageDetails = getimagesize($uri);
        } else {
            $imageDetails = getimagesizefromstring($image);
        }

        $result['data'] = array(
            'width' => $imageDetails[0],
            'height' => $imageDetails[1],
            'mime' => $mimeType,
            'url' => $logourl,
            'raw' => 'data:'.$mimeType.';base64,'.base64_encode($image).''
        );
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function getfeds()
    {
        if (!$this->jauth->loggedInAndAjax()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $tmpFeds = new models\Federations();
        $feds = $tmpFeds->getAllIdNames();
        return $this->output->set_content_type('application/json')->set_output(json_encode($feds));

    }

    public function changelanguage($languageIn = null)
    {
        if ($languageIn === null || !$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $language = substr($languageIn, 0, 7);

        $langs = MY_Controller::guiLangs();

        if (array_key_exists($language, $langs)) {
            log_message('info', __METHOD__ . 'changed gui lang to:' . $language);
            $cookieValue = $language;
        } else {
            log_message('warning', __METHOD__ . ' ' . $language . ' not found in allowed langs, setting english');
            $cookieValue = 'english';
        }
        $langCookie = array(
            'name' => 'rrlang',
            'value' => $cookieValue,
            'expire' => '2600000',
            'secure' => TRUE
        );
        $this->input->set_cookie($langCookie);
        return $this->output->set_status_header(200)->set_output('OK');
    }

    public function fedcat($fedcatId = null)
    {
        if (!$this->jauth->loggedInAndAjax()) {
            return $this->output->set_status_header(403)->set_output('Invalid method');

        }

        if ($fedcatId !== null && !ctype_digit($fedcatId)) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }

        /**
         * @var $fedcat models\FederationCategory
         * @var $federations models\Federation[]
         */
        if ($fedcatId !== null) {
            $fedcat = $this->em->getRepository("models\FederationCategory")->findOneBy(array('id' => $fedcatId));
            if ($fedcat === null) {
                return $this->output->set_status_header(404)->set_output('Federation category not found');
            }
            $federations = $fedcat->getFederations();
        } else {
            $federations = $this->em->getRepository("models\Federation")->findAll();
        }

        $result = array();
        $imgtoggle = '<img class="toggle" src="' . base_url() . 'images/icons/control-270.png" />';
        foreach ($federations as $v) {
            $lbs = array(
                'pub' => makeLabel('notpublic', '', lang('rr_fed_notpublic')),
                'act' => makeLabel('disabled', '', lang('rr_fed_inactive')),

            );
            if ($v->getPublic()) {
                $lbs['pub'] = makeLabel('public', '', lang('rr_fed_public')) . ' ';
            }
            if ($v->getActive()) {
                $lbs['act'] = makeLabel('active', '', lang('rr_fed_active')) . ' ';
            }
         
            $members = ' <a href="' . base_url() . 'federations/manage/showmembers/' . $v->getId() . '" class="fmembers" id="' . $v->getId() . '">' . $imgtoggle . '</a>';
            $result[] = array(
                'name' => anchor(base_url('federations/manage/show/' . base64url_encode($v->getName() . '')), $v->getName()),
                'urn' => $v->getUrn(),
                'desc' => $v->getDescription(),
                'members' => $members,
                'labels' => implode(' ', $lbs),
            );
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function showhelpstatus($str = null)
    {
        if (!$this->jauth->loggedInAndAjax()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        if ($str === null) {
            return $this->output->set_status_header(403)->set_output('Empty param');
        }

        $char = substr($str, 0, 1);
        if (!($char === 'y' || $char === 'n')) {
            return $this->output->set_status_header(403)->set_output('Incorrect param');
        }

        $username = $this->jauth->getLoggedinUsername();
        /**
         * @var $user models\User
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        $toShowHelp = false;
        if ($char === 'y') {
            $toShowHelp = true;
        }
        $user->setShowHelp($toShowHelp);
        $this->session->set_userdata('showhelp', $toShowHelp);
        $this->em->persist($user);
        try {
            $this->em->flush();
            return $this->output->set_status_header(200)->set_output('OK');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('problem with saving in db');
        }

    }

    public function bookmarkentity($entID = null, $action = null)
    {
        if ($action === null || !ctype_digit($entID)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        if (!$this->jauth->loggedInAndAjax()) {
            return $this->output->set_status_header(401)->set_output('Access Denied');
        }
        $myLang = MY_Controller::getLang();
        $username = $this->jauth->getLoggedinUsername();
        /**
         * @var $user models\User
         */
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $username . ''));
        if ($user === null) {
            log_message('error', __METHOD__ . ' username:' . $username . ' loggedin but user not found in db');
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        if ($action === 'add') {
            /**
             * @var $ent models\Provider
             */
            $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $entID));
            if ($ent === null) {
                return $this->output->set_status_header(404)->set_output('Provider not found');
            }
            $user->addEntityToBookmark($ent->getId(), $ent->getNameToWebInLang($myLang, $ent->getType()), $ent->getType(), $ent->getEntityId());
            $this->em->persist($user);
            $userprefs = $user->getUserpref();
            $this->session->set_userdata(array('board' => $userprefs['board']));
        } elseif ($action === 'del') {
            $user->delEntityFromBookmark($entID);
            $this->em->persist($user);
            $userprefs = $user->getUserpref();
            $this->session->set_userdata(array('board' => $userprefs['board']));
        } else {
            return $this->output->set_status_header(403)->set_output('Access Denied - unknown action');
        }
        try {
            $this->em->flush();
            return $this->output->set_status_header(200)->set_output('ok');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' : ' . $e);
            return $this->output->set_status_header(500)->set_output('Database Error');
        }

    }

    public function bookfed($entID = null, $action = null)
    {
        if ($action === null || !ctype_digit($entID) || !$this->jauth->loggedInAndAjax()) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $username = $this->jauth->getLoggedinUsername();
        /**
         * @var $user models\User
         */
        try {
            $user = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $username . ''));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }
        if ($user === null) {
            log_message('error', __METHOD__ . ' username:' . $username . ' loggedin but user not found in db');
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        if ($action === 'add') {
            /**
             * @var $fed models\Federation
             */
            $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $entID));
            if ($fed === null) {
                return $this->output->set_status_header(404)->set_output('Federation not found');
            }
            $user->addFedToBookmark($fed->getId(), $fed->getName(), base64url_encode($fed->getName()));
        } elseif ($action === 'del') {
            $user->delFedFromBookmark($entID);
        } else {
            return $this->output->set_status_header(403)->set_output('unknown action');
        }

        $this->em->persist($user);
        $userprefs = $user->getUserpref();
        $this->session->set_userdata(array('board' => $userprefs['board']));

        try {
            $this->em->flush();
            return $this->output->set_status_header(200)->set_output('ok');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal Server Error');
        }

    }

}

