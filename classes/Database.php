<?php
class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;

    public function __construct() {
        $this->host = '127.0.0.1';
        $this->username = 'majko'; // Change to your DB username
        $this->password = 'majko1122'; // Change to your DB password
        $this->database = 'ukol'; // Change to your DB name
    }

    public function connectionDB() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            return $this->connection;
            
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function closeConnection() {
        $this->connection = null;
    }
}
?>
