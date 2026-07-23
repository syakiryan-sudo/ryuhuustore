<?php
require_once __DIR__ . '/config.php';

/* "Buy now" quick add then show cart */
if (isset($_GET['buy'])) {
    cart_add((int)$_GET['buy'], 1);
    redirect(url('cart.php'));
}

$items = cart();
$subtotal = cart_subtotal();
$shipping = $items ? (float)setting('shipping_flat', '0') : 0;
$pageTitle = 'Keranjang';
require __DIR__ . '/includes/header.php';
?>

<section class="section reveal">
    <div class="section-head"><h2>Keranjang</h2></div>

    <?php if (!$items): ?>
        <div class="empty glass">
            <p class="muted">Keranjang kamu masih kosong.</p>
            <a class="btn btn-primary" href="<?= url('index.php#produk') ?>">Mulai belanja</a>
        </div>
    <?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($items as $item): ?>
            <div class="cart-item glass" data-row="<?= (int)$item['id'] ?>">
                <img src="<?= product_image_url($item['image']) ?>" alt="<?= e($item['name']) ?>">
                <div class="cart-item-main">
                    <div class="cart-item-name"><?= e($item['name']) ?></div>
                    <div class="muted price" data-unit="<?= (float)$item['price'] ?>"><?= money($item['price']) ?></div>
                </div>
                <div class="stepper glass">
                    <button type="button" data-cart-step="-1" aria-label="Kurangi">−</button>
                    <input type="number" value="<?= (int)$item['qty'] ?>" min="1" data-cart-qty="<?= (int)$item['id'] ?>" inputmode="numeric">
                    <button type="button" data-cart-step="1" aria-label="Tambah">+</button>
                </div>
                <div class="cart-item-total"><?= money($item['price'] * $item['qty']) ?></div>
                <button class="icon-btn" data-cart-remove="<?= (int)$item['id'] ?>" aria-label="Hapus">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <aside class="cart-summary glass">
            <h3>Ringkasan</h3>
            <div class="row"><span class="muted">Subtotal</span><span id="sumSubtotal"><?= money($subtotal) ?></span></div>
            <div class="row"><span class="muted">Pengiriman</span><span><?= money($shipping) ?></span></div>
            <div class="row total"><span>Total</span><span><?= money($subtotal + $shipping) ?></span></div>
            <a class="btn btn-primary block" href="<?= url('checkout.php') ?>">Checkout</a>
            <form method="post" action="<?= url('cart_action.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-ghost block" type="submit">Kosongkan keranjang</button>
            </form>
        </aside>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
