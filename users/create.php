<?php
// POST-only handler — the Add User form lives in the side card on index.php.
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
    header("Location: index.php?add=1");
    exit;
}

$username  = (string) trim($_POST["username"]);
$full_name = (string) trim($_POST["full_name"]);
$password  = (string) ($_POST["password"] ?? "");
$role      = (string) trim($_POST["role"]);

if ($username === "" || $full_name === "" || $password === "") {
    header("Location: index.php?add=1&error=" . urlencode("All fields are required."));
    exit;
}
if (strlen($password) < 6) {
    header("Location: index.php?add=1&error=" . urlencode("Password must be at least 6 characters."));
    exit;
}
if (!in_array($role, ["admin", "staff"], true)) {
    header("Location: index.php?add=1&error=" . urlencode("Invalid role."));
    exit;
}
if ($userModel->usernameExists($username)) {
    header("Location: index.php?add=1&error=" . urlencode("That username is already taken."));
    exit;
}

$user_id = $userModel->create($username, $password, $full_name, $role);
logActivity($pdo, "create", "user", (int) $user_id, "Added user {$username} ({$role})");
header("Location: index.php?msg=" . urlencode("User added successfully."));
exit;
