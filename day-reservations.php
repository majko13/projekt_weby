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
    <link rel="stylesheet" href="assets/styles.css?v=<?= time() ?>">

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
                                <form method="POST" id="approveForm_<?= $reservation['reservation_id'] ?>">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="button" class="btn approve-btn"
                                            data-reservation-id="<?= $reservation['reservation_id'] ?>"
                                            data-user-name="<?= htmlspecialchars($reservation['user_name']) ?>"
                                            data-action="approve">Approve</button>
                                </form>
                                <form method="POST" id="rejectForm_<?= $reservation['reservation_id'] ?>">
                                    <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="button" class="btn danger reject-btn"
                                            data-reservation-id="<?= $reservation['reservation_id'] ?>"
                                            data-user-name="<?= htmlspecialchars($reservation['user_name']) ?>"
                                            data-action="reject">Reject</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- Approval Confirmation Modal -->
    <div id="approvalModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-title" id="modalTitle">Confirm Action</div>
            <div class="modal-message" id="modalMessage">
                Are you sure you want to perform this action?
            </div>
            <div class="modal-buttons">
                <button class="modal-btn" id="confirmAction">
                    Confirm
                </button>
                <button class="modal-btn modal-btn-cancel" id="cancelAction">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <?php require "assets/footer.php"; ?>

    <script>
        let currentForm = null;
        const modal = document.getElementById('approvalModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmAction');
        const cancelBtn = document.getElementById('cancelAction');

        // Handle approve button clicks
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reservationId = this.dataset.reservationId;
                const userName = this.dataset.userName;

                currentForm = document.getElementById(`approveForm_${reservationId}`);
                modalTitle.textContent = '✅ Approve Reservation';
                modalMessage.textContent = `Are you sure you want to approve the reservation for ${userName}?`;
                confirmBtn.textContent = '✓ Yes, Approve';
                confirmBtn.className = 'modal-btn modal-btn-confirm';

                modal.style.display = 'flex';
            });
        });

        // Handle reject button clicks
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reservationId = this.dataset.reservationId;
                const userName = this.dataset.userName;

                currentForm = document.getElementById(`rejectForm_${reservationId}`);
                modalTitle.textContent = '❌ Reject Reservation';
                modalMessage.textContent = `Are you sure you want to reject the reservation for ${userName}?`;
                confirmBtn.textContent = '✗ Yes, Reject';
                confirmBtn.className = 'modal-btn modal-btn-danger';

                modal.style.display = 'flex';
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
    </script>
</body>
</html>