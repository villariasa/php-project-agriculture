<?php
// POST-only handler — the Edit Value form lives in the slider on index.php.
// Saves via fetch() (JSON, no reload) with a redirect fallback.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../includes/form_response.php";
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
    respondAndExit(false, "Invalid lookup type.", "index.php?error=" . urlencode("Invalid lookup type."));
}

$row = $lookup->getById($type, $id);
if (!$row) {
    respondAndExit(false, "Value not found.", "index.php?type={$type}&error=" . urlencode("Value not found."));
}

if ($name === "") {
    respondAndExit(false, "Please enter a value.", "index.php?type={$type}&error=" . urlencode("Please enter a value."));
}

$lookup->update($type, $id, $name);
logActivity($pdo, "update", "lookup", $id, "Renamed {$lookup->getLabel($type)} value to: {$name}");
respondAndExit(true, "Value updated successfully.",
    "index.php?type={$type}&msg=" . urlencode("Value updated successfully."));
