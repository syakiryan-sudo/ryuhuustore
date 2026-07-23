<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$ordersToday   = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()')->fetchColumn();
$revenue       = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed')")->fetchColumn();
$pendingCount  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

/* Last 7 days revenue for mini chart */
$chart = $pdo->query("SELECT DATE(order_date) d, COALESCE(SUM(total),0) v
    FROM orders WHERE status IN ('paid','shipped','completed') AND order_date >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(order_date) ORDER BY d ASC")->fetchAll();
$map = [];
foreach ($chart as $r) { $map[$r['d']] = (float)$r['v']; }
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[] = ['label' => date('D', strtotime($d)), 'value' => $map[$d] ?? 0];
}
$maxVal = max(1, ...array_map(fn($x) => $x['value'], $days));

$recent = $pdo->query('SELECT invoice, customer_name, total, status, order_date FROM orders ORDER BY order_date DESC LIMIT 6')->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="stats">
    <div class="stat glass"><span class="muted">Total Produk</span><strong><?= number_format($totalProducts,0,',','.') ?></strong></div>
    <div class="stat glass"><span class="muted">Pesanan Hari Ini</span><strong><?= number_format($ordersToday,0,',','.') ?></strong></div>
    <div class="stat glass"><span class="muted">Pendapatan</span><strong><?= money($revenue) ?></strong></div>
    <div class="stat glass"><span class="muted">Menunggu Verifikasi</span><strong><?= number_format($pendingCount,0,',','.') ?></strong></div>
</div>

<div class="panel glass">
    <div class="panel-head"><h3>Pendapatan 7 Hari</h3></div>
    <div class="minichart">
        <?php foreach ($days as $d): ?>
            <div class="bar-wrap">
                <div class="bar" style="height: <?= (int)round(($d['value'] / $maxVal) * 100) ?>%" title="<?= money($d['value']) ?>"></div>
                <span class="bar-label muted"><?= e($d['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel glass">
    <div class="panel-head"><h3>Pesanan Terbaru</h3><a class="link" href="<?= url('admin/orders.php') ?>">Semua</a></div>
    <table class="table">
        <thead><tr><th>Invoice</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $o): ?>
            <tr>
                <td><a class="link" href="<?= url('admin/order_view.php?inv=' . urlencode($o['invoice'])) ?>"><?= e($o['invoice']) ?></a></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= money($o['total']) ?></td>
                <td><span class="badge <?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                <td class="muted"><?= e(date('d M Y', strtotime($o['order_date']))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="5" class="muted">Belum ada pesanan.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
