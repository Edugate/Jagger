<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * Auth Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Auth extends MY_Controller
{
	private $data;

	function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}

	function index()
	{
		if (!$this->j_auth->logged_in()) {
			return $this->login();
		} else {
			redirect($this->config->item('base_url'), 'location');
		}
	}

	function logout()
	{
		if ($this->j_auth->logged_in()) {
			$this->j_auth->logout();
		}
		$this->load->view('auth/logout');
	}

	function fedregister()
	{
		$method = $this->input->method(TRUE);
		if (!$this->input->is_ajax_request()) {
			show_error('Permission denied', 403);
		}
		if ($method !== 'POST') {
			set_status_header(403);
			echo 'permission denied';
			return;
		}
		if ($this->j_auth->logged_in()) {
			set_status_header(403);
			echo 'already euthenticated';
			return;
		}

		$fedidentity = $this->session->userdata('fedidentity');
		log_message('debug', __METHOD__ . ' fedregistration in post received' . serialize($this->session->userdata()));
		if (!empty($fedidentity) && is_array($fedidentity)) {
			if (isset($fedidentity['fedusername'])) {
				$username = $fedidentity['fedusername'];
			}
			if (isset($fedidentity['fedemail'])) {
				$email = $fedidentity['fedemail'];
			}
			if (empty($username) || empty($email)) {
				set_status_header(403);
				$this->session->sess_destroy();
				$this->session->sess_regenerate(TRUE);
				echo 'missing some attrs like username or/and email';
				return;
			}
			if (isset($fedidentity['fedfname'])) {
				$fname = $fedidentity['fedfname'];
			}
			if (isset($fedidentity['fedsname'])) {
				$sname = $fedidentity['fedsname'];
			}
		}
		$ip = $this->input->ip_address();
		$checkuser = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
		if (!empty($checkuser)) {
			$this->session->sess_destroy();
			$this->session->sess_regenerate(TRUE);
			set_status_header(403);
			echo lang('err_userexist');
			return;
		}
		$inqueue = $this->em->getRepository("models\Queue")->findOneBy(array('name' => $username, 'action' => 'Create'));
		if (!empty($inqueue)) {
			set_status_header(403);
			echo lang('err_userinqueue');
			return;
		}


		$user = array(
			'username' => trim($username),
			'email' => trim($email),
			'fname' => trim($fname),
			'sname' => trim($sname),
			'type' => 'federated',
			'ip' => $ip,
		);
		$queue = new models\Queue;
		$queue->setAction('Create');
		$queue->setName($username);
		$queue->setEmail($email);
		$queue->setToken();
		$queue->addUser($user);
		$this->em->persist($queue);
		/**
		 * BEGIN send notification
		 */
		if (!empty($fname) || !empty($sname)) {
			$reqfullname = trim($fname) . ' ' . trim($sname);
		} else {
			$reqfullname = 'unknown fullname';
		}
		$nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

		$templateArgs = array(
			'token' => $queue->getToken(),
			'srcip' => $ip,
			'reqemail' => trim($email),
			'requsername' => trim($username),
			'reqfullname' => $reqfullname,
			'qurl' => '' . base_url() . 'reports/awaiting/detail/' . $queue->getToken() . '',
			'datetimeutc' => '' . $nowUtc->format('Y-m-d h:i:s') . ' UTC',
		);

		$mailTemplate = $this->email_sender->generateLocalizedMail('userregresquest', $templateArgs);

		if (empty($mailTemplate)) {
			$sbj = 'User registration request';
			$body = 'Dear user,' . PHP_EOL;
			$body .= 'You have received this mail because your email address is on the notification list' . PHP_EOL;
			$body .= 'User from ' . $ip . ' using federated access has applied for an account.' . PHP_EOL;
			$body .= 'Please review the request and make appriopriate action (reject/approve)' . PHP_EOL;
			$body .= 'Details about the request: ' . base_url() . 'reports/awaiting/detail/' . $queue->getToken() . PHP_EOL;
			$this->email_sender->addToMailQueue(array(), null, $sbj, $body, array(), FALSE);
		} else {
			$this->email_sender->addToMailQueue(array(), null, $mailTemplate['subject'], $mailTemplate['body'], array(), FALSE);

		}
		/**
		 * END send notification
		 */
		try {
			$this->em->flush();
			$this->session->sess_destroy();
			$this->session->sess_regenerate(TRUE);
			set_status_header(200);
			echo lang('userregreceived');
		} catch (Exception $e) {
			log_message('error', __METHOD__ . ' ' . $e);
			set_status_header(500);
			echo 'Unknown error occured';
		}
	}

	function ssphpauth()
	{
		if ($this->j_auth->logged_in()) {
			redirect($this->config->item('base_url'), 'location');
		}
		$spsp = $this->config->item('simplesamlphp');
		if (empty($spsp['enabled'])) {
			show_error('Federated access is not enabled', 403);
		}
		if (empty($spsp['location']) || !file_exists($spsp['location'])) {
			log_message('error', 'location of simeplsamlphp is not set or not exist. check config file and check $[simplesamlphp][location]');
			show_error('Server error', 500);
		}

		if (!isset($spsp['attributes'])) {
			log_message('error', 'missing defined $[simplesamlphp][attributes]');
			show_error('Server error', 500);
		}
		$mapped = $spsp['attributes'];
		if (empty($mapped['username']) || empty($mapped['mail'])) {
			log_message('error', 'missing defined $[simplesamlphp][attributes][username] or/and $[simplesamlphp][attributes][mail] in config ');
			show_error('Server error', 500);
		}
		require_once($spsp['location']);
		$auth = new \SimpleSAML_Auth_Simple('' . $spsp['authsourceid'] . '');
		$auth->requireAuth();

		$attributes = $auth->getAttributes();


		if (!empty($attributes['' . $mapped['username'] . ''])) {
			if (is_array($attributes['' . $mapped['username'] . '']) && count($attributes['' . $mapped['username'] . '']) == 1) {
				$username = reset($attributes['' . $mapped['username'] . '']);
				if (empty($username)) {
					show_error('Missing atribute from IdP', 403);
				}
			} else {
				log_message('warning', 'Missing or multiple values found for attr: ' . $mapped['username'] . ' ');
				show_error('Missing or multiple values found for attr', 403);
			}
		} else {
			log_message('warning', 'Couldn find ' . $mapped['username'] . ' provider by simplesaml');
			show_error('Missing atribute from IdP to map as username', 403);
		}
		$mail = null;
		if (!empty($attributes['' . $mapped['mail'] . ''])) {
			if (is_array($attributes['' . $mapped['mail'] . '']) && count($attributes['' . $mapped['mail'] . '']) > 0) {

				$mail = reset($attributes['' . $mapped['mail'] . '']);

				if (empty($mail)) {
					log_message('warning', 'IdP didnt provide mail');
					show_error('Missing mail attribute', 403);
				}
			} else {
				log_message('warning', 'IdP didnt provide mail');
				show_error('Missing mail attribute', 403);
			}
		} else {
			log_message('warning', 'IdP didnt provide mail');
			show_error('Missing mail attribute', 403);
		}

		$user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));

		if (!empty($user)) {

			$can_access = (bool)($user->isEnabled() && $user->getFederated());
			if (!$can_access) {
				show_error(lang('rerror_youraccountdisorfeddis'), 403);
			}
			$session_data = $user->getBasic();
			$userprefs = $user->getUserpref();

			$this->session->set_userdata($session_data);
			if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
				$this->session->set_userdata(array('board' => $userprefs['board']));
			}
			$this->session->set_userdata('username', '' . $session_data['username'] . '');
			$this->session->set_userdata('user_id', '' . $session_data['user_id'] . '');
			if (!empty($timeoffset) && is_numeric($timeoffset)) {
				$this->session->set_userdata('timeoffset', (int)$timeoffset);
			}
			$this->session->set_userdata('logged', 1);
			$ip = $this->input->ip_address();
			$user->setIP($ip);
			$user->updated();
			$this->em->persist($user);
			$this->load->library('tracker');
			$track_details = 'Authn from ' . $ip . '  with federated access';
			$this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
			$this->em->flush();
		} else {

			$can_autoregister = $this->config->item('autoregister_federated');
			if (!$can_autoregister) {

				log_message('error', 'User authorization failed: ' . $username . ' doesnt exist in RR');
				show_error(' ' . htmlentities($username) . ' - ' . lang('error_usernotexist') . ' ' . lang('applyforaccount') . ' <a href="mailto:' . $this->config->item('support_mailto') . '?subject=Access%20request%20from%20' . $mail . '">' . lang('rrhere') . '</a>', 403);
			} else {
				$attrs = array('username' => $username, 'mail' => $mail);
				$reg = $this->registerUser($attrs);
				if ($reg !== TRUE) {
					show_error('User couldnt be registered.', 403);
				}
			}
			redirect(current_url(), 'location');
		}
		redirect(base_url(), 'location');
	}

	function login()
	{

		if ($this->j_auth->logged_in()) {
			redirect($this->config->item('base_url'), 'location');
		}
		$this->data['dontshowsigning'] = true;
		$this->data['title'] = lang('authn_form');
		$this->data['showloginform'] = TRUE;
		$this->data['content_view'] = 'auth/empty_view';
		$this->load->view('page', $this->data);

	}

	private function getShibUsername()
	{
		$usernameVarName = $this->config->item('Shib_username');
		if (isset($_SERVER[$usernameVarName])) {
			return $_SERVER[$usernameVarName];
		} elseif (isset($_SERVER['REDIRECT_' . $usernameVarName])) {
			return $_SERVER['REDIRECT_' . $usernameVarName];
		} else {
			return FALSE;
		}
	}

	private function getShibFname()
	{
		$fnameVarName = $this->config->item('Shib_fname');
		if (empty($fnameVarName)) {
			return false;
		}
		if (isset($_SERVER[$fnameVarName])) {
			return $_SERVER[$fnameVarName];
		} elseif (isset($_SERVER['REDIRECT_' . $fnameVarName])) {
			return $_SERVER['REDIRECT_' . $fnameVarName];
		}
		return false;
	}

	private function getShibSname()
	{
		$snameVarName = $this->config->item('Shib_sname');
		if (empty($snameVarName)) {
			return false;
		}
		if (isset($_SERVER[$snameVarName])) {
			return $_SERVER[$snameVarName];
		} elseif (isset($_SERVER['REDIRECT_' . $snameVarName])) {
			return $_SERVER['REDIRECT_' . $snameVarName];
		}
		return false;
	}

	private function getShibMail()
	{
		$emailVarName = $this->config->item('Shib_mail');
		if (isset($_SERVER[$emailVarName])) {
			return $_SERVER[$emailVarName];
		} elseif (isset($_SERVER['REDIRECT_' . $emailVarName])) {
			return $_SERVER['REDIRECT_' . $emailVarName];
		} else {
			return '';
		}
	}

	private function getShibIdp()
	{
		if (isset($_SERVER['Shib-Identity-Provider'])) {
			return $_SERVER['Shib-Identity-Provider'];
		} elseif (isset($_SERVER['REDIRECT_Shib-Identity-Provider'])) {
			return $_SERVER['REDIRECT_Shib-Identity-Provider'];
		} elseif (isset($_SERVER['Shib_Identity_Provider'])) {
			return $_SERVER['Shib_Identity_Provider'];
		} elseif (isset($_SERVER['REDIRECT_Shib_Identity_Provider'])) {
			return $_SERVER['REDIRECT_Shib_Identity_Provider'];
		} else {
			return '';
		}
	}

	private function registerUser($attrs)
	{
		$username = $attrs['username'];
		$mail = $attrs['mail'];
		$fname = trim($attrs['fname']);
		$sname = trim($attrs['sname']);

		$user = new models\User;
		$this->load->helper('random_generator');
		$randompass = str_generator();
		$user->setUsername($username);
		$user->setEmail($mail);
		$user->setSalt();
		$user->setPassword($randompass);
		$user->setLocalDisabled();
		$user->setFederatedEnabled();
		$user->setAccepted();
		$user->setEnabled();
		$user->setValid();
		if (!empty($fname)) {
			$user->setGivenname($fname);
		}
		if (!empty($sname)) {
			$user->setSurname($sname);
		}
		$user->setUserpref(array());
		$defaultRole = $this->config->item('register_defaultrole');
		$allowedroles = array('Guest', 'Member');
		if (empty($defaultRole) || !in_array($defaultRole, $allowedroles)) {
			$defaultRole = 'Guest';
		}
		$member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $defaultRole));
		if (!empty($member)) {
			$user->setRole($member);
		}
		$p_role = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $username));
		if (empty($p_role)) {
			$p_role = new models\AclRole;
			$p_role->setName($username);
			$p_role->setType('user');
			$p_role->setDescription('personal role for user ' . $username);
			$user->setRole($p_role);
			$this->em->persist($p_role);
		}
		$this->em->persist($user);
		$this->tracker->save_track('user', 'create', $username, 'user autocreated in the system', false);
		$this->em->flush();

		$this->assignRolesFromAA($user);
		return true;
	}
	
	private function assignRolesFromAA($user) {
		if (empty($this->config->item('Shib_groups'))) {
			// Groups not retrieved from AA, do nothing and exit
			return;
		}
		
		$groups = $this->getShibGroups();
		$group_administrator = $this->config->item('register_group_administrator');
		$group_member = $this->config->item('register_group_member');
		$group_guest = $this->config->item('register_group_guest');
		
		// Check configuration parameters for group names in the AA
		if (empty($group_administrator)) {
			$group_administrator = 'Administrator';
		}
		if (empty($group_member)) {
			$group_member = 'Member';
		}
		if (empty($group_guest)) {
			$group_guest = 'Guest';
		}
		
		// Obtain user group by looking at AA attribute
		if (strstr($groups, $group_administrator) !== false)
		{
			$userGroup = 'Administrator';
		}
		else if (strstr($groups, $group_member) !== false)
		{
			$userGroup = 'Member';
		}
		else if (strstr($groups, $group_guest) !== false)
		{
			$userGroup = 'Guest';
		}
		else
		{
			log_message('warning', 'The group attribute is not present in federated authentication');
			return;
		}
		
		// Remove user from old groups
		foreach ($user->getRoles() as $role)
		{
			$user->unsetRole($role);
		}
		
		// Assign user to the right group
		$member = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $userGroup));
		if (!empty($member))
		{
			$user->setRole($member);
			log_message('debug', 'The user has the ' . $member->getName() . ' role.');
		}

		$this->em->persist($user);
		$user->updated();
		$this->em->flush();
	}

	public function fedauth($timeoffset = null)
	{
		$shibb_valid = (bool)$this->getShibIdp();
		if (!$shibb_valid) {
			log_message('error', 'This location should be protected by shibboleth in apache');
			show_error('Internal server error', 500);
			return;
		}
		if ($this->j_auth->logged_in()) {
			redirect('' . base_url() . '', 'location');
		}
		$userValue = $this->getShibUsername();
		if (empty($userValue)) {
			log_message('error', 'IdP:: '.$this->getShibIdp().' didnt provide username');
			show_error('Internal server error: missing required attribute in SAML response from Identity Provider', 500);
		}

		$user = $this->em->getRepository("models\User")->findOneBy(array('username' => $userValue));
		if (!empty($user)) {
			$can_access = (bool)($user->isEnabled() && $user->getFederated());
			if (!$can_access) {
				show_error(lang('rerror_youraccountdisorfeddis'), 403);
			}
			$session_data = $user->getBasic();
			$userprefs = $user->getUserpref();
			$this->session->set_userdata('username',''.$session_data['username'].'');
			$this->session->set_userdata('user_id',''.$session_data['user_id'].'');
			$this->session->set_userdata('authntype','federated');

			$systemTwoFactor = $this->config->item('twofactorauthn');
			if (!empty($systemTwoFactor) && $systemTwoFactor === true) {
				$userSecondFactor = $user->getSecondFactor();
				$systemAllowed2Factors = $this->config->item('2fengines');
				if (empty($systemAllowed2Factors) || !is_array($systemAllowed2Factors)) {
					$systemAllowed2Factors = array();
				}
				if (!empty($userSecondFactor) && in_array($userSecondFactor, $systemAllowed2Factors)) {

					$this->session->set_userdata('partiallogged',1);
					$this->session->set_userdata('secondfactor',trim($userSecondFactor));
					$this->session->set_userdata('twofactor',1);
					$this->session->set_userdata('logged',0);
				} else {

					$this->session->set_userdata('logged',1);
				}
			} else {

				$this->session->set_userdata('logged',1);
			}
			$this->session->set_userdata($session_data);
			if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
				$this->session->set_userdata('board' ,$userprefs['board']);
			}
			if (!empty($timeoffset) && is_numeric($timeoffset)) {

				$this->session->set_userdata('timeoffset',(int)$timeoffset);
			}
			$updatefullname = $this->config->item('shibb_updatefullname');
			if (!empty($updatefullname) && $updatefullname === TRUE) {
				$fname = trim($this->getShibFname());
				$sname = trim($this->getShibSname());
				if (!empty($fname)) {
					$user->setGivenname('' . $fname . '');
				}
				if (!empty($sname)) {
					$user->setSurname('' . $sname . '');
				}
			}
			$islogged = $this->session->userdata('logged');
			if(!empty($islogged)) {

				$ip = $this->input->ip_address();
				$user->setIP($ip);
				$user->updated();
				$this->em->persist($user);
				$this->load->library('tracker');
				$track_details = 'Authn from ' . $ip . '  with federated access';
				$this->tracker->save_track('user', 'authn', $user->getUsername(), $track_details, false);
				$this->em->flush();
				
				$this->assignRolesFromAA($user);
			}
		} else {
			$fnameVarName = $this->getShibFname();
			$snameVarName = $this->getShibSname();
			$emailVarName = $this->getShibMail();
			$canAutoRegister = $this->config->item('autoregister_federated');
			if (empty($emailVarName)) {
				log_message('warning', __METHOD__ . ' User hasnt provided email attr during federated access');
				show_error(lang('error_noemail'), 403);
				return;
			}

			if (!$canAutoRegister) {
				log_message('error', 'User authorization failed: ' . $userValue . ' doesnt exist in RR');

				$fedidentity = array('fedusername' => $userValue, 'fedfname' => $this->getShibFname(), 'fedsname' => $this->getShibSname(), 'fedemail' => $this->getShibMail());
				$this->session->set_userdata(array('fedidentity' => $fedidentity));
				$data['content_view'] = 'feduserregister_view';
				$this->load->view('page', $data);
				return;
			} else {

				$attrs = array('username' => $userValue, 'mail' => $emailVarName, 'fname' => $fnameVarName, 'sname' => $snameVarName);
				$reg = $this->registerUser($attrs);

				if ($reg !== TRUE) {
					show_error('User couldnt be registered.', 403);
				}
				redirect(current_url(), 'location');
			}
		}
		redirect(base_url(), 'location');
	}

}
