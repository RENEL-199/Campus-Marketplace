<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

$date_from = $_POST['date_from'] ?? null;
$date_to = $_POST['date_to'] ?? null;

$borrower_name = $_POST['borrower_name'] ?? null;
$student_no = $_POST['student_no'] ?? null;
$age = (int)($_POST['age'] ?? 0);
$gender = $_POST['gender'] ?? null;

$stmt = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?,
        date_from = ?,
        date_to = ?,
        borrower_name = ?,
        student_no = ?,
        age = ?,
        gender = ?
    WHERE user_id = ? AND product_id = ?
");

$stmt->execute([
    $quantity,
    $date_from,
    $date_to,
    $borrower_name,
    $student_no,
    $age,
    $gender,
    $user_id,
    $product_id
]);

header("Location: cart.php");
exit;