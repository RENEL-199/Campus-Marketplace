<?php
// Shared navigation bar — include from any page
// Unread notification count for inbox badge
if (!isset($pdo)) {
    require_once __DIR__ . '/../../app/Database.php';
    $db = new Database();
    $pdo = $db->pdo;
}
if (!isset($user_id)) {
    require_once __DIR__ . '/../../app/auth.php';
    $user_id = current_user_id();
}
$_navUnread = 0;
if ($user_id) {
    $__nStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $__nStmt->execute([$user_id]);
    $_navUnread = (int)$__nStmt->fetchColumn();
}
?>
<style>
nav { height: 58px; background: #810C01; color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
nav h1 { font-size: 20px; margin: 0; }
nav div { display: flex; align-items: center; gap: 18px; }
nav a { color: white; text-decoration: none; font-size: 14px; }
nav i { margin-right: 4px; }
.inbox-badge { background: #ff4444; color: #fff; border-radius: 50%; padding: 1px 6px; font-size: 11px; margin-left: 2px; }
</style>
<nav>
    <h1>IskoHub</h1>
    <div>
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <a href="inbox.php"><i class="fa-solid fa-envelope"></i> Inbox<?php if ($_navUnread > 0): ?><span class="inbox-badge"><?= $_navUnread ?></span><?php endif; ?></a>
        <a href="orders.php"><i class="fa-solid fa-box"></i> Orders</a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>
        <a href="seller_orders.php"><i class="fa-solid fa-clipboard-list"></i> Seller Orders</a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
        <a href="logout.php">Logout</a>
    </div>
</nav>
