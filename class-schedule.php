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
    $user_id = $_SESSION['logged_in_user_id'];

    $database = new Database();
    $connection = $database->connectionDB();

    $existing = ClassItem::getReservationStatus($connection, $class_id, $date);
    if ($existing === 'approved') {
        $_SESSION['error'] = "This date is already booked";
        header("Location: class-schedule.php?class_id=" . $class_id);
        exit;
    }
    
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
        $success = ClassItem::createReservation($connection, $class_id, $user_id, $date, 'pending');
        
        if ($success) {
            $_SESSION['success'] = "Reservation requested! Waiting for approval.";
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
            cursor: pointer;
            transition: all 0.2s;
        }
        .day-cell:hover {
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
            transform: translateY(-2px);
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
            cursor: not-allowed;
        }
        .past-day {
            background-color: #f9f9f9;
            color: #ccc;
            cursor: not-allowed;
        }
        .weekend-day {
            background-color: #fff3cd;
            cursor: not-allowed;
        }
        .reservation-form {
            margin-top: 5px;
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
        .day-cell.past-day:hover, 
        .day-cell.reservation-booked:hover,
        .day-cell.weekend-day:hover {
            box-shadow: none;
            transform: none;
        }
        .pending-approval {
            background-color: #fff3cd;
        }
        .requested-by-other {
            background-color: #ffeeba;
        }
        .approval-form {
            margin-top: 5px;
        }
        .approve-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            width: 100%;
            font-size: 0.8em;
        }
        .approve-btn:hover {
            background: #218838;
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
                <!-- Day headers -->
                <div class="day-header">Monday</div>
                <div class="day-header">Tuesday</div>
                <div class="day-header">Wednesday</div>
                <div class="day-header">Thursday</div>
                <div class="day-header">Friday</div>
                <div class="day-header">Saturday</div>
                <div class="day-header">Sunday</div>
                
                <?php
                // Empty cells for days before the first day of the month
                for ($i = 1; $i < $first_day; $i++) {
                    echo '<div class="day-cell empty-cell"></div>';
                }
                
                // Days of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $is_past = strtotime($current_date) < strtotime(date('Y-m-d'));
                    $is_reserved = isset($reservations[$current_date]);
                    $is_weekend = date('N', strtotime($current_date)) >= 6;
                    $reserved_by_me = false;
                    $status = '';
                    
                    if ($is_reserved) {
                        $status = $reservations[$current_date]['status'];
                        if (isset($_SESSION['user_id'])) {
                            $reserved_by_me = ($reservations[$current_date]['user_id'] == $_SESSION['user_id']);
                        }
                    }
                    
                    $cell_class = 'day-cell';
                    if ($is_past) {
                        $cell_class .= ' past-day';
                    } elseif ($is_reserved) {
                        if ($status === 'approved') {
                            $cell_class .= ' reservation-booked';
                        } elseif ($status === 'pending') {
                            if ($reserved_by_me) {
                                $cell_class .= ' pending-approval';
                            } else {
                                $cell_class .= ' requested-by-other';
                            }
                        }
                    } elseif ($is_weekend) {
                        $cell_class .= ' weekend-day';
                    } else {
                        $cell_class .= ' reservation-available';
                    }
                    
                    echo '<div class="' . $cell_class . '">';
                    echo '<div class="day-number">' . $day . '</div>';
                    
                    // Reservation status display
                    if ($is_reserved) {
                        if ($status === 'approved') {
                            echo '<div class="reservation-info">Booked</div>';
                        } elseif ($status === 'pending') {
                            if ($reserved_by_me) {
                                echo '<div class="reservation-info">Pending approval</div>';
                            } else {
                                echo '<div class="reservation-info">Requested by someone else</div>';
                            }
                            
                            // Show approve button for admin/verification
                            if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification') {
                                echo '<form method="POST" action="approve-reservation.php" class="approval-form">';
                                echo '<input type="hidden" name="reservation_id" value="' . $reservations[$current_date]['reservation_id'] . '">';
                                echo '<input type="hidden" name="class_id" value="' . $class_id . '">';
                                echo '<button type="submit" name="action" value="approve" class="approve-btn">Approve</button>';
                                echo '</form>';
                            }
                        }
                    } elseif ($is_weekend) {
                        echo '<div class="reservation-info">Closed</div>';
                    }
                    
                    // Show reserve button only if date is available
                    if (!$is_past && !$is_reserved && !$is_weekend && 
                        ($_SESSION['user_role'] === 'customer' || $_SESSION['user_role'] === 'admin')) {
                        echo '<form method="POST" action="class-schedule.php" class="reservation-form">';
                        echo '<input type="hidden" name="class_id" value="' . $class_id . '">';
                        echo '<input type="hidden" name="reserve_date" value="' . $current_date . '">';
                        echo '<button type="submit" class="reserve-btn">Request Reservation</button>';
                        echo '</form>';
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
    
    <script>
        // Add click handler for available days
        document.querySelectorAll('.reservation-available').forEach(cell => {
            cell.addEventListener('click', function() {
                const form = this.querySelector('form');
                if (form) {
                    if (confirm('Are you sure you want to request this class on ' + this.querySelector('.day-number').textContent + '?')) {
                        form.submit();
                    }
                }
            });
        });
    </script>
</body>
</html>