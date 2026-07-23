<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $icon = trim((string)($_POST['icon'] ?? ''));
        $slug = $slug !== '' ? slugify($slug) : slugify($name);
        if ($name === '') {
            $errors[] = 'Nama kategori wajib diisi.';
        } else {
            if ($id) {
                $pdo->prepare('UPDATE categories SET name=?, slug=?, icon=? WHERE id=?')->execute([$name, $slug, $icon ?: null, $id]);
            } else {
                $pdo->prepare('INSERT INTO categories (name, slug, icon) VALUES (?,?,?)')->execute([$name, $slug, $icon ?: null]);
            }
            cache_forget('categories');
            redirect(url('admin/categories.php'));
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
        cache_forget('categories');
        redirect(url('admin/categories.php'));
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$edit = ['id'=>0,'name'=>'','slug'=>'','icon'=>''];
if ($editId) {
    $s = $pdo->prepare('SELECT * FROM categories WHERE id=? LIMIT 1');
    $s->execute([$editId]);
    $edit = $s->fetch() ?: $edit;
}

$cats = $pdo->query('SELECT c.id, c.name, c.slug, c.icon, COUNT(p.id) AS product_count
    FROM categories c LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.slug, c.icon ORDER BY c.name ASC')->fetchAll();

$pageTitle = 'Kategori';
require __DIR__ . '/includes/admin_header.php';
?>
<?php if ($errors): ?><div class="alert glass"><?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="two-col">
    <form method="post" class="panel glass form-panel">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <h3><?= $edit['id'] ? 'Edit Kategori' : 'Kategori Baru' ?></h3>
        <div class="field"><label>Nama</label><input name="name" value="<?= e($edit['name']) ?>" required></div>
        <div class="field"><label>Slug (opsional)</label><input name="slug" value="<?= e($edit['slug']) ?>" placeholder="otomatis dari nama"></div>
        <div class="field"><label>Ikon (opsional)</label><input name="icon" value="<?= e($edit['icon'] ?? '') ?>" placeholder="mis. headphones"></div>
        <div class="form-actions">
            <?php if ($edit['id']): ?><a class="btn btn-ghost" href="<?= url('admin/categories.php') ?>">Batal</a><?php endif; ?>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </form>

    <div class="panel glass">
        <table class="table">
            <thead><tr><th>Nama</th><th>Slug</th><th>Produk</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($cats as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td class="muted"><?= e($c['slug']) ?></td>
                    <td><?= (int)$c['product_count'] ?></td>
                    <td class="actions-cell">
                        <a class="link" href="<?= url('admin/categories.php?edit=' . (int)$c['id']) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus kategori ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="link danger" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cats): ?><tr><td colspan="4" class="muted">Belum ada kategori.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
