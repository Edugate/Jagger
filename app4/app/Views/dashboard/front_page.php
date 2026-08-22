<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title><?= esc($title) ?></title></head>
<body>
<?php if ($content !== null): ?>
    <?= $content /* Staticpage content is trusted admin-authored HTML, same as the CI3 front page (jaggerTagsReplacer) */ ?>
<?php else: ?>
    <p>Welcome. <a href="<?= site_url('auth/login') ?>">Log in</a></p>
<?php endif; ?>
</body>
</html>
