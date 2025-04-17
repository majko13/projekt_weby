<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/ClassItem.php';

session_start();

// Only allow admin and verification users
if (!isset($_SESSION["is_logged_in"]) || 
    ($_SESSION["user_role"] !== 'admin' && $_SESSION["user_role"] !== 'verification')) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $database = new Database();
    $connection = $database->connectionDB();
    
    $name = htmlspecialchars(trim($_POST["name"]));
    $description = htmlspecialchars(trim($_POST["description"]));
    $capacity = filter_var($_POST["capacity"], FILTER_VALIDATE_INT);
    
    if ($name && $description && $capacity) {
        $success = ClassItem::createClass(
            $connection,
            $name,
            $description,
            $capacity,
            $_SESSION["logged_in_user_id"]
        );
        
        $database->closeConnection();
        
        if ($success) {
            header("Location: classes.php");
            exit;
        }
    }
    
    $error = "Invalid input data";
    $database->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Class</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="form">
            <h1>Add New Class</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="text" name="name" placeholder="Class Name" required>
                <textarea name="description" placeholder="Description" required></textarea>
                <input type="number" name="capacity" placeholder="Capacity" min="1" required>
                <button type="submit" class="btn">Add Class</button>
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>