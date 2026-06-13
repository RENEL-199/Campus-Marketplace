<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

/* ===========================
   MARK AS READ
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notifId = (int)$_POST['mark_read'];
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
        ->execute([$notifId, $user_id]);
    header('Location: inbox.php');
    exit;
}

/* ===========================
   FETCH NOTIFICATIONS
=========================== */
$stmt = $pdo->prepare("
    SELECT n.*, o.total, o.fullname, o.created_at AS order_date
    FROM notifications n
    JOIN orders o ON o.order_id = n.order_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = 0;
foreach ($notifications as $n) {
    if (!(int)$n['is_read']) $unreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inbox</title>
<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin: 0; background: #f3f5f2; font-family: Arial, sans-serif; }
.page { max-width: 800px; margin: 80px auto 40px; padding: 20px; }
h1 { color: #2e2e2e; margin-bottom: 6px; }
.subtitle { color: #777; font-size: 14px; margin-bottom: 24px; }
.notif-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: background 0.2s;
}
.notif-card.unread { background: #f0f8ff; border-left: 4px solid #007bff; }
.notif-icon {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.icon-approved { background: #d4edda; color: #28a745; }
.icon-rejected { background: #f8d7da; color: #dc3545; }
.notif-body { flex: 1; }
.notif-title { font-weight: bold; font-size: 14px; color: #222; margin-bottom: 4px; }
.notif-msg { font-size: 13px; color: #555; margin-bottom: 6px; }
.notif-meta { font-size: 12px; color: #999; }
.mark-btn {
    background: none; border: 1px solid #007bff; color: #007bff;
    padding: 4px 10px; border-radius: 6px; font-size: 12px;
    cursor: pointer; white-space: nowrap;
}
.mark-btn:hover { background: #007bff; color: #fff; }
.empty { text-align: center; color: #888; padding: 60px 20px; }
.badge-count {
    background: #dc3545; color: #fff; border-radius: 50%;
    padding: 2px 7px; font-size: 12px; margin-left: 6px;
}
</style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="page">
    <h1>
        <i class="fas fa-inbox"></i> Inbox
        <?php if ($unreadCount > 0): ?>
            <span class="badge-count"><?= $unreadCount ?></span>
        <?php endif; ?>
    </h1>
    <p class="subtitle">Notifications about your order approvals and rejections</p>

    <?php if (empty($notifications)): ?>
        <div class="empty">
            <i class="fas fa-bell-slash" style="font-size:48px; color:#ccc;"></i>
            <p>No notifications yet. You'll be notified when a seller approves or rejects your order.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
            <?php
                $isUnread = !(int)$notif['is_read'];
                $isApproved = $notif['type'] === 'approved';
                $iconClass = $isApproved ? 'icon-approved' : 'icon-rejected';
                $icon = $isApproved ? 'fa-check-circle' : 'fa-times-circle';
                $title = $isApproved
                    ? "Order #{$notif['order_id']} has been approved!"
                    : "Order #{$notif['order_id']} has been rejected.";
            ?>
            <div class="notif-card <?= $isUnread ? 'unread' : '' ?>">
                <div class="notif-icon <?= $iconClass ?>">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div class="notif-body">
                    <div class="notif-title"><?= htmlspecialchars($title) ?></div>
                    <?php if (!empty($notif['message'])): ?>
                        <div class="notif-msg">
                            <i class="fas fa-quote-left" style="font-size:10px; color:#bbb;"></i>
                            <?= htmlspecialchars($notif['message']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="notif-meta">
                        Order total: ₱<?= number_format($notif['total'], 2) ?> &bull;
                        <?= date('M j, Y g:i A', strtotime($notif['created_at'])) ?>
                    </div>
                </div>
                <?php if ($isUnread): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="mark_read" value="<?= $notif['id'] ?>">
                    <button type="submit" class="mark-btn">Mark read</button>
                </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
