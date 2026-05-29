<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$db = new Database();
$pdo = $db->pdo;
$user_id = current_user_id();

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
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(prod_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $pdo->prepare("SELECT o.id, o.total, o.created_at, COUNT(oi.id) AS item_count, MIN(p.prod_image) AS sample_image, MIN(p.prod_name) AS sample_name
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.prod_id = oi.product_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Order History</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f7f5;
    color: #111;
}

nav {
    height: 58px;
    background: #810C01;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 26px;
}

nav h1 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 18px;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 12px;
}

nav i {
    margin-right: 4px;
    font-size: 13px;
}

.history-container {
    width: 1035px;
    min-height: 580px;
    background: white;
    margin: 34px auto;
    border-radius: 24px;
    padding: 30px 64px;
    box-shadow: 0 3px 4px rgba(0,0,0,0.25);
}

.history-container h2 {
    font-size: 34px;
    margin-bottom: 26px;
}

.order {
    height: 74px;
    border: 1px solid #333;
    border-radius: 6px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    padding: 6px;
}

.order-img {
    width: 68px;
    height: 60px;
    background: #d9d9d9;
    border-radius: 10px;
    margin-right: 14px;
    overflow: hidden;
}

.order-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.order-info {
    flex: 1;
}

.order-info h3 {
    font-size: 23px;
    margin-bottom: 2px;
}

.order-info p {
    font-size: 14px;
    margin: 0;
}

.view-btn {
    background: #970d03;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 10px 46px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
}

.empty-message {
    color: #900;
    font-size: 16px;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.modal-content {
    position: relative;
    background: white;
    width: 360px;
    padding: 22px;
    border-radius: 12px;
    font-family: 'Courier New', monospace;
}

.close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    cursor: pointer;
    font-weight: bold;
}

.receipt-item {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
    font-size: 14px;
}

.title {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 10px;
}

.item {
    display: flex;
    justify-content: space-between;
    margin: 7px 0;
    font-size: 14px;
}

.total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-top: 12px;
}
</style>
</head>

<body>

<nav>
    <h1>IskoHub</h1>

    <div class="nav-links">
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <a href="orders.php"><i class="fa-solid fa-box"></i> Order History</a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>

<div class="history-container">
    <h2>Order History</h2>

    <?php if (empty($orders)): ?>
        <p class="empty-message">You have no orders yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order">
                <div class="order-img">
                    <?php if (!empty($order['sample_image'])): ?>
                        <img src="<?= htmlspecialchars($order['sample_image']) ?>" alt="Order image">
                    <?php endif; ?>
                </div>
                <div class="order-info">
                    <h3><?= htmlspecialchars($order['sample_name'] ?: 'Order #' . $order['id']) ?></h3>
                    <p>Order #<?= htmlspecialchars($order['id']) ?> • <?= htmlspecialchars($order['created_at']) ?></p>
                    <p>Total: ₱<?= number_format($order['total'], 2) ?> • <?= (int)$order['item_count'] ?> item(s)</p>
                </div>
                <button class="view-btn" onclick="openReceipt(<?= (int)$order['id'] ?>)">View Receipt</button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal" id="receiptModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">✕</span>
        <div id="receiptContent">Loading...</div>
    </div>
</div>

<script>
function openReceipt(orderId) {
    fetch('receipt_modal.php?id=' + encodeURIComponent(orderId))
        .then(response => response.text())
        .then(html => {
            document.getElementById('receiptContent').innerHTML = html;
            document.getElementById('receiptModal').style.display = 'flex';
        })
        .catch(() => {
            document.getElementById('receiptContent').innerText = 'Unable to load receipt.';
            document.getElementById('receiptModal').style.display = 'flex';
        });
}

function closeModal() {
    document.getElementById('receiptModal').style.display = 'none';
}
</script>

</body>
</html>