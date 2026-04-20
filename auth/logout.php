<?php
require_once __DIR__ . '/../config/auth.php';
session_destroy();
header('Location: /ewallet/auth/login.php');
exit;
