<?php
// Styled error page for 403 / 404 / 500 (public layout).
require_once __DIR__ . '/includes/functions.php';

$code = (int) ($errorCode ?? $_SERVER['REDIRECT_STATUS'] ?? $_GET['code'] ?? 404);
$pages = [
    403 => ['Access denied', 'You do not have permission to view this page.'],
    404 => ['Page not found', 'The page you are looking for does not exist or has been moved.'],
    500 => ['Something went wrong', 'Please try again in a moment.'],
];
if (!isset($pages[$code])) $code = 404;
[$title, $msg] = $pages[$code];
if (!empty($errorMsg)) $msg = $errorMsg;

http_response_code($code);
$pageTitle = $title; $layout = 'public';
require __DIR__ . '/includes/header.php';
?>
<style>
  .err-wrap { text-align: center; padding: 4rem 1rem; }
  .err-code { font-size: clamp(5rem, 16vw, 9rem); font-weight: 800; line-height: 1; color: var(--accent-color, #0a84a8); }
</style>
<section class="section"><div class="container err-wrap">
  <div class="err-code"><?= $code ?></div>
  <h2><?= e($title) ?></h2>
  <p class="text-muted mb-4"><?= e($msg) ?></p>
  <?php if ($user): ?>
    <a class="btn btn-vx" href="<?= url(dashboard_path($user['role'])) ?>">Back to my dashboard</a>
  <?php else: ?>
    <a class="btn btn-vx" href="<?= url('index.php') ?>">Go to homepage</a>
    <a class="btn btn-outline-vx ms-2" href="<?= url('login.php') ?>">Log in</a>
  <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>