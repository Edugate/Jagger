<?php
/**
 * CodeIgniter 4 front controller for migrated Jagger routes.
 *
 * Not served directly by Apache/PHP-FPM: the repo-root index.php (legacy
 * CodeIgniter 3 front controller) requires this file when the requested
 * URI matches a prefix listed in app4_routes.php. See docs/CI4_MIGRATION.md.
 */

define('APP4_START', microtime(true));

// The vendored CI4 framework and Doctrine live under app4/vendor (this
// application's own Composer install, kept separate from application/vendor
// which holds the legacy CI3 dependency tree — the two frameworks do not
// share a dependency graph during the transition).
define('APP4_PATH', realpath(__DIR__ . '/..'));

// Config\Paths and Config\Constants (APP_NAMESPACE, needed by
// Config\Autoload) are required directly, not via Composer autoload: they
// tell the framework where everything else -- including vendor/ itself --
// lives, so they have to work before any autoloading exists. Required here
// explicitly rather than trusting system/bootstrap.php to require
// Constants.php itself at the right point, since this project's vendor/
// layout differs from a standalone CI4 appstarter checkout (see the
// vendor/autoload.php comment below) and an undefined APP_NAMESPACE is a
// PHP 8 fatal error, not a warning.
require APP4_PATH . '/app/Config/Paths.php';
require APP4_PATH . '/app/Config/Constants.php';

// Required explicitly here rather than relying on system/bootstrap.php to
// find it via a systemDirectory-relative guess, since this project's
// vendor/ layout (Composer-installed CI4 as one dependency among others,
// not a standalone appstarter checkout) may not match what that guess
// assumes. require_once further down is a harmless no-op if bootstrap.php
// also loads it.
require APP4_PATH . '/vendor/autoload.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);

exit($app->run());
