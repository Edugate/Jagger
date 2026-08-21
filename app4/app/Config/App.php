<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    // Left blank deliberately: CI4 auto-detects the base URL from the
    // request when this is empty, which keeps it in agreement with CI3's
    // own $config['base_url'] (set in /etc/jagger/config.php) without this
    // file needing to read that CI3 config file itself at CI4 bootstrap
    // time -- config classes are instantiated too early in the framework's
    // own lifecycle for that to be straightforward to do correctly.
    public string $baseURL = '';

    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $defaultLocale = 'en';

    public bool $negotiateLocale = false;

    public array $supportedLocales = ['en'];

    public string $appTimezone = 'UTC';

    public string $charset = 'UTF-8';

    public bool $forceGlobalSecureRequests = true;

    // Matches CI3's $config['sess_cookie_name'] ('rr3sess') so a session
    // created by the legacy app is readable here. See BaseController.
    public string $sessionCookieName = 'rr3sess';

    public int $sessionExpiration = 3600;

    public bool $sessionMatchIP = false;

    // Seconds between session ID regeneration -- this is an int in CI4
    // (unlike a same-named bool option in some other frameworks); matches
    // CI3's $config['sess_time_to_update'] default (config-default.php).
    public int $sessionTimeToUpdate = 300;

    public bool $sessionRegenerateDestroy = false;

    public string $cookiePrefix = '';

    public string $cookieDomain = '';

    public string $cookiePath = '/';

    public bool $cookieSecure = true;

    public bool $cookieHTTPOnly = true;

    public string $cookieSameSite = 'Lax';

    public bool $CSPEnabled = false;
}
