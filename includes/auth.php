<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    $user  = getCurrentUser();
    if (!in_array($user['role'], $roles)) {
        header('Location: /dashboard.php?error=unauthorized');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return $_SESSION['user'];
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}
