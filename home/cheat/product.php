<?php
require_once __DIR__ . '/config.php';

$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
$stmt = db()->prepare('SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.category_id,
    c.name AS category_name, c.slug AS category_slug
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.slug = ? AND p.is_active = 1 LIMIT 1');
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produk tidak ditemukan';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section reveal"><h2>Produk tidak ditemukan</h2><p class="muted">Produk mungkin sudah tidak tersedia.</p><a class="btn btn-primary" href="' . url('index.php') . '">Kembali</a></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$imgStmt = db()->prepare('SELECT path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC');
$imgStmt->execute([(int)$product['id']]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
if (!$images) {
    $images = [null];
}

$pageTitle = $product['name'];
require __DIR__ . '/includes/header.php';
?>

<nav class="crumbs muted">
    <a href="<?= url('index.php') ?>">Home</a> <span>/</span>
    <?php if ($product['category_slug']): ?>
        <a href="<?= url('category.php?slug=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a> <span>/</span>
    <?php endif; ?>
    <span><?= e($product['name']) ?></span>
</nav>

<section class="product reveal">
    <div class="gallery">
        <div class="gallery-main glass">
            <img id="mainImg" src="<?= product_image_url($images[0]) ?>" alt="<?= e($product['name']) ?>" data-lightbox>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="thumbs">
            <?php foreach ($images as $i => $img): ?>
                <button class="thumb glass<?= $i === 0 ? ' active' : '' ?>" data-thumb="<?= product_image_url($img) ?>">
                    <img src="<?= product_image_url($img) ?>" alt="">
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="product-info glass">
        <?php if ($product['category_name']): ?><p class="eyebrow"><?= e($product['category_name']) ?></p><?php endif; ?>
        <h1><?= e($product['name']) ?></h1>
        <div class="price-lg"><?= money($product['price']) ?></div>
        <p class="stock <?= $product['stock'] > 0 ? 'in' : 'out' ?>">
            <?= $product['stock'] > 0 ? 'Stok tersedia (' . (int)$product['stock'] . ')' : 'Stok habis' ?>
        </p>
        <p class="desc"><?= nl2br(e($product['description'] ?? '')) ?></p>

        <?php if ($product['stock'] > 0): ?>
        <div class="qty-row">
            <div class="stepper glass">
                <button type="button" data-step="-1" aria-label="Kurangi">−</button>
                <input id="qty" type="number" value="1" min="1" max="<?= (int)$product['stock'] ?>" inputmode="numeric">
                <button type="button" data-step="1" aria-label="Tambah">+</button>
            </div>
        </div>
        <div class="actions">
            <button class="btn btn-ghost" data-add-to-cart data-id="<?= (int)$product['id'] ?>" data-qty-source="#qty">Tambahkan ke Keranjang</button>
            <a class="btn btn-primary" href="<?= url('cart.php?buy=' . (int)$product['id']) ?>">Beli Sekarang</a>
        </div>
        <?php else: ?>
            <button class="btn btn-primary" disabled>Stok Habis</button>
        <?php endif; ?>
    </div>
</section>

<div class="lightbox" id="lightbox" hidden>
    <button class="lightbox-close" aria-label="Tutup">×</button>
    <img src="" alt="">
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
