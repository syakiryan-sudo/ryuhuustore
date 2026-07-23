<?php
/**
 * cart_action.php — AJAX + form endpoint for cart mutations.
 * Actions: add, update, remove, clear. Returns JSON for AJAX (X-Requested-With).
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('cart.php'));
}
csrf_check();

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$qty    = (int)($_POST['qty'] ?? 1);

switch ($action) {
    case 'add':    cart_add($id, max(1, $qty)); break;
    case 'update': cart_update($id, $qty);      break;
    case 'remove': cart_remove($id);            break;
    case 'clear':  cart_clear();                break;
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok'       => true,
        'count'    => cart_count(),
        'subtotal' => cart_subtotal(),
        'subtotal_fmt' => money(cart_subtotal()),
    ]);
    exit;
}
redirect(url('cart.php'));
