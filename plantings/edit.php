<?php
// POST-only handler — the Edit Planting form lives in the slider on index.php (?edit=<id>).
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Planting.php";

$pdo           = (new Database())->connect();
$plantingModel = new Planting($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = (int) ($_GET["id"] ?? 0);
    header("Location: index.php" . ($id > 0 ? "?edit={$id}" : ""));
    exit;
}

$planting_id = (int) ($_POST["id"] ?? 0);
if (!$plantingModel->getById($planting_id)) {
    header("Location: index.php?error=" . urlencode("Planting record not found."));
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
    header("Location: index.php?edit={$planting_id}&error=" . urlencode("All fields are required and amounts must be greater than zero."));
    exit;
}

if ($expected_harvest_date <= $planting_date) {
    header("Location: index.php?edit={$planting_id}&error=" . urlencode("Expected harvest date must be after the planting date."));
    exit;
}

$plantingModel->update($planting_id, $farm_id, $crop_id, $planting_date, $area_planted, $seed_quantity, $expected_harvest_date, $status_id);
logActivity($pdo, "update", "planting", $planting_id, "Updated planting dated {$planting_date}");
header("Location: index.php?msg=" . urlencode("Planting record updated successfully."));
exit;
