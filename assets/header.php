<?php
$userRole = $_SESSION['user_role'] ?? 'guest';
$isLoggedIn = $_SESSION['is_logged_in'] ?? false;
?>

<nav>
    <ul>
        <?php if ($isLoggedIn): ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <?php if ($userRole === 'admin'): ?>
                <li><a href="admin.php">Admin Panel</a></li>
            <?php endif; ?>
        <?php else: ?>
            <li><a href="index.php">Home</a></li>
            <li><a href="registration-form.php">Register</a></li>
            <li><a href="signin.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>