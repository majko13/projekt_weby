<?php
require "./classes/Database.php";
require "./classes/Url.php";
require "./classes/User.php";

session_start();

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $database = new Database();
    $connection = $database->connectionDB();

    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    $userId = User::authenticate($connection, $email, $password);

    if($userId) {
        session_regenerate_id(true);
        
        // Get user info
        $user = User::getUserById($connection, $userId);
        
        $_SESSION["is_logged_in"] = true;
        $_SESSION["logged_in_user_id"] = $userId;
        $_SESSION["user_name"] = $user['first_name'] . " " . $user['second_name'];
        
        $database->closeConnection();
        Url::redirectUrl("/dashboard.php");
    } else {
        $database->closeConnection();
        echo "Invalid email or password";
    }
} else {
    echo "Unauthorized access";
}
?>