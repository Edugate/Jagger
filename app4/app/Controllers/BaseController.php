<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Doctrine\Common\ClassLoader;
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

        $entitiesClassLoader = new ClassLoader('models', rtrim($legacyAppPath, '/'));
        $entitiesClassLoader->register();

        $proxiesClassLoader = new ClassLoader('Proxies', $legacyAppPath . 'models');
        $proxiesClassLoader->register();

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
     * Reads the exact same file CI3's application/config/database.php
     * shims to (see packaging/etc/jagger/database.php + M4), so both
     * frameworks always connect with identical credentials.
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
        return [
            'driver'   => 'pdo_mysql',
            'user'     => $db['default']['username'],
            'password' => $db['default']['password'],
            'dbname'   => $db['default']['database'],
            'host'     => parse_url('mysql://' . $db['default']['hostname'])['host'] ?? $db['default']['hostname'],
            'charset'  => $db['default']['char_set'] ?? 'utf8',
        ];
    }
}
