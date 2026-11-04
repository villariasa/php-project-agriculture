<?php
// POST-only handler — the Edit Harvest form lives in the slider on index.php.
// Saves via fetch() (JSON, no reload) with a redirect fallback.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../includes/form_response.php";
require_once "../config/Database.php";
require_once "../models/Harvest.php";

$pdo          = (new Database())->connect();
$harvestModel = new Harvest($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = (int) ($_GET["id"] ?? 0);
    header("Location: index.php" . ($id > 0 ? "?edit={$id}" : ""));
    exit;
}

$harvest_id = (int) ($_POST["id"] ?? 0);
if (!$harvestModel->getById($harvest_id)) {
    respondAndExit(false, "Harvest record not found.", "index.php?error=" . urlencode("Harvest record not found."));
}

$planting_id          = (int) $_POST["planting_id"];
$harvest_date         = (string) trim($_POST["harvest_date"]);
$quantity_kg          = (float) $_POST["quantity_kg"];
$selling_price_per_kg = (float) $_POST["selling_price_per_kg"];
$grade_id             = (int) $_POST["grade_id"];
$remark_id            = (int) $_POST["remark_id"];

if ($planting_id <= 0 || $harvest_date === "" || $quantity_kg <= 0 || $selling_price_per_kg <= 0 || $grade_id <= 0 || $remark_id <= 0) {
    respondAndExit(false, "All fields are required and amounts must be greater than zero.",
        "index.php?edit={$harvest_id}&error=" . urlencode("All fields are required and amounts must be greater than zero."));
}

$harvestModel->update($harvest_id, $planting_id, $harvest_date, $quantity_kg, $selling_price_per_kg, $grade_id, $remark_id);
logActivity($pdo, "update", "harvest", $harvest_id, "Updated harvest of {$quantity_kg} kg");
respondAndExit(true, "Harvest record updated successfully.",
    "index.php?msg=" . urlencode("Harvest record updated successfully."));
