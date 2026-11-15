<?php
class Farmer {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT * FROM farmer WHERE is_deleted = FALSE ORDER BY farmer_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function search(string $term) {
        $sql = "SELECT * FROM farmer
                WHERE is_deleted = FALSE
                  AND (first_name ILIKE :term OR last_name ILIKE :term OR address ILIKE :term)
                ORDER BY farmer_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["term" => "%{$term}%"]);
        return $stmt->fetchAll();
    }

    // Count of active farmers matching the optional search term (for pagination)
    public function countAll(string $term = "") {
        $sql    = "SELECT COUNT(*) FROM farmer WHERE is_deleted = FALSE";
        $params = [];
        if ($term !== "") {
            $sql .= " AND (first_name ILIKE :term OR last_name ILIKE :term OR address ILIKE :term)";
            $params["term"] = "%{$term}%";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // One page of farmers. $order comes only from the page's sort whitelist.
    public function getPage(string $term, string $order, string $dir, int $limit, int $offset) {
        $sql    = "SELECT * FROM farmer WHERE is_deleted = FALSE";
        $params = [];
        if ($term !== "") {
            $sql .= " AND (first_name ILIKE :term OR last_name ILIKE :term OR address ILIKE :term)";
            $params["term"] = "%{$term}%";
        }
        $sql .= " ORDER BY {$order} {$dir} LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Returns active farmers formatted for dropdowns; if duplicate full names exist, appends address and contact
    public function getAllForDropdown() {
        $sql = "SELECT f.*,
                       COUNT(*) OVER(PARTITION BY LOWER(TRIM(f.first_name)), LOWER(TRIM(f.last_name))) AS name_count
                FROM farmer f
                WHERE f.is_deleted = FALSE
                ORDER BY f.first_name, f.last_name, f.farmer_id";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $fullName = $row["first_name"] . " " . $row["last_name"];
            if ($row["name_count"] > 1) {
                $row["display_name"] = $fullName . " (" . $row["address"] . " · " . $row["contact_number"] . ")";
            } else {
                $row["display_name"] = $fullName;
            }
        }
        return $rows;
    }

    public function findDuplicate(string $first_name, string $last_name, string $contact_number, string $address, ?int $exclude_id = null) {
        $sql = "SELECT * FROM farmer
                WHERE LOWER(TRIM(first_name))     = LOWER(TRIM(:first_name))
                  AND LOWER(TRIM(last_name))      = LOWER(TRIM(:last_name))
                  AND LOWER(TRIM(contact_number)) = LOWER(TRIM(:contact_number))
                  AND LOWER(TRIM(address))        = LOWER(TRIM(:address))
                  AND is_deleted = FALSE";
        $params = [
            "first_name"     => $first_name,
            "last_name"      => $last_name,
            "contact_number" => $contact_number,
            "address"        => $address
        ];
        if ($exclude_id !== null) {
            $sql .= " AND farmer_id != :exclude_id";
            $params["exclude_id"] = $exclude_id;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findDeletedDuplicate(string $first_name, string $last_name, string $contact_number, string $address) {
        $sql = "SELECT * FROM farmer
                WHERE LOWER(TRIM(first_name))     = LOWER(TRIM(:first_name))
                  AND LOWER(TRIM(last_name))      = LOWER(TRIM(:last_name))
                  AND LOWER(TRIM(contact_number)) = LOWER(TRIM(:contact_number))
                  AND LOWER(TRIM(address))        = LOWER(TRIM(:address))
                  AND is_deleted = TRUE
                ORDER BY farmer_id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "first_name"     => $first_name,
            "last_name"      => $last_name,
            "contact_number" => $contact_number,
            "address"        => $address
        ]);
        return $stmt->fetch() ?: null;
    }

    public function getById(int $farmer_id) {
        $sql = "SELECT * FROM farmer WHERE farmer_id = :farmer_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farmer_id" => $farmer_id]);
        return $stmt->fetch();
    }

    public function create(string $first_name, string $last_name, string $contact_number, string $address) {
        $first_name     = trim($first_name);
        $last_name      = trim($last_name);
        $contact_number = trim($contact_number);
        $address        = trim($address);

        // Check if an active duplicate exists
        if ($this->findDuplicate($first_name, $last_name, $contact_number, $address)) {
            return false;
        }

        // If an exact soft-deleted record exists, re-activate it
        $deleted = $this->findDeletedDuplicate($first_name, $last_name, $contact_number, $address);
        if ($deleted) {
            $sql = "UPDATE farmer
                    SET is_deleted = FALSE, first_name = :first_name, last_name = :last_name,
                        contact_number = :contact_number, address = :address, updated_at = NOW()
                    WHERE farmer_id = :farmer_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "first_name"     => $first_name,
                "last_name"      => $last_name,
                "contact_number" => $contact_number,
                "address"        => $address,
                "farmer_id"      => $deleted["farmer_id"]
            ]);
            return (int) $deleted["farmer_id"];
        }

        $sql = "INSERT INTO farmer (first_name, last_name, contact_number, address)
                VALUES (:first_name, :last_name, :contact_number, :address)
                RETURNING farmer_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "first_name"     => $first_name,
            "last_name"      => $last_name,
            "contact_number" => $contact_number,
            "address"        => $address
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $farmer_id, string $first_name, string $last_name, string $contact_number, string $address) {
        $first_name     = trim($first_name);
        $last_name      = trim($last_name);
        $contact_number = trim($contact_number);
        $address        = trim($address);

        // Check if another active farmer already has identical full name, contact, and address
        if ($this->findDuplicate($first_name, $last_name, $contact_number, $address, $farmer_id)) {
            return false;
        }

        $sql = "UPDATE farmer
                SET first_name = :first_name, last_name = :last_name, contact_number = :contact_number,
                    address = :address, updated_at = NOW()
                WHERE farmer_id = :farmer_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "first_name"     => $first_name,
            "last_name"      => $last_name,
            "contact_number" => $contact_number,
            "address"        => $address,
            "farmer_id"      => $farmer_id
        ]);
    }

    // Soft delete: flag the record instead of removing it
    public function delete(int $farmer_id) {
        $sql = "UPDATE farmer SET is_deleted = TRUE, updated_at = NOW() WHERE farmer_id = :farmer_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["farmer_id" => $farmer_id]);
    }

    // TRUE when the farmer still owns active farms (blocks deletion)
    public function hasActiveFarms(int $farmer_id) {
        $sql = "SELECT COUNT(*) FROM farm WHERE farmer_id = :farmer_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farmer_id" => $farmer_id]);
        return $stmt->fetchColumn() > 0;
    }
}
