<?php

session_start();

$_SESSION = [];

// remove session cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// remove remember me cookies
setcookie(
    "remember_token",
    "",
    time() - 3600,
    "/"
);
setcookie(
    "remember_username",
    "",
    time() - 3600,
    "/"
);

session_destroy();

header("Location: login.php");
exit;
?>