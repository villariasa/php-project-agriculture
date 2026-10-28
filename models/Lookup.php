<?php
class Lookup {
    private PDO $pdo;

    // Whitelist of all lookup tables. SQL identifiers come ONLY from here.
    private array $config = [
        "soil_types" => [
            "label"   => "Soil Types",
            "pk"      => "soil_type_id",
            "name"    => "soil_type_name",
            "used_in" => ["farm", "soil_type_id"]
        ],
        "irrigation_types" => [
            "label"   => "Irrigation Types",
            "pk"      => "irrigation_type_id",
            "name"    => "irrigation_type_name",
            "used_in" => ["farm", "irrigation_type_id"]
        ],
        "crop_categories" => [
            "label"   => "Crop Categories",
            "pk"      => "category_id",
            "name"    => "category_name",
            "used_in" => ["crop", "category_id"]
        ],
        "seasons" => [
            "label"   => "Seasons",
            "pk"      => "season_id",
            "name"    => "season_name",
            "used_in" => ["crop", "season_id"]
        ],
        "planting_status" => [
            "label"   => "Planting Status",
            "pk"      => "status_id",
            "name"    => "status_name",
            "used_in" => ["planting", "status_id"]
        ],
        "quality_grades" => [
            "label"   => "Quality Grades",
            "pk"      => "grade_id",
            "name"    => "grade_name",
            "used_in" => ["harvest", "grade_id"]
        ],
        "harvest_remarks" => [
            "label"   => "Harvest Remarks",
            "pk"      => "remark_id",
            "name"    => "remark_description",
            "used_in" => ["harvest", "remark_id"]
        ],
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getTypes() {
        $types = [];
        foreach ($this->config as $key => $cfg) {
            $types[$key] = $cfg["label"];
        }
        return $types;
    }

    public function isValidType(string $type) {
        return isset($this->config[$type]);
    }

    public function getLabel(string $type) {
        return $this->config[$type]["label"];
    }

    // Returns active rows as generic [id, name] pairs for dropdowns and lists
    public function getAll(string $type) {
        $cfg = $this->config[$type];
        $sql = "SELECT {$cfg['pk']} AS id, {$cfg['name']} AS name
                FROM {$type}
                WHERE is_deleted = FALSE
                ORDER BY {$cfg['pk']}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(string $type, int $id) {
        $cfg = $this->config[$type];
        $sql = "SELECT {$cfg['pk']} AS id, {$cfg['name']} AS name
                FROM {$type}
                WHERE {$cfg['pk']} = :id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->fetch();
    }

    public function create(string $type, string $name) {
        $cfg = $this->config[$type];
        $sql = "INSERT INTO {$type} ({$cfg['name']}) VALUES (:name) RETURNING {$cfg['pk']}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["name" => $name]);
        return $stmt->fetchColumn();
    }

    public function update(string $type, int $id, string $name) {
        $cfg = $this->config[$type];
        $sql = "UPDATE {$type} SET {$cfg['name']} = :name WHERE {$cfg['pk']} = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["name" => $name, "id" => $id]);
    }

    // Soft delete: flag the row instead of removing it
    public function delete(string $type, int $id) {
        $cfg = $this->config[$type];
        $sql = "UPDATE {$type} SET is_deleted = TRUE WHERE {$cfg['pk']} = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["id" => $id]);
    }

    // TRUE when an active record still references this lookup value
    public function isInUse(string $type, int $id) {
        [$table, $column] = $this->config[$type]["used_in"];
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->fetchColumn() > 0;
    }

    // Convenience for the harvest module
    public function getRemarks() {
        return $this->getAll("harvest_remarks");
    }
}
