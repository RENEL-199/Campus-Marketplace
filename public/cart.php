<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

function saveServiceFiles(array $files): array {
    $savedNames = [];

    if (empty($files['name']) || !is_array($files['name'])) {
        return $savedNames;
    }

    $uploadDir = __DIR__ . '/uploads/services/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'ppt', 'pptx'];

    foreach ($files['name'] as $index => $originalName) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $originalName = basename((string)$originalName);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        $safeBase = preg_replace('/[^A-Za-z0-9_\.-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $storedName = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $extension;
        $targetPath = $uploadDir . $storedName;

        if (move_uploaded_file($files['tmp_name'][$index], $targetPath)) {
            $savedNames[] = $originalName;
        }
    }

    return $savedNames;
}

function decodeServiceFiles($value): array {
    if (empty($value)) {
        return [];
    }

    $decoded = json_decode($value, true);

    if (is_array($decoded)) {
        return array_values(array_filter($decoded, fn($name) => trim((string)$name) !== ''));
    }

    return array_values(array_filter(array_map('trim', explode(',', (string)$value))));
}

/* =========================
   ADD TO CART
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {

    $product_id = (int)$_POST['product_id'];
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    $date_from  = $_POST['date_from'] ?? null;
    $date_to    = $_POST['date_to'] ?? null;
    $full_name  = $_POST['full_name'] ?? null;
    $student_no = $_POST['student_no'] ?? null;
    $age        = $_POST['age'] ?? null;
    $gender     = $_POST['gender'] ?? null;

    $print_type = $_POST['print_type'] ?? null;
    $serviceFiles = [];

    $productStmt = $pdo->prepare("SELECT category_id, prod_stock FROM products WHERE prod_id = ? LIMIT 1");
    $productStmt->execute([$product_id]);
    $productInfo = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$productInfo) {
        header("Location: cart.php?cart_error=not_found");
        exit;
    }

    $categoryId = (int)($productInfo['category_id'] ?? 0);
    $isService = $categoryId === 3 || isset($_POST['is_service']);

    if ($isService && isset($_FILES['service_files'])) {
        $serviceFiles = saveServiceFiles($_FILES['service_files']);
        $quantity = max(1, count($serviceFiles));
        $date_from = null;
        $date_to = null;
        $age = null;
        $gender = null;
    }

    $stock = (int)($productInfo['prod_stock'] ?? 0);

    if (!$isService && $stock > 0) {
        $existingQtyStmt = $pdo->prepare("
            SELECT quantity 
            FROM cart_items 
            WHERE user_id = ? AND product_id = ?
        ");
        $existingQtyStmt->execute([$user_id, $product_id]);
        $existingQty = (int)($existingQtyStmt->fetchColumn() ?: 0);

        if (($existingQty + $quantity) > $stock) {
            header("Location: cart.php?cart_error=stock&available=" . $stock);
            exit;
        }
    }

    $check = $pdo->prepare("
        SELECT id, quantity, service_files
        FROM cart_items 
        WHERE user_id = ? AND product_id = ?
    ");
    $check->execute([$user_id, $product_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $oldServiceFiles = decodeServiceFiles($existing['service_files'] ?? null);
        $mergedServiceFiles = $isService ? array_merge($oldServiceFiles, $serviceFiles) : [];
        $newQuantity = $isService ? max(1, count($mergedServiceFiles)) : ((int)$existing['quantity'] + $quantity);

        $update = $pdo->prepare("
            UPDATE cart_items 
            SET 
                quantity = ?,
                date_from = ?,
                date_to = ?,
                full_name = COALESCE(?, full_name),
                student_no = COALESCE(?, student_no),
                age = ?,
                gender = ?,
                print_type = COALESCE(?, print_type),
                service_files = ?
            WHERE user_id = ? AND product_id = ?
        ");

        $update->execute([
            $newQuantity,
            $isService ? null : $date_from,
            $isService ? null : $date_to,
            $full_name,
            $student_no,
            $isService ? null : $age,
            $isService ? null : $gender,
            $print_type,
            $isService ? json_encode($mergedServiceFiles) : null,
            $user_id,
            $product_id
        ]);

    } else {
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
                gender,
                print_type,
                service_files
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->execute([
            $user_id,
            $product_id,
            $quantity,
            $isService ? null : $date_from,
            $isService ? null : $date_to,
            $full_name,
            $student_no,
            $isService ? null : $age,
            $isService ? null : $gender,
            $print_type,
            $isService ? json_encode($serviceFiles) : null
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
   GET CART ITEMS
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
        c.print_type,
        c.service_files,
        p.prod_name,
        p.prod_price,
        p.prod_image,
        p.prod_rate_type,
        p.category_id
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

    return $price * $quantity;
}

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
    
        .service-file-list {
            margin: 6px 0 0 18px;
            padding: 0;
            font-size: 12px;
            line-height: 1.5;
        }

        .service-file-list li {
            word-break: break-word;
        }

        .service-detail-box {
            background: #f3f7f5;
            border-radius: 0 24px 24px 24px;
            padding: 15px 18px;
            box-shadow: 0 3px 4px rgba(0, 0, 0, 0.25);
            margin-bottom: 14px;
        }

        .service-detail-box p {
            font-size: 13px;
            margin: 8px 0;
        }

        .service-detail-box ul {
            margin: 8px 0 0 18px;
            padding: 0;
            font-size: 13px;
            line-height: 1.5;
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

        <?php if (isset($_GET['cart_error'])): ?>
            <?php
                $cartMessage = 'Unable to update cart.';

                if ($_GET['cart_error'] === 'stock') {
                    $available = (int)($_GET['available'] ?? 0);
                    $cartMessage = 'Quantity cannot exceed available stock' . ($available > 0 ? ' (' . $available . ' available).' : '.');
                } elseif ($_GET['cart_error'] === 'not_found') {
                    $cartMessage = 'Product not found.';
                }
            ?>
            <script>alert(<?= json_encode($cartMessage) ?>);</script>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>

        <?php foreach ($items as $item): ?>

            <?php
                $price = (float)$item['prod_price'];
                $qty = (int)$item['quantity'];
                $categoryId = (int)($item['category_id'] ?? 0);
                $isService = $categoryId === 3;
                $serviceFiles = decodeServiceFiles($item['service_files'] ?? null);
                if ($isService && count($serviceFiles) > 0) {
                    $qty = count($serviceFiles);
                }

                $rateType = $isService ? 'Per File' : trim($item['prod_rate_type'] ?? 'Per Piece');
                $subtotal = calculateCartSubtotal($price, $qty, $isService ? null : $rateType, $item['date_from'], $item['date_to']);
                $total += $subtotal;
                $duration = getRentalDurationDays($item['date_from'], $item['date_to']);
                $durationLabel = '';

                if (!$isService && strtolower($rateType) === 'per day') {
                    $durationLabel = $duration . ' day' . ($duration > 1 ? 's' : '');
                }
            ?>

            <div 
                class="cart-row"
                onclick="selectCartItem(event, this)"
                data-category-id="<?= $categoryId ?>"
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
                data-print-type="<?= htmlspecialchars($item['print_type'] ?? '', ENT_QUOTES) ?>"
                data-service-files="<?= htmlspecialchars(json_encode($serviceFiles), ENT_QUOTES) ?>"
            >

                <div class="cart-item">

                    <img src="<?= htmlspecialchars($item['prod_image']) ?>">

                    <div class="cart-info">
                        <h3><?= htmlspecialchars($item['prod_name']) ?></h3>

                        <?php if ($isService): ?>
                           
                           
                            <?php if (!empty($serviceFiles)): ?>
                                <ul class="service-file-list">
                                   
                                </ul>
                            <?php endif; ?>
                        <?php else: ?>
                            
                            <p>Quantity: <?= $qty ?></p>

                            <?php if (!empty($durationLabel)): ?>
                                <p>Duration: <?= htmlspecialchars($durationLabel) ?></p>
                            <?php endif; ?>
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

            <button class="checkout-btn" type="button" onclick="window.location.href='checkout.php'">
                Checkout
            </button>
        </div>
    </div>

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

            <div class="qty-control" id="quantitySection">
                Quantity:
                <span class="qty-num" id="detailQty">0</span>
            </div>

            <div class="service-detail-box" id="serviceSection" style="display:none;">
                <h3>Service Information</h3>
                <p>Print Type: <span id="detailPrintType"></span></p>
                <p>Files selected: <span id="detailFileCount"></span></p>
                <ul id="detailServiceFiles"></ul>

                <h3 style="margin-top:14px;">Customer Information</h3>
                <p>Full Name: <span id="serviceFullName"></span></p>
                <p>Student No.: <span id="serviceStudentNo"></span></p>
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
    if (event.target.closest('.remove-btn')) {
        return;
    }

    const serviceFiles = JSON.parse(row.dataset.serviceFiles || '[]');

    const details = {
        categoryId: parseInt(row.dataset.categoryId || '0', 10),
        name: row.dataset.name || '',
        price: row.dataset.price || '0.00',
        qty: row.dataset.qty || '0',
        image: row.dataset.image || '',
        dateFrom: row.dataset.dateFrom || '',
        dateTo: row.dataset.dateTo || '',
        fullName: row.dataset.fullName || '',
        studentNo: row.dataset.studentNo || '',
        age: row.dataset.age || '',
        gender: row.dataset.gender || '',
        printType: row.dataset.printType || '',
        serviceFiles: serviceFiles
    };

    const isService = details.categoryId === 3;

    const placeholder = document.getElementById('detailPlaceholder');
    const content = document.getElementById('detailContent');
    const dateRow = document.getElementById('detailDateRow');
    const borrowerSection = document.getElementById('borrowerSection');
    const serviceSection = document.getElementById('serviceSection');
    const quantitySection = document.getElementById('quantitySection');
    const serviceFilesList = document.getElementById('detailServiceFiles');

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

    serviceFilesList.innerHTML = '';

    if (isService) {
        document.getElementById('detailPrintType').innerText = details.printType || 'Not provided';
        document.getElementById('detailFileCount').innerText = details.serviceFiles.length || details.qty;
        document.getElementById('serviceFullName').innerText = details.fullName || 'Not provided';
        document.getElementById('serviceStudentNo').innerText = details.studentNo || 'Not provided';

        if (details.serviceFiles.length > 0) {
            details.serviceFiles.forEach(function(fileName) {
                const li = document.createElement('li');
                li.textContent = fileName;
                serviceFilesList.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.textContent = 'No file names saved';
            serviceFilesList.appendChild(li);
        }

        serviceSection.style.display = 'block';
        dateRow.style.display = 'none';
        borrowerSection.style.display = 'none';
        quantitySection.style.display = 'flex';
    } else {
        const hasRentalInfo = Boolean(details.dateFrom || details.dateTo || details.fullName || details.studentNo || details.age || details.gender);

        serviceSection.style.display = 'none';
        dateRow.style.display = hasRentalInfo ? 'flex' : 'none';
        borrowerSection.style.display = hasRentalInfo ? 'block' : 'none';
        quantitySection.style.display = 'flex';
    }

    placeholder.style.display = 'none';
    content.style.display = 'block';

    document.querySelectorAll('.cart-row').forEach(item => {
        item.classList.toggle('active', item === row);
    });
}
</script>

</body>
</html>
