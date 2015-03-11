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
 * J_auth Class
 *
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class J_auth
{

	protected $em;
	protected $ci;
	protected $status;
	protected $messages;
	protected $errors = array();
	public static $timeOffset = 0;
	protected static $isAdmin;

	public function __construct()
	{
		$this->ci = &get_instance();
		$this->em = $this->ci->doctrine->em;
		$this->ci->load->helper('cookie');
        self::$timeOffset = (int) $this->ci->session->userdata('timeoffset') *60;
		log_message('debug', 'TimeOffset  :' . self::$timeOffset);
	}

	public function finalizepartiallogin()
	{

		$usersession = $this->ci->session->userdata();
		if (!empty($usersession['partiallogged']) && !empty($usersession['username'])) {
			try {
				$u = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $usersession['username'] . ''));
			} catch (PDOException $e) {
				log_message('error', $e);
				show_error("Server error", 500);
				exit;
			}
			if (empty($u)) {

				return false;
			}
			$ip = $this->ci->input->ip_address();
			$userprefs = $u->getUserpref();
			if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
				$this->ci->session->set_userdata(array('board' => $userprefs['board']));
			}

			$u->setIP($ip);
			$u->updated();
			$this->em->persist($u);
			if(isset($usersession['authntype']))
			{
				$authntype = $usersession['authntype'];
			}
			else
			{
				$authntype = '';
			}
			$track_details = 'Authn from ' . $ip . ' ::  ' . $authntype . ' Authn and 2F';
			$this->ci->tracker->save_track('user', 'authn', $u->getUsername(), $track_details, false);

			$this->em->flush();
			$this->ci->session->set_userdata('logged', 1);
			$this->ci->session->unset_userdata('partiallogged');

			return true;
		}
		return false;
	}

	public function login($identity, $password)
	{
		/**
		 * @todo change to use static from model, add more condition like user is local,valid etc
		 */
		try {
			$u = $this->em->getRepository("models\User")->findOneBy(array('username' => $identity, 'local' => true));
		} catch (PDOException $e) {
			log_message('error', $e);
			show_error("Server error", 500);
			exit;
		}
		if ($u) {
			$salt = $u->getSalt();
			$encrypted_password = sha1($password . $salt);
			$pass = $u->getPassword();
			if (strcmp($pass, $encrypted_password) == 0) {
				$twofactorauthn = $this->ci->config->item('twofactorauthn');
				$secondfactor = $u->getSecondFactor();
				if (!empty($twofactorauthn) && $twofactorauthn === TRUE && !empty($secondfactor) && $secondfactor === 'duo') {
					$sig_request = Duo::signRequest($this->ci->config->item('duo-ikey'), $this->ci->config->item('duo-skey'), $this->ci->config->item('duo-akey'), $u->getUsername());
					$this->ci->session->set_userdata(
						array('partiallogged' => 1,
							'logged' => 0,
							'username' => '' . $u->getUsername() . '',
							'user_id' => '' . $u->getId() . '',
							'secondfactor' => $secondfactor,
							'authntype' => 'local')
					);
					return TRUE;
				} else {
					$ip = $this->ci->input->ip_address();
					$userprefs = $u->getUserpref();
					if (!empty($userprefs) && array_key_exists('board', $userprefs)) {
						$this->ci->session->set_userdata(array('board' => $userprefs['board']));
					}

					$u->setIP($ip);
					$u->updated();
					$this->em->persist($u);
					$track_details = 'Authn from ' . $ip . ' ::  Local Authn';
					$this->ci->tracker->save_track('user', 'authn', $u->getUsername(), $track_details, false);

					$session_data = $u->getBasic();
					$this->em->flush();
					$this->ci->session->set_userdata(array(
						'logged' => 1,
						'username' => '' . $session_data['username'] . '',
						'user_id' => '' . $session_data['user_id'] . '',
						'showhelp' => '' . $session_data['showhelp'] . '',
						'authntype' => 'local'
					));
					$this->ci->session->sess_regenerate();
					$this->set_message('login_successful');
					return TRUE;
				}
			} else {
				$this->set_error('login_unsuccessful');
				return FALSE;
			}
		} else {
			$this->set_error('login_unsuccessful');
			return FALSE;
		}
	}

	public function logout()
	{
		$identity = $this->ci->config->item('identity', 'j_auth');
		$this->ci->session->sess_destroy();
		$this->ci->session->sess_regenerate(TRUE);
		$this->set_message('logout_successful');
		return TRUE;
	}

	public function logged_in()
	{
        $loggedin = trim($this->ci->session->userdata('logged'));
        $username = trim($this->ci->session->userdata('username'));
        if(!empty($loggedin)  && !empty($username))
        {
            log_message('debug', 'session is active for: ' .$username . '');
            return true;
        }
        else {
			return false;
		}
	}

	public function current_user()
	{
        if($this->logged_in())
        {
            return trim($this->ci->session->userdata('username'));
        }

		return false;
	}

	public function set_error($error)
	{
		$this->errors[] = $error;

		return $error;
	}

	public function errors()
	{
		$_output = '';
		foreach ($this->errors as $error) {
			$_output .= "<p>" . $this->ci->lang->line($error) . "</p>";
		}

		return $_output;
	}

	public function set_message($message)
	{
		$this->messages[] = $message;

		return $message;
	}

	public function messages()
	{
		$_output = '';
		foreach ($this->messages as $message) {
			$_output .= "<p>" . $this->ci->lang->line($message) . "</p>";
		}

		return $_output;
	}

	public function isAdministrator()
	{
		if (self::$isAdmin === true) {
			return TRUE;
		} elseif (self::$isAdmin === false) {
			return FALSE;
		}

		$username = $this->current_user();
		if (empty($username)) {
			self::$isAdmin = false;
			return FALSE;
		}
		$u = $this->em->getRepository("models\User")->findOneBy(array('username' => '' . $username . ''));
		if (empty($u)) {
			log_message('error', 'isAdministrator: Browser client session from IP:' . $_SERVER['REMOTE_ADDR'] . ' references to nonexist user: ' . $username);
			$this->ci->session->sess_destroy();
			return FALSE;
		}
		$adminRole = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => 'Administrator', 'type' => 'system'));
		if (empty($adminRole)) {
			log_message('error', 'isAdministrator: Administrator Role is missing in DB AclRoles tbl');

			return FALSE;
		} else {
			$userRoles = $u->getRoles();
			if ($userRoles->contains($adminRole)) {
				log_message('debug', 'isAdministrator: user ' . $u->getUsername() . ' found in Administrator group');
				self::$isAdmin = true;
				return TRUE;
			} else {
				log_message('debug', 'isAdministrator: user ' . $u->getUsername() . ' not found in Administrator group');
				self::$isAdmin = false;
				return FALSE;
			}
		}
		return FALSE;
	}

}

?>
