<?php

class Database {

    public function connectionDB() {
        $db_host = "127.0.0.1";
        $db_user = "majko";
        $db_password = "majko1122";
        $db_name = "ukol";
        
        $connection = "mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=utf8";

        try {
            $db = new PDO($connection, $db_user, $db_password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
        }

    }

}
