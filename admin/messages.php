<?php
// Admin: messages sent through the homepage contact form.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('admin');
$pdo  = db();

$f = ($_GET['f'] ?? '') === 'unread' ? 'unread' : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_valid()) {
        $id = (int) ($_POST['id'] ?? 0);
        switch ($_POST['action'] ?? '') {
            case 'read':   $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]); break;
            case 'unread': $pdo->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$id]); break;
            case 'delete': $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]); flash('success', 'Message deleted.'); break;
        }
    }
    redirect('admin/messages.php?f=' . $f);
}

$unread = scalar_count('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
$where  = $f === 'unread' ? 'WHERE is_read = 0' : '';
$pg = paginate(scalar_count("SELECT COUNT(*) FROM contact_messages $where"), 8);
$rows = $pdo->query("SELECT * FROM contact_messages $where ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}")->fetchAll();

$layout = 'app'; $pageTitle = 'Messages';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Messages</h2><p class="text-secondary mb-0">Sent through the contact form on the homepage.</p></div>

<div class="panel">
  <div class="d-flex gap-1 mb-3">
    <a class="btn btn-sm <?= $f === 'all' ? 'btn-vx' : 'btn-ghost' ?>" href="?f=all">All</a>
    <a class="btn btn-sm <?= $f === 'unread' ? 'btn-vx' : 'btn-ghost' ?>" href="?f=unread">Unread (<?= $unread ?>)</a>
  </div>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-envelope-open"></i>No messages here.</div>
  <?php else: foreach ($rows as $m): ?>
    <div class="py-3 border-bottom">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div><b><?= e($m['subject']) ?></b> <?= $m['is_read'] ? '' : '<span class="tag next">New</span>' ?>
          <div class="small text-secondary"><?= e($m['name']) ?> &middot; <?= e($m['email']) ?> &middot; <?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></div></div>
        <div class="act">
          <a class="btn btn-ghost btn-sm" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>"><i class="bi bi-reply"></i> Reply</a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
            <input type="hidden" name="action" value="<?= $m['is_read'] ? 'unread' : 'read' ?>">
            <button class="btn btn-ghost btn-sm"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
          <form method="post" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="delete">
            <button class="btn btn-ghost btn-sm text-danger" aria-label="Delete message"><i class="bi bi-trash"></i></button></form>
        </div>
      </div>
      <p class="mt-2 mb-0"><?= nl2br(e($m['message'])) ?></p>
    </div>
  <?php endforeach; ?>
  <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>