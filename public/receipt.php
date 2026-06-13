<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_login();

$user_id = current_user_id();
$db = new Database();
$pdo = $db->pdo;
$order_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found";
    exit;
}

$stmt = $pdo->prepare(" 
    SELECT 
        oi.*, p.prod_name,
        p.rate_type,
        rd.rental_days AS rental_duration,
        sd.file_count,
        GROUP_CONCAT(sf.original_filename ORDER BY sf.service_file_id SEPARATOR '||') AS service_files_text
    FROM order_items oi
    JOIN products p ON p.prod_id = oi.product_id
    LEFT JOIN rental_details rd ON rd.ref_id = oi.order_item_id AND rd.ref_type = 'order'
    LEFT JOIN service_details sd ON sd.ref_id = oi.order_item_id AND sd.ref_type = 'order'
    LEFT JOIN service_files sf ON sf.ref_id = oi.order_item_id AND sf.ref_type = 'order'
    WHERE oi.order_id = ?
    GROUP BY oi.order_item_id
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function decodeServiceFiles(?string $text): array {
    if (empty($text)) return [];
    if (str_starts_with(trim($text), '[')) {
        $files = json_decode($text, true);
        return is_array($files) ? $files : [];
    }
    return array_values(array_filter(explode('||', $text)));
}
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

        <div class="title">IskoHub</div>
        <div class="subtitle">Official Receipt</div>

        <hr>

        <div class="subtitle">
            Order #<?= $order_id ?><br>
            <?= $order['created_at'] ?>
        </div>

        <hr>

        <!-- ITEMS -->
        <?php foreach ($items as $item): ?>
            <?php
                $itemSubtotal = isset($item['subtotal']) && (float)$item['subtotal'] > 0
                    ? (float)$item['subtotal']
                    : ((float)$item['unit_price'] * (int)$item['quantity']);

                $rateType = $item['rate_type'] ?? '';
                $duration = isset($item['rental_duration']) ? (int)$item['rental_duration'] : 1;
                $durationText = '';
                $serviceFiles = decodeServiceFiles($item['service_files_text'] ?? null);
                $serviceText = '';
                if (count($serviceFiles) > 0) {
                    $serviceText = ' (' . count($serviceFiles) . ' file' . (count($serviceFiles) > 1 ? 's' : '') . ')';
                }

                if ((strtolower(trim($rateType)) === 'per day' || strtolower(trim($rateType)) === 'per hour')) {
                    $durationText = ' (' . $duration . ' day' . ($duration > 1 ? 's' : '') . ')';
                }
            ?>
            <div class="item">
                <span><?= htmlspecialchars($item['prod_name']) ?> x<?= (int)$item['quantity'] ?><?= htmlspecialchars($durationText . $serviceText) ?></span>
                <span>₱<?= number_format($itemSubtotal, 2) ?></span>
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