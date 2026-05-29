<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user_id() {
    $userId = $_SESSION['user_id'] ?? null;
    $userId = is_numeric($userId) ? (int)$userId : null;
    return ($userId && $userId > 0) ? $userId : null;
}

function require_login() {
    if (current_user_id() === null) {
        header("Location: login.php");
        exit;
    }
}