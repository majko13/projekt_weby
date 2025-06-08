<?php






?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace Tříd - Class Reservation System</title>
    <link rel="stylesheet" href="assets/styles.css?v=<?= time() ?>">
</head>
<body>
    <?php require "assets/header.php" ?>

    <main>
        <section class="homepage">
            <div class="page-header">
                <h1>🎓 Welcome to Class Reservation System</h1>
                <p class="description">Professional classroom booking and management platform</p>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <h3>📚 Easy Booking</h3>
                    <p>Reserve classrooms with our intuitive calendar interface. View availability in real-time and book instantly.</p>
                    <a href="classes.php" class="btn">Browse Classes</a>
                </div>

                <div class="feature-card">
                    <h3>📅 Smart Calendar</h3>
                    <p>Visual calendar system with color-coded availability. See all your reservations at a glance.</p>
                    <a href="signin.php" class="btn">Get Started</a>
                </div>

                <div class="feature-card">
                    <h3>⚡ Real-time Updates</h3>
                    <p>Instant notifications and status updates. Never miss important reservation changes.</p>
                    <a href="registration-form.php" class="btn success">Sign Up</a>
                </div>
            </div>

            <div class="cta-section">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of users who trust our platform for their classroom reservations.</p>
                <div class="cta-buttons">
                    <a href="registration-form.php" class="btn success">Create Account</a>
                    <a href="signin.php" class="btn">Sign In</a>
                </div>
            </div>
        </section>
    </main>

    <?php require "assets/footer.php" ?>

</body>
</html>