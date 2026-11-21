<?php
// Reset password using the one-time link from forgot.php.
require_once __DIR__ . '/includes/functions.php';
redirect_if_logged_in();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$hash  = hash('sha256', (string) $token);

// A valid link: not used, not expired
$q = db()->prepare('SELECT pr.id, pr.user_id, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id
                    WHERE pr.token_hash = ? AND pr.used = 0 AND pr.expires_at > NOW() LIMIT 1');
$q->execute([$hash]);
$reset = $q->fetch();

$errors = [];
if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new'] ?? ''; $confirm = $_POST['confirm'] ?? '';
    if (!csrf_valid())          $errors[] = 'Your session expired. Please try again.';
    elseif (strlen($new) < 8)   $errors[] = 'Password must be at least 8 characters.';
    elseif ($new !== $confirm)  $errors[] = 'Passwords do not match.';
    else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$reset['user_id']]);
        $pdo->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$reset['email']]);   // unblock the account too
        $pdo->commit();
        flash('success', 'Password changed. You can log in now.');
        redirect('login.php');
    }
}

$pageTitle = 'Reset password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-wrap"><div class="auth-card">
  <?php if (!$reset): ?>
    <h2 class="mb-2">Link not valid</h2>
    <p class="text-secondary">This reset link is invalid, already used, or has expired.</p>
    <a class="btn btn-vx" href="<?= url('forgot.php') ?>">Get a new link</a>
  <?php else: ?>
    <h2 class="mb-1">Choose a new password</h2>
    <p class="text-secondary">For <?= e($reset['email']) ?></p>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="mb-3"><label class="form-label" for="new">New password</label>
        <input class="form-control" type="password" id="new" name="new" minlength="8" required autofocus autocomplete="new-password">
        <div class="form-text">At least 8 characters.</div></div>
      <div class="mb-4"><label class="form-label" for="confirm">Confirm password</label>
        <input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password"></div>
      <button class="btn btn-vx w-100 py-2" type="submit">Save password</button>
    </form>
  <?php endif; ?>
</div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>