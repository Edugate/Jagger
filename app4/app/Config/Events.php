<?php

namespace Config;

use CodeIgniter\Events\Events;

/**
 * Required to exist (system/bootstrap.php requires app/Config/Events.php
 * directly) even with nothing registered -- Jagger doesn't currently need
 * any CI4 event hooks, unlike the legacy CI3 application/config/hooks.php
 * (which is itself empty -- see docs/AUDIT.md).
 */

// (intentionally no Events::on(...) registrations yet)
