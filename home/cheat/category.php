<?php
require_once __DIR__ . '/config.php';

$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
$sort = $_GET['sort'] ?? 'new';
$perPage = 12;
$page = current_page_param();

$category = null;
if ($slug !== '') {
    $cStmt = db()->prepare('SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1');
    $cStmt->execute([$slug]);
    $category = $cStmt->fetch();
}

/* Build WHERE + ORDER using whitelist (no user text in SQL) */
$where = 'p.is_active = 1';
$params = [];
if ($category) {
    $where .= ' AND p.category_id = ?';
    $params[] = (int)$category['id'];
}
$orderMap = [
    'new'       => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc'=> 'p.price DESC',
    'popular'   => 'p.sold DESC',
];
$orderBy = $orderMap[$sort] ?? $orderMap['new'];

/* Count for pagination */
$countStmt = db()->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pg = paginate($total, $perPage, $page);

/* Page of products */
$sql = "SELECT p.id, p.name, p.slug, p.price,
    (SELECT path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS image
    FROM products p WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET {$pg['offset']}";
$listStmt = db()->prepare($sql);
$listStmt->execute($params);
$products = $listStmt->fetchAll();

$categories = all_categories();
$pageTitle = $category ? $category['name'] : 'Semua Produk';
require __DIR__ . '/includes/header.php';

function cat_link(array $q): string {
    return url('category.php') . '?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null));
}
?>

<section class="section reveal">
    <div class="section-head">
        <h2><?= e($pageTitle) ?></h2>
        <form class="sort" method="get">
            <?php if ($slug): ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><?php endif; ?>
            <select name="sort" class="glass" onchange="this.form.submit()">
                <option value="new"        <?= $sort==='new'?'selected':'' ?>>Terbaru</option>
                <option value="popular"    <?= $sort==='popular'?'selected':'' ?>>Terlaris</option>
                <option value="price_asc"  <?= $sort==='price_asc'?'selected':'' ?>>Harga terendah</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Harga tertinggi</option>
            </select>
        </form>
    </div>

    <div class="chips">
        <a class="chip glass<?= $slug===''?' active':'' ?>" href="<?= cat_link(['sort'=>$sort]) ?>">Semua</a>
        <?php foreach ($categories as $c): ?>
            <a class="chip glass<?= $slug===$c['slug']?' active':'' ?>" href="<?= cat_link(['slug'=>$c['slug'],'sort'=>$sort]) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$products): ?>
        <p class="muted">Belum ada produk di kategori ini.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($products as $p): ?>
                <?php include __DIR__ . '/includes/_product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pg['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
            <a class="page-link glass<?= $i === $pg['page'] ? ' active' : '' ?>" href="<?= cat_link(['slug'=>$slug,'sort'=>$sort,'page'=>$i]) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
