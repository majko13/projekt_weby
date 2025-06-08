<?php
require __DIR__ . '/../classes/Database.php';
require __DIR__ . '/../classes/Class.php';

session_start();

// Only allow admin and verification users
if (!isset($_SESSION['logged_in_user_id']) ||
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'verification')) {
    header("Location: ../signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    try {
        if ($action === 'approve') {
            // Get the reservation first to get class_id and date
            $reservation = ClassItem::getReservationById($connection, $reservation_id);

            if (!$reservation) {
                throw new Exception("Reservation not found");
            }

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

    header("Location: approve-reservations.php");
    exit;
}

// Get pending reservations
$pending_reservations = ClassItem::getPendingReservations($connection);

$database->closeConnection();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Reservations</title>
    <link rel="stylesheet" href="../assets/styles.css?v=<?= time() ?>">
</head>
<body>
    <?php require "../assets/header.php"; ?>

    <main>
        <section class="admin-panel">
            <h1>Pending Reservations</h1>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (empty($pending_reservations)): ?>
                <p>No pending reservations</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>User</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_reservations as $reservation): ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['class_name']) ?></td>
                            <td><?= htmlspecialchars($reservation['user_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($reservation['date'])) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p><a href="../admin.php" class="btn">Back to Admin Panel</a></p>
        </section>
    </main>

    <?php require "../assets/footer.php"; ?>
</body>
</html>
