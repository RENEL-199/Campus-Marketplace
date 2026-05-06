<?php

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/auth.php';

require_login();

$user_id = current_user_id();

$db = new Database();
$pdo = $db->pdo;


$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {

    $stmt = $pdo->prepare("
        UPDATE users SET
            full_name=?,
            student_id=?,
            course=?,
            year_level=?,
            age=?,
            gender=?,
            birthday=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['full_name'],
        $_POST['student_id'],
        $_POST['course'],
        $_POST['year_level'],
        $_POST['age'],
        $_POST['gender'],
        $_POST['birthday'],
        $user_id
    ]);

    header("Location: account.php");
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {

    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$newPassword, $user_id]);

    header("Location: account.php");
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['profile_pic'])) {

    $uploadDir = __DIR__ . "/uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {

        $path = "uploads/" . $fileName;

        $stmt = $pdo->prepare("UPDATE users SET profile_pic=? WHERE id=?");
        $stmt->execute([$path, $user_id]);

        header("Location: account.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>My Account</title>

<link rel="stylesheet" href="../assets/index-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

.account-container {
    max-width: 900px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.profile {
    display: flex;
    gap: 20px;
    align-items: center;
}

.profile img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
}

.section {
    margin-top: 25px;
}

input, select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.btn {
    background: var(--primary);
    color: white;
    padding: 10px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

.btn:hover {
    background: var(--primary-dark);
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 700px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>

<nav>
    <h1>Campus Market</h1>
    <div>
  <a href="index.php"><i class="fa-solid fa-house"></i></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
        <a href="orders.php"><i class="fa-solid fa-box"></i></a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i></a>
        <a href="account.php"><i class="fa-solid fa-user"></i></a>
        
    </div>
</nav>

<div class="account-container">

<h2>My Account</h2>


<div class="profile">

    <img src="<?= $user['profile_pic'] ?? 'uploads/default.png' ?>">

    <div>
        <h3><?= htmlspecialchars($user['full_name'] ?? 'No name set') ?></h3>
        <p><?= htmlspecialchars($user['student_id'] ?? '') ?></p>
    </div>

</div>

<div class="section">
    <h3>Profile Picture</h3>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="profile_pic" required>
        <button class="btn">Upload</button>
    </form>
</div>

<div class="section">
    <h3>Personal Information</h3>

    <form method="POST">

        <div class="grid">

            <div>
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= $user['full_name'] ?? '' ?>">

                <label>Student ID</label>
                <input type="text" name="student_id" value="<?= $user['student_id'] ?? '' ?>">

                <label>Course</label>
                <input type="text" name="course" value="<?= $user['course'] ?? '' ?>">
            </div>

            <div>
                <label>Year Level</label>
                <input type="text" name="year_level" value="<?= $user['year_level'] ?? '' ?>">

                <label>Age</label>
                <input type="number" name="age" value="<?= $user['age'] ?? '' ?>">

                <label>Gender</label>
                <input type="text" name="gender" value="<?= $user['gender'] ?? '' ?>">

                <label>Birthday</label>
                <input type="date" name="birthday" value="<?= $user['birthday'] ?? '' ?>">
            </div>

        </div>

        <button class="btn" name="update_profile">Save Profile</button>
    </form>
</div>


<div class="section">
    <h3>Change Password</h3>

    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required>
        <button class="btn" name="change_password">Update Password</button>
    </form>
</div>

<br>
<a href="logout.php">← Log Out</a>
</div>

</body>
</html>