<?php
// POST-only handler — the Edit User form lives in the slider on index.php.
// Saves via fetch() (JSON, no reload) with a redirect fallback.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../includes/form_response.php";
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
    respondAndExit(false, "User not found.", "index.php?error=" . urlencode("User not found."));
}

$full_name = (string) trim($_POST["full_name"]);
$role      = (string) trim($_POST["role"]);
$password  = (string) ($_POST["password"] ?? "");

if ($full_name === "") {
    respondAndExit(false, "Full name is required.",
        "index.php?edit={$user_id}&error=" . urlencode("Full name is required."));
}
if (!in_array($role, ["admin", "staff"], true)) {
    respondAndExit(false, "Invalid role.",
        "index.php?edit={$user_id}&error=" . urlencode("Invalid role."));
}
if ($password !== "" && strlen($password) < 6) {
    respondAndExit(false, "Password must be at least 6 characters.",
        "index.php?edit={$user_id}&error=" . urlencode("Password must be at least 6 characters."));
}
if ($user['role'] === 'admin' && $role === 'staff' && $userModel->countActiveAdmins() <= 1) {
    respondAndExit(false, "Cannot demote the last active administrator.",
        "index.php?edit={$user_id}&error=" . urlencode("Cannot demote the last active administrator."));
}

$userModel->update($user_id, $full_name, $role, $password);
logActivity($pdo, "update", "user", $user_id, "Updated user " . $user['username']);
respondAndExit(true, "User updated successfully.",
    "index.php?msg=" . urlencode("User updated successfully."));
