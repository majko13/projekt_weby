<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';
require __DIR__ . '/classes/User.php';

session_start();

// Redirect if not logged in
if (!isset($_SESSION["is_logged_in"])) {
    header("Location: signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();
$classes = ClassItem::getAllClasses($connection);
$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class List</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="class-list">
            <h1>Available Classes</h1>
            
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification'): ?>
                <a href="add-class.php" class="btn">Add New Class</a>
            <?php endif; ?>

            <div class="class-grid">
                <?php foreach ($classes as $class): ?>
                <div class="class-card">
                    <h3><?= htmlspecialchars($class['name']) ?></h3>
                    <p><?= htmlspecialchars($class['description']) ?></p>
                    <p>Capacity: <?= htmlspecialchars($class['capacity']) ?></p>
                    <p>Created by: <?= htmlspecialchars($class['creator_name']) ?></p>
                    
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div class="class-actions">
                            <a href="edit-class.php?id=<?= $class['class_id'] ?>" class="btn">Edit</a>
                            <a href="delete-class.php?id=<?= $class['class_id'] ?>" class="btn danger">Delete</a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>