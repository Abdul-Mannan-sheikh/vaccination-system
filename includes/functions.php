<?php
require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------
 * Output + navigation helpers
 * ------------------------------------------------------------------ */

/** Escape text for safe HTML output (prevents XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Build a full URL from a path inside the project. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Remember the value a user typed so the form can be re-filled after an error. */
function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

/* ------------------------------------------------------------------
 * Flash messages (one-time alerts shown after a redirect)
 * ------------------------------------------------------------------ */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ------------------------------------------------------------------
 * CSRF protection for every POST form
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ------------------------------------------------------------------
 * Authentication + role-based access
 * ------------------------------------------------------------------ */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function dashboard_path(string $role): string
{
    return match ($role) {
        'admin'    => 'admin/dashboard.php',
        'hospital' => 'hospital/dashboard.php',
        default    => 'parent/dashboard.php',
    };
}

/** Block guests. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    return $user;
}

/** Block everyone except one role (e.g. a parent cannot open admin pages). */
function require_role(string $role): array
{
    $user = require_login();
    if ($user['role'] !== $role) {
               show_error(403, 'You do not have permission to view this page.');
    }
    return $user;
}

/** Send already-logged-in users straight to their own dashboard. */
function redirect_if_logged_in(): void
{
    if ($user = current_user()) {
        redirect(dashboard_path($user['role']));
    }
}

/* ------------------------------------------------------------------
 * Small data helpers
 * ------------------------------------------------------------------ */

/** Run a COUNT-style query and return a single integer. */
function scalar_count(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function add_notification(int $userId, string $title, string $message): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $message]);
}

/* ------------------------------------------------------------------
 * UI theme (medical / gradient / dark) remembered in a cookie
 * ------------------------------------------------------------------ */
function current_theme(): string
{
    $t = $_COOKIE['vx_theme'] ?? 'medical';
    return in_array($t, ['medical', 'gradient', 'dark'], true) ? $t : 'medical';
}

/** Human-friendly age for a vaccine due age given in days (e.g. "6 weeks", "9 months"). */
function age_label(int $days): string
{
    if ($days === 0) return 'At birth';
    return $days < 120 ? round($days / 7) . ' weeks' : round($days / 30) . ' months';
}

/* ------------------------------------------------------------------
 * Children + vaccination schedule helpers
 * ------------------------------------------------------------------ */

/**
 * ALGORITHM: build a child's vaccination schedule
 * 1. Read every vaccine (id, age_days) from the vaccines table.
 * 2. For each vaccine: due_date = date of birth + age_days.
 * 3. Insert one row per vaccine into child_vaccinations (status 'pending').
 * INSERT IGNORE + the UNIQUE(child_id, vaccine_id) key stop duplicates.
 */
function generate_schedule(int $childId, string $dob): void
{
    $pdo = db();
    $vaccines = $pdo->query('SELECT id, age_days FROM vaccines')->fetchAll();
    $ins = $pdo->prepare('INSERT IGNORE INTO child_vaccinations (child_id, vaccine_id, due_date) VALUES (?, ?, ?)');
    foreach ($vaccines as $v) {
        $due = (new DateTime($dob))->modify('+' . (int) $v['age_days'] . ' days')->format('Y-m-d');
        $ins->execute([$childId, $v['id'], $due]);
    }
}

/** Label + colour class for one dose: Done, Booked, Overdue, Due soon or Upcoming. */
function dose_state(string $status, string $dueDate): array
{
    if ($status === 'vaccinated') return ['Done', 'ok'];
    if ($status === 'booked')     return ['Booked', 'book'];
    $days = (int) floor((strtotime($dueDate) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0)   return ['Overdue', 'bad'];
    if ($days <= 14) return ['Due soon', 'next'];
    return ['Upcoming', 'later'];
}

/** Child's age as text, e.g. "2 years 3 months" or "5 weeks". */
function child_age(string $dob): string
{
    $d = (new DateTime($dob))->diff(new DateTime('today'));
    if ($d->y > 0) return $d->y . ' yr' . ($d->m ? ' ' . $d->m . ' mo' : '');
    if ($d->m > 0) return $d->m . ' month' . ($d->m > 1 ? 's' : '');
    return max(0, intdiv($d->days, 7)) . ' weeks';
}


/** Change a user's password after checking the old one. Returns an error text, or null on success. */
function change_password(int $userId, string $old, string $new, string $confirm): ?string
{
    if (strlen($new) < 8)   return 'New password must be at least 8 characters.';
    if ($new !== $confirm)  return 'New passwords do not match.';
    $s = db()->prepare('SELECT password FROM users WHERE id = ?');
    $s->execute([$userId]);
    $hash = $s->fetchColumn();
    if (!$hash || !password_verify($old, $hash)) return 'Your current password is incorrect.';
    if ($old === $new)      return 'Please choose a password different from the current one.';
    db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
    return null;
}


/* ------------------------------------------------------------------
 * Login throttling: 5 wrong passwords in 10 minutes blocks that email from that IP
 * ------------------------------------------------------------------ */

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function login_blocked(string $email): bool
{
    return scalar_count(
        'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip = ? AND attempted_at > (NOW() - INTERVAL 10 MINUTE)',
        [substr($email, 0, 150), client_ip()]
    ) >= 5;
}

function login_failed(string $email): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip) VALUES (?, ?)')->execute([substr($email, 0, 150), client_ip()]);
    db()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');   // keep the table small
}

function login_cleared(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = ? AND ip = ?')->execute([substr($email, 0, 150), client_ip()]);
}

/** Show a styled error page (403, 404, 500) using the public layout, then stop. */
function show_error(int $code, string $message = ''): never
{
    $errorCode = $code;
    $errorMsg  = $message;
    require __DIR__ . '/../error.php';
    exit;
}


/** Half-hour booking slots from 09:00 to 16:30. */
function booking_slots(): array
{
    $slots = [];
    for ($h = 9; $h <= 16; $h++) {
        foreach (['00', '30'] as $m) $slots[] = sprintf('%02d:%s', $h, $m);
    }
    return $slots;
}


require_once __DIR__ . '/public_parts.php';