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

    public static function getClassReservations($connection, $class_id, $month, $year) {
        $sql = "SELECT reservation_id, class_id, user_id, DATE(date) as reservation_date, status
                FROM class_reservations
                WHERE class_id = :class_id
                AND MONTH(date) = :month
                AND YEAR(date) = :year
                AND status != 'rejected'";
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'class_id' => $class_id,
            'month' => $month,
            'year' => $year
        ]);

        $reservations = [];
        while ($row = $stmt->fetch()) {
            $reservations[$row['reservation_date']] = $row;
        }

        return $reservations;
    }

    public static function createReservation($connection, $class_id, $user_id, $date, $status = 'pending') {
        $sql = "INSERT INTO class_reservations (class_id, user_id, date, status)
                VALUES (:class_id, :user_id, :date, :status)";
        $stmt = $connection->prepare($sql);
        return $stmt->execute([
            'class_id' => $class_id,
            'user_id' => $user_id,
            'date' => $date,
            'status' => $status
        ]);
    }

    public static function getReservationStatus($connection, $class_id, $date) {
        $sql = "SELECT status FROM class_reservations
                WHERE class_id = :class_id AND date = :date
                ORDER BY status = 'approved' DESC LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['class_id' => $class_id, 'date' => $date]);
        $result = $stmt->fetch();
        return $result ? $result['status'] : null;
    }

    public static function getPendingReservations($connection) {
        $sql = "SELECT r.*, c.name as class_name, u.name as user_name
                FROM class_reservations r
                JOIN classes c ON r.class_id = c.class_id
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'pending'";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getReservationById($connection, $reservation_id) {
        $sql = "SELECT * FROM class_reservations WHERE reservation_id = :id";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['id' => $reservation_id]);
        return $stmt->fetch();
    }

    public static function updateReservationStatus($connection, $reservation_id, $status) {
        $sql = "UPDATE class_reservations SET status = :status WHERE reservation_id = :id";
        $stmt = $connection->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id' => $reservation_id
        ]);
    }

    public static function getDayReservations($connection, $class_id, $date) {
        $sql = "SELECT r.*, u.name as user_name, r.created_at
                FROM class_reservations r
                JOIN users u ON r.user_id = u.id
                WHERE r.class_id = :class_id
                AND DATE(r.date) = :date
                ORDER BY r.created_at ASC";
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'class_id' => $class_id,
            'date' => $date
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteReservation($connection, $reservation_id) {
        $sql = "DELETE FROM class_reservations WHERE reservation_id = :id";
        $stmt = $connection->prepare($sql);
        return $stmt->execute(['id' => $reservation_id]);
    }

    // Database cleanup methods for reservations
    public static function deleteReservationsBeforeDate($connection, $date) {
        $sql = "DELETE FROM class_reservations WHERE date < :date";
        $stmt = $connection->prepare($sql);
        return $stmt->execute(['date' => $date]);
    }

    public static function deleteReservationsAfterDate($connection, $date) {
        $sql = "DELETE FROM class_reservations WHERE date > :date";
        $stmt = $connection->prepare($sql);
        return $stmt->execute(['date' => $date]);
    }

    public static function deleteReservationsOnDate($connection, $date) {
        $sql = "DELETE FROM class_reservations WHERE DATE(date) = :date";
        $stmt = $connection->prepare($sql);
        return $stmt->execute(['date' => $date]);
    }

    public static function deleteReservationsInDateRange($connection, $start_date, $end_date) {
        $sql = "DELETE FROM class_reservations WHERE date BETWEEN :start_date AND :end_date";
        $stmt = $connection->prepare($sql);
        return $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    public static function deleteReservationsOlderThanDays($connection, $days) {
        $sql = "DELETE FROM class_reservations WHERE date < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $connection->prepare($sql);
        return $stmt->execute(['days' => $days]);
    }

    public static function deleteReservationsByStatus($connection, $status, $before_date = null) {
        if ($before_date) {
            $sql = "DELETE FROM class_reservations WHERE status = :status AND date < :date";
            $stmt = $connection->prepare($sql);
            return $stmt->execute(['status' => $status, 'date' => $before_date]);
        } else {
            $sql = "DELETE FROM class_reservations WHERE status = :status";
            $stmt = $connection->prepare($sql);
            return $stmt->execute(['status' => $status]);
        }
    }

    public static function countReservationsBeforeDate($connection, $date) {
        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date < :date";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['date' => $date]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public static function getReservationsBeforeDate($connection, $date, $limit = 100) {
        $sql = "SELECT reservation_id, class_id, user_id, date, status, created_at
                FROM class_reservations
                WHERE date < :date
                ORDER BY date ASC
                LIMIT :limit";
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>
