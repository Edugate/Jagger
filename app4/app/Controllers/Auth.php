<?php

namespace App\Controllers;

/**
 * Only the logout path is migrated here — it just destroys session state,
 * so it carries none of the risk that porting login (Shibboleth attribute
 * parsing, 2FA, auto-registration — application/controllers/Auth.php,
 * ~550 lines) would without a live IdP/session-driver to test against.
 * Login stays on CodeIgniter 3 until each of those paths is migrated and
 * reviewed individually per docs/CI4_MIGRATION.md.
 */
class Auth extends BaseController
{
    public function logout()
    {
        // Mirrors application/libraries/Jauth.php::logout() clearing the
        // same $_SESSION array the CI3<->CI4 bridge in BaseController reads.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        return redirect()->to(site_url('/'));
    }
}
