<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

define('DEBUGGING', false);

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2018, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Doctrine
{

    public $em = null;

    public function __construct()
    {
        // load database configuration and custom config from CodeIgniter
        require APPPATH . 'config/database.php';

        // Doctrine\Common\ClassLoader (a PSR-0 autoloader Doctrine used to
        // ship) was removed in doctrine/common 3.x, which doctrine/orm
        // ^2.19 (composer.json) pulls in -- it's no longer available to
        // call here. It's also no longer needed: Composer's own autoloader
        // is already active by the time this constructor runs (required in
        // application/doctrine.php and by application/config/autoload.php's
        // $config['composer_autoload']), and application/composer.json now
        // declares the same "models\\" -> models/ and "Proxies\\" ->
        // models/Proxies/ PSR-4 mappings the ClassLoader calls used to set
        // up by hand. Unlike a classmap, PSR-4 resolves by convention at
        // runtime, so Doctrine's dynamically-generated proxy classes (new
        // files appearing in models/Proxies/ after `composer install` already
        // ran) are still found correctly without needing `composer dump-autoload`.
        $cache = new \Doctrine\Common\Cache\ArrayCache;

        // Choose caching method based on application mode
        if (ENVIRONMENT === 'production' && extension_loaded('apc') && ini_get('apc.enabled')) {
            $cache = new \Doctrine\Common\Cache\ApcCache;
        }
        $config = new Configuration;

        // Metadata driver. newDefaultAnnotationDriver() is deprecated as of
        // later doctrine/orm 2.x releases (in favor of ORMSetup::create*, and
        // ultimately PHP 8 attributes in ORM 3.x) but is kept working
        // throughout the 2.x line under Doctrine's deprecate-in-2.x/
        // remove-in-3.x policy -- staying on this call, rather than
        // rewriting all 39 files under application/models from bare
        // @Entity/@Column annotations to attributes, is a deliberate choice
        // (see docs/AUDIT.md): that rewrite is real work with real blast
        // radius, out of scope for dependency-compatibility fixes. Needs
        // doctrine/annotations pinned to ^1.14 (composer.json) -- 2.x
        // removed SimpleAnnotationReader, which is what makes the bare
        // (non-@ORM\-prefixed) annotation style these models use resolve
        // at all.
        $driverImpl = $config->newDefaultAnnotationDriver(APPPATH . 'models');
        $config->setMetadataDriverImpl($driverImpl);


        // Caching
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);

        // Proxies
        $config->setProxyDir(APPPATH . 'models/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setAutoGenerateProxyClasses(false);
        if (ENVIRONMENT === 'development') {
            $config->setAutoGenerateProxyClasses(true);
        }

        // SQL query logger
        if (DEBUGGING) {
            $logger = new \Doctrine\DBAL\Logging\EchoSQLLogger;
            $config->setSQLLogger($logger);
        }


        $connectionOptions = self::getDBDriver($db);

        // Create EntityManager
        $this->em = EntityManager::create($connectionOptions, $config);
    }

    private static function getDBDriver($db)
    {
        if (!isset($db['default']) || !is_array($db['default'])) {
            log_message('error', __METHOD__ . ' ::: database.php conf file is misconfigured. missing array : $db[\'default\']');
            throw new \Exception('system misconfigured');
        }
        $dbconfig = $db['default'];
        if (!isset($dbconfig['dbdriver'])) {
            log_message('error', __METHOD__ . ' ::: database.php conf file is misconfigured. missing : $db[\'default\'][\'dbdriver\']');
            throw new \Exception('system misconfigured');
        }
        $dbriver = $dbconfig['dbdriver'];
        if ($dbriver === 'pdo') {
            if (!isset($dbconfig['dsn'])) {
                log_message('error', __METHOD__ . ' ::: database.php conf file is misconfigured: $db[\'default\'][\'dbdriver\'] is set to "pdo" but $db[\'default\'][\'dsn\'] is missing');
                throw new \Exception('system misconfigured');
            }
            if (preg_match('/([^:]+):/', $dbconfig['dsn'], $match) && count($match) === 2) {
                $dbriver = 'pdo_' . $match['1'];
            }


        } elseif ($dbriver === 'mysql') {
            $dbriver = 'pdo_mysql';
        }

        // Database connection information
        $connectionOptions = array(
            'driver' => $dbriver,
            'user' => $dbconfig['username'],
            'password' => $dbconfig['password'],
            'host' => $dbconfig['hostname'],
            'dbname' => $dbconfig['database']
        );
        if (isset($dbconfig['port'])) {
            $connectionOptions['port'] = $dbconfig['port'];
        }
        if ($dbriver === 'pdo_mysql') {

            if (array_key_exists('ssl',$dbconfig) && ($dbconfig['ssl'] === true  || $dbconfig['ssl'] === 'true' ) && isset($dbconfig['encrypt'])) {
                $connectionOptions['driverOptions'] = array(
                    PDO::MYSQL_ATTR_SSL_CA => $dbconfig['encrypt']['ssl_ca'],
                    PDO::MYSQL_ATTR_SSL_KEY => $dbconfig['encrypt']['ssl_key'],
                    PDO::MYSQL_ATTR_SSL_CERT => $dbconfig['encrypt']['ssl_cert'],
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $dbconfig['encrypt']['ssl_verify']
                );
            }
            if(array_key_exists('char_set',$dbconfig)){
                $connectionOptions['driverOptions']['' . PDO::MYSQL_ATTR_INIT_COMMAND . ''] = 'SET NAMES ' . $dbconfig['char_set'] . '';
            }


        }

        return $connectionOptions;
    }

}
