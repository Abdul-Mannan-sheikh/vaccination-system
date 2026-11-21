<?php
/** Public header (MediCio template): used by index, login and register. */
$base = url('assets/medicio/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
  <meta name="description" content="VaxTrack - book hospital vaccinations and track every child's immunisation schedule.">
  <link href="<?= $base ?>img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= $base ?>vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $base ?>vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= $base ?>vendor/aos/aos.css" rel="stylesheet">
  <link href="<?= $base ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="<?= $base ?>vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="<?= $base ?>vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="<?= $base ?>css/main.css" rel="stylesheet">
  <link href="<?= $base ?>css/public.css" rel="stylesheet">
</head>
<body class="<?= $cur === 'index.php' ? 'index-page' : 'starter-page-page' ?>">
  <header id="header" class="header sticky-top">
    <div class="topbar d-flex align-items-center">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="d-none d-md-flex align-items-center"><i class="bi bi-clock me-1"></i> Online booking open 24/7</div>
        <div class="d-flex align-items-center"><i class="bi bi-envelope me-1"></i> support@vaxtrack.local</div>
      </div>
    </div>
    <div class="branding d-flex align-items-center">
      <div class="container position-relative d-flex align-items-center justify-content-end">
        <a href="<?= url('index.php') ?>" class="logo d-flex align-items-center me-auto">
          <i class="bi bi-shield-plus" style="font-size:2rem;color:var(--accent-color)"></i>
          <h1 class="sitename ms-2"><?= APP_NAME ?></h1>
        </a>
        <nav id="navmenu" class="navmenu">
                    <ul>
            <?php foreach ([['index.php', 'Home'], ['about.php', 'About'], ['how-it-works.php', 'How it works'], ['vaccines.php', 'Vaccines'],
                            ['hospitals.php', 'Hospitals'], ['faq.php', 'FAQ'], ['contact.php', 'Contact']] as [$p, $l]): ?>
              <li><a href="<?= url($p) ?>" class="<?= $cur === $p ? 'active' : '' ?>"><?= $l ?></a></li>
            <?php endforeach; ?>
            <?php if (!$user): ?><li><a class="nav-login <?= $cur === 'login.php' ? 'active' : '' ?>" href="<?= url('login.php') ?>">Log in</a></li><?php endif; ?>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
        <?php if ($user): ?>
          <a class="cta-btn" href="<?= url(dashboard_path($user['role'])) ?>">My Dashboard</a>
        <?php else: ?>
          <a class="cta-btn" href="<?= url('register.php') ?>">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <main class="main">
<?php $flashes = get_flashes(); if ($flashes): ?>
  <div class="container flash-wrap">
  <?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert"><?= e($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
  <?php endforeach; ?></div>
<?php endif; ?>
