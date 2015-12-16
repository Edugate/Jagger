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

        $entitiesClassLoader = new \Doctrine\Common\ClassLoader('models', rtrim(APPPATH, '/'));
        $entitiesClassLoader->register();

        $proxiesClassLoader = new \Doctrine\Common\ClassLoader('Proxies', APPPATH . 'models');
        $proxiesClassLoader->register();


        // Choose caching method based on application mode
        if (ENVIRONMENT === 'production' && extension_loaded('apc') && ini_get('apc.enabled')) {
            $cache = new \Doctrine\Common\Cache\ApcCache;
        } else {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
        }
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
            $config->setAutoGenerateProxyClasses(true);
        } else {
            $config->setAutoGenerateProxyClasses(false);
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
        if($dbriver === 'mysql'){
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
        if(isset($db['default']['port'])) {
	    $connectionOptions['port'] = $db['default']['port'];                                                                                                                                                  
	}    

        // Create EntityManager
        $this->em = EntityManager::create($connectionOptions, $config);
    }

}
