<?php
require __DIR__ . '/../classes/Database.php';
require __DIR__ . '/../classes/Class.php';

session_start();

// Only allow admin and verification users
if (!isset($_SESSION['user_id']) || 
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
    
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    
    $sql = "UPDATE class_reservations SET status = :status WHERE reservation_id = :id";
    $stmt = $connection->prepare($sql);
    $success = $stmt->execute(['status' => $new_status, 'id' => $reservation_id]);
    
    if ($success) {
        $_SESSION['success'] = "Reservation $new_status!";
    } else {
        $_SESSION['error'] = "Failed to update reservation";
    }
    
    header("Location: approve-reservations.php");
    exit;
}

// Get pending reservations
$pending_reservations = Class::getPendingReservations($connection);

// Add this method to your Class.php
/*
public static function getPendingReservations($connection) {
    $sql = "SELECT r.*, c.name as class_name, u.name as user_name 
            FROM class_reservations r
            JOIN classes c ON r.class_id = c.class_id
            JOIN users u ON r.user_id = u.id
            WHERE r.status = 'pending'";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
*/
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Reservations</title>
</head>
<body>
    <h1>Pending Reservations</h1>
    
    <?php if (empty($pending_reservations)): ?>
        <p>No pending reservations</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Class</th>
                <th>User</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($pending_reservations as $reservation): ?>
            <tr>
                <td><?= htmlspecialchars($reservation['class_name']) ?></td>
                <td><?= htmlspecialchars($reservation['user_name']) ?></td>
                <td><?= date('M j, Y', strtotime($reservation['date'])) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                        <button type="submit" name="action" value="approve">Approve</button>
                        <button type="submit" name="action" value="reject">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>