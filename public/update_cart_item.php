<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

$date_from = $_POST['date_from'] ?? null;
$date_to = $_POST['date_to'] ?? null;

$full_name = $_POST['full_name'] ?? null;
$student_no = $_POST['student_no'] ?? null;
$age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
$gender = $_POST['gender'] ?? null;

if ($product_id <= 0) {
    header("Location: cart.php");
    exit;
}

$stockStmt = $pdo->prepare("SELECT prod_stock FROM products WHERE prod_id = ? LIMIT 1");
$stockStmt->execute([$product_id]);
$availableStock = $stockStmt->fetchColumn();

if ($availableStock === false) {
    header("Location: cart.php?cart_error=not_found");
    exit;
}

$availableStock = (int)$availableStock;

if ($quantity > $availableStock) {
    header("Location: cart.php?cart_error=stock&available=" . $availableStock);
    exit;
}

// Update quantity on cart_items
$stmt = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE user_id = ? AND product_id = ?
");
$stmt->execute([$quantity, $user_id, $product_id]);

// Find the cart_item_id to update rental details
$cartStmt = $pdo->prepare("SELECT cart_item_id FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1");
$cartStmt->execute([$user_id, $product_id]);
$cartItemId = $cartStmt->fetchColumn();

if ($cartItemId && $date_from && $date_to) {
    $rentalDays = max(1, (int)floor((strtotime($date_to) - strtotime($date_from)) / 86400) + 1);
    $updateRental = $pdo->prepare("
        UPDATE rental_details
        SET date_from = ?, date_to = ?, rental_days = ?, borrower_name = ?, student_no = ?, age = ?, gender = ?
        WHERE ref_type = 'cart' AND ref_id = ?
    ");
    $updateRental->execute([
        $date_from,
        $date_to,
        $rentalDays,
        $full_name ?? '',
        $student_no ?? '',
        $age,
        $gender,
        $cartItemId
    ]);
}

header("Location: cart.php");
exit;
