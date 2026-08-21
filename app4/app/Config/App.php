<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    // Populated at runtime from the same /etc/jagger/config.php value CI3 uses
    // (see BaseController::bootstrap()), so both frameworks always agree on it.
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

    public bool $sessionTimeToUpdate = true;

    public bool $sessionRegenerateDestroy = false;

    public string $cookiePrefix = '';

    public string $cookieDomain = '';

    public string $cookiePath = '/';

    public bool $cookieSecure = true;

    public bool $cookieHTTPOnly = true;

    public string $cookieSameSite = 'Lax';

    public bool $CSPEnabled = false;
}
