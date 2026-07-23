<?php
/**
 * admin/setup_admin.php — One-time helper to create/reset the default admin.
 * Visit once in the browser, then DELETE this file.
 */
require_once __DIR__ . '/../config.php';

$username = 'admin';
$password = 'admin123';
$name     = 'Store Admin';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = db()->prepare('INSERT INTO admins (username, password_hash, name) VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), name = VALUES(name)');
$stmt->execute([$username, $hash, $name]);

echo 'Admin siap. Username: ' . e($username) . ' | Password: ' . e($password)
   . '<br><strong>Hapus file ini sekarang (delete setup_admin.php).</strong>';
