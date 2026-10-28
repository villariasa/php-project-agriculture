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

    public function getById(int $farmer_id) {
        $sql = "SELECT * FROM farmer WHERE farmer_id = :farmer_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farmer_id" => $farmer_id]);
        return $stmt->fetch();
    }

    public function create(string $first_name, string $last_name, string $contact_number, string $address) {
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
        return $stmt->fetchColumn();
    }

    public function update(int $farmer_id, string $first_name, string $last_name, string $contact_number, string $address) {
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
