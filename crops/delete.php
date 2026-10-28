<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Crop.php";

// Only Admin can delete
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Only administrators can delete records."));
    exit;
}

$crop_id = (int) ($_GET["id"] ?? 0);

$pdo       = (new Database())->connect();
$cropModel = new Crop($pdo);

// Referential integrity: block deletion while active plantings use this crop
if ($cropModel->hasActivePlantings($crop_id)) {
    header("Location: index.php?error=" . urlencode("Cannot delete this crop because it is still used in active planting records."));
    exit;
}

$cropModel->delete($crop_id); // soft delete (is_deleted flag)
header("Location: index.php?msg=" . urlencode("Crop deleted successfully."));
exit;
