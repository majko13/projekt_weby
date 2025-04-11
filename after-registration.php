<?php
require "./classes/Database.php";
require "./classes/Url.php";
require "./classes/User.php";

session_start();

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $database = new Database();
    $connection = $database->connectionDB();

    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    $passwordAgain = $_POST["password-again"];

    // Validate passwords match
    if ($password !== $passwordAgain) {
        die("Passwords do not match");
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Create user
    $id = User::createUser($connection, $name, $email, $passwordHash);

    if($id) {
        session_regenerate_id(true);
        
        $_SESSION["is_logged_in"] = true;
        $_SESSION["logged_in_user_id"] = $id;
        $_SESSION["user_name"] = $name;
        $_SESSION["user_email"] = $email;
        
        $database->closeConnection();
        Url::redirectUrl("/dashboard.php");
    } else {
        $database->closeConnection();
        echo "User creation failed. Email may already be in use.";
    }
} else {
    echo "Unauthorized access";
}
?>