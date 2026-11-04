<?php
// POST-only handler — the Add Farm form lives in the side card on index.php.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Farm.php";

$pdo       = (new Database())->connect();
$farmModel = new Farm($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?add=1");
    exit;
}

$farmer_id          = (int) $_POST["farmer_id"];
$farm_name          = (string) trim($_POST["farm_name"]);
$location           = (string) trim($_POST["location"]);
$area_hectares      = (float) $_POST["area_hectares"];
$soil_type_id       = (int) $_POST["soil_type_id"];
$irrigation_type_id = (int) $_POST["irrigation_type_id"];

if ($farmer_id <= 0 || $farm_name === "" || $location === "" || $area_hectares <= 0 || $soil_type_id <= 0 || $irrigation_type_id <= 0) {
    header("Location: index.php?add=1&error=" . urlencode("All fields are required and area must be greater than zero."));
    exit;
}

$farm_id = $farmModel->create($farmer_id, $farm_name, $location, $area_hectares, $soil_type_id, $irrigation_type_id);
logActivity($pdo, "create", "farm", (int) $farm_id, "Added farm {$farm_name}");
header("Location: index.php?msg=" . urlencode("Farm added successfully."));
exit;
