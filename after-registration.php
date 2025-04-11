<?php
require "./classes/Database.php";
require "./classes/User.php";
require "./classes/Url.php";

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $database = new Database();
    $connection = $database->connectionDB();

    // Sanitize inputs
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    $passwordAgain = $_POST["password-again"];

    // Validate inputs
    if ($password !== $passwordAgain) {
        die("Passwords do not match");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // Create user with default 'readonly' role
    $userId = User::createUser($connection, $name, $email, $password);

    if ($userId) {
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION["is_logged_in"] = true;
        $_SESSION["logged_in_user_id"] = $userId;
        $_SESSION["user_name"] = $name;
        $_SESSION["user_email"] = $email;
        $_SESSION["user_role"] = ''; // Default role
        
        $database->closeConnection();
        header("Location: dashboard.php");
        exit;
    } else {
        $database->closeConnection();
        die("User creation failed. Email may already be in use.");
    }
} else {
    die("Unauthorized access");
}
?>