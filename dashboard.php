<?php
session_start();

if (!isset($_SESSION["is_logged_in"]) || !$_SESSION["is_logged_in"]) {
    header("Location: signin.php");
    exit;
}

require "./classes/Database.php";
require "./classes/Url.php";

// Get fresh user data from database
$database = new Database();
$connection = $database->connectionDB();
$user = User::getUserById($connection, $_SESSION["logged_in_user_id"]);
$database->closeConnection();

if (!$user) {
    session_destroy();
    header("Location: signin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="dashboard">
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] . " " . $user['second_name']); ?>!</h1>
            <p>You are now logged in.</p>
            <p>Email: <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
            <a href="logout.php" class="btn">Logout</a>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>