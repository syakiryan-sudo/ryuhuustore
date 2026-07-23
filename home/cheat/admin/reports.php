<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
/* Validate dates (whitelist format) */
$fromD = DateTime::createFromFormat('Y-m-d', $from) ? $from : date('Y-m-01');
$toD   = DateTime::createFromFormat('Y-m-d', $to)   ? $to   : date('Y-m-d');

$paidStatuses = "'paid','shipped','completed'";

/* CSV export */
if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $pdo->prepare("SELECT invoice, customer_name, total, status, order_date
        FROM orders WHERE DATE(order_date) BETWEEN ? AND ? ORDER BY order_date ASC");
    $stmt->execute([$fromD, $toD]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $fromD . '_' . $toD . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Invoice', 'Pelanggan', 'Total', 'Status', 'Tanggal']);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [$row['invoice'], $row['customer_name'], $row['total'], status_label($row['status']), $row['order_date']]);
    }
    fclose($out);
    exit;
}

$sumStmt = $pdo->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t
    FROM orders WHERE status IN ($paidStatuses) AND DATE(order_date) BETWEEN ? AND ?");
$sumStmt->execute([$fromD, $toD]);
$summary = $sumStmt->fetch();

$daily = $pdo->prepare("SELECT DATE(order_date) d, COUNT(*) c, COALESCE(SUM(total),0) t
    FROM orders WHERE status IN ($paidStatuses) AND DATE(order_date) BETWEEN ? AND ?
    GROUP BY DATE(order_date) ORDER BY d ASC");
$daily->execute([$fromD, $toD]);
$daily = $daily->fetchAll();

$pageTitle = 'Laporan Penjualan';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="panel glass">
    <form method="get" class="report-filter">
        <div class="field"><label>Dari</label><input type="date" name="from" value="<?= e($fromD) ?>"></div>
        <div class="field"><label>Sampai</label><input type="date" name="to" value="<?= e($toD) ?>"></div>
        <button class="btn btn-primary" type="submit">Terapkan</button>
        <a class="btn btn-ghost" href="<?= url('admin/reports.php') . '?' . http_build_query(['from'=>$fromD,'to'=>$toD,'export'=>'csv']) ?>">Export CSV</a>
    </form>
</div>

<div class="stats">
    <div class="stat glass"><span class="muted">Total Pesanan (dibayar)</span><strong><?= number_format((int)$summary['c'],0,',','.') ?></strong></div>
    <div class="stat glass"><span class="muted">Total Pendapatan</span><strong><?= money($summary['t']) ?></strong></div>
</div>

<div class="panel glass">
    <div class="panel-head"><h3>Rincian Harian</h3></div>
    <table class="table">
        <thead><tr><th>Tanggal</th><th>Jumlah Pesanan</th><th>Pendapatan</th></tr></thead>
        <tbody>
        <?php foreach ($daily as $d): ?>
            <tr><td><?= e(date('d M Y', strtotime($d['d']))) ?></td><td><?= (int)$d['c'] ?></td><td><?= money($d['t']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$daily): ?><tr><td colspan="3" class="muted">Tidak ada data pada rentang ini.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
