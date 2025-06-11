<?php
require __DIR__ . '/../classes/Database.php';
require __DIR__ . '/../classes/Class.php';

session_start();

// Only allow admin users
if (!isset($_SESSION['logged_in_user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();

$message = '';
$error = '';
$deleted_count = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cleanup_type = $_POST['cleanup_type'];
        
        switch ($cleanup_type) {
            case 'before_date':
                $date = $_POST['before_date'];
                if ($date) {
                    $count = ClassItem::countReservationsBeforeDate($connection, $date);
                    if ($count > 0) {
                        $success = ClassItem::deleteReservationsBeforeDate($connection, $date);
                        if ($success) {
                            $deleted_count = $count;
                            $message = "Successfully deleted $deleted_count reservations before $date";
                        } else {
                            $error = "Failed to delete reservations";
                        }
                    } else {
                        $message = "No reservations found before $date";
                    }
                } else {
                    $error = "Please select a date";
                }
                break;
                
            case 'after_date':
                $date = $_POST['after_date'];
                if ($date) {
                    $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date > :date";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute(['date' => $date]);
                    $count = $stmt->fetch()['count'];
                    
                    if ($count > 0) {
                        $success = ClassItem::deleteReservationsAfterDate($connection, $date);
                        if ($success) {
                            $deleted_count = $count;
                            $message = "Successfully deleted $deleted_count reservations after $date";
                        } else {
                            $error = "Failed to delete reservations";
                        }
                    } else {
                        $message = "No reservations found after $date";
                    }
                } else {
                    $error = "Please select a date";
                }
                break;
                
            case 'on_date':
                $date = $_POST['on_date'];
                if ($date) {
                    $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE DATE(date) = :date";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute(['date' => $date]);
                    $count = $stmt->fetch()['count'];
                    
                    if ($count > 0) {
                        $success = ClassItem::deleteReservationsOnDate($connection, $date);
                        if ($success) {
                            $deleted_count = $count;
                            $message = "Successfully deleted $deleted_count reservations on $date";
                        } else {
                            $error = "Failed to delete reservations";
                        }
                    } else {
                        $message = "No reservations found on $date";
                    }
                } else {
                    $error = "Please select a date";
                }
                break;
                
            case 'date_range':
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                if ($start_date && $end_date) {
                    $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date BETWEEN :start_date AND :end_date";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
                    $count = $stmt->fetch()['count'];
                    
                    if ($count > 0) {
                        $success = ClassItem::deleteReservationsInDateRange($connection, $start_date, $end_date);
                        if ($success) {
                            $deleted_count = $count;
                            $message = "Successfully deleted $deleted_count reservations between $start_date and $end_date";
                        } else {
                            $error = "Failed to delete reservations";
                        }
                    } else {
                        $message = "No reservations found in the specified date range";
                    }
                } else {
                    $error = "Please select both start and end dates";
                }
                break;
                
            case 'older_than_days':
                $days = $_POST['days'];
                if ($days && is_numeric($days)) {
                    $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date < DATE_SUB(NOW(), INTERVAL :days DAY)";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute(['days' => $days]);
                    $count = $stmt->fetch()['count'];
                    
                    if ($count > 0) {
                        $success = ClassItem::deleteReservationsOlderThanDays($connection, $days);
                        if ($success) {
                            $deleted_count = $count;
                            $message = "Successfully deleted $deleted_count reservations older than $days days";
                        } else {
                            $error = "Failed to delete reservations";
                        }
                    } else {
                        $message = "No reservations found older than $days days";
                    }
                } else {
                    $error = "Please enter a valid number of days";
                }
                break;
                
            case 'by_status':
                $status = $_POST['status'];
                $before_date = $_POST['status_before_date'] ?? null;
                
                if ($status) {
                    if ($before_date) {
                        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE status = :status AND date < :date";
                        $stmt = $connection->prepare($sql);
                        $stmt->execute(['status' => $status, 'date' => $before_date]);
                        $count = $stmt->fetch()['count'];
                        
                        if ($count > 0) {
                            $success = ClassItem::deleteReservationsByStatus($connection, $status, $before_date);
                            if ($success) {
                                $deleted_count = $count;
                                $message = "Successfully deleted $deleted_count $status reservations before $before_date";
                            } else {
                                $error = "Failed to delete reservations";
                            }
                        } else {
                            $message = "No $status reservations found before $before_date";
                        }
                    } else {
                        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE status = :status";
                        $stmt = $connection->prepare($sql);
                        $stmt->execute(['status' => $status]);
                        $count = $stmt->fetch()['count'];
                        
                        if ($count > 0) {
                            $success = ClassItem::deleteReservationsByStatus($connection, $status);
                            if ($success) {
                                $deleted_count = $count;
                                $message = "Successfully deleted $deleted_count $status reservations";
                            } else {
                                $error = "Failed to delete reservations";
                            }
                        } else {
                            $message = "No $status reservations found";
                        }
                    }
                } else {
                    $error = "Please select a status";
                }
                break;
                
            default:
                $error = "Invalid cleanup type selected";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get current statistics
$total_reservations = 0;
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

try {
    $sql = "SELECT COUNT(*) as total FROM class_reservations";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $total_reservations = $stmt->fetch()['total'];
    
    $sql = "SELECT status, COUNT(*) as count FROM class_reservations GROUP BY status";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        switch ($row['status']) {
            case 'pending':
                $pending_count = $row['count'];
                break;
            case 'approved':
                $approved_count = $row['count'];
                break;
            case 'rejected':
                $rejected_count = $row['count'];
                break;
        }
    }
} catch (Exception $e) {
    $error = "Error getting statistics: " . $e->getMessage();
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Reservations - Admin Panel</title>
    <link rel="stylesheet" href="../assets/styles.css?v=<?= time() ?>">
    <style>
        .cleanup-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cleanup-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .cleanup-options {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .cleanup-options.active {
            display: block;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <?php require "../assets/header.php"; ?>
    
    <div class="cleanup-container">
        <h1>Database Cleanup - Class Reservations</h1>
        
        <?php if ($message): ?>
            <div class="success-message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <strong>⚠️ Warning:</strong> This action will permanently delete reservations from the database. 
            This action cannot be undone. Please make sure you have a backup before proceeding.
        </div>
        
        <h2>Current Database Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_reservations ?></div>
                <div>Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $pending_count ?></div>
                <div>Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $approved_count ?></div>
                <div>Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $rejected_count ?></div>
                <div>Rejected</div>
            </div>
        </div>
        
        <form method="POST" class="cleanup-form" onsubmit="return confirm('Are you sure you want to delete these reservations? This action cannot be undone!');">
            <div class="form-group">
                <label for="cleanup_type">Select Cleanup Type:</label>
                <select name="cleanup_type" id="cleanup_type" required onchange="showCleanupOptions()">
                    <option value="">-- Select cleanup type --</option>
                    <option value="before_date">Delete reservations before a specific date</option>
                    <option value="after_date">Delete reservations after a specific date</option>
                    <option value="on_date">Delete reservations on a specific date</option>
                    <option value="date_range">Delete reservations in a date range</option>
                    <option value="older_than_days">Delete reservations older than X days</option>
                    <option value="by_status">Delete reservations by status</option>
                </select>
            </div>
            
            <div id="before_date_options" class="cleanup-options">
                <div class="form-group">
                    <label for="before_date">Delete all reservations before this date:</label>
                    <input type="date" name="before_date" id="before_date">
                </div>
            </div>
            
            <div id="after_date_options" class="cleanup-options">
                <div class="form-group">
                    <label for="after_date">Delete all reservations after this date:</label>
                    <input type="date" name="after_date" id="after_date">
                </div>
            </div>
            
            <div id="on_date_options" class="cleanup-options">
                <div class="form-group">
                    <label for="on_date">Delete all reservations on this date:</label>
                    <input type="date" name="on_date" id="on_date">
                </div>
            </div>
            
            <div id="date_range_options" class="cleanup-options">
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date">
                </div>
            </div>
            
            <div id="older_than_days_options" class="cleanup-options">
                <div class="form-group">
                    <label for="days">Delete reservations older than (days):</label>
                    <input type="number" name="days" id="days" min="1" placeholder="e.g., 30">
                </div>
            </div>
            
            <div id="by_status_options" class="cleanup-options">
                <div class="form-group">
                    <label for="status">Status to delete:</label>
                    <select name="status" id="status">
                        <option value="">-- Select status --</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status_before_date">Only delete if before this date (optional):</label>
                    <input type="date" name="status_before_date" id="status_before_date">
                </div>
            </div>
            
            <button type="submit" class="btn-danger">Delete Reservations</button>
            <a href="../admin.php" class="btn-secondary">Back to Admin Panel</a>
        </form>
    </div>
    
    <script>
        function showCleanupOptions() {
            // Hide all options
            const options = document.querySelectorAll('.cleanup-options');
            options.forEach(option => option.classList.remove('active'));
            
            // Show selected option
            const selectedType = document.getElementById('cleanup_type').value;
            if (selectedType) {
                const targetOption = document.getElementById(selectedType + '_options');
                if (targetOption) {
                    targetOption.classList.add('active');
                }
            }
        }
    </script>
    
    <?php require "../assets/footer.php"; ?>
</body>
</html>
