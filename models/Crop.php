<?php
class Crop {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT
                    c.*,
                    cc.category_name,
                    s.season_name
                FROM crop c
                JOIN crop_categories cc ON c.category_id = cc.category_id
                JOIN seasons s ON c.season_id = s.season_id
                WHERE c.is_deleted = FALSE
                ORDER BY c.crop_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $crop_id) {
        $sql = "SELECT * FROM crop WHERE crop_id = :crop_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["crop_id" => $crop_id]);
        return $stmt->fetch();
    }

    public function create(string $crop_name, string $variety, int $category_id, int $growing_period_days, int $season_id) {
        $sql = "INSERT INTO crop (crop_name, variety, category_id, growing_period_days, season_id)
                VALUES (:crop_name, :variety, :category_id, :growing_period_days, :season_id)
                RETURNING crop_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "crop_name"           => $crop_name,
            "variety"             => $variety,
            "category_id"         => $category_id,
            "growing_period_days" => $growing_period_days,
            "season_id"           => $season_id
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $crop_id, string $crop_name, string $variety, int $category_id, int $growing_period_days, int $season_id) {
        $sql = "UPDATE crop
                SET crop_name = :crop_name, variety = :variety, category_id = :category_id,
                    growing_period_days = :growing_period_days, season_id = :season_id, updated_at = NOW()
                WHERE crop_id = :crop_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "crop_name"           => $crop_name,
            "variety"             => $variety,
            "category_id"         => $category_id,
            "growing_period_days" => $growing_period_days,
            "season_id"           => $season_id,
            "crop_id"             => $crop_id
        ]);
    }

    // Soft delete: flag the record instead of removing it
    public function delete(int $crop_id) {
        $sql = "UPDATE crop SET is_deleted = TRUE, updated_at = NOW() WHERE crop_id = :crop_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["crop_id" => $crop_id]);
    }

    // TRUE when the crop is still used by active plantings (blocks deletion)
    public function hasActivePlantings(int $crop_id) {
        $sql = "SELECT COUNT(*) FROM planting WHERE crop_id = :crop_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["crop_id" => $crop_id]);
        return $stmt->fetchColumn() > 0;
    }
}
