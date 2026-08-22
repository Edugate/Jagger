<?php

namespace App\Controllers;

/**
 * Migrated reference slice, chosen as the pattern to follow for the
 * remaining controllers (docs/CI4_MIGRATION.md) because it is read-only:
 * no form submission, no state change, so a mistake here can't corrupt
 * data or create an auth bypass the way a mis-migrated write path could.
 *
 * Mirrors application/controllers/Dashboard.php::showFrontPage() — the
 * front-page/migration-status display shown to logged-out visitors and
 * the dashboard shown to logged-in users. The rest of the legacy
 * Dashboard controller (widgets, stats, per-user preferences) is not
 * migrated yet; requests for those stay on CodeIgniter 3.
 */
class Dashboard extends BaseController
{
    public function index()
    {
        if (!$this->isLoggedIn()) {
            /**
             * @var \models\Staticpage|null $frontpage
             */
            $frontpage = $this->em->getRepository('models\Staticpage')
                ->findOneBy(['pcode' => 'front_page', 'enabled' => true, 'ispublic' => true]);

            return view('dashboard/front_page', [
                'title'   => 'Jagger',
                'content' => $frontpage?->getContent(),
            ]);
        }

        return view('dashboard/index', [
            'title'    => 'Dashboard',
            'username' => $this->legacySession['username'],
        ]);
    }
}
