<?php
class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT user_id, username, full_name, role, created_at FROM users ORDER BY user_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function findByUsername(string $username) {
        $sql = "SELECT * FROM users WHERE username = :username AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["username" => $username]);
        return $stmt->fetch();
    }

    public function create(string $username, string $password, string $full_name, string $role) {
        $sql = "INSERT INTO users (username, password_hash, full_name, role)
                VALUES (:username, :password_hash, :full_name, :role)
                RETURNING user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "username"      => $username,
            "password_hash" => password_hash($password, PASSWORD_DEFAULT),
            "full_name"     => $full_name,
            "role"          => $role
        ]);
        return $stmt->fetchColumn();
    }
}
