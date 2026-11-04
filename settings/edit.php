<?php
// POST-only handler — the Edit Value form lives in the side card on index.php.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Lookup.php";

// Settings is an Admin-only module
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can access Settings."));
    exit;
}

$pdo    = (new Database())->connect();
$lookup = new Lookup($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$type = (string) ($_POST["type"] ?? "");
$id   = (int) ($_POST["id"] ?? 0);
$name = (string) trim($_POST["name"] ?? "");

if (!$lookup->isValidType($type)) {
    header("Location: index.php?error=" . urlencode("Invalid lookup type."));
    exit;
}

$row = $lookup->getById($type, $id);
if (!$row) {
    header("Location: index.php?type={$type}&error=" . urlencode("Value not found."));
    exit;
}

if ($name === "") {
    header("Location: index.php?type={$type}&error=" . urlencode("Please enter a value."));
    exit;
}

$lookup->update($type, $id, $name);
logActivity($pdo, "update", "lookup", $id, "Renamed {$lookup->getLabel($type)} value to: {$name}");
header("Location: index.php?type={$type}&msg=" . urlencode("Value updated successfully."));
exit;
