<?php
// POST-only handler — the Edit User form lives in the slider on index.php (?edit=<id>).
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

$pdo       = (new Database())->connect();
$userModel = new User($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = (int) ($_GET["id"] ?? 0);
    header("Location: index.php" . ($id > 0 ? "?edit={$id}" : ""));
    exit;
}

$user_id = (int) ($_POST["id"] ?? 0);
$user    = $userModel->getById($user_id);
if (!$user) {
    header("Location: index.php?error=" . urlencode("User not found."));
    exit;
}

$full_name = (string) trim($_POST["full_name"]);
$role      = (string) trim($_POST["role"]);
$password  = (string) ($_POST["password"] ?? "");

if ($full_name === "") {
    header("Location: index.php?edit={$user_id}&error=" . urlencode("Full name is required."));
    exit;
}
if (!in_array($role, ["admin", "staff"], true)) {
    header("Location: index.php?edit={$user_id}&error=" . urlencode("Invalid role."));
    exit;
}
if ($password !== "" && strlen($password) < 6) {
    header("Location: index.php?edit={$user_id}&error=" . urlencode("Password must be at least 6 characters."));
    exit;
}
if ($user['role'] === 'admin' && $role === 'staff' && $userModel->countActiveAdmins() <= 1) {
    header("Location: index.php?edit={$user_id}&error=" . urlencode("Cannot demote the last active administrator."));
    exit;
}

$userModel->update($user_id, $full_name, $role, $password);
logActivity($pdo, "update", "user", $user_id, "Updated user " . $user['username']);
header("Location: index.php?msg=" . urlencode("User updated successfully."));
exit;
