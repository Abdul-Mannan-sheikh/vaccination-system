<?php
// All notifications for the logged in user (any role). Visiting the page marks them as read.
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pagination.php';
$user = require_login();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_valid() && ($_POST['action'] ?? '') === 'read_all') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
        flash('success', 'All notifications marked as read.');
    }
    redirect('notifications.php');
}

$pg = paginate(scalar_count('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$user['id']]), 10);
$stmt = $pdo->prepare("SELECT title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Mark as read AFTER fetching, so this visit can still show which ones were new
$pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$user['id']]);

$layout = 'app'; $pageTitle = 'Notifications';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Notifications</h2><p class="text-secondary mb-0"><?= $pg['total'] ?> in total</p></div>

<div class="panel">
  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-bell"></i>No notifications yet.</div>
  <?php else: foreach ($rows as $n): ?>
    <div class="d-flex gap-3 py-3 border-bottom">
      <i class="bi bi-bell<?= $n['is_read'] ? '' : '-fill' ?> text-primary mt-1"></i>
      <div class="flex-grow-1"><b><?= e($n['title']) ?></b><?= $n['is_read'] ? '' : ' <span class="tag next">New</span>' ?>
        <div class="text-secondary"><?= e($n['message']) ?></div>
        <div class="text-secondary small"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></div></div>
    </div>
  <?php endforeach; ?>
  <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>