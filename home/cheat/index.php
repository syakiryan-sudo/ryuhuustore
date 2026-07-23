<?php
require_once __DIR__ . '/config.php';
$pageTitle = setting('store_name', 'Liquid') . ' — ' . setting('store_tagline', '');

/* Featured product (top seller) */
$featured = db()->query('SELECT p.id, p.name, p.slug, p.description, p.price,
    (SELECT path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS image
    FROM products p WHERE p.is_active = 1 ORDER BY p.sold DESC LIMIT 1')->fetch();

/* Latest products (paginated-ready, first 8) */
$latest = db()->query('SELECT p.id, p.name, p.slug, p.price,
    (SELECT path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS image
    FROM products p WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 8')->fetchAll();

$categories = all_categories();
require __DIR__ . '/includes/header.php';
?>

<section class="hero reveal">
    <div class="hero-copy">
        <p class="eyebrow">New · <?= e(setting('store_tagline', '')) ?></p>
        <h1><?= $featured ? e($featured['name']) : 'Objects of pure clarity.' ?></h1>
        <p class="lede"><?= $featured ? e(mb_strimwidth($featured['description'] ?? '', 0, 140, '…')) : 'A curated collection of high-end essentials.' ?></p>
        <?php if ($featured): ?>
            <div class="hero-cta">
                <a class="btn btn-primary" href="<?= url('product.php?slug=' . urlencode($featured['slug'])) ?>">Lihat produk</a>
                <span class="price-lg"><?= money($featured['price']) ?></span>
            </div>
        <?php endif; ?>
    </div>
    <div class="hero-visual glass">
        <img src="<?= $featured ? product_image_url($featured['image']) : url('assets/img/placeholder.svg') ?>" alt="<?= e($featured['name'] ?? 'Featured') ?>" loading="eager">
    </div>
</section>

<section id="kategori" class="section reveal">
    <div class="section-head">
        <h2>Kategori</h2>
    </div>
    <div class="chips">
        <?php foreach ($categories as $c): ?>
            <a class="chip glass" href="<?= url('category.php?slug=' . urlencode($c['slug'])) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<section id="produk" class="section reveal">
    <div class="section-head">
        <h2>Terbaru</h2>
        <a class="link" href="<?= url('category.php') ?>">Lihat semua</a>
    </div>
    <div class="grid">
        <?php foreach ($latest as $p): ?>
            <?php include __DIR__ . '/includes/_product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
