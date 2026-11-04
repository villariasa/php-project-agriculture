<?php
// POST-only handler — the Edit Crop form lives in the slider on index.php (?edit=<id>).
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Crop.php";

$pdo       = (new Database())->connect();
$cropModel = new Crop($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = (int) ($_GET["id"] ?? 0);
    header("Location: index.php" . ($id > 0 ? "?edit={$id}" : ""));
    exit;
}

$crop_id = (int) ($_POST["id"] ?? 0);
if (!$cropModel->getById($crop_id)) {
    header("Location: index.php?error=" . urlencode("Crop not found."));
    exit;
}

$crop_name           = (string) trim($_POST["crop_name"]);
$variety             = (string) trim($_POST["variety"]);
$category_id         = (int) $_POST["category_id"];
$growing_period_days = (int) $_POST["growing_period_days"];
$season_id           = (int) $_POST["season_id"];

if ($crop_name === "" || $variety === "" || $category_id <= 0 || $growing_period_days <= 0 || $season_id <= 0) {
    header("Location: index.php?edit={$crop_id}&error=" . urlencode("All fields are required and growing period must be greater than zero."));
    exit;
}

$cropModel->update($crop_id, $crop_name, $variety, $category_id, $growing_period_days, $season_id);
logActivity($pdo, "update", "crop", $crop_id, "Updated crop {$crop_name} ({$variety})");
header("Location: index.php?msg=" . urlencode("Crop updated successfully."));
exit;
