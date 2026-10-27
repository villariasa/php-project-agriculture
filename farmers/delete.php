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

try {
    $farmerModel->delete($farmer_id);
    header("Location: index.php?msg=" . urlencode("Farmer deleted successfully."));
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Cannot delete this farmer because they still own farms."));
}
exit;
