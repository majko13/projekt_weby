<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';

session_start();

// Only allow admin and verification users
if (!isset($_SESSION['logged_in_user_id']) ||
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'verification')) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reservation_id'])) {
    header("Location: classes.php");
    exit;
}

$reservation_id = $_POST['reservation_id'];
$action = $_POST['action']; // 'approve' or 'reject'
$class_id = $_POST['class_id'] ?? null;

$database = new Database();
$connection = $database->connectionDB();

try {
    // Get the reservation first
    $reservation = ClassItem::getReservationById($connection, $reservation_id);

    if (!$reservation) {
        throw new Exception("Reservation not found");
    }

    if ($action === 'approve') {
        // Approve the reservation
        $success = ClassItem::updateReservationStatus($connection, $reservation_id, 'approved');

        if ($success) {
            $_SESSION['success'] = "Reservation approved!";

            // Delete all other pending requests for this date
            $sql = "DELETE FROM class_reservations
                    WHERE class_id = :class_id
                    AND date = :date
                    AND status = 'pending'
                    AND reservation_id != :reservation_id";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                'class_id' => $reservation['class_id'],
                'date' => $reservation['date'],
                'reservation_id' => $reservation_id
            ]);
        } else {
            $_SESSION['error'] = "Failed to approve reservation";
        }
    } else {
        // Delete the rejected reservation
        $success = ClassItem::deleteReservation($connection, $reservation_id);

        if ($success) {
            $_SESSION['success'] = "Reservation rejected and removed!";
        } else {
            $_SESSION['error'] = "Failed to reject reservation";
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

$database->closeConnection();

// Redirect back to the schedule
$redirect_url = $class_id ? "class-schedule.php?class_id=$class_id" : "classes.php";
header("Location: $redirect_url");
exit;
