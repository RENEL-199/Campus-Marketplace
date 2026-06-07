<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: cart.php?cart_error=' . urlencode('Invalid security token.'));
    exit;
}

$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

$date_from = $_POST['date_from'] ?? null;
$date_to = $_POST['date_to'] ?? null;

$full_name = $_POST['full_name'] ?? null;
$student_no = $_POST['student_no'] ?? null;
$age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
$gender = $_POST['gender'] ?? null;

if ($cart_item_id <= 0 || $product_id <= 0) {
    header("Location: cart.php?cart_error=" . urlencode('Invalid cart item.'));
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

$stmt = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?,
        date_from = ?,
        date_to = ?,
        full_name = ?,
        student_no = ?,
        age = ?,
        gender = ?
    WHERE user_id = ? AND cart_item_id = ?
");

$stmt->execute([
    $quantity,
    $date_from,
    $date_to,
    $full_name,
    $student_no,
    $age,
    $gender,
    $user_id,
    $cart_item_id
]);

header("Location: cart.php");
exit;
