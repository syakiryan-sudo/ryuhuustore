<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

$perPage = 15;
$page = current_page_param();
$status = $_GET['status'] ?? '';
$validStatuses = ['pending','paid','shipped','completed','cancelled'];

$where = '1=1';
$params = [];
if (in_array($status, $validStatuses, true)) {
    $where .= ' AND status = ?';
    $params[] = $status;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT invoice, customer_name, phone, total, status, order_date
    FROM orders WHERE $where ORDER BY order_date DESC LIMIT $perPage OFFSET {$pg['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Pesanan';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="panel glass">
    <div class="panel-head">
        <div class="filter-chips">
            <a class="chip glass<?= $status===''?' active':'' ?>" href="<?= url('admin/orders.php') ?>">Semua</a>
            <?php foreach ($validStatuses as $s): ?>
                <a class="chip glass<?= $status===$s?' active':'' ?>" href="<?= url('admin/orders.php?status=' . $s) ?>"><?= e(status_label($s)) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <table class="table">
        <thead><tr><th>Invoice</th><th>Pelanggan</th><th>Telepon</th><th>Total</th><th>Status</th><th>Tanggal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e($o['invoice']) ?></td>
                <td><?= e($o['customer_name']) ?></td>
                <td class="muted"><?= e($o['phone']) ?></td>
                <td><?= money($o['total']) ?></td>
                <td><span class="badge <?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                <td class="muted"><?= e(date('d M Y H:i', strtotime($o['order_date']))) ?></td>
                <td><a class="link" href="<?= url('admin/order_view.php?inv=' . urlencode($o['invoice'])) ?>">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="7" class="muted">Tidak ada pesanan.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if ($pg['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
            <a class="page-link glass<?= $i===$pg['page']?' active':'' ?>" href="<?= url('admin/orders.php') . '?' . http_build_query(array_filter(['status'=>$status,'page'=>$i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
