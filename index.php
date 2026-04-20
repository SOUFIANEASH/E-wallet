<?php
require_once __DIR__ . '/config/auth.php';
if (isLoggedIn()) {
    header('Location: /ewallet/dashboard.php');
} else {
    header('Location: /ewallet/auth/login.php');
}
exit;
