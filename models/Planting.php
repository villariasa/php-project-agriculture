<?php
class Planting {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(int $status_id = 0) {
        $sql = "SELECT
                    p.planting_id,
                    p.farm_id,
                    p.crop_id,
                    p.status_id,
                    f.farm_name,
                    c.crop_name,
                    c.variety,
                    fm.first_name || ' ' || fm.last_name AS farmer_name,
                    p.planting_date,
                    p.area_planted,
                    p.seed_quantity,
                    p.expected_harvest_date,
                    ps.status_name AS status
                FROM planting p
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN farmer fm ON f.farmer_id = fm.farmer_id
                JOIN crop c ON p.crop_id = c.crop_id
                JOIN planting_status ps ON p.status_id = ps.status_id
                WHERE p.is_deleted = FALSE";
        if ($status_id > 0) {
            $sql .= " AND p.status_id = :status_id";
        }
        $sql .= " ORDER BY p.planting_id DESC";

        $stmt = $this->pdo->prepare($sql);
        $status_id > 0 ? $stmt->execute(["status_id" => $status_id]) : $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $planting_id) {
        $sql = "SELECT * FROM planting WHERE planting_id = :planting_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["planting_id" => $planting_id]);
        return $stmt->fetch();
    }

    public function create(int $farm_id, int $crop_id, string $planting_date, float $area_planted, float $seed_quantity, string $expected_harvest_date, int $status_id) {
        $sql = "INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
                VALUES (:farm_id, :crop_id, :planting_date, :area_planted, :seed_quantity, :expected_harvest_date, :status_id)
                RETURNING planting_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "farm_id"               => $farm_id,
            "crop_id"               => $crop_id,
            "planting_date"         => $planting_date,
            "area_planted"          => $area_planted,
            "seed_quantity"         => $seed_quantity,
            "expected_harvest_date" => $expected_harvest_date,
            "status_id"             => $status_id
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $planting_id, int $farm_id, int $crop_id, string $planting_date, float $area_planted, float $seed_quantity, string $expected_harvest_date, int $status_id) {
        $sql = "UPDATE planting
                SET farm_id = :farm_id, crop_id = :crop_id, planting_date = :planting_date,
                    area_planted = :area_planted, seed_quantity = :seed_quantity,
                    expected_harvest_date = :expected_harvest_date, status_id = :status_id, updated_at = NOW()
                WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "farm_id"               => $farm_id,
            "crop_id"               => $crop_id,
            "planting_date"         => $planting_date,
            "area_planted"          => $area_planted,
            "seed_quantity"         => $seed_quantity,
            "expected_harvest_date" => $expected_harvest_date,
            "status_id"             => $status_id,
            "planting_id"           => $planting_id
        ]);
    }

    // Set status by its lookup name (e.g. mark as Harvested after recording a harvest)
    public function updateStatusByName(int $planting_id, string $status_name) {
        $sql = "UPDATE planting
                SET status_id = (SELECT status_id FROM planting_status WHERE status_name = :status_name),
                    updated_at = NOW()
                WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "status_name" => $status_name,
            "planting_id" => $planting_id
        ]);
    }

    // Soft delete: flag the record instead of removing it
    public function delete(int $planting_id) {
        $sql = "UPDATE planting SET is_deleted = TRUE, updated_at = NOW() WHERE planting_id = :planting_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["planting_id" => $planting_id]);
    }

    // TRUE when the planting still has active harvests (blocks deletion)
    public function hasActiveHarvests(int $planting_id) {
        $sql = "SELECT COUNT(*) FROM harvest WHERE planting_id = :planting_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["planting_id" => $planting_id]);
        return $stmt->fetchColumn() > 0;
    }
}
