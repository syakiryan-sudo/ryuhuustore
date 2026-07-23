<?php
require_once __DIR__ . '/config.php';

$order = null;
$notice = '';
$inv = isset($_GET['inv']) ? (string)$_GET['inv'] : (string)($_POST['invoice'] ?? '');

/* Handle proof upload */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'proof') {
    csrf_check();
    $inv = trim((string)($_POST['invoice'] ?? ''));
    $stmt = db()->prepare('SELECT id, invoice, total, status FROM orders WHERE invoice = ? LIMIT 1');
    $stmt->execute([$inv]);
    $order = $stmt->fetch();
    if (!$order) {
        $notice = 'Invoice tidak ditemukan.';
    } else {
        $path = handle_image_upload($_FILES['proof'] ?? []);
        if (!$path) {
            $notice = 'Bukti transfer tidak valid (JPG/PNG/WebP, maks 3MB).';
        } else {
            $pdo = db();
            $pdo->prepare('INSERT INTO payments (order_id, proof_path, amount, note) VALUES (?,?,?,?)')
                ->execute([(int)$order['id'], $path, (float)$order['total'], 'Uploaded by customer']);
            $notice = 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.';
        }
    }
}

/* Lookup by invoice (GET or after post) */
if ($inv !== '' && !$order) {
    $stmt = db()->prepare('SELECT id, invoice, customer_name, total, payment_method, status, order_date FROM orders WHERE invoice = ? LIMIT 1');
    $stmt->execute([$inv]);
    $order = $stmt->fetch();
    if (!$order && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $notice = 'Masukkan nomor invoice untuk melihat status pesanan.';
    }
}

$steps = ['pending','paid','shipped','completed'];
$currentIdx = $order ? array_search($order['status'], $steps, true) : -1;
$pageTitle = 'Konfirmasi Pembayaran';
require __DIR__ . '/includes/header.php';
?>

<section class="section reveal center-narrow">
    <div class="section-head"><h2>Status Pesanan</h2></div>

    <form method="get" class="lookup glass">
        <input name="inv" placeholder="Masukkan nomor invoice (mis. INV-...)" value="<?= e($inv) ?>">
        <button class="btn btn-primary" type="submit">Cek</button>
    </form>

    <?php if ($notice): ?><div class="alert glass"><?= e($notice) ?></div><?php endif; ?>

    <?php if ($order): ?>
    <div class="glass order-status">
        <div class="row"><span class="muted">Invoice</span><strong><?= e($order['invoice']) ?></strong></div>
        <div class="row"><span class="muted">Total</span><span><?= money($order['total']) ?></span></div>

        <?php if ($order['status'] === 'cancelled'): ?>
            <div class="badge cancelled big">Dibatalkan</div>
        <?php else: ?>
        <div class="tracker">
            <?php foreach ($steps as $i => $s): ?>
                <div class="track-step <?= $i <= $currentIdx ? 'done' : '' ?>">
                    <span class="dot"></span>
                    <span class="track-label"><?= e(status_label($s)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array($order['status'], ['pending'], true)): ?>
        <hr class="hair">
        <h3>Upload Bukti Transfer</h3>
        <form method="post" enctype="multipart/form-data" class="proof-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="proof">
            <input type="hidden" name="invoice" value="<?= e($order['invoice']) ?>">
            <input type="file" name="proof" accept="image/*" required>
            <button class="btn btn-primary" type="submit">Kirim Bukti</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
