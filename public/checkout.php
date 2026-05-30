<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/CartRepository.php';

require_login();

$db = new Database();
$pdo = $db->pdo;
$user_id = current_user_id();
$cartRepo = new CartRepository($pdo);

$selectedItems = $_POST['selected_items'] ?? $_SESSION['checkout_selected_items'] ?? [];
$selectedItems = array_values(array_unique(array_filter(array_map('intval', (array)$selectedItems), fn($id) => $id > 0)));

if (empty($selectedItems)) {
    header('Location: cart.php?select_error=1');
    exit;
}

$_SESSION['checkout_selected_items'] = $selectedItems;
$items = $cartRepo->getSelectedItems($user_id, $selectedItems);

if (empty($items)) {
    unset($_SESSION['checkout_selected_items']);
    header('Location: cart.php?select_error=1');
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += CartRepository::subtotal($item);
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial;
            background: #eef1ef;
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

        /* MAIN CONTAINER */

        .checkout-container {
            width: 1060px;
            min-height: 560px;
            background: white;
            margin: 38px auto;
            border-radius: 24px;
            padding: 44px 62px 28px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* TOP CONTENT */

        .checkout-content {
            display: flex;
            gap: 38px;
        }

        /* LEFT SIDE */

        .items-list {
            width: 470px;
        }

        .checkout-item {
            background: #f1f4f2;
            height: 96px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            padding: 16px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .img-box {
            width: 68px;
            height: 60px;
            background: #d9d9d9;
            border-radius: 12px;
        }

        .item-info {
            margin-left: 22px;
        }

        .item-info h3 {
            margin: 0;
            font-size: 23px;
            font-weight: 500;
        }

        .item-info p {
            margin: 0;
            font-size: 14px;
        }

        /* RIGHT SIDE */

        .info-box {
            width: 410px;
            height: 345px;
            background: #f1f4f2;
            border-radius: 0 24px 24px 24px;
            padding: 18px 22px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .info-box h2 {
            margin: 0 0 14px;
            font-size: 22px;
            font-weight: 500;
        }

        /* INPUTS */

        .info-box input {
            width: 258px;
            height: 33px;
            border: 1px solid #999;
            border-radius: 8px;
            margin-bottom: 11px;
            padding: 0 10px;
            font-size: 14px;
        }

        /* PAYMENT */

        .payment-title {
            margin-top: 48px;
            font-size: 22px;
        }

        .payment-buttons {
            display: flex;
            gap: 14px;
            margin-top: 10px;
        }

        .payment-buttons button {
            background: white;
            border: 1px solid #b64d42;
            border-radius: 10px;
            padding: 10px 14px;
            color: #777;
            cursor: pointer;
        }

        .payment-buttons button.active {
            background: #b64d42;
            color: white;
            border-color: #990b00;
        }

        .payment-buttons button:hover {
            border-color: #990b00;
        }

        /* BOTTOM */

        .bottom-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 150px;
            margin-top: 78px;
        }

        .total {
            font-size: 25px;
            font-weight: bold;
        }

        .place-btn {
            background: #990b00;
            color: white;
            border: none;
            border-radius: 14px;
            padding: 12px 28px;
            font-size: 17px;
            cursor: pointer;
        }

        .receipt-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .receipt-box {
            width: 390px;
            background: white;
            border-radius: 22px;
            padding: 24px 30px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close-modal {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 25px;
            cursor: pointer;
        }

        .receipt-section {
            background: #f1f4f2;
            border-radius: 0 20px 20px 20px;
            padding: 15px;
            margin-top: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .receipt-section p {
            margin: 7px 0;
            font-size: 14px;
        }

        .confirm-btn {
            width: 100%;
            margin-top: 18px;
            background: #810C01;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
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

    <div class="checkout-container">

        <div class="checkout-content">

            <!-- LEFT -->

            <div class="items-list">

                <?php if (empty($items)): ?>
                    <p>Your cart is empty.</p>
                <?php else: ?>

                    <?php foreach ($items as $item): ?>

                        <div class="checkout-item">

                            <div class="img-box">
                                <?php if (!empty($item['prod_image'])): ?>
                                    <img src="<?= htmlspecialchars($item['prod_image']) ?>" alt="<?= htmlspecialchars($item['prod_name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                                <?php endif; ?>
                            </div>

                            <div class="item-info">
                                <h3><?= htmlspecialchars($item['prod_name']) ?></h3>
                                <p>Price: ₱<?= number_format($item['prod_price'], 2) ?> / <?= htmlspecialchars($item['prod_rate_type'] ?: 'Per Piece') ?></p>
                                <p>Quantity: <?= (int)$item['quantity'] ?></p>
                                <?php
                                $rateType = trim($item['prod_rate_type'] ?? 'Per Piece');
                                $duration = CartRepository::rentalDays($item['date_from'] ?? null, $item['date_to'] ?? null);
                                $durationLabel = '';
                                if (strtolower($rateType) === 'per day') {
                                    $durationLabel = $duration . ' day' . ($duration > 1 ? 's' : '');
                                } elseif (strtolower($rateType) === 'per hour') {
                                    $durationLabel = ($duration * 24) . ' hour' . ($duration * 24 > 1 ? 's' : '');
                                }
                                ?>
                                <?php if (!empty($durationLabel)): ?>
                                    <p>Duration: <?= htmlspecialchars($durationLabel) ?></p>
                                <?php endif; ?>
                                <p><strong>Subtotal: ₱<?= number_format(CartRepository::subtotal($item), 2) ?></strong></p>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <!-- RIGHT -->

            <div class="info-box">

                <h2>Information:</h2>

                <form method="POST" action="place_order.php">

                    <input type="text" name="fullname" id="receiverName" placeholder="Receiving Person" required>

                    <input type="text" name="address" id="receiverAddress" placeholder="Receiving Address" required>

                    <input type="text" name="phone" id="receiverContact" placeholder="Contact Number" required>

                    <div class="payment-title">
                        Payment Method
                    </div>

                    <div class="payment-buttons">
                        <button type="button" class="payment-option active" onclick="selectPayment('Cash on Delivery', this)">
                            Cash on Delivery
                        </button>

                        <button type="button" class="payment-option" onclick="selectPayment('Gcash', this)">
                            Gcash
                        </button>
                    </div>

                    <input type="hidden" name="payment_method" id="paymentMethod" value="Cash on Delivery">
                    <input type="hidden" name="total" value="<?= htmlspecialchars($total, ENT_QUOTES) ?>">
                    <?php foreach ($selectedItems as $selectedId): ?>
                        <input type="hidden" name="selected_items[]" value="<?= (int)$selectedId ?>">
                    <?php endforeach; ?>

                    <div class="bottom-row">

                        <div class="total">
                            Total: ₱<?= number_format($total, 2) ?>
                        </div>

                        <button class="place-btn" type="submit" <?= empty($items) ? 'disabled' : '' ?>>
                            PLACE ORDER
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <?php if (empty($items)): ?>
            <p style="margin-top: 20px; color: #900;">Your cart is empty. Add items to cart before checking out.</p>
        <?php endif; ?>

    </div>

    <script>
        function selectPayment(method, element) {
            document.getElementById('paymentMethod').value = method;
            document.querySelectorAll('.payment-option').forEach(btn => btn.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            }
        }
    </script>

</body>

</html>