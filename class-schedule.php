<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';
require __DIR__ . '/classes/User.php';

session_start();

if (!isset($_SESSION["is_logged_in"])) {
    header("Location: signin.php");
    exit;
}

// Handle form submission for reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_date'])) {
    $class_id = $_POST['class_id'];
    $date = $_POST['reserve_date'];
    $user_id = $_SESSION['user_id'];

    $database = new Database();
    $connection = $database->connectionDB();

    try {
        // Check if date is valid and in the future
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            $_SESSION['error'] = "Cannot reserve past dates";
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
        $month = date('n', strtotime($date));
        $year = date('Y', strtotime($date));
        $reservations = ClassItem::getClassReservations($connection, $class_id, $month, $year);
        
        if (isset($reservations[$date])) {
            $_SESSION['error'] = "This date is already booked";
            header("Location: class-schedule.php?class_id=" . $class_id);
            exit;
        }

        // Create reservation
        $success = ClassItem::createReservation($connection, $class_id, $user_id, $date);

        if ($success) {
            $_SESSION['success'] = "Reservation created successfully for " . date('F j, Y', strtotime($date));
        } else {
            $_SESSION['error'] = "Failed to create reservation";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    $database->closeConnection();
    header("Location: class-schedule.php?class_id=" . $class_id);
    exit;
}

// Rest of your original code for displaying the calendar...
if (!isset($_GET['class_id'])) {
    header("Location: classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$database = new Database();
$connection = $database->connectionDB();

try {
    $class = ClassItem::getClassById($connection, $class_id);
    if (!$class) {
        header("Location: classes.php");
        exit;
    }
    
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day = date('N', strtotime("$year-$month-01"));
    $reservations = ClassItem::getClassReservations($connection, $class_id, $month, $year);
    
} catch (PDOException $e) {
    $error = "Error loading class schedule: " . $e->getMessage();
}

$database->closeConnection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> Schedule</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .calendar {
            max-width: 1000px;
            margin: 0 auto;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .day-header {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            background: #f0f0f0;
        }
        .day-cell {
            border: 1px solid #ddd;
            min-height: 100px;
            padding: 5px;
            position: relative;
        }
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .empty-cell {
            background: #f9f9f9;
        }
        .reservation-available {
            background: #e6f7e6;
        }
        .reservation-booked {
            background: #ffebeb;
        }
        .past-day {
            background-color: #f9f9f9;
            color: #ccc;
        }
        .weekend-day {
            background-color: #fff3cd;
        }
        .reservation-form {
            margin-top: 5px;
        }
        .reserve-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            width: 100%;
        }
        .reserve-btn:hover {
            background: #45a049;
        }
        .reservation-info {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
        .month-nav {
            display: flex;
            gap: 20px;
        }

        .day-cell {
    border: 1px solid #ddd;
    min-height: 100px;
    padding: 5px;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.day-cell:hover {
    box-shadow: 0 0 5px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

.day-cell.past-day, 
.day-cell.reservation-booked,
.day-cell.weekend-day {
    cursor: not-allowed;
}

.day-cell.past-day:hover, 
.day-cell.reservation-booked:hover,
.day-cell.weekend-day:hover {
    box-shadow: none;
    transform: none;
}

.reservation-link {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: block;
    text-decoration: none;
}
    </style>
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="calendar">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="calendar-header">
                <h1><?= htmlspecialchars($class['name']) ?> - Schedule</h1>
                <p><?= htmlspecialchars($class['description']) ?></p>
            </div>
            
            <div class="calendar-header">
                <h2><?= date('F Y', strtotime("$year-$month-01")) ?></h2>
                <div class="month-nav">
                    <?php
                    $prev_month = $month - 1;
                    $prev_year = $year;
                    if ($prev_month < 1) {
                        $prev_month = 12;
                        $prev_year--;
                    }
                    
                    $next_month = $month + 1;
                    $next_year = $year;
                    if ($next_month > 12) {
                        $next_month = 1;
                        $next_year++;
                    }
                    ?>
                    <a href="class-schedule.php?class_id=<?= $class_id ?>&month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn">Previous Month</a>
                    <a href="class-schedule.php?class_id=<?= $class_id ?>&month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn">Current Month</a>
                    <a href="class-schedule.php?class_id=<?= $class_id ?>&month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn">Next Month</a>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="calendar-grid">
    <!-- Day headers remain the same -->
    <div class="day-header">Monday</div>
    <div class="day-header">Tuesday</div>
    <div class="day-header">Wednesday</div>
    <div class="day-header">Thursday</div>
    <div class="day-header">Friday</div>
    <div class="day-header">Saturday</div>
    <div class="day-header">Sunday</div>
    
    <?php
    // Empty cells for days before the first day of the month
    for ($day = 1; $day <= $days_in_month; $day++) {
        $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $is_past = strtotime($current_date) < strtotime(date('Y-m-d'));
        $is_reserved = isset($reservations[$current_date]);
        $is_weekend = date('N', strtotime($current_date)) >= 6;
        $reserved_by_me = false;
        
        if ($is_reserved && isset($_SESSION['user_id'])) {
            $reserved_by_me = ($reservations[$current_date]['user_id'] == $_SESSION['user_id']);
        }
        
        $cell_class = 'day-cell';
        if ($is_past) {
            $cell_class .= ' past-day';
        } elseif ($is_reserved) {
            $cell_class .= ' reservation-booked';
        } elseif ($is_weekend) {
            $cell_class .= ' weekend-day';
        } else {
            $cell_class .= ' reservation-available';
        }
        
        echo '<div class="' . $cell_class . '">';
        echo '<div class="day-number">' . $day . '</div>';
        
        if (!$is_past && !$is_reserved && !$is_weekend && 
            ($_SESSION['user_role'] === 'customer' || $_SESSION['user_role'] === 'admin')) {
            echo '<form method="POST" action="class-schedule.php" class="reservation-form">';
            echo '<input type="hidden" name="class_id" value="' . $class_id . '">';
            echo '<input type="hidden" name="reserve_date" value="' . $current_date . '">';
            echo '<button type="submit" class="reserve-btn">Reserve</button>';
            echo '</form>';
        }
        
        if ($is_reserved) {
            echo '<div class="reservation-info">' . ($reserved_by_me ? 'Booked by you' : 'Booked') . '</div>';
        }
        
        if ($is_weekend) {
            echo '<div class="reservation-info">Closed</div>';
        }
        
        echo '</div>';
        
        // Break to new row after Sunday
        if (($day + $first_day - 1) % 7 == 0 && $day != $days_in_month) {
            echo '</div><div class="calendar-grid">';
        }
    }
    
    // Empty cells after last day of month to complete the grid
    $last_day = date('N', strtotime("$year-$month-$days_in_month"));
    for ($i = $last_day; $i < 7; $i++) {
        echo '<div class="day-cell empty-cell"></div>';
    }
    ?>
</div>
        </section>

        
    </main>

    <?php require "assets/footer.php"; ?>
    
</body>
</html>