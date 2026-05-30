<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();
$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;

$pdo->exec("\n    CREATE TABLE IF NOT EXISTS lost_items (\n        id INT(11) NOT NULL AUTO_INCREMENT,\n        item_name VARCHAR(255) NOT NULL,\n        description TEXT NOT NULL,\n        owner_name VARCHAR(255) DEFAULT NULL,\n        program VARCHAR(255) DEFAULT NULL,\n        contact VARCHAR(100) DEFAULT NULL,\n        social VARCHAR(255) DEFAULT NULL,\n        image VARCHAR(255) DEFAULT NULL,\n        user_id INT(11) DEFAULT NULL,\n        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n        type ENUM('lost','found') NOT NULL DEFAULT 'lost',\n        PRIMARY KEY (id),\n        KEY user_id (user_id)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");

$pdo->exec("\n    CREATE TABLE IF NOT EXISTS lost_found_claims (\n        id INT(11) NOT NULL AUTO_INCREMENT,\n        item_id INT(11) NOT NULL,\n        item_type ENUM('lost','found') NOT NULL,\n        claimant_name VARCHAR(255) NOT NULL,\n        claimant_program VARCHAR(255) DEFAULT NULL,\n        claimant_contact VARCHAR(100) DEFAULT NULL,\n        message TEXT DEFAULT NULL,\n        user_id INT(11) DEFAULT NULL,\n        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n        PRIMARY KEY (id),\n        KEY item_id (item_id),\n        KEY user_id (user_id)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");



function ensureLfColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

ensureLfColumn($pdo, 'lost_items', 'status', "status ENUM('open','claimed') NOT NULL DEFAULT 'open'");
ensureLfColumn($pdo, 'lost_items', 'claimed_claim_id', "claimed_claim_id INT(11) DEFAULT NULL");
ensureLfColumn($pdo, 'lost_items', 'claimed_at', "claimed_at DATETIME DEFAULT NULL");

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lfShortText(string $text, int $limit = 70): string {
    $text = trim($text);
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...');
    }
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

function lostFoundImageSrc(?string $image): string {
    $image = trim((string)$image);

    if ($image === '') {
        return 'uploads/lost_found-default.png';
    }

    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }

    $image = ltrim($image, '/');

    if (str_starts_with($image, 'uploads/')) {
        return $image;
    }

    return 'uploads/lost_found/' . $image;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_lf_claim') {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $item_type = strtolower(trim($_POST['item_type'] ?? 'lost'));
    $item_type = in_array($item_type, ['lost', 'found'], true) ? $item_type : 'lost';
    $claimant_name = trim($_POST['claimant_name'] ?? '');
    $claimant_program = trim($_POST['claimant_program'] ?? '');
    $claimant_contact = trim($_POST['claimant_contact'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($item_id <= 0 || $claimant_name === '' || $claimant_contact === '') {
        $error = 'Please complete your name and contact information.';
    } else {
        $checkItem = $pdo->prepare("SELECT id FROM lost_items WHERE id = ? LIMIT 1");
        $checkItem->execute([$item_id]);

        if (!$checkItem->fetchColumn()) {
            $error = 'Item not found.';
        } else {
            $stmt = $pdo->prepare("\n                INSERT INTO lost_found_claims\n                    (item_id, item_type, claimant_name, claimant_program, claimant_contact, message, user_id)\n                VALUES\n                    (?, ?, ?, ?, ?, ?, ?)\n            ");
            $stmt->execute([
                $item_id,
                $item_type,
                $claimant_name,
                $claimant_program ?: null,
                $claimant_contact,
                $message ?: null,
                $user_id
            ]);

            header('Location: lost_found.php?claim_sent=1&claim_type=' . urlencode($item_type));
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_lost_found') {
    $type = strtolower(trim($_POST['type'] ?? 'lost'));
    $type = in_array($type, ['lost', 'found'], true) ? $type : 'lost';

    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $social = trim($_POST['social'] ?? '');

    if ($item_name === '' || $description === '') {
        $error = 'Please enter the item name and description.';
    } else {
        $imagePath = null;

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $originalName = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                $error = 'Only JPG, PNG, WEBP, and GIF images are allowed.';
            } else {
                $uploadDir = __DIR__ . '/uploads/lost_found/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $safeName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $safeName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/lost_found/' . $safeName;
                } else {
                    $error = 'Image upload failed. Please try again.';
                }
            }
        }

        if ($error === null) {
            $stmt = $pdo->prepare("\n                INSERT INTO lost_items\n                    (item_name, description, owner_name, program, contact, social, image, user_id, type)\n                VALUES\n                    (?, ?, ?, ?, ?, ?, ?, ?, ?)\n            ");

            $stmt->execute([
                $item_name,
                $description,
                $owner_name ?: null,
                $program ?: null,
                $contact ?: null,
                $social ?: null,
                $imagePath,
                $user_id,
                $type
            ]);

            header('Location: lost_found.php?posted=1&type=' . urlencode($type));
            exit;
        }
    }
}

$filter = strtolower(trim($_GET['filter'] ?? 'all'));
$filter = in_array($filter, ['all', 'lost', 'found'], true) ? $filter : 'all';
$search = trim($_GET['q'] ?? '');

$where = ["li.status = 'open'"];
$params = [];

if ($filter !== 'all') {
    $where[] = 'li.type = ?';
    $params[] = $filter;
}

if ($search !== '') {
    $where[] = '(li.item_name LIKE ? OR li.description LIKE ? OR li.owner_name LIKE ? OR li.program LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("\n    SELECT li.*, u.user_name AS posted_by\n    FROM lost_items li\n    LEFT JOIN users u ON li.user_id = u.user_id\n    $whereSql\n    ORDER BY li.created_at DESC, li.id DESC\n");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lostCount = (int)$pdo->query("SELECT COUNT(*) FROM lost_items WHERE type = 'lost' AND status = 'open'")->fetchColumn();
$foundCount = (int)$pdo->query("SELECT COUNT(*) FROM lost_items WHERE type = 'found' AND status = 'open'")->fetchColumn();
$totalCount = $lostCount + $foundCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Lost & Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f6f4f1; color: #111; }
        nav { height: 58px; background: #810C01; color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 26px; box-shadow: 0 2px 10px rgba(0,0,0,.14); }
        nav h1 { margin: 0; font-size: 24px; font-weight: bold; }
        nav div { display: flex; align-items: center; gap: 18px; }
        nav a { color: white; text-decoration: none; font-size: 12px; }
        nav i { margin-right: 4px; font-size: 13px; }

        .lf-hero { position: relative; overflow: hidden; background: radial-gradient(circle at 75% 10%, rgba(255,221,210,.22), transparent 28%), linear-gradient(135deg, #6f0000 0%, #8d0a02 52%, #510000 100%); color: white; min-height: 360px; padding: 52px 7%; display: grid; grid-template-columns: 1.05fr .95fr; gap: 42px; align-items: center; }
        .lf-hero::before { content: ''; position: absolute; width: 420px; height: 420px; right: -160px; bottom: -190px; border-radius: 50%; background: rgba(255,255,255,.08); }
        .lf-hero-left, .lf-hero-right { position: relative; z-index: 1; }
        .lf-pill { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22); padding: 8px 13px; border-radius: 999px; font-size: 12px; font-weight: 700; margin-bottom: 16px; }
        .lf-hero h2 { font-size: clamp(42px, 5vw, 68px); line-height: .95; margin: 0 0 12px; font-family: Georgia, serif; letter-spacing: 1px; }
        .lf-hero p { margin: 0 0 28px; color: #fff2f0; font-size: 16px; max-width: 460px; }
        .lf-search { display: flex; gap: 12px; max-width: 520px; background: rgba(255,255,255,.12); padding: 8px; border-radius: 15px; backdrop-filter: blur(6px); }
        .lf-search input { flex: 1; height: 42px; border: none; outline: none; border-radius: 10px; padding: 0 14px; font-size: 14px; }
        .lf-search button { width: 92px; border: none; border-radius: 10px; background: #ffdd00; cursor: pointer; font-weight: bold; color: #201500; }
        .lf-hero-right { display: grid; grid-template-columns: .9fr 1fr; gap: 18px; align-items: center; }
        .lf-hero-visual { background: rgba(255,255,255,.9); border-radius: 26px; padding: 16px; box-shadow: 0 18px 45px rgba(0,0,0,.2); }
        .lf-hero-visual img { width: 100%; display: block; border-radius: 22px; }
        .lf-actions { display: flex; flex-direction: column; gap: 16px; }
        .lf-upload-card { width: 100%; min-height: 132px; border: none; border-radius: 22px; cursor: pointer; font-size: 17px; font-weight: 800; display: grid; grid-template-columns: 92px 1fr; gap: 12px; align-items: center; text-align: left; padding: 14px; transition: transform .15s ease, box-shadow .15s ease; box-shadow: 0 10px 25px rgba(0,0,0,.15); }
        .lf-upload-card:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(0,0,0,.24); }
        .lf-upload-card img { width: 92px; height: 78px; object-fit: contain; border-radius: 14px; background: rgba(255,255,255,.7); }
        .lf-upload-card span { display: block; font-size: 12px; font-weight: 600; margin-top: 4px; line-height: 1.3; }
        .lf-upload-card.lost { background: #f8d6d2; color: #810C01; }
        .lf-upload-card.found { background: #ffdd00; color: #111; }

        .lf-container { width: min(1080px, 88%); margin: 32px auto 70px; }
        .lf-message { background: white; border-left: 5px solid #810C01; padding: 12px 15px; margin-bottom: 18px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,.08); }
        .lf-message.error { border-left-color: #b00020; }
        .lf-title-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 14px; }
        .lf-title-row h2 { margin: 0; font-size: 30px; }
        .lf-inbox { text-decoration: none; width: 66px; height: 66px; background: #8b0d04; color: white; border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 10px; box-shadow: 0 8px 18px rgba(139,13,4,.22); }
        .lf-inbox i { font-size: 26px; margin-bottom: 4px; }
        .lf-filters { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 24px; }
        .lf-filters a { min-width: 110px; padding: 8px 18px; border: 1px solid #d9cbc7; border-radius: 999px; text-align: center; text-decoration: none; color: #111; font-weight: bold; background: white; transition: .15s ease; }
        .lf-filters a.active, .lf-filters a:hover { background: #8b0d04; color: white; border-color: #8b0d04; }
        .lf-section-heading { font-size: 25px; margin: 28px 0 16px; }
        .lf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 22px; }
        .lf-card { background: white; border-radius: 18px; overflow: hidden; min-height: 330px; box-shadow: 0 6px 18px rgba(0,0,0,.12); display: flex; flex-direction: column; transition: transform .15s ease, box-shadow .15s ease; }
        .lf-card:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(0,0,0,.16); }
        .lf-card-image { height: 190px; background: #fff7f6; }
        .lf-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .lf-card-body { padding: 14px 14px 16px; flex: 1; display: flex; flex-direction: column; }
        .lf-card-title { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 5px; }
        .lf-card-title h3 { font-size: 18px; margin: 0; }
        .lf-badge { border-radius: 999px; padding: 4px 12px; font-size: 11px; text-transform: capitalize; }
        .lf-badge.lost { background: #f1c7c2; color: #5b0905; }
        .lf-badge.found { background: #4c8c6b; color: white; }
        .lf-desc { margin: 0 0 10px; font-size: 13px; color: #444; line-height: 1.35; }
        .lf-posted { font-size: 12px; color: #777; margin-bottom: 12px; }
        .lf-check-btn { margin-top: auto; width: 100%; border: none; background: #8b0d04; color: white; border-radius: 10px; padding: 11px; font-weight: bold; cursor: pointer; }
        .lf-empty { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,.08); }
        .lf-modal { display: none; position: fixed; z-index: 1000; inset: 0; background: rgba(0,0,0,.45); padding: 26px; overflow: auto; }
        .lf-modal-content { width: min(720px, 96%); margin: 35px auto; background: white; border-radius: 22px; padding: 24px; position: relative; box-shadow: 0 20px 45px rgba(0,0,0,.24); }
        .lf-close { position: absolute; right: 18px; top: 15px; font-size: 28px; cursor: pointer; font-weight: bold; }
        .lf-form-head { display: flex; align-items: center; gap: 15px; margin-bottom: 16px; padding-right: 30px; }
        .lf-form-head img { width: 82px; height: 66px; object-fit: contain; border-radius: 16px; background: #fff7f6; }
        .lf-form-head h2 { margin: 0; }
        .lf-form-head p { margin: 3px 0 0; color: #666; font-size: 13px; }
        .lf-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .lf-form-full { grid-column: 1 / -1; }
        .lf-modal input, .lf-modal select, .lf-modal textarea { width: 100%; border: 1px solid #bbb; border-radius: 10px; padding: 11px 12px; font-family: Arial, sans-serif; }
        .lf-modal textarea { resize: vertical; min-height: 95px; }
        .lf-submit { width: 100%; border: none; background: #8b0d04; color: white; border-radius: 10px; padding: 13px; font-weight: bold; cursor: pointer; margin-top: 8px; }
        .lf-detail-image { width: 100%; max-height: 280px; object-fit: cover; border-radius: 14px; margin-bottom: 15px; background: #f5f5f5; }
        .lf-detail-row { margin: 8px 0; color: #333; }
        .lf-detail-row strong { color: #111; }
        @media (max-width: 900px) { .lf-hero { grid-template-columns: 1fr; } .lf-hero-right { grid-template-columns: 1fr; } .lf-actions { flex-direction: row; } }
        @media (max-width: 640px) { nav { padding: 0 16px; } nav h1 { font-size: 18px; } nav div { gap: 10px; } nav a span { display: none; } .lf-hero { padding: 38px 7%; } .lf-search { flex-direction: column; } .lf-search button { width: 100%; height: 40px; } .lf-actions { flex-direction: column; } .lf-form-grid { grid-template-columns: 1fr; } .lf-form-head { align-items: flex-start; } }

        .lf-modal-panel { width: min(780px, 96%); margin: 38px auto; background: #fff; border-radius: 24px; position: relative; box-shadow: 0 22px 55px rgba(0,0,0,.28); overflow: hidden; }
        .lf-detail-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; padding: 22px; align-items: stretch; }
        .lf-detail-photo { min-height: 285px; background: #f4f6f5; border-radius: 24px; overflow: hidden; box-shadow: inset 0 -3px 0 rgba(0,0,0,.08); }
        .lf-detail-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .lf-detail-info { display: flex; flex-direction: column; min-width: 0; }
        .lf-detail-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
        .lf-detail-top h2 { margin: 0; font-size: 23px; line-height: 1.15; }
        .lf-owner-box { font-size: 13px; line-height: 1.45; margin-bottom: 13px; }
        .lf-owner-box p { margin: 2px 0; }
        .lf-description-box { background: #f4f6f5; border-radius: 18px; padding: 16px; min-height: 118px; box-shadow: inset 0 -2px 0 rgba(0,0,0,.08); margin-bottom: 14px; }
        .lf-description-box h3 { margin: 0 0 8px; font-size: 16px; }
        .lf-description-box p { margin: 0; font-size: 13px; line-height: 1.45; color: #333; }
        .lf-primary-action { margin-top: auto; border: none; background: #8b0d04; color: #fff; border-radius: 10px; padding: 13px 16px; font-weight: 800; cursor: pointer; width: 100%; }
        .lf-claim-panel { width: min(430px, 94%); margin: 48px auto; background: #fff; border-radius: 20px; padding: 24px; position: relative; box-shadow: 0 22px 55px rgba(0,0,0,.28); }
        .lf-claim-panel h2 { font-size: 18px; margin: 0 0 16px; }
        .lf-claim-panel label { display: block; font-size: 12px; font-weight: 700; margin: 10px 0 5px; color: #333; }
        .lf-claim-panel input, .lf-claim-panel textarea { width: 100%; border: 1px solid #bbb; border-radius: 8px; padding: 9px 10px; font-family: Arial, sans-serif; }
        .lf-claim-panel textarea { min-height: 96px; resize: vertical; }
        .lf-help-text { font-size: 12px; color: #555; line-height: 1.35; margin: 0 0 8px; }
        .lf-small-submit { display: block; width: 72%; margin: 14px auto 0; border: none; background: #8b0d04; color: #fff; padding: 12px 14px; border-radius: 9px; font-weight: 800; cursor: pointer; }
        .lf-success-panel { width: min(410px, 92%); margin: 58px auto; background: #fff; border-radius: 20px; padding: 34px 28px 28px; text-align: center; position: relative; box-shadow: 0 22px 55px rgba(0,0,0,.28); }
        .lf-success-icon { width: 54px; height: 54px; margin: 0 auto 14px; border-radius: 50%; background: #f5dedb; color: #8b0d04; display: grid; place-items: center; font-size: 22px; }
        .lf-success-panel h2 { margin: 0 0 14px; font-size: 19px; }
        .lf-success-panel p { margin: 0 auto 22px; font-size: 14px; line-height: 1.42; max-width: 310px; }
        .lf-noted-btn { border: none; background: #8b0d04; color: #fff; border-radius: 9px; padding: 11px 34px; font-weight: 800; cursor: pointer; }
        @media (max-width: 700px) { .lf-detail-layout { grid-template-columns: 1fr; } .lf-detail-photo { min-height: 220px; } }
    </style>
</head>
<body>
<nav>
    <h1>Campus Lost & Found</h1>
    <div>
        <a href="index.php"><i class="fa-solid fa-house"></i> <span>Home</span></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> <span>Cart</span></a>
        <a href="orders.php"><i class="fa-solid fa-box"></i> <span>Orders</span></a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> <span>Sell</span></a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>
<section class="lf-hero">
    <div class="lf-hero-left">
        <div class="lf-pill"><i class="fa-solid fa-shield-heart"></i> Campus item recovery board</div>
        <h2>LOST &<br>FOUND</h2>
        <p>Recover what you lost and help return what you find. Post items, browse reports, and connect with the right person faster.</p>
        <form method="GET" class="lf-search">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <input type="text" name="q" placeholder="Search lost or found items..." value="<?= e($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    <div class="lf-hero-right">
        <div class="lf-hero-visual"><img src="uploads/lf-hero-visual.svg" alt="Lost and found illustration"></div>
        <div class="lf-actions">
            <button class="lf-upload-card lost" type="button" onclick="openPostModal('lost')">
                <img src="uploads/lf-post-lost.svg" alt="Post lost item">
                <div>Post Lost <span>Report something you lost on campus.</span></div>
            </button>
            <button class="lf-upload-card found" type="button" onclick="openPostModal('found')">
                <img src="uploads/lf-post-found.svg" alt="Post found item">
                <div>Post Found <span>Share an item you found safely.</span></div>
            </button>
        </div>
    </div>
</section>
<main class="lf-container">
    <?php if (isset($_GET['posted'])): ?><div class="lf-message">Your <?= e($_GET['type'] ?? 'item') ?> item was posted successfully.</div><?php endif; ?>
    <?php if ($error): ?><div class="lf-message error"><?= e($error) ?></div><?php endif; ?>
    <div class="lf-title-row">
        <h2>Categories</h2>
        <a class="lf-inbox" href="lost_found_inbox.php" title="Open lost and found inbox"><i class="fa-regular fa-envelope-open"></i>INBOX</a>
    </div>
    <div class="lf-filters">
        <a class="<?= $filter === 'all' ? 'active' : '' ?>" href="lost_found.php?filter=all<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">All (<?= $totalCount ?>)</a>
        <a class="<?= $filter === 'lost' ? 'active' : '' ?>" href="lost_found.php?filter=lost<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Lost (<?= $lostCount ?>)</a>
        <a class="<?= $filter === 'found' ? 'active' : '' ?>" href="lost_found.php?filter=found<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Found (<?= $foundCount ?>)</a>
    </div>
    <h2 class="lf-section-heading"><?= $filter === 'all' ? 'All Items' : ucfirst($filter) . ' Items' ?></h2>
    <?php if (empty($items)): ?>
        <div class="lf-empty">No <?= e($filter) ?> items found.</div>
    <?php else: ?>
        <div class="lf-grid">
            <?php foreach ($items as $item): ?>
                <?php $itemData = ['id'=>(int)$item['id'],'item_name'=>$item['item_name'],'description'=>$item['description'],'owner_name'=>$item['owner_name'],'program'=>$item['program'],'contact'=>$item['contact'],'social'=>$item['social'],'image'=>lostFoundImageSrc($item['image']),'type'=>$item['type'],'posted_by'=>$item['posted_by'] ?? 'Unknown','created_at'=>date('M d, Y', strtotime($item['created_at']))]; ?>
                <div class="lf-card">
                    <div class="lf-card-image"><img src="<?= e(lostFoundImageSrc($item['image'])) ?>" alt="<?= e($item['item_name']) ?>"></div>
                    <div class="lf-card-body">
                        <div class="lf-card-title"><h3><?= e($item['item_name']) ?></h3><span class="lf-badge <?= e($item['type']) ?>"><?= e($item['type']) ?></span></div>
                        <p class="lf-desc"><?= e(lfShortText($item['description'], 70)) ?></p>
                        <div class="lf-posted">Posted by <?= e($item['posted_by'] ?? 'Unknown') ?></div>
                        <button type="button" class="lf-check-btn" onclick='openDetailModal(<?= json_encode($itemData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Check Item</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<div id="postModal" class="lf-modal">
    <div class="lf-modal-content">
        <span class="lf-close" onclick="closePostModal()">&times;</span>
        <div class="lf-form-head">
            <img id="postModalImage" src="uploads/lf-post-lost.svg" alt="Post item">
            <div><h2 id="postModalTitle">Post Item</h2><p id="postModalText">Add details so students can identify the item easily.</p></div>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_lost_found">
            <input type="hidden" name="type" id="postTypeInput" value="lost">
            <div class="lf-form-grid">
                <div><label>Item Name</label><input type="text" name="item_name" required></div>
                <div><label>Your Name</label><input type="text" name="owner_name" placeholder="Name of reporter"></div>
                <div class="lf-form-full"><label>Description</label><textarea name="description" placeholder="Describe the item, where it was lost/found, color, marks, etc." required></textarea></div>
                <div><label>Program / Section</label><input type="text" name="program" placeholder="Example: BSIT 2-2"></div>
                <div><label>Contact No.</label><input type="text" name="contact" placeholder="Phone number"></div>
                <div><label>Social Media</label><input type="text" name="social" placeholder="Facebook / Messenger / IG"></div>
                <div class="lf-form-full"><label>Item Image</label><input type="file" name="image" accept="image/*"></div>
            </div>
            <button type="submit" class="lf-submit">Submit Post</button>
        </form>
    </div>
</div>
<div id="detailModal" class="lf-modal">
    <div class="lf-modal-panel">
        <span class="lf-close" onclick="closeDetailModal()">&times;</span>
        <div class="lf-detail-layout">
            <div class="lf-detail-photo">
                <img id="detailImage" src="" alt="Item image">
            </div>
            <div class="lf-detail-info">
                <div class="lf-detail-top">
                    <h2 id="detailName"></h2>
                    <div id="detailBadge"></div>
                </div>
                <div class="lf-owner-box">
                    <p><strong id="detailReporterLabel">Reporter:</strong> <span id="detailOwner"></span></p>
                    <p><strong>Program/Year:</strong> <span id="detailProgram"></span></p>
                    <p><strong>Contact:</strong> <span id="detailContact"></span></p>
                    <p><strong>Social Media:</strong> <span id="detailSocial"></span></p>
                </div>
                <div class="lf-description-box">
                    <h3>Description</h3>
                    <p id="detailDescription"></p>
                </div>
                <button type="button" id="detailActionBtn" class="lf-primary-action" onclick="openClaimModalFromDetail()">Continue</button>
            </div>
        </div>
    </div>
</div>
<div id="claimModal" class="lf-modal">
    <div class="lf-claim-panel">
        <span class="lf-close" onclick="closeClaimModal()">&times;</span>
        <h2 id="claimModalTitle">Contact Form</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_lf_claim">
            <input type="hidden" name="item_id" id="claimItemId">
            <input type="hidden" name="item_type" id="claimItemType">
            <label id="claimNameLabel">Your Information</label>
            <input type="text" name="claimant_name" placeholder="Full Name" required>
            <input type="text" name="claimant_program" placeholder="Program/Year" style="margin-top:8px;">
            <label>Your Contact Info</label>
            <p id="claimHelpText" class="lf-help-text">Provide your details so the reporter can contact you.</p>
            <input type="text" name="claimant_contact" placeholder="Contact number or social media" required>
            <textarea name="message" placeholder="Description"></textarea>
            <button type="submit" id="claimSubmitBtn" class="lf-small-submit">Send</button>
        </form>
    </div>
</div>
<div id="successModal" class="lf-modal">
    <div class="lf-success-panel">
        <span class="lf-close" onclick="closeSuccessModal()">&times;</span>
        <div class="lf-success-icon"><i class="fa-solid fa-check"></i></div>
        <h2>Notification Sent!</h2>
        <p id="successMessage">Your details were submitted successfully.</p>
        <button type="button" class="lf-noted-btn" onclick="closeSuccessModal()">Noted</button>
    </div>
</div>
<script>
function openPostModal(type) {
    const modal = document.getElementById('postModal');
    const typeInput = document.getElementById('postTypeInput');
    const title = document.getElementById('postModalTitle');
    const text = document.getElementById('postModalText');
    const image = document.getElementById('postModalImage');
    typeInput.value = type;
    if (type === 'found') {
        title.textContent = 'Post Found Item';
        text.textContent = 'Share the item you found so the owner can claim it.';
        image.src = 'uploads/lf-post-found.svg';
    } else {
        title.textContent = 'Post Lost Item';
        text.textContent = 'Report the item you lost so others can help you find it.';
        image.src = 'uploads/lf-post-lost.svg';
    }
    modal.style.display = 'block';
}
function closePostModal() { document.getElementById('postModal').style.display = 'none'; }
let currentLfItem = null;
function openDetailModal(item) {
    currentLfItem = item;
    document.getElementById('detailImage').src = item.image || 'uploads/lost_found-default.png';
    document.getElementById('detailName').textContent = item.item_name || '';
    document.getElementById('detailDescription').textContent = item.description || '';
    document.getElementById('detailOwner').textContent = item.owner_name || 'Not provided';
    document.getElementById('detailProgram').textContent = item.program || 'Not provided';
    document.getElementById('detailContact').textContent = item.contact || 'Not provided';
    document.getElementById('detailSocial').textContent = item.social || 'Not provided';
    document.getElementById('detailBadge').innerHTML = '<span class="lf-badge ' + item.type + '">' + item.type + '</span>';

    const reporterLabel = document.getElementById('detailReporterLabel');
    const actionBtn = document.getElementById('detailActionBtn');

    if (item.type === 'found') {
        reporterLabel.textContent = 'Finder:';
        actionBtn.textContent = 'Claim the Item';
    } else {
        reporterLabel.textContent = 'Owner:';
        actionBtn.textContent = 'I found the Item';
    }

    document.getElementById('detailModal').style.display = 'block';
}
function closeDetailModal() { document.getElementById('detailModal').style.display = 'none'; }
function openClaimModalFromDetail() {
    if (!currentLfItem) return;
    closeDetailModal();
    const itemType = currentLfItem.type || 'lost';
    document.getElementById('claimItemId').value = currentLfItem.id || '';
    document.getElementById('claimItemType').value = itemType;

    const title = document.getElementById('claimModalTitle');
    const help = document.getElementById('claimHelpText');
    const submitBtn = document.getElementById('claimSubmitBtn');

    if (itemType === 'found') {
        title.textContent = "Owner's Claim Form";
        help.textContent = 'Please enter your contact details below. We will notify the finder so they can coordinate the return.';
        submitBtn.textContent = 'Claim Item';
    } else {
        title.textContent = 'Information of the Finder';
        help.textContent = 'Provide your details so the owner can contact you and arrange a safe meet-up.';
        submitBtn.textContent = 'Notify the Owner';
    }

    document.getElementById('claimModal').style.display = 'block';
}
function closeClaimModal() { document.getElementById('claimModal').style.display = 'none'; }
function closeSuccessModal() { document.getElementById('successModal').style.display = 'none'; }
function openSuccessModal(type) {
    const msg = document.getElementById('successMessage');
    if (type === 'found') {
        msg.textContent = 'The finder has been notified. Please check your inbox for their reply and coordinate the safe return of your item.';
    } else {
        msg.textContent = 'The owner has been notified. Please wait for them to contact you and arrange a safe meet-up.';
    }
    document.getElementById('successModal').style.display = 'block';
}
<?php if (isset($_GET['claim_sent'])): ?>
openSuccessModal(<?= json_encode($_GET['claim_type'] ?? 'lost') ?>);
<?php endif; ?>
window.addEventListener('click', function(event) {
    const postModal = document.getElementById('postModal');
    const detailModal = document.getElementById('detailModal');
    const claimModal = document.getElementById('claimModal');
    const successModal = document.getElementById('successModal');
    if (event.target === postModal) closePostModal();
    if (event.target === detailModal) closeDetailModal();
    if (event.target === claimModal) closeClaimModal();
    if (event.target === successModal) closeSuccessModal();
});
</script>
</body>
</html>
