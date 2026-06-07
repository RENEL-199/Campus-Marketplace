<?php
require_once __DIR__ . '/../app/Database.php';

session_start();

$db = new Database();
$pdo = $db->pdo;

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_name = trim($_POST['username'] ?? '');
    $stud_id = trim($_POST['studoid'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $reenter_password = (string)($_POST['reenter_password'] ?? '');

    if ($user_name === '' || $stud_id === '' || $password === '' || $reenter_password === '') {
        $error = 'All fields are required.';
    } elseif (strlen($user_name) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $reenter_password) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare('SELECT 1 FROM users WHERE user_name = ? OR stud_id = ? LIMIT 1');
        $check->execute([$user_name, $stud_id]);

        if ($check->fetchColumn()) {
            $error = 'Username or student ID already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (user_name, stud_id, password) VALUES (?, ?, ?)');
            $stmt->execute([$user_name, $stud_id, $hashedPassword]);

            $_SESSION['registration_success'] = true;
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>

<html>
<head>
<title>Register</title>
<link rel="stylesheet" href="../assets/register.css">
</head>
<body>

<div class="container">

<div class="box-1">
 <div class="txt-sm">Welcome to</div>
<div class="txt-1">IskoHub</div>
<div class="txt-sm">
A student-friendly marketplace where you can buy, sell, and discover affordable items, services, and essentials within your campus community.
</div>
    </div>



<div class="box-2">
<form method="post">
<h2>Register</h2>
<input type="text" name="username" placeholder="Username" required>
<input type="text" name="studoid" placeholder="Student ID" required>
<input type="password" name="password" placeholder="Password" required>
<input type="password" name="reenter_password" placeholder="Re-enter Password" required>
<?php if (!empty($error)) : ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>
<button name="register">Register</button>
<div class="txt-2">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</form>

</div>


</div>
</body>
</html>
