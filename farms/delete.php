<?php
$base = "../";
require_once "../includes/auth_check.php";
require_once "../config/Database.php";
require_once "../models/Farm.php";

// Only Admin can delete
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Only administrators can delete records."));
    exit;
}

$farm_id = (int) ($_GET["id"] ?? 0);

$pdo       = (new Database())->connect();
$farmModel = new Farm($pdo);

try {
    $farmModel->delete($farm_id);
    header("Location: index.php?msg=" . urlencode("Farm deleted successfully."));
} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("Cannot delete this farm because it still has planting records."));
}
exit;
