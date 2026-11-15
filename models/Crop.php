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

    // Count of active crops matching the optional filter + search term (for pagination)
    public function countAll(int $category_id = 0, string $term = "") {
        $sql    = "SELECT COUNT(*) FROM crop WHERE is_deleted = FALSE";
        $params = [];
        if ($category_id > 0) {
            $sql .= " AND category_id = :category_id";
            $params["category_id"] = $category_id;
        }
        if ($term !== "") {
            $sql .= " AND (crop_name ILIKE :term OR variety ILIKE :term)";
            $params["term"] = "%{$term}%";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // One page of crops with lookup names. $order comes only from the page's sort whitelist.
    public function getPage(int $category_id, string $term, string $order, string $dir, int $limit, int $offset) {
        $sql = "SELECT
                    c.*,
                    cc.category_name,
                    s.season_name
                FROM crop c
                JOIN crop_categories cc ON c.category_id = cc.category_id
                JOIN seasons s ON c.season_id = s.season_id
                WHERE c.is_deleted = FALSE";
        $params = [];
        if ($category_id > 0) {
            $sql .= " AND c.category_id = :category_id";
            $params["category_id"] = $category_id;
        }
        if ($term !== "") {
            $sql .= " AND (c.crop_name ILIKE :term OR c.variety ILIKE :term)";
            $params["term"] = "%{$term}%";
        }
        $sql .= " ORDER BY {$order} {$dir} LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findDuplicate(string $crop_name, string $variety, ?int $exclude_id = null) {
        $sql = "SELECT * FROM crop
                WHERE LOWER(TRIM(crop_name)) = LOWER(TRIM(:crop_name))
                  AND LOWER(TRIM(variety))   = LOWER(TRIM(:variety))
                  AND is_deleted = FALSE";
        $params = [
            "crop_name" => $crop_name,
            "variety"   => $variety
        ];
        if ($exclude_id !== null) {
            $sql .= " AND crop_id != :exclude_id";
            $params["exclude_id"] = $exclude_id;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findDeletedDuplicate(string $crop_name, string $variety) {
        $sql = "SELECT * FROM crop
                WHERE LOWER(TRIM(crop_name)) = LOWER(TRIM(:crop_name))
                  AND LOWER(TRIM(variety))   = LOWER(TRIM(:variety))
                  AND is_deleted = TRUE
                ORDER BY crop_id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "crop_name" => $crop_name,
            "variety"   => $variety
        ]);
        return $stmt->fetch() ?: null;
    }

    public function getById(int $crop_id) {
        $sql = "SELECT * FROM crop WHERE crop_id = :crop_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["crop_id" => $crop_id]);
        return $stmt->fetch();
    }

    public function create(string $crop_name, string $variety, int $category_id, int $growing_period_days, int $season_id) {
        $crop_name = trim($crop_name);
        $variety   = trim($variety);

        if ($this->findDuplicate($crop_name, $variety)) {
            return false;
        }

        $deleted = $this->findDeletedDuplicate($crop_name, $variety);
        if ($deleted) {
            $sql = "UPDATE crop
                    SET is_deleted = FALSE, crop_name = :crop_name, variety = :variety,
                        category_id = :category_id, growing_period_days = :growing_period_days,
                        season_id = :season_id, updated_at = NOW()
                    WHERE crop_id = :crop_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "crop_name"           => $crop_name,
                "variety"             => $variety,
                "category_id"         => $category_id,
                "growing_period_days" => $growing_period_days,
                "season_id"           => $season_id,
                "crop_id"             => $deleted["crop_id"]
            ]);
            return (int) $deleted["crop_id"];
        }

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
        return (int) $stmt->fetchColumn();
    }

    public function update(int $crop_id, string $crop_name, string $variety, int $category_id, int $growing_period_days, int $season_id) {
        $crop_name = trim($crop_name);
        $variety   = trim($variety);

        if ($this->findDuplicate($crop_name, $variety, $crop_id)) {
            return false;
        }

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
