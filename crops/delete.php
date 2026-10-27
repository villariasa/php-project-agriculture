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

try {
    $cropModel->delete($crop_id);
    header("Location: index.php?msg=" . urlencode("Crop deleted successfully."));
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Cannot delete this crop because it is used in planting records."));
}
exit;
