<?php
// POST-only handler — the Edit Farm form lives in the slider on index.php (?edit=<id>).
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Farm.php";

$pdo       = (new Database())->connect();
$farmModel = new Farm($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = (int) ($_GET["id"] ?? 0);
    header("Location: index.php" . ($id > 0 ? "?edit={$id}" : ""));
    exit;
}

$farm_id = (int) ($_POST["id"] ?? 0);
if (!$farmModel->getById($farm_id)) {
    header("Location: index.php?error=" . urlencode("Farm not found."));
    exit;
}

$farmer_id          = (int) $_POST["farmer_id"];
$farm_name          = (string) trim($_POST["farm_name"]);
$location           = (string) trim($_POST["location"]);
$area_hectares      = (float) $_POST["area_hectares"];
$soil_type_id       = (int) $_POST["soil_type_id"];
$irrigation_type_id = (int) $_POST["irrigation_type_id"];

if ($farmer_id <= 0 || $farm_name === "" || $location === "" || $area_hectares <= 0 || $soil_type_id <= 0 || $irrigation_type_id <= 0) {
    header("Location: index.php?edit={$farm_id}&error=" . urlencode("All fields are required and area must be greater than zero."));
    exit;
}

$farmModel->update($farm_id, $farmer_id, $farm_name, $location, $area_hectares, $soil_type_id, $irrigation_type_id);
logActivity($pdo, "update", "farm", $farm_id, "Updated farm {$farm_name}");
header("Location: index.php?msg=" . urlencode("Farm updated successfully."));
exit;
