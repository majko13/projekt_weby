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

    $user = User::authenticate($connection, $email, $password);

    if($user) {
        session_regenerate_id(true);
        
        $_SESSION["is_logged_in"] = true;
        $_SESSION["logged_in_user_id"] = $user['id'];
        $_SESSION["user_name"] = $user['name'];
        $_SESSION["user_email"] = $email;
        
        $database->closeConnection();
        Url::redirectUrl("/projekt_weby/dashboard.php");
    } else {
        $database->closeConnection();
        echo "Invalid email or password";
    }
} else {
    echo "Unauthorized access";
}
?>