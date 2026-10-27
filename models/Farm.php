<?php
class Farm {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT
                    f.*,
                    fm.first_name || ' ' || fm.last_name AS owner_name
                FROM farm f
                JOIN farmer fm ON f.farmer_id = fm.farmer_id
                ORDER BY f.farm_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $farm_id) {
        $sql = "SELECT * FROM farm WHERE farm_id = :farm_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farm_id" => $farm_id]);
        return $stmt->fetch();
    }

    public function create(int $farmer_id, string $farm_name, string $location, float $area_hectares, string $soil_type, string $irrigation_type) {
        $sql = "INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type, irrigation_type)
                VALUES (:farmer_id, :farm_name, :location, :area_hectares, :soil_type, :irrigation_type)
                RETURNING farm_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "farmer_id"       => $farmer_id,
            "farm_name"       => $farm_name,
            "location"        => $location,
            "area_hectares"   => $area_hectares,
            "soil_type"       => $soil_type,
            "irrigation_type" => $irrigation_type
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $farm_id, int $farmer_id, string $farm_name, string $location, float $area_hectares, string $soil_type, string $irrigation_type) {
        $sql = "UPDATE farm
                SET farmer_id = :farmer_id, farm_name = :farm_name, location = :location,
                    area_hectares = :area_hectares, soil_type = :soil_type, irrigation_type = :irrigation_type
                WHERE farm_id = :farm_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "farmer_id"       => $farmer_id,
            "farm_name"       => $farm_name,
            "location"        => $location,
            "area_hectares"   => $area_hectares,
            "soil_type"       => $soil_type,
            "irrigation_type" => $irrigation_type,
            "farm_id"         => $farm_id
        ]);
    }

    public function delete(int $farm_id) {
        $sql = "DELETE FROM farm WHERE farm_id = :farm_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["farm_id" => $farm_id]);
    }
}
