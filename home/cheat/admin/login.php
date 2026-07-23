<?php
require_once __DIR__ . '/includes/auth.php';

if (admin()) {
    redirect(url('admin/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id, username, name, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => (int)$row['id'], 'username' => $row['username'], 'name' => $row['name']];
        redirect(url('admin/index.php'));
    }
    $error = 'Username atau password salah.';
}
$pageTitle = 'Masuk Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="auth-body">
<div class="bg-aurora" aria-hidden="true"></div>
<div class="auth-card glass">
    <div class="brand center"><?= e(setting('store_name', 'Liquid')) ?></div>
    <p class="muted center">Masuk ke panel admin</p>
    <?php if ($error): ?><div class="alert glass"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label>Username</label><input name="username" autofocus required></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button class="btn btn-primary block" type="submit">Masuk</button>
    </form>
    <p class="muted small center">Default: admin / admin123 (jalankan setup_admin.php).</p>
</div>
</body>
</html>
