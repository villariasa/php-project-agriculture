<?php
class Planting {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(string $status = "") {
        $sql = "SELECT
                    p.planting_id,
                    p.farm_id,
                    p.crop_id,
                    f.farm_name,
                    c.crop_name,
                    c.variety,
                    fm.first_name || ' ' || fm.last_name AS farmer_name,
                    p.planting_date,
                    p.area_planted,
                    p.seed_quantity,
                    p.expected_harvest_date,
                    p.status
                FROM planting p
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN farmer fm ON f.farmer_id = fm.farmer_id
                JOIN crop c ON p.crop_id = c.crop_id";
        if ($status !== "") {
            $sql .= " WHERE p.status = :status";
        }
        $sql .= " ORDER BY p.planting_id DESC";

        $stmt = $this->pdo->prepare($sql);
        $status !== "" ? $stmt->execute(["status" => $status]) : $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $planting_id) {
        $sql = "SELECT * FROM planting WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["planting_id" => $planting_id]);
        return $stmt->fetch();
    }

    public function create(int $farm_id, int $crop_id, string $planting_date, float $area_planted, float $seed_quantity, string $expected_harvest_date, string $status) {
        $sql = "INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status)
                VALUES (:farm_id, :crop_id, :planting_date, :area_planted, :seed_quantity, :expected_harvest_date, :status)
                RETURNING planting_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "farm_id"               => $farm_id,
            "crop_id"               => $crop_id,
            "planting_date"         => $planting_date,
            "area_planted"          => $area_planted,
            "seed_quantity"         => $seed_quantity,
            "expected_harvest_date" => $expected_harvest_date,
            "status"                => $status
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $planting_id, int $farm_id, int $crop_id, string $planting_date, float $area_planted, float $seed_quantity, string $expected_harvest_date, string $status) {
        $sql = "UPDATE planting
                SET farm_id = :farm_id, crop_id = :crop_id, planting_date = :planting_date,
                    area_planted = :area_planted, seed_quantity = :seed_quantity,
                    expected_harvest_date = :expected_harvest_date, status = :status
                WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "farm_id"               => $farm_id,
            "crop_id"               => $crop_id,
            "planting_date"         => $planting_date,
            "area_planted"          => $area_planted,
            "seed_quantity"         => $seed_quantity,
            "expected_harvest_date" => $expected_harvest_date,
            "status"                => $status,
            "planting_id"           => $planting_id
        ]);
    }

    public function updateStatus(int $planting_id, string $status) {
        $sql = "UPDATE planting SET status = :status WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "status"      => $status,
            "planting_id" => $planting_id
        ]);
    }

    public function delete(int $planting_id) {
        $sql = "DELETE FROM planting WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["planting_id" => $planting_id]);
    }
}
