<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    public array $psr4 = [
        APP_NAMESPACE => APPPATH,
    ];

    public array $classmap = [];

    public array $files = [];
}
