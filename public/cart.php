<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;


/* =========================
   ADD TO CART (PRODUCT + RENTAL)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {

    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)($_POST['quantity'] ?? 1);

    // rental fields (optional)
    $date_from  = $_POST['date_from'] ?? null;
    $date_to    = $_POST['date_to'] ?? null;
    $full_name  = $_POST['full_name'] ?? null;
    $student_no = $_POST['student_no'] ?? null;
    $age        = $_POST['age'] ?? null;
    $gender     = $_POST['gender'] ?? null;

    // check existing cart item
    $check = $pdo->prepare("
        SELECT id 
        FROM cart_items 
        WHERE user_id = ? AND product_id = ?
    ");
    $check->execute([$user_id, $product_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {

        // UPDATE (FIXED: now includes rental fields too)
        $update = $pdo->prepare("
            UPDATE cart_items 
            SET 
                quantity = quantity + ?,
                date_from = COALESCE(?, date_from),
                date_to = COALESCE(?, date_to),
                full_name = COALESCE(?, full_name),
                student_no = COALESCE(?, student_no),
                age = COALESCE(?, age),
                gender = COALESCE(?, gender)
            WHERE user_id = ? AND product_id = ?
        ");

        $update->execute([
            $quantity,
            $date_from,
            $date_to,
            $full_name,
            $student_no,
            $age,
            $gender,
            $user_id,
            $product_id
        ]);

    } else {

        // INSERT NEW ITEM
        $insert = $pdo->prepare("
            INSERT INTO cart_items 
            (
                user_id,
                product_id,
                quantity,
                date_from,
                date_to,
                full_name,
                student_no,
                age,
                gender
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->execute([
            $user_id,
            $product_id,
            $quantity,
            $date_from,
            $date_to,
            $full_name,
            $student_no,
            $age,
            $gender
        ]);
    }

    header("Location: cart.php");
    exit;
}


/* =========================
   REMOVE ITEM
========================= */
if (isset($_GET['remove'])) {

    $product_id = (int)$_GET['remove'];

    $stmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->execute([$user_id, $product_id]);

    header("Location: cart.php");
    exit;
}


/* =========================
   GET CART ITEMS (FIXED)
========================= */
$stmt = $pdo->prepare("
    SELECT 
        c.product_id,
        c.quantity,
        c.date_from,
        c.date_to,
        c.full_name,
        c.student_no,
        c.age,
        c.gender,
        p.prod_name,
        p.prod_price,
        p.prod_image,
        p.prod_rate_type
    FROM cart_items c
    JOIN products p ON p.prod_id = c.product_id
    WHERE c.user_id = ?
");

$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getRentalDurationDays(?string $from, ?string $to): int {
    if (empty($from) || empty($to)) {
        return 1;
    }

    $start = strtotime($from);
    $end = strtotime($to);

    if ($start === false || $end === false || $end < $start) {
        return 1;
    }

    $days = (int)floor(($end - $start) / 86400) + 1;
    return max(1, $days);
}

function calculateCartSubtotal(float $price, int $quantity, ?string $rateType, ?string $from, ?string $to): float {
    $duration = getRentalDurationDays($from, $to);
    $rate = strtolower(trim($rateType ?? ''));

    if ($rate === 'per day') {
        return $price * $quantity * $duration;
    }

    if ($rate === 'per hour') {
        return $price * $quantity * max(1, $duration * 24);
    }

    return $price * $quantity;
}


/* =========================
   TOTAL
========================= */
$total = 0;

?>

<!DOCTYPE html>
<html>
<head>
<title>Cart</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
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
                   font-family: Arial, sans-serif;
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

        .page {
            display: flex;
            gap: 34px;
            max-width: 1090px;
            margin: 40px auto;
        }

        .cart-container {
            width: 635px;
            min-height: 550px;
            background: white;
            border-radius: 22px;
            padding: 18px 32px 30px;
            box-shadow: 0 3px 4px rgba(0, 0, 0, 0.25);
        }

        .cart-container h2 {
            font-size: 32px;
            margin: 0 0 18px;
        }

        .cart-row {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            cursor: pointer;
        }

        .cart-check {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            accent-color: #810C01;
            cursor: pointer;
        }

        .cart-row.active .cart-item {
            border-color: #810C01;
            background: #fff5f2;
        }

        .cart-item {
            flex: 1;
            height: 72px;
            border: 1px solid #333;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding: 6px;
            background: white;
        }

        .detail-placeholder {
            min-height: 240px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #444;
            text-align: center;
            padding: 32px 18px;
        }

        .detail-content {
            display: none;
        }

        .cart-item img {
            width: 62px;
            height: 56px;
            object-fit: cover;
            border-radius: 10px;
            background: #d9d9d9;
        }

        .cart-info {
            flex: 1;
            margin-left: 14px;
        }

        .cart-info h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .cart-info p {
            margin: 1px 0;
            font-size: 13px;
        }

        .remove-btn {
            background: #810C01;
            color: white;
            padding: 10px 27px;
            border-radius: 14px;
            text-decoration: none;
            font-size: 16px;
            text-transform: uppercase;
        }

        .cart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 45px;
        }

        .total {
            font-size: 22px;
            font-weight: bold;
        }

        .checkout-btn {
            background: #810C01;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 22px;
            font-size: 16px;
            cursor: pointer;
            text-transform: uppercase;
        }

        .right-panel {
            width: 395px;
            min-height: 550px;
            background: white;
            border-radius: 22px;
            padding: 16px 44px;
            box-shadow: 0 3px 4px rgba(0, 0, 0, 0.25);
        }

        .preview-img {
            height: 138px;
            border-radius: 28px;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .preview-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .details {
            background: #f3f7f5;
            border-radius: 0 24px 24px 24px;
            padding: 15px 18px;
            box-shadow: 0 3px 4px rgba(0, 0, 0, 0.25);
            margin-bottom: 14px;
        }

        .details h3 {
            margin: 0 0 12px;
        }

        .details p {
            font-size: 13px;
            margin: 8px 0;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0;
            font-size: 13px;
        }

        .qty-control button {
            border: none;
            background: transparent;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
        }

        .qty-num {
            border: 1px solid #333;
            padding: 1px 7px;
            border-radius: 4px;
        }

        .date-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .date-row input[type="date"] {
            width: 120px;
            height: 30px;
            border: 1px solid #999;
            border-radius: 7px;
            padding: 0 8px;
            font-size: 12px;
        }

        .borrower h3 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        .borrower input {
            width: 100%;
            height: 34px;
            border: 1px solid #999;
            border-radius: 7px;
            margin-bottom: 8px;
            padding: 0 12px;
        }

        .two-input {
            display: flex;
            gap: 16px;
        }

        .cart-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .cart-modal-box {
            width: 410px;
            background: white;
            border-radius: 24px;
            padding: 22px 32px;
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

        .modal-img {
            height: 145px;
            border-radius: 26px;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 14px;
        }

        .modal-details {
            background: #f3f7f5;
            border-radius: 0 24px 24px 24px;
            padding: 16px 20px;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
            margin-bottom: 14px;
        }

        .modal-details h3 {
            margin: 0 0 12px;
        }

        .modal-details p {
            margin: 8px 0;
            font-size: 14px;
        }

        .modal-qty {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .modal-qty button {
            border: none;
            background: transparent;
            font-weight: bold;
            font-size: 18px;
        }

        .modal-qty span {
            border: 1px solid #333;
            border-radius: 4px;
            padding: 1px 8px;
        }

        .modal-date {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .modal-date input {
            width: 82px;
            height: 24px;
            border: 1px solid #999;
            border-radius: 7px;
        }

        .modal-borrower h3 {
            margin-bottom: 12px;
        }

        .modal-borrower input {
            width: 100%;
            height: 34px;
            border: 1px solid #999;
            border-radius: 8px;
            margin-bottom: 9px;
            padding: 0 12px;
        }

        .modal-borrower div {
            display: flex;
            gap: 14px;
        }

        .modal-add-btn {
            width: 100%;
            margin-top: 10px;
            background: #810C01;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-size: 15px;
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

<div class="page">

    <div class="cart-container">
        <h2>Cart</h2>

        <?php if (isset($_GET['select_error'])): ?>
            <script>alert('Please check at least one item before checkout.');</script>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>

        <?php if (!empty($items)): ?>
        <form method="POST" action="checkout.php" id="checkoutSelectionForm">
        <?php endif; ?>

        <?php foreach ($items as $item): ?>

            <?php
                $price = (float)$item['prod_price'];
                $qty = (int)$item['quantity'];
                $rateType = trim($item['prod_rate_type'] ?? 'Per Piece');
                $subtotal = calculateCartSubtotal($price, $qty, $rateType, $item['date_from'], $item['date_to']);
                $total += $subtotal;
                $duration = getRentalDurationDays($item['date_from'], $item['date_to']);
                $durationLabel = '';
                if (strtolower($rateType) === 'per day') {
                    $durationLabel = $duration . ' day' . ($duration > 1 ? 's' : '');
                } elseif (strtolower($rateType) === 'per hour') {
                    $durationLabel = ($duration * 24) . ' hour' . ($duration * 24 > 1 ? 's' : '');
                }
            ?>

            <div 
                class="cart-row"
                onclick="selectCartItem(event, this)"
                data-name="<?= htmlspecialchars($item['prod_name'], ENT_QUOTES) ?>"
                data-price="<?= $price ?>"
                data-qty="<?= $qty ?>"
                data-image="<?= htmlspecialchars($item['prod_image'], ENT_QUOTES) ?>"
                data-date-from="<?= htmlspecialchars($item['date_from'] ?? '', ENT_QUOTES) ?>"
                data-date-to="<?= htmlspecialchars($item['date_to'] ?? '', ENT_QUOTES) ?>"
                data-full-name="<?= htmlspecialchars($item['full_name'] ?? '', ENT_QUOTES) ?>"
                data-student-no="<?= htmlspecialchars($item['student_no'] ?? '', ENT_QUOTES) ?>"
                data-age="<?= htmlspecialchars($item['age'] ?? '', ENT_QUOTES) ?>"
                data-gender="<?= htmlspecialchars($item['gender'] ?? '', ENT_QUOTES) ?>"
            >

                <input 
                    type="checkbox" 
                    class="cart-check" 
                    name="selected_items[]" 
                    value="<?= (int)$item['product_id'] ?>"
                    onclick="event.stopPropagation();"
                >

                <div class="cart-item">

                    <img src="<?= htmlspecialchars($item['prod_image']) ?>">

                    <div class="cart-info">
                        <h3><?= htmlspecialchars($item['prod_name']) ?></h3>
                     
                      
                        <?php if (!empty($durationLabel)): ?>
                            <p>Duration: <?= htmlspecialchars($durationLabel) ?></p>
                        <?php endif; ?>
                        <p><strong>Subtotal: ₱<?= number_format($subtotal, 2) ?></strong></p>
                    </div>

                    <a class="remove-btn"
                       href="cart.php?remove=<?= $item['product_id'] ?>">
                       Remove
                    </a>

                </div>
            </div>

        <?php endforeach; ?>

        <div class="cart-footer">
            <div class="total">
                Total: ₱<?= number_format($total, 2) ?>
            </div>

            <button class="checkout-btn" type="submit">
                Checkout
            </button>
        </div>

        <?php if (!empty($items)): ?>
        </form>
        <?php endif; ?>
    </div>

    <!-- RIGHT PANEL -->
    <?php if (!empty($items)): ?>

    <div class="right-panel">

        <div class="detail-placeholder" id="detailPlaceholder">
            <h3>Select a cart item to view details</h3>
            <p>Click any item on the left to preview it here.</p>
        </div>

        <div class="detail-content" id="detailContent" style="display:none;">
            <div class="preview-img">
                <img id="detailImage" src="" alt="Selected item">
            </div>

            <div class="details">
                <h3>Details</h3>
                <p>Item Name: <span id="detailName"></span></p>
                <p>Price: ₱<span id="detailPrice"></span></p>
            </div>

            <div class="qty-control">
                Quantity:
                <button type="button" onclick="minusQty()">−</button>
                <span class="qty-num" id="detailQty">0</span>
                <button type="button" onclick="plusQty()">+</button>
            </div>

            <div class="date-row" id="detailDateRow" style="display:none;">
                From:
                <input type="date" id="fromDate" readonly>
                To:
                <input type="date" id="toDate" readonly>
            </div>

            <div class="borrower" id="borrowerSection" style="display:none;">
                <h3>Borrower Information</h3>

                <input type="text" id="borrowerName" placeholder="Full Name" readonly>
                <input type="text" id="studentNo" placeholder="Student No." readonly>

                <div class="two-input">
                    <input type="text" id="age" placeholder="Age" readonly>
                    <input type="text" id="gender" placeholder="Gender" readonly>
                </div>
            </div>
        </div>

    </div>

    <?php endif; ?>

</div>

<script>
function selectCartItem(event, row) {
    if (event.target.closest('.remove-btn') || event.target.closest('.cart-check')) {
        return;
    }

    const details = {
        name: row.dataset.name || '',
        price: row.dataset.price || '0.00',
        qty: row.dataset.qty || '0',
        image: row.dataset.image || '',
        dateFrom: row.dataset.dateFrom || '',
        dateTo: row.dataset.dateTo || '',
        fullName: row.dataset.fullName || '',
        studentNo: row.dataset.studentNo || '',
        age: row.dataset.age || '',
        gender: row.dataset.gender || ''
    };

    const placeholder = document.getElementById('detailPlaceholder');
    const content = document.getElementById('detailContent');
    const dateRow = document.getElementById('detailDateRow');
    const borrowerSection = document.getElementById('borrowerSection');

    document.getElementById('detailName').innerText = details.name;
    document.getElementById('detailPrice').innerText = parseFloat(details.price).toFixed(2);
    document.getElementById('detailQty').innerText = details.qty;
    document.getElementById('detailImage').src = details.image;
    document.getElementById('fromDate').value = details.dateFrom;
    document.getElementById('toDate').value = details.dateTo;
    document.getElementById('borrowerName').value = details.fullName;
    document.getElementById('studentNo').value = details.studentNo;
    document.getElementById('age').value = details.age;
    document.getElementById('gender').value = details.gender;

    const hasRentalInfo = Boolean(details.dateFrom || details.dateTo || details.fullName || details.studentNo || details.age || details.gender);

    dateRow.style.display = hasRentalInfo ? 'flex' : 'none';
    borrowerSection.style.display = hasRentalInfo ? 'block' : 'none';

    placeholder.style.display = 'none';
    content.style.display = 'block';

    document.querySelectorAll('.cart-row').forEach(item => {
        item.classList.toggle('active', item === row);
    });
}

function plusQty() {
    const qtyBox = document.getElementById('detailQty');
    qtyBox.innerText = parseInt(qtyBox.innerText || 0, 10) + 1;
}

function minusQty() {
    const qtyBox = document.getElementById('detailQty');
    const qty = parseInt(qtyBox.innerText || 0, 10);
    if (qty > 1) {
        qtyBox.innerText = qty - 1;
    }
}


</script>

</body>
</html>