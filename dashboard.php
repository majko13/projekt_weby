<?php



session_start();

// Include required classes at the top of the file
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/User.php';
require __DIR__ . '/classes/Url.php';

// At the top of dashboard.php
if ($_SESSION["user_role"] === 'readonly') {
    // Show limited functionality
    echo "<p>Your account is readonly. Please contact admin for full access.</p>";
} elseif ($_SESSION["user_role"] === 'customer') {
    // Show customer features
} // etc.

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
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h1>
            <p>You are successfully registered and logged in.</p>
            <a href="logout.php" class="btn">Logout</a>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>