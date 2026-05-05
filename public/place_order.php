<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$db = new Database();
$pdo = $db->pdo;

$user_id = current_user_id();

/* CART */
$stmt = $pdo->prepare("
    SELECT c.*, p.price, p.stock
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id=?
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

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
    $pdo->prepare("DELETE FROM cart WHERE user_id=?")
        ->execute([$user_id]);

    $pdo->commit();

    header("Location: receipt.php?id=" . $order_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}