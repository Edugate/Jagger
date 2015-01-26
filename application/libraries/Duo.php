<?php

class Duo {
	const DUO_PREFIX = "TX";
	const APP_PREFIX = "APP";
	const AUTH_PREFIX = "AUTH";

	const DUO_EXPIRE = 300;
	const APP_EXPIRE = 3600;

	const IKEY_LEN = 20;
	const SKEY_LEN = 40;
	const AKEY_LEN = 40; // if this changes you have to change ERR_AKEY

	const ERR_USER = 'ERR|The username passed to sign_request() is invalid.';
	const ERR_IKEY = 'ERR|The Duo integration key passed to sign_request() is invalid.';
	const ERR_SKEY = 'ERR|The Duo secret key passed to sign_request() is invalid.';
	const ERR_AKEY =  "ERR|The application secret key passed to sign_request() must be at least 40 characters."; 

	private static function sign_vals($key, $vals, $prefix, $expire) { 
		$exp = time() + $expire;

		$val = $vals . '|' . $exp;
		$b64 = base64_encode($val);
		$cookie = $prefix . '|' . $b64;

		$sig = hash_hmac("sha1", $cookie, $key);
		return $cookie . '|' . $sig;
	}

	private static function parse_vals($key, $val, $prefix) {
		$ts = time();
		list($u_prefix, $u_b64, $u_sig) = explode('|', $val);

		$sig = hash_hmac("sha1", $u_prefix . '|' . $u_b64, $key);
		if (hash_hmac("sha1", $sig, $key) != hash_hmac("sha1", $u_sig, $key)) {
			return null;
		}

		if ($u_prefix != $prefix) {
			return null;
		}

		list($user, $ikey, $exp) = explode('|', base64_decode($u_b64));

		if ($ts >= intval($exp)) {
			return null;
		}

		return $user;
	}

	public static function signRequest($ikey, $skey, $akey, $username) {
                log_message('debug','GLOS : '.$username.' ::'.$akey.' :: '.strlen($akey));
		if (!isset($username) || strlen($username) == 0){
			return self::ERR_USER;
		}
		if (!isset($ikey) || strlen($ikey) != self::IKEY_LEN) {
			return self::ERR_IKEY;
		}
		if (!isset($skey) || strlen($skey) != self::SKEY_LEN) {
			return self::ERR_SKEY;
		}
		if (!isset($akey) || strlen($akey) < self::AKEY_LEN) {
			return self::ERR_AKEY;
		}

		$vals = $username . '|' . $ikey;

		$duo_sig = self::sign_vals($skey, $vals, self::DUO_PREFIX, self::DUO_EXPIRE);
		$app_sig = self::sign_vals($akey, $vals, self::APP_PREFIX, self::APP_EXPIRE);	

		return $duo_sig . ':' . $app_sig;
	}

	public static function verifyResponse($ikey, $skey, $akey, $sig_response) {
		list($auth_sig, $app_sig) = explode(':', $sig_response);

		$auth_user = self::parse_vals($skey, $auth_sig, self::AUTH_PREFIX);
		$app_user = self::parse_vals($akey, $app_sig, self::APP_PREFIX);

		if ($auth_user != $app_user) {
			return null;
		}

		return $auth_user;
	}
}

?>
