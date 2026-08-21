<?php

namespace Config;

/**
 * Path overrides for the app4 CI4 shell. Kept separate from a normal CI4
 * project layout because app4 lives inside the Jagger repo root rather than
 * being the repo root itself.
 */
class Paths
{
    public string $systemDirectory = APP4_PATH . '/vendor/codeigniter4/framework/system';

    public string $appDirectory = APP4_PATH . '/app';

    public string $writableDirectory = APP4_PATH . '/writable';

    public string $testsDirectory = APP4_PATH . '/tests';

    public string $viewDirectory = APP4_PATH . '/app/Views';
}
