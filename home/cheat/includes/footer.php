</main>
<footer class="footer">
    <div class="container footer-inner">
        <div>
            <div class="brand small"><?= e(setting('store_name', 'Liquid')) ?></div>
            <p class="muted"><?= e(setting('store_tagline', 'Objects of pure clarity.')) ?></p>
        </div>
        <nav class="footer-links">
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('index.php#produk') ?>">Produk</a>
            <a href="<?= url('cart.php') ?>">Keranjang</a>
            <a href="<?= url('admin/login.php') ?>">Admin</a>
        </nav>
    </div>
    <div class="container copyright muted">© <?= date('Y') ?> <?= e(setting('store_name', 'Liquid')) ?>. All rights reserved.</div>
</footer>
<script src="<?= url('assets/js/script.js') ?>"></script>
</body>
</html>
