<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$db = new Database();
$pdo = $db->pdo;

$user_id = current_user_id();

$cartItems = $_SESSION['cart'] ?? [];
if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

$productIds = array_keys($cartItems);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));

$stmt = $pdo->prepare("SELECT id, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute($productIds);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($products as $product) {
    $quantity = min($cartItems[$product['id']], max(0, $product['stock']));
    if ($quantity > 0) {
        $items[] = [
            'product_id' => $product['id'],
            'quantity' => $quantity,
            'price' => $product['price'],
        ];
    }
}

$pdo->beginTransaction();

try {

    $total = 0;
    foreach ($items as $i) {
        $total += $i['price'] * $i['quantity'];
    }

    /* ORDER */
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, fullname, address, phone, total)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $_POST['fullname'],
        $_POST['address'],
        $_POST['phone'],
        $total
    ]);

    $order_id = $pdo->lastInsertId();

    /* ITEMS + STOCK */
    foreach ($items as $i) {

        $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $order_id,
            $i['product_id'],
            $i['quantity'],
            $i['price']
        ]);

        $pdo->prepare("
            UPDATE products SET stock = stock - ?
            WHERE id=?
        ")->execute([$i['quantity'], $i['product_id']]);
    }

    /* CLEAR CART */
    unset($_SESSION['cart']);
    setcookie("cart_count", '', time() - 3600, "/");

    $pdo->prepare("DELETE FROM cart WHERE user_id=?")
        ->execute([$user_id]);

    $pdo->commit();

    header("Location: receipt.php?id=" . $order_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}