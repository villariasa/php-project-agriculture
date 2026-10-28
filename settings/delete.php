<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Lookup.php";

// Settings is an Admin-only module
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Only administrators can access Settings."));
    exit;
}

$pdo    = (new Database())->connect();
$lookup = new Lookup($pdo);

$type = (string) ($_GET["type"] ?? "");
$id   = (int) ($_GET["id"] ?? 0);

if (!$lookup->isValidType($type)) {
    header("Location: index.php?error=" . urlencode("Invalid lookup type."));
    exit;
}

// Block deletion while active records still use this value
if ($lookup->isInUse($type, $id)) {
    header("Location: index.php?type={$type}&error=" . urlencode("Cannot delete this value because it is still used by active records."));
    exit;
}

$lookup->delete($type, $id); // soft delete (is_deleted flag)
header("Location: index.php?type={$type}&msg=" . urlencode("Value deleted successfully."));
exit;
