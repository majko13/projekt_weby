<?php
class User {
    public static function createUser($connection, $firstName, $secondName, $email, $password) {
        $sql = "INSERT INTO users (first_name, second_name, email, password) 
                VALUES (:first_name, :second_name, :email, :password)";
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                ':first_name' => $firstName,
                ':second_name' => $secondName,
                ':email' => $email,
                ':password' => $password
            ]);
            
            return $connection->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public static function authenticate($connection, $email, $password) {
        $sql = "SELECT id, password FROM users WHERE email = :email";
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                return $user['id'];
            }
            return false;
            
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public static function getUserById($connection, $userId) {
        $sql = "SELECT first_name, second_name FROM users WHERE id = :id";
        
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