<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

/* ===========================
   HANDLE APPROVE / REJECT
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($order_id > 0 && in_array($action, ['approve', 'reject'], true)) {
        // Verify this seller owns at least one product in the order
        $check = $pdo->prepare("
            SELECT o.user_id AS buyer_id
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.order_id
            JOIN products p ON p.prod_id = oi.product_id
            WHERE o.order_id = ? AND p.user_id = ?
            LIMIT 1
        ");
        $check->execute([$order_id, $user_id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newStatus = ($action === 'approve') ? 'confirmed' : 'cancelled';
            $notifType = ($action === 'approve') ? 'approved' : 'rejected';

            $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")
                ->execute([$newStatus, $order_id]);

            $pdo->prepare("
                INSERT INTO notifications (user_id, order_id, type, message)
                VALUES (?, ?, ?, ?)
            ")->execute([$row['buyer_id'], $order_id, $notifType, $message ?: null]);
        }
    }

    header('Location: seller_orders.php');
    exit;
}

/* ===========================
   FETCH ORDERS FOR THIS SELLER
=========================== */
$stmt = $pdo->prepare("
    SELECT DISTINCT o.order_id, o.user_id AS buyer_id, o.fullname, o.address,
           o.phone, o.payment_method, o.total, o.status, o.created_at,
           u.user_name AS buyer_name
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.order_id
    JOIN products p ON p.prod_id = oi.product_id
    LEFT JOIN users u ON u.user_id = o.user_id
    WHERE p.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seller Orders</title>
<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin: 0; background: #f3f5f2; font-family: Arial, sans-serif; }
.page { max-width: 900px; margin: 80px auto 40px; padding: 20px; }
h1 { color: #2e2e2e; margin-bottom: 20px; }
.order-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.order-header h3 { margin: 0; font-size: 16px; }
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.badge-pending { background: #fff3cd; color: #856404; }
.badge-confirmed { background: #d4edda; color: #155724; }
.badge-cancelled { background: #f8d7da; color: #721c24; }
.badge-completed { background: #cce5ff; color: #004085; }
.order-info { font-size: 14px; color: #555; margin: 4px 0; }
.actions { margin-top: 14px; display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
.actions input[type="text"] {
    flex: 1; min-width: 200px; padding: 8px 12px;
    border: 1px solid #ccc; border-radius: 8px; font-size: 13px;
}
.btn-approve, .btn-reject {
    padding: 8px 16px; border: none; border-radius: 8px;
    font-size: 13px; font-weight: bold; cursor: pointer;
}
.btn-approve { background: #28a745; color: #fff; }
.btn-reject { background: #dc3545; color: #fff; }
.btn-approve:hover { background: #218838; }
.btn-reject:hover { background: #c82333; }
.empty { text-align: center; color: #888; padding: 60px 20px; }
</style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="page">
    <h1><i class="fas fa-clipboard-list"></i> Incoming Orders</h1>

    <?php if (empty($orders)): ?>
        <div class="empty">
            <i class="fas fa-box-open" style="font-size:48px; color:#ccc;"></i>
            <p>No orders for your products yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <h3>Order #<?= $order['order_id'] ?> &mdash; <?= htmlspecialchars($order['buyer_name'] ?? $order['fullname']) ?></h3>
                    <?php
                        $badgeClass = 'badge-' . $order['status'];
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($order['status']) ?></span>
                </div>
                <div class="order-info"><strong>Total:</strong> ₱<?= number_format($order['total'], 2) ?></div>
                <div class="order-info"><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?></div>
                <div class="order-info"><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></div>
                <div class="order-info"><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></div>
                <div class="order-info"><strong>Date:</strong> <?= $order['created_at'] ?></div>

                <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="actions">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <input type="text" name="message" placeholder="Optional message to buyer...">
                    <button type="submit" name="action" value="approve" class="btn-approve">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="submit" name="action" value="reject" class="btn-reject">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
