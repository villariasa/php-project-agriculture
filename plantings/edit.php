<?php
// POST-only handler — the Edit Planting form lives in the slider on index.php.
// Saves via fetch() (JSON, no reload) with a redirect fallback.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../includes/form_response.php";
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
    respondAndExit(false, "Planting record not found.", "index.php?error=" . urlencode("Planting record not found."));
}

$farm_id               = (int) $_POST["farm_id"];
$crop_id               = (int) $_POST["crop_id"];
$planting_date         = (string) trim($_POST["planting_date"]);
$area_planted          = (float) $_POST["area_planted"];
$seed_quantity         = (float) $_POST["seed_quantity"];
$expected_harvest_date = (string) trim($_POST["expected_harvest_date"]);
$status_id             = (int) $_POST["status_id"];

if ($farm_id <= 0 || $crop_id <= 0 || $planting_date === "" || $expected_harvest_date === "" || $area_planted <= 0 || $seed_quantity <= 0 || $status_id <= 0) {
    respondAndExit(false, "All fields are required and amounts must be greater than zero.",
        "index.php?edit={$planting_id}&error=" . urlencode("All fields are required and amounts must be greater than zero."));
}

if ($expected_harvest_date <= $planting_date) {
    respondAndExit(false, "Expected harvest date must be after the planting date.",
        "index.php?edit={$planting_id}&error=" . urlencode("Expected harvest date must be after the planting date."));
}

$plantingModel->update($planting_id, $farm_id, $crop_id, $planting_date, $area_planted, $seed_quantity, $expected_harvest_date, $status_id);
logActivity($pdo, "update", "planting", $planting_id, "Updated planting dated {$planting_date}");
respondAndExit(true, "Planting record updated successfully.",
    "index.php?msg=" . urlencode("Planting record updated successfully."));
