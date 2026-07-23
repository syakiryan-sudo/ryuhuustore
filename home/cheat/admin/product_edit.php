<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$product = ['id'=>0,'name'=>'','description'=>'','price'=>'','stock'=>'','category_id'=>'','is_active'=>1];
$images = [];
if ($id) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $s->execute([$id]);
    $product = $s->fetch() ?: $product;
    $im = $pdo->prepare('SELECT id, path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC');
    $im->execute([$id]);
    $images = $im->fetchAll();
}
$categories = all_categories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name        = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $categoryId  = ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '')  $errors[] = 'Nama produk wajib diisi.';
    if ($price < 0)    $errors[] = 'Harga tidak valid.';
    if ($stock < 0)    $errors[] = 'Stok tidak valid.';

    if (!$errors) {
        if ($id) {
            $slug = slugify($name) . '-' . $id;
            $pdo->prepare('UPDATE products SET name=?, slug=?, description=?, price=?, stock=?, category_id=?, is_active=? WHERE id=?')
                ->execute([$name, $slug, $description, $price, $stock, $categoryId, $isActive, $id]);
        } else {
            $pdo->prepare('INSERT INTO products (name, slug, description, price, stock, category_id, is_active) VALUES (?,?,?,?,?,?,?)')
                ->execute([$name, slugify($name), $description, $price, $stock, $categoryId, $isActive]);
            $id = (int)$pdo->lastInsertId();
            /* Refresh slug with id suffix for uniqueness */
            $pdo->prepare('UPDATE products SET slug=? WHERE id=?')->execute([slugify($name) . '-' . $id, $id]);
        }

        /* Multiple image upload */
        if (!empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $hasPrimary = (int)$pdo->query('SELECT COUNT(*) FROM product_images WHERE product_id=' . (int)$id . ' AND is_primary=1')->fetchColumn() > 0;
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $single = [
                    'name' => $files['name'][$i], 'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ];
                $path = handle_image_upload($single);
                if ($path) {
                    $primary = $hasPrimary ? 0 : 1;
                    $hasPrimary = true;
                    $pdo->prepare('INSERT INTO product_images (product_id, path, is_primary, sort_order) VALUES (?,?,?,?)')
                        ->execute([$id, $path, $primary, $i]);
                }
            }
        }
        redirect(url('admin/product_edit.php?id=' . $id . '&saved=1'));
    }
}

/* Delete an image */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'del_image') {
    csrf_check();
    $imgId = (int)($_POST['image_id'] ?? 0);
    $pdo->prepare('DELETE FROM product_images WHERE id=? AND product_id=?')->execute([$imgId, $id]);
    redirect(url('admin/product_edit.php?id=' . $id));
}

$pageTitle = $id ? 'Edit Produk' : 'Produk Baru';
require __DIR__ . '/includes/admin_header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="alert glass ok">Produk tersimpan.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert glass"><?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="panel glass form-panel">
    <?= csrf_field() ?>
    <div class="field"><label>Nama Produk</label><input name="name" value="<?= e($product['name']) ?>" required></div>
    <div class="field"><label>Deskripsi</label><textarea name="description" rows="5"><?= e($product['description']) ?></textarea></div>
    <div class="field-row">
        <div class="field"><label>Harga</label><input type="number" step="0.01" name="price" value="<?= e((string)$product['price']) ?>" required></div>
        <div class="field"><label>Stok</label><input type="number" name="stock" value="<?= e((string)$product['stock']) ?>" required></div>
    </div>
    <div class="field-row">
        <div class="field">
            <label>Kategori</label>
            <select name="category_id">
                <option value="">— Tanpa kategori —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field checkbox"><label><input type="checkbox" name="is_active" <?= $product['is_active']?'checked':'' ?>> Aktif</label></div>
    </div>
    <div class="field"><label>Gambar (bisa banyak)</label><input type="file" name="images[]" accept="image/*" multiple></div>

    <?php if ($images): ?>
    <div class="img-grid">
        <?php foreach ($images as $img): ?>
            <div class="img-cell glass">
                <img src="<?= product_image_url($img['path']) ?>" alt="">
                <?php if ($img['is_primary']): ?><span class="badge completed">Utama</span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="form-actions">
        <a class="btn btn-ghost" href="<?= url('admin/products.php') ?>">Kembali</a>
        <button class="btn btn-primary" type="submit">Simpan</button>
    </div>
</form>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
