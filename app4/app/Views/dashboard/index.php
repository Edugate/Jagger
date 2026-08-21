<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title><?= esc($title) ?></title></head>
<body>
<p>Welcome, <?= esc($username) ?>. (<a href="<?= site_url('auth/logout') ?>">Log out</a>)</p>
<p><em>This is the migrated CI4 dashboard placeholder — the full widget/stats
dashboard from application/controllers/Dashboard.php is not migrated yet.</em></p>
</body>
</html>
