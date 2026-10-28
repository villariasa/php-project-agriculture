<?php
class Harvest {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT
                    h.harvest_id,
                    h.planting_id,
                    f.farm_name,
                    c.crop_name,
                    c.variety,
                    h.harvest_date,
                    h.quantity_kg,
                    h.selling_price_per_kg,
                    qg.grade_name AS quality_grade,
                    r.remark_description
                FROM harvest h
                JOIN planting p ON h.planting_id = p.planting_id
                JOIN farm f ON p.farm_id = f.farm_id
                JOIN crop c ON p.crop_id = c.crop_id
                JOIN quality_grades qg ON h.grade_id = qg.grade_id
                JOIN harvest_remarks r ON h.remark_id = r.remark_id
                WHERE h.is_deleted = FALSE
                ORDER BY h.harvest_id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $harvest_id) {
        $sql = "SELECT * FROM harvest WHERE harvest_id = :harvest_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["harvest_id" => $harvest_id]);
        return $stmt->fetch();
    }

    public function create(int $planting_id, string $harvest_date, float $quantity_kg, float $selling_price_per_kg, int $grade_id, int $remark_id) {
        $sql = "INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id)
                VALUES (:planting_id, :harvest_date, :quantity_kg, :selling_price_per_kg, :grade_id, :remark_id)
                RETURNING harvest_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "planting_id"          => $planting_id,
            "harvest_date"         => $harvest_date,
            "quantity_kg"          => $quantity_kg,
            "selling_price_per_kg" => $selling_price_per_kg,
            "grade_id"             => $grade_id,
            "remark_id"            => $remark_id
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $harvest_id, int $planting_id, string $harvest_date, float $quantity_kg, float $selling_price_per_kg, int $grade_id, int $remark_id) {
        $sql = "UPDATE harvest
                SET planting_id = :planting_id, harvest_date = :harvest_date, quantity_kg = :quantity_kg,
                    selling_price_per_kg = :selling_price_per_kg, grade_id = :grade_id, remark_id = :remark_id,
                    updated_at = NOW()
                WHERE harvest_id = :harvest_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "planting_id"          => $planting_id,
            "harvest_date"         => $harvest_date,
            "quantity_kg"          => $quantity_kg,
            "selling_price_per_kg" => $selling_price_per_kg,
            "grade_id"             => $grade_id,
            "remark_id"            => $remark_id,
            "harvest_id"           => $harvest_id
        ]);
    }

    // Soft delete: flag the record instead of removing it
    public function delete(int $harvest_id) {
        $sql = "UPDATE harvest SET is_deleted = TRUE, updated_at = NOW() WHERE harvest_id = :harvest_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["harvest_id" => $harvest_id]);
    }
}
