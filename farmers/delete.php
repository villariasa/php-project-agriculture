<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Farmer.php";

// Only Admin can delete
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Only administrators can delete records."));
    exit;
}

$farmer_id = (int) ($_GET["id"] ?? 0);

$pdo         = (new Database())->connect();
$farmerModel = new Farmer($pdo);

// Referential integrity: block deletion while active farms belong to this farmer
if ($farmerModel->hasActiveFarms($farmer_id)) {
    header("Location: index.php?error=" . urlencode("Cannot delete this farmer because they still own active farms."));
    exit;
}

$farmerModel->delete($farmer_id); // soft delete (is_deleted flag)
header("Location: index.php?msg=" . urlencode("Farmer deleted successfully."));
exit;
