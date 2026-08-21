<?php

/**
 * Migrated CI4 routes. Each entry here must have a matching prefix in
 * ../../app4_routes.php (repo root) so the legacy front controller knows to
 * dispatch into this application instead of CodeIgniter 3. See
 * docs/CI4_MIGRATION.md for the full controller-by-controller status.
 */

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('dashboard', 'Dashboard::index');
$routes->get('auth/logout', 'Auth::logout');
