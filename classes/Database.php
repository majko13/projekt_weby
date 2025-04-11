<?php
class Database {
    private $host = '127.0.0.1';
    private $username = 'majko';
    private $password = 'majko1122';
    private $database = 'databaze';
    private $connection;

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