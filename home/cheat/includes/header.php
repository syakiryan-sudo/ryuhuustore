<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title><?= e($pageTitle ?? setting('store_name', 'Liquid')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="icon" href="<?= url('assets/img/placeholder.svg') ?>">
</head>
<body>
<div class="bg-aurora" aria-hidden="true"></div>

<header class="nav" id="nav">
    <div class="container nav-inner">
        <a class="brand" href="<?= url('index.php') ?>"><?= e(setting('store_name', 'Liquid')) ?></a>
        <nav class="nav-links" aria-label="Primary">
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('index.php#produk') ?>">Produk</a>
            <a href="<?= url('index.php#kategori') ?>">Kategori</a>
            <a href="<?= url('payment_confirmation.php') ?>">Pesanan</a>
        </nav>
        <a class="cart-pill" href="<?= url('cart.php') ?>" aria-label="Keranjang">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6 5 3H2"/></svg>
            <span class="cart-count" id="cartCount"><?= cart_count() ?></span>
        </a>
    </div>
</header>
<main class="container page">
