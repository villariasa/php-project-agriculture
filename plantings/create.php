<?php
// POST-only handler — the Add Planting form lives in the side card on index.php.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Planting.php";

$pdo           = (new Database())->connect();
$plantingModel = new Planting($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?add=1");
    exit;
}

$farm_id               = (int) $_POST["farm_id"];
$crop_id               = (int) $_POST["crop_id"];
$planting_date         = (string) trim($_POST["planting_date"]);
$area_planted          = (float) $_POST["area_planted"];
$seed_quantity         = (float) $_POST["seed_quantity"];
$expected_harvest_date = (string) trim($_POST["expected_harvest_date"]);
$status_id             = (int) $_POST["status_id"];

if ($farm_id <= 0 || $crop_id <= 0 || $planting_date === "" || $expected_harvest_date === "" || $area_planted <= 0 || $seed_quantity <= 0 || $status_id <= 0) {
    header("Location: index.php?add=1&error=" . urlencode("All fields are required and amounts must be greater than zero."));
    exit;
}

if ($expected_harvest_date <= $planting_date) {
    header("Location: index.php?add=1&error=" . urlencode("Expected harvest date must be after the planting date."));
    exit;
}

$planting_id = $plantingModel->create($farm_id, $crop_id, $planting_date, $area_planted, $seed_quantity, $expected_harvest_date, $status_id);
logActivity($pdo, "create", "planting", (int) $planting_id, "Added planting dated {$planting_date}");
header("Location: index.php?msg=" . urlencode("Planting record added successfully."));
exit;
