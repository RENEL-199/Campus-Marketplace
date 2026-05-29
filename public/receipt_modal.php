<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';
require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$order_id = $_GET['id'] ?? null;

/* ORDER */
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Invalid order";
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

function decodeServiceFiles(?string $json): array {
    if (empty($json)) {
        return [];
    }
    $files = json_decode($json, true);
    return is_array($files) ? $files : [];
}
?>

<div class="title">IskoHub</div>
<div style="text-align:center; font-size:12px;">Receipt #<?= htmlspecialchars($order_id) ?></div>

<hr>

<?php foreach ($items as $item): ?>
<?php
    $itemSubtotal = isset($item['subtotal']) && (float)$item['subtotal'] > 0
        ? (float)$item['subtotal']
        : ((float)$item['price'] * (int)$item['quantity']);

    $rateType = $item['rate_type'] ?? '';
    $duration = isset($item['rental_duration']) ? (int)$item['rental_duration'] : 1;
    $durationText = '';
    $serviceFiles = decodeServiceFiles($item['service_files'] ?? null);
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

<div class="total">
    <span>TOTAL</span>
    <span>₱<?= $order['total'] ?></span>
</div>