<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$db = new Database();
$pdo = $db->pdo;
$user_id = current_user_id();

if (!$user_id) {
    header('Location: login.php');
    exit;
}

$checkUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
$checkUser->execute([$user_id]);
if (!$checkUser->fetch()) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');

$selected_items = $_POST['selected_items'] ?? [];
if (!is_array($selected_items)) {
    $selected_items = [$selected_items];
}

$selected_items = array_values(array_unique(array_filter(array_map('intval', $selected_items), function ($id) {
    return $id > 0;
})));

if (empty($selected_items)) {
    header('Location: cart.php?checkout_error=no_selected');
    exit;
}

if ($fullname === '' || $address === '' || $phone === '') {
    header('Location: checkout.php');
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    payment_method VARCHAR(100) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    rate_type VARCHAR(50) DEFAULT NULL,
    date_from DATE DEFAULT NULL,
    date_to DATE DEFAULT NULL,
    rental_duration INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    service_files TEXT DEFAULT NULL,
    print_type VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(prod_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* Add these columns too if order_items already existed before this fix */
$columnsToAdd = [
    "rate_type VARCHAR(50) DEFAULT NULL",
    "date_from DATE DEFAULT NULL",
    "date_to DATE DEFAULT NULL",
    "rental_duration INT NOT NULL DEFAULT 1",
    "subtotal DECIMAL(10,2) NOT NULL DEFAULT 0",
    "service_files TEXT DEFAULT NULL",
    "print_type VARCHAR(50) DEFAULT NULL"
];

foreach ($columnsToAdd as $columnSql) {
    $columnName = strtok($columnSql, ' ');
    try {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN $columnSql");
    } catch (PDOException $e) {
        // Column already exists. Ignore.
    }
}

$placeholders = implode(',', array_fill(0, count($selected_items), '?'));

$stmt = $pdo->prepare("
    SELECT c.*, p.prod_name, p.prod_price, p.prod_rate_type
    FROM cart_items c
    JOIN products p ON p.prod_id = c.product_id
    WHERE c.user_id = ?
    AND c.product_id IN ($placeholders)
");
$stmt->execute(array_merge([$user_id], $selected_items));
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

function getRentalDurationDays(?string $from, ?string $to): int {
    if (empty($from) || empty($to)) {
        return 1;
    }

    $start = strtotime($from);
    $end = strtotime($to);

    if ($start === false || $end === false || $end < $start) {
        return 1;
    }

    // Inclusive count: May 1 to May 2 = 2 days
    $days = (int)floor(($end - $start) / 86400) + 1;
    return max(1, $days);
}

function rentalUnits(?string $rateType, ?string $from, ?string $to): int {
    $rate = strtolower(trim($rateType ?? ''));
    $days = getRentalDurationDays($from, $to);

    if ($rate === 'per day' || $rate === 'per hour') {
        return $days;
    }

    return 1;
}

function calculateOrderSubtotal(float $price, int $quantity, ?string $rateType, ?string $from, ?string $to): float {
    $units = rentalUnits($rateType, $from, $to);
    return $price * $quantity * $units;
}

$total = 0;
foreach ($items as $item) {
    $total += calculateOrderSubtotal(
        (float)$item['prod_price'],
        (int)$item['quantity'],
        $item['prod_rate_type'] ?? null,
        $item['date_from'] ?? null,
        $item['date_to'] ?? null
    );
}

$pdo->beginTransaction();

try {
    $stockCheck = $pdo->prepare("SELECT prod_stock FROM products WHERE prod_id = ? FOR UPDATE");

    foreach ($items as $item) {
        $stockCheck->execute([$item['product_id']]);
        $currentStock = $stockCheck->fetchColumn();

        if ($currentStock === false) {
            throw new Exception('Order failed: Product not found.');
        }

        if ((int)$currentStock < (int)$item['quantity']) {
            throw new Exception('Order failed: Insufficient stock for ' . $item['prod_name'] . '.');
        }
    }

    $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, fullname, address, phone, payment_method, total) VALUES (?, ?, ?, ?, ?, ?)");
    $orderStmt->execute([
        $user_id,
        $fullname,
        $address,
        $phone,
        $payment_method,
        $total
    ]);

    $order_id = $pdo->lastInsertId();

    $insertItem = $pdo->prepare("
        INSERT INTO order_items
        (order_id, product_id, quantity, price, rate_type, date_from, date_to, rental_duration, subtotal, service_files, print_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $updateStock = $pdo->prepare("UPDATE products SET prod_stock = prod_stock - ? WHERE prod_id = ?");

    foreach ($items as $item) {
        $rateType = $item['prod_rate_type'] ?? null;
        if (strtolower(trim((string)$rateType)) === 'per hour') {
            $rateType = 'Per Day';
        }
        $dateFrom = $item['date_from'] ?? null;
        $dateTo = $item['date_to'] ?? null;
        $duration = rentalUnits($rateType, $dateFrom, $dateTo);
        $subtotal = calculateOrderSubtotal(
            (float)$item['prod_price'],
            (int)$item['quantity'],
            $rateType,
            $dateFrom,
            $dateTo
        );

        $insertItem->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['prod_price'],
            $rateType,
            $dateFrom,
            $dateTo,
            $duration,
            $subtotal,
            $item['service_files'] ?? null,
            $item['print_type'] ?? null
        ]);

        $updateStock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    $deletePlaceholders = implode(',', array_fill(0, count($selected_items), '?'));
    $deleteStmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id IN ($deletePlaceholders)");
    $deleteStmt->execute(array_merge([$user_id], $selected_items));

    $pdo->commit();

    header('Location: receipt.php?id=' . $order_id);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}
