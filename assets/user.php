<?php

    function createStudent($connection, $username, $password)
    {

        $sql = "INSERT INTO users (username, password) 
            VALUES (?, ?)";
            // var_dump($sql);
            
            
            // $connection = databaseConnection();
            $database  = new Database();
            $connection = $database->connectionDB();
            $statement = $database->prepare($sql);
            //$statement = mysqli_prepare($connection, $sql);

            // if($statement===false){
            //     echo mysqli_error($connection);
            // }
            // else
            // {
            //    // mysqli_stmt_bind_param($statement, "ssiss", $first_name, $second_name, $age, $life, $college);
                

            //     if(mysqli_stmt_execute($statement)===true){

            //         $id =mysqli_insert_id($connection);
            //         Url::redirectUrl("/databaze/jeden_zak.php?Id=$id");
                    
            //         // if(isset($_SERVER["HTTPS"]) and $_SERVER!= "off"){
            //         //     $url_protocol = "https";
            //         // }else{
            //         //     $url_protocol = "http";
            //         // }
            //         // // header("location: jeden_zak.php?Id=$id");
            //         // header("location: $url_protocol://". $_SERVER["HTTP_HOST"] . "/databaze/jeden_zak.php?Id=$id");
                    
            //     }else{
            //         mysqli_stmt_execute($statement);
            //     }

                
            // }
            $statement->execute($username, $password);
    }

?>