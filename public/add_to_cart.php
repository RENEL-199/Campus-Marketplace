<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/CartRepository.php';

require_login();

$user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if ($product_id <= 0) {
    header('Location: cart.php?cart_error=' . urlencode('Invalid product.'));
    exit;
}

try {
    $db = new Database();
    $cartRepo = new CartRepository($db->pdo);
    $cartRepo->addItem($user_id, $product_id, $quantity, $_POST, $_FILES);

    header('Location: cart.php');
    exit;
} catch (Throwable $e) {
    header('Location: cart.php?cart_error=' . urlencode($e->getMessage()));
    exit;
}