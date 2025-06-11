<?php
/**
 * Command-line script for cleaning up class reservations
 * Usage examples:
 * php cleanup-reservations.php --before-date=2024-01-01
 * php cleanup-reservations.php --older-than-days=30
 * php cleanup-reservations.php --status=rejected
 * php cleanup-reservations.php --date-range=2024-01-01,2024-12-31
 */

require __DIR__ . '/../classes/Database.php';
require __DIR__ . '/../classes/Class.php';

// Parse command line arguments
$options = getopt('', [
    'before-date:',
    'after-date:',
    'on-date:',
    'date-range:',
    'older-than-days:',
    'status:',
    'status-before-date:',
    'dry-run',
    'help'
]);

if (isset($options['help']) || empty($options)) {
    showHelp();
    exit(0);
}

$database = new Database();
$connection = $database->connectionDB();

$dry_run = isset($options['dry-run']);
$deleted_count = 0;

try {
    if (isset($options['before-date'])) {
        $date = $options['before-date'];
        if (!validateDate($date)) {
            die("Error: Invalid date format. Use YYYY-MM-DD\n");
        }
        
        $count = ClassItem::countReservationsBeforeDate($connection, $date);
        echo "Found $count reservations before $date\n";
        
        if ($count > 0 && !$dry_run) {
            $success = ClassItem::deleteReservationsBeforeDate($connection, $date);
            if ($success) {
                echo "Successfully deleted $count reservations before $date\n";
                $deleted_count = $count;
            } else {
                echo "Error: Failed to delete reservations\n";
            }
        }
        
    } elseif (isset($options['after-date'])) {
        $date = $options['after-date'];
        if (!validateDate($date)) {
            die("Error: Invalid date format. Use YYYY-MM-DD\n");
        }
        
        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date > :date";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['date' => $date]);
        $count = $stmt->fetch()['count'];
        
        echo "Found $count reservations after $date\n";
        
        if ($count > 0 && !$dry_run) {
            $success = ClassItem::deleteReservationsAfterDate($connection, $date);
            if ($success) {
                echo "Successfully deleted $count reservations after $date\n";
                $deleted_count = $count;
            } else {
                echo "Error: Failed to delete reservations\n";
            }
        }
        
    } elseif (isset($options['on-date'])) {
        $date = $options['on-date'];
        if (!validateDate($date)) {
            die("Error: Invalid date format. Use YYYY-MM-DD\n");
        }
        
        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE DATE(date) = :date";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['date' => $date]);
        $count = $stmt->fetch()['count'];
        
        echo "Found $count reservations on $date\n";
        
        if ($count > 0 && !$dry_run) {
            $success = ClassItem::deleteReservationsOnDate($connection, $date);
            if ($success) {
                echo "Successfully deleted $count reservations on $date\n";
                $deleted_count = $count;
            } else {
                echo "Error: Failed to delete reservations\n";
            }
        }
        
    } elseif (isset($options['date-range'])) {
        $range = explode(',', $options['date-range']);
        if (count($range) !== 2) {
            die("Error: Date range must be in format: start_date,end_date\n");
        }
        
        $start_date = trim($range[0]);
        $end_date = trim($range[1]);
        
        if (!validateDate($start_date) || !validateDate($end_date)) {
            die("Error: Invalid date format. Use YYYY-MM-DD\n");
        }
        
        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date BETWEEN :start_date AND :end_date";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        $count = $stmt->fetch()['count'];
        
        echo "Found $count reservations between $start_date and $end_date\n";
        
        if ($count > 0 && !$dry_run) {
            $success = ClassItem::deleteReservationsInDateRange($connection, $start_date, $end_date);
            if ($success) {
                echo "Successfully deleted $count reservations in date range\n";
                $deleted_count = $count;
            } else {
                echo "Error: Failed to delete reservations\n";
            }
        }
        
    } elseif (isset($options['older-than-days'])) {
        $days = $options['older-than-days'];
        if (!is_numeric($days) || $days < 1) {
            die("Error: Days must be a positive number\n");
        }
        
        $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE date < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['days' => $days]);
        $count = $stmt->fetch()['count'];
        
        echo "Found $count reservations older than $days days\n";
        
        if ($count > 0 && !$dry_run) {
            $success = ClassItem::deleteReservationsOlderThanDays($connection, $days);
            if ($success) {
                echo "Successfully deleted $count reservations older than $days days\n";
                $deleted_count = $count;
            } else {
                echo "Error: Failed to delete reservations\n";
            }
        }
        
    } elseif (isset($options['status'])) {
        $status = $options['status'];
        $valid_statuses = ['pending', 'approved', 'rejected'];
        
        if (!in_array($status, $valid_statuses)) {
            die("Error: Status must be one of: " . implode(', ', $valid_statuses) . "\n");
        }
        
        $before_date = $options['status-before-date'] ?? null;
        
        if ($before_date) {
            if (!validateDate($before_date)) {
                die("Error: Invalid date format. Use YYYY-MM-DD\n");
            }
            
            $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE status = :status AND date < :date";
            $stmt = $connection->prepare($sql);
            $stmt->execute(['status' => $status, 'date' => $before_date]);
            $count = $stmt->fetch()['count'];
            
            echo "Found $count $status reservations before $before_date\n";
            
            if ($count > 0 && !$dry_run) {
                $success = ClassItem::deleteReservationsByStatus($connection, $status, $before_date);
                if ($success) {
                    echo "Successfully deleted $count $status reservations before $before_date\n";
                    $deleted_count = $count;
                } else {
                    echo "Error: Failed to delete reservations\n";
                }
            }
        } else {
            $sql = "SELECT COUNT(*) as count FROM class_reservations WHERE status = :status";
            $stmt = $connection->prepare($sql);
            $stmt->execute(['status' => $status]);
            $count = $stmt->fetch()['count'];
            
            echo "Found $count $status reservations\n";
            
            if ($count > 0 && !$dry_run) {
                $success = ClassItem::deleteReservationsByStatus($connection, $status);
                if ($success) {
                    echo "Successfully deleted $count $status reservations\n";
                    $deleted_count = $count;
                } else {
                    echo "Error: Failed to delete reservations\n";
                }
            }
        }
        
    } else {
        echo "Error: No valid cleanup option specified. Use --help for usage information.\n";
        exit(1);
    }
    
    if ($dry_run && $count > 0) {
        echo "\nDRY RUN: No reservations were actually deleted. Remove --dry-run to perform the deletion.\n";
    } elseif ($count === 0) {
        echo "No reservations found matching the criteria.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$database->closeConnection();

// Show final statistics
showStatistics($connection);

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function showStatistics($connection) {
    try {
        $database = new Database();
        $connection = $database->connectionDB();
        
        $sql = "SELECT COUNT(*) as total FROM class_reservations";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        $sql = "SELECT status, COUNT(*) as count FROM class_reservations GROUP BY status";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = $row['count'];
        }
        
        echo "\n=== Current Database Statistics ===\n";
        echo "Total reservations: $total\n";
        echo "Pending: {$stats['pending']}\n";
        echo "Approved: {$stats['approved']}\n";
        echo "Rejected: {$stats['rejected']}\n";
        
        $database->closeConnection();
    } catch (Exception $e) {
        echo "Error getting statistics: " . $e->getMessage() . "\n";
    }
}

function showHelp() {
    echo "Class Reservations Cleanup Script\n";
    echo "=================================\n\n";
    echo "Usage: php cleanup-reservations.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --before-date=YYYY-MM-DD        Delete reservations before specified date\n";
    echo "  --after-date=YYYY-MM-DD         Delete reservations after specified date\n";
    echo "  --on-date=YYYY-MM-DD            Delete reservations on specified date\n";
    echo "  --date-range=START,END          Delete reservations in date range\n";
    echo "  --older-than-days=N             Delete reservations older than N days\n";
    echo "  --status=STATUS                 Delete reservations by status (pending/approved/rejected)\n";
    echo "  --status-before-date=YYYY-MM-DD Optional: only delete status reservations before this date\n";
    echo "  --dry-run                       Show what would be deleted without actually deleting\n";
    echo "  --help                          Show this help message\n\n";
    echo "Examples:\n";
    echo "  php cleanup-reservations.php --before-date=2024-01-01\n";
    echo "  php cleanup-reservations.php --older-than-days=30\n";
    echo "  php cleanup-reservations.php --status=rejected\n";
    echo "  php cleanup-reservations.php --date-range=2024-01-01,2024-12-31\n";
    echo "  php cleanup-reservations.php --status=pending --status-before-date=2024-01-01\n";
    echo "  php cleanup-reservations.php --older-than-days=30 --dry-run\n\n";
    echo "Note: Use --dry-run to preview what will be deleted before running the actual cleanup.\n";
}
?>
