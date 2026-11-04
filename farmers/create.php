<?php
// POST-only handler — the Add Farmer form lives in the side card on index.php.
$base = "../";
require_once "../includes/auth_check.php";
require_once "../includes/activity_log.php";
require_once "../config/Database.php";
require_once "../models/Farmer.php";

$pdo         = (new Database())->connect();
$farmerModel = new Farmer($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?add=1");
    exit;
}

$first_name     = (string) trim($_POST["first_name"]);
$last_name      = (string) trim($_POST["last_name"]);
$contact_number = (string) trim($_POST["contact_number"]);
$address        = (string) trim($_POST["address"]);

if ($first_name === "" || $last_name === "" || $contact_number === "" || $address === "") {
    header("Location: index.php?add=1&error=" . urlencode("All fields are required."));
    exit;
}

$farmer_id = $farmerModel->create($first_name, $last_name, $contact_number, $address);
logActivity($pdo, "create", "farmer", (int) $farmer_id, "Added farmer {$first_name} {$last_name}");
header("Location: index.php?msg=" . urlencode("Farmer added successfully."));
exit;
