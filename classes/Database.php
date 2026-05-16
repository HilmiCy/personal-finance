<?php
require_once dirname(__DIR__) . '/config/database.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        global $pdo;
        $this->connection = $pdo;
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>