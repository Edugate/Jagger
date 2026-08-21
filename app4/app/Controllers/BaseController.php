<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Base for every migrated CI4 controller.
 *
 * Two things every migrated controller needs that a stock CI4 install
 * doesn't provide out of the box, both implemented here so each controller
 * doesn't reimplement them: a Doctrine EntityManager pointed at the SAME
 * entity classes the legacy CI3 app uses (application/models — not
 * duplicated here, to avoid two copies of the schema mapping drifting
 * apart), and a read of the CI3-created session so a user who logged in
 * through the legacy app is still recognized as logged in here.
 */
abstract class BaseController extends Controller
{
    protected $helpers = [];

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * CI3 legacy session userdata, keyed the same way Jauth/User::getBasic()
     * populate it (username, user_id, logged, ...). Empty array if no CI3
     * session cookie is present at all.
     */
    protected array $legacySession = [];

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->bridgeLegacySession();
        $this->em = $this->buildEntityManager();
    }

    /**
     * Reads the session CI3 already created instead of starting a second,
     * disconnected one.
     *
     * This only works because Jagger's shipped config_rr/config default to
     * CodeIgniter 3's 'files' session driver (application/config/config-
     * default.php: $config['sess_driver'] = 'files';), which — unlike CI2's
     * custom cookie-based sessions — is a thin wrapper around PHP's native
     * session_start()/$_SESSION. Using the identical cookie name
     * ('rr3sess', set in app4/app/Config/App.php to match
     * $config['sess_cookie_name']) means CI4's native session handling
     * reads the exact same $_SESSION array CI3 wrote.
     *
     * If a deployment overrides sess_driver to 'database' or a custom
     * driver, this bridge does not apply and must be redone against that
     * driver's storage instead — flagged in docs/CI4_MIGRATION.md as the
     * first thing to re-check before migrating any state-changing
     * (non-Dashboard/logout) controller.
     */
    private function bridgeLegacySession(): void
    {
        // Reading $_SESSION only works if something actually called
        // session_start() first -- CI4 doesn't do that automatically just
        // because Config\App::$sessionCookieName is set, only when its own
        // Session service is actively used, which nothing here does.
        // Without this, isLoggedIn() would silently always return false
        // even for an actually-logged-in CI3 user.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('rr3sess'); // must match $config['sess_cookie_name'], config-default.php
            session_start();
        }
        $this->legacySession = $_SESSION ?? [];
    }

    protected function isLoggedIn(): bool
    {
        return !empty($this->legacySession['logged']) && !empty($this->legacySession['username']);
    }

    private function buildEntityManager(): EntityManagerInterface
    {
        $legacyAppPath = realpath(APP4_PATH . '/../application') . '/';

        $db = $this->loadDatabaseConfig();

        // Doctrine\Common\ClassLoader was removed in doctrine/common 3.x
        // (pulled in transitively by doctrine/orm ^2.19) and isn't needed
        // anyway: app4/composer.json declares the same "models\\"/"Proxies\\"
        // PSR-4 mappings (pointed at ../application/models) that the
        // ClassLoader calls used to set up by hand, resolved by Composer's
        // own autoloader. See application/libraries/Doctrine.php for the
        // same fix applied on the CI3 side.
        $cache = new ArrayCache();
        if (ENVIRONMENT === 'production' && extension_loaded('apcu')) {
            $cache = new ApcCache();
        }

        $config = new Configuration();
        $driverImpl = $config->newDefaultAnnotationDriver($legacyAppPath . 'models');
        $config->setMetadataDriverImpl($driverImpl);
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);
        $config->setProxyDir($legacyAppPath . 'models/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setAutoGenerateProxyClasses(ENVIRONMENT !== 'production');

        return EntityManager::create($db, $config);
    }

    /**
     * Reads /etc/jagger/database.php -- the same file
     * application/config/database.php is symlinked to by
     * packaging/scripts/provision.sh -- so both frameworks always connect
     * with identical credentials.
     */
    private function loadDatabaseConfig(): array
    {
        $configPath = getenv('JAGGER_DATABASE_CONFIG') ?: '/etc/jagger/database.php';
        if (!is_file($configPath)) {
            // Local/dev fallback when not installed via packaging.
            $configPath = realpath(APP4_PATH . '/../application/config/database.php');
        }
        if ($configPath === false || !is_file($configPath)) {
            throw new \RuntimeException('No database.php config found (checked JAGGER_DATABASE_CONFIG, /etc/jagger/database.php, application/config/database.php).');
        }

        require $configPath;
        /** @var array $db */
        $connectionOptions = [
            'driver'   => 'pdo_mysql',
            'user'     => $db['default']['username'],
            'password' => $db['default']['password'],
            'dbname'   => $db['default']['database'],
            'host'     => $db['default']['hostname'],
            'charset'  => $db['default']['char_set'] ?? 'utf8',
        ];
        // Matches application/libraries/Doctrine.php::getDBDriver()'s
        // handling of the same file: 'port' isn't set by
        // database-default.php's template (it's embedded in the 'dsn'
        // string instead, which this CI4 side doesn't parse), but an
        // admin can still add it by hand, and both sides need to honor it
        // the same way if they do.
        if (isset($db['default']['port'])) {
            $connectionOptions['port'] = $db['default']['port'];
        }

        return $connectionOptions;
    }
}
