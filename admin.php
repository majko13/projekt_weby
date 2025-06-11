<?php
session_start();

// Check if user is admin
if (!isset($_SESSION["is_logged_in"]) || ($_SESSION["user_role"] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/User.php';

$database = new Database();
$connection = $database->connectionDB();

// Handle role update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_role"])) {
    $userId = $_POST["user_id"];
    $newRole = $_POST["new_role"];
    
    if (User::updateUserRole($connection, $userId, $newRole)) {
        $message = "Role updated successfully!";
    } else {
        $error = "Failed to update role.";
    }
}

// Add a link to the verification page for admin and verification users
if ($_SESSION["user_role"] === 'admin' || $_SESSION["user_role"] === 'verification') {
    $verification_link = '<div class="admin-actions">
        <a href="admin/approve-reservations.php" class="btn">Verify Pending Reservations</a>
    </div>';
}

// Add cleanup link for admin users only
if ($_SESSION["user_role"] === 'admin') {
    $cleanup_link = '<div class="admin-actions">
        <a href="admin/cleanup-reservations.php" class="btn" style="background: #dc3545; margin-left: 10px;">Database Cleanup</a>
    </div>';
}

// Get all users
$users = User::getAllUsers($connection);
$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/styles.css?v=<?= time() ?>">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="admin-panel">
            <h1>User Management</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($verification_link)) echo $verification_link; ?>
            <?php if (isset($cleanup_link)) echo $cleanup_link; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Change Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($user['id']) ?></td>
                        <td data-label="Name"><?= htmlspecialchars($user['name']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                        <td data-label="Current Role">
                            <span class="role-<?= $user['role'] ?>">
                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                            </span>
                        </td>
                        <td data-label="Change Role">
                            <form method="POST" class="role-management-form">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="new_role">
                                    <option value="readonly" <?= $user['role'] === 'readonly' ? 'selected' : '' ?>>👁️ Readonly</option>
                                    <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>👤 Customer</option>
                                    <option value="verification" <?= $user['role'] === 'verification' ? 'selected' : '' ?>>✅ Verification</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>⚙️ Admin</option>
                                </select>
                                <button type="submit" name="update_role">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>
