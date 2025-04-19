<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';

session_start();

if (!isset($_SESSION["is_logged_in"])) {
    header("Location: signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();

$classId = $_GET['id'] ?? null;
$class = $classId ? ClassItem::getClassById($connection, $classId) : null;

if (!$class) {
    header("Location: classes.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $datetime = $_POST['reservation_datetime'];
    $userId = $_SESSION['logged_in_user_id'];
    
    if (ClassItem::reserveClass($connection, $classId, $userId, $datetime)) {
        header("Location: classes.php");
        exit;
    } else {
        $error = "Failed to make reservation";
    }
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Class</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="form">
            <h1>Reserve <?= htmlspecialchars($class['name']) ?></h1>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <label for="reservation_datetime">Date and Time:</label>
                <input type="datetime-local" name="reservation_datetime" required>
                <button type="submit" class="btn">Reserve Class</button>
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>