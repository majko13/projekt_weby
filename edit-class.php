<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/ClassItem.php';

session_start();

// Only allow admin users
if (!isset($_SESSION["is_logged_in"]) || $_SESSION["user_role"] !== 'admin') {
    header("Location: signin.php");
    exit;
}

$database = new Database();
$connection = $database->connectionDB();

// Get class to edit
$classId = $_GET["id"] ?? null;
$class = $classId ? ClassItem::getClassById($connection, $classId) : null;

if (!$class) {
    header("Location: classes.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST["name"]));
    $description = htmlspecialchars(trim($_POST["description"]));
    $capacity = filter_var($_POST["capacity"], FILTER_VALIDATE_INT);
    
    if ($name && $description && $capacity) {
        $success = ClassItem::updateClass(
            $connection,
            $classId,
            $name,
            $description,
            $capacity
        );
        
        $database->closeConnection();
        
        if ($success) {
            header("Location: classes.php");
            exit;
        }
    }
    
    $error = "Invalid input data";
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="form">
            <h1>Edit Class</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="text" name="name" value="<?= htmlspecialchars($class['name']) ?>" required>
                <textarea name="description" required><?= htmlspecialchars($class['description']) ?></textarea>
                <input type="number" name="capacity" value="<?= htmlspecialchars($class['capacity']) ?>" min="1" required>
                <button type="submit" class="btn">Update Class</button>
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>