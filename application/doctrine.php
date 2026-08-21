<?php
define('APPPATH', dirname(__FILE__) . '/');
// CodeIgniter 3 is now a Composer dependency (codeigniter/framework, see composer.json)
// instead of a manually-downloaded tarball unpacked to /opt/codeigniter.
define('BASEPATH', APPPATH . 'vendor/codeigniter/framework/system/');
define('ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'production');
define('ATTR_DEFAULT_TABLE_COLLATE', 'utf8_general_ci');
define('ATTR_DEFAULT_TABLE_CHARSET', 'utf8');
chdir(APPPATH);
require_once(APPPATH.'vendor/autoload.php');
use Doctrine\ORM\Tools\Console\ConsoleRunner;

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

// NOTE (doctrine/orm bumped 2.4/2.8 -> ^2.19, see application/composer.json):
// this HelperSet-based ConsoleRunner::run() wiring is the pattern that
// worked on 2.4/2.8. Newer 2.x releases prefer passing an
// EntityManagerProvider instead and may emit a deprecation notice for this
// form, but ORM 2.x's extended compatibility window (EOL pushed to Feb
// 2027) means it should keep working, not break outright. This is
// unverified against a real 2.19 install in this environment (no PHP here
// -- docs/AUDIT.md #6); it's exactly what `./doctrine orm:schema-tool:create`
// exercises in the CI install-and-boot job, so a real failure here will
// surface there, not silently in production.
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);


