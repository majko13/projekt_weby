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

if (!isset($_GET['class_id']) || !isset($_GET['date'])) {
    header("Location: classes.php");
    exit;
}

$class_id = $_GET['class_id'];
$date = $_GET['date'];

$database = new Database();
$connection = $database->connectionDB();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    $new_status = $action === 'approve' ? 'approved' : 'rejected';

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

    // Redirect back to the same page to see updated status
    header("Location: day-reservations.php?class_id=$class_id&date=$date");
    exit;
}

// Get class details
$class = ClassItem::getClassById($connection, $class_id);
if (!$class) {
    $_SESSION['error'] = "Class not found";
    header("Location: classes.php");
    exit;
}

// Get reservations for this day
$day_reservations = ClassItem::getDayReservations($connection, $class_id, $date);

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations for <?= htmlspecialchars(date('F j, Y', strtotime($date))) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .reservation-list {
            max-width: 800px;
            margin: 0 auto;
        }
        .reservation-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .reservation-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .reservation-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .back-link {
            margin-bottom: 20px;
            display: block;
        }
    </style>
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="reservation-list">
            <a href="class-schedule.php?class_id=<?= $class_id ?>" class="back-link">&laquo; Back to Schedule</a>

            <h1>Reservations for <?= htmlspecialchars($class['name']) ?></h1>
            <h2><?= htmlspecialchars(date('F j, Y', strtotime($date))) ?></h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (empty($day_reservations)): ?>
                <p>No reservations for this day.</p>
            <?php else: ?>
                <?php foreach ($day_reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-status status-<?= $reservation['status'] ?>">
                            Status: <?= ucfirst(htmlspecialchars($reservation['status'])) ?>
                        </div>

                        <p><strong>User:</strong> <?= htmlspecialchars($reservation['user_name']) ?></p>
                        <p><strong>Requested on:</strong> <?= date('F j, Y g:i a', strtotime($reservation['created_at'])) ?></p>

                        <?php if ($reservation['status'] === 'pending'): ?>
                            <div class="reservation-actions">
                                <form method="POST">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn">Approve</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn danger">Reject</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>