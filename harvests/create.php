<?php
// POST-only handler — the Add Harvest form lives in the side card on index.php.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Harvest.php";
require_once "../models/Planting.php";

$pdo           = (new Database())->connect();
$harvestModel  = new Harvest($pdo);
$plantingModel = new Planting($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?add=1");
    exit;
}

$planting_id          = (int) $_POST["planting_id"];
$harvest_date         = (string) trim($_POST["harvest_date"]);
$quantity_kg          = (float) $_POST["quantity_kg"];
$selling_price_per_kg = (float) $_POST["selling_price_per_kg"];
$grade_id             = (int) $_POST["grade_id"];
$remark_id            = (int) $_POST["remark_id"];

if ($planting_id <= 0 || $harvest_date === "" || $quantity_kg <= 0 || $selling_price_per_kg <= 0 || $grade_id <= 0 || $remark_id <= 0) {
    header("Location: index.php?add=1&error=" . urlencode("All fields are required and amounts must be greater than zero."));
    exit;
}

$harvest_id = $harvestModel->create($planting_id, $harvest_date, $quantity_kg, $selling_price_per_kg, $grade_id, $remark_id);
// Mark the related planting as Harvested
$plantingModel->updateStatusByName($planting_id, "Harvested");
logActivity($pdo, "create", "harvest", (int) $harvest_id, "Added harvest of {$quantity_kg} kg");
header("Location: index.php?msg=" . urlencode("Harvest record added successfully."));
exit;
