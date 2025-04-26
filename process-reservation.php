// In process-reservation.php (simplified example)
<?php
session_start();

if (!isset($_SESSION["is_logged_in"])) {
    header("Location: signin.php");
    exit;
}

if (!isset($_GET['class_id']) || !isset($_GET['date'])) {
    header("Location: classes.php");
    exit;
}

require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';

$class_id = $_GET['class_id'];
$date = $_GET['date'];

$database = new Database();
$connection = $database->connectionDB();

try {
    // Check if the class exists
    $class = ClassItem::getClassById($connection, $class_id);
    if (!$class) {
        $_SESSION['error'] = "Class not found";
        header("Location: classes.php");
        exit;
    }
    
    // Check if date is valid and in the future
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        $_SESSION['error'] = "Cannot reserve for past dates";
        header("Location: class-schedule.php?class_id=" . $class_id);
        exit;
    }
    
    // Check if date is a weekend
    if (date('N', strtotime($date)) >= 6) {
        $_SESSION['error'] = "Cannot reserve on weekends";
        header("Location: class-schedule.php?class_id=" . $class_id);
        exit;
    }
    
    // Check if already reserved
    $reservations = ClassItem::getClassReservations($connection, $class_id, date('n', strtotime($date)), date('Y', strtotime($date)));
    if (isset($reservations[$date])) {
        $_SESSION['error'] = "This date is already booked";
        header("Location: class-schedule.php?class_id=" . $class_id);
        exit;
    }
    
    // Create reservation
    $user_id = $_SESSION['user_id'];
    $success = ClassItem::createReservation($connection, $class_id, $user_id, $date);
    
    if ($success) {
        $_SESSION['success'] = "Reservation created successfully!";
    } else {
        $_SESSION['error'] = "Failed to create reservation";
    }
    
    header("Location: class-schedule.php?class_id=" . $class_id);
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: class-schedule.php?class_id=" . $class_id);
    exit;
}