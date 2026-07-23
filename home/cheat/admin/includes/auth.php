<?php
/** admin/includes/auth.php — Session guard for the admin area. */
require_once __DIR__ . '/../../config.php';

function admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): void
{
    if (!admin()) {
        redirect(url('admin/login.php'));
    }
}
