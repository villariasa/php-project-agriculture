<?php
// setup_database.php - Sets up agriculture_system_db and seeds tables
$host     = "localhost";
$port     = "5432";
$dbname   = "agriculture_system_db";
$username = "postgres";
$password = "123456";

try {
    echo "Connecting to PostgreSQL server...\n";
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=postgres", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
    $stmt->execute([$dbname]);

    if (!$stmt->fetch()) {
        $pdo->exec('CREATE DATABASE "' . $dbname . '"');
        echo ">>> Database '$dbname' created successfully.\n";
    } else {
        echo ">>> Database '$dbname' already exists.\n";
    }

    $pdo = null;

    // Connect to the project database
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Execute schema.sql
    echo "Executing database/schema.sql...\n";
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    $pdo->exec($sql);

    echo ">>> Schema and seed data successfully loaded into '$dbname'!\n\n";

    // Summary verification
    echo "========================================\n";
    echo "        DATABASE RECORD SUMMARY         \n";
    echo "========================================\n";
    $tables = ['users', 'farmer', 'farm', 'crop', 'planting', 'harvest_remarks', 'harvest'];
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        printf("%-20s: %d records\n", ucfirst($t), $count);
    }
    echo "========================================\n";
    echo "Setup Complete! Default Logins:\n";
    echo "  • Admin: username='admin' | password='admin123'\n";
    echo "  • Staff: username='staff' | password='staff123'\n";
    echo "========================================\n\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
