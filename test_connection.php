<?php
// test_connection.php - Run in Sublime with Ctrl + B to verify database connection
require_once __DIR__ . '/config/Database.php';

echo "Testing Database Connection...\n";

$db = new Database();
$pdo = $db->connect();

echo ">>> SUCCESS: Connected to PostgreSQL database!\n";

$tables = ['users', 'farmer', 'farm', 'crop', 'planting', 'harvest_remarks', 'harvest'];
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "  • " . str_pad(ucfirst($t), 18) . ": " . $count . " rows\n";
}

echo "\nReady to run with: php -S localhost:8000\n";
