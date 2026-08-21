<?php
/**
 * URI prefixes dispatched to the CodeIgniter 4 application (app4/) instead
 * of the legacy CodeIgniter 3 application. Read by the repo-root index.php.
 *
 * Keep in sync with app4/app/Config/Routes.php and
 * docs/CI4_MIGRATION.md — a prefix listed here with no matching CI4 route
 * (or vice versa) is a bug, not a valid intermediate state.
 */
return [
    'dashboard',
    'auth/logout',
];
