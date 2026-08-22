<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'honeypot'      => Honeypot::class,
        'cors'          => Cors::class,
    ];

    public array $globals = [
        'before' => [
            // 'csrf' is intentionally not global here: every migrated route is
            // read-only (Dashboard) or session-terminating (Auth::logout) for
            // now. Turn this on per-route as soon as the first state-changing
            // controller is migrated (docs/CI4_MIGRATION.md).
        ],
        'after' => [
            'secureheaders',
        ],
    ];

    public array $methods = [];

    public array $filters = [];
}
