<?php
// Login for all three roles; redirects to the matching dashboard.
require_once __DIR__ . '/includes/functions.php';
redirect_if_logged_in();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (login_blocked($email)) {
            $errors[] = 'Too many failed attempts. Please wait 10 minutes and try again.';
        } else {
            $stmt = db()->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $row = $stmt->fetch();

            // Same message for "no such user" and "wrong password" so attackers learn nothing
            if (!$row || !password_verify($password, $row['password'])) {
                login_failed($email);
                $errors[] = 'Incorrect email or password.';
            } elseif ($row['status'] !== 'active') {
                $errors[] = 'This account has been blocked. Please contact the administrator.';
            } else {
                login_cleared($email);
                session_regenerate_id(true);          // prevents session fixation
                $_SESSION['user'] = ['id' => (int) $row['id'], 'name' => $row['name'], 'role' => $row['role']];
                flash('success', 'Welcome back, ' . $row['name'] . '!');
                redirect(dashboard_path($row['role']));
            }
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-wrap">
  <div class="auth-card">
    <h2 class="mb-1">Log in</h2>
    <p class="text-secondary">Parents, hospitals and admins all sign in here.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
            <div class="text-end mb-3" style="margin-top:-1rem"><a href="<?= url('forgot.php') ?>">Forgot password?</a></div>
      <button class="btn btn-vx w-100 py-2" type="submit">Log in</button>
    </form>
    <p class="text-center mt-3 mb-0">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
  </div>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>