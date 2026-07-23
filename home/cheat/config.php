<?php
/**
 * config.php — Global configuration & PDO bootstrap.
 * Adjust DB credentials and BASE_URL to match your server.
 */

declare(strict_types=1);

/* ---------- Environment ---------- */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'liquid_glass_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* Base URL of the app (no trailing slash). Leave '' if in web root. */
define('BASE_URL', '');

/* Absolute paths */
define('ROOT_PATH', __DIR__);
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('CACHE_DIR', ROOT_PATH . '/cache');

/* Upload limits */
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3 MB
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

/* ---------- Error handling ---------- */
error_reporting(E_ALL);
ini_set('display_errors', '0'); // set '1' during local development

/* ---------- Secure session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

/* ---------- PDO singleton ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        PDO::ATTR_PERSISTENT         => false, // enable behind a pooler if desired
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Database connection failed.');
    }
    return $pdo;
}

require_once __DIR__ . '/includes/functions.php';
