<?php
/**
 * Jagger front controller.
 *
 * CodeIgniter 3 (`codeigniter/framework`) is now installed as a Composer
 * dependency instead of being hand-copied from a separately-downloaded
 * tarball into /opt/codeigniter (see application/composer.json and
 * docs/AUDIT.md). This file replaces the manual "cp
 * /opt/codeigniter/index.php" step from the old INSTALL.md.
 *
 * Requests that match a route migrated to CodeIgniter 4 are dispatched
 * there instead; everything else falls through to the legacy CodeIgniter 3
 * application. See docs/CI4_MIGRATION.md for the migrated-route list and
 * the reasoning behind running both frameworks side by side.
 */

date_default_timezone_set(getenv('TZ') ?: 'UTC');

// PHP version check (CodeIgniter 3 core requirement, kept from stock index.php).
if (version_compare(PHP_VERSION, '5.6.0', '<') === true) {
    exit('Your PHP version is ' . PHP_VERSION . '. CodeIgniter requires PHP 5.6 or newer.');
}

/*
 * --------------------------------------------------------------------
 * CI4 STRANGLER-FIG DISPATCH
 * --------------------------------------------------------------------
 * A small, explicit allow-list of URI prefixes that have been migrated to
 * CodeIgniter 4 live in app4_routes.php. Everything not on that list keeps
 * running on the legacy CodeIgniter 3 application below, unchanged.
 */
$ci4RoutesFile = __DIR__ . '/app4_routes.php';
if (is_file($ci4RoutesFile)) {
    $migratedPrefixes = require $ci4RoutesFile;
    $requestUri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
    foreach ($migratedPrefixes as $prefix) {
        if ($requestUri === $prefix || strncmp($requestUri, $prefix . '/', strlen($prefix) + 1) === 0) {
            require __DIR__ . '/app4/public/index.php';
            return;
        }
    }
}

/*
 * --------------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 * --------------------------------------------------------------------
 * Set via the systemd unit (Environment=CI_ENVIRONMENT=production) rather
 * than hard-coded, so the same file works unmodified in every deployment.
 */
define('ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'production');

/*
 * --------------------------------------------------------------------
 * APPLICATION FOLDER NAME / SYSTEM FOLDER PATH
 * --------------------------------------------------------------------
 */
$system_path = __DIR__ . '/application/vendor/codeigniter/framework/system';
$application_folder = __DIR__ . '/application';

/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 */
$route['default_controller'] = 'welcome';

/*
 * --------------------------------------------------------------------
 * VIEW EXTENSION
 * --------------------------------------------------------------------
 */
$view_extension = '.php';

/*
 * --------------------------------------------------------------------
 * SUB-APPLICATION MULTI-ENVIRONMENT SUPPORT
 * --------------------------------------------------------------------
 * Not used by Jagger; kept FALSE to match stock CodeIgniter 3 behavior.
 */
if (is_file($system_path . '/core/CodeIgniter.php') === false) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your system folder path (' . $system_path . ') does not appear to be set correctly.'
        . ' Run "composer install" in application/ to install codeigniter/framework.';
    exit(3);
}

$system_path = rtrim($system_path, '/\\') . '/';
if (realpath($application_folder) !== false) {
    $application_folder = realpath($application_folder);
}
$application_folder = rtrim($application_folder, '/\\');

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH', str_replace('\\', '/', $system_path));
define('FCPATH', str_replace(SELF, '', __FILE__));
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));

if (is_dir($application_folder)) {
    define('APPPATH', $application_folder . '/');
} else {
    if (!is_dir(BASEPATH . $application_folder . '/')) {
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'Your application folder path does not appear to be set correctly.';
        exit(3);
    }
    define('APPPATH', BASEPATH . $application_folder . '/');
}

require_once BASEPATH . 'core/CodeIgniter.php';
