<?php
// ── Session helper ───────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /ewallet/auth/login.php');
        exit;
    }
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
