<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';
require __DIR__ . '/classes/User.php';

session_start();

if (!isset($_SESSION["is_logged_in"]) || ($_SESSION['user_role'] !== 'customer' && $_SESSION['user_role'] !== 'verification')) {
    header("Location: signin.php");
    exit;
}

if (!isset($_GET['class_id']) || !isset($_GET['date'])) {
    header("Location: classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$date = $_GET['date'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$connection = $database->connectionDB();

try {
    // Check if class exists
    $class = ClassItem::getClassById($connection, $class_id);
    if (!$class) {
        header("Location: classes.php");
        exit;
    }
    
    // Check if date is valid and in the future
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        $_SESSION['error'] = "Cannot reserve a class in the past";
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }
    
    // Check if already reserved
    $is_reserved = ClassItem::isClassReservedForDate($connection, $class_id, $date);
    if ($is_reserved) {
        $_SESSION['error'] = "This class is already reserved for the selected date";
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }
    
    // Make reservation
    $sql = "INSERT INTO reservations (class_id, user_id, reservation_date) 
            VALUES (:class_id, :user_id, :reservation_date)";
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        'class_id' => $class_id,
        'user_id' => $user_id,
        'reservation_date' => $date
    ]);
    
    $_SESSION['success'] = "Class successfully reserved!";
    header("Location: class-schedule.php?class_id=$class_id");
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error making reservation: " . $e->getMessage();
    header("Location: class-schedule.php?class_id=$class_id");
    exit;
}