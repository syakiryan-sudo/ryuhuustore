<?php /* Reusable product card. Expects $p with keys: id, name, slug, price, image */ ?>
<article class="card glass">
    <a class="card-media" href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
        <img src="<?= product_image_url($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    </a>
    <div class="card-body">
        <a class="card-title" href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['name']) ?></a>
        <div class="card-foot">
            <span class="price"><?= money($p['price']) ?></span>
            <button class="btn-add" data-add-to-cart data-id="<?= (int)$p['id'] ?>" aria-label="Tambah ke keranjang">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </button>
        </div>
    </div>
</article>
