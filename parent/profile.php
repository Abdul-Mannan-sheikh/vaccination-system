<?php
// Parent: edit own details and change password.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('parent/profile.php'); }

    if (($_POST['action'] ?? '') === 'profile') {
        $name = trim($_POST['name'] ?? '');  $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');  $addr  = trim($_POST['address'] ?? '');
        $err = [];
        if ($name === '' || mb_strlen($name) > 100) $err[] = 'Please enter your name.';
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $err[] = 'Please enter a valid phone number.';
        if ($city === '') $err[] = 'City is required.';
        if ($err) {
            flash('danger', implode(' ', $err));
        } else {
            $pdo->prepare('UPDATE users SET name = ?, phone = ?, city = ?, address = ? WHERE id = ?')
                ->execute([$name, $phone, $city, $addr ?: null, $user['id']]);
            $_SESSION['user']['name'] = $name;          // keep the name in the top bar up to date
            flash('success', 'Profile updated.');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $error = change_password($user['id'], $_POST['old'] ?? '', $_POST['new'] ?? '', $_POST['confirm'] ?? '');
        flash($error ? 'danger' : 'success', $error ?? 'Password changed.');
    }
    redirect('parent/profile.php');
}

$me = $pdo->prepare('SELECT name, email, phone, city, address, created_at FROM users WHERE id = ?');
$me->execute([$user['id']]);
$me = $me->fetch();

$layout = 'app'; $pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">My profile</h2><p class="text-secondary mb-0">Member since <?= date('M Y', strtotime($me['created_at'])) ?></p></div>

<div class="row g-3">
  <div class="col-lg-7"><form method="post" class="panel h-100"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <h5 class="mb-3">Personal details</h5>
    <div class="row g-3">
      <div class="col-12"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" name="name" value="<?= e($me['name']) ?>" required maxlength="100"></div>
      <div class="col-12"><label class="form-label" for="email">Email</label><input class="form-control" id="email" value="<?= e($me['email']) ?>" disabled>
        <div class="form-text">Your email is your login and cannot be changed.</div></div>
      <div class="col-sm-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="<?= e($me['phone']) ?>" required></div>
      <div class="col-sm-6"><label class="form-label" for="city">City</label><input class="form-control" id="city" name="city" value="<?= e($me['city']) ?>" required></div>
      <div class="col-12"><label class="form-label" for="address">Address</label><input class="form-control" id="address" name="address" value="<?= e($me['address']) ?>"></div>
      <div class="col-12 text-end"><button class="btn btn-vx px-4">Save changes</button></div>
    </div>
  </form></div>

  <div class="col-lg-5"><form method="post" class="panel h-100" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="password">
    <h5 class="mb-3">Change password</h5>
    <div class="mb-3"><label class="form-label" for="old">Current password</label><input class="form-control" type="password" id="old" name="old" required autocomplete="current-password"></div>
    <div class="mb-3"><label class="form-label" for="new">New password</label><input class="form-control" type="password" id="new" name="new" minlength="8" required autocomplete="new-password">
      <div class="form-text">At least 8 characters.</div></div>
    <div class="mb-3"><label class="form-label" for="confirm">Confirm new password</label><input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password"></div>
    <button class="btn btn-vx w-100">Update password</button>
  </form></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>