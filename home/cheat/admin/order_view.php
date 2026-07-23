<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

$inv = (string)($_GET['inv'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE invoice = ? LIMIT 1');
$stmt->execute([$inv]);
$order = $stmt->fetch();
if (!$order) {
    require __DIR__ . '/includes/admin_header.php';
    echo '<div class="panel glass"><p class="muted">Pesanan tidak ditemukan.</p></div>';
    require __DIR__ . '/includes/admin_footer.php';
    exit;
}

/* Update status */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    csrf_check();
    $new = $_POST['status'] ?? '';
    $valid = ['pending','paid','shipped','completed','cancelled'];
    if (in_array($new, $valid, true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$new, (int)$order['id']]);
        redirect(url('admin/order_view.php?inv=' . urlencode($inv)));
    }
}

$items = $pdo->prepare('SELECT product_name, price, qty, line_total FROM order_items WHERE order_id = ?');
$items->execute([(int)$order['id']]);
$items = $items->fetchAll();

$payments = $pdo->prepare('SELECT proof_path, amount, note, created_at FROM payments WHERE order_id = ? ORDER BY created_at DESC');
$payments->execute([(int)$order['id']]);
$payments = $payments->fetchAll();

$pageTitle = 'Pesanan ' . $order['invoice'];
require __DIR__ . '/includes/admin_header.php';
?>
<div class="two-col">
    <div class="panel glass">
        <h3>Item Pesanan</h3>
        <table class="table">
            <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr><td><?= e($it['product_name']) ?></td><td><?= money($it['price']) ?></td><td><?= (int)$it['qty'] ?></td><td><?= money($it['line_total']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="row"><span class="muted">Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
        <div class="row"><span class="muted">Pengiriman</span><span><?= money($order['shipping']) ?></span></div>
        <div class="row total"><span>Total</span><span><?= money($order['total']) ?></span></div>

        <h3>Bukti Pembayaran</h3>
        <?php if ($payments): ?>
            <div class="img-grid">
            <?php foreach ($payments as $pay): ?>
                <a class="img-cell glass" href="<?= product_image_url($pay['proof_path']) ?>" target="_blank">
                    <img src="<?= product_image_url($pay['proof_path']) ?>" alt="Bukti">
                </a>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">Belum ada bukti pembayaran.</p>
        <?php endif; ?>
    </div>

    <aside class="panel glass">
        <h3>Detail Pengiriman</h3>
        <p><strong><?= e($order['customer_name']) ?></strong><br>
           <?= e($order['phone']) ?><br>
           <?= e($order['address']) ?><br>
           <?= e($order['city']) ?>, <?= e($order['postal_code']) ?></p>
        <div class="row"><span class="muted">Metode</span><span><?= e($order['payment_method']) ?></span></div>
        <div class="row"><span class="muted">Tanggal</span><span><?= e(date('d M Y H:i', strtotime($order['order_date']))) ?></span></div>

        <hr class="hair">
        <h3>Update Status</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="status">
            <div class="field">
                <select name="status">
                    <?php foreach (['pending','paid','shipped','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= e(status_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary block" type="submit">Perbarui</button>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
