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

    
    
    public static function getClassReservations(PDO $connection, int $class_id, int $month, int $year): array {
        $start_date = "$year-$month-01";
        $end_date = "$year-$month-" . cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        $sql = "SELECT DATE(reservation_date) as reservation_day 
                FROM class_reservations
                WHERE class_id = :class_id 
                AND reservation_date BETWEEN :start_date AND :end_date";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'class_id' => $class_id,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reservations = [];
        foreach ($result as $row) {
            $reservations[$row['reservation_day']] = true;
        }
        
        return $reservations;
    }

    public static function isClassReservedForDate(PDO $connection, int $class_id, string $date): bool {
        $sql = "SELECT COUNT(*) FROM reservations 
                WHERE class_id = :class_id 
                AND DATE(reservation_date) = :date";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['class_id' => $class_id, 'date' => $date]);
        return $stmt->fetchColumn() > 0;
    }
}
?>