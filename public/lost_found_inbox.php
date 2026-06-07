<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$pdo->exec("CREATE TABLE IF NOT EXISTS lost_found_claims (
    id INT(11) NOT NULL AUTO_INCREMENT,
    item_id INT(11) NOT NULL,
    item_type ENUM('lost','found') NOT NULL,
    claimant_name VARCHAR(255) NOT NULL,
    claimant_program VARCHAR(255) DEFAULT NULL,
    claimant_contact VARCHAR(100) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    user_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY item_id (item_id),
    KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

ensureColumn($pdo, 'lost_items', 'status', "status ENUM('open','claimed') NOT NULL DEFAULT 'open'");
ensureColumn($pdo, 'lost_items', 'claimed_claim_id', "claimed_claim_id INT(11) DEFAULT NULL");
ensureColumn($pdo, 'lost_items', 'claimed_at', "claimed_at DATETIME DEFAULT NULL");
ensureColumn($pdo, 'lost_found_claims', 'deleted_by_owner', "deleted_by_owner TINYINT(1) NOT NULL DEFAULT 0");
ensureColumn($pdo, 'lost_found_claims', 'deleted_by_claimant', "deleted_by_claimant TINYINT(1) NOT NULL DEFAULT 0");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_claimed') {
    $claim_id = (int)($_POST['claim_id'] ?? 0);

    $check = $pdo->prepare("SELECT c.id, c.item_id FROM lost_found_claims c INNER JOIN lost_items i ON i.id = c.item_id WHERE c.id = ? AND i.user_id = ? LIMIT 1");
    $check->execute([$claim_id, $user_id]);
    $claim = $check->fetch(PDO::FETCH_ASSOC);

    if ($claim) {
        $update = $pdo->prepare("UPDATE lost_items SET status = 'claimed', claimed_claim_id = ?, claimed_at = NOW() WHERE id = ? AND user_id = ?");
        $update->execute([$claim_id, (int)$claim['item_id'], $user_id]);
        header('Location: lost_found_inbox.php?marked=1&claim_id=' . $claim_id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_message') {
    $claim_id = (int)($_POST['claim_id'] ?? 0);
    $delete_tab = $_POST['tab'] ?? 'received';
    $delete_tab = in_array($delete_tab, ['received', 'sent'], true) ? $delete_tab : 'received';

    if ($delete_tab === 'received') {
        $check = $pdo->prepare("SELECT c.id FROM lost_found_claims c INNER JOIN lost_items i ON i.id = c.item_id WHERE c.id = ? AND i.user_id = ? LIMIT 1");
        $check->execute([$claim_id, $user_id]);

        if ($check->fetch(PDO::FETCH_ASSOC)) {
            $delete = $pdo->prepare("UPDATE lost_found_claims SET deleted_by_owner = 1 WHERE id = ?");
            $delete->execute([$claim_id]);
            header('Location: lost_found_inbox.php?tab=received&deleted=1');
            exit;
        }
    } else {
        $check = $pdo->prepare("SELECT id FROM lost_found_claims WHERE id = ? AND user_id = ? LIMIT 1");
        $check->execute([$claim_id, $user_id]);

        if ($check->fetch(PDO::FETCH_ASSOC)) {
            $delete = $pdo->prepare("UPDATE lost_found_claims SET deleted_by_claimant = 1 WHERE id = ?");
            $delete->execute([$claim_id]);
            header('Location: lost_found_inbox.php?tab=sent&deleted=1');
            exit;
        }
    }
}

$tab = $_GET['tab'] ?? 'received';
$tab = in_array($tab, ['received', 'sent'], true) ? $tab : 'received';
$search = trim($_GET['q'] ?? '');
$selectedClaimId = (int)($_GET['claim_id'] ?? 0);

$params = [];
$whereSearch = '';
if ($search !== '') {
    $whereSearch = " AND (i.item_name LIKE ? OR c.claimant_name LIKE ? OR c.message LIKE ?)";
    $like = '%' . $search . '%';
}

if ($tab === 'received') {
    $sql = "SELECT c.*, i.item_name, i.description AS item_description, i.owner_name, i.program, i.contact, i.social, i.type AS post_type, i.status, i.user_id AS item_owner_id
            FROM lost_found_claims c
            INNER JOIN lost_items i ON i.id = c.item_id
            WHERE i.user_id = ? AND c.deleted_by_owner = 0" . $whereSearch . "
            ORDER BY c.created_at DESC";
    $params[] = $user_id;
} else {
    $sql = "SELECT c.*, i.item_name, i.description AS item_description, i.owner_name, i.program, i.contact, i.social, i.type AS post_type, i.status, i.user_id AS item_owner_id
            FROM lost_found_claims c
            INNER JOIN lost_items i ON i.id = c.item_id
            WHERE c.user_id = ? AND c.deleted_by_claimant = 0" . $whereSearch . "
            ORDER BY c.created_at DESC";
    $params[] = $user_id;
}
if ($search !== '') { $params[] = $like; $params[] = $like; $params[] = $like; }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($selectedClaimId <= 0 && count($claims) > 0) {
    $selectedClaimId = (int)$claims[0]['id'];
}

$selected = null;
foreach ($claims as $row) {
    if ((int)$row['id'] === $selectedClaimId) {
        $selected = $row;
        break;
    }
}

function claimTitle(array $row): string {
    if (($row['post_type'] ?? '') === 'lost') {
        return 'Someone found your item';
    }
    return 'Someone is claiming your found item';
}

function claimPersonLabel(array $row): string {
    if (($row['post_type'] ?? '') === 'lost') {
        return 'Found by';
    }
    return 'Claim by';
}

function formatDateTime($value): string {
    $time = strtotime((string)$value);
    if (!$time) return '';
    return date('F j, Y | h:i A', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found Inbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f5f3; color: #111; }
        nav { height: 58px; background: #8b0d04; color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
        nav h1 { margin: 0; font-size: 18px; font-weight: 800; }
        nav div { display: flex; align-items: center; gap: 16px; }
        nav a { color: white; text-decoration: none; font-size: 14px; }
        .page { padding: 30px 42px; }
        .inbox-shell { display: grid; grid-template-columns: 340px 1fr; gap: 26px; min-height: calc(100vh - 118px); }
        .sidebar, .detail-wrap { background: white; border-radius: 22px; box-shadow: 0 8px 22px rgba(0,0,0,.08); }
        .sidebar { padding: 18px; overflow: hidden; }
        .search-row { display: flex; gap: 8px; margin-bottom: 12px; }
        .search-row input { flex: 1; height: 36px; border: 1px solid #bbb; border-radius: 8px; padding: 0 10px; }
        .search-row button { width: 54px; border: none; border-radius: 8px; background: #ffdc00; cursor: pointer; }
        .tabs { display: flex; gap: 6px; margin-bottom: 16px; }
        .tabs a { flex: 1; text-align: center; padding: 9px; border-radius: 9px; color: #333; text-decoration: none; font-weight: 800; font-size: 13px; }
        .tabs a.active { background: #8b0d04; color: white; }
        .message-list { display: flex; flex-direction: column; gap: 10px; max-height: calc(100vh - 230px); overflow-y: auto; padding-right: 4px; }
        .msg-card { display: block; text-decoration: none; color: #111; border: 1px solid #e2e2e2; border-radius: 12px; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); background: #fff; }
        .msg-card.active { background: #e7bbb7; border-color: #d99a94; }
        .msg-top { display: flex; justify-content: space-between; gap: 8px; font-size: 11px; color: #555; margin-bottom: 6px; }
        .msg-title { font-weight: 900; font-size: 14px; margin-bottom: 2px; }
        .msg-sub { font-size: 12px; color: #444; line-height: 1.35; }
        .detail-wrap { background: #ddd; padding: 24px; position: relative; }
        .detail-card { background: white; border-radius: 14px; padding: 24px; min-height: 440px; box-shadow: 0 3px 10px rgba(0,0,0,.13); }
        .detail-head { display: flex; align-items: start; gap: 14px; margin-bottom: 16px; }
        .detail-head h2 { margin: 0; font-size: 22px; }
        .status { border-radius: 999px; padding: 7px 15px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .status.open { background: #e8f4ee; color: #2f7d57; }
        .status.claimed { background: #f3dddd; color: #8b0d04; }
        .info-line { margin: 4px 0; font-size: 14px; }
        .info-line strong { display: inline-block; min-width: 115px; }
        .section-title { margin: 18px 0 8px; font-size: 17px; font-weight: 900; }
        .desc-box, .claim-box { background: #f4f6f5; border-radius: 16px; padding: 18px; box-shadow: inset 0 -2px 0 rgba(0,0,0,.07); }
        .claim-box { margin-top: 18px; }
        .center-note { text-align: center; margin-top: 24px; font-size: 13px; line-height: 1.45; }
        .top-actions { position: absolute; top: 24px; right: 24px; display: flex; gap: 10px; align-items: center; }
        .mark-btn, .delete-btn { border: none; color: white; font-weight: 900; border-radius: 7px; padding: 11px 16px; cursor: pointer; }
        .mark-btn { background: #8b0d04; }
        .delete-btn { background: #555; }
        .delete-btn:hover { background: #333; }
        .empty { text-align: center; color: #666; padding: 40px 10px; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; color: #8b0d04; text-decoration: none; font-weight: 900; margin-bottom: 14px; }
        .alert { background: #e8f4ee; border-left: 5px solid #2f7d57; padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; }
        @media (max-width: 900px) { .page { padding: 20px; } .inbox-shell { grid-template-columns: 1fr; } .message-list { max-height: 360px; } .top-actions { position: static; margin-bottom: 14px; justify-content: flex-start; } }
    </style>
</head>
<body>
<nav>
    <h1>Campus Market</h1>
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

<div class="page">
    <a class="back-link" href="lost_found.php"><i class="fa-solid fa-arrow-left"></i> Back to Lost & Found</a>
    <?php if (isset($_GET['marked'])): ?><div class="alert">Item marked as claimed.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert">Message deleted.</div><?php endif; ?>

    <div class="inbox-shell">
        <aside class="sidebar">
            <form method="GET" class="search-row">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <input type="text" name="q" placeholder="Search" value="<?= e($search) ?>">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <div class="tabs">
                <a class="<?= $tab === 'received' ? 'active' : '' ?>" href="lost_found_inbox.php?tab=received">Received</a>
                <a class="<?= $tab === 'sent' ? 'active' : '' ?>" href="lost_found_inbox.php?tab=sent">Sent</a>
            </div>
            <div class="message-list">
                <?php if (!$claims): ?>
                    <div class="empty">No <?= e($tab) ?> notifications yet.</div>
                <?php endif; ?>
                <?php foreach ($claims as $claim): ?>
                    <a class="msg-card <?= (int)$claim['id'] === $selectedClaimId ? 'active' : '' ?>" href="lost_found_inbox.php?tab=<?= e($tab) ?>&claim_id=<?= (int)$claim['id'] ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">
                        <div class="msg-top"><span><i class="fa-solid fa-user"></i> <?= e($claim['claimant_name']) ?></span><span><?= e(formatDateTime($claim['created_at'])) ?></span></div>
                        <div class="msg-title"><?= e($claim['item_name']) ?></div>
                        <div class="msg-sub"><?= e(claimTitle($claim)) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="detail-wrap">
            <?php if ($selected): ?>
                <div class="top-actions">
                    <?php if ($tab === 'received' && ($selected['status'] ?? 'open') !== 'claimed'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_claimed">
                            <input type="hidden" name="claim_id" value="<?= (int)$selected['id'] ?>">
                            <button class="mark-btn" type="submit">Mark As Claimed</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return confirm('Delete this message from your <?= e($tab) ?> inbox?');">
                        <input type="hidden" name="action" value="delete_message">
                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <input type="hidden" name="claim_id" value="<?= (int)$selected['id'] ?>">
                        <button class="delete-btn" type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
                </div>
                <div class="detail-card">
                    <div class="detail-head">
                        <h2><?= e($selected['item_name']) ?></h2>
                        <span class="status <?= e($selected['status'] ?? 'open') ?>"><?= e($selected['status'] ?? 'open') ?></span>
                    </div>

                    <div class="info-line"><strong><?= ($selected['post_type'] ?? '') === 'lost' ? 'Owner:' : 'Finder:' ?></strong> <?= e($selected['owner_name'] ?: 'Not provided') ?></div>
                    <div class="info-line"><strong>Program/Year:</strong> <?= e($selected['program'] ?: 'Not provided') ?></div>
                    <div class="info-line"><strong>Contact:</strong> <?= e($selected['contact'] ?: 'Not provided') ?></div>
                    <div class="info-line"><strong>Social Media:</strong> <?= e($selected['social'] ?: 'Not provided') ?></div>

                    <div class="section-title">Description</div>
                    <div class="desc-box"><?= nl2br(e($selected['item_description'])) ?></div>

                    <div class="claim-box">
                        <div class="info-line"><strong><?= e(claimPersonLabel($selected)) ?>:</strong> <?= e($selected['claimant_name']) ?></div>
                        <div class="info-line"><strong>Program/Year:</strong> <?= e($selected['claimant_program'] ?: 'Not provided') ?></div>
                        <div class="info-line"><strong>Contact:</strong> <?= e($selected['claimant_contact'] ?: 'Not provided') ?></div>
                        <div class="info-line"><strong>Message:</strong> <?= e($selected['message'] ?: 'No message provided') ?></div>
                    </div>

                    <div class="center-note">
                        <?php if ($tab === 'received'): ?>
                            <strong><?= ($selected['post_type'] ?? '') === 'lost' ? 'Claim your belonging' : 'Stay Reachable' ?></strong><br>
                            Please use the contact information above to coordinate the item return.
                        <?php else: ?>
                            <strong>Notification sent</strong><br>
                            Please wait for the poster to contact you using the details you provided.
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="detail-card empty">Select a notification to view its details.</div>
            <?php endif; ?>
        </section>
    </div>
</div>
</body>
</html>
