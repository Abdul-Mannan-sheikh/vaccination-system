<?php
// Forgot password: creates a one-time reset link that expires in 30 minutes.
require_once __DIR__ . '/includes/functions.php';
redirect_if_logged_in();

$done = false; $link = null; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (time() - ($_SESSION['last_reset'] ?? 0) < 20) {
        $errors[] = 'Please wait a few seconds before trying again.';
    } else {
        $_SESSION['last_reset'] = time();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $u = db()->prepare("SELECT id, name FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $u->execute([$email]);
        $row = $u->fetch();

        if ($row) {
            db()->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$row['id']]);   // old links stop working
            $token = bin2hex(random_bytes(32));                                    // the secret sent to the user
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                ->execute([$row['id'], hash('sha256', $token)]);                   // only the hash is stored
            $link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('reset.php?token=' . $token);

            if (!SHOW_RESET_LINK) {
                @mail($email, 'Reset your ' . APP_NAME . ' password',
                      "Hello " . $row['name'] . ",\n\nOpen this link to choose a new password (valid for 30 minutes):\n$link\n\nIf you did not ask for this, ignore this email.",
                      'From: no-reply@vaxtrack.local');
                $link = null;
            }
        }
        $done = true;     // same message whether or not the email exists
    }
}

$pageTitle = 'Forgot password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-wrap"><div class="auth-card">
  <h2 class="mb-1">Forgot password?</h2>
  <p class="text-secondary">Enter your email and we will give you a link to choose a new password.</p>

  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

  <?php if ($done): ?>
    <div class="alert alert-success py-2">If that email is registered, a reset link has been prepared. It works for 30 minutes.</div>
    <?php if ($link): ?>
      <div class="alert alert-warning small">
        <b>Demo mode:</b> this computer has no mail server, so the link is shown here instead of being e-mailed.<br>
        <a href="<?= e($link) ?>">Choose a new password</a>
      </div>
    <?php endif; ?>
    <a href="<?= url('login.php') ?>">Back to log in</a>
  <?php else: ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-4"><label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus></div>
      <button class="btn btn-vx w-100 py-2" type="submit">Send reset link</button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="<?= url('login.php') ?>">Back to log in</a></p>
  <?php endif; ?>
</div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>