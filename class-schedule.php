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
    <link rel="stylesheet" href="assets/styles.css?v=<?= time() ?>">

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

            <div class="schedule-header">
                <h1><?= htmlspecialchars($class['name']) ?> - Schedule</h1>
                <p class="description"><?= htmlspecialchars($class['description']) ?></p>
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
                    <a href="class-schedule.php?class_id=<?= $class_id ?>&month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn">← Previous Month</a>
                    <a href="class-schedule.php?class_id=<?= $class_id ?>&month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn">📅 Current Month</a>
                    <a href="class-schedule.php?class_id=<?= $class_id ?>&month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn">Next Month →</a>
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
                    $current_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $is_past = strtotime($current_date) < strtotime(date('Y-m-d'));
                    $is_reserved = isset($reservations[$current_date]);
                    $is_weekend = date('N', strtotime($current_date)) >= 6;
                    $reserved_by_me = false;
                    $status = '';

                    // Get status from reservations array
                    if (isset($reservations[$current_date])) {
                        $status = $reservations[$current_date]['status'];
                        $is_reserved = true;
                        if (isset($_SESSION['logged_in_user_id'])) {
                            $reserved_by_me = ($reservations[$current_date]['user_id'] == $_SESSION['logged_in_user_id']);
                        }
                    }

                    // Cell class assignment - STATUS FIRST approach
                    $cell_class = 'day-cell';

                    if ($is_past) {
                        $cell_class .= ' past-day';
                    } elseif ($status === 'approved') {
                        $cell_class .= ' reservation-booked'; // RED - Approved reservation
                    } elseif ($status === 'pending') {
                        // Only show pending status to admin and verification users
                        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification') {
                            $cell_class .= ' pending-approval'; // YELLOW - Pending reservation (admin view)
                        } else {
                            $cell_class .= ' reservation-available'; // GREEN - Available (customer view)
                        }
                    } elseif ($is_weekend) {
                        $cell_class .= ' weekend-day'; // RED - Weekend/Closed
                    } else {
                        $cell_class .= ' reservation-available'; // GREEN - Available (no status)
                    }

                    echo '<div class="' . $cell_class . '">';
                    echo '<div class="day-number">' . $day . '</div>';



                    // Display status info
                    if ($status === 'approved') {
                        echo '<div class="reservation-info">Booked</div>';
                    } elseif ($status === 'pending' && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification')) {
                        echo '<div class="reservation-info">Pending</div>';
                    } elseif ($is_weekend) {
                        echo '<div class="reservation-info">Closed</div>';
                    }

                    // Reserve button - show for available days and pending days (for customers who see them as available)
                    if (!$is_past && !$is_weekend && ($_SESSION['user_role'] === 'customer' || $_SESSION['user_role'] === 'admin')) {
                        // Show button if no status OR if pending status but user is not admin/verification (they see it as available)
                        if ($status === '' || ($status === 'pending' && $_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'verification')) {
                            echo '<form method="POST" action="class-schedule.php" class="reservation-form">';
                            echo '<input type="hidden" name="class_id" value="' . $class_id . '">';
                            echo '<input type="hidden" name="reserve_date" value="' . $current_date . '">';
                            echo '<button type="submit" class="reserve-btn">Request Reservation</button>';
                            echo '</form>';
                        }
                    }

                    // Admin "View Day" button - show on green (available), red (booked), and yellow (pending) cells
                    if (($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification') &&
                        !$is_weekend && !$is_past &&
                        ($status === '' || $status === 'approved' || $status === 'pending')) {
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

            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <h3>📅 Calendar Status Guide</h3>
                <div class="legend-container">
                    <div class="legend-item">
                        <div class="legend-color legend-available"></div>
                        <span><strong>🟢 Available</strong></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-booked"></div>
                        <span><strong>🔴 Booked</strong></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-pending"></div>
                        <span><strong>🟡 Pending</strong></span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color legend-weekend"></div>
                        <span><strong>🔴 Weekend/Closed</strong></span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Reservation Confirmation Modal -->
    <div id="reservationModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-title">📅 Confirm Reservation Request</div>
            <div class="modal-message" id="modalMessage">
                Are you sure you want to request this class reservation?
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-confirm" id="confirmReservation">
                    ✓ Yes, Request
                </button>
                <button class="modal-btn modal-btn-cancel" id="cancelReservation">
                    ✗ Cancel
                </button>
            </div>
        </div>
    </div>

    <?php require "assets/footer.php"; ?>

    <script>
        let currentForm = null;
        const modal = document.getElementById('reservationModal');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmReservation');
        const cancelBtn = document.getElementById('cancelReservation');

        // Add click handler for reserve buttons specifically
        document.querySelectorAll('.reserve-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default form submission
                e.stopPropagation(); // Stop event bubbling

                const form = this.closest('form');
                const cell = this.closest('.day-cell');
                const dayNumber = cell.querySelector('.day-number').textContent;

                currentForm = form;
                modalMessage.textContent = `Are you sure you want to request this class on ${dayNumber}?`;
                modal.style.display = 'flex';
            });
        });

        // Add click handler for available days (but exclude button clicks)
        document.querySelectorAll('.reservation-available').forEach(cell => {
            cell.addEventListener('click', function(e) {
                // Don't trigger if clicking on the "View Day" button or reserve button
                if (e.target.classList.contains('view-day-btn') ||
                    e.target.closest('.view-day-btn') ||
                    e.target.classList.contains('reserve-btn') ||
                    e.target.closest('.reserve-btn')) {
                    return;
                }

                const form = this.querySelector('form');
                if (form) {
                    const dayNumber = this.querySelector('.day-number').textContent;
                    currentForm = form;
                    modalMessage.textContent = `Are you sure you want to request this class on ${dayNumber}?`;
                    modal.style.display = 'flex';
                }
            });
        });

        // Modal event handlers
        confirmBtn.addEventListener('click', function() {
            if (currentForm) {
                currentForm.submit();
            }
            modal.style.display = 'none';
        });

        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            currentForm = null;
        });

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                currentForm = null;
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
                currentForm = null;
            }
        });

        // Prevent any clicks on booked cells except for "View Day" button
        document.querySelectorAll('.reservation-booked').forEach(cell => {
            cell.addEventListener('click', function(e) {
                // Allow clicks on the "View Day" button
                if (e.target.classList.contains('view-day-btn') || e.target.closest('.view-day-btn')) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        });

        // Prevent any clicks on pending cells except for "View Day" button
        document.querySelectorAll('.pending-approval').forEach(cell => {
            cell.addEventListener('click', function(e) {
                // Allow clicks on the "View Day" button
                if (e.target.classList.contains('view-day-btn') || e.target.closest('.view-day-btn')) {
                    return;
                }

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