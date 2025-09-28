<?php
// config/database.php

class Database
{
    private $host = 'localhost';
    private $db_name = 'constituency_system'; // Replace with your actual database name
    private $username = 'root'; // Replace with your actual database username
    private $password = ''; // Replace with your actual database password
    public $conn;

    public function getConnection()
    {
    
    
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}







