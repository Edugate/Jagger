<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Configuration;

define('DEBUGGING', FALSE);
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
 * Doctrine Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Doctrine {

    public $em = null;

    public function __construct() {
        // load database configuration and custom config from CodeIgniter
        require APPPATH . 'config/database.php';

        // Set up class loading.
        require_once APPPATH . 'libraries/Doctrine/Common/ClassLoader.php';

        $doctrineClassLoader = new \Doctrine\Common\ClassLoader('Doctrine', APPPATH . 'libraries');
        $doctrineClassLoader->register();

        $entitiesClassLoader = new \Doctrine\Common\ClassLoader('models', rtrim(APPPATH, '/'));
        $entitiesClassLoader->register();

        $proxiesClassLoader = new \Doctrine\Common\ClassLoader('Proxies', APPPATH . 'models');
        $proxiesClassLoader->register();

        $symfonyClassLoader = new \Doctrine\Common\ClassLoader('Symfony', APPPATH . 'libraries/Doctrine');
        $symfonyClassLoader->register();

        // Choose caching method based on application mode
        if (ENVIRONMENT === 'production' && extension_loaded('apc') && ini_get('apc.enabled')) {
            $cache = new \Doctrine\Common\Cache\ApcCache;
            //	$memcache = new \Memcache();
            //	$memcache->connect('127.0.0.1', 11211);
            //	$cache = new \Doctrine\Common\Cache\MemcacheCache();
            //	$cache->setMemcache($memcache);
            //	$cache->save('cache_id', 'my_data');
        } else {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
        }
        /**
         * @todo fix memcache
         */
        //	$memcache = new Memcache();
        //	$memcache->connect('127.0.0.1',11211);
        //	$cache = new \Doctrine\Common\Cache\MemcacheCache();
        //	$cache->setMemcache($memcache);
        //	$cache->save('cache_id','my_data');

        /**
         * end test
         */
        // Set some configuration options
        $config = new Configuration;

        // Metadata driver
        $driverImpl = $config->newDefaultAnnotationDriver(APPPATH . 'models');
        $config->setMetadataDriverImpl($driverImpl);



        // Caching
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);

        // Proxies
        $config->setProxyDir(APPPATH . 'models/Proxies');
        $config->setProxyNamespace('Proxies');

        if (ENVIRONMENT === 'development') {
            $config->setAutoGenerateProxyClasses(TRUE);
        } else {
            $config->setAutoGenerateProxyClasses(FALSE);
        }

        // SQL query logger
        if (DEBUGGING) {
            $logger = new \Doctrine\DBAL\Logging\EchoSQLLogger;
            $config->setSQLLogger($logger);
        }
        
        /** 
         * keep compatibility with old configs
         */
        $dbriver = $db['default']['dbdriver'];
        if($dbriver === 'mysql')
        {
           $dbriver = 'pdo_mysql';
        }
        // Database connection information
        $connectionOptions = array(
            'driver' => $dbriver,
            'user' => $db['default']['username'],
            'password' => $db['default']['password'],
            'host' => $db['default']['hostname'],
            'dbname' => $db['default']['database']
        );

        // Create EntityManager
        $this->em = EntityManager::create($connectionOptions, $config);
    }

}
