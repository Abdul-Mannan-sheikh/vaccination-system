<?php
/**
 * Global configuration for VaxTrack.
 * Change BASE_URL if you put the project in a different folder name.
 */
define('APP_NAME', 'VaxTrack');
define('BASE_URL', '/vaccination-system');   // folder name inside C:\xampp\htdocs

// Database credentials (XAMPP defaults)
define('DB_HOST', 'localhost');
define('DB_NAME', 'vaxtrack');
define('DB_USER', 'root');
define('DB_PASS', '');

// Local XAMPP has no mail server, so the password-reset link is shown on screen.
// On a real server set this to false and the link is e-mailed instead.
define('SHOW_RESET_LINK', true);

date_default_timezone_set('Asia/Karachi');

// Start a secure session once per request
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,      // JS cannot read the session cookie
        'samesite' => 'Lax',     // basic CSRF protection for cross-site requests
    ]);
    session_start();
}
