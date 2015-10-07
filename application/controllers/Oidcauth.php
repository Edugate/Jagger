<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 * @link      https://github.com/Edugate/Jagger
 */
class Oidcauth extends MY_Controller
{
    private $oidcEnabled;
    private $oidcOps;

    public function __construct() {
        parent::__construct();
        $this->oidcEnabled = $this->config->item('oidc_enabled');
        $this->oidcOps = $this->config->item('oidc_ops');
    }

    public function authn() {

        try {
            $this->checkGlobal();
        } catch (Exception $e) {
            return $this->output->set_status_header(403)->set_output($e->getMessage());
        }
        if ($this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Already authenticated');
        }
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Method not allowed');
        }

        $openProvider = $this->input->post('op', true);
        if (strlen($openProvider) && array_key_exists($openProvider, $this->oidcOps)) {
            $provider = $this->oidcOps[$openProvider];
            $client = new Jagger\oidc\Client($provider['openid_configuration']);
            $client->addScope($provider['scopes']);
            $client->setProviderURL($openProvider);
            $client->setClientID($provider['client_id']);
            $client->setClientSecret($provider['client_secret']);
            $client->setRedirectURL(base_url('oidcauth/callback'));
            $client->setStateSession();
            $client->addAuthzParams($provider['authzparams']);
            $authzRedirectUrl = $client->generateAuthzRequest();
            return $this->output->set_header('application/json')->set_status_header(200)->set_output(json_encode(array('redirect' => $authzRedirectUrl)));
        } else {
            return $this->output->set_status_header(403)->set_output('Missing');
        }


    }

    public function callback() {
        try {
            $this->checkGlobal();
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            return $this->load->view('page', array('content_view' => 'error_message', 'error_message' => html_escape($error_message)));
        }
        if ($this->jauth->isLoggedIn()) {
            $error_message = 'Already authenticated';
            return $this->load->view('page', array('content_view' => 'error_message', 'error_message' => html_escape($error_message)));
        }

        $sessIssuer = $this->session->userdata('joidc_issuer');
        if ($sessIssuer !== null && array_key_exists($sessIssuer, $this->oidcOps)) {
            $provider = $this->oidcOps[$sessIssuer];
        } else {
            return $this->output->set_status_header(403)->set_output('Missing');
        }


        $client = new Jagger\oidc\Client($provider['openid_configuration']);
        $client->addScope($provider['scopes']);
        $client->setProviderURL($sessIssuer);
        $client->setClientID($provider['client_id']);
        $client->setClientSecret($provider['client_secret']);
        $client->setRedirectURL(base_url('oidcauth/callback'));


        try {
            $claims = $client->authenticate();
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            return $this->load->view('page', array('content_view' => 'error_message', 'error_message' => html_escape($error_message)));
        }

        if (!isset($claims['sub'])) {
            $error_message = 'Missing required claim "sub" from Authorization Server';
            return $this->load->view('page', array('content_view' => 'error_message', 'error_message' => html_escape($error_message)));
        }
        log_message('debug','CLAIMS: '.serialize($claims));
        $username = (string)$claims['sub'] . '@' . $claims['iss'];
        $fname = null;
        $sname = null;
        $email = null;
        if (isset($provider['mapping_claims']['fname']) && isset($claims[$provider['mapping_claims']['fname']])) {
            $fname = $claims[$provider['mapping_claims']['fname']];
        }
        if (isset($provider['mapping_claims']['sname']) && isset($claims[$provider['mapping_claims']['sname']])) {
            $sname = $claims[$provider['mapping_claims']['sname']];
        }

        if (isset($provider['mapping_claims']['email']) && isset($claims[$provider['mapping_claims']['email']])) {
            $email = $claims[$provider['mapping_claims']['email']];
        }


        /**
         * @var models\User $user
         */
        $user = $this->em->getRepository('models\User')->findOneBy(array('username' => $username));

        if ($user !== null) {
            $can_access = (bool)($user->isEnabled() && $user->getFederated());
            if (!$can_access) {
                show_error(lang('rerror_youraccountdisorfeddis'), 403);
            }
            $session_data = $user->getBasic();
            $userprefs = $user->getUserpref();
            $this->session->set_userdata('username', '' . $session_data['username'] . '');
            $this->session->set_userdata('user_id', '' . $session_data['user_id'] . '');
            $this->session->set_userdata('authntype', 'federated');
            $systemTwoFactor = $this->config->item('twofactorauthn');
            if (!empty($systemTwoFactor) && $systemTwoFactor === true) {
                $userSecondFactor = $user->getSecondFactor();
                $systemAllowed2Factors = $this->config->item('2fengines');
                if (empty($systemAllowed2Factors) || !is_array($systemAllowed2Factors)) {
                    $systemAllowed2Factors = array();
                }
                if (!empty($userSecondFactor) && in_array($userSecondFactor, $systemAllowed2Factors)) {

                    $this->session->set_userdata('partiallogged', 1);
                    $this->session->set_userdata('secondfactor', trim($userSecondFactor));
                    $this->session->set_userdata('twofactor', 1);
                    $this->session->set_userdata('logged', 0);
                } else {

                    $this->session->set_userdata('logged', 1);
                }
            } else {

                $this->session->set_userdata('logged', 1);
            }
            $this->session->set_userdata($session_data);
            if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
                $this->session->set_userdata('board', $userprefs['board']);
            }
            if (!empty($timeoffset) && is_numeric($timeoffset)) {

                $this->session->set_userdata('timeoffset', (int)$timeoffset);
            }

            $islogged = $this->session->userdata('logged');
            if (!empty($islogged)) {

                $ip = $this->input->ip_address();
                if ($email !== null) {
                    $user->setEmail($email);
                }
                $user->setIP($ip);
                $user->updated();
                $this->em->persist($user);
                $this->load->library('tracker');
                $track_details = 'Authn from ' . $ip . '  with oidc';
                $this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
                $this->em->flush();

            }
        } else {


            $canAutoRegister = $this->config->item('autoregister_federated');
            if ($email === null) {
                log_message('warning', __METHOD__ . ' User hasnt provided email attr during oidc access');
                show_error(lang('error_noemail'), 403);
            }

            if (!$canAutoRegister) {
                log_message('error', 'User authorization failed: ' . $username . ' doesnt exist in RR');

                $fedidentity = array('fedusername' => $username, 'fedfname' => $fname, 'fedsname' => $sname, 'fedemail' => $email);
                $this->session->set_userdata(array('fedidentity' => $fedidentity));
                $data['content_view'] = 'feduserregister_view';
                return $this->load->view('page', $data);
            } else {

                $attrs = array('username' => $username, 'mail' => $email, 'fname' => $fname, 'sname' => $sname);

                try{
                    $nuser = $this->jauth->registerUser($attrs,'oidc',null);
                    $this->em->persist($nuser);
                }
                catch(Exception $e){
                    log_message('error',__METHOD__.' '.$e);
                    show_error(html_escape($e->getMessage()), 403);
                }
                try{
                    $this->em->flush();
                }
                catch(Exception $e){
                    log_message('error',__METHOD__.' '.$e);
                    show_error('Internal server error',500);
                }

                redirect(current_url(), 'location');
            }

        }

        redirect(base_url(), 'location');


    }



    private function checkGlobal() {
        if ($this->oidcEnabled !== true || !is_array($this->oidcOps) || count($this->oidcOps) == 0 || !class_exists('Jagger\oidc\Client')) {
            throw new Exception('OpenID Connect not enabled or extension not found');
        }
    }
}
