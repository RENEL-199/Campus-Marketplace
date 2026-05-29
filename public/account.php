<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['profile'])) {
    $_SESSION['profile'] = [
        'full_name' => '',
        'username' => $_SESSION['username'] ?? '',
        'student_id' => '',
        'address' => '',
        'contact_number' => '',
        'email' => '',
        'birthday' => '',
        'gender' => '',
        'profile_picture' => ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['profile']['full_name'] = $_POST['full_name'] ?? '';
    $_SESSION['profile']['username'] = $_POST['username'] ?? '';
    $_SESSION['profile']['student_id'] = $_POST['student_id'] ?? '';
    $_SESSION['profile']['address'] = $_POST['address'] ?? '';
    $_SESSION['profile']['contact_number'] = $_POST['contact_number'] ?? '';
    $_SESSION['profile']['email'] = $_POST['email'] ?? '';
    $_SESSION['profile']['birthday'] = $_POST['birthday'] ?? '';
    $_SESSION['profile']['gender'] = $_POST['gender'] ?? '';

    if (!empty($_FILES['profile_picture']['name'])) {
        $uploadDir = __DIR__ . '/uploads/profile/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['profile_picture']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
            $_SESSION['profile']['profile_picture'] = 'uploads/profile/' . $fileName;
        }
    }

    header("Location: profile.php");
    exit;
}

$profile = $_SESSION['profile'];

$displayName = !empty($profile['full_name']) ? $profile['full_name'] : 'User Name';
$studentNo = !empty($profile['student_id']) ? $profile['student_id'] : 'Student No.';
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial;
    background: #eef1ef;
}

nav {
    height: 58px;
    background: #810C01;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 26px;
}

nav h1 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 18px;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 12px;
}

nav i {
    margin-right: 4px;
    font-size: 13px;
}

.profile-container {
    width: 920px;
    min-height: 620px;
    background: white;
    margin: 26px auto;
    border-radius: 18px;
    padding: 18px 65px 55px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.25);
}

.profile-top {
    text-align: center;
    margin-bottom: 25px;
}

.profile-pic {
    width: 96px;
    height: 96px;
    background: #d9d9d9;
    border-radius: 50%;
    margin: 0 auto 12px;
    object-fit: cover;
    display: block;
}

.profile-top h3 {
    margin: 0 0 6px;
    font-size: 13px;
}

.profile-top p {
    margin: 0;
    font-size: 13px;
    font-weight: bold;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 150px;
    row-gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: span 2;
}

label {
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 6px;
}

input, select {
    height: 34px;
    border: 1px solid #aaa;
    border-radius: 5px;
    padding: 6px 10px;
    font-size: 13px;
}

.btn-update {
    grid-column: span 2;
    margin-top: 25px;
    height: 35px;
    border: none;
    border-radius: 5px;
    background: #990b00;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-update:hover {
    background: #810C01;
}
</style>
</head>

<body>

<nav>
    <h1>IskoHub</h1>

    <div class="nav-links">
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <a href="orders.php"><i class="fa-solid fa-box"></i> Order History</a>
        <a href="seller_dashboard.php"><i class="fa-solid fa-dollar-sign"></i> Sell</a>
        <a href="profile.php"><i class="fa-solid fa-user"></i></a>
    </div>
</nav>

<div class="profile-container">

    <div class="profile-top">
        <?php if (!empty($profile['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($profile['profile_picture']); ?>" class="profile-pic">
        <?php else: ?>
            <div class="profile-pic"></div>
        <?php endif; ?>

        <h3><?php echo htmlspecialchars($displayName); ?></h3>
        <p><?php echo htmlspecialchars($studentNo); ?></p>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>">
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($profile['username']); ?>">
            </div>

            <div class="form-group">
                <label>Change Profile Picture</label>
                <input type="file" name="profile_picture" accept="image/*">
            </div>

            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($profile['student_id']); ?>">
            </div>

            <div class="form-group full">
                <label>Address</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>">
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="<?php echo htmlspecialchars($profile['contact_number']); ?>">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>">
            </div>

            <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="birthday" value="<?php echo htmlspecialchars($profile['birthday']); ?>">
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value=""></option>
                    <option value="Female" <?php if ($profile['gender'] === 'Female') echo 'selected'; ?>>Female</option>
                    <option value="Male" <?php if ($profile['gender'] === 'Male') echo 'selected'; ?>>Male</option>
                </select>
            </div>

            <button type="submit" class="btn-update">Update Profile</button>

        </div>
    </form>

</div>

</body>
</html>