<?php
// POST-only handler — the Add Crop form lives in the side card on index.php.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Crop.php";

$pdo       = (new Database())->connect();
$cropModel = new Crop($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?add=1");
    exit;
}

$crop_name           = (string) trim($_POST["crop_name"]);
$variety             = (string) trim($_POST["variety"]);
$category_id         = (int) $_POST["category_id"];
$growing_period_days = (int) $_POST["growing_period_days"];
$season_id           = (int) $_POST["season_id"];

if ($crop_name === "" || $variety === "" || $category_id <= 0 || $growing_period_days <= 0 || $season_id <= 0) {
    header("Location: index.php?add=1&error=" . urlencode("All fields are required and growing period must be greater than zero."));
    exit;
}

$crop_id = $cropModel->create($crop_name, $variety, $category_id, $growing_period_days, $season_id);
logActivity($pdo, "create", "crop", (int) $crop_id, "Added crop {$crop_name} ({$variety})");
header("Location: index.php?msg=" . urlencode("Crop added successfully."));
exit;
