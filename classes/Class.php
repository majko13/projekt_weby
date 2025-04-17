<?php
class ClassItem {
    public static function createClass($connection, $name, $description, $capacity, $createdBy) {
        $sql = "INSERT INTO classes (name, description, capacity, created_by) 
                VALUES (:name, :description, :capacity, :created_by)";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);
        $stmt->bindValue(":capacity", $capacity, PDO::PARAM_INT);
        $stmt->bindValue(":created_by", $createdBy, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public static function getAllClasses($connection) {
        $sql = "SELECT c.*, u.name as creator_name 
                FROM classes c
                JOIN users u ON c.created_by = u.id";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getClassById($connection, $classId) {
        $sql = "SELECT * FROM classes WHERE class_id = :class_id";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(":class_id", $classId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateClass($connection, $classId, $name, $description, $capacity) {
        $sql = "UPDATE classes 
                SET name = :name, description = :description, capacity = :capacity
                WHERE class_id = :class_id";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);
        $stmt->bindValue(":capacity", $capacity, PDO::PARAM_INT);
        $stmt->bindValue(":class_id", $classId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public static function deleteClass($connection, $classId) {
        $sql = "DELETE FROM classes WHERE class_id = :class_id";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(":class_id", $classId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
?>