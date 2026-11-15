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

    // Count of active farms matching the optional filter + search term (for pagination)
    public function countAll(int $irrigation_type_id = 0, string $term = "") {
        $sql    = "SELECT COUNT(*) FROM farm WHERE is_deleted = FALSE";
        $params = [];
        if ($irrigation_type_id > 0) {
            $sql .= " AND irrigation_type_id = :irrigation_type_id";
            $params["irrigation_type_id"] = $irrigation_type_id;
        }
        if ($term !== "") {
            $sql .= " AND (farm_name ILIKE :term OR location ILIKE :term)";
            $params["term"] = "%{$term}%";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // One page of farms with lookup names. $order comes only from the page's sort whitelist.
    public function getPage(int $irrigation_type_id, string $term, string $order, string $dir, int $limit, int $offset) {
        $sql = "SELECT
                    f.*,
                    fm.first_name || ' ' || fm.last_name AS owner_name,
                    st.soil_type_name,
                    it.irrigation_type_name
                FROM farm f
                JOIN farmer fm ON f.farmer_id = fm.farmer_id
                JOIN soil_types st ON f.soil_type_id = st.soil_type_id
                JOIN irrigation_types it ON f.irrigation_type_id = it.irrigation_type_id
                WHERE f.is_deleted = FALSE";
        $params = [];
        if ($irrigation_type_id > 0) {
            $sql .= " AND f.irrigation_type_id = :irrigation_type_id";
            $params["irrigation_type_id"] = $irrigation_type_id;
        }
        if ($term !== "") {
            $sql .= " AND (f.farm_name ILIKE :term OR f.location ILIKE :term)";
            $params["term"] = "%{$term}%";
        }
        $sql .= " ORDER BY {$order} {$dir} LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findDuplicate(int $farmer_id, string $farm_name, ?int $exclude_id = null) {
        $sql = "SELECT * FROM farm
                WHERE farmer_id = :farmer_id
                  AND LOWER(TRIM(farm_name)) = LOWER(TRIM(:farm_name))
                  AND is_deleted = FALSE";
        $params = [
            "farmer_id" => $farmer_id,
            "farm_name" => $farm_name
        ];
        if ($exclude_id !== null) {
            $sql .= " AND farm_id != :exclude_id";
            $params["exclude_id"] = $exclude_id;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findDeletedDuplicate(int $farmer_id, string $farm_name) {
        $sql = "SELECT * FROM farm
                WHERE farmer_id = :farmer_id
                  AND LOWER(TRIM(farm_name)) = LOWER(TRIM(:farm_name))
                  AND is_deleted = TRUE
                ORDER BY farm_id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "farmer_id" => $farmer_id,
            "farm_name" => $farm_name
        ]);
        return $stmt->fetch() ?: null;
    }

    public function getById(int $farm_id) {
        $sql = "SELECT * FROM farm WHERE farm_id = :farm_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farm_id" => $farm_id]);
        return $stmt->fetch();
    }

    public function create(int $farmer_id, string $farm_name, string $location, float $area_hectares, int $soil_type_id, int $irrigation_type_id) {
        $farm_name = trim($farm_name);
        $location  = trim($location);

        if ($this->findDuplicate($farmer_id, $farm_name)) {
            return false;
        }

        $deleted = $this->findDeletedDuplicate($farmer_id, $farm_name);
        if ($deleted) {
            $sql = "UPDATE farm
                    SET is_deleted = FALSE, location = :location, area_hectares = :area_hectares,
                        soil_type_id = :soil_type_id, irrigation_type_id = :irrigation_type_id,
                        farm_name = :farm_name, updated_at = NOW()
                    WHERE farm_id = :farm_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "location"           => $location,
                "area_hectares"      => $area_hectares,
                "soil_type_id"       => $soil_type_id,
                "irrigation_type_id" => $irrigation_type_id,
                "farm_name"          => $farm_name,
                "farm_id"            => $deleted["farm_id"]
            ]);
            return (int) $deleted["farm_id"];
        }

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
        return (int) $stmt->fetchColumn();
    }

    public function update(int $farm_id, int $farmer_id, string $farm_name, string $location, float $area_hectares, int $soil_type_id, int $irrigation_type_id) {
        $farm_name = trim($farm_name);
        $location  = trim($location);

        if ($this->findDuplicate($farmer_id, $farm_name, $farm_id)) {
            return false;
        }

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
