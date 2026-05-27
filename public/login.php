<?php
require_once __DIR__ . '/../app/Database.php';

session_start();

$db = new Database();
$pdo = $db->pdo;

$error = "";

/* AUTO LOGIN (REMEMBER ME) */
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {

    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['user_name'];

        header("Location: index.php");
        exit;
    }
}

/* LOGIN PROCESS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_name = trim($_POST['username']); // form stays "username"
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_name = ?");
    $stmt->execute([$user_name]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['user_name'];

        /* REMEMBER ME */
        if (isset($_POST['remember'])) {

            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                UPDATE users 
                SET remember_token = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$token, $user['user_id']]);

            setcookie(
                "remember_token",
                $token,
                time() + (60 * 60 * 24 * 7), // 7 days
                "/",
                "",
                false,
                true
            );
        }

        header("Location: index.php");
        exit;

    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../assets/login.css">
</head>

<body>

<div class="container">

    <div class="box-2">

        <form method="post">

            <h2>Login</h2>

            <input 
                type="text" 
                name="username" 
                placeholder="Username" 
                required
            >

            <input 
                type="password" 
                name="password" 
                placeholder="Password" 
                required
            >

            <?php if (!empty($error)) : ?>
                <p style="color:red;">
                    <?php echo $error; ?>
                </p>
            <?php endif; ?>

           

            <button type="submit">
                Login
            </button>
             <label>
                <div class="class-1">
                <input type="checkbox" name="remember">
                <div class="test">Remember Me</div>
                </div>
            </label>

            <div class="txt-2">
                Don’t have an account yet?
                <a href="register.php">Register here</a>
            </div>

        </form>

    </div>

    <div class="box-1">

        <div class="txt-sm">
            Welcome to
        </div>

        <div class="txt-1">
            IskoHub
        </div>

        <div class="txt-sm">
            A student-friendly marketplace where you can buy, sell,
            and discover affordable items, services, and essentials
            within your campus community.
        </div>

    </div>

</div>

</body>
</html>