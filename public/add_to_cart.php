<?php
session_start();

if (!isset($_SESSION['cart_items'])) {
    $_SESSION['cart_items'] = [];
}

$product_id = $_POST['product_id'] ?? null;
$name = $_POST['name'] ?? '';
$price = (float)($_POST['price'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);
$image = $_POST['image'] ?? '';

if (!$product_id) {
    die("Invalid product");
}

/* CHECK IF ALREADY IN CART */
$found = false;

foreach ($_SESSION['cart_items'] as &$item) {
    if ($item['cart_id'] == $product_id) {
        $item['quantity'] += $quantity;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['cart_items'][] = [
        "cart_id" => $product_id,
        "name" => $name,
        "price" => $price,
        "quantity" => $quantity,
        "image" => $image
    ];
}

header("Location: cart.php");
exit;