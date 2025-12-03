<?php
if(!defined('DB_SERVER')){
    require_once("../initialize.php");
}
class DBConnection{

    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            
            $this->conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if ($this->conn->connect_error) {
                die('Cannot connect to database server: ' . $this->conn->connect_error);
            }            
        }    
        
    }
    public function __destruct(){
        $this->conn->close();
    }
}
?>