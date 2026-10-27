<?php
class Lookup {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getRemarks() {
        $sql = "SELECT * FROM harvest_remarks ORDER BY remark_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
