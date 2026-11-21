<?php
/**
 * Shared header. Before including set:
 *   $pageTitle  - browser title
 *   $layout     - 'public' (top navbar, default) or 'app' (sidebar dashboard)
 */
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Welcome';
$layout    = $layout ?? 'public';
$user      = current_user();
$theme     = current_theme();
$cur       = basename($_SERVER['SCRIPT_NAME']);

// Notification bell data (only needed on dashboard pages)
$unread = 0; $recent = [];
if ($layout === 'app' && $user) {
    $unread = scalar_count('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']]);
    $nq = db()->prepare('SELECT title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5');
    $nq->execute([$user['id']]);
    $recent = $nq->fetchAll();
}

// [path, label, icon, built?]  - unbuilt pages show a "Soon" tag until their module is finished
$menus = [
                   'admin' => [
        ['admin/dashboard.php', 'Dashboard', 'bi-grid-1x2', 1], ['admin/hospitals.php', 'Hospitals', 'bi-hospital', 1],
        ['admin/vaccines.php', 'Vaccines', 'bi-capsule', 1], ['admin/bookings.php', 'Bookings', 'bi-calendar2-check', 1],
        ['admin/children.php', 'Children', 'bi-emoji-smile', 1], ['admin/reports.php', 'Reports', 'bi-bar-chart', 1],
        ['admin/messages.php', 'Messages', 'bi-envelope', 1],
    ],
                    'parent' => [
        ['parent/dashboard.php', 'Dashboard', 'bi-grid-1x2', 1], ['parent/children.php', 'My children', 'bi-emoji-smile', 1],
        ['parent/book.php', 'Book vaccine', 'bi-calendar-plus', 1], ['parent/bookings.php', 'My bookings', 'bi-calendar2-check', 1],
        ['parent/report.php', 'Vaccination report', 'bi-file-earmark-medical', 1], ['parent/profile.php', 'My profile', 'bi-person', 1],
    ],
    'hospital' => [
        ['hospital/dashboard.php', 'Dashboard', 'bi-grid-1x2', 1], ['hospital/appointments.php', 'Appointments', 'bi-calendar2-check', 1],
        ['hospital/log.php', 'Vaccination log', 'bi-journal-medical', 1], ['hospital/profile.php', 'Hospital profile', 'bi-building', 1],
    ],
];

$switcher = '<div class="theme-switch" role="group" aria-label="Choose look">';
foreach (['medical' => '#0a84a8', 'gradient' => '#7c4dff', 'dark' => '#0b1020'] as $key => $dot) {
    $switcher .= '<button type="button" data-set-theme="' . $key . '" class="' . ($theme === $key ? 'on' : '') .
                 '" title="' . ucfirst($key) . ' look" aria-label="' . ucfirst($key) . ' look"><span style="background:' . $dot . '"></span></button>';
}
$switcher .= '</div>';

// Public pages (home, login, register) use the MediCio template layout
if ($layout === 'public') {
    require __DIR__ . '/public_header.php';
    return;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>" data-bs-theme="<?= $theme === 'dark' ? 'dark' : 'light' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="layout-<?= e($layout) ?>">
<?php if ($layout === 'app' && $user): ?>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="<?= url(dashboard_path($user['role'])) ?>"><span class="brand-mark"><i class="bi bi-shield-plus"></i></span><?= APP_NAME ?></a>
    <nav class="side-nav">
      <?php foreach ($menus[$user['role']] as [$path, $label, $icon, $built]): ?>
        <?php if ($built): ?>
          <a href="<?= url($path) ?>" class="<?= basename($path) === $cur ? 'active' : '' ?>"><i class="bi <?= $icon ?>"></i><?= e($label) ?></a>
        <?php else: ?>
          <span class="soon"><i class="bi <?= $icon ?>"></i><?= e($label) ?><em>Soon</em></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <a class="side-logout" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-left"></i>Log out</a>
  </aside>
  <div class="app-main">
    <header class="topbar">
      <button class="icon-btn d-lg-none" data-toggle-side aria-label="Menu"><i class="bi bi-list"></i></button>
      <strong class="topbar-title"><?= e($pageTitle) ?></strong>
           <div class="ms-auto d-flex align-items-center gap-3">
        <div class="dropdown">
          <button class="icon-btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications, <?= $unread ?> unread">
            <i class="bi bi-bell"></i><?php if ($unread): ?><span class="bell-badge"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end bell-menu p-0">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
              <b>Notifications</b>
              <?php if ($unread): ?>
                <form method="post" action="<?= url('notifications.php') ?>" class="m-0"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="read_all"><button class="btn btn-link btn-sm p-0">Mark all read</button></form>
              <?php endif; ?>
            </div>
            <?php if (!$recent): ?>
              <div class="p-3 text-secondary small">You are all caught up.</div>
            <?php else: foreach ($recent as $n): ?>
              <a class="bell-item <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= url('notifications.php') ?>">
                <b><?= e($n['title']) ?></b><span><?= e($n['message']) ?></span><small><?= date('d M, h:i A', strtotime($n['created_at'])) ?></small></a>
            <?php endforeach; endif; ?>
            <a class="d-block text-center py-2 border-top small" href="<?= url('notifications.php') ?>">View all</a>
          </div>
        </div>
        <?= $switcher ?>
        <span class="who"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?= e($user['name']) ?></span>
          <span class="role-pill"><?= e($user['role']) ?></span></span>
      </div>
    </header>
    <main class="app-content">
<?php else: ?>
<nav class="pub-nav">
  <div class="container d-flex align-items-center gap-3">
    <a class="brand" href="<?= $user ? url(dashboard_path($user['role'])) : url('index.php') ?>"><span class="brand-mark"><i class="bi bi-shield-plus"></i></span><?= APP_NAME ?></a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <?= $switcher ?>
      <?php if ($user): ?>
        <a class="btn btn-vx btn-sm" href="<?= url(dashboard_path($user['role'])) ?>">Dashboard</a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= url('login.php') ?>">Log in</a>
        <a class="btn btn-vx btn-sm" href="<?= url('register.php') ?>">Get started</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="<?= $layout === 'public' ? 'pub-main' : '' ?>">
<div class="container pt-3">
<?php endif; ?>
<?php foreach (get_flashes() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endforeach; ?>
<?php if ($layout !== 'app'): ?></div><?php endif; ?>
