<?php
class User {
    public static function createUser($connection, $name, $email, $password) {
        $sql = "INSERT INTO users (name, email, password, role) 
                VALUES (:name, :email, :password, 'readonly')";
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            
            return $connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public static function authenticate($connection, $email, $password) {
        $sql = "SELECT id, name, email, role, password FROM users WHERE email = :email";
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                return [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public static function getAllUsers($connection) {
        $sql = "SELECT id, name, email, role FROM users";
        try {
            $stmt = $connection->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            return false;
        }
    }

    public static function updateUserRole($connection, $userId, $newRole) {
        $allowedRoles = ['admin', 'readonly', 'customer', 'verification'];
        if (!in_array($newRole, $allowedRoles)) {
            return false;
        }

        $sql = "UPDATE users SET role = :role WHERE id = :id";
        try {
            $stmt = $connection->prepare($sql);
            return $stmt->execute([':role' => $newRole, ':id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error updating role: " . $e->getMessage());
            return false;
        }
    }

    public static function getUserById($connection, $userId) {
        $sql = "SELECT name, email, role FROM users WHERE id = :id";
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute([':id' => $userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return false;
        }
    }
}
?>