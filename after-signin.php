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
        $_SESSION["user_role"] = $user['role'] ?? 'readonly';
        
        $database->closeConnection();
        Url::redirectUrl("/projekt_weby/dashboard.php");
    } else {
        $database->closeConnection();
        Url::redirectUrl("/projekt_weby/signin.php?error=invalid_credentials");
    }
} else {
    Url::redirectUrl("/projekt_weby/signin.php?error=unauthorized");
}
?>