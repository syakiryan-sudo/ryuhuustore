<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

$perPage = 20;
$page = current_page_param();
$q = trim((string)($_GET['q'] ?? ''));

/* Customers are derived from orders (guest checkout) + users table. */
$where = '1=1';
$params = [];
if ($q !== '') {
    $where .= ' AND (customer_name LIKE ? OR phone LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(DISTINCT phone) FROM orders WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT customer_name, phone, city,
        COUNT(*) AS order_count,
        SUM(total) AS total_spent,
        MAX(order_date) AS last_order
    FROM orders WHERE $where
    GROUP BY customer_name, phone, city
    ORDER BY total_spent DESC
    LIMIT $perPage OFFSET {$pg['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Pelanggan';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="panel glass">
    <div class="panel-head">
        <form method="get" class="search-inline"><input name="q" value="<?= e($q) ?>" placeholder="Cari nama / telepon…"></form>
    </div>
    <table class="table">
        <thead><tr><th>Nama</th><th>Telepon</th><th>Kota</th><th>Pesanan</th><th>Total Belanja</th><th>Terakhir</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= e($c['customer_name']) ?></td>
                <td class="muted"><?= e($c['phone']) ?></td>
                <td class="muted"><?= e($c['city']) ?></td>
                <td><?= (int)$c['order_count'] ?></td>
                <td><?= money($c['total_spent']) ?></td>
                <td class="muted"><?= e(date('d M Y', strtotime($c['last_order']))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$customers): ?><tr><td colspan="6" class="muted">Belum ada pelanggan.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if ($pg['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
            <a class="page-link glass<?= $i===$pg['page']?' active':'' ?>" href="<?= url('admin/customers.php') . '?' . http_build_query(array_filter(['q'=>$q,'page'=>$i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
