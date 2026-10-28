<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Planting.php";

// Only Admin can delete
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Only administrators can delete records."));
    exit;
}

$planting_id = (int) ($_GET["id"] ?? 0);

$pdo           = (new Database())->connect();
$plantingModel = new Planting($pdo);

// Referential integrity: block deletion while active harvests reference this planting
if ($plantingModel->hasActiveHarvests($planting_id)) {
    header("Location: index.php?error=" . urlencode("Cannot delete this planting because it still has active harvest records."));
    exit;
}

$plantingModel->delete($planting_id); // soft delete (is_deleted flag)
header("Location: index.php?msg=" . urlencode("Planting record deleted successfully."));
exit;
