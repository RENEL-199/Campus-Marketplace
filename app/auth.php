<?php

require_once __DIR__ . '/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DEFAULT_SESSION_TIMEOUT_SECONDS = 30; // 30 seconds

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function is_session_active(int $timeout = DEFAULT_SESSION_TIMEOUT_SECONDS): bool {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }

    return (time() - $_SESSION['last_activity']) <= $timeout;
}

function update_session_activity(): void {
    $_SESSION['last_activity'] = time();
}

function restore_session_from_remember_token(): bool {
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    if (empty($_COOKIE['remember_token'])) {
        return false;
    }

    $db = new Database();
    $stmt = $db->pdo->prepare("SELECT id FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        delete_user_cookie('remember_token');
        return false;
    }

    $_SESSION['user_id'] = $user['id'];
    update_session_activity();
    return true;
}

function clear_session(): void {
    $_SESSION = [];

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

    session_destroy();
}

function require_login(int $timeout = DEFAULT_SESSION_TIMEOUT_SECONDS): void {
    if (!isset($_SESSION['user_id'])) {
        if (!restore_session_from_remember_token()) {
            header("Location: login.php");
            exit;
        }
    } else if (!is_session_active($timeout)) {
        clear_session();
        header("Location: login.php?timeout=1");
        exit;
    }

    update_session_activity();
}

function set_user_cookie(string $name, string $value, int $expireSeconds = 604800, string $path = "/", bool $secure = false, bool $httpOnly = true): void {
    setcookie($name, $value, time() + $expireSeconds, $path, "", $secure, $httpOnly);
}

function get_user_cookie(string $name): ?string {
    return $_COOKIE[$name] ?? null;
}

function delete_user_cookie(string $name, string $path = "/"): void {
    setcookie($name, "", time() - 3600, $path);
    unset($_COOKIE[$name]);
}
