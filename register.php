<?php
// Registration for Parents and Hospitals (admin is created by the SQL seed).
require_once __DIR__ . '/includes/functions.php';
redirect_if_logged_in();

$errors = [];
$role   = $_POST['role'] ?? 'parent';
if (!in_array($role, ['parent', 'hospital'], true)) {
    $role = 'parent';                    // never trust the posted role
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        // ---- validation ----
        if ($name === '' || mb_strlen($name) > 100)             $errors[] = 'Please enter a valid name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Please enter a valid email address.';
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone))        $errors[] = 'Please enter a valid phone number.';
        if ($city === '')                                       $errors[] = 'City is required.';
        if ($role === 'hospital' && $address === '')            $errors[] = 'Hospital address is required.';
        if (strlen($password) < 8)                              $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)                             $errors[] = 'Passwords do not match.';

        if (!$errors) {
            $check = db()->prepare('SELECT 1 FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email already exists.';
            }
        }

        // ---- save ----
        if (!$errors) {
            $pdo = db();
            try {
                $pdo->beginTransaction();         // users + hospitals rows succeed or fail together

                $ins = $pdo->prepare(
                    'INSERT INTO users (name, email, password, phone, address, city, role)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT),
                               $phone, $address ?: null, $city, $role]);
                $userId = (int) $pdo->lastInsertId();

                if ($role === 'hospital') {
                    $h = $pdo->prepare('INSERT INTO hospitals (user_id, name, address, city, phone) VALUES (?, ?, ?, ?, ?)');
                    $h->execute([$userId, $name, $address, $city, $phone]);
                }

                $pdo->commit();
                flash('success', 'Account created. You can log in now.');
                redirect('login.php');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = 'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-wrap">
  <div class="auth-card">
    <h2 class="mb-1">Create your account</h2>
    <p class="text-secondary">It takes less than a minute.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <div class="role-switch mb-3" role="radiogroup" aria-label="Account type">
        <div><input type="radio" name="role" id="r-parent" value="parent" <?= $role === 'parent' ? 'checked' : '' ?>>
          <label for="r-parent"><i class="bi bi-person-hearts me-1"></i>I'm a parent</label></div>
        <div><input type="radio" name="role" id="r-hospital" value="hospital" <?= $role === 'hospital' ? 'checked' : '' ?>>
          <label for="r-hospital"><i class="bi bi-hospital me-1"></i>I'm a hospital</label></div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="name" id="name-label"><?= $role === 'hospital' ? 'Hospital name' : 'Full name' ?></label>
        <input class="form-control" id="name" name="name" value="<?= old('name') ?>" required>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-sm-6"><label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required></div>
        <div class="col-sm-6"><label class="form-label" for="phone">Phone</label>
          <input class="form-control" id="phone" name="phone" value="<?= old('phone') ?>" placeholder="03xx-xxxxxxx" required></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-sm-5"><label class="form-label" for="city">City</label>
          <input class="form-control" id="city" name="city" value="<?= old('city') ?>" required></div>
        <div class="col-sm-7"><label class="form-label" for="address">Address <span class="text-secondary fw-normal" id="addr-hint">(optional)</span></label>
          <input class="form-control" id="address" name="address" value="<?= old('address') ?>"></div>
      </div>
      <div class="row g-3 mb-4">
        <div class="col-sm-6"><label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" minlength="8" required>
          <div class="form-text">At least 8 characters.</div></div>
        <div class="col-sm-6"><label class="form-label" for="confirm">Confirm password</label>
          <input class="form-control" type="password" id="confirm" name="confirm" required></div>
      </div>
      <button class="btn btn-vx w-100 py-2" type="submit">Create account</button>
    </form>
    <p class="text-center mt-3 mb-0">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
  </div>
</div>
</div></section>

<script>
// Adjust labels when the user switches between parent and hospital
document.querySelectorAll('input[name="role"]').forEach(function (r) {
  r.addEventListener('change', function () {
    var isHospital = this.value === 'hospital';
    document.getElementById('name-label').textContent = isHospital ? 'Hospital name' : 'Full name';
    document.getElementById('addr-hint').textContent  = isHospital ? '(required)' : '(optional)';
  });
});
document.getElementById('addr-hint').textContent =
  document.getElementById('r-hospital').checked ? '(required)' : '(optional)';
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
