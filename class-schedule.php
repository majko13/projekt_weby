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

        // Check if already reserved or approved
        $month = date('n', strtotime($date));
        $year = date('Y', strtotime($date));
        $reservations = ClassItem::getClassReservations($connection, $class_id, $month, $year);

        if (isset($reservations[$date])) {
            if ($reservations[$date]['status'] === 'approved') {
                $_SESSION['error'] = "This date is already booked and approved";
            } else {
                $_SESSION['error'] = "This date already has a pending reservation";
            }
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
            min-height: 120px;
            padding: 10px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
        }
        .day-cell:hover {
            transform: translateY(-2px);
        }
        .reservation-available:hover {
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
        }
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1em;
            position: relative;
            z-index: 10;
        }
        .reservation-booked .day-number {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-size: 1.3em;
        }
        .pending-approval .day-number,
        .requested-by-other .day-number {
            color: #333;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }
        .empty-cell {
            background: #f9f9f9;
        }
        .reservation-available {
            background: linear-gradient(135deg, #e6f7e6, #d4edda);
            border: 2px solid #28a745;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
            animation: subtle-pulse 3s ease-in-out infinite;
        }
        @keyframes subtle-pulse {
            0%, 100% {
                box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
                transform: scale(1.02);
            }
        }
        .reservation-booked {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            cursor: not-allowed;
            border: 3px solid #bd2130;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            position: relative;
            overflow: hidden;
            pointer-events: none; /* Make completely unclickable */
        }
        .reservation-booked::before {
            content: "🔒";
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 16px;
            opacity: 0.8;
        }
        .reservation-booked::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.1) 10px,
                rgba(255,255,255,0.1) 20px
            );
            animation: diagonal-stripes 20s linear infinite;
        }
        @keyframes diagonal-stripes {
            0% { transform: translateX(-50px) translateY(-50px); }
            100% { transform: translateX(50px) translateY(50px); }
        }
        .past-day {
            background-color: #f9f9f9;
            color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .weekend-day {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            cursor: not-allowed;
            border: 2px solid #dc3545;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        .reservation-form {
            margin-top: 5px;
        }
        .reservation-info {
            font-size: 0.85em;
            font-weight: bold;
            margin-top: 5px;
            padding: 3px 6px;
            border-radius: 4px;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .reservation-booked .reservation-info {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .pending-approval .reservation-info {
            background: rgba(255,255,255,0.8);
            color: #856404;
            border: 1px solid #ffc107;
        }
        .requested-by-other .reservation-info {
            background: rgba(255,255,255,0.8);
            color: #856404;
            border: 1px solid #ffc107;
        }
        .weekend-day .reservation-info {
            background: rgba(255,255,255,0.8);
            color: #721c24;
            border: 1px solid #dc3545;
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
            cursor: not-allowed;
        }
        .reservation-booked:hover {
            /* Completely disable hover effects for booked cells */
            background: linear-gradient(135deg, #dc3545, #c82333) !important;
            transform: none !important;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3) !important;
        }
        .pending-approval {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
            position: relative;
        }
        .pending-approval::before {
            content: "⏳";
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 16px;
            opacity: 0.8;
        }
        .requested-by-other {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
            position: relative;
        }
        .requested-by-other::before {
            content: "👤";
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 16px;
            opacity: 0.8;
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
            pointer-events: auto; /* Ensure admin buttons work even on unclickable cells */
            position: relative;
            z-index: 10;
        }
        .approve-btn:hover {
            background: #218838;
        }
        .view-day-btn {
            display: block;
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            text-align: center;
            margin-top: 5px;
            font-size: 0.8em;
            transition: background-color 0.2s;
            pointer-events: auto; /* Ensure admin buttons work even on unclickable cells */
            position: relative;
            z-index: 10;
        }
        .view-day-btn:hover {
            background: #0056b3;
            text-decoration: none;
        }
        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            font-size: 0.85em;
            text-align: center;
            min-width: 120px;
        }
        .legend-color {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 2px solid #ddd;
            position: relative;
        }
        .legend-available {
            background: linear-gradient(135deg, #e6f7e6, #d4edda);
            border: 2px solid #28a745;
        }
        .legend-booked {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: 2px solid #bd2130;
        }
        .legend-booked::after {
            content: "🔒";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }
        .legend-pending {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
        }
        .legend-pending::after {
            content: "⏳";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }
        .legend-other {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
        }
        .legend-other::after {
            content: "👤";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }
        .legend-weekend {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid #dc3545;
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

            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <h3 style="text-align: center; margin-bottom: 15px; color: #333;">Calendar Status Guide</h3>
                <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <div class="legend-item">
                        <div class="legend-color legend-available"></div>
                        <span><strong>🟢 Available</strong><br><small>No reservation</small></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-booked"></div>
                        <span><strong>🔴 Booked</strong><br><small>Status: approved</small></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-pending"></div>
                        <span><strong>🟡 Your Pending</strong><br><small>Status: pending</small></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-other"></div>
                        <span><strong>🟡 Other's Request</strong><br><small>Status: pending</small></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-weekend"></div>
                        <span><strong>🔴 Weekend/Closed</strong><br><small>Not available</small></span>
                    </div>
                </div>
            </div>

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
                        if (isset($_SESSION['logged_in_user_id'])) {
                            $reserved_by_me = ($reservations[$current_date]['user_id'] == $_SESSION['logged_in_user_id']);
                        }
                    }

                    $cell_class = 'day-cell';
                    if ($is_past) {
                        $cell_class .= ' past-day';
                    } elseif ($is_reserved) {
                        // Check database status and apply appropriate class
                        if ($status === 'approved') {
                            $cell_class .= ' reservation-booked'; // RED - Booked
                        } elseif ($status === 'pending') {
                            if ($reserved_by_me) {
                                $cell_class .= ' pending-approval'; // YELLOW - Your Pending
                            } else {
                                $cell_class .= ' requested-by-other'; // YELLOW - Other's Request
                            }
                        }
                        // Note: 'rejected' status reservations are deleted from DB, so won't appear here
                    } elseif ($is_weekend) {
                        $cell_class .= ' weekend-day'; // RED - Weekend/Closed
                    } else {
                        $cell_class .= ' reservation-available'; // GREEN - Available
                    }

                    echo '<div class="' . $cell_class . '">';
                    echo '<div class="day-number">' . $day . '</div>';

                    // Reservation status display
                    if ($is_reserved) {
                        // Display status based on database value
                        if ($status === 'approved') {
                            echo '<div class="reservation-info">Booked</div>';
                        } elseif ($status === 'pending') {
                            if ($reserved_by_me) {
                                echo '<div class="reservation-info">Pending approval</div>';
                            } else {
                                echo '<div class="reservation-info">Requested by someone else</div>';
                            }
                        }

                        // Show approve button for admin/verification (only for pending status)
                        if ($status === 'pending' && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification')) {
                            echo '<form method="POST" action="approve-reservation.php" class="approval-form">';
                            echo '<input type="hidden" name="reservation_id" value="' . $reservations[$current_date]['reservation_id'] . '">';
                            echo '<input type="hidden" name="class_id" value="' . $class_id . '">';
                            echo '<button type="submit" name="action" value="approve" class="approve-btn">Approve</button>';
                            echo '</form>';
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

                    // Show "View Day" button for admin and verification roles
                    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification') {
                        echo '<a href="day-reservations.php?class_id=' . $class_id . '&date=' . $current_date . '" class="view-day-btn">View Day</a>';
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
        // Add click handler for available days only
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

        // Prevent any clicks on booked cells
        document.querySelectorAll('.reservation-booked').forEach(cell => {
            cell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        });

        // Add visual feedback for unclickable cells
        document.querySelectorAll('.reservation-booked, .weekend-day, .past-day').forEach(cell => {
            cell.style.userSelect = 'none'; // Prevent text selection
        });
    </script>
</body>
</html>