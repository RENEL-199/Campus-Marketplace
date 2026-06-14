<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/CartRepository.php';
require_once __DIR__ . '/../app/OrderRepository.php';
require_once __DIR__ . '/../app/csrf.php';

require_login();
$user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: cart.php?cart_error=' . urlencode('Invalid security token.'));
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');
$isGcash = strtolower($payment_method) === 'gcash';
$selectedItems = $_POST['selected_items'] ?? $_SESSION['checkout_selected_items'] ?? [];
$rentalTermsAccepted = !empty($_POST['rental_terms_accepted']);

if ($fullname === '' || $address === '' || $phone === '') {
    header('Location: checkout.php?error=' . urlencode('Please fill in the receiving person, address, and contact number.'));
    exit;
}

try {
        $db = new Database();
    ensureColumn($db->pdo, 'orders', 'payment_proof_path', "payment_proof_path VARCHAR(255) DEFAULT NULL");
    $cartRepo = new CartRepository($db->pdo);
    $items = $cartRepo->getSelectedItems($user_id, (array)$selectedItems);
    $hasRental = false;
    foreach ($items as $item) {
        if (strtolower((string)($item['category_type'] ?? 'product')) === 'rental') {
            $hasRental = true;
            break;
        }
    }

    if ($hasRental && !$rentalTermsAccepted) {
        header('Location: checkout.php?error=' . urlencode('You must accept the rental terms before placing a rental order.'));
        exit;
    }

    $paymentProofPath = null;
    if ($isGcash) {
        $uploadDir = $hasRental ? __DIR__ . '/uploads/rental_receipts/' : __DIR__ . '/uploads/gcash_receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!isset($_FILES['payment_proof']) || !is_uploaded_file($_FILES['payment_proof']['tmp_name'] ?? '')) {
            header('Location: checkout.php?error=' . urlencode($hasRental ? 'Please upload your GCash payment proof for the rental reservation.' : 'Please upload your GCash payment proof.'));
            exit;
        }
        $ext = strtolower(pathinfo((string)($_FILES['payment_proof']['name'] ?? ''), PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true) || ($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            header('Location: checkout.php?error=' . urlencode('Invalid payment proof file.'));
            exit;
        }
        $stored = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadDir . $stored)) {
            header('Location: checkout.php?error=' . urlencode('Could not save payment proof.'));
            exit;
        }
        $paymentProofPath = ($hasRental ? 'uploads/rental_receipts/' : 'uploads/gcash_receipts/') . $stored;
    }

    $orderRepo = new OrderRepository($db->pdo);
    $orderId = $orderRepo->placeOrder($user_id, (array)$selectedItems, $fullname, $address, $phone, $payment_method, $paymentProofPath, $rentalTermsAccepted);
    unset($_SESSION['checkout_selected_items']);
    header('Location: receipt.php?id=' . $orderId);
    exit;
} catch (Throwable $e) {
    header('Location: checkout.php?error=' . urlencode($e->getMessage()));
    exit;
}

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->query("SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "'");
    if ($stmt->fetch()) {
        return;
    }
    $pdo->exec("ALTER TABLE `" . $table . "` ADD COLUMN " . $definition);
}
