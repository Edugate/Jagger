<?php
define('APPPATH', dirname(__FILE__) . '/');
define('BASEPATH',  '/opt/codeigniter/system/');
define('ENVIRONMENT', 'development');
define('ATTR_DEFAULT_TABLE_COLLATE', 'utf8_general_ci');
define('ATTR_DEFAULT_TABLE_CHARSET', 'utf8');
chdir(APPPATH);

use Doctrine\ORM\Tools\Console\ConsoleRunner;

//$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', 'libraries');
//$classLoader->register();

//$classLoader = new \Doctrine\Common\ClassLoader('Symfony', 'libraries/Doctrine');
//$classLoader->register();

$configFile = getcwd() . '/libraries/Doctrine.php';

$helperSet = null;
if (file_exists($configFile)) {
    if ( ! is_readable($configFile)) {
        trigger_error(
            'Configuration file [' . $configFile . '] does not have read permission.', E_ERROR
        );
    }

    require $configFile;

    foreach ($GLOBALS as $helperSetCandidate) {
        if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
            $helperSet = $helperSetCandidate;
            break;
        }
    }
}
$doctrine = new Doctrine;
$em = $doctrine->em;

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);


