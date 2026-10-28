<?php
class Farm {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT
                    f.*,
                    fm.first_name || ' ' || fm.last_name AS owner_name,
                    st.soil_type_name,
                    it.irrigation_type_name
                FROM farm f
                JOIN farmer fm ON f.farmer_id = fm.farmer_id
                JOIN soil_types st ON f.soil_type_id = st.soil_type_id
                JOIN irrigation_types it ON f.irrigation_type_id = it.irrigation_type_id
                WHERE f.is_deleted = FALSE
                ORDER BY f.farm_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $farm_id) {
        $sql = "SELECT * FROM farm WHERE farm_id = :farm_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farm_id" => $farm_id]);
        return $stmt->fetch();
    }

    public function create(int $farmer_id, string $farm_name, string $location, float $area_hectares, int $soil_type_id, int $irrigation_type_id) {
        $sql = "INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
                VALUES (:farmer_id, :farm_name, :location, :area_hectares, :soil_type_id, :irrigation_type_id)
                RETURNING farm_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "farmer_id"          => $farmer_id,
            "farm_name"          => $farm_name,
            "location"           => $location,
            "area_hectares"      => $area_hectares,
            "soil_type_id"       => $soil_type_id,
            "irrigation_type_id" => $irrigation_type_id
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $farm_id, int $farmer_id, string $farm_name, string $location, float $area_hectares, int $soil_type_id, int $irrigation_type_id) {
        $sql = "UPDATE farm
                SET farmer_id = :farmer_id, farm_name = :farm_name, location = :location,
                    area_hectares = :area_hectares, soil_type_id = :soil_type_id,
                    irrigation_type_id = :irrigation_type_id, updated_at = NOW()
                WHERE farm_id = :farm_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "farmer_id"          => $farmer_id,
            "farm_name"          => $farm_name,
            "location"           => $location,
            "area_hectares"      => $area_hectares,
            "soil_type_id"       => $soil_type_id,
            "irrigation_type_id" => $irrigation_type_id,
            "farm_id"            => $farm_id
        ]);
    }

    // Soft delete: flag the record instead of removing it
    public function delete(int $farm_id) {
        $sql = "UPDATE farm SET is_deleted = TRUE, updated_at = NOW() WHERE farm_id = :farm_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["farm_id" => $farm_id]);
    }

    // TRUE when the farm still has active plantings (blocks deletion)
    public function hasActivePlantings(int $farm_id) {
        $sql = "SELECT COUNT(*) FROM planting WHERE farm_id = :farm_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farm_id" => $farm_id]);
        return $stmt->fetchColumn() > 0;
    }
}
