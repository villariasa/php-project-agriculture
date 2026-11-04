<?php
// POST-only handler — the Edit Farmer form lives in the slider on index.php (?edit=<id>).
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Farmer.php";

$pdo         = (new Database())->connect();
$farmerModel = new Farmer($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = (int) ($_GET["id"] ?? 0);
    header("Location: index.php" . ($id > 0 ? "?edit={$id}" : ""));
    exit;
}

$farmer_id = (int) ($_POST["id"] ?? 0);
if (!$farmerModel->getById($farmer_id)) {
    header("Location: index.php?error=" . urlencode("Farmer not found."));
    exit;
}

$first_name     = (string) trim($_POST["first_name"]);
$last_name      = (string) trim($_POST["last_name"]);
$contact_number = (string) trim($_POST["contact_number"]);
$address        = (string) trim($_POST["address"]);

if ($first_name === "" || $last_name === "" || $contact_number === "" || $address === "") {
    header("Location: index.php?edit={$farmer_id}&error=" . urlencode("All fields are required."));
    exit;
}

$farmerModel->update($farmer_id, $first_name, $last_name, $contact_number, $address);
logActivity($pdo, "update", "farmer", $farmer_id, "Updated farmer {$first_name} {$last_name}");
header("Location: index.php?msg=" . urlencode("Farmer updated successfully."));
exit;
