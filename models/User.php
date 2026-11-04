<?php
class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $sql = "SELECT user_id, username, full_name, role, created_at
                FROM users WHERE is_deleted = FALSE ORDER BY user_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function findByUsername(string $username) {
        $sql = "SELECT * FROM users WHERE username = :username AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["username" => $username]);
        return $stmt->fetch();
    }

    public function getById(int $user_id) {
        $sql = "SELECT user_id, username, full_name, role FROM users
                WHERE user_id = :user_id AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
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

    // Update profile fields; password stays unchanged when $password is blank
    public function update(int $user_id, string $full_name, string $role, string $password = "") {
        if ($password !== "") {
            $sql = "UPDATE users
                    SET full_name = :full_name, role = :role,
                        password_hash = :password_hash, updated_at = NOW()
                    WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                "full_name"     => $full_name,
                "role"          => $role,
                "password_hash" => password_hash($password, PASSWORD_DEFAULT),
                "user_id"       => $user_id
            ]);
        }
        $sql = "UPDATE users SET full_name = :full_name, role = :role, updated_at = NOW()
                WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "full_name" => $full_name,
            "role"      => $role,
            "user_id"   => $user_id
        ]);
    }

    // Change own password (profile page)
    public function updatePassword(int $user_id, string $password) {
        $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW()
                WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "password_hash" => password_hash($password, PASSWORD_DEFAULT),
            "user_id"       => $user_id
        ]);
    }

    // Soft delete (deactivate): the user can no longer log in
    public function delete(int $user_id) {
        $sql = "UPDATE users SET is_deleted = TRUE, updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["user_id" => $user_id]);
    }

    // TRUE when a username is already taken by an active user
    public function usernameExists(string $username) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username AND is_deleted = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["username" => $username]);
        return $stmt->fetchColumn() > 0;
    }

    // Number of active admin accounts (used to protect the last admin)
    public function countActiveAdmins() {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_deleted = FALSE";
        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetchColumn();
    }
}
