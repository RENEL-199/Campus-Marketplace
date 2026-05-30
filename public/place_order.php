<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/OrderRepository.php';

require_login();
$user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');
$selectedItems = $_POST['selected_items'] ?? $_SESSION['checkout_selected_items'] ?? [];

if ($fullname === '' || $address === '' || $phone === '') {
    header('Location: checkout.php');
    exit;
}

try {
    $db = new Database();
    $orderRepo = new OrderRepository($db->pdo);
    $orderId = $orderRepo->placeOrder($user_id, (array)$selectedItems, $fullname, $address, $phone, $payment_method);
    unset($_SESSION['checkout_selected_items']);
    header('Location: receipt.php?id=' . $orderId);
    exit;
} catch (Throwable $e) {
    die('Order failed: ' . htmlspecialchars($e->getMessage()));
}
