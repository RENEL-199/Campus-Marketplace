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
    echo "Invalid order";
    exit;
}

$stmt = $pdo->prepare(" 
    SELECT 
        oi.*,
        rd.rental_days AS rental_duration,
        sd.file_count,
        GROUP_CONCAT(sf.original_filename ORDER BY sf.service_file_id SEPARATOR '||') AS service_files_text
    FROM order_items oi
    LEFT JOIN rental_details rd ON rd.ref_type = 'order' AND rd.ref_id = oi.order_item_id
    LEFT JOIN service_details sd ON sd.ref_type = 'order' AND sd.ref_id = oi.order_item_id
    LEFT JOIN service_files sf ON sf.ref_type = 'order' AND sf.ref_id = oi.order_item_id
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
<div class="title">IskoHub</div>
<div style="text-align:center; font-size:12px;">Receipt #<?= htmlspecialchars($order_id) ?></div>

<hr>

<?php foreach ($items as $item): ?>
<?php
    $itemSubtotal = isset($item['subtotal']) && (float)$item['subtotal'] > 0
        ? (float)$item['subtotal']
        : ((float)$item['unit_price'] * (int)$item['quantity']);

    $rateType = $item['rate_type_snapshot'] ?? '';
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
    <span><?= htmlspecialchars($item['product_name_snapshot'] ?? '') ?> x<?= (int)$item['quantity'] ?><?= htmlspecialchars($durationText . $serviceText) ?></span>
    <span>₱<?= number_format($itemSubtotal, 2) ?></span>
</div>
<?php endforeach; ?>

<hr>

<div class="total">
    <span>TOTAL</span>
    <span>₱<?= $order['total'] ?></span>
</div>