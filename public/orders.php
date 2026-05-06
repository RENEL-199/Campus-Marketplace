<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$stmt = $pdo->prepare("
    SELECT * FROM orders
    WHERE user_id=?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Orders</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* BASE */
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f7f5;
    margin: 0;
}

/* NAV */
nav {
    background: #3A7D5D;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

nav a {
    color: white;
    margin-left: 20px;
    text-decoration: none;
}

/* CONTAINER */
.container {
    max-width: 800px;
    margin: 40px auto;
}

/* ORDER CARD */
.order {
    background: white;
    padding: 18px;
    margin-bottom: 15px;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-info h3 {
    margin-bottom: 5px;
}

.order a {
    color: #3A7D5D;
    font-weight: bold;
    cursor: pointer;
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}

.modal-content {
    position: relative;
    background: white;
    width: 320px;
    padding: 20px;
    border-radius: 12px;
    font-family: 'Courier New', monospace;
    animation: pop 0.2s ease;
}

@keyframes pop {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* RECEIPT STYLE */
.title {
    text-align: center;
    font-weight: bold;
}

hr {
    border: none;
    border-top: 1px dashed #999;
    margin: 10px 0;
}

.item {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin: 5px 0;
}

.total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-top: 10px;
}

.close {
    float: right;
    cursor: pointer;
}

/* BUTTON */
.view-btn {
    background: none;
    border: none;
    color: #3A7D5D;
    font-weight: bold;
    cursor: pointer;
}

.close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    cursor: pointer;
    font-weight: bold;
}

</style>
</head>

<body>

<nav>
    <h1>Campus Market</h1>
    <div>
  <a href="index.php"><i class="fa-solid fa-house"></i></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
        <a href="orders.php"><i class="fa-solid fa-box"></i></a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i></a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
        
    </div>
</nav>

<div class="container">

<h2>Order History</h2>

<?php foreach ($orders as $o): ?>
<div class="order">

    <div class="order-info">
        <h3>Order #<?= $o['id'] ?></h3>
        <p><?= $o['created_at'] ?></p>
        <p>₱<?= $o['total'] ?></p>
    </div>

    <button class="view-btn" onclick="openReceipt(<?= $o['id'] ?>)">
        View Receipt
    </button>

</div>
<?php endforeach; ?>

</div>

<div class="modal" id="receiptModal">
    <div class="modal-content">

        <span class="close" onclick="closeModal()">✕</span>

        <div id="receiptContent">
            Loading...
        </div>

    </div>
</div>

<script>
function openReceipt(id) {
    document.getElementById("receiptModal").style.display = "flex";

    fetch("receipt_modal.php?id=" + id)
        .then(res => res.text())
        .then(data => {
            document.getElementById("receiptContent").innerHTML = data;
        });
}

function closeModal() {
    document.getElementById("receiptModal").style.display = "none";
}
</script>

</body>
</html>