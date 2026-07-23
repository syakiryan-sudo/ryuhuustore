<?php require_once __DIR__ . '/auth.php'; require_admin(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Admin') ?> · <?= e(setting('store_name', 'Liquid')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="admin">
<div class="bg-aurora" aria-hidden="true"></div>
<div class="admin-shell">
    <aside class="sidebar glass">
        <a class="brand" href="<?= url('admin/index.php') ?>"><?= e(setting('store_name', 'Liquid')) ?><span class="muted"> admin</span></a>
        <nav class="side-nav">
            <?php $cur = basename($_SERVER['PHP_SELF']); ?>
            <a href="<?= url('admin/index.php') ?>"      class="<?= $cur==='index.php'?'active':'' ?>">Dashboard</a>
            <a href="<?= url('admin/products.php') ?>"   class="<?= in_array($cur,['products.php','product_edit.php'])?'active':'' ?>">Produk</a>
            <a href="<?= url('admin/categories.php') ?>" class="<?= $cur==='categories.php'?'active':'' ?>">Kategori</a>
            <a href="<?= url('admin/orders.php') ?>"     class="<?= in_array($cur,['orders.php','order_view.php'])?'active':'' ?>">Pesanan</a>
            <a href="<?= url('admin/customers.php') ?>"  class="<?= $cur==='customers.php'?'active':'' ?>">Pelanggan</a>
            <a href="<?= url('admin/reports.php') ?>"    class="<?= $cur==='reports.php'?'active':'' ?>">Laporan</a>
        </nav>
        <div class="side-foot">
            <span class="muted small"><?= e(admin()['name'] ?? 'Admin') ?></span>
            <a class="link" href="<?= url('admin/logout.php') ?>">Keluar</a>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-topbar">
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
            <a class="btn btn-ghost" href="<?= url('index.php') ?>" target="_blank">Lihat Toko</a>
        </div>
