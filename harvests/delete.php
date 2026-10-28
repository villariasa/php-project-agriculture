<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Harvest.php";

// Only Admin can delete
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Only administrators can delete records."));
    exit;
}

$harvest_id = (int) ($_GET["id"] ?? 0);

$pdo          = (new Database())->connect();
$harvestModel = new Harvest($pdo);

$harvestModel->delete($harvest_id); // soft delete (is_deleted flag)
header("Location: index.php?msg=" . urlencode("Harvest record deleted successfully."));
exit;
