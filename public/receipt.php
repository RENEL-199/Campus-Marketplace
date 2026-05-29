<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$order_id = $_GET['id'] ?? null;

/* ORDER INFO */
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found";
    exit;
}

/* ITEMS */
$stmt = $pdo->prepare("
    SELECT oi.*, p.prod_name
    FROM order_items oi
    JOIN products p ON p.prod_id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Receipt Modal</title>

<style>

/* BACKDROP */
.modal {
    display: flex;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

/* RECEIPT CARD */
.receipt {
    width: 320px;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    font-family: 'Courier New', monospace;
    position: relative;
    animation: pop 0.2s ease-in-out;
}

/* ANIMATION */
@keyframes pop {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* HEADER */
.title {
    text-align: center;
    font-weight: bold;
    font-size: 18px;
}

.subtitle {
    text-align: center;
    font-size: 12px;
    color: #555;
}

/* LINE */
hr {
    border: none;
    border-top: 1px dashed #999;
    margin: 10px 0;
}

/* ITEMS */
.item {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin: 5px 0;
}

/* TOTAL */
.total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-top: 10px;
}

/* CLOSE BUTTON */
.close {
    position: absolute;
    top: 8px;
    right: 10px;
    cursor: pointer;
    font-size: 18px;
}

/* BUTTON */
.btn {
    display: block;
    margin-top: 15px;
    padding: 10px;
    background: #3A7D5D;
    color: white;
    text-align: center;
    border-radius: 8px;
    text-decoration: none;
}

</style>
</head>

<body>

<!-- MODAL -->
<div class="modal" id="receiptModal">

    <div class="receipt">

        <div class="close" onclick="closeModal()">✕</div>

        <div class="title">CAMPUS MARKET</div>
        <div class="subtitle">Official Receipt</div>

        <hr>

        <div class="subtitle">
            Order #<?= $order_id ?><br>
            <?= $order['created_at'] ?>
        </div>

        <hr>

        <!-- ITEMS -->
        <?php foreach ($items as $item): ?>
            <div class="item">
                <span><?= htmlspecialchars($item['prod_name']) ?> x<?= (int)$item['quantity'] ?></span>
                <span>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
            </div>
        <?php endforeach; ?>

        <hr>

        <!-- TOTAL -->
        <div class="total">
            <span>TOTAL</span>
            <span>₱<?= number_format($order['total'], 2) ?></span>
        </div>

        <a class="btn" href="orders.php">View Orders</a>

    </div>

</div>

<script>
function closeModal() {
    document.getElementById("receiptModal").style.display = "none";
    window.location.href = "orders.php";
}
</script>

</body>
</html>