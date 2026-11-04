<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/User.php";

// User management is an Admin-only module
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can manage users."));
    exit;
}

$user_id = (int) ($_GET["id"] ?? 0);

$pdo       = (new Database())->connect();
$userModel = new User($pdo);

// You cannot deactivate your own account
if ($user_id === (int) $_SESSION['user_id']) {
    header("Location: index.php?error=" . urlencode("You cannot deactivate your own account."));
    exit;
}

$user = $userModel->getById($user_id);
if (!$user) {
    header("Location: index.php?error=" . urlencode("User not found."));
    exit;
}

// The system must always keep at least one active admin
if ($user['role'] === 'admin' && $userModel->countActiveAdmins() <= 1) {
    header("Location: index.php?error=" . urlencode("Cannot deactivate the last active administrator."));
    exit;
}

$userModel->delete($user_id); // soft delete (is_deleted flag) — blocks login
logActivity($pdo, "delete", "user", $user_id, "Deactivated user " . $user['username']);
header("Location: index.php?msg=" . urlencode("User deactivated successfully."));
exit;
