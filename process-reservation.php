<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';
require __DIR__ . '/classes/User.php';

session_start();

if (!isset($_SESSION["is_logged_in"]) || ($_SESSION['user_role'] !== 'customer' && $_SESSION['user_role'] !== 'verification')) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['class_id']) || !isset($_POST['date'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: classes.php");
    exit;
}

$class_id = $_POST['class_id'];
$date = $_POST['date'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$connection = $database->connectionDB();

try {
    // Verify class exists
    $class = ClassItem::getClassById($connection, $class_id);
    if (!$class) {
        $_SESSION['error'] = "Class not found";
        header("Location: classes.php");
        exit;
    }
    
    // Validate date
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        $_SESSION['error'] = "Cannot reserve a class in the past";
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }
    
    // Check if weekend
    if (date('N', strtotime($date)) >= 6) {
        $_SESSION['error'] = "Cannot reserve classes on weekends";
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }
    
    // Check if already reserved
    if (ClassItem::isClassReser<?php
    require __DIR__ . '/classes/Database.php';
    require __DIR__ . '/classes/ClassItem.php';
    require __DIR__ . '/classes/User.php';
    
    session_start();
    
    // 1. Check if user is logged in and has permission
    if (!isset($_SESSION["is_logged_in"]) || ($_SESSION['user_role'] !== 'customer' && $_SESSION['user_role'] !== 'verification')) {
        $_SESSION['error'] = "Please log in to reserve classes";
        header("Location: signin.php");
        exit;
    }
    
    // 2. Validate the request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['class_id']) || !isset($_POST['date'])) {
        $_SESSION['error'] = "Invalid reservation request";
        header("Location: classes.php");
        exit;
    }
    
    $class_id = $_POST['class_id'];
    $date = $_POST['date'];
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $connection = $database->connectionDB();
    
    try {
        // 3. Verify class exists
        $class = ClassItem::getClassById($connection, $class_id);
        if (!$class) {
            $_SESSION['error'] = "Class not found";
            header("Location: classes.php");
            exit;
        }
        
        // 4. Validate date (not past or weekend)
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            $_SESSION['error'] = "Cannot reserve classes in the past";
            header("Location: class-schedule.php?class_id=$class_id");
            exit;
        }
        
        if (date('N', strtotime($date)) >= 6) {
            $_SESSION['error'] = "Cannot reserve classes on weekends";
            header("Location: class-schedule.php?class_id=$class_id");
            exit;
        }
        
        // 5. Check availability
        if (ClassItem::isClassReservedForDate($connection, $class_id, $date)) {
            $_SESSION['error'] = "This class is already booked for the selected date";
            header("Location: class-schedule.php?class_id=$class_id");
            exit;
        }
        
        // 6. Create reservation
        $sql = "INSERT INTO reservations (class_id, user_id, reservation_date, created_at) 
                VALUES (:class_id, :user_id, :reservation_date, NOW())";
        $stmt = $connection->prepare($sql);
        $success = $stmt->execute([
            'class_id' => $class_id,
            'user_id' => $user_id,
            'reservation_date' => $date
        ]);
        
        if ($success) {
            $_SESSION['success'] = "Successfully reserved " . htmlspecialchars($class['name']) . " for " . date('F j, Y', strtotime($date));
        } else {
            $_SESSION['error'] = "Failed to make reservation";
        }
        
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }vedForDate($connection, $class_id, $date)) {
        $_SESSION['error'] = "This class is already reserved for the selected date";
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }
    
    // Check if user has already reserved this class too many times this month
    $reservationCount = ClassItem::getUserReservationCount($connection, $user_id, $class_id, date('m'), date('Y'));
    if ($reservationCount >= 3) { // Limit to 3 reservations per class per month
        $_SESSION['error'] = "You've reached the maximum reservations for this class this month";
        header("Location: class-schedule.php?class_id=$class_id");
        exit;
    }
    
    // Make reservation
    $sql = "INSERT INTO reservations (class_id, user_id, reservation_date, created_at) 
            VALUES (:class_id, :user_id, :reservation_date, NOW())";
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        'class_id' => $class_id,
        'user_id' => $user_id,
        'reservation_date' => $date
    ]);
    
    $_SESSION['success'] = "Class successfully reserved for " . date('F j, Y', strtotime($date));
    header("Location: class-schedule.php?class_id=$class_id");
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error making reservation: " . $e->getMessage();
    header("Location: class-schedule.php?class_id=$class_id");
    exit;
}