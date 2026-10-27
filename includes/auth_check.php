<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $base = $base ?? "";
    header("Location: {$base}auth/login.php");
    exit;
}
