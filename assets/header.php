<?php
$userRole = $_SESSION['user_role'] ?? 'guest';
$isLoggedIn = $_SESSION['is_logged_in'] ?? false;
?>

<header class="app-header">
    <div class="header-container">
        <div class="header-brand">
            <h1>📅 Rezervace Tříd</h1>
            <p class="header-subtitle">Systém rezervací učeben</p>
        </div>

        <nav class="header-nav">
            <ul class="nav-list">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <span class="nav-icon">🏠</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="classes.php" class="nav-link">
                            <span class="nav-icon">📚</span>
                            Classes
                        </a>
                    </li>
                    <?php if ($userRole === 'admin'): ?>
                        <li class="nav-item">
                            <a href="admin.php" class="nav-link">
                                <span class="nav-icon">⚙️</span>
                                Admin Panel
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item user-info">
                        <span class="user-role"><?= ucfirst($userRole) ?></span>
                        <a href="logout.php" class="nav-link logout">
                            <span class="nav-icon">🚪</span>
                            Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <span class="nav-icon">🏠</span>
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="registration-form.php" class="nav-link">
                            <span class="nav-icon">📝</span>
                            Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="signin.php" class="nav-link">
                            <span class="nav-icon">🔑</span>
                            Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>