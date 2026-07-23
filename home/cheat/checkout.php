<?php
require_once __DIR__ . '/config.php';

$items = cart();
if (!$items) {
    redirect(url('cart.php'));
}

$errors = [];
$old = ['name'=>'','phone'=>'','address'=>'','city'=>'','postal_code'=>'','payment_method'=>'bank_transfer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) {
        $old[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if ($old['name'] === '')        $errors[] = 'Nama wajib diisi.';
    if ($old['phone'] === '')       $errors[] = 'Nomor telepon wajib diisi.';
    if ($old['address'] === '')     $errors[] = 'Alamat wajib diisi.';
    if ($old['city'] === '')        $errors[] = 'Kota wajib diisi.';
    if ($old['postal_code'] === '') $errors[] = 'Kode pos wajib diisi.';
    $methods = ['bank_transfer','ewallet','cod'];
    if (!in_array($old['payment_method'], $methods, true)) $errors[] = 'Metode pembayaran tidak valid.';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $subtotal = 0.0;
            $lines = [];
            /* Lock product rows & re-check stock/price server-side */
            $stmt = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');
            foreach ($items as $it) {
                $stmt->execute([(int)$it['id']]);
                $p = $stmt->fetch();
                if (!$p || (int)$p['stock'] < (int)$it['qty']) {
                    throw new RuntimeException('Stok tidak mencukupi untuk: ' . ($p['name'] ?? 'produk'));
                }
                $line = (float)$p['price'] * (int)$it['qty'];
                $subtotal += $line;
                $lines[] = ['id'=>(int)$p['id'],'name'=>$p['name'],'price'=>(float)$p['price'],'qty'=>(int)$it['qty'],'line'=>$line];
            }

            $shipping = ($old['payment_method'] === 'cod') ? 0 : (float)setting('shipping_flat', '0');
            $total = $subtotal + $shipping;
            $invoice = generate_invoice();

            /* Optional: attach/create a lightweight customer record */
            $userId = null;

            $ins = $pdo->prepare('INSERT INTO orders
                (invoice, user_id, customer_name, phone, address, city, postal_code, payment_method, subtotal, shipping, total, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?, "pending")');
            $ins->execute([$invoice, $userId, $old['name'], $old['phone'], $old['address'], $old['city'], $old['postal_code'], $old['payment_method'], $subtotal, $shipping, $total]);
            $orderId = (int)$pdo->lastInsertId();

            $itemIns = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, qty, line_total) VALUES (?,?,?,?,?,?)');
            $stockUpd = $pdo->prepare('UPDATE products SET stock = stock - ?, sold = sold + ? WHERE id = ?');
            foreach ($lines as $l) {
                $itemIns->execute([$orderId, $l['id'], $l['name'], $l['price'], $l['qty'], $l['line']]);
                $stockUpd->execute([$l['qty'], $l['qty'], $l['id']]);
            }

            $pdo->commit();
            cart_clear();
            redirect(url('order_success.php?inv=' . urlencode($invoice)));
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Gagal memproses pesanan: ' . $ex->getMessage();
        }
    }
}

$subtotal = cart_subtotal();
$shipping = (float)setting('shipping_flat', '0');
$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<section class="section reveal">
    <div class="section-head"><h2>Checkout</h2></div>

    <?php if ($errors): ?>
        <div class="alert glass"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" class="checkout-layout" id="checkoutForm" novalidate>
        <?= csrf_field() ?>
        <div class="checkout-form glass">
            <h3>Alamat Pengiriman</h3>
            <div class="field"><label>Nama Lengkap</label><input name="name" value="<?= e($old['name']) ?>" required></div>
            <div class="field"><label>Nomor Telepon</label><input name="phone" value="<?= e($old['phone']) ?>" inputmode="tel" required></div>
            <div class="field"><label>Alamat</label><textarea name="address" rows="2" required><?= e($old['address']) ?></textarea></div>
            <div class="field-row">
                <div class="field"><label>Kota</label><input name="city" value="<?= e($old['city']) ?>" required></div>
                <div class="field"><label>Kode Pos</label><input name="postal_code" value="<?= e($old['postal_code']) ?>" inputmode="numeric" required></div>
            </div>

            <h3>Metode Pembayaran</h3>
            <div class="pay-options">
                <label class="pay glass"><input type="radio" name="payment_method" value="bank_transfer" <?= $old['payment_method']==='bank_transfer'?'checked':'' ?>><span>Transfer Bank</span></label>
                <label class="pay glass"><input type="radio" name="payment_method" value="ewallet" <?= $old['payment_method']==='ewallet'?'checked':'' ?>><span>E-Wallet</span></label>
                <label class="pay glass"><input type="radio" name="payment_method" value="cod" <?= $old['payment_method']==='cod'?'checked':'' ?>><span>COD</span></label>
            </div>
        </div>

        <aside class="cart-summary glass">
            <h3>Ringkasan Pesanan</h3>
            <?php foreach ($items as $it): ?>
                <div class="row"><span class="muted"><?= e($it['name']) ?> × <?= (int)$it['qty'] ?></span><span><?= money($it['price'] * $it['qty']) ?></span></div>
            <?php endforeach; ?>
            <hr class="hair">
            <div class="row"><span class="muted">Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="row"><span class="muted">Pengiriman</span><span><?= money($shipping) ?></span></div>
            <div class="row total"><span>Total</span><span><?= money($subtotal + $shipping) ?></span></div>
            <button class="btn btn-primary block" type="submit">Buat Pesanan</button>
        </aside>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
