<?php
class ClassItem {
    public static function createClass($connection, $name, $description, $capacity) {
        $sql = "INSERT INTO classes (name, description, capacity) 
                VALUES (:name, :description, :capacity)";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);
        $stmt->bindValue(":capacity", $capacity, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public static function getAllClasses($connection) {
        $sql = "SELECT * FROM classes";
        
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

    public static function isClassReserved($connection, $classId, $datetime = null) {
        if ($datetime === null) {
            $datetime = date('Y-m-d H:i:s');
        }
        
        $sql = "SELECT COUNT(*) as count FROM class_reservations 
                WHERE class_id = :class_id AND reservation_date = :reservation_date";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(":class_id", $classId, PDO::PARAM_INT);
        $stmt->bindValue(":reservation_date", $datetime, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    
    
   

    public static function isClassReservedForDate(PDO $connection, int $class_id, string $date): bool {
        $sql = "SELECT COUNT(*) FROM class_reservations 
                WHERE class_id = :class_id 
                AND DATE(date) = :date";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['class_id' => $class_id, 'date' => $date]);
        return $stmt->fetchColumn() > 0;
    }

    public static function getUserReservationCount(PDO $connection, int $user_id, int $class_id, int $month, int $year): int {
        $start_date = "$year-$month-01";
        $end_date = "$year-$month-" . cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        $sql = "SELECT COUNT(*) FROM class_reservations 
                WHERE user_id = :user_id 
                AND class_id = :class_id
                AND date BETWEEN :start_date AND :end_date";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'class_id' => $class_id,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        return (int)$stmt->fetchColumn();
    }
    
    public static function getUserReservations(PDO $connection, int $user_id): array {
        $sql = "SELECT r.*, c.name as class_name 
                FROM class_reservations r
                JOIN classes c ON r.class_id = c.class_id
                WHERE r.user_id = :user_id
                ORDER BY r.date DESC";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function createReservation($connection, $class_id, $user_id, $date) {
        try {
            $sql = "INSERT INTO class_reservations (class_id, user_id, date) 
                    VALUES (:class_id, :user_id, :date)";
            $stmt = $connection->prepare($sql);
            
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Reservation Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getClassReservations($connection, $class_id, $month, $year) {
        $start_date = "$year-$month-01";
        $end_date = date("Y-m-t", strtotime($start_date));
        
        $sql = "SELECT DATE(date) as date, user_id 
                FROM class_reservations 
                WHERE class_id = :class_id 
                AND date BETWEEN :start_date AND :end_date";
        
        $stmt = $connection->prepare($sql);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        $stmt->execute();
        
        $reservations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservations[$row['date']] = $row;
        }
        
        return $reservations;
    }
}
?>