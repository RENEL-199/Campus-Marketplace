<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* add to cart using session */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_id'])) {

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity < 1) {
        $quantity = 1;
    }

    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;
    setcookie("cart_count", count($_SESSION['cart']), time() + 3600, "/");

    header("Location: cart.php");
    exit;
}

/* remove item from session cart */
if (isset($_GET['remove'])) {

    $product_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
    setcookie("cart_count", count($_SESSION['cart']), time() + 3600, "/");

    header("Location: cart.php");
    exit;
}

$items = [];
$cartItems = $_SESSION['cart'] ?? [];

if (!empty($cartItems)) {
    $productIds = array_keys($cartItems);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare("SELECT id, name, price, image, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $quantity = min($cartItems[$product['id']], max(0, $product['stock']));

        if ($quantity > 0) {
            $items[] = [
                'cart_id' => $product['id'],
                'product_id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'stock' => $product['stock'],
                'quantity' => $quantity,
            ];
        }
    }
}

/* TOTAL */
$total = 0;

?>

<!DOCTYPE html>
<html>

<head>
    <title>Cart</title>

    <link rel="stylesheet" href="../assets/index-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .cart-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;

            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 15px;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .cart-info {
            flex: 1;
            margin-left: 15px;
        }

        .price {
            color: #2F684C;
            font-weight: bold;
        }

        .qty {
            font-weight: bold;
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
        }

        .checkout-btn {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background: #3A7D5D;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
        }

        .checkout-btn:hover {
            background: #2F684C;
        }

        .empty {
            text-align: center;
            color: #777;
        }

        .h2 {
            margin-bottom: 15px;
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

    <div class="cart-container">

        <h2>Cart</h2>

        <?php if (empty($items)): ?>

            <p class="empty">Your cart is empty 🛒</p>

        <?php else: ?>

            <?php foreach ($items as $item): ?>

                <?php
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
                ?>

                <div class="cart-item">

                    <img src="<?= $item['image'] ?>">

                    <div class="cart-info">
                        <h3><?= $item['name'] ?></h3>
                        <p class="qty">Qty: <?= $item['quantity'] ?></p>
                        <p class="price">₱<?= $subtotal ?></p>
                    </div>

                    <a class="remove-btn" href="cart.php?remove=<?= $item['cart_id'] ?>">
                        Remove
                    </a>

                </div>

            <?php endforeach; ?>

            <hr>

            <h3>Total: ₱<?= $total ?></h3>

            <!-- checkout -->
            <form method="POST" action="checkout.php">
                <button class="checkout-btn">Place Order</button>
            </form>

        <?php endif; ?>

    </div>

</body>

</html>