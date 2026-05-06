<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$stmt = $pdo->prepare("
    SELECT 
        c.product_id,
        c.quantity,
        p.name,
        p.price,
        p.image,
        p.stock
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
");

$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* redirect if empty */
if (!$items) {
    header("Location: cart.php");
    exit;
}

/* TOTAL */
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Checkout</title>

<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>

body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f7f5;
    margin: 0;
}

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
    position: relative;
    font-size: 0.95rem;
}

nav a::after {
    content: "";
    position: absolute;
    width: 0%;
    height: 2px;
    bottom: -3px;
    left: 0;
    background: #E9C46A;
    transition: 0.3s;
}

nav a:hover::after {
    width: 100%;
}

.checkout-wrapper {
    max-width: 1100px;
    margin: 40px auto;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    padding: 0 15px;
}

.checkout-box {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
}

.section-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: #1f2937;
}

/* INPUTS */
input, textarea {
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    outline: none;
}

input:focus, textarea:focus {
    border-color: #3A7D5D;
    box-shadow: 0 0 0 3px rgba(58,125,93,0.1);
}


.summary-box {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    height: fit-content;
    position: sticky;
    top: 20px;
}


.order-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    font-size: 0.95rem;
}

.order-item span:last-child {
    font-weight: bold;
    color: #3A7D5D;
}

/* TOTAL */
.total-box {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 2px solid #eee;
    font-size: 1.3rem;
    font-weight: bold;
    text-align: right;
}


.confirm-btn {
    width: 100%;
    margin-top: 15px;
    padding: 14px;
    background: linear-gradient(135deg, #3A7D5D, #2F684C);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    cursor: pointer;
    transition: 0.2s;
}

.confirm-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(58,125,93,0.3);
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width: 800px) {
    .checkout-wrapper {
        grid-template-columns: 1fr;
    }
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

<div class="checkout-wrapper">

    <!-- LEFT SIDE -->
    <div class="checkout-box">

        <h2 class="section-title">Checkout Details</h2>

        <form method="POST" action="place_order.php">

            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="phone" placeholder="Phone Number" required>

            <button class="confirm-btn">Confirm Order</button>

    </div>

    <!-- RIGHT SIDE -->
    <div class="summary-box">

        <h3>Order Summary</h3>

        <?php foreach ($items as $item): ?>

            <div class="order-item">
                <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                <span>₱<?= $item['price'] * $item['quantity'] ?></span>
            </div>

            <input type="hidden" name="product_ids[]" value="<?= $item['product_id'] ?>">
            <input type="hidden" name="quantities[]" value="<?= $item['quantity'] ?>">

        <?php endforeach; ?>

        <div class="total-box">
            Total: ₱<?= $total ?>
        </div>

        <input type="hidden" name="total" value="<?= $total ?>">

        </form>

    </div>

</div>

</body>
</html>