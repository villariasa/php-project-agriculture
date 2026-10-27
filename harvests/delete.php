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

try {
    $harvestModel->delete($harvest_id);
    header("Location: index.php?msg=" . urlencode("Harvest record deleted successfully."));
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Unable to delete this harvest record."));
}
exit;
