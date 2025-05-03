<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';

session_start();

// Only allow admin and verification users
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'verification')) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reservation_id']) {
    header("Location: classes.php");
    exit;
}

$reservation_id = $_POST['reservation_id'];
$action = $_POST['action']; // 'approve' or 'reject'
$class_id = $_GET['class_id'] ?? null;

$database = new Database();
$connection = $database->connectionDB();

try {
    // Get the reservation first
    $reservation = ClassItem::getReservationById($connection, $reservation_id);
    
    if (!$reservation) {
        throw new Exception("Reservation not found");
    }

    // Update status
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    $success = ClassItem::updateReservationStatus($connection, $reservation_id, $new_status);

    if ($success) {
        $_SESSION['success'] = "Reservation $new_status!";
        
        // If approved, reject all other pending requests for this date
        if ($new_status === 'approved') {
            ClassItem::rejectOtherPendingReservations($connection, $reservation['class_id'], $reservation['date']);
        }
    } else {
        $_SESSION['error'] = "Failed to update reservation";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

$database->closeConnection();

// Redirect back to the schedule
$redirect_url = $class_id ? "class-schedule.php?class_id=$class_id" : "classes.php";
header("Location: $redirect_url");
exit;