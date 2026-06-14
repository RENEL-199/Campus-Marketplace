<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/ProductRepository.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';

require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

// Validate that the logged-in user still exists in the database.
$checkUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
$checkUser->execute([$user_id]);

if (!$checkUser->fetchColumn()) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$repo = new ProductRepository();
$csrf = csrf_token();

ensureColumn($pdo, 'products', 'rental_terms', "rental_terms TEXT DEFAULT NULL");
ensureColumn($pdo, 'products', 'seller_terms_accepted_at', "seller_terms_accepted_at DATETIME DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'payment_status', "payment_status VARCHAR(60) DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'payment_proof_path', "payment_proof_path VARCHAR(255) DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'payment_verified_at', "payment_verified_at DATETIME DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'payment_verified_by', "payment_verified_by INT DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'payment_rejection_reason', "payment_rejection_reason TEXT DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'reservation_status', "reservation_status VARCHAR(60) DEFAULT NULL");
ensureColumn($pdo, 'rental_details', 'rental_terms_accepted', "rental_terms_accepted TINYINT(1) NOT NULL DEFAULT 0");

ensureTable($pdo, "CREATE TABLE IF NOT EXISTS terms_acceptances (id INT AUTO_INCREMENT PRIMARY KEY, acceptance_type ENUM('seller','rental') NOT NULL, subject_id INT NOT NULL, user_id INT DEFAULT NULL, accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, terms_text TEXT DEFAULT NULL, user_agent TEXT DEFAULT NULL, ip_address VARCHAR(100) DEFAULT NULL, UNIQUE KEY uniq_terms_acceptance (acceptance_type, subject_id), INDEX idx_terms_acceptance_user (user_id))");
ensureTable($pdo, "CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, type VARCHAR(60) NOT NULL DEFAULT 'general', title VARCHAR(150) NOT NULL, message TEXT NOT NULL, related_order_item_id INT DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_notifications_user (user_id), INDEX idx_notifications_unread (user_id, is_read))");

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->query("SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "'");
    if ($stmt->fetch()) {
        return;
    }
    $pdo->exec("ALTER TABLE `" . $table . "` ADD COLUMN " . $definition);
}

function ensureTable(PDO $pdo, string $sql): void {
    $pdo->exec($sql);
}

function uploadProductImage(array $file, string $fallback = 'uploads/default.png'): string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return $fallback;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if ((int)($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Image must be 2MB or smaller.');
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        throw new RuntimeException('Only JPG, PNG, GIF, and WEBP images are allowed.');
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }

    return 'uploads/' . $fileName;
}

/* =========================
   DELETE PRODUCT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        echo "<script>alert('Invalid security token.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    if (empty($_POST["selected_product_id"])) {
        echo "<script>alert('Select product first to Delete it.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $delete_id = (int) $_POST["selected_product_id"];

    $repo->delete($delete_id, $user_id);

    header("Location: seller_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && in_array($_POST["action"], ['verify_payment','reject_payment'], true)) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        echo "<script>alert('Invalid security token.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }
    $orderItemId = (int)($_POST['order_item_id'] ?? 0);
    if ($orderItemId <= 0) {
        echo "<script>alert('Missing rental order to verify.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $current = $pdo->prepare("SELECT rd.payment_status, rd.reservation_status FROM rental_details rd JOIN order_items oi ON oi.order_item_id = rd.ref_id WHERE rd.ref_type = 'order' AND rd.ref_id = ? AND oi.seller_id = ? LIMIT 1");
    $current->execute([$orderItemId, $user_id]);
    $currentStatus = $current->fetch(PDO::FETCH_ASSOC);
    if (!$currentStatus) {
        echo "<script>alert('Rental order not found.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    if ($_POST['action'] === 'verify_payment') {
        $pdo->prepare("UPDATE rental_details SET payment_status = 'Reserved', reservation_status = 'Reserved', payment_verified_at = NOW(), payment_verified_by = ?, payment_rejection_reason = NULL WHERE ref_type = 'order' AND ref_id = ?")->execute([$user_id, $orderItemId]);
        $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE order_id = (SELECT order_id FROM order_items WHERE order_item_id = ?)")->execute([$orderItemId]);

        // Fetch buyer, seller, and product details
        $buyerStmt = $pdo->prepare("SELECT o.user_id, o.fullname, o.phone, oi.product_name_snapshot AS prod_name FROM order_items oi JOIN orders o ON o.order_id = oi.order_id WHERE oi.order_item_id = ? LIMIT 1");
        $buyerStmt->execute([$orderItemId]);
        $buyer = $buyerStmt->fetch(PDO::FETCH_ASSOC);
        $buyerId = $buyer['user_id'] ?? null;
        $productName = $buyer['prod_name'] ?? 'Selected rental item';

        $sellerStmt = $pdo->prepare("SELECT user_name, contact_number, course, year_level FROM users WHERE user_id = ? LIMIT 1");
        $sellerStmt->execute([$user_id]);
        $seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);
        $sellerName = $seller['user_name'] ?? 'Seller';
        $sellerContact = $seller['contact_number'] ?? '';
        $sellerProgram = trim((($seller['course'] ?? '') . ' ' . ($seller['year_level'] ?? '')));

        // Notification for buyer (received)
        if ($buyerId) {
            $title = 'Rental approved: ' . $productName;
            $message = 'Your payment for "' . $productName . '" has been approved by ' . $sellerName . '. Contact: ' . ($sellerContact ?: 'Not provided') . '. Program/Year: ' . ($sellerProgram ?: 'Not provided') . '.';
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_item_id) VALUES (?, 'rental', ?, ?, ?)")->execute([$buyerId, $title, $message, $orderItemId]);

            // Notification for seller (sent)
            $titleS = 'Rental approved: ' . $productName;
            $messageS = 'You approved "' . $productName . '" for buyer ' . ($buyer['fullname'] ?? 'Buyer') . ' (phone: ' . ($buyer['phone'] ?? 'Not provided') . ').';
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_item_id) VALUES (?, 'rental_sent', ?, ?, ?)")->execute([$user_id, $titleS, $messageS, $orderItemId]);
        }
    } else {
        $reason = trim($_POST['reason'] ?? '');
        $pdo->prepare("UPDATE rental_details SET payment_status = 'Rejected', payment_rejection_reason = ?, reservation_status = 'Rejected' WHERE ref_type = 'order' AND ref_id = ?")->execute([$reason !== '' ? $reason : 'Payment rejected by seller.', $orderItemId]);

        // Fetch buyer, seller, and product details
        $buyerStmt = $pdo->prepare("SELECT o.user_id, o.fullname, o.phone, oi.product_name_snapshot AS prod_name FROM order_items oi JOIN orders o ON o.order_id = oi.order_id WHERE oi.order_item_id = ? LIMIT 1");
        $buyerStmt->execute([$orderItemId]);
        $buyer = $buyerStmt->fetch(PDO::FETCH_ASSOC);
        $buyerId = $buyer['user_id'] ?? null;
        $productName = $buyer['prod_name'] ?? 'Selected rental item';

        $sellerStmt = $pdo->prepare("SELECT user_name, contact_number, course, year_level FROM users WHERE user_id = ? LIMIT 1");
        $sellerStmt->execute([$user_id]);
        $seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);
        $sellerName = $seller['user_name'] ?? 'Seller';
        $sellerContact = $seller['contact_number'] ?? '';
        $sellerProgram = trim((($seller['course'] ?? '') . ' ' . ($seller['year_level'] ?? '')));

        if ($buyerId) {
            $title = 'Rental rejected: ' . $productName;
            $message = 'Your payment for "' . $productName . '" was rejected by ' . $sellerName . '. Reason: ' . ($reason !== '' ? $reason : 'No reason provided.') . '. Contact: ' . ($sellerContact ?: 'Not provided') . '.';
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_item_id) VALUES (?, 'rental', ?, ?, ?)")->execute([$buyerId, $title, $message, $orderItemId]);

            // Notification for seller (sent)
            $titleS = 'Rental rejected: ' . $productName;
            $messageS = 'You rejected "' . $productName . '" for buyer ' . ($buyer['fullname'] ?? 'Buyer') . '. Reason: ' . ($reason !== '' ? $reason : 'No reason provided') . '.';
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_item_id) VALUES (?, 'rental_sent', ?, ?, ?)")->execute([$user_id, $titleS, $messageS, $orderItemId]);
        }
    }

    header("Location: seller_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "accept_terms") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $pdo->prepare("INSERT INTO terms_acceptances (acceptance_type, subject_id, user_id, accepted_at, user_agent, ip_address) VALUES ('seller', ?, ?, NOW(), ?, ?) ON DUPLICATE KEY UPDATE accepted_at = NOW(), user_agent = VALUES(user_agent), ip_address = VALUES(ip_address)")->execute([
        $user_id,
        $user_id,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    header("Location: seller_dashboard.php");
    exit;
}

/* =========================
   UPDATE PRODUCT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        echo "<script>alert('Invalid security token.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }
    if (empty($_POST['seller_terms_accepted'])) {
        echo "<script>alert('You must accept the platform Terms and Conditions before updating listings.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }
    $pdo->prepare("INSERT INTO terms_acceptances (acceptance_type, subject_id, user_id, accepted_at, user_agent, ip_address) VALUES ('seller', ?, ?, NOW(), ?, ?) ON DUPLICATE KEY UPDATE accepted_at=VALUES(accepted_at), user_agent=VALUES(user_agent), ip_address=VALUES(ip_address)")->execute([$user_id, $user_id, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null]);

    if (empty($_POST["selected_product_id"])) {
        echo "<script>alert('Select product first to update it.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $update_id = (int) $_POST["selected_product_id"];

    $name = trim($_POST["name"]);
    $desc = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $category_id = trim($_POST["category"]);
    $stock = trim($_POST["stock"]);
    $location = trim($_POST["location"] ?? "");
    $rate_type = trim($_POST["rate_type"] ?? "");
    $rental_terms = trim($_POST['rental_terms'] ?? '');
    if (strcasecmp($rate_type, "Per Hour") === 0) {
        $rate_type = "Per Day";
    }

    if ($name === "" || $desc === "" || $price === "" || $category_id === "" || $stock === "") {
        echo "<script>alert('Please fill out Product Name, Description, Price, Quantity, and Category.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $price = (float)$price;
    $stock = (int)$stock;
    $category_id = (int)$category_id;

    if ($price < 0 || $stock < 0 || $category_id <= 0) {
        echo "<script>alert('Please enter valid price, quantity, and category.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT prod_image FROM products WHERE prod_id = ? AND user_id = ?");
    $stmt->execute([$update_id, $user_id]);
    $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    $image = $oldProduct['prod_image'] ?? 'uploads/default.png';
    try {
        if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $image = uploadProductImage($_FILES['image'], $image);
        }
    } catch (RuntimeException $e) {
        echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $repo->update(new Product(
        $update_id,
        $user_id,
        $name,
        $desc,
        $price,
        $image,
        $stock,
        $location,
        null,
        $category_id,
        $rate_type,
        null,
        null,
        null,
        $rental_terms
    ));

    header("Location: seller_dashboard.php");
    exit;
}

/* =========================
   ADD PRODUCT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        echo "<script>alert('Invalid security token.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }
    if (empty($_POST['seller_terms_accepted'])) {
        echo "<script>alert('You must accept the platform Terms and Conditions before creating a listing.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }
    $pdo->prepare("INSERT INTO terms_acceptances (acceptance_type, subject_id, user_id, accepted_at, user_agent, ip_address) VALUES ('seller', ?, ?, NOW(), ?, ?) ON DUPLICATE KEY UPDATE accepted_at=VALUES(accepted_at), user_agent=VALUES(user_agent), ip_address=VALUES(ip_address)")->execute([$user_id, $user_id, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null]);
    $name = trim($_POST["name"]);
    $desc = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $category_id = trim($_POST["category"]);
    $stock = trim($_POST["stock"]);
    $location = trim($_POST["location"] ?? "");
    $rate_type = trim($_POST["rate_type"] ?? "");
    $rental_terms = trim($_POST['rental_terms'] ?? '');
    if (strcasecmp($rate_type, "Per Hour") === 0) {
        $rate_type = "Per Day";
    }

    if ($name === "" || $desc === "" || $price === "" || $category_id === "" || $stock === "") {
        echo "<script>alert('Please fill out Product Name, Description, Price, Quantity, and Category.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $price = (float)$price;
    $stock = (int)$stock;
    $category_id = (int)$category_id;

    if ($price < 0 || $stock < 0 || $category_id <= 0) {
        echo "<script>alert('Please enter valid price, quantity, and category.'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $image = 'uploads/default.png';
    try {
        if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $image = uploadProductImage($_FILES['image'], $image);
        }
    } catch (RuntimeException $e) {
        echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.location.href='seller_dashboard.php';</script>";
        exit;
    }

    $repo->add(new Product(
        0,
        $user_id,
        $name,
        $desc,
        $price,
        $image,
        $stock,
        $location,
        null,
        $category_id,
        $rate_type,
        null,
        null,
        null,
        $rental_terms
    ));

    header("Location: seller_dashboard.php");
    exit;
}

/* =========================
   GET PRODUCTS
========================= */
$stmt = $pdo->prepare("SELECT *, rate_type AS prod_rate_type, location AS prod_location FROM products WHERE user_id = ? AND status <> 'deleted'");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_OBJ);

$sellerAcceptedAt = $pdo->prepare("SELECT accepted_at FROM terms_acceptances WHERE acceptance_type = 'seller' AND subject_id = ? LIMIT 1");
$sellerAcceptedAt->execute([$user_id]);
$sellerAcceptedAt = $sellerAcceptedAt->fetchColumn();

/* =========================
   STATS
========================= */
$total = count($products);
$active = 0;
$out = 0;

foreach ($products as $p) {
    if ($p->prod_stock > 0) $active++;
    else $out++;
}

$pendingRentalPayments = $pdo->prepare(" 
    SELECT oi.order_item_id, oi.order_id, oi.product_name_snapshot AS prod_name, o.fullname, o.phone, rd.payment_proof_path, rd.payment_status, rd.payment_rejection_reason
    FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id
    JOIN rental_details rd ON rd.ref_type = 'order' AND rd.ref_id = oi.order_item_id
    WHERE oi.seller_id = ? AND rd.payment_status IN ('Pending Payment','Payment Proof Submitted','Payment Under Review') AND COALESCE(rd.payment_status, '') <> 'Rejected'
    ORDER BY o.created_at DESC
");
$pendingRentalPayments->execute([$user_id]);
$pendingRentalPayments = $pendingRentalPayments->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seller Dashboard</title>

<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* PAGE */
body {
    margin: 0;
    background: linear-gradient(180deg, #f4f6f8 0%, #eef2ef 100%);
    font-family: Arial, sans-serif;
    color: #1f2937;
}

.dashboard {
    max-width: 1200px;
    margin: 20px auto 36px;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 12px;
    margin-bottom: 18px;
}

.page-title h1 {
    margin: 0;
    font-size: 28px;
    color: #111827;
}

.page-title p {
    margin: 6px 0 0;
    color: #5b6775;
    font-size: 14px;
}

.badge-pill {
    background: #fff7f3;
    color: #991000;
    border: 1px solid #f3d7cf;
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 700;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.stat {
    background: linear-gradient(145deg, #ffffff 0%, #f7f9f7 100%);
    padding: 14px 16px;
    border-radius: 18px;
    box-shadow: 0 10px 28px rgba(17, 24, 39, 0.08);
    border: 1px solid #edf1ed;
}

.stat h2 {
    margin: 0;
    color: #000;
    font-size: 24px;
    font-weight: 800;
}

.stat p {
    display: inline;
}

.stat h2 {
    margin: 0;
    color: #000;
    font-size: 24px;
    font-weight: 800;
    display: inline;
}

/* MAIN LAYOUT */
.grid {
    display: grid;
 grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
    gap: 22px;
    align-items: start;
}

.card {
    background: linear-gradient(145deg, #ffffff 0%, #fcfdfc 100%);
    padding: 18px;
    border-radius: 18px;
    box-shadow: 0 14px 32px rgba(17, 24, 39, 0.08);
    border: 1px solid #edf1ed;
    min-height: 100%;
    width: 100%;
}

.card h2 {
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 800;
    color: #111827;
}

.card-subtle {
    color: #5b6775;
    font-size: 13px;
    margin-bottom: 10px;
}

/* IMAGE UPLOAD */
.image-upload {
    width: 100%;
    height: 185px;
    border: 1px dashed #b56b62;
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    background: #f7f9f7;
    margin-bottom: 14px;
}

.image-upload input {
    display: none;
}

.image-upload img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: none;
}

.image-upload span {
    color: transparent;
}

/* FORM */
input,
textarea,
select {
    width: 100%;
    padding: 7px;
    margin: 0 0 5px;
    border-radius: 4px;
    border: 1px solid #aaa;
    font-size: 15px;
    box-sizing: border-box;
}

label input{
    width: auto;
}

textarea {
    height: 64px;
    resize: none;
}

select {
    height: 34px;
    font-size: 12px;
    padding-right: 25px;
}

button {
    background: #991000;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
}

/* ADD PRODUCT BUTTON */



.small-label {
    display: block;
    font-size: 14px;
    margin: 0 0 2px;
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    align-items: end;
}

.rate-buttons {
    display: flex;
    gap: 5px;
}

.rate-buttons button {
    background: white;
    color: #991000;
    border: 1px solid #aaa;
    border-radius: 4px;
    font-size: 14px;
    padding: 4px;
}

.rate-option.selected-rate {
    background: #991000;
    color: white;
    border-color: #991000;
}



.bottom-row {
    align-items: center;
}

.quantity-box {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 15px;
}


.quantity-box input {
    width: 38px;
    height: 22px;
    margin: 0;
    padding: 2px;
    text-align: center;
    border: 1px solid #aaa;
    border-radius: 4px;
    background: white;
    font-size: 14px;
}

.quantity-box button {
    display: flex;
    align-items: center;
    width: 18px;
    height: 18px;
    background: white;
    color: black;
    padding: 0;
    font-weight: bold;
    justify-content: center;
    line-height: 1;

    position: relative;
    top: -4px;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-top: 8px;
}

.action-buttons button {
    padding: 9px;
    background: #991000;
    color: white;
    border-radius: 8px;
    font-size: 18px;
}



/* PRODUCT LIST */
.product {
    display: grid;
    grid-template-columns: 38px 1fr 32px;
    gap: 8px;
    align-items: center;
    padding: 4px;
    margin-bottom: 10px;
    border: 1px solid #777;
    border-radius: 4px;
    background: white;
}

.product.sold-out {
    opacity: 0.7;
    border-color: #c33;
    background: #ffebeb;
}

.product img {
    width: 34px;
    height: 34px;
    object-fit: cover;
    border-radius: 6px;
    background: #d9d9d9;
}

.product strong {
    font-size: 18px;
}

.product div {
    font-size: 16px;
    line-height: 1.5;
}


/* MODAL */
.terms-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.62);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 18px;
}

.terms-modal {
    width: min(900px, 100%);
    max-height: 92vh;
    overflow: auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 22px 48px rgba(17, 24, 39, 0.22);
    border: 1px solid #edf1ed;
}

.terms-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 18px 18px 10px;
    border-bottom: 1px solid #edf1ed;
}

.terms-header h3 { margin: 0; font-size: 20px; }
.terms-body { padding: 16px 18px 8px; color: #374151; font-size: 14px; line-height: 1.5; }
.terms-body ul { margin: 8px 0 8px 18px; }
.terms-footer { padding: 12px 18px 18px; border-top: 1px solid #edf1ed; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.terms-footer label { display:flex; align-items:center; gap:8px; font-size:13px; color:#374151; }

        .receipt-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.68);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 18px;
        }

        .receipt-modal-box {
            width: min(980px, 100%);
            max-height: 92vh;
            overflow: auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 22px 48px rgba(17, 24, 39, 0.22);
            border: 1px solid #edf1ed;
            padding: 16px;
        }

        .receipt-modal-box img {
            width: 100%;
            max-height: 78vh;
            object-fit: contain;
            border-radius: 12px;
            background: #f3f4f6;
        }

        .receipt-modal-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
@media (max-width: 980px) { .grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
    .stats { grid-template-columns: 1fr; }
    .dashboard { padding: 0 12px; }
    .page-header { align-items: start; flex-direction: column; }
    .action-buttons { grid-template-columns: 1fr 1fr; }
    .top-form-row { grid-template-columns: 1fr; }
}
@media (max-width: 480px) { .action-buttons { grid-template-columns: 1fr; } }


/* Move Per Day / Per Piece boxes upward */
.rate-box {
    transform: translateY(-6px); /* adjust -4px, -8px, etc. */
}

.rate-box .small-label {
    position: relative;
    top: 8px;
}

.top-form-row {
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 14px;
    align-items: start;
    margin-bottom: 10px;
}

.name-desc-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.image-upload {
    height: 185px;
    margin-bottom: 0;
}

.name-desc-box textarea {
    height: 110px;
}

.product {
    cursor: pointer;
}

.product.selected {
    border: 2px solid #991000;
    background: #fff3f1;
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

        nav div {
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
</style>
</head>

<body>
    <?php if (!$sellerAcceptedAt): ?>
    <div class="terms-modal-backdrop" id="termsModalBackdrop">
        <div class="terms-modal" role="dialog" aria-modal="true" aria-labelledby="termsTitle">
            <div class="terms-header">
                <div>
                    <h3 id="termsTitle">Seller Terms &amp; Conditions</h3>
                    <div class="card-subtle">Please review and accept these guidelines before creating or updating listings.</div>
                </div>
                <button type="button" id="closeTermsBtn" style="background:#fff;color:#991000;border:1px solid #e5c2b8;padding:8px 10px;font-size:13px;">Close</button>
            </div>
            <div class="terms-body">
                <p>By listing products or rentals on IskoHub, you agree to:</p>
                <ul>
                    <li>Provide accurate item details, pricing, and stock availability.</li>
                    <li>Be responsible for the legality, quality, and condition of any product or rental you list.</li>
                    <li>Honor all confirmed orders, rental terms, and customer communication.</li>
                    <li>Use the platform in a lawful manner and respect buyers, renters, and campus policies.</li>
                    <li>Accept that false or misleading listings may be removed and reported.</li>
                </ul>
                <p>These terms help protect both sellers and buyers and are required before listing any item.</p>
            </div>
            <div class="terms-footer">
                <label><input type="checkbox" id="termsAcceptCheckbox"> I have reviewed and accept the seller terms and conditions.</label>
                <form id="acceptTermsForm" method="POST" style="display:flex; align-items:center; gap:8px; margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="accept_terms">
                    <button type="submit" id="acceptTermsBtn" style="padding:10px 14px;">Accept &amp; Continue</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- NAV -->
    <nav>
        <h1>IskoHub</h1>
        <div>
<a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
            <a href="orders.php"><i class="fa-solid fa-box"></i> Order History</a>
            <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>            
            <a href="lost_found_inbox.php"><i class="fa-solid fa-box-open">  Inbox</i></a>
            <a href="account.php"><i class="fa-solid fa-user"></i></a>
            <a href="logout.php" class="logout-btn">
Logout
</a>
        </div>
    </nav>

    <div class="receipt-modal-backdrop" id="receiptModalBackdrop" aria-hidden="true">
        <div class="receipt-modal-box" role="dialog" aria-modal="true" aria-label="Rental receipt preview">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:8px;">
                <strong>GCash Receipt Preview</strong>
                <button type="button" id="closeReceiptModalBtn" style="background:#fff;color:#991000;border:1px solid #e5c2b8;padding:8px 10px;font-size:13px;">Close</button>
            </div>
            <img id="receiptModalImage" src="" alt="GCash payment receipt preview">
            <div class="receipt-modal-actions">
                <span id="receiptModalLabel" style="font-size:13px;color:#374151;">Receipt image</span>
                <a id="receiptModalOpenLink" href="#" target="_blank" style="color:#991000;font-weight:700;text-decoration:none;">Open full image</a>
            </div>
        </div>
    </div>

<div class="dashboard">
    <div class="page-header">
        <div class="page-title">
            <h1>Seller Dashboard</h1>
          
        </div>
      
    </div>

    <div class="stats">

    <div class="stat">
        <h2><?= $total ?></h2>
        <p>Total</p>
    </div>

    <div class="stat">
        <h2><?= $active ?></h2>
        <p>Active</p>
    </div>

    <div class="stat">
        <h2><?= $out ?></h2>
        <p>Sold Out</p>
    </div>

</div>

<div class="grid">

<!-- ADD PRODUCT -->
<div class="card">
    <h2>List an Item</h2>


<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="selected_product_id" id="selected_product_id">
   <div class="top-form-row">
    <label class="image-upload">
        <input type="file" name="image" id="imageInput" required>
        <img id="preview">
        <span id="uploadText">Click to upload image</span>
    </label>

    <div class="name-desc-box">
        <input type="text" name="name" placeholder="Product Name" required>
        <textarea name="description" placeholder="Description" required></textarea>
    </div>
</div>



<input type="checkbox" name="seller_terms_accepted" id="sellerTermsAccepted" value="1" required <?= $sellerAcceptedAt ? 'checked' : '' ?> style="display:none;">

<label class="small-label">If Applicable:</label>
<input type="text" name="location" placeholder="Location">

<div class="form-row">
    <input type="number" name="price" placeholder="Price" required>

    <div class="rate-box">
        <label class="small-label">If Applicable:</label>
        <div class="rate-buttons">
<button type="button" class="rate-option" data-rate="Per Day">Per Day</button>
<button type="button" class="rate-option" data-rate="Per Piece">Per Piece</button>

<input type="hidden" name="rate_type" id="rate_type">

        </div>
    </div>
</div>

<label class="small-label">Rental Terms &amp; Conditions (for rentals)</label>
<textarea name="rental_terms" placeholder="Example: Customer is responsible for any damage, loss, or theft; late return penalties apply; replacement cost for unreturned items; security deposit policy."></textarea>

<div class="form-row bottom-row">
    <div class="quantity-box">
    <span>Quantity:</span>

    <button type="button" id="minusQty">−</button>

    <input
        type="text"
        name="stock"
        id="stockInput"
        value="1"
        required
        readonly
    >

    <button type="button" id="plusQty">+</button>
</div>

    <select name="category">

        <option value="1">Electronics</option>
        <option value="2">School Supplies</option>
        <option value="3">Services</option>
        <option value="4">Preloved</option>
        <option value="5">Rental</option>
        <option value="7">Others</option>

    </select>
</div>

<div class="action-buttons">
    <button type="submit" name="action" value="create">Create</button>
        <button type="submit"
                name="action"
                value="update"
                id="updateBtn"
                formnovalidate>
            Update
        </button>
        <button type="submit"
            name="action"
            value="delete"
            id="deleteBtn"
            formnovalidate>
        Delete
    </button>
    <button type="button" id="clearBtn">Clear</button>
</div>

</form>

</div>

<!-- PRODUCT LIST -->
<div class="card">
    <h2>My Products</h2>
    <p class="card-subtle">Select any listing to edit it, or use the action buttons to manage stock and visibility.</p>

<?php foreach ($products as $p): ?>

<div class="product<?= $p->prod_stock <= 0 ? ' sold-out' : '' ?>"
    data-id="<?= $p->prod_id ?>"
    data-name="<?= htmlspecialchars($p->prod_name) ?>"
    data-description="<?= htmlspecialchars($p->prod_desc ?? '') ?>"
    data-price="<?= $p->prod_price ?>"
    data-stock="<?= $p->prod_stock ?>"
    data-category="<?= $p->category_id ?? '' ?>"
    data-image="<?= htmlspecialchars($p->prod_image) ?>"
    data-location="<?= htmlspecialchars($p->prod_location ?? '') ?>"
    data-rate="<?= htmlspecialchars($p->prod_rate_type ?? '') ?>"
    data-rental-terms="<?= htmlspecialchars($p->rental_terms ?? '') ?>"
>

    <img src="<?= htmlspecialchars($p->prod_image) ?>">

    <div>
        <strong><?= htmlspecialchars($p->prod_name) ?></strong><br>
        ₱<?= $p->prod_price ?> | Stock: <?= $p->prod_stock ?>
        <?php if ($p->prod_stock <= 0): ?>
            <div style="color:#c00;font-weight:700;margin-top:4px;">SOLD OUT</div>
        <?php endif; ?>
    </div>

</div>

<?php endforeach; ?>

</div>
</div>


<div class="gap" style="margin-top:22px;">
<div class="card" >
    <h2>Rental Payment Verification</h2>
    <?php if (empty($pendingRentalPayments)): ?>
        <p style="color:#666;">No rental payment proofs are awaiting review.</p>
    <?php else: ?>
        <?php foreach ($pendingRentalPayments as $row): ?>
            <div style="border:1px solid #ddd;border-radius:10px;padding:10px;margin-bottom:10px;">
                <strong><?= htmlspecialchars($row['prod_name']) ?></strong><br>
                Customer: <?= htmlspecialchars($row['fullname']) ?> | Contact: <?= htmlspecialchars($row['phone']) ?><br>
                Status: <?= htmlspecialchars($row['payment_status']) ?><br>
                <?php if (!empty($row['payment_proof_path'])): ?>
                    <a href="#" class="open-receipt-modal" data-receipt="<?= htmlspecialchars($row['payment_proof_path']) ?>">View receipt</a>
                <?php endif; ?>
                <form method="POST" style="margin-top:8px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="order_item_id" value="<?= (int)$row['order_item_id'] ?>">
                    <input type="hidden" name="action" value="verify_payment">
                    <button type="submit" style="padding:8px 10px;">Approve</button>
                </form>
                <form method="POST" style="margin-top:6px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="order_item_id" value="<?= (int)$row['order_item_id'] ?>">
                    <input type="hidden" name="action" value="reject_payment">
                    <input type="text" name="reason" placeholder="Reason for rejection" required style="margin-bottom:6px;">
                    <button type="submit" style="padding:8px 10px;background:#6b1d13;">Reject</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>


</div>

<script>
const imageInput = document.getElementById("imageInput");
const preview = document.getElementById("preview");
const uploadText = document.getElementById("uploadText");
const clearBtn = document.getElementById("clearBtn");

imageInput.addEventListener("change", function(event) {
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = "block";
            uploadText.style.display = "none";
        };

        reader.readAsDataURL(file);
    }
});

clearBtn.addEventListener("click", function() {
    document.querySelector("form").reset();

    preview.src = "";
    preview.style.display = "none";
    uploadText.style.display = "block";
    rateOptions.forEach(btn => btn.classList.remove("selected-rate"));
    rateTypeInput.value = "";
});
</script>


<script>
const products = document.querySelectorAll(".product");

products.forEach(product => {

    product.addEventListener("click", function() {

        products.forEach(p => p.classList.remove("selected"));
        this.classList.add("selected");

        document.getElementById("selected_product_id").value = this.dataset.id;

        document.querySelector('input[name="name"]').value = this.dataset.name;

        document.querySelector('textarea[name="description"]').value =
            this.dataset.description;

        document.querySelector('input[name="price"]').value =
            this.dataset.price;

        document.querySelector('input[name="stock"]').value =
            this.dataset.stock;

        document.querySelector('select[name="category"]').value =
            this.dataset.category;

        document.querySelector('input[name="location"]').value =
            this.dataset.location;

        document.querySelector('textarea[name="rental_terms"]').value =
            this.dataset.rentalTerms || "";

        rateOptions.forEach(btn => btn.classList.remove("selected-rate"));

rateTypeInput.value = this.dataset.rate || "";

rateOptions.forEach(btn => {
    if (btn.dataset.rate === this.dataset.rate) {
        btn.classList.add("selected-rate");
    }
});



            preview.src = this.dataset.image;
            preview.style.display = "block";
            uploadText.style.display = "none";
    });

});


document.getElementById("updateBtn").addEventListener("click", function(event) {

    const selectedId = document.getElementById("selected_product_id").value;

    if (selectedId === "") {

        event.preventDefault();

        alert("Select product first to update it.");

        return;
    }
});



document.getElementById("deleteBtn").addEventListener("click", function(event) {
    const selectedId = document.getElementById("selected_product_id").value;

    if (selectedId === "") {
        event.preventDefault();
        alert("Select product first to Delete it.");
        return;
    }

    const confirmDelete = confirm("This product will be gone in the system and can't be retrieve anymore. Continue?");

    if (!confirmDelete) {
        event.preventDefault();
    }
});




const rateOptions = document.querySelectorAll(".rate-option");
const rateTypeInput = document.getElementById("rate_type");

rateOptions.forEach(button => {
    button.addEventListener("click", function() {
        rateOptions.forEach(btn => btn.classList.remove("selected-rate"));

        this.classList.add("selected-rate");

        rateTypeInput.value = this.dataset.rate;
    });
});


</script>


<script>

const minusQty = document.getElementById("minusQty");
const plusQty = document.getElementById("plusQty");
const stockInput = document.getElementById("stockInput");
const termsModalBackdrop = document.getElementById("termsModalBackdrop");
const termsAcceptCheckbox = document.getElementById("termsAcceptCheckbox");
const receiptModalBackdrop = document.getElementById("receiptModalBackdrop");
const receiptModalImage = document.getElementById("receiptModalImage");
const receiptModalOpenLink = document.getElementById("receiptModalOpenLink");
const receiptModalLabel = document.getElementById("receiptModalLabel");
const closeReceiptModalBtn = document.getElementById("closeReceiptModalBtn");
const sellerTermsAccepted = document.getElementById("sellerTermsAccepted");
const acceptTermsBtn = document.getElementById("acceptTermsBtn");
const acceptTermsForm = document.getElementById("acceptTermsForm");
const closeTermsBtn = document.getElementById("closeTermsBtn");

function syncTermsAcceptance() {
    if (sellerTermsAccepted && termsAcceptCheckbox) {
        sellerTermsAccepted.checked = termsAcceptCheckbox.checked;
    }
}

if (acceptTermsForm) {
    acceptTermsForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (!termsAcceptCheckbox.checked) {
            alert("Please accept the seller terms and conditions to continue.");
            return;
        }

        fetch("seller_dashboard.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
            body: new URLSearchParams(new FormData(acceptTermsForm))
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error("Unable to save your acceptance.");
            }
            window.location.reload();
        })
        .catch(function () {
            alert("Unable to save your acceptance. Please try again.");
        });
    });
}

if (closeTermsBtn && termsModalBackdrop) {
    closeTermsBtn.addEventListener("click", function () {
        if (!termsAcceptCheckbox || !termsAcceptCheckbox.checked) {
            window.location.href = "index.php";
            return;
        }

        termsModalBackdrop.style.display = "none";
    });
}

if (termsModalBackdrop) {
    termsModalBackdrop.addEventListener("click", function (event) {
        if (event.target === termsModalBackdrop && (!termsAcceptCheckbox || !termsAcceptCheckbox.checked)) {
            window.location.href = "index.php";
        }
    });
}

if (termsAcceptCheckbox) {
    termsAcceptCheckbox.addEventListener("change", syncTermsAcceptance);
}

function openReceiptModal(path) {
    if (!receiptModalBackdrop || !receiptModalImage || !receiptModalOpenLink) return;
    receiptModalImage.src = path;
    receiptModalOpenLink.href = path;
    receiptModalLabel.textContent = path.split('/').pop();
    receiptModalBackdrop.style.display = 'flex';
}

document.querySelectorAll('.open-receipt-modal').forEach(function (link) {
    link.addEventListener('click', function (event) {
        event.preventDefault();
        openReceiptModal(this.dataset.receipt || '');
    });
});

if (closeReceiptModalBtn && receiptModalBackdrop) {
    closeReceiptModalBtn.addEventListener('click', function () {
        receiptModalBackdrop.style.display = 'none';
    });
}

if (receiptModalBackdrop) {
    receiptModalBackdrop.addEventListener('click', function (event) {
        if (event.target === receiptModalBackdrop) {
            receiptModalBackdrop.style.display = 'none';
        }
    });
}

/* PLUS */
plusQty.addEventListener("click", function () {

    let current = parseInt(stockInput.value);

    stockInput.value = current + 1;
});

/* MINUS */
minusQty.addEventListener("click", function () {

    let current = parseInt(stockInput.value);

    if (current > 1) {
        stockInput.value = current - 1;
    }
});

</script>

</body>
</html>