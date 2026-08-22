<?php

/**
 * CI4 requires this file to exist and to define APP_NAMESPACE before
 * app/Config/Autoload.php (which references that constant) is ever
 * instantiated -- system/bootstrap.php requires this file directly, very
 * early in its own bootstrap sequence, before the framework does any of
 * its own autoloading.
 */

/*
 |--------------------------------------------------------------------
 | APP NAMESPACE
 |--------------------------------------------------------------------
 | Must match the psr-4 mapping in app4/composer.json ("App\\": "app/").
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 |--------------------------------------------------------------------
 | COMPOSER PATH
 |--------------------------------------------------------------------
 | Points at this project's own vendor/autoload.php explicitly rather than
 | letting system/bootstrap.php guess it from a systemDirectory-relative
 | path -- see the comment in app4/public/index.php for why (this app's
 | vendor/ layout isn't a standalone CI4 appstarter checkout).
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', APP4_PATH . '/vendor/autoload.php');

/*
 |--------------------------------------------------------------------
 | Exit Status Codes
 |--------------------------------------------------------------------
 | Standard CI4 defaults, unchanged.
 */
defined('EXIT_SUCCESS') || define('EXIT_SUCCESS', 0);
defined('EXIT_ERROR') || define('EXIT_ERROR', 1);
defined('EXIT_CONFIG') || define('EXIT_CONFIG', 3);
defined('EXIT_UNKNOWN_FILE') || define('EXIT_UNKNOWN_FILE', 4);
defined('EXIT_UNKNOWN_CLASS') || define('EXIT_UNKNOWN_CLASS', 5);
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6);
defined('EXIT_USER_INPUT') || define('EXIT_USER_INPUT', 7);
defined('EXIT_RUNTIME') || define('EXIT_RUNTIME', 8);
defined('EXIT_LIBRARY') || define('EXIT_LIBRARY', 9);
defined('EXIT_USAGE') || define('EXIT_USAGE', 64);
defined('EXIT_SOFTWARE') || define('EXIT_SOFTWARE', 70);
defined('EXIT_DATAERR') || define('EXIT_DATAERR', 65);
