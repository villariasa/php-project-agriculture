<?php
class Crop {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT * FROM crop ORDER BY crop_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $crop_id) {
        $sql = "SELECT * FROM crop WHERE crop_id = :crop_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["crop_id" => $crop_id]);
        return $stmt->fetch();
    }

    public function create(string $crop_name, string $variety, string $crop_category, int $growing_period_days, string $recommended_season) {
        $sql = "INSERT INTO crop (crop_name, variety, crop_category, growing_period_days, recommended_season)
                VALUES (:crop_name, :variety, :crop_category, :growing_period_days, :recommended_season)
                RETURNING crop_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "crop_name"           => $crop_name,
            "variety"             => $variety,
            "crop_category"       => $crop_category,
            "growing_period_days" => $growing_period_days,
            "recommended_season"  => $recommended_season
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $crop_id, string $crop_name, string $variety, string $crop_category, int $growing_period_days, string $recommended_season) {
        $sql = "UPDATE crop
                SET crop_name = :crop_name, variety = :variety, crop_category = :crop_category,
                    growing_period_days = :growing_period_days, recommended_season = :recommended_season
                WHERE crop_id = :crop_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "crop_name"           => $crop_name,
            "variety"             => $variety,
            "crop_category"       => $crop_category,
            "growing_period_days" => $growing_period_days,
            "recommended_season"  => $recommended_season,
            "crop_id"             => $crop_id
        ]);
    }

    public function delete(int $crop_id) {
        $sql = "DELETE FROM crop WHERE crop_id = :crop_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["crop_id" => $crop_id]);
    }
}
