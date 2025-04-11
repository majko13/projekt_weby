<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <?php if (isset($_SESSION["is_logged_in"])): ?>
            <?php if ($_SESSION["user_role"] === 'admin'): ?>
                <li><a href="admin.php">Admin Panel</a></li>
            <?php endif; ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="registration-form.php">Register</a></li>
            <li><a href="signin.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>