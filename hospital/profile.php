<?php
// Hospital: edit hospital details (name, address, location) and change password.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('hospital');
$pdo  = db();

$h = $pdo->prepare('SELECT * FROM hospitals WHERE user_id = ? LIMIT 1');
$h->execute([$user['id']]);
$hospital = $h->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('hospital/profile.php'); }

    if (($_POST['action'] ?? '') === 'profile' && $hospital) {
        $name = trim($_POST['name'] ?? '');   $addr = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');   $phone = trim($_POST['phone'] ?? '');
        $lat  = trim($_POST['latitude'] ?? ''); $lng = trim($_POST['longitude'] ?? '');
                $cap  = (int) ($_POST['slot_capacity'] ?? 3);
        $err = [];
        if ($name === '' || mb_strlen($name) > 150) $err[] = 'Hospital name is required.';
        if ($addr === '') $err[] = 'Address is required.';
        if ($city === '') $err[] = 'City is required.';
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $err[] = 'Please enter a valid phone number.';
        if ($lat !== '' && (!is_numeric($lat) || $lat < -90 || $lat > 90))    $err[] = 'Latitude must be between -90 and 90.';
        if ($lng !== '' && (!is_numeric($lng) || $lng < -180 || $lng > 180))  $err[] = 'Longitude must be between -180 and 180.';
        
        if ($err) {
            flash('danger', implode(' ', $err));
        } else {
            $pdo->beginTransaction();
                       $pdo->prepare('UPDATE hospitals SET name=?, address=?, city=?, phone=?, latitude=?, longitude=?, slot_capacity=? WHERE id=?')
                ->execute([$name, $addr, $city, $phone, $lat === '' ? null : $lat, $lng === '' ? null : $lng, $cap, $hospital['id']]);
            $pdo->prepare('UPDATE users SET name=?, address=?, city=?, phone=? WHERE id=?')->execute([$name, $addr, $city, $phone, $user['id']]);
            $pdo->commit();
            $_SESSION['user']['name'] = $name;
            flash('success', 'Hospital profile updated.');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $error = change_password($user['id'], $_POST['old'] ?? '', $_POST['new'] ?? '', $_POST['confirm'] ?? '');
        flash($error ? 'danger' : 'success', $error ?? 'Password changed.');
    }
    redirect('hospital/profile.php');
}

$layout = 'app'; $pageTitle = 'Hospital Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Hospital profile</h2><p class="text-secondary mb-0">Parents see these details when they book.</p></div>

<?php if (!$hospital): ?>
  <div class="panel"><div class="empty"><i class="bi bi-exclamation-circle"></i>No hospital profile is linked to this account.</div></div>
<?php else: ?>
<div class="row g-3">
  <div class="col-lg-7"><form method="post" class="panel h-100"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <h5 class="mb-3">Details</h5>
    <div class="row g-3">
      <div class="col-12"><label class="form-label" for="name">Hospital name</label><input class="form-control" id="name" name="name" value="<?= e($hospital['name']) ?>" required maxlength="150"></div>
      <div class="col-12"><label class="form-label" for="address">Address</label><input class="form-control" id="address" name="address" value="<?= e($hospital['address']) ?>" required></div>
      <div class="col-sm-6"><label class="form-label" for="city">City</label><input class="form-control" id="city" name="city" value="<?= e($hospital['city']) ?>" required></div>
      <div class="col-sm-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="<?= e($hospital['phone']) ?>" required></div>
      <div class="col-sm-6"><label class="form-label" for="latitude">Latitude <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control" id="latitude" name="latitude" value="<?= e($hospital['latitude']) ?>" placeholder="24.8607"></div>
      <div class="col-sm-6"><label class="form-label" for="longitude">Longitude <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control" id="longitude" name="longitude" value="<?= e($hospital['longitude']) ?>" placeholder="67.0011"></div>
            <div class="col-sm-6"><label class="form-label" for="slot_capacity">Patients per time slot</label><input class="form-control" type="number" min="1" max="50" id="slot_capacity" name="slot_capacity" value="<?= (int) ($hospital['slot_capacity'] ?? 3) ?>" required>
        <div class="form-text">Parents cannot book a slot once it is full.</div></div>
      <div class="col-12 text-end"><button class="btn btn-vx px-4">Save changes</button></div>
    </div>
  </form></div>

  <div class="col-lg-5"><form method="post" class="panel h-100" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="password">
    <h5 class="mb-3">Change password</h5>
    <div class="mb-3"><label class="form-label" for="old">Current password</label><input class="form-control" type="password" id="old" name="old" required autocomplete="current-password"></div>
    <div class="mb-3"><label class="form-label" for="new">New password</label><input class="form-control" type="password" id="new" name="new" minlength="8" required autocomplete="new-password"></div>
    <div class="mb-3"><label class="form-label" for="confirm">Confirm new password</label><input class="form-control" type="password" id="confirm" name="confirm" required autocomplete="new-password"></div>
    <button class="btn btn-vx w-100">Update password</button>
  </form></div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>