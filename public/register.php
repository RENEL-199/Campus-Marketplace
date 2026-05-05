<?php
require_once __DIR__ . '/../app/Database.php';

$db = new Database();
$pdo = $db->pdo;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $reenter_password = $_POST['reenter_password'];

    // Check if passwords match
    if ($password !== $reenter_password) {
       
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);

        header("Location: login.php");
        exit;
    }

    $error = "";

if ($password !== $reenter_password) {
    $error = "Passwords do not match!";
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
<div class="txt-1">Campus Market Place</div>
<div class="txt-sm">
A student-friendly marketplace where you can buy, sell, and discover affordable items, services, and essentials within your campus community.
</div>
    </div>



<div class="box-2">
<form method="post">
<h2>Register</h2>
<input type="text" name="username" placeholder="Username" required>
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
