<?php
session_start();

// Include required classes at the top of the file
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/User.php';
require __DIR__ . '/classes/Url.php';

// Redirect to login if not authenticated
if (!isset($_SESSION["is_logged_in"]) || !$_SESSION["is_logged_in"]) {
    header("Location: signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();
$user = User::getUserById($connection, $_SESSION["logged_in_user_id"]);
$database->closeConnection();

if (!$user) {
    session_destroy();
    header("Location: signin.php");
    exit;
}

// Set role in session if not already set
$_SESSION['role'] = $user['role'] ?? 'readonly';

// Role is set and will be used for popup modal if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/styles.css?v=<?= time() ?>">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="dashboard">
            <div class="page-header">
                <h1>🎉 Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h1>
                <p class="description">You are successfully logged in to the Class Reservation System</p>
            </div>



            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>📚 Your Classes</h3>
                    <p>View and manage your class reservations</p>
                    <a href="classes.php" class="btn">View Classes</a>
                </div>

                <div class="dashboard-card">
                    <h3>👤 Account Info</h3>
                    <p><strong>Role:</strong> <?= ucfirst($_SESSION["role"] ?? 'customer') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION["user_email"] ?? 'N/A') ?></p>
                </div>

                <?php if ($_SESSION["role"] === 'admin'): ?>
                <div class="dashboard-card">
                    <h3>⚙️ Admin Panel</h3>
                    <p>Manage users, classes, and reservations</p>
                    <a href="admin.php" class="btn">Admin Panel</a>
                </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <h3>🚪 Session</h3>
                    <p>Manage your login session</p>
                    <a href="logout.php" class="btn danger">Logout</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Read-only Account Popup Modal -->
    <?php if ($_SESSION["role"] === 'readonly'): ?>
    <div id="readonlyModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-title">⚠️ Limited Access Account</div>
            <div class="modal-message">
                <p><strong>Your account is read-only.</strong></p>
                <p>You can view content but cannot make reservations or modifications.</p>
                <p>Please contact an administrator for full access privileges.</p>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-confirm" id="closeReadonlyModal">
                    ✓ I Understand
                </button>
            </div>
        </div>
    </div>

    <script>
        // Show read-only popup on page load
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('readonlyModal');
            const closeBtn = document.getElementById('closeReadonlyModal');

            // Show modal
            modal.style.display = 'flex';

            // Close modal when button is clicked
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
    <?php endif; ?>

    <?php require "assets/footer.php"; ?>
</body>
</html>