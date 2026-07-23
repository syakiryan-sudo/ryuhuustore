<?php
/**
 * functions.php — Shared helpers: security, escaping, cart, cache, pagination.
 */
declare(strict_types=1);

/* ---------- Output escaping ---------- */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- Redirect ---------- */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/* ---------- URL helper ---------- */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/* ---------- Money formatting ---------- */
function money($amount): string
{
    return setting('currency', 'Rp') . ' ' . number_format((float)$amount, 0, ',', '.');
}

/* ---------- Slugify ---------- */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : substr(md5((string)microtime(true)), 0, 8);
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

/* ---------- Simple file cache (settings/categories) ---------- */
function cache_get(string $key, int $ttl)
{
    $file = CACHE_DIR . '/' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.cache';
    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $data = @file_get_contents($file);
        if ($data !== false) {
            return unserialize($data);
        }
    }
    return null;
}
function cache_set(string $key, $value): void
{
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0775, true);
    }
    $file = CACHE_DIR . '/' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.cache';
    @file_put_contents($file, serialize($value), LOCK_EX);
}
function cache_forget(string $key): void
{
    $file = CACHE_DIR . '/' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.cache';
    if (is_file($file)) {
        @unlink($file);
    }
}

/* ---------- Settings (cached key/value) ---------- */
function settings_all(): array
{
    $cached = cache_get('settings', 300);
    if (is_array($cached)) {
        return $cached;
    }
    $rows = db()->query('SELECT `key`, `value` FROM settings')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['key']] = $r['value'];
    }
    cache_set('settings', $out);
    return $out;
}
function setting(string $key, ?string $default = null): ?string
{
    $all = settings_all();
    return $all[$key] ?? $default;
}

/* ---------- Categories (cached) ---------- */
function all_categories(): array
{
    $cached = cache_get('categories', 300);
    if (is_array($cached)) {
        return $cached;
    }
    $rows = db()->query('SELECT id, name, slug, icon FROM categories ORDER BY name ASC')->fetchAll();
    cache_set('categories', $rows);
    return $rows;
}

/* ---------- Pagination helper ---------- */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;
    return ['page' => $page, 'pages' => $pages, 'offset' => $offset, 'perPage' => $perPage, 'total' => $total];
}

function current_page_param(string $name = 'page'): int
{
    return max(1, (int)($_GET[$name] ?? 1));
}

/* ---------- Cart (session-based) ---------- */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}
function cart_count(): int
{
    $n = 0;
    foreach (cart() as $item) {
        $n += (int)$item['qty'];
    }
    return $n;
}
function cart_add(int $productId, int $qty = 1): void
{
    $qty = max(1, $qty);
    $stmt = db()->prepare('SELECT p.id, p.name, p.price, p.stock,
        (SELECT path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS image
        FROM products p WHERE p.id = ? AND p.is_active = 1');
    $stmt->execute([$productId]);
    $p = $stmt->fetch();
    if (!$p) {
        return;
    }
    $cart = cart();
    $existing = $cart[$productId]['qty'] ?? 0;
    $newQty = min((int)$p['stock'], $existing + $qty);
    $cart[$productId] = [
        'id'    => (int)$p['id'],
        'name'  => $p['name'],
        'price' => (float)$p['price'],
        'image' => $p['image'],
        'qty'   => max(1, $newQty),
    ];
    $_SESSION['cart'] = $cart;
}
function cart_update(int $productId, int $qty): void
{
    $cart = cart();
    if (!isset($cart[$productId])) {
        return;
    }
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId]['qty'] = $qty;
    }
    $_SESSION['cart'] = $cart;
}
function cart_remove(int $productId): void
{
    $cart = cart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}
function cart_clear(): void
{
    unset($_SESSION['cart']);
}
function cart_subtotal(): float
{
    $sum = 0.0;
    foreach (cart() as $item) {
        $sum += (float)$item['price'] * (int)$item['qty'];
    }
    return $sum;
}

/* ---------- Product image URL ---------- */
function product_image_url(?string $path): string
{
    if ($path) {
        return UPLOAD_URL . '/' . e($path);
    }
    return url('assets/img/placeholder.svg');
}

/* ---------- Image upload (validated) ---------- */
function handle_image_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        return null;
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return $name;
}

/* ---------- Status label / badge ---------- */
function status_label(string $status): string
{
    return [
        'pending'   => 'Pending',
        'paid'      => 'Dibayar',
        'shipped'   => 'Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ][$status] ?? ucfirst($status);
}

/* ---------- Invoice generator ---------- */
function generate_invoice(): string
{
    return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}
