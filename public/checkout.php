<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/CartRepository.php';
require_once __DIR__ . '/../app/csrf.php';

require_login();

$db = new Database();
$pdo = $db->pdo;
$user_id = current_user_id();
$cartRepo = new CartRepository($pdo);
$csrf = csrf_token();

$selectedItems = $_POST['selected_items'] ?? $_SESSION['checkout_selected_items'] ?? [];
$selectedItems = array_values(array_unique(array_filter(array_map('intval', (array)$selectedItems), fn($id) => $id > 0)));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: cart.php?cart_error=' . urlencode('Invalid security token.'));
    exit;
}

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

$hasRental = false;
$rentalTermsText = [];
foreach ($items as $item) {
    if (strtolower((string)($item['category_type'] ?? 'product')) === 'rental') {
        $hasRental = true;
        if (!empty($item['rental_terms'])) {
            $rentalTermsText[] = trim((string)$item['rental_terms']);
        }
    }
}
$rentalTermsText = array_values(array_unique(array_filter($rentalTermsText)));

$selectedProductIds = array_values(array_unique(array_map('intval', array_column($items, 'product_id'))));
$sellerGcashNumbers = [];
if (!empty($selectedProductIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedProductIds), '?'));
    $sellerStmt = $pdo->prepare("SELECT DISTINCT p.prod_id, u.contact_number FROM products p JOIN users u ON u.user_id = p.user_id WHERE p.prod_id IN ($placeholders)");
    $sellerStmt->execute($selectedProductIds);
    foreach ($sellerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['contact_number'])) {
            $sellerGcashNumbers[] = trim((string)$row['contact_number']);
        }
    }
}
$sellerGcashNumbers = array_values(array_unique($sellerGcashNumbers));
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
            width: min(1160px, calc(100% - 24px));
            min-height: 560px;
            background: white;
            margin: 24px auto 36px;
            border-radius: 24px;
            padding: 28px 24px 24px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .checkout-error {
            width: min(1160px, calc(100% - 24px));
            margin: 18px auto 0;
            background: #fff4f2;
            border: 1px solid #e7b4aa;
            color: #8a1f12;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 14px;
        }

        /* TOP CONTENT */

        .checkout-content {
            display: flex;
            gap: 28px;
            align-items: flex-start;
            justify-content: space-between;
        }

        /* LEFT SIDE */

        .items-list {
            flex: 1 1 520px;
            min-width: 0;
        }

        .checkout-item {
            background: #f1f4f2;
            min-height: 96px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            padding: 14px;
            margin-bottom: 18px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .img-box {
            width: 68px;
            height: 60px;
            background: #d9d9d9;
            border-radius: 12px;
        }

        .item-info {
            margin-left: 14px;
            flex: 1;
            min-width: 0;
        }

        .item-info h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.2;
        }

        .item-info p {
            margin: 0;
            font-size: 14px;
        }

        /* RIGHT SIDE */

        .info-box {
            flex: 0 1 420px;
            width: 100%;
            max-width: 420px;
            min-height: 345px;
            background: #f1f4f2;
            border-radius: 0 24px 24px 24px;
            padding: 18px 18px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .info-box h2 {
            margin: 0 0 14px;
            font-size: 22px;
            font-weight: 500;
        }

        /* INPUTS */

        .info-box input {
            width: 100%;
            min-height: 38px;
            border: 1px solid #999;
            border-radius: 8px;
            margin-bottom: 10px;
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
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .payment-buttons button {
            flex: 1 1 140px;
            background: white;
            border: 1px solid #b64d42;
            border-radius: 10px;
            padding: 10px 12px;
            color: #777;
            cursor: pointer;
            text-align: center;
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
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .total {
            font-size: 22px;
            font-weight: bold;
        }

        .place-btn {
            background: #990b00;
            color: white;
            border: none;
            border-radius: 14px;
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            white-space: nowrap;
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

        @media (max-width: 1080px) {
            .checkout-content {
                flex-direction: column;
            }

            .info-box {
                max-width: none;
            }
        }

        @media (max-width: 640px) {
            .checkout-container {
                padding: 18px 14px 18px;
                border-radius: 18px;
            }

            .checkout-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .img-box {
                width: 100%;
                height: 110px;
            }

            .item-info {
                margin-left: 0;
                margin-top: 10px;
            }

            .payment-buttons button {
                flex-basis: 100%;
            }

            .bottom-row {
                flex-direction: column;
                align-items: stretch;
            }

            .place-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <?php if (!empty($_GET['error']) || !empty($_GET['cart_error']) || !empty($_GET['select_error'])): ?>
        <div class="checkout-error">
            <?= htmlspecialchars((string)($_GET['error'] ?? $_GET['cart_error'] ?? 'Please review your checkout details and try again.'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <nav>
        <h1>IskoHub</h1>

        <div class="nav-links">
            <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
            <a href="orders.php"><i class="fa-solid fa-box"></i> Order History</a>
            <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>
            <a href="account.php"><i class="fa-solid fa-user"></i></a>
            <a href="logout.php" class="logout-btn">Logout</a>
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

                <form method="POST" action="place_order.php" enctype="multipart/form-data">

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

                    <div id="gcashPaymentSection" style="display:none; margin-top:14px; padding:12px; border:1px solid #d6b1ad; border-radius:12px; background:#fffaf9;">
                        <?php if (!empty($sellerGcashNumbers)): ?>
                            <div style="margin-bottom:10px; font-size:13px; color:#333;">
                                <strong>GCash Number(s)</strong>
                                <ul style="margin:6px 0 0 18px; padding:0;">
                                    <?php foreach ($sellerGcashNumbers as $gcashNo): ?>
                                        <li><?= htmlspecialchars($gcashNo) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <label style="display:block;font-size:13px;color:#333;margin-top:6px;">GCash Payment Proof<input type="file" id="paymentProofInput" name="payment_proof" accept="image/*" style="margin-top:4px;"></label>

                        <?php if ($hasRental): ?>
                            <div style="margin-top:10px;">
                                <strong>Rental Terms &amp; Conditions</strong>
                                <div style="font-size:12px;color:#555;white-space:pre-wrap;margin:6px 0;"><?= htmlspecialchars(implode("\n\n", $rentalTermsText) ?: 'Customer is responsible for any damage, loss, or theft; late return penalties apply; replacement cost for unreturned items; security deposit policy.') ?></div>
                                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#333;margin-top:8px;"><input type="checkbox" name="rental_terms_accepted" value="1"> I accept the rental terms and understand that a 50% down payment is required to reserve the item.</label>
                            </div>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="payment_method" id="paymentMethod" value="Cash on Delivery">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
        const paymentMethodInput = document.getElementById('paymentMethod');
        const gcashPaymentSection = document.getElementById('gcashPaymentSection');
        const paymentProofInput = document.getElementById('paymentProofInput');
        const rentalTermsCheckbox = document.querySelector('input[name="rental_terms_accepted"]');

        function syncGcashUI() {
            const isGcash = (paymentMethodInput.value || '').toLowerCase() === 'gcash';
            if (gcashPaymentSection) {
                gcashPaymentSection.style.display = isGcash ? 'block' : 'none';
            }
            if (paymentProofInput) {
                paymentProofInput.required = isGcash;
            }
            if (rentalTermsCheckbox) {
                rentalTermsCheckbox.required = isGcash && <?= $hasRental ? 'true' : 'false' ?>;
            }
        }

        function selectPayment(method, element) {
            paymentMethodInput.value = method;
            document.querySelectorAll('.payment-option').forEach(btn => btn.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            }
            syncGcashUI();
        }

        window.addEventListener('DOMContentLoaded', syncGcashUI);
    </script>

</body>

</html>