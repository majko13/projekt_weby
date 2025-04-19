<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';
require __DIR__ . '/classes/User.php';

session_start();

if (!isset($_SESSION["is_logged_in"])) {
    header("Location: signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();

try {
    $classes = ClassItem::getAllClasses($connection) ?? [];
} catch (PDOException $e) {
    $classes = [];
    $error = "Error loading classes: " . $e->getMessage();
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class List</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .class-list { max-width: 800px; margin: 0 auto; }
        .class-card { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 5px;
            transition: all 0.3s;
        }
        .class-card:hover {
            background-color: #f5f5f5;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .class-card h3 { margin-top: 0; }
    </style>
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="class-list">
            <h1>Available Classes</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'verification'): ?>
                <a href="add-class.php" class="btn">Add New Class</a>
            <?php endif; ?>

            <div class="classes-container">
                <?php if (!empty($classes)): ?>
                    <?php foreach ($classes as $class): ?>
                    <div class="class-card">
                        <h3><?= htmlspecialchars($class['name']) ?></h3>
                        <p><?= htmlspecialchars($class['description']) ?></p>
                        <a href="class-schedule.php?class_id=<?= $class['class_id'] ?>" class="btn">View Schedule</a>
                        
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <div class="class-actions">
                                <a href="edit-class.php?id=<?= $class['class_id'] ?>" class="btn">Edit</a>
                                <a href="delete-class.php?id=<?= $class['class_id'] ?>" class="btn danger">Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No classes available at the moment.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>