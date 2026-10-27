<?php
// Template: copy this file to Database.php and fill in your Supabase credentials.
class Database {
    private string $host     = "db.YOUR_SUPABASE_PROJECT.supabase.co";
    private string $port     = "5432";
    private string $dbname   = "postgres";
    private string $username = "postgres";
    private string $password = "YOUR_PASSWORD";

    public function connect(): PDO {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
}
