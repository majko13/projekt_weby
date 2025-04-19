<?php
require __DIR__ . '/classes/Database.php';
require __DIR__ . '/classes/Class.php';

session_start();

// Only allow admin users
if (!isset($_SESSION["is_logged_in"]) || $_SESSION["user_role"] !== 'admin') {
    header("Location: signin.php");
    exit;
}

if (isset($_GET["id"])) {
    $database = new Database();
    $connection = $database->connectionDB();
    
    $classId = $_GET["id"];
    ClassItem::deleteClass($connection, $classId);
    
    $database->closeConnection();
}

header("Location: classes.php");
exit;
?>