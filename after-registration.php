<?php
    
    require "./classes/Database.php";
    require "./assets/user.php";

    $username = null;
    $password = null;
    $role =  null;
    $created_at = null;

    if($_SERVER["REQUEST_METHOD"]==="POST"){

        
        $username = $_POST["username"];
        $password = $_POST["password"];

        // $errors = [];
        // if($_POST["first_name"]===""){
        //     $errors[] = "Křestni jmeno je povinne";
        // }
        // if($_POST["second_name"]===""){
        //     $errors[] = "Prijmeni je povinne";
        // }
        // if(empty( $errors[])){

        // $connection = databaseConnection();
        $database  = new Database();
        $connection = $database->connectionDB();

        createStudent($connection, $username,$password);
        
        // }
        // $result  = mysqli_query($connection, $sql);
        
        // if($result === false)
        // {
        //     echo mysqli_error($connection);
        // }
        // else
        // {
        //     $id =mysqli_insert_id($connection);
            
        //     echo "Uspesne vlozeno zak = ".$id;
        // }
    }

?>