<?php
require_once __DIR__ . '/config.php';

$inv = isset($_GET['inv']) ? (string)$_GET['inv'] : '';
$stmt = db()->prepare('SELECT invoice, customer_name, total, payment_method, status FROM orders WHERE invoice = ? LIMIT 1');
$stmt->execute([$inv]);
$order = $stmt->fetch();

$pageTitle = 'Pesanan Berhasil';
require __DIR__ . '/includes/header.php';
?>

<section class="section reveal center-narrow">
    <div class="success glass">
        <div class="success-mark">
            <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <h1>Terima kasih!</h1>
        <?php if ($order): ?>
            <p class="muted">Pesanan kamu telah kami terima.</p>
            <div class="invoice-box">
                <span class="muted">Nomor Invoice</span>
                <strong><?= e($order['invoice']) ?></strong>
            </div>
            <div class="row"><span class="muted">Total</span><span><?= money($order['total']) ?></span></div>
            <div class="row"><span class="muted">Status</span><span class="badge <?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></div>
            <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                <p class="muted small">Silakan transfer ke: <strong><?= e(setting('bank_info', '')) ?></strong></p>
            <?php endif; ?>
            <a class="btn btn-primary block" href="<?= url('payment_confirmation.php?inv=' . urlencode($order['invoice'])) ?>">Konfirmasi Pembayaran</a>
            <a class="btn btn-ghost block" href="<?= url('index.php') ?>">Kembali ke Beranda</a>
        <?php else: ?>
            <p class="muted">Pesanan tidak ditemukan.</p>
            <a class="btn btn-primary block" href="<?= url('index.php') ?>">Kembali ke Beranda</a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
