<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

/* Delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    cache_forget('categories');
    redirect(url('admin/products.php'));
}

$perPage = 15;
$page = current_page_param();
$q = trim((string)($_GET['q'] ?? ''));

$where = '1=1';
$params = [];
if ($q !== '') {
    $where .= ' AND p.name LIKE ?';
    $params[] = '%' . $q . '%';
}
$total = (int)(function() use ($pdo, $where, $params) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where");
    $s->execute($params);
    return $s->fetchColumn();
})();
$pg = paginate($total, $perPage, $page);

$sql = "SELECT p.id, p.name, p.price, p.stock, p.is_active, c.name AS category,
    (SELECT path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS image
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET {$pg['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Produk';
require __DIR__ . '/includes/admin_header.php';
?>
<div class="panel glass">
    <div class="panel-head">
        <form method="get" class="search-inline">
            <input name="q" value="<?= e($q) ?>" placeholder="Cari produk…">
        </form>
        <a class="btn btn-primary" href="<?= url('admin/product_edit.php') ?>">+ Produk Baru</a>
    </div>
    <table class="table">
        <thead><tr><th></th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><img class="thumb-sm" src="<?= product_image_url($p['image']) ?>" alt=""></td>
                <td><?= e($p['name']) ?></td>
                <td class="muted"><?= e($p['category'] ?? '—') ?></td>
                <td><?= money($p['price']) ?></td>
                <td><?= (int)$p['stock'] ?></td>
                <td><span class="badge <?= $p['is_active'] ? 'completed' : 'cancelled' ?>"><?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td class="actions-cell">
                    <a class="link" href="<?= url('admin/product_edit.php?id=' . (int)$p['id']) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Hapus produk ini?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="link danger" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="7" class="muted">Tidak ada produk.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if ($pg['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
            <a class="page-link glass<?= $i===$pg['page']?' active':'' ?>" href="<?= url('admin/products.php') . '?' . http_build_query(array_filter(['q'=>$q,'page'=>$i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
