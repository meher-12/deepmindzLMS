<?php
class Auth {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    // Login method
    public function login($name, $password) {
        $user = User::findByName($this->conn, $name);
        return $user && $user->verifyPassword($password);
    }
}
?>