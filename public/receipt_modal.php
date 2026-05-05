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
    SELECT oi.*, p.name
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="title">CAMPUS MARKET</div>
<div style="text-align:center; font-size:12px;">Receipt #<?= $order_id ?></div>

<hr>

<?php foreach ($items as $item): ?>
<div class="item">
    <span><?= $item['name'] ?> x<?= $item['quantity'] ?></span>
    <span>₱<?= $item['price'] * $item['quantity'] ?></span>
</div>
<?php endforeach; ?>

<hr>

<div class="total">
    <span>TOTAL</span>
    <span>₱<?= $order['total'] ?></span>
</div>